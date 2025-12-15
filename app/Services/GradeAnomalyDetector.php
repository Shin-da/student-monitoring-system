<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Models\GradeModel;
use PDO;
use PDOException;

/**
 * Grade Anomaly Detector Service
 * 
 * AI-powered service that detects unusual grade patterns and potential errors
 * using statistical analysis and pattern recognition.
 * 
 * Features:
 * - Statistical outlier detection (Z-score analysis)
 * - Sudden drop/spike detection
 * - Pattern consistency checks
 * - Historical comparison
 * - Data entry error detection
 */
class GradeAnomalyDetector
{
    private PDO $pdo;
    private GradeModel $gradeModel;
    
    // Thresholds
    private const Z_SCORE_THRESHOLD = 2.5; // Standard deviations (2.5 = ~99% confidence)
    private const SUDDEN_DROP_THRESHOLD = 20; // Percentage points
    private const SUDDEN_SPIKE_THRESHOLD = 25; // Percentage points
    private const MIN_HISTORICAL_DATA = 3; // Minimum grades needed for comparison
    
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
     * Detect anomalies in a grade before submission
     * 
     * @param array $gradeData Grade data to check
     * @return array Anomaly detection results
     */
    public function detectAnomalies(array $gradeData): array
    {
        try {
            $studentId = (int)($gradeData['student_id'] ?? 0);
            $subjectId = (int)($gradeData['subject_id'] ?? 0);
            $gradeType = $gradeData['grade_type'] ?? '';
            $quarter = (int)($gradeData['quarter'] ?? 0);
            $gradeValue = (float)($gradeData['grade_value'] ?? 0);
            $maxScore = (float)($gradeData['max_score'] ?? 100);
            $academicYear = $gradeData['academic_year'] ?? null;
            
            if (!$studentId || !$subjectId || !$gradeType || !$quarter) {
                return $this->getEmptyResult();
            }
            
            // Calculate percentage
            $percentage = $maxScore > 0 ? ($gradeValue / $maxScore) * 100 : 0;
            
            // Get historical grades for comparison
            $historicalGrades = $this->getHistoricalGrades($studentId, $subjectId, $gradeType, $quarter, $academicYear);
            
            $anomalies = [];
            $warnings = [];
            $suggestions = [];
            
            // 1. Check for statistical outliers (Z-score)
            if (count($historicalGrades) >= self::MIN_HISTORICAL_DATA) {
                $zScoreResult = $this->checkZScore($percentage, $historicalGrades);
                if ($zScoreResult['is_anomaly']) {
                    $anomalies[] = [
                        'type' => 'statistical_outlier',
                        'severity' => $zScoreResult['severity'],
                        'description' => $zScoreResult['description'],
                        'z_score' => $zScoreResult['z_score'],
                        'confidence' => $zScoreResult['confidence'],
                    ];
                }
            }
            
            // 2. Check for sudden drops or spikes
            if (!empty($historicalGrades)) {
                $recentGrade = $this->getMostRecentGrade($historicalGrades);
                if ($recentGrade) {
                    $dropSpikeResult = $this->checkSuddenChange($percentage, $recentGrade['percentage']);
                    if ($dropSpikeResult['is_anomaly']) {
                        $anomalies[] = [
                            'type' => $dropSpikeResult['type'],
                            'severity' => $dropSpikeResult['severity'],
                            'description' => $dropSpikeResult['description'],
                            'change' => $dropSpikeResult['change'],
                            'confidence' => $dropSpikeResult['confidence'],
                        ];
                    }
                }
            }
            
            // 3. Check pattern consistency
            $patternResult = $this->checkPatternConsistency($percentage, $historicalGrades, $gradeType);
            if ($patternResult['is_inconsistent']) {
                $warnings[] = [
                    'type' => 'pattern_inconsistency',
                    'description' => $patternResult['description'],
                    'confidence' => $patternResult['confidence'],
                ];
            }
            
            // 4. Check for impossible values
            $impossibleCheck = $this->checkImpossibleValues($gradeValue, $maxScore, $percentage);
            if ($impossibleCheck['is_anomaly']) {
                $anomalies[] = [
                    'type' => 'impossible_value',
                    'severity' => 'high',
                    'description' => $impossibleCheck['description'],
                    'confidence' => 100,
                ];
            }
            
            // 5. Check against class average (if available)
            $classComparison = $this->compareWithClassAverage($studentId, $subjectId, $gradeType, $quarter, $percentage, $academicYear);
            if ($classComparison['is_anomaly']) {
                $warnings[] = [
                    'type' => 'class_deviation',
                    'description' => $classComparison['description'],
                    'confidence' => $classComparison['confidence'],
                ];
            }
            
            // Generate suggestions
            if (!empty($anomalies) || !empty($warnings)) {
                $suggestions = $this->generateSuggestions($anomalies, $warnings, $historicalGrades, $percentage);
            }
            
            // Overall assessment
            $hasHighSeverityAnomaly = !empty(array_filter($anomalies, fn($a) => $a['severity'] === 'high'));
            $hasMediumSeverityAnomaly = !empty(array_filter($anomalies, fn($a) => $a['severity'] === 'medium'));
            
            $overallSeverity = 'none';
            if ($hasHighSeverityAnomaly) {
                $overallSeverity = 'high';
            } elseif ($hasMediumSeverityAnomaly || !empty($warnings)) {
                $overallSeverity = 'medium';
            }
            
            return [
                'has_anomalies' => !empty($anomalies),
                'has_warnings' => !empty($warnings),
                'overall_severity' => $overallSeverity,
                'anomalies' => $anomalies,
                'warnings' => $warnings,
                'suggestions' => $suggestions,
                'should_block' => $hasHighSeverityAnomaly && $overallSeverity === 'high',
                'should_warn' => $overallSeverity !== 'none',
                'analyzed_grade' => [
                    'value' => $gradeValue,
                    'max_score' => $maxScore,
                    'percentage' => round($percentage, 2),
                ],
                'historical_context' => [
                    'data_points' => count($historicalGrades),
                    'average' => count($historicalGrades) > 0 ? round(array_sum(array_column($historicalGrades, 'percentage')) / count($historicalGrades), 2) : null,
                    'last_grade' => $this->getMostRecentGrade($historicalGrades),
                ],
                'analyzed_at' => date('Y-m-d H:i:s'),
            ];
        } catch (PDOException $e) {
            error_log("GradeAnomalyDetector::detectAnomalies error: " . $e->getMessage());
            return $this->getEmptyResult();
        }
    }
    
    /**
     * Get historical grades for comparison
     */
    private function getHistoricalGrades(
        int $studentId,
        int $subjectId,
        string $gradeType,
        int $quarter,
        ?string $academicYear
    ): array {
        try {
            $academicYear = $academicYear ?? $this->gradeModel->getCurrentAcademicYear();
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    grade_value,
                    max_score,
                    ROUND((grade_value / NULLIF(max_score, 0)) * 100, 2) as percentage,
                    graded_at,
                    quarter
                FROM grades
                WHERE student_id = ?
                  AND subject_id = ?
                  AND grade_type = ?
                  AND quarter = ?
                  AND academic_year = ?
                  AND id != ? -- Exclude current grade if updating
                ORDER BY graded_at DESC
                LIMIT 20
            ");
            
            // For new grades, use 0 as exclude ID
            $stmt->execute([$studentId, $subjectId, $gradeType, $quarter, $academicYear, 0]);
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert percentage to float
            foreach ($grades as &$grade) {
                $grade['percentage'] = (float)$grade['percentage'];
            }
            
            return $grades;
        } catch (PDOException $e) {
            error_log("GradeAnomalyDetector::getHistoricalGrades error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check Z-score for statistical outliers
     */
    private function checkZScore(float $currentPercentage, array $historicalGrades): array
    {
        $percentages = array_column($historicalGrades, 'percentage');
        
        if (count($percentages) < self::MIN_HISTORICAL_DATA) {
            return ['is_anomaly' => false];
        }
        
        $mean = array_sum($percentages) / count($percentages);
        $variance = 0;
        foreach ($percentages as $p) {
            $variance += pow($p - $mean, 2);
        }
        $variance = $variance / count($percentages);
        $stdDev = sqrt($variance);
        
        if ($stdDev == 0) {
            return ['is_anomaly' => false];
        }
        
        $zScore = abs(($currentPercentage - $mean) / $stdDev);
        
        $isAnomaly = $zScore >= self::Z_SCORE_THRESHOLD;
        $severity = $zScore >= 3.5 ? 'high' : ($zScore >= self::Z_SCORE_THRESHOLD ? 'medium' : 'low');
        
        $direction = $currentPercentage > $mean ? 'above' : 'below';
        $description = "Grade is {$direction} average by " . round($zScore, 1) . " standard deviations. ";
        $description .= "Expected range: " . round($mean - (2 * $stdDev), 1) . "-" . round($mean + (2 * $stdDev), 1) . "%. ";
        $description .= "Current: " . round($currentPercentage, 1) . "%.";
        
        $confidence = min(100, ($zScore / 4) * 100); // Higher Z-score = higher confidence
        
        return [
            'is_anomaly' => $isAnomaly,
            'severity' => $severity,
            'description' => $description,
            'z_score' => round($zScore, 2),
            'mean' => round($mean, 2),
            'std_dev' => round($stdDev, 2),
            'confidence' => round($confidence, 1),
        ];
    }
    
    /**
     * Check for sudden drops or spikes
     */
    private function checkSuddenChange(float $currentPercentage, float $previousPercentage): array
    {
        $change = $currentPercentage - $previousPercentage;
        $absChange = abs($change);
        
        $isAnomaly = false;
        $type = '';
        $severity = 'low';
        
        if ($change < 0 && $absChange >= self::SUDDEN_DROP_THRESHOLD) {
            $isAnomaly = true;
            $type = 'sudden_drop';
            $severity = $absChange >= 30 ? 'high' : 'medium';
            $description = "Sudden drop detected: " . round($absChange, 1) . "% decrease from previous grade ({$previousPercentage}% → {$currentPercentage}%).";
        } elseif ($change > 0 && $absChange >= self::SUDDEN_SPIKE_THRESHOLD) {
            $isAnomaly = true;
            $type = 'sudden_spike';
            $severity = $absChange >= 35 ? 'high' : 'medium';
            $description = "Sudden spike detected: " . round($absChange, 1) . "% increase from previous grade ({$previousPercentage}% → {$currentPercentage}%).";
        } else {
            $description = "Change is within normal range.";
        }
        
        $confidence = min(100, ($absChange / 40) * 100);
        
        return [
            'is_anomaly' => $isAnomaly,
            'type' => $type,
            'severity' => $severity,
            'description' => $description,
            'change' => round($change, 2),
            'confidence' => round($confidence, 1),
        ];
    }
    
    /**
     * Check pattern consistency
     */
    private function checkPatternConsistency(float $currentPercentage, array $historicalGrades, string $gradeType): array
    {
        if (count($historicalGrades) < 3) {
            return ['is_inconsistent' => false];
        }
        
        // Get average for this grade type
        $percentages = array_column($historicalGrades, 'percentage');
        $average = array_sum($percentages) / count($percentages);
        
        // Check if current grade is significantly different from pattern
        $deviation = abs($currentPercentage - $average);
        $isInconsistent = $deviation > 15; // More than 15% deviation from pattern
        
        $description = '';
        if ($isInconsistent) {
            $description = "Grade ({$currentPercentage}%) is inconsistent with student's typical performance ";
            $description .= "in {$gradeType} (average: " . round($average, 1) . "%). ";
            $description .= "Deviation: " . round($deviation, 1) . "%.";
        }
        
        $confidence = min(100, ($deviation / 25) * 100);
        
        return [
            'is_inconsistent' => $isInconsistent,
            'description' => $description,
            'deviation' => round($deviation, 2),
            'average' => round($average, 2),
            'confidence' => round($confidence, 1),
        ];
    }
    
    /**
     * Check for impossible values
     */
    private function checkImpossibleValues(float $gradeValue, float $maxScore, float $percentage): array
    {
        $issues = [];
        
        // Check if grade exceeds max score
        if ($gradeValue > $maxScore) {
            $issues[] = "Grade value ({$gradeValue}) exceeds maximum score ({$maxScore}).";
        }
        
        // Check if percentage is outside valid range
        if ($percentage < 0 || $percentage > 100) {
            $issues[] = "Calculated percentage ({$percentage}%) is outside valid range (0-100%).";
        }
        
        // Check for negative values
        if ($gradeValue < 0) {
            $issues[] = "Grade value cannot be negative.";
        }
        
        if ($maxScore <= 0) {
            $issues[] = "Maximum score must be greater than zero.";
        }
        
        $isAnomaly = !empty($issues);
        $description = implode(' ', $issues);
        
        return [
            'is_anomaly' => $isAnomaly,
            'description' => $description,
        ];
    }
    
    /**
     * Compare with class average
     */
    private function compareWithClassAverage(
        int $studentId,
        int $subjectId,
        string $gradeType,
        int $quarter,
        float $currentPercentage,
        ?string $academicYear
    ): array {
        try {
            $academicYear = $academicYear ?? $this->gradeModel->getCurrentAcademicYear();
            
            // Get class average for this subject, grade type, and quarter
            $stmt = $this->pdo->prepare("
                SELECT 
                    AVG(ROUND((grade_value / NULLIF(max_score, 0)) * 100, 2)) as class_average,
                    COUNT(*) as student_count
                FROM grades
                WHERE subject_id = ?
                  AND grade_type = ?
                  AND quarter = ?
                  AND academic_year = ?
                  AND student_id != ?
            ");
            $stmt->execute([$subjectId, $gradeType, $quarter, $academicYear, $studentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || $result['student_count'] < 5 || $result['class_average'] === null) {
                return ['is_anomaly' => false];
            }
            
            $classAverage = (float)$result['class_average'];
            $deviation = abs($currentPercentage - $classAverage);
            
            // Flag if significantly different from class average (more than 2 standard deviations)
            // For simplicity, we'll use a fixed threshold of 25%
            $isAnomaly = $deviation > 25;
            
            $description = '';
            if ($isAnomaly) {
                $direction = $currentPercentage > $classAverage ? 'above' : 'below';
                $description = "Grade ({$currentPercentage}%) is significantly {$direction} class average ";
                $description .= "(" . round($classAverage, 1) . "%). Deviation: " . round($deviation, 1) . "%.";
            }
            
            $confidence = min(100, ($deviation / 40) * 100);
            
            return [
                'is_anomaly' => $isAnomaly,
                'description' => $description,
                'class_average' => round($classAverage, 2),
                'deviation' => round($deviation, 2),
                'confidence' => round($confidence, 1),
            ];
        } catch (PDOException $e) {
            error_log("GradeAnomalyDetector::compareWithClassAverage error: " . $e->getMessage());
            return ['is_anomaly' => false];
        }
    }
    
    /**
     * Get most recent grade
     */
    private function getMostRecentGrade(array $historicalGrades): ?array
    {
        if (empty($historicalGrades)) {
            return null;
        }
        
        // Grades are already sorted by graded_at DESC
        return $historicalGrades[0];
    }
    
    /**
     * Generate suggestions based on anomalies
     */
    private function generateSuggestions(array $anomalies, array $warnings, array $historicalGrades, float $currentPercentage): array
    {
        $suggestions = [];
        
        foreach ($anomalies as $anomaly) {
            if ($anomaly['type'] === 'sudden_drop' && $anomaly['severity'] === 'high') {
                $suggestions[] = "Verify this grade is correct. Consider reviewing the assessment or student's work.";
            } elseif ($anomaly['type'] === 'sudden_spike' && $anomaly['severity'] === 'high') {
                $suggestions[] = "Confirm this grade is accurate. This is a significant improvement from previous performance.";
            } elseif ($anomaly['type'] === 'statistical_outlier') {
                $suggestions[] = "This grade is statistically unusual. Please double-check the grade value and max score.";
            } elseif ($anomaly['type'] === 'impossible_value') {
                $suggestions[] = "Invalid grade value detected. Please correct before submitting.";
            }
        }
        
        foreach ($warnings as $warning) {
            if ($warning['type'] === 'pattern_inconsistency') {
                $suggestions[] = "This grade doesn't match the student's typical performance pattern. Please verify.";
            } elseif ($warning['type'] === 'class_deviation') {
                $suggestions[] = "This grade is significantly different from the class average. Please review.";
            }
        }
        
        if (empty($suggestions)) {
            $suggestions[] = "Please review this grade before submitting to ensure accuracy.";
        }
        
        return array_unique($suggestions);
    }
    
    /**
     * Get empty result structure
     */
    private function getEmptyResult(): array
    {
        return [
            'has_anomalies' => false,
            'has_warnings' => false,
            'overall_severity' => 'none',
            'anomalies' => [],
            'warnings' => [],
            'suggestions' => [],
            'should_block' => false,
            'should_warn' => false,
            'analyzed_grade' => [],
            'historical_context' => [],
            'analyzed_at' => date('Y-m-d H:i:s'),
        ];
    }
}

