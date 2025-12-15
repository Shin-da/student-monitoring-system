<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use PDO;
use PDOException;

/**
 * Attendance Pattern Analyzer Service
 * 
 * AI-powered service that detects patterns in student attendance data
 * and predicts future attendance issues.
 * 
 * Features:
 * - Day-of-week pattern detection
 * - Frequency analysis
 * - Trend detection (improving/declining)
 * - Predictive attendance forecasting
 * - Correlation with academic performance
 */
class AttendancePatternAnalyzer
{
    private PDO $pdo;
    
    // Thresholds
    private const CHRONIC_ABSENTEEISM_THRESHOLD = 0.20; // 20% absence rate
    private const CONCERNING_ABSENCE_RATE = 0.15; // 15% absence rate
    private const PATTERN_CONFIDENCE_THRESHOLD = 0.60; // 60% pattern confidence
    
    public function __construct(?PDO $pdo = null)
    {
        if ($pdo === null) {
            $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
            $this->pdo = Database::connection($config['database']);
        } else {
            $this->pdo = $pdo;
        }
    }
    
    /**
     * Analyze attendance patterns for a student
     * 
     * @param int $studentId Student ID
     * @param int|null $sectionId Section ID (optional)
     * @param int|null $subjectId Subject ID (optional, null = all subjects)
     * @param string|null $startDate Start date (defaults to 30 days ago)
     * @param string|null $endDate End date (defaults to today)
     * @return array Pattern analysis results
     */
    public function analyzePatterns(
        int $studentId,
        ?int $sectionId = null,
        ?int $subjectId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            $endDate = $endDate ?? date('Y-m-d');
            $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
            
            // Get attendance records
            $attendanceRecords = $this->getAttendanceRecords($studentId, $sectionId, $subjectId, $startDate, $endDate);
            
            if (empty($attendanceRecords)) {
                return $this->getEmptyAnalysis();
            }
            
            // Analyze patterns
            $dayOfWeekPattern = $this->analyzeDayOfWeekPattern($attendanceRecords);
            $frequencyAnalysis = $this->analyzeFrequency($attendanceRecords, $startDate, $endDate);
            $trendAnalysis = $this->analyzeTrend($attendanceRecords);
            $predictiveAnalysis = $this->predictFutureAttendance($frequencyAnalysis, $trendAnalysis);
            
            // Overall assessment
            $overallAssessment = $this->assessOverallPattern(
                $dayOfWeekPattern,
                $frequencyAnalysis,
                $trendAnalysis
            );
            
            return [
                'student_id' => $studentId,
                'section_id' => $sectionId,
                'subject_id' => $subjectId,
                'analysis_period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_days' => $this->calculateTotalDays($startDate, $endDate),
                ],
                'day_of_week_pattern' => $dayOfWeekPattern,
                'frequency_analysis' => $frequencyAnalysis,
                'trend_analysis' => $trendAnalysis,
                'predictive_analysis' => $predictiveAnalysis,
                'overall_assessment' => $overallAssessment,
                'patterns_detected' => $this->detectPatterns($dayOfWeekPattern, $frequencyAnalysis),
                'recommendations' => $this->generateRecommendations($overallAssessment, $dayOfWeekPattern),
                'analyzed_at' => date('Y-m-d H:i:s'),
            ];
        } catch (PDOException $e) {
            error_log("AttendancePatternAnalyzer::analyzePatterns error: " . $e->getMessage());
            return $this->getEmptyAnalysis();
        }
    }
    
    /**
     * Get attendance records from database
     */
    private function getAttendanceRecords(
        int $studentId,
        ?int $sectionId,
        ?int $subjectId,
        string $startDate,
        string $endDate
    ): array {
        $sql = "
            SELECT 
                attendance_date,
                status,
                DAYNAME(attendance_date) as day_name,
                DAYOFWEEK(attendance_date) as day_of_week,
                WEEKDAY(attendance_date) as weekday_index,
                subject_id,
                section_id
            FROM attendance
            WHERE student_id = ?
              AND attendance_date >= ?
              AND attendance_date <= ?
        ";
        
        $params = [$studentId, $startDate, $endDate];
        
        if ($sectionId !== null) {
            $sql .= " AND section_id = ?";
            $params[] = $sectionId;
        }
        
        if ($subjectId !== null) {
            $sql .= " AND subject_id = ?";
            $params[] = $subjectId;
        }
        
        $sql .= " ORDER BY attendance_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Analyze day-of-week patterns
     */
    private function analyzeDayOfWeekPattern(array $records): array
    {
        $dayStats = [
            'Monday' => ['total' => 0, 'absent' => 0, 'present' => 0, 'late' => 0],
            'Tuesday' => ['total' => 0, 'absent' => 0, 'present' => 0, 'late' => 0],
            'Wednesday' => ['total' => 0, 'absent' => 0, 'present' => 0, 'late' => 0],
            'Thursday' => ['total' => 0, 'absent' => 0, 'present' => 0, 'late' => 0],
            'Friday' => ['total' => 0, 'absent' => 0, 'present' => 0, 'late' => 0],
            'Saturday' => ['total' => 0, 'absent' => 0, 'present' => 0, 'late' => 0],
            'Sunday' => ['total' => 0, 'absent' => 0, 'present' => 0, 'late' => 0],
        ];
        
        foreach ($records as $record) {
            $dayName = $record['day_name'] ?? 'Unknown';
            if (isset($dayStats[$dayName])) {
                $dayStats[$dayName]['total']++;
                
                $status = $record['status'] ?? 'absent';
                if ($status === 'absent') {
                    $dayStats[$dayName]['absent']++;
                } elseif ($status === 'present') {
                    $dayStats[$dayName]['present']++;
                } elseif ($status === 'late') {
                    $dayStats[$dayName]['late']++;
                }
            }
        }
        
        // Calculate percentages and identify patterns
        $patterns = [];
        foreach ($dayStats as $day => $stats) {
            if ($stats['total'] > 0) {
                $absentRate = $stats['absent'] / $stats['total'];
                $presentRate = $stats['present'] / $stats['total'];
                $lateRate = $stats['late'] / $stats['total'];
                
                $dayStats[$day]['absent_rate'] = round($absentRate * 100, 2);
                $dayStats[$day]['present_rate'] = round($presentRate * 100, 2);
                $dayStats[$day]['late_rate'] = round($lateRate * 100, 2);
                
                // Identify concerning patterns
                if ($absentRate >= self::CONCERNING_ABSENCE_RATE && $stats['total'] >= 3) {
                    $patterns[] = [
                        'day' => $day,
                        'type' => 'high_absence',
                        'rate' => $absentRate,
                        'confidence' => min(100, ($stats['total'] / 5) * 100), // More data = higher confidence
                    ];
                }
            }
        }
        
        return [
            'day_statistics' => $dayStats,
            'concerning_days' => $patterns,
            'most_problematic_day' => $this->findMostProblematicDay($dayStats),
        ];
    }
    
    /**
     * Find the most problematic day
     */
    private function findMostProblematicDay(array $dayStats): ?array
    {
        $maxAbsentRate = 0;
        $problematicDay = null;
        
        foreach ($dayStats as $day => $stats) {
            if (($stats['absent_rate'] ?? 0) > $maxAbsentRate && $stats['total'] >= 3) {
                $maxAbsentRate = $stats['absent_rate'];
                $problematicDay = [
                    'day' => $day,
                    'absent_rate' => $stats['absent_rate'],
                    'total_occurrences' => $stats['total'],
                    'absent_count' => $stats['absent'],
                ];
            }
        }
        
        return $problematicDay;
    }
    
    /**
     * Analyze attendance frequency
     */
    private function analyzeFrequency(array $records, string $startDate, string $endDate): array
    {
        $totalDays = $this->calculateTotalDays($startDate, $endDate);
        $totalRecords = count($records);
        
        $statusCounts = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
        ];
        
        foreach ($records as $record) {
            $status = $record['status'] ?? 'absent';
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }
        
        $presentDays = $statusCounts['present'] + $statusCounts['late'] + $statusCounts['excused'];
        $absentDays = $statusCounts['absent'];
        
        $attendanceRate = $totalRecords > 0 ? ($presentDays / $totalRecords) * 100 : 100;
        $absenceRate = $totalRecords > 0 ? ($absentDays / $totalRecords) * 100 : 0;
        
        // Determine status
        $status = 'good';
        if ($absenceRate >= (self::CHRONIC_ABSENTEEISM_THRESHOLD * 100)) {
            $status = 'chronic';
        } elseif ($absenceRate >= (self::CONCERNING_ABSENCE_RATE * 100)) {
            $status = 'concerning';
        } elseif ($absenceRate > 10) {
            $status = 'fair';
        }
        
        return [
            'total_days_analyzed' => $totalRecords,
            'total_calendar_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $statusCounts['late'],
            'excused_days' => $statusCounts['excused'],
            'attendance_rate' => round($attendanceRate, 2),
            'absence_rate' => round($absenceRate, 2),
            'status' => $status,
            'is_chronic_absentee' => $status === 'chronic',
            'is_concerning' => in_array($status, ['chronic', 'concerning']),
        ];
    }
    
    /**
     * Analyze attendance trend over time
     */
    private function analyzeTrend(array $records): array
    {
        if (count($records) < 4) {
            return [
                'trend' => 'insufficient_data',
                'direction' => 'unknown',
                'slope' => 0,
                'confidence' => 0,
            ];
        }
        
        // Group by week
        $weeklyData = [];
        foreach ($records as $record) {
            $week = date('Y-W', strtotime($record['attendance_date']));
            if (!isset($weeklyData[$week])) {
                $weeklyData[$week] = ['total' => 0, 'present' => 0, 'absent' => 0];
            }
            
            $weeklyData[$week]['total']++;
            if (in_array($record['status'], ['present', 'late', 'excused'])) {
                $weeklyData[$week]['present']++;
            } else {
                $weeklyData[$week]['absent']++;
            }
        }
        
        // Calculate weekly attendance rates
        $weeklyRates = [];
        $weekNumbers = [];
        $weekIndex = 0;
        
        foreach ($weeklyData as $week => $data) {
            if ($data['total'] > 0) {
                $rate = ($data['present'] / $data['total']) * 100;
                $weeklyRates[] = $rate;
                $weekNumbers[] = $weekIndex++;
            }
        }
        
        if (count($weeklyRates) < 2) {
            return [
                'trend' => 'insufficient_data',
                'direction' => 'unknown',
                'slope' => 0,
                'confidence' => 0,
            ];
        }
        
        // Calculate trend slope using linear regression
        $slope = $this->calculateTrendSlope($weekNumbers, $weeklyRates);
        
        $direction = 'stable';
        if ($slope < -5) {
            $direction = 'declining';
        } elseif ($slope > 5) {
            $direction = 'improving';
        }
        
        // Calculate confidence based on data points and consistency
        $confidence = min(100, (count($weeklyRates) - 1) * 25);
        if (count($weeklyRates) >= 3) {
            $variance = $this->calculateVariance($weeklyRates);
            $consistencyBonus = max(0, 30 - ($variance * 2));
            $confidence = min(100, $confidence + $consistencyBonus);
        }
        
        return [
            'trend' => $direction,
            'direction' => $direction,
            'slope' => round($slope, 2),
            'confidence' => round($confidence, 1),
            'weekly_rates' => $weeklyRates,
            'data_points' => count($weeklyRates),
        ];
    }
    
    /**
     * Calculate trend slope using linear regression
     */
    private function calculateTrendSlope(array $xValues, array $yValues): float
    {
        if (count($xValues) !== count($yValues) || count($xValues) < 2) {
            return 0;
        }
        
        $n = count($xValues);
        $sumX = array_sum($xValues);
        $sumY = array_sum($yValues);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $xValues[$i] * $yValues[$i];
            $sumX2 += $xValues[$i] * $xValues[$i];
        }
        
        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) {
            return 0;
        }
        
        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        return $slope;
    }
    
    /**
     * Calculate variance
     */
    private function calculateVariance(array $values): float
    {
        if (empty($values)) {
            return 0;
        }
        
        $mean = array_sum($values) / count($values);
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return $variance / count($values);
    }
    
    /**
     * Predict future attendance based on current patterns
     */
    private function predictFutureAttendance(array $frequencyAnalysis, array $trendAnalysis): array
    {
        $currentRate = $frequencyAnalysis['attendance_rate'] ?? 100;
        $trendSlope = $trendAnalysis['slope'] ?? 0;
        
        // Predict next 2 weeks (10 school days typically)
        $projectedDays = 10;
        $currentAbsenceRate = $frequencyAnalysis['absence_rate'] ?? 0;
        
        // Adjust based on trend
        $projectedAbsenceRate = $currentAbsenceRate;
        if ($trendAnalysis['direction'] === 'declining') {
            $projectedAbsenceRate += abs($trendSlope) * 0.1; // Increase if declining
        } elseif ($trendAnalysis['direction'] === 'improving') {
            $projectedAbsenceRate -= abs($trendSlope) * 0.1; // Decrease if improving
        }
        
        $projectedAbsenceRate = max(0, min(100, $projectedAbsenceRate));
        $projectedAbsences = round($projectedDays * ($projectedAbsenceRate / 100), 1);
        
        $confidence = $trendAnalysis['confidence'] ?? 50;
        
        return [
            'projected_absence_rate' => round($projectedAbsenceRate, 2),
            'projected_absences_next_2_weeks' => $projectedAbsences,
            'projected_attendance_rate' => round(100 - $projectedAbsenceRate, 2),
            'confidence' => round($confidence, 1),
            'based_on_trend' => $trendAnalysis['direction'] ?? 'unknown',
        ];
    }
    
    /**
     * Assess overall pattern
     */
    private function assessOverallPattern(
        array $dayOfWeekPattern,
        array $frequencyAnalysis,
        array $trendAnalysis
    ): array {
        $severity = 'low';
        $concerns = [];
        
        // Check for chronic absenteeism
        if ($frequencyAnalysis['is_chronic_absentee']) {
            $severity = 'high';
            $concerns[] = 'Chronic absenteeism detected';
        } elseif ($frequencyAnalysis['is_concerning']) {
            $severity = 'medium';
            $concerns[] = 'Concerning absence rate';
        }
        
        // Check for day-of-week patterns
        if (!empty($dayOfWeekPattern['concerning_days'])) {
            $severity = max($severity, 'medium');
            $concerns[] = 'Pattern detected: Frequent absences on specific days';
        }
        
        // Check for declining trend
        if ($trendAnalysis['direction'] === 'declining') {
            $severity = max($severity, 'medium');
            $concerns[] = 'Attendance trend is declining';
        }
        
        return [
            'severity' => $severity,
            'concerns' => $concerns,
            'requires_intervention' => $severity !== 'low',
            'priority' => $severity === 'high' ? 'high' : ($severity === 'medium' ? 'medium' : 'low'),
        ];
    }
    
    /**
     * Detect specific patterns
     */
    private function detectPatterns(array $dayOfWeekPattern, array $frequencyAnalysis): array
    {
        $patterns = [];
        
        // Day-of-week pattern
        if (!empty($dayOfWeekPattern['most_problematic_day'])) {
            $problematicDay = $dayOfWeekPattern['most_problematic_day'];
            if ($problematicDay['absent_rate'] >= 30) {
                $patterns[] = [
                    'type' => 'day_of_week',
                    'description' => "Frequently absent on {$problematicDay['day']}s",
                    'confidence' => min(100, ($problematicDay['total_occurrences'] / 5) * 100),
                    'details' => $problematicDay,
                ];
            }
        }
        
        // Chronic absenteeism
        if ($frequencyAnalysis['is_chronic_absentee']) {
            $patterns[] = [
                'type' => 'chronic_absenteeism',
                'description' => 'Chronic absenteeism (20%+ absence rate)',
                'confidence' => 100,
                'details' => [
                    'absence_rate' => $frequencyAnalysis['absence_rate'],
                ],
            ];
        }
        
        return $patterns;
    }
    
    /**
     * Generate recommendations based on patterns
     */
    private function generateRecommendations(array $overallAssessment, array $dayOfWeekPattern): array
    {
        $recommendations = [];
        
        if ($overallAssessment['severity'] === 'high') {
            $recommendations[] = 'Immediate intervention required. Contact student and parents.';
        } elseif ($overallAssessment['severity'] === 'medium') {
            $recommendations[] = 'Monitor closely and consider early intervention.';
        }
        
        if (!empty($dayOfWeekPattern['most_problematic_day'])) {
            $day = $dayOfWeekPattern['most_problematic_day']['day'];
            $recommendations[] = "Investigate reasons for frequent absences on {$day}s.";
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Attendance is within acceptable range. Continue monitoring.';
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate total days between two dates
     */
    private function calculateTotalDays(string $startDate, string $endDate): int
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $diff = $start->diff($end);
        return (int)$diff->days + 1;
    }
    
    /**
     * Get empty analysis structure
     */
    private function getEmptyAnalysis(): array
    {
        return [
            'student_id' => 0,
            'section_id' => null,
            'subject_id' => null,
            'analysis_period' => [
                'start_date' => null,
                'end_date' => null,
                'total_days' => 0,
            ],
            'day_of_week_pattern' => [
                'day_statistics' => [],
                'concerning_days' => [],
                'most_problematic_day' => null,
            ],
            'frequency_analysis' => [
                'total_days_analyzed' => 0,
                'attendance_rate' => 100,
                'absence_rate' => 0,
                'status' => 'no_data',
            ],
            'trend_analysis' => [
                'trend' => 'insufficient_data',
                'direction' => 'unknown',
            ],
            'predictive_analysis' => [
                'projected_absence_rate' => 0,
                'confidence' => 0,
            ],
            'overall_assessment' => [
                'severity' => 'low',
                'concerns' => [],
                'requires_intervention' => false,
            ],
            'patterns_detected' => [],
            'recommendations' => ['Insufficient data for analysis'],
            'analyzed_at' => date('Y-m-d H:i:s'),
        ];
    }
}

