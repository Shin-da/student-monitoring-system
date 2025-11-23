<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;
use PDOException;

class GradeModel
{
    private PDO $pdo;

    public function __construct()
    {
        $config = require BASE_PATH . '/config/config.php';
        $this->pdo = Database::connection($config['database']);
    }

    /**
     * Create a new grade entry
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO grades (
            student_id, section_id, subject_id, teacher_id, 
            grade_type, quarter, academic_year, 
            grade_value, max_score, description, remarks, graded_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['student_id'],
            $data['section_id'],
            $data['subject_id'],
            $data['teacher_id'],
            $data['grade_type'],
            $data['quarter'],
            $data['academic_year'] ?? $this->getCurrentAcademicYear(),
            $data['grade_value'],
            $data['max_score'] ?? 100.00,
            $data['description'] ?? null,
            $data['remarks'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update an existing grade
     */
    public function update(int $id, array $data): bool
    {
        $updates = [];
        $params = [];

        $allowedFields = ['grade_value', 'max_score', 'description', 'remarks', 'grade_type', 'quarter'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE grades SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a grade entry
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM grades WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get grade by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT g.*, 
                   u.name AS student_name,
                   st.lrn,
                   sec.name AS section_name,
                   sub.name AS subject_name,
                   t.user_id AS teacher_user_id
            FROM grades g
            JOIN students st ON g.student_id = st.id
            JOIN users u ON st.user_id = u.id
            JOIN sections sec ON g.section_id = sec.id
            JOIN subjects sub ON g.subject_id = sub.id
            JOIN teachers t ON g.teacher_id = t.id
            WHERE g.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get grades with filters
     */
    public function find(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (isset($filters['student_id'])) {
            $where[] = "g.student_id = ?";
            $params[] = $filters['student_id'];
        }

        if (isset($filters['section_id'])) {
            $where[] = "g.section_id = ?";
            $params[] = $filters['section_id'];
        }

        if (isset($filters['subject_id'])) {
            $where[] = "g.subject_id = ?";
            $params[] = $filters['subject_id'];
        }

        if (isset($filters['teacher_id'])) {
            $where[] = "g.teacher_id = ?";
            $params[] = $filters['teacher_id'];
        }

        if (isset($filters['grade_type'])) {
            $where[] = "g.grade_type = ?";
            $params[] = $filters['grade_type'];
        }

        if (isset($filters['quarter'])) {
            $where[] = "g.quarter = ?";
            $params[] = $filters['quarter'];
        }

        if (isset($filters['academic_year'])) {
            $where[] = "g.academic_year = ?";
            $params[] = $filters['academic_year'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "
            SELECT g.*, 
                   u.name AS student_name,
                   st.lrn,
                   sec.name AS section_name,
                   sub.name AS subject_name,
                   ROUND((g.grade_value / NULLIF(g.max_score, 0)) * 100, 2) AS percentage
            FROM grades g
            JOIN students st ON g.student_id = st.id
            JOIN users u ON st.user_id = u.id
            JOIN sections sec ON g.section_id = sec.id
            JOIN subjects sub ON g.subject_id = sub.id
            {$whereClause}
            ORDER BY g.graded_at DESC, u.name
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate quarterly grade for a student in a subject
     */
    public function calculateQuarterlyGrade(int $studentId, int $subjectId, int $quarter, string $academicYear): ?array
    {
        $sql = "
            SELECT 
                AVG(CASE WHEN grade_type = 'ww' THEN (grade_value / NULLIF(max_score, 0)) * 100 END) AS ww_average,
                AVG(CASE WHEN grade_type = 'pt' THEN (grade_value / NULLIF(max_score, 0)) * 100 END) AS pt_average,
                AVG(CASE WHEN grade_type = 'qe' THEN (grade_value / NULLIF(max_score, 0)) * 100 END) AS qe_average,
                COUNT(CASE WHEN grade_type = 'ww' THEN 1 END) AS ww_count,
                COUNT(CASE WHEN grade_type = 'pt' THEN 1 END) AS pt_count,
                COUNT(CASE WHEN grade_type = 'qe' THEN 1 END) AS qe_count
            FROM grades
            WHERE student_id = ? 
              AND subject_id = ? 
              AND quarter = ? 
              AND academic_year = ?
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$studentId, $subjectId, $quarter, $academicYear]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get subject weights (including attendance_percent)
        $subjectStmt = $this->pdo->prepare("SELECT ww_percent, pt_percent, qe_percent, attendance_percent FROM subjects WHERE id = ?");
        $subjectStmt->execute([$subjectId]);
        $subject = $subjectStmt->fetch(PDO::FETCH_ASSOC);

        $wwPercent = $subject['ww_percent'] ?? 20;
        $ptPercent = $subject['pt_percent'] ?? 50;
        $qePercent = $subject['qe_percent'] ?? 20;
        $attendancePercent = $subject['attendance_percent'] ?? 10;

        $wwAvg = (float)($result['ww_average'] ?? 0);
        $ptAvg = (float)($result['pt_average'] ?? 0);
        $qeAvg = (float)($result['qe_average'] ?? 0);

        // Calculate attendance average for the quarter (always calculate, even if no grades)
        $attendanceAvg = $this->calculateAttendanceAverage($studentId, $subjectId, $quarter, $academicYear);

        // If no grades exist, return attendance data only
        if (!$result || (!$result['ww_average'] && !$result['pt_average'] && !$result['qe_average'])) {
            return [
                'ww_average' => null,
                'pt_average' => null,
                'qe_average' => null,
                'attendance_average' => $attendanceAvg > 0 ? round($attendanceAvg, 2) : null,
                'ww_count' => 0,
                'pt_count' => 0,
                'qe_count' => 0,
                'final_grade' => null,
                'status' => 'No Grades',
                'ww_percent' => $wwPercent,
                'pt_percent' => $ptPercent,
                'qe_percent' => $qePercent,
                'attendance_percent' => $attendancePercent,
            ];
        }

        // Calculate final grade (including attendance)
        $finalGrade = round(
            ($wwAvg * $wwPercent / 100) +
            ($ptAvg * $ptPercent / 100) +
            ($qeAvg * $qePercent / 100) +
            ($attendanceAvg * $attendancePercent / 100),
            2
        );

        return [
            'ww_average' => round($wwAvg, 2),
            'pt_average' => round($ptAvg, 2),
            'qe_average' => round($qeAvg, 2),
            'attendance_average' => round($attendanceAvg, 2),
            'ww_count' => (int)$result['ww_count'],
            'pt_count' => (int)$result['pt_count'],
            'qe_count' => (int)$result['qe_count'],
            'final_grade' => $finalGrade,
            'status' => $finalGrade >= 75 ? 'Passed' : 'Failed',
            'ww_percent' => $wwPercent,
            'pt_percent' => $ptPercent,
            'qe_percent' => $qePercent,
            'attendance_percent' => $attendancePercent,
        ];
    }

    /**
     * Calculate attendance average for a student in a subject for a specific quarter
     */
    private function calculateAttendanceAverage(int $studentId, int $subjectId, int $quarter, string $academicYear): float
    {
        // Try multiple methods to get section_id:
        // 1. From grades table (if grades exist)
        // 2. From student's current section
        // 3. From attendance records (if attendance exists)
        // 4. From classes table (find which section this student is in for this subject)
        
        $sectionId = null;
        
        // Method 1: Try to get from grades table
        $sectionStmt = $this->pdo->prepare("
            SELECT DISTINCT section_id 
            FROM grades 
            WHERE student_id = ? AND subject_id = ? AND quarter = ? AND academic_year = ?
            LIMIT 1
        ");
        $sectionStmt->execute([$studentId, $subjectId, $quarter, $academicYear]);
        $section = $sectionStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($section && !empty($section['section_id'])) {
            $sectionId = (int)$section['section_id'];
        } else {
            // Method 2: Try to get from student's current section
            $studentStmt = $this->pdo->prepare("SELECT section_id FROM students WHERE id = ? LIMIT 1");
            $studentStmt->execute([$studentId]);
            $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student && !empty($student['section_id'])) {
                $sectionId = (int)$student['section_id'];
            } else {
                // Method 3: Try to get from attendance records
                $attendanceStmt = $this->pdo->prepare("
                    SELECT DISTINCT section_id 
                    FROM attendance 
                    WHERE student_id = ? AND subject_id = ?
                    LIMIT 1
                ");
                $attendanceStmt->execute([$studentId, $subjectId]);
                $attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($attendance && !empty($attendance['section_id'])) {
                    $sectionId = (int)$attendance['section_id'];
                } else {
                    // Method 4: Try to get from classes table via student_classes
                    $classStmt = $this->pdo->prepare("
                        SELECT DISTINCT c.section_id
                        FROM classes c
                        JOIN student_classes sc ON c.id = sc.class_id
                        WHERE sc.student_id = ? AND c.subject_id = ? AND c.is_active = 1
                        LIMIT 1
                    ");
                    $classStmt->execute([$studentId, $subjectId]);
                    $class = $classStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($class && !empty($class['section_id'])) {
                        $sectionId = (int)$class['section_id'];
                    }
                }
            }
        }
        
        if (!$sectionId) {
            return 0.0;
        }
        
        // Extract year from academic year (e.g., "2024-2025" -> 2024)
        $yearParts = explode('-', $academicYear);
        $startYear = (int)($yearParts[0] ?? date('Y'));
        
        // Determine date range for the quarter
        // Quarter 1: June-August (months 6-8)
        // Quarter 2: September-November (months 9-11)
        // Quarter 3: December-February (months 12, 1, 2) - spans two years
        // Quarter 4: March-May (months 3-5)
        
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
        
        if (!$startDate || !$endDate) {
            return 0.0;
        }
        
        // Calculate attendance percentage: (present + late + excused) / total * 100
        // Check if attendance table exists first
        try {
            $attendanceStmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) AS total_days,
                    COUNT(CASE WHEN status IN ('present', 'late', 'excused') THEN 1 END) AS present_days
                FROM attendance
                WHERE student_id = ?
                  AND section_id = ?
                  AND subject_id = ?
                  AND attendance_date >= ?
                  AND attendance_date <= ?
            ");
            $attendanceStmt->execute([$studentId, $sectionId, $subjectId, $startDate, $endDate]);
            $attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$attendance || (int)$attendance['total_days'] === 0) {
                return 0.0;
            }
            
            $totalDays = (int)$attendance['total_days'];
            $presentDays = (int)$attendance['present_days'];
            
            return round(($presentDays / $totalDays) * 100, 2);
        } catch (PDOException $e) {
            // If attendance table doesn't exist, return 0.0
            // This allows the system to work even without attendance data
            return 0.0;
        }
    }

    /**
     * Get all quarterly grades for a student
     */
    public function getStudentQuarterlyGrades(int $studentId, string $academicYear = null): array
    {
        $academicYear = $academicYear ?? $this->getCurrentAcademicYear();
        
        // Get subject/quarter combinations from grades
        $sql = "
            SELECT DISTINCT subject_id, quarter
            FROM grades
            WHERE student_id = ? AND academic_year = ?
            ORDER BY subject_id, quarter
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$studentId, $academicYear]);
        $combinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also get subject/quarter combinations from attendance (to include attendance even without grades)
        $attendanceSql = "
            SELECT DISTINCT subject_id, 
                   CASE 
                       WHEN MONTH(attendance_date) BETWEEN 6 AND 8 THEN 1
                       WHEN MONTH(attendance_date) BETWEEN 9 AND 11 THEN 2
                       WHEN MONTH(attendance_date) = 12 OR MONTH(attendance_date) BETWEEN 1 AND 2 THEN 3
                       WHEN MONTH(attendance_date) BETWEEN 3 AND 5 THEN 4
                   END AS quarter
            FROM attendance
            WHERE student_id = ?
            AND YEAR(attendance_date) BETWEEN ? AND ?
        ";
        
        // Extract year from academic year
        $yearParts = explode('-', $academicYear);
        $startYear = (int)($yearParts[0] ?? date('Y'));
        $endYear = (int)($yearParts[1] ?? $startYear + 1);
        
        $attendanceStmt = $this->pdo->prepare($attendanceSql);
        $attendanceStmt->execute([$studentId, $startYear, $endYear]);
        $attendanceCombinations = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge both sets and remove duplicates
        $allCombinations = [];
        foreach ($combinations as $combo) {
            $key = (int)$combo['subject_id'] . '_' . (int)$combo['quarter'];
            $allCombinations[$key] = [
                'subject_id' => (int)$combo['subject_id'],
                'quarter' => (int)$combo['quarter']
            ];
        }
        foreach ($attendanceCombinations as $combo) {
            if (!empty($combo['subject_id']) && !empty($combo['quarter'])) {
                $key = (int)$combo['subject_id'] . '_' . (int)$combo['quarter'];
                if (!isset($allCombinations[$key])) {
                    $allCombinations[$key] = [
                        'subject_id' => (int)$combo['subject_id'],
                        'quarter' => (int)$combo['quarter']
                    ];
                }
            }
        }

        $results = [];
        foreach ($allCombinations as $combo) {
            $grade = $this->calculateQuarterlyGrade(
                $studentId,
                (int)$combo['subject_id'],
                (int)$combo['quarter'],
                $academicYear
            );
            if ($grade) {
                $grade['subject_id'] = (int)$combo['subject_id'];
                $grade['quarter'] = (int)$combo['quarter'];
                $results[] = $grade;
            }
        }

        return $results;
    }

    /**
     * Bulk create grades
     */
    public function bulkCreate(array $grades): array
    {
        $this->pdo->beginTransaction();
        $insertedIds = [];
        $errors = [];

        try {
            foreach ($grades as $index => $gradeData) {
                try {
                    $id = $this->create($gradeData);
                    $insertedIds[] = $id;
                } catch (PDOException $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            if (!empty($errors) && empty($insertedIds)) {
                $this->pdo->rollBack();
                return ['success' => false, 'errors' => $errors];
            }

            $this->pdo->commit();
            return [
                'success' => true,
                'inserted' => count($insertedIds),
                'errors' => $errors,
                'ids' => $insertedIds
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Get current academic year
     */
    public function getCurrentAcademicYear(): string
    {
        $month = (int)date('n');
        $year = (int)date('Y');
        
        // Academic year typically runs from June to May
        if ($month >= 6) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }

    /**
     * Get grade statistics for a teacher
     */
    public function getTeacherGradeStats(int $teacherId, int $sectionId = null, int $subjectId = null): array
    {
        $where = ["g.teacher_id = ?"];
        $params = [$teacherId];

        if ($sectionId) {
            $where[] = "g.section_id = ?";
            $params[] = $sectionId;
        }

        if ($subjectId) {
            $where[] = "g.subject_id = ?";
            $params[] = $subjectId;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "
            SELECT 
                COUNT(DISTINCT g.student_id) AS total_students,
                COUNT(g.id) AS grades_entered,
                AVG((g.grade_value / NULLIF(g.max_score, 0)) * 100) AS avg_grade
            FROM grades g
            WHERE {$whereClause}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get pending grades count
        $pendingSql = "
            SELECT COUNT(DISTINCT sc.student_id) AS pending_count
            FROM student_classes sc
            JOIN classes c ON sc.class_id = c.id
            LEFT JOIN grades g ON g.student_id = sc.student_id 
                AND g.section_id = c.section_id 
                AND g.subject_id = c.subject_id 
                AND g.teacher_id = c.teacher_id
            WHERE c.teacher_id = ? 
              AND sc.status = 'enrolled'
              AND g.id IS NULL
        ";

        if ($sectionId) {
            $pendingSql .= " AND c.section_id = ?";
            $pendingParams = [$teacherId, $sectionId];
        } else {
            $pendingParams = [$teacherId];
        }

        if ($subjectId) {
            $pendingSql .= " AND c.subject_id = ?";
            $pendingParams[] = $subjectId;
        }

        $pendingStmt = $this->pdo->prepare($pendingSql);
        $pendingStmt->execute($pendingParams);
        $pending = $pendingStmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_students' => (int)($stats['total_students'] ?? 0),
            'grades_entered' => (int)($stats['grades_entered'] ?? 0),
            'pending_grades' => (int)($pending['pending_count'] ?? 0),
            'avg_grade' => round((float)($stats['avg_grade'] ?? 0), 1),
        ];
    }
}

