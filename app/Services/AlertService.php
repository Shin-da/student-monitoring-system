<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Helpers\Notification;
use PDO;
use PDOException;

/**
 * Alert Service
 * 
 * Automatically generates and manages performance alerts based on AI analysis.
 * 
 * Features:
 * - Automatic alert generation when risk is detected
 * - Smart notification routing (student, teacher, adviser, parent)
 * - Alert prioritization and severity levels
 * - Duplicate prevention (doesn't create duplicate alerts)
 * - Alert resolution tracking
 */
class AlertService
{
    private PDO $pdo;
    private PerformanceAnalyzer $analyzer;
    
    public function __construct(?PDO $pdo = null, ?PerformanceAnalyzer $analyzer = null)
    {
        if ($pdo === null) {
            $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
            $this->pdo = Database::connection($config['database']);
        } else {
            $this->pdo = $pdo;
        }
        
        $this->analyzer = $analyzer ?? new PerformanceAnalyzer($this->pdo);
    }
    
    /**
     * Check and generate alerts for all students
     * 
     * @param int|null $sectionId Optional: check only specific section
     * @param int|null $quarter Optional: specific quarter
     * @param string|null $academicYear Optional: specific academic year
     * @return array Generated alerts
     */
    public function checkAndGenerateAlerts(?int $sectionId = null, ?int $quarter = null, ?string $academicYear = null): array
    {
        try {
            $analyses = $this->analyzer->analyzeAllStudents($sectionId, $quarter, $academicYear);
            $generatedAlerts = [];
            
            foreach ($analyses as $analysis) {
                if ($analysis['is_at_risk']) {
                    $alerts = $this->generateAlertsForStudent($analysis);
                    $generatedAlerts = array_merge($generatedAlerts, $alerts);
                }
            }
            
            return $generatedAlerts;
        } catch (\Exception $e) {
            error_log("AlertService::checkAndGenerateAlerts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate alerts for a specific student based on analysis
     * 
     * @param array $analysis Analysis result from PerformanceAnalyzer
     * @return array Generated alert IDs
     */
    public function generateAlertsForStudent(array $analysis): array
    {
        $alertIds = [];
        
        try {
            // Generate alerts for each at-risk subject
            foreach ($analysis['at_risk_subjects'] ?? [] as $subjectAnalysis) {
                $alertId = $this->createSubjectAlert($analysis, $subjectAnalysis);
                if ($alertId) {
                    $alertIds[] = $alertId;
                }
            }
            
            // Generate overall alert if multiple subjects are at risk or high risk
            if (count($analysis['at_risk_subjects'] ?? []) >= 2 || 
                $analysis['risk_level'] === 'high') {
                $alertId = $this->createOverallAlert($analysis);
                if ($alertId) {
                    $alertIds[] = $alertId;
                }
            }
            
            // Generate attendance alert if attendance is poor
            if (($analysis['attendance_analysis']['status'] ?? 'good') === 'poor') {
                $alertId = $this->createAttendanceAlert($analysis);
                if ($alertId) {
                    $alertIds[] = $alertId;
                }
            }
            
            return $alertIds;
        } catch (\Exception $e) {
            error_log("AlertService::generateAlertsForStudent error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create alert for a specific subject
     */
    private function createSubjectAlert(array $analysis, array $subjectAnalysis): ?int
    {
        try {
            $studentId = (int)$analysis['student_id'];
            $subjectId = (int)$subjectAnalysis['subject_id'];
            $sectionId = (int)$analysis['section_id'];
            
            // Check if alert already exists (prevent duplicates)
            if ($this->alertExists($studentId, $subjectId, $sectionId, $analysis['quarter'], $analysis['academic_year'])) {
                return null;
            }
            
            // Get teacher for this subject
            $teacherId = $this->getTeacherForSubject($subjectId, $sectionId);
            if (!$teacherId) {
                error_log("AlertService: No teacher found for subject {$subjectId} in section {$sectionId}");
                return null;
            }
            
            // Determine severity
            $severity = $subjectAnalysis['risk_level'] === 'high' ? 'high' : 'medium';
            
            // Create alert message
            $finalGrade = $subjectAnalysis['final_grade'] ?? 0;
            $subjectName = $subjectAnalysis['subject_name'];
            $reasons = implode('; ', $subjectAnalysis['reasons'] ?? []);
            
            $title = "Academic Risk Alert: {$subjectName}";
            $description = "Student {$analysis['student_name']} is at risk in {$subjectName}. ";
            $description .= "Current grade: {$finalGrade}. ";
            if (!empty($reasons)) {
                $description .= "Reasons: {$reasons}.";
            }
            
            // Insert into performance_alerts table
            $stmt = $this->pdo->prepare("
                INSERT INTO performance_alerts (
                    student_id, teacher_id, section_id, subject_id,
                    alert_type, title, description, severity, status,
                    quarter, academic_year, metadata, created_at
                ) VALUES (
                    ?, ?, ?, ?,
                    'academic_risk', ?, ?, ?, 'active',
                    ?, ?, ?, NOW()
                )
            ");
            
            $metadata = json_encode([
                'risk_level' => $subjectAnalysis['risk_level'],
                'risk_score' => $subjectAnalysis['risk_score'],
                'final_grade' => $finalGrade,
                'reasons' => $subjectAnalysis['reasons'] ?? [],
                'trend' => $subjectAnalysis['trend'] ?? 'unknown',
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt->execute([
                $studentId,
                $teacherId,
                $sectionId,
                $subjectId,
                $title,
                $description,
                $severity,
                $analysis['quarter'],
                $analysis['academic_year'],
                $metadata,
            ]);
            
            $alertId = (int)$this->pdo->lastInsertId();
            
            // Notify stakeholders
            $this->notifyStakeholders($analysis, $subjectAnalysis, $alertId, $title, $description);
            
            return $alertId;
        } catch (PDOException $e) {
            error_log("AlertService::createSubjectAlert error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create overall performance alert
     */
    private function createOverallAlert(array $analysis): ?int
    {
        try {
            $studentId = (int)$analysis['student_id'];
            $sectionId = (int)$analysis['section_id'];
            
            // Check if overall alert already exists
            if ($this->alertExists($studentId, null, $sectionId, $analysis['quarter'], $analysis['academic_year'], 'overall_risk')) {
                return null;
            }
            
            // Get adviser for this section
            $adviserId = $this->getAdviserForSection($sectionId);
            if (!$adviserId) {
                // Use first teacher if no adviser
                $adviserId = $this->getFirstTeacherForSection($sectionId);
            }
            
            if (!$adviserId) {
                error_log("AlertService: No teacher/adviser found for section {$sectionId}");
                return null;
            }
            
            $severity = $analysis['risk_level'] === 'high' ? 'high' : 'medium';
            $failingCount = $analysis['failing_subjects'] ?? 0;
            $atRiskCount = count($analysis['at_risk_subjects'] ?? []);
            
            $title = "Overall Academic Risk Alert";
            $description = "Student {$analysis['student_name']} is at risk across multiple subjects. ";
            $description .= "At-risk subjects: {$atRiskCount}. ";
            if ($failingCount > 0) {
                $description .= "Failing subjects: {$failingCount}. ";
            }
            $description .= "Overall risk level: {$analysis['risk_level']}.";
            
            // Insert alert
            $stmt = $this->pdo->prepare("
                INSERT INTO performance_alerts (
                    student_id, teacher_id, section_id, subject_id,
                    alert_type, title, description, severity, status,
                    quarter, academic_year, metadata, created_at
                ) VALUES (
                    ?, ?, ?, NULL,
                    'overall_risk', ?, ?, ?, 'active',
                    ?, ?, ?, NOW()
                )
            ");
            
            $metadata = json_encode([
                'risk_level' => $analysis['risk_level'],
                'risk_score' => $analysis['overall_risk_score'],
                'at_risk_subjects_count' => $atRiskCount,
                'failing_subjects_count' => $failingCount,
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt->execute([
                $studentId,
                $adviserId,
                $sectionId,
                $title,
                $description,
                $severity,
                $analysis['quarter'],
                $analysis['academic_year'],
                $metadata,
            ]);
            
            $alertId = (int)$this->pdo->lastInsertId();
            
            // Notify stakeholders
            $this->notifyStakeholders($analysis, null, $alertId, $title, $description, true);
            
            return $alertId;
        } catch (PDOException $e) {
            error_log("AlertService::createOverallAlert error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create attendance alert
     */
    private function createAttendanceAlert(array $analysis): ?int
    {
        try {
            $studentId = (int)$analysis['student_id'];
            $sectionId = (int)$analysis['section_id'];
            $attendance = $analysis['attendance_analysis'] ?? [];
            
            // Check if attendance alert already exists
            if ($this->alertExists($studentId, null, $sectionId, $analysis['quarter'], $analysis['academic_year'], 'attendance')) {
                return null;
            }
            
            $adviserId = $this->getAdviserForSection($sectionId);
            if (!$adviserId) {
                $adviserId = $this->getFirstTeacherForSection($sectionId);
            }
            
            if (!$adviserId) {
                return null;
            }
            
            $attendancePercent = $attendance['percentage'] ?? 100;
            $severity = $attendancePercent < 70 ? 'high' : 'medium';
            
            $title = "Attendance Concern";
            $description = "Student {$analysis['student_name']} has low attendance ({$attendancePercent}%). ";
            $description .= "Present: {$attendance['present_days']}/{$attendance['total_days']} days.";
            
            $stmt = $this->pdo->prepare("
                INSERT INTO performance_alerts (
                    student_id, teacher_id, section_id, subject_id,
                    alert_type, title, description, severity, status,
                    quarter, academic_year, metadata, created_at
                ) VALUES (
                    ?, ?, ?, NULL,
                    'attendance', ?, ?, ?, 'active',
                    ?, ?, ?, NOW()
                )
            ");
            
            $metadata = json_encode([
                'attendance_percentage' => $attendancePercent,
                'total_days' => $attendance['total_days'] ?? 0,
                'present_days' => $attendance['present_days'] ?? 0,
                'absent_days' => $attendance['absent_days'] ?? 0,
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt->execute([
                $studentId,
                $adviserId,
                $sectionId,
                $title,
                $description,
                $severity,
                $analysis['quarter'],
                $analysis['academic_year'],
                $metadata,
            ]);
            
            $alertId = (int)$this->pdo->lastInsertId();
            
            // Notify stakeholders
            $this->notifyStakeholders($analysis, null, $alertId, $title, $description, false, true);
            
            return $alertId;
        } catch (PDOException $e) {
            error_log("AlertService::createAttendanceAlert error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Notify all stakeholders about an alert
     */
    private function notifyStakeholders(
        array $analysis,
        ?array $subjectAnalysis,
        int $alertId,
        string $title,
        string $description,
        bool $isOverall = false,
        bool $isAttendance = false
    ): void {
        try {
            $studentId = (int)$analysis['student_id'];
            
            // Get student user ID if not in analysis
            $studentUserId = (int)($analysis['student_user_id'] ?? 0);
            if ($studentUserId === 0) {
                try {
                    $stmt = $this->pdo->prepare("SELECT user_id FROM students WHERE id = ?");
                    $stmt->execute([$studentId]);
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);
                    $studentUserId = $student ? (int)$student['user_id'] : 0;
                } catch (PDOException $e) {
                    error_log("AlertService::notifyStakeholders: Error getting student user_id: " . $e->getMessage());
                }
            }
            
            // Notify student
            if ($studentUserId > 0) {
                $category = $isAttendance ? 'attendance_alert' : ($isOverall ? 'overall_risk_alert' : 'subject_risk_alert');
                $type = ($subjectAnalysis['risk_level'] ?? $analysis['risk_level']) === 'high' ? 'error' : 'warning';
                
                Notification::create(
                    recipientIds: $studentUserId,
                    type: $type,
                    category: $category,
                    title: $title,
                    message: $description,
                    options: [
                        'link' => '/student/alerts',
                        'priority' => ($subjectAnalysis['risk_level'] ?? $analysis['risk_level']) === 'high' ? 'high' : 'normal',
                        'metadata' => [
                            'alert_id' => $alertId,
                            'student_id' => $studentId,
                            'risk_level' => $subjectAnalysis['risk_level'] ?? $analysis['risk_level'],
                        ],
                    ]
                );
            }
            
            // Notify parents
            Notification::createForParents(
                studentId: $studentId,
                type: ($subjectAnalysis['risk_level'] ?? $analysis['risk_level']) === 'high' ? 'error' : 'warning',
                category: $isAttendance ? 'attendance_alert' : 'academic_risk_alert',
                title: $title,
                message: $description,
                options: [
                    'priority' => 'high',
                    'link' => '/parent/grades',
                    'metadata' => [
                        'alert_id' => $alertId,
                        'student_id' => $studentId,
                    ],
                ]
            );
        } catch (\Exception $e) {
            error_log("AlertService::notifyStakeholders error: " . $e->getMessage());
            // Don't throw - notification failure shouldn't break alert creation
        }
    }
    
    /**
     * Check if alert already exists (prevent duplicates)
     */
    private function alertExists(int $studentId, ?int $subjectId, int $sectionId, int $quarter, string $academicYear, string $alertType = 'academic_risk'): bool
    {
        try {
            $sql = "
                SELECT COUNT(*) 
                FROM performance_alerts 
                WHERE student_id = ? 
                  AND section_id = ?
                  AND quarter = ?
                  AND academic_year = ?
                  AND alert_type = ?
                  AND status = 'active'
            ";
            
            $params = [$studentId, $sectionId, $quarter, $academicYear, $alertType];
            
            if ($subjectId !== null) {
                $sql .= " AND subject_id = ?";
                $params[] = $subjectId;
            } else {
                $sql .= " AND subject_id IS NULL";
            }
            
            // Check for alerts created in the last 7 days (to prevent spam)
            $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("AlertService::alertExists error: " . $e->getMessage());
            return false; // If check fails, allow alert creation
        }
    }
    
    /**
     * Get teacher ID for a subject in a section
     */
    private function getTeacherForSubject(int $subjectId, int $sectionId): ?int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.user_id
                FROM classes c
                JOIN teachers t ON c.teacher_id = t.id
                WHERE c.subject_id = ? 
                  AND c.section_id = ?
                  AND c.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$subjectId, $sectionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['user_id'] : null;
        } catch (PDOException $e) {
            error_log("AlertService::getTeacherForSubject error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get adviser user ID for a section
     */
    private function getAdviserForSection(int $sectionId): ?int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT adviser_id
                FROM sections
                WHERE id = ? AND adviser_id IS NOT NULL
            ");
            $stmt->execute([$sectionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['adviser_id'] : null;
        } catch (PDOException $e) {
            error_log("AlertService::getAdviserForSection error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get first teacher user ID for a section
     */
    private function getFirstTeacherForSection(int $sectionId): ?int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT t.user_id
                FROM classes c
                JOIN teachers t ON c.teacher_id = t.id
                WHERE c.section_id = ? AND c.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$sectionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['user_id'] : null;
        } catch (PDOException $e) {
            error_log("AlertService::getFirstTeacherForSection error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Resolve an alert
     */
    public function resolveAlert(int $alertId, int $resolvedBy): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE performance_alerts
                SET status = 'resolved',
                    resolved_at = NOW(),
                    resolved_by = ?
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$resolvedBy, $alertId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("AlertService::resolveAlert error: " . $e->getMessage());
            return false;
        }
    }
}

