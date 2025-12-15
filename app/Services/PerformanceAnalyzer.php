<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Models\GradeModel;
use PDO;
use PDOException;

/**
 * Performance Analyzer Service
 * 
 * Rule-based AI system that automatically analyzes student academic performance
 * and identifies students at risk of academic failure.
 * 
 * Features:
 * - Automatic grade calculation and risk assessment
 * - Multi-factor risk scoring (grades, attendance, assignments)
 * - Trend analysis (declining performance detection)
 * - Early warning system (before failure occurs)
 */
class PerformanceAnalyzer
{
    private PDO $pdo;
    private GradeModel $gradeModel;
    
    // Risk thresholds
    private const PASSING_GRADE = 75.0;
    private const HIGH_RISK_THRESHOLD = 70.0;  // Below this is high risk
    private const MEDIUM_RISK_THRESHOLD = 75.0; // Between 70-75 is medium risk
    private const LOW_ATTENDANCE_THRESHOLD = 80.0; // Below 80% attendance is concerning
    
    public function __construct(?PDO $pdo = null)
    {
        if ($pdo === null) {
            $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
            $this->pdo = Database::connection($config['database']);
        } else {
            $this->pdo = $pdo;
        }
        
        $this->gradeModel = new GradeModel();
    }
    
    /**
     * Analyze all students for the current academic year and quarter
     * 
     * @param int|null $sectionId Optional: analyze only specific section
     * @param int|null $quarter Optional: specific quarter (defaults to current)
     * @param string|null $academicYear Optional: specific academic year
     * @return array Analysis results with risk assessments
     */
    public function analyzeAllStudents(?int $sectionId = null, ?int $quarter = null, ?string $academicYear = null): array
    {
        try {
            $academicYear = $academicYear ?? $this->gradeModel->getCurrentAcademicYear();
            $quarter = $quarter ?? $this->getCurrentQuarter();
            
            // Get all active students
            $sql = "
                SELECT DISTINCT s.id, s.user_id, s.section_id, u.name as student_name, s.lrn
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE u.status = 'active'
            ";
            
            $params = [];
            if ($sectionId !== null) {
                $sql .= " AND s.section_id = ?";
                $params[] = $sectionId;
            }
            
            $sql .= " ORDER BY s.section_id, u.name";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            foreach ($students as $student) {
                try {
                    $analysis = $this->analyzeStudent(
                        (int)$student['id'],
                        (int)$student['section_id'],
                        $quarter,
                        $academicYear
                    );
                    
                    if ($analysis) {
                        $results[] = $analysis;
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other students
                    error_log("PerformanceAnalyzer: Error analyzing student {$student['id']}: " . $e->getMessage());
                    continue;
                }
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("PerformanceAnalyzer::analyzeAllStudents error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Analyze a specific student's performance
     * 
     * @param int $studentId Student ID
     * @param int|null $sectionId Section ID (optional, will be fetched if not provided)
     * @param int|null $quarter Quarter (defaults to current)
     * @param string|null $academicYear Academic year (defaults to current)
     * @return array|null Analysis result with risk assessment
     */
    public function analyzeStudent(int $studentId, ?int $sectionId = null, ?int $quarter = null, ?string $academicYear = null): ?array
    {
        try {
            $academicYear = $academicYear ?? $this->gradeModel->getCurrentAcademicYear();
            $quarter = $quarter ?? $this->getCurrentQuarter();
            
            // Get student info
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.user_id, s.section_id, s.lrn, u.name as student_name, u.email
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ? AND u.status = 'active'
            ");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return null;
            }
            
            $sectionId = $sectionId ?? (int)($student['section_id'] ?? 0);
            
            // Get all subjects for this student
            $subjects = $this->getStudentSubjects($studentId, $sectionId);
            
            if (empty($subjects)) {
                return null; // No subjects to analyze
            }
            
            $subjectAnalyses = [];
            $overallRiskScore = 0;
            $atRiskSubjects = [];
            $totalSubjects = count($subjects);
            
            foreach ($subjects as $subject) {
                $subjectAnalysis = $this->analyzeSubjectPerformance(
                    $studentId,
                    (int)$subject['subject_id'],
                    $sectionId,
                    $quarter,
                    $academicYear
                );
                
                if ($subjectAnalysis) {
                    $subjectAnalyses[] = $subjectAnalysis;
                    $overallRiskScore += $subjectAnalysis['risk_score'];
                    
                    if ($subjectAnalysis['is_at_risk']) {
                        $atRiskSubjects[] = $subjectAnalysis;
                    }
                }
            }
            
            // Calculate overall risk (average of all subjects)
            $overallRiskScore = $totalSubjects > 0 ? ($overallRiskScore / $totalSubjects) : 0;
            
            // Get attendance analysis
            $attendanceAnalysis = $this->analyzeAttendance($studentId, $sectionId, $quarter, $academicYear);
            
            // Get attendance pattern analysis
            $attendancePatternAnalysis = null;
            try {
                $patternAnalyzer = new \Services\AttendancePatternAnalyzer($this->pdo);
                $dateRange = $this->getQuarterDateRange($quarter, (int)explode('-', $academicYear)[0]);
                if ($dateRange) {
                    $attendancePatternAnalysis = $patternAnalyzer->analyzePatterns(
                        $studentId,
                        $sectionId,
                        null, // All subjects
                        $dateRange['start'],
                        $dateRange['end']
                    );
                }
            } catch (\Exception $e) {
                error_log("PerformanceAnalyzer: Attendance pattern analysis error: " . $e->getMessage());
            }
            
            // Determine overall risk level (consider attendance patterns)
            $riskLevel = $this->calculateRiskLevel(
                $overallRiskScore,
                $atRiskSubjects,
                $attendanceAnalysis,
                $attendancePatternAnalysis
            );
            
            // Add predictive analytics for each subject
            $predictions = [];
            foreach ($subjects as $subject) {
                $prediction = $this->predictFutureGrade(
                    $studentId,
                    (int)$subject['subject_id'],
                    $quarter,
                    $academicYear
                );
                
                if ($prediction) {
                    $predictions[(int)$subject['subject_id']] = $prediction;
                }
            }
            
            return [
                'student_id' => $studentId,
                'student_name' => $student['student_name'],
                'student_email' => $student['email'] ?? null,
                'student_user_id' => (int)($student['user_id'] ?? 0),
                'lrn' => $student['lrn'] ?? null,
                'section_id' => $sectionId,
                'quarter' => $quarter,
                'academic_year' => $academicYear,
                'overall_risk_score' => round($overallRiskScore, 2),
                'risk_level' => $riskLevel,
                'is_at_risk' => $riskLevel !== 'low',
                'at_risk_subjects' => $atRiskSubjects,
                'subject_analyses' => $subjectAnalyses,
                'attendance_analysis' => $attendanceAnalysis,
                'attendance_pattern_analysis' => $attendancePatternAnalysis,
                'total_subjects' => $totalSubjects,
                'failing_subjects' => count(array_filter($subjectAnalyses, fn($s) => $s['final_grade'] !== null && $s['final_grade'] < self::PASSING_GRADE)),
                'predictions' => $predictions,
                'analyzed_at' => date('Y-m-d H:i:s'),
            ];
        } catch (PDOException $e) {
            error_log("PerformanceAnalyzer::analyzeStudent error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Analyze performance for a specific subject
     */
    private function analyzeSubjectPerformance(int $studentId, int $subjectId, int $sectionId, int $quarter, string $academicYear): ?array
    {
        try {
            // Calculate quarterly grade
            $quarterlyGrade = $this->gradeModel->calculateQuarterlyGrade(
                $studentId,
                $subjectId,
                $quarter,
                $academicYear
            );
            
            if (!$quarterlyGrade || $quarterlyGrade['final_grade'] === null) {
                // No grades yet, but still analyze attendance
                return [
                    'subject_id' => $subjectId,
                    'subject_name' => $this->getSubjectName($subjectId),
                    'final_grade' => null,
                    'ww_average' => null,
                    'pt_average' => null,
                    'qe_average' => null,
                    'attendance_average' => $quarterlyGrade['attendance_average'] ?? null,
                    'risk_score' => 0,
                    'risk_level' => 'unknown',
                    'is_at_risk' => false,
                    'reasons' => ['No grades recorded yet'],
                ];
            }
            
            $finalGrade = (float)$quarterlyGrade['final_grade'];
            $wwAvg = (float)($quarterlyGrade['ww_average'] ?? 0);
            $ptAvg = (float)($quarterlyGrade['pt_average'] ?? 0);
            $qeAvg = (float)($quarterlyGrade['qe_average'] ?? 0);
            $attendanceAvg = (float)($quarterlyGrade['attendance_average'] ?? 100);
            
            // Calculate risk score (0-100, higher = more risk)
            $riskScore = $this->calculateSubjectRiskScore($finalGrade, $wwAvg, $ptAvg, $qeAvg, $attendanceAvg);
            
            // Check for declining trend (compare with previous quarter if available)
            $trend = $this->analyzeTrend($studentId, $subjectId, $quarter, $academicYear);
            
            // Determine risk level
            $riskLevel = $this->calculateSubjectRiskLevel($finalGrade, $riskScore, $trend);
            
            // Collect risk reasons
            $reasons = [];
            if ($finalGrade < self::PASSING_GRADE) {
                $reasons[] = "Final grade ({$finalGrade}) is below passing mark (" . self::PASSING_GRADE . ")";
            }
            if ($wwAvg > 0 && $wwAvg < 70) {
                $reasons[] = "Written Work average ({$wwAvg}) is low";
            }
            if ($ptAvg > 0 && $ptAvg < 70) {
                $reasons[] = "Performance Task average ({$ptAvg}) is low";
            }
            if ($qeAvg > 0 && $qeAvg < 70) {
                $reasons[] = "Quarterly Exam score ({$qeAvg}) is low";
            }
            if ($attendanceAvg < self::LOW_ATTENDANCE_THRESHOLD) {
                $reasons[] = "Attendance ({$attendanceAvg}%) is below threshold";
            }
            if ($trend === 'declining') {
                $reasons[] = "Performance is declining compared to previous quarter";
            }
            
            // Get prediction for this subject
            $prediction = $this->predictFutureGrade($studentId, $subjectId, $quarter, $academicYear);
            
            return [
                'subject_id' => $subjectId,
                'subject_name' => $this->getSubjectName($subjectId),
                'final_grade' => round($finalGrade, 2),
                'ww_average' => round($wwAvg, 2),
                'pt_average' => round($ptAvg, 2),
                'qe_average' => round($qeAvg, 2),
                'attendance_average' => round($attendanceAvg, 2),
                'risk_score' => round($riskScore, 2),
                'risk_level' => $riskLevel,
                'is_at_risk' => $riskLevel !== 'low',
                'trend' => $trend,
                'reasons' => $reasons,
                'status' => $finalGrade >= self::PASSING_GRADE ? 'Passing' : 'Failing',
                'prediction' => $prediction, // Add prediction data
            ];
        } catch (\Exception $e) {
            error_log("PerformanceAnalyzer::analyzeSubjectPerformance error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate risk score for a subject (0-100, higher = more risk)
     */
    private function calculateSubjectRiskScore(float $finalGrade, float $wwAvg, float $ptAvg, float $qeAvg, float $attendanceAvg): float
    {
        $score = 0;
        
        // Final grade component (0-60 points)
        if ($finalGrade < self::PASSING_GRADE) {
            $score += 60; // Maximum risk if failing
        } elseif ($finalGrade < self::HIGH_RISK_THRESHOLD) {
            $score += 50; // High risk
        } elseif ($finalGrade < self::MEDIUM_RISK_THRESHOLD) {
            $score += 30; // Medium risk
        }
        
        // Component scores (0-20 points total)
        $componentRisk = 0;
        if ($wwAvg > 0 && $wwAvg < 70) $componentRisk += 7;
        if ($ptAvg > 0 && $ptAvg < 70) $componentRisk += 7;
        if ($qeAvg > 0 && $qeAvg < 70) $componentRisk += 6;
        $score += min($componentRisk, 20);
        
        // Attendance component (0-20 points)
        if ($attendanceAvg < self::LOW_ATTENDANCE_THRESHOLD) {
            $score += 20 * (1 - ($attendanceAvg / self::LOW_ATTENDANCE_THRESHOLD));
        }
        
        return min($score, 100); // Cap at 100
    }
    
    /**
     * Calculate risk level based on grade and risk score
     */
    private function calculateSubjectRiskLevel(float $finalGrade, float $riskScore, string $trend): string
    {
        if ($finalGrade < self::HIGH_RISK_THRESHOLD || $riskScore >= 70) {
            return 'high';
        } elseif ($finalGrade < self::PASSING_GRADE || $riskScore >= 40 || $trend === 'declining') {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Calculate overall risk level
     */
    private function calculateRiskLevel(
        float $overallRiskScore,
        array $atRiskSubjects,
        array $attendanceAnalysis,
        ?array $attendancePatternAnalysis = null
    ): string {
        $highRiskSubjects = count(array_filter($atRiskSubjects, fn($s) => $s['risk_level'] === 'high'));
        
        // Check attendance pattern severity
        $attendancePatternSeverity = 'low';
        if ($attendancePatternAnalysis && !empty($attendancePatternAnalysis['overall_assessment'])) {
            $attendancePatternSeverity = $attendancePatternAnalysis['overall_assessment']['severity'] ?? 'low';
        }
        
        // High risk conditions
        if ($overallRiskScore >= 70 || 
            $highRiskSubjects >= 2 || 
            $attendancePatternSeverity === 'high') {
            return 'high';
        }
        
        // Medium risk conditions
        if ($overallRiskScore >= 40 || 
            count($atRiskSubjects) >= 1 || 
            ($attendanceAnalysis['percentage'] ?? 100) < self::LOW_ATTENDANCE_THRESHOLD ||
            $attendancePatternSeverity === 'medium') {
            return 'medium';
        }
        
        return 'low';
    }
    
    /**
     * Analyze attendance for a student
     */
    private function analyzeAttendance(int $studentId, int $sectionId, int $quarter, string $academicYear): array
    {
        try {
            // Extract year from academic year
            $yearParts = explode('-', $academicYear);
            $startYear = (int)($yearParts[0] ?? date('Y'));
            
            // Determine date range for the quarter
            $dateRange = $this->getQuarterDateRange($quarter, $startYear);
            
            if (!$dateRange) {
                return ['percentage' => 100, 'total_days' => 0, 'present_days' => 0, 'status' => 'unknown'];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) AS total_days,
                    COUNT(CASE WHEN status IN ('present', 'late', 'excused') THEN 1 END) AS present_days,
                    COUNT(CASE WHEN status = 'absent' THEN 1 END) AS absent_days,
                    COUNT(CASE WHEN status = 'late' THEN 1 END) AS late_days
                FROM attendance
                WHERE student_id = ?
                  AND section_id = ?
                  AND attendance_date >= ?
                  AND attendance_date <= ?
            ");
            $stmt->execute([$studentId, $sectionId, $dateRange['start'], $dateRange['end']]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalDays = (int)($attendance['total_days'] ?? 0);
            $presentDays = (int)($attendance['present_days'] ?? 0);
            
            if ($totalDays === 0) {
                return ['percentage' => 100, 'total_days' => 0, 'present_days' => 0, 'status' => 'no_data'];
            }
            
            $percentage = round(($presentDays / $totalDays) * 100, 2);
            
            $status = 'good';
            if ($percentage < self::LOW_ATTENDANCE_THRESHOLD) {
                $status = 'poor';
            } elseif ($percentage < 90) {
                $status = 'fair';
            }
            
            return [
                'percentage' => $percentage,
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'absent_days' => (int)($attendance['absent_days'] ?? 0),
                'late_days' => (int)($attendance['late_days'] ?? 0),
                'status' => $status,
            ];
        } catch (PDOException $e) {
            error_log("PerformanceAnalyzer::analyzeAttendance error: " . $e->getMessage());
            return ['percentage' => 100, 'total_days' => 0, 'present_days' => 0, 'status' => 'error'];
        }
    }
    
    /**
     * Analyze performance trend (improving, declining, stable)
     * Enhanced with multiple data points for better accuracy
     */
    private function analyzeTrend(int $studentId, int $subjectId, int $currentQuarter, string $academicYear): string
    {
        if ($currentQuarter <= 1) {
            return 'unknown'; // No previous quarter to compare
        }
        
        try {
            // Get all available quarters for trend analysis
            $quarters = [];
            for ($q = 1; $q <= $currentQuarter; $q++) {
                $grade = $this->gradeModel->calculateQuarterlyGrade(
                    $studentId,
                    $subjectId,
                    $q,
                    $academicYear
                );
                
                if ($grade && $grade['final_grade'] !== null) {
                    $quarters[$q] = (float)$grade['final_grade'];
                }
            }
            
            if (count($quarters) < 2) {
                return 'unknown';
            }
            
            // Calculate trend using linear regression (simple slope)
            $trend = $this->calculateTrendSlope($quarters);
            
            if ($trend < -3) {
                return 'declining';
            } elseif ($trend > 3) {
                return 'improving';
            } else {
                return 'stable';
            }
        } catch (\Exception $e) {
            error_log("PerformanceAnalyzer::analyzeTrend error: " . $e->getMessage());
            return 'unknown';
        }
    }
    
    /**
     * Calculate trend slope using simple linear regression
     * Returns the slope (positive = improving, negative = declining)
     */
    private function calculateTrendSlope(array $quarters): float
    {
        if (count($quarters) < 2) {
            return 0;
        }
        
        $n = count($quarters);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        foreach ($quarters as $quarter => $grade) {
            $sumX += $quarter;
            $sumY += $grade;
            $sumXY += $quarter * $grade;
            $sumX2 += $quarter * $quarter;
        }
        
        // Simple linear regression: slope = (n*ΣXY - ΣX*ΣY) / (n*ΣX² - (ΣX)²)
        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) {
            return 0;
        }
        
        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        return round($slope, 2);
    }
    
    /**
     * Predict future grade based on historical trends
     * Uses linear regression to forecast performance
     * 
     * @param int $studentId Student ID
     * @param int $subjectId Subject ID
     * @param int $currentQuarter Current quarter
     * @param string $academicYear Academic year
     * @return array Prediction data with confidence
     */
    public function predictFutureGrade(int $studentId, int $subjectId, int $currentQuarter, string $academicYear): ?array
    {
        try {
            // Get historical grades for all available quarters
            $historicalGrades = [];
            for ($q = 1; $q <= $currentQuarter; $q++) {
                $grade = $this->gradeModel->calculateQuarterlyGrade(
                    $studentId,
                    $subjectId,
                    $q,
                    $academicYear
                );
                
                if ($grade && $grade['final_grade'] !== null) {
                    $historicalGrades[$q] = (float)$grade['final_grade'];
                }
            }
            
            if (count($historicalGrades) < 2) {
                // Not enough data for prediction
                return [
                    'predicted_grade' => null,
                    'confidence' => 0,
                    'trend' => 'unknown',
                    'message' => 'Insufficient data for prediction',
                    'data_points' => count($historicalGrades),
                ];
            }
            
            // Calculate trend slope
            $slope = $this->calculateTrendSlope($historicalGrades);
            
            // Predict next quarter grade using linear extrapolation
            $lastQuarter = max(array_keys($historicalGrades));
            $lastGrade = $historicalGrades[$lastQuarter];
            
            // Predict for next quarter
            $nextQuarter = $lastQuarter + 1;
            $predictedGrade = $lastGrade + $slope;
            
            // Ensure grade is within reasonable bounds (0-100)
            $predictedGrade = max(0, min(100, $predictedGrade));
            
            // Calculate confidence based on:
            // 1. Number of data points (more = higher confidence)
            // 2. Consistency of trend (stable trend = higher confidence)
            // 3. Recent performance stability
            $confidence = $this->calculatePredictionConfidence($historicalGrades, $slope);
            
            // Determine trend direction
            $trend = 'stable';
            if ($slope < -2) {
                $trend = 'declining';
            } elseif ($slope > 2) {
                $trend = 'improving';
            }
            
            // Generate prediction message
            $message = $this->generatePredictionMessage($predictedGrade, $trend, $slope);
            
            return [
                'predicted_grade' => round($predictedGrade, 2),
                'confidence' => round($confidence, 1),
                'trend' => $trend,
                'trend_slope' => $slope,
                'message' => $message,
                'data_points' => count($historicalGrades),
                'last_quarter' => $lastQuarter,
                'last_grade' => round($lastGrade, 2),
                'historical_grades' => $historicalGrades,
            ];
        } catch (\Exception $e) {
            error_log("PerformanceAnalyzer::predictFutureGrade error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate prediction confidence (0-100)
     */
    private function calculatePredictionConfidence(array $historicalGrades, float $slope): float
    {
        $dataPoints = count($historicalGrades);
        
        // Base confidence from data points (max 60 points)
        $dataConfidence = min(60, ($dataPoints - 1) * 20);
        
        // Trend consistency (max 30 points)
        // Calculate variance in grades
        $grades = array_values($historicalGrades);
        $mean = array_sum($grades) / count($grades);
        $variance = 0;
        foreach ($grades as $grade) {
            $variance += pow($grade - $mean, 2);
        }
        $variance = $variance / count($grades);
        $stdDev = sqrt($variance);
        
        // Lower variance = higher confidence (more consistent)
        $consistencyScore = max(0, 30 - ($stdDev * 2));
        
        // Slope stability (max 10 points)
        // Smaller absolute slope = more stable = higher confidence
        $stabilityScore = max(0, 10 - (abs($slope) * 2));
        
        $totalConfidence = $dataConfidence + $consistencyScore + $stabilityScore;
        return min(100, max(0, $totalConfidence));
    }
    
    /**
     * Generate human-readable prediction message
     */
    private function generatePredictionMessage(float $predictedGrade, string $trend, float $slope): string
    {
        $passingGrade = self::PASSING_GRADE;
        
        if ($predictedGrade < $passingGrade) {
            if ($trend === 'declining') {
                return "If current declining trend continues, projected grade is {$predictedGrade}% (below passing). Immediate intervention recommended.";
            } elseif ($trend === 'improving') {
                return "Projected grade: {$predictedGrade}%. While below passing, performance is improving. With continued effort, passing is achievable.";
            } else {
                return "Projected grade: {$predictedGrade}% (below passing). Current performance is stable but needs improvement to pass.";
            }
        } else {
            if ($trend === 'improving') {
                return "Excellent! Projected grade: {$predictedGrade}% (above passing). Performance is improving. Keep up the great work!";
            } elseif ($trend === 'declining') {
                return "Projected grade: {$predictedGrade}% (above passing but declining). Monitor closely to maintain passing status.";
            } else {
                return "Projected grade: {$predictedGrade}% (above passing). Performance is stable. Continue current efforts.";
            }
        }
    }
    
    /**
     * Get student subjects
     */
    private function getStudentSubjects(int $studentId, int $sectionId): array
    {
        try {
            // Try to get from grades first
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT g.subject_id, s.name as subject_name
                FROM grades g
                JOIN subjects s ON g.subject_id = s.id
                WHERE g.student_id = ? AND g.section_id = ?
            ");
            $stmt->execute([$studentId, $sectionId]);
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no grades, try to get from classes
            if (empty($subjects)) {
                $stmt = $this->pdo->prepare("
                    SELECT DISTINCT c.subject_id, s.name as subject_name
                    FROM classes c
                    JOIN subjects s ON c.subject_id = s.id
                    JOIN student_classes sc ON c.id = sc.class_id
                    WHERE sc.student_id = ? AND c.section_id = ? AND c.is_active = 1 AND sc.status = 'enrolled'
                ");
                $stmt->execute([$studentId, $sectionId]);
                $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $subjects;
        } catch (PDOException $e) {
            error_log("PerformanceAnalyzer::getStudentSubjects error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get subject name
     */
    private function getSubjectName(int $subjectId): string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT name FROM subjects WHERE id = ?");
            $stmt->execute([$subjectId]);
            $subject = $stmt->fetch(PDO::FETCH_ASSOC);
            return $subject['name'] ?? "Subject #{$subjectId}";
        } catch (PDOException $e) {
            return "Subject #{$subjectId}";
        }
    }
    
    /**
     * Get current quarter based on month
     */
    private function getCurrentQuarter(): int
    {
        $month = (int)date('n');
        
        // Quarter 1: June-August (months 6-8)
        // Quarter 2: September-November (months 9-11)
        // Quarter 3: December-February (months 12, 1, 2)
        // Quarter 4: March-May (months 3-5)
        
        if ($month >= 6 && $month <= 8) {
            return 1;
        } elseif ($month >= 9 && $month <= 11) {
            return 2;
        } elseif ($month === 12 || $month <= 2) {
            return 3;
        } else {
            return 4;
        }
    }
    
    /**
     * Get date range for a quarter
     */
    private function getQuarterDateRange(int $quarter, int $startYear): ?array
    {
        $startDate = null;
        $endDate = null;
        
        switch ($quarter) {
            case 1:
                $startDate = sprintf('%d-06-01', $startYear);
                $endDate = sprintf('%d-08-31', $startYear);
                break;
            case 2:
                $startDate = sprintf('%d-09-01', $startYear);
                $endDate = sprintf('%d-11-30', $startYear);
                break;
            case 3:
                $startDate = sprintf('%d-12-01', $startYear);
                $endDate = sprintf('%d-02-28', $startYear + 1);
                break;
            case 4:
                $startDate = sprintf('%d-03-01', $startYear + 1);
                $endDate = sprintf('%d-05-31', $startYear + 1);
                break;
        }
        
        return ($startDate && $endDate) ? ['start' => $startDate, 'end' => $endDate] : null;
    }
}

