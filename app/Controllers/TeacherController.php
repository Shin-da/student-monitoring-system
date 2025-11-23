<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use Helpers\Csrf;
use PDO;

class TeacherController extends Controller
{
    private function getDatabaseConnection()
    {
        $config = require BASE_PATH . '/config/config.php';
        $pdo = new \PDO(
            'mysql:host=' . $config['database']['host'] . ';dbname=' . $config['database']['database'],
            $config['database']['username'],
            $config['database']['password']
        );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    private function resolveTeacher(\PDO $pdo, int $userId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $teacher = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $teacher ?: null;
    }

    public function dashboard(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array($user['role'] ?? '', ['teacher', 'adviser'], true)) {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();
            $teacher = $this->resolveTeacher($pdo, (int)$user['id']);

            if (!$teacher) {
                \Helpers\ErrorHandler::notFound('Teacher profile not found.');
                return;
            }

            $stats = $this->getTeacherStats($pdo, (int)$teacher['id']);
            $sections = $this->getTeacherSections($pdo, (int)$teacher['id'], (int)$user['id']);
            $activities = $this->getRecentActivities($pdo, (int)$teacher['id']);
            $alerts = $this->getPendingAlerts($pdo, (int)$teacher['id']);

            $this->view->render('teacher/dashboard', [
                'title' => 'Teacher Dashboard',
                'user' => $user,
                'activeNav' => 'dashboard',
                'showBack' => false,
                'stats' => $stats,
                'sections' => $sections,
                'activities' => $activities,
                'alerts' => $alerts,
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load teacher dashboard: ' . $e->getMessage());
        }
    }

    private function getTeacherStats($pdo, $teacherId)
    {
        try {
            $stmt = $pdo->prepare('\n                SELECT COUNT(DISTINCT c.section_id)\n                FROM classes c\n                WHERE c.teacher_id = ? AND c.is_active = 1\n            ');
            $stmt->execute([$teacherId]);
            $sectionsCount = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare('\n                SELECT COUNT(DISTINCT sc.student_id)\n                FROM student_classes sc\n                JOIN classes c ON sc.class_id = c.id\n                WHERE c.teacher_id = ? AND c.is_active = 1 AND sc.status = "enrolled"\n            ');
            $stmt->execute([$teacherId]);
            $studentsCount = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare('\n                SELECT COUNT(DISTINCT c.subject_id)\n                FROM classes c\n                WHERE c.teacher_id = ? AND c.is_active = 1\n            ');
            $stmt->execute([$teacherId]);
            $subjectsCount = (int)$stmt->fetchColumn();

            $alertsCount = 0;
            try {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM performance_alerts WHERE teacher_id = ? AND status = "active"');
                $stmt->execute([$teacherId]);
                $alertsCount = (int)$stmt->fetchColumn();
            } catch (\Throwable $inner) {
                $alertsCount = 0;
            }

            return [
                'sections_count' => $sectionsCount,
                'students_count' => $studentsCount,
                'subjects_count' => $subjectsCount,
                'alerts_count' => $alertsCount,
            ];
        } catch (\Throwable $e) {
            return [
                'sections_count' => 0,
                'students_count' => 0,
                'subjects_count' => 0,
                'alerts_count' => 0,
            ];
        }
    }

    private function getTeacherSections($pdo, $teacherId, int $teacherUserId)
    {
        try {
            // Get sections where teacher teaches classes
            $stmt = $pdo->prepare('
                SELECT
                    c.id AS class_id,
                    c.section_id,
                    c.subject_id,
                    c.schedule,
                    c.room AS class_room,
                    sec.name AS section_name,
                    sec.grade_level,
                    sec.room AS section_room,
                    sub.name AS subject_name,
                    sub.code AS subject_code,
                    (SELECT COUNT(*) FROM student_classes sc WHERE sc.class_id = c.id AND sc.status = "enrolled") AS student_count,
                    (SELECT COUNT(*) FROM attendance att WHERE att.section_id = c.section_id AND att.subject_id = c.subject_id) AS attendance_records,
                    (sec.adviser_id = :adviser_user_id) AS is_adviser
                FROM classes c
                JOIN sections sec ON c.section_id = sec.id
                JOIN subjects sub ON c.subject_id = sub.id
                WHERE c.teacher_id = :teacher_id AND c.is_active = 1
                ORDER BY sec.grade_level, sec.name, sub.name
            ');
            $stmt->bindValue(':teacher_id', $teacherId, \PDO::PARAM_INT);
            $stmt->bindValue(':adviser_user_id', $teacherUserId, \PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Also get sections where teacher is assigned as adviser (even if no classes taught)
            $stmt = $pdo->prepare('
                SELECT DISTINCT
                    NULL AS class_id,
                    sec.id AS section_id,
                    NULL AS subject_id,
                    NULL AS schedule,
                    NULL AS class_room,
                    sec.name AS section_name,
                    sec.grade_level,
                    sec.room AS section_room,
                    NULL AS subject_name,
                    NULL AS subject_code,
                    (SELECT COUNT(*) FROM students s WHERE s.section_id = sec.id) AS student_count,
                    0 AS attendance_records,
                    1 AS is_adviser
                FROM sections sec
                WHERE sec.adviser_id = :adviser_user_id AND sec.is_active = 1
                AND sec.id NOT IN (
                    SELECT DISTINCT c.section_id 
                    FROM classes c 
                    WHERE c.teacher_id = :teacher_id AND c.is_active = 1
                )
                ORDER BY sec.grade_level, sec.name
            ');
            $stmt->bindValue(':teacher_id', $teacherId, \PDO::PARAM_INT);
            $stmt->bindValue(':adviser_user_id', $teacherUserId, \PDO::PARAM_INT);
            $stmt->execute();
            $advisoryRows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Merge both results
            $allRows = array_merge($rows, $advisoryRows);

            return array_map(static function (array $row): array {
                return [
                    'class_id' => $row['class_id'] ? (int)$row['class_id'] : null,
                    'section_id' => (int)$row['section_id'],
                    'subject_id' => $row['subject_id'] ? (int)$row['subject_id'] : null,
                    'section_name' => $row['section_name'],
                    'grade_level' => (int)$row['grade_level'],
                    'room' => $row['section_room'] ?? $row['class_room'],
                    'schedule' => $row['schedule'],
                    'subject_name' => $row['subject_name'],
                    'subject_code' => $row['subject_code'],
                    'student_count' => (int)($row['student_count'] ?? 0),
                    'attendance_records' => (int)($row['attendance_records'] ?? 0),
                    'is_adviser' => (bool)$row['is_adviser'],
                ];
            }, $allRows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getRecentActivities($pdo, $teacherId)
    {
        try {
            $stmt = $pdo->prepare('\n                SELECT activity_type, description, target_type, target_id, created_at\n                FROM teacher_activities\n                WHERE teacher_id = ?\n                ORDER BY created_at DESC\n                LIMIT 10\n            ');
            $stmt->execute([$teacherId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getPendingAlerts($pdo, $teacherId)
    {
        try {
            $stmt = $pdo->prepare('\n                SELECT \n                    pa.id,\n                    pa.alert_type,\n                    pa.title,\n                    pa.description,\n                    pa.severity,\n                    pa.created_at,\n                    u.name AS student_name,\n                    sec.name AS section_name,\n                    sub.name AS subject_name\n                FROM performance_alerts pa\n                JOIN students st ON pa.student_id = st.id\n                JOIN users u ON st.user_id = u.id\n                JOIN sections sec ON pa.section_id = sec.id\n                JOIN subjects sub ON pa.subject_id = sub.id\n                WHERE pa.teacher_id = ? AND pa.status = "active"\n                ORDER BY pa.created_at DESC\n                LIMIT 5\n            ');
            $stmt->execute([$teacherId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function alerts(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array($user['role'] ?? '', ['teacher', 'adviser'], true)) {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();
            $teacherId = $user['id'];

            // Get all alerts for this teacher
            $stmt = $pdo->prepare("
                SELECT 
                    pa.id,
                    pa.alert_type,
                    pa.title,
                    pa.description,
                    pa.severity,
                    pa.status,
                    pa.created_at,
                    pa.resolved_at,
                    u.name as student_name,
                    s.class_name,
                    sub.name as subject_name,
                    resolver.name as resolved_by_name
                FROM performance_alerts pa
                JOIN students st ON pa.student_id = st.id
                JOIN users u ON st.user_id = u.id
                JOIN sections s ON pa.section_id = s.section_id
                JOIN subjects sub ON pa.subject_id = sub.id
                LEFT JOIN users resolver ON pa.resolved_by = resolver.id
                WHERE pa.teacher_id = ?
                ORDER BY pa.created_at DESC
            ");
            $stmt->execute([$teacherId]);
            $alerts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view->render('teacher/alerts', [
                'title' => 'Alerts',
                'user' => $user,
                'alerts' => $alerts,
                'activeNav' => 'alerts',
                'showBack' => true,
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load alerts: ' . $e->getMessage());
        }
    }

    public function grades(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array(($user['role'] ?? ''), ['teacher','adviser'], true)) {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        $sectionId = isset($_GET['section']) ? (int)$_GET['section'] : null;
        $subjectId = isset($_GET['subject']) ? (int)$_GET['subject'] : null;
        $gradeType = $_GET['grade_type'] ?? null;
        $studentId = isset($_GET['student']) ? (int)$_GET['student'] : null;

        try {
            $pdo = $this->getDatabaseConnection();
            $teacher = $this->resolveTeacher($pdo, (int)$user['id']);

            if (!$teacher) {
                \Helpers\ErrorHandler::notFound('Teacher profile not found.');
                return;
            }

            $sections = $this->getTeacherSections($pdo, (int)$teacher['id'], (int)$user['id']);
            $subjects = array_values(array_reduce($sections, static function ($carry, $section) {
                $carry[$section['subject_id']] = [
                    'id' => $section['subject_id'],
                    'name' => $section['subject_name'],
                    'grade_level' => $section['grade_level'],
                ];
                return $carry;
            }, []));

            // Use GradeModel for grade operations
            $gradeModel = new \Models\GradeModel();
            $stats = $gradeModel->getTeacherGradeStats((int)$teacher['id'], $sectionId, $subjectId);
            
            $filters = ['teacher_id' => (int)$teacher['id']];
            if ($sectionId) $filters['section_id'] = $sectionId;
            if ($subjectId) $filters['subject_id'] = $subjectId;
            if ($gradeType) $filters['grade_type'] = $gradeType;
            if ($studentId) $filters['student_id'] = $studentId;
            
            $grades = $gradeModel->find($filters);
            $assignments = $this->getTeacherAssignments($pdo, (int)$teacher['id'], $sectionId, $subjectId);

            // Get students for grade entry (from teacher's sections or advisory sections)
            $students = [];
            
            if ($sectionId) {
                // Get students from specific section that teacher teaches OR is adviser for
                $stmt = $pdo->prepare("
                    SELECT DISTINCT s.id, u.name, s.lrn, sec.name AS section_name, s.section_id
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    JOIN sections sec ON s.section_id = sec.id
                    WHERE s.section_id = ? AND (
                        EXISTS (
                            SELECT 1 FROM classes c 
                            WHERE c.section_id = ? AND c.teacher_id = ? AND c.is_active = 1
                        )
                        OR sec.adviser_id = ?
                    )
                    ORDER BY u.name
                ");
                $stmt->execute([$sectionId, $sectionId, (int)$teacher['id'], (int)$user['id']]);
                $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                // Get all students from sections that teacher teaches OR is adviser for
                $stmt = $pdo->prepare("
                    SELECT DISTINCT s.id, u.name, s.lrn, sec.name AS section_name, s.section_id
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    JOIN sections sec ON s.section_id = sec.id
                    WHERE (
                        EXISTS (
                            SELECT 1 FROM classes c 
                            WHERE c.section_id = sec.id AND c.teacher_id = ? AND c.is_active = 1
                        )
                        OR sec.adviser_id = ?
                    )
                    ORDER BY sec.name, u.name
                ");
                $stmt->execute([(int)$teacher['id'], (int)$user['id']]);
                $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            // Get student details and quarterly grades if student is selected
            $selectedStudent = null;
            $studentQuarterlyGrades = [];
            if ($studentId) {
                $stmt = $pdo->prepare("
                    SELECT s.id, u.name, s.lrn, s.grade_level, sec.name AS section_name
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN sections sec ON s.section_id = sec.id
                    WHERE s.id = ?
                ");
                $stmt->execute([$studentId]);
                $selectedStudent = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($selectedStudent) {
                    $academicYear = $gradeModel->getCurrentAcademicYear();
                    $quarterlyGrades = $gradeModel->getStudentQuarterlyGrades($studentId, $academicYear);
                    
                    // Add subject names to quarterly grades
                    foreach ($quarterlyGrades as &$grade) {
                        $subjectId = $grade['subject_id'] ?? 0;
                        foreach ($subjects as $subject) {
                            if ((int)$subject['id'] === $subjectId) {
                                $grade['subject_name'] = $subject['name'];
                                break;
                            }
                        }
                    }
                    unset($grade);
                    $studentQuarterlyGrades = $quarterlyGrades;
                }
            }

            $this->view->render('teacher/grades', [
                'title' => 'Grade Management',
                'user' => $user,
                'activeNav' => 'grades',
                'sections' => $sections,
                'subjects' => $subjects,
                'students' => $students,
                'stats' => $stats,
                'grades' => $grades,
                'assignments' => $assignments,
                'selectedStudent' => $selectedStudent,
                'studentQuarterlyGrades' => $studentQuarterlyGrades,
                'filters' => [
                    'section_id' => $sectionId,
                    'subject_id' => $subjectId,
                    'grade_type' => $gradeType,
                    'student_id' => $studentId,
                ],
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load grades: ' . $e->getMessage());
        }
    }

    private function getGradeStats($pdo, $teacherId, $sectionId = null, $subjectId = null)
    {
        $params = [$teacherId];
        $whereClasses = 'c.teacher_id = ? AND c.is_active = 1';

        if ($sectionId) {
            $whereClasses .= ' AND c.section_id = ?';
            $params[] = $sectionId;
        }

        if ($subjectId) {
            $whereClasses .= ' AND c.subject_id = ?';
            $params[] = $subjectId;
        }

        try {
            $stmt = $pdo->prepare("\n                SELECT COUNT(DISTINCT sc.student_id)\n                FROM student_classes sc\n                JOIN classes c ON sc.class_id = c.id\n                WHERE $whereClasses AND sc.status = 'enrolled'\n            ");
            $stmt->execute($params);
            $totalStudents = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("\n                SELECT COUNT(*)\n                FROM grades g\n                WHERE g.teacher_id = ?" . ($sectionId ? ' AND g.section_id = ?' : '') . ($subjectId ? ' AND g.subject_id = ?' : '') . '\n            ');
            $stmt->execute(array_filter([$teacherId, $sectionId, $subjectId], static fn($value) => $value !== null));
            $gradesEntered = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("\n                SELECT COUNT(*)\n                FROM student_classes sc\n                JOIN classes c ON sc.class_id = c.id\n                LEFT JOIN grades g ON g.student_id = sc.student_id\n                    AND g.section_id = c.section_id\n                    AND g.subject_id = c.subject_id\n                    AND g.teacher_id = c.teacher_id\n                WHERE $whereClasses AND sc.status = 'enrolled' AND g.id IS NULL\n            ");
            $stmt->execute($params);
            $pendingGrades = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("\n                SELECT COALESCE(AVG(g.grade_value), 0)\n                FROM grades g\n                WHERE g.teacher_id = ?" . ($sectionId ? ' AND g.section_id = ?' : '') . ($subjectId ? ' AND g.subject_id = ?' : '') . '\n            ');
            $stmt->execute(array_filter([$teacherId, $sectionId, $subjectId], static fn($value) => $value !== null));
            $avgGrade = (float)$stmt->fetchColumn();

            return [
                'total_students' => $totalStudents,
                'grades_entered' => $gradesEntered,
                'pending_grades' => $pendingGrades,
                'avg_grade' => round($avgGrade, 1),
            ];
        } catch (\Throwable $e) {
            return [
                'total_students' => 0,
                'grades_entered' => 0,
                'pending_grades' => 0,
                'avg_grade' => 0.0,
            ];
        }
    }

    private function getGradesWithDetails($pdo, $teacherId, $sectionId = null, $subjectId = null, $gradeType = null)
    {
        $whereClause = 'WHERE g.teacher_id = ?';
        $params = [$teacherId];

        if ($sectionId) {
            $whereClause .= ' AND g.section_id = ?';
            $params[] = $sectionId;
        }

        if ($subjectId) {
            $whereClause .= ' AND g.subject_id = ?';
            $params[] = $subjectId;
        }

        if ($gradeType) {
            $whereClause .= ' AND g.grade_type = ?';
            $params[] = $gradeType;
        }

        try {
            $stmt = $pdo->prepare("\n                SELECT \n                    g.id,\n                    g.grade_value,\n                    g.max_score,\n                    g.grade_type,\n                    g.description,\n                    g.graded_at,\n                    u.name AS student_name,\n                    st.lrn,\n                    sec.name AS section_name,\n                    sub.name AS subject_name,\n                    g.section_id,\n                    g.subject_id\n                FROM grades g\n                JOIN students st ON g.student_id = st.id\n                JOIN users u ON st.user_id = u.id\n                JOIN sections sec ON g.section_id = sec.id\n                JOIN subjects sub ON g.subject_id = sub.id\n                $whereClause\n                ORDER BY g.graded_at DESC, u.name\n            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            return array_map(static function (array $row): array {
                $gradeValue = (float)($row['grade_value'] ?? 0);
                $maxScore = (float)($row['max_score'] ?? 100);
                $percentage = $maxScore > 0 ? round(($gradeValue / $maxScore) * 100, 1) : 0;
                $status = $gradeValue >= 75 ? 'passing' : 'failing';

                return [
                    'id' => (int)$row['id'],
                    'grade_value' => $gradeValue,
                    'max_score' => $maxScore,
                    'grade_type' => $row['grade_type'],
                    'description' => $row['description'],
                    'graded_at' => $row['graded_at'],
                    'student_name' => $row['student_name'],
                    'lrn' => $row['lrn'],
                    'section_name' => $row['section_name'],
                    'subject_name' => $row['subject_name'],
                    'percentage' => $percentage,
                    'status' => $status,
                ];
            }, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getTeacherAssignments($pdo, $teacherId, $sectionId = null, $subjectId = null)
    {
        $whereClause = "WHERE a.teacher_id = ? AND a.is_active = 1";
        $params = [$teacherId];

        if ($sectionId) {
            $whereClause .= " AND a.section_id = ?";
            $params[] = $sectionId;
        }

        if ($subjectId) {
            $whereClause .= " AND a.subject_id = ?";
            $params[] = $subjectId;
        }

        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.title,
                a.description,
                a.assignment_type,
                a.max_score,
                a.due_date,
                a.created_at,
                sec.name as section_name,
                sub.name as subject_name,
                COUNT(g.id) as grades_count,
                COUNT(DISTINCT st.id) as total_students
            FROM assignments a
            JOIN sections sec ON a.section_id = sec.id
            JOIN subjects sub ON a.subject_id = sub.id
            LEFT JOIN students st ON st.section_id = a.section_id
            LEFT JOIN grades g ON g.section_id = a.section_id AND g.subject_id = a.subject_id AND g.description = a.title
            $whereClause
            GROUP BY a.id, a.title, a.description, a.assignment_type, a.max_score, a.due_date, a.created_at, sec.name, sub.name
            ORDER BY a.due_date DESC, a.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function classes(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array(($user['role'] ?? ''), ['teacher','adviser'], true)) {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }
        $this->view->render('teacher/classes', [
            'title' => 'Class Management',
            'user' => $user,
            'activeNav' => 'classes',
        ], 'layouts/dashboard');
    }

    public function sections(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array(($user['role'] ?? ''), ['teacher', 'adviser'], true)) {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();
            $teacher = $this->resolveTeacher($pdo, (int)$user['id']);

            if (!$teacher) {
                \Helpers\ErrorHandler::notFound('Teacher profile not found.');
                return;
            }

            $sections = $this->getTeacherSections($pdo, (int)$teacher['id'], (int)$user['id']);

            $totalStudents = array_sum(array_map(static fn($section) => $section['student_count'] ?? 0, $sections));
            $statistics = [
                'sections' => count($sections),
                'students' => $totalStudents,
                'subjects' => count(array_unique(array_map(static fn($section) => $section['subject_id'], $sections))),
                'advisory_sections' => count(array_filter($sections, static fn($section) => !empty($section['is_adviser']))),
            ];

            $this->view->render('teacher/sections', [
                'title' => 'My Sections',
                'user' => $user,
                'sections' => $sections,
                'statistics' => $statistics,
                'activeNav' => 'classes',
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load sections: ' . $e->getMessage());
        }
    }

    public function assignments(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        $sectionId = isset($_GET['section']) ? (int)$_GET['section'] : null;
        $subjectId = isset($_GET['subject']) ? (int)$_GET['subject'] : null;
        $status = $_GET['status'] ?? null;

        try {
            $pdo = $this->getDatabaseConnection();
            $teacher = $this->resolveTeacher($pdo, (int)$user['id']);

            if (!$teacher) {
                \Helpers\ErrorHandler::notFound('Teacher profile not found.');
                return;
            }

            $assignments = $this->getTeacherAssignments($pdo, (int)$teacher['id'], $sectionId, $subjectId);

            if ($status) {
                $assignments = array_values(array_filter($assignments, static function ($assignment) use ($status) {
                    return ($assignment['status'] ?? null) === $status;
                }));
            }

            $totalAssignments = count($assignments);
            $activeAssignments = count(array_filter($assignments, static fn($assignment) => ($assignment['status'] ?? '') === 'active'));
            $overdueAssignments = count(array_filter($assignments, static fn($assignment) => ($assignment['status'] ?? '') === 'overdue'));
            $avgCompletion = $totalAssignments > 0
                ? round(array_sum(array_map(static fn($assignment) => (float)($assignment['completion_percentage'] ?? 0), $assignments)) / $totalAssignments, 1)
                : 0.0;

            $sections = $this->getTeacherSections($pdo, (int)$teacher['id'], (int)$user['id']);
            $subjects = array_values(array_reduce($sections, static function ($carry, $section) {
                $carry[$section['subject_id']] = [
                    'id' => $section['subject_id'],
                    'name' => $section['subject_name'],
                    'grade_level' => $section['grade_level'],
                ];
                return $carry;
            }, []));

            $this->view->render('teacher/assignments', [
                'title' => 'Assignment Management',
                'user' => $user,
                'activeNav' => 'assignments',
                'sections' => $sections,
                'subjects' => $subjects,
                'stats' => [
                    'total_assignments' => $totalAssignments,
                    'active_assignments' => $activeAssignments,
                    'overdue_assignments' => $overdueAssignments,
                    'avg_completion' => $avgCompletion,
                ],
                'assignments' => $assignments,
                'filters' => [
                    'section_id' => $sectionId,
                    'subject_id' => $subjectId,
                    'status' => $status,
                ],
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load assignments: ' . $e->getMessage());
        }
    }

    private function getAssignmentStats($pdo, $teacherId, $sectionId = null, $subjectId = null)
    {
        $whereClause = "WHERE a.teacher_id = ? AND a.is_active = 1";
        $params = [$teacherId];

        if ($sectionId) {
            $whereClause .= " AND a.section_id = ?";
            $params[] = $sectionId;
        }

        if ($subjectId) {
            $whereClause .= " AND a.subject_id = ?";
            $params[] = $subjectId;
        }

        // Total assignments
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_assignments
            FROM assignments a
            $whereClause
        ");
        $stmt->execute($params);
        $totalAssignments = $stmt->fetchColumn();

        // Completed assignments (all students graded)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as completed_assignments
            FROM assignments a
            LEFT JOIN students st ON st.section_id = a.section_id
            LEFT JOIN grades g ON g.student_id = st.id AND g.section_id = a.section_id AND g.subject_id = a.subject_id AND g.description = a.title
            $whereClause
            GROUP BY a.id
            HAVING COUNT(st.id) = COUNT(g.id) AND COUNT(st.id) > 0
        ");
        $stmt->execute($params);
        $completedAssignments = count($stmt->fetchAll());

        // Active assignments (not completed, not overdue)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as active_assignments
            FROM assignments a
            $whereClause
            AND (a.due_date IS NULL OR a.due_date >= CURDATE())
        ");
        $stmt->execute($params);
        $activeAssignments = $stmt->fetchColumn();

        // Overdue assignments
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as overdue_assignments
            FROM assignments a
            $whereClause
            AND a.due_date < CURDATE()
        ");
        $stmt->execute($params);
        $overdueAssignments = $stmt->fetchColumn();

        return [
            'total_assignments' => (int)$totalAssignments,
            'completed_assignments' => (int)$completedAssignments,
            'active_assignments' => (int)$activeAssignments,
            'overdue_assignments' => (int)$overdueAssignments
        ];
    }

    private function getAssignmentsWithDetails($pdo, $teacherId, $sectionId = null, $subjectId = null, $status = null)
    {
        $whereClause = "WHERE a.teacher_id = ? AND a.is_active = 1";
        $params = [$teacherId];

        if ($sectionId) {
            $whereClause .= " AND a.section_id = ?";
            $params[] = $sectionId;
        }

        if ($subjectId) {
            $whereClause .= " AND a.subject_id = ?";
            $params[] = $subjectId;
        }

        // Add status filtering
        if ($status) {
            switch ($status) {
                case 'active':
                    $whereClause .= " AND (a.due_date IS NULL OR a.due_date >= CURDATE())";
                    break;
                case 'completed':
                    // This would need a more complex query to check if all students are graded
                    break;
                case 'overdue':
                    $whereClause .= " AND a.due_date < CURDATE()";
                    break;
            }
        }

        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.title,
                a.description,
                a.assignment_type,
                a.max_score,
                a.due_date,
                a.created_at,
                sec.name as section_name,
                sub.name as subject_name,
                COUNT(DISTINCT st.id) as total_students,
                COUNT(DISTINCT g.id) as graded_students,
                CASE 
                    WHEN a.due_date IS NULL THEN 'active'
                    WHEN a.due_date >= CURDATE() THEN 'active'
                    ELSE 'overdue'
                END as status,
                CASE 
                    WHEN COUNT(DISTINCT st.id) = 0 THEN 0
                    ELSE ROUND((COUNT(DISTINCT g.id) / COUNT(DISTINCT st.id)) * 100, 1)
                END as completion_percentage
            FROM assignments a
            JOIN sections sec ON a.section_id = sec.id
            JOIN subjects sub ON a.subject_id = sub.id
            LEFT JOIN students st ON st.section_id = a.section_id
            LEFT JOIN grades g ON g.student_id = st.id AND g.section_id = a.section_id AND g.subject_id = a.subject_id AND g.description = a.title
            $whereClause
            GROUP BY a.id, a.title, a.description, a.assignment_type, a.max_score, a.due_date, a.created_at, sec.name, sub.name
            ORDER BY a.due_date DESC, a.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function attendance(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        $sectionId = isset($_GET['section']) ? (int)$_GET['section'] : null;
        $subjectId = isset($_GET['subject']) ? (int)$_GET['subject'] : null;

        try {
            $pdo = $this->getDatabaseConnection();
            $teacher = $this->resolveTeacher($pdo, (int)$user['id']);

            if (!$teacher) {
                \Helpers\ErrorHandler::notFound('Teacher profile not found.');
                return;
            }

            $sections = $this->getTeacherSections($pdo, (int)$teacher['id'], (int)$user['id']);

            if (empty($sections)) {
                $this->view->render('teacher/attendance', [
                    'title' => 'Attendance Management',
                    'user' => $user,
                    'activeNav' => 'attendance',
                    'sections' => [],
                    'filters' => [
                        'date' => $date,
                        'section_id' => null,
                        'subject_id' => null,
                    ],
                    'students' => [],
                    'summary' => [
                        'present' => 0,
                        'absent' => 0,
                        'late' => 0,
                        'excused' => 0,
                    ],
                ], 'layouts/dashboard');
                return;
            }

            if (!$sectionId || !$subjectId) {
                $sectionId = $sections[0]['section_id'];
                $subjectId = $sections[0]['subject_id'];
            }

            $attendanceSnapshot = $this->getAttendanceSnapshot($pdo, (int)$teacher['id'], $sectionId, $subjectId, $date);

            $summary = [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
            ];

            foreach ($attendanceSnapshot as $row) {
                $status = $row['attendance_status'] ?? 'absent';
                if (isset($summary[$status])) {
                    $summary[$status]++;
                }
            }

            $this->view->render('teacher/attendance', [
                'title' => 'Attendance Management',
                'user' => $user,
                'activeNav' => 'attendance',
                'sections' => $sections,
                'filters' => [
                    'date' => $date,
                    'section_id' => $sectionId,
                    'subject_id' => $subjectId,
                ],
                'students' => $attendanceSnapshot,
                'summary' => $summary,
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load attendance page: ' . $e->getMessage());
        }
    }

    public function getAttendanceList(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        $sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        if (!$sectionId || !$subjectId) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing parameters']); return; }

        try {
            $pdo = $this->getDatabaseConnection();
            $stmt = $pdo->prepare("\n                SELECT \n                    s.id AS student_id, u.name AS student_name, s.lrn, s.grade_level,\n                    a.status AS attendance_status\n                FROM students s\n                JOIN users u ON s.user_id = u.id\n                LEFT JOIN attendance a ON a.student_id = s.id AND a.section_id = ? AND a.subject_id = ? AND a.attendance_date = ?\n                WHERE s.section_id = ?\n                ORDER BY u.name\n            ");
            $stmt->execute([$sectionId, $subjectId, $date, $sectionId]);
            $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $students]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function saveAttendance(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        foreach (['student_id','section_id','subject_id','date','status'] as $k) {
            if (!isset($input[$k]) || $input[$k] === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing field: '.$k]); return; }
        }

        $studentId = (int)$input['student_id'];
        $sectionId = (int)$input['section_id'];
        $subjectId = (int)$input['subject_id'];
        $date = $input['date'];
        $status = $input['status'];
        if (!in_array($status, ['present','absent','late','excused'], true)) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid status']); return; }

        try {
            $pdo = $this->getDatabaseConnection();
            $teacher = $this->resolveTeacher($pdo, (int)$user['id']);
            
            if (!$teacher) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Teacher profile not found']);
                return;
            }
            
            $teacherId = (int)$teacher['id'];
            
            $stmt = $pdo->prepare('SELECT id FROM attendance WHERE student_id=? AND section_id=? AND subject_id=? AND attendance_date=?');
            $stmt->execute([$studentId, $sectionId, $subjectId, $date]);
            $existingId = $stmt->fetchColumn();
            if ($existingId) {
                $stmt = $pdo->prepare('UPDATE attendance SET status=?, updated_at=NOW() WHERE id=?');
                $stmt->execute([$status, $existingId]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO attendance (student_id, teacher_id, section_id, subject_id, attendance_date, status) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$studentId, $teacherId, $sectionId, $subjectId, $date, $status]);
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function getAttendanceHistory(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
        $sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

        if (!$studentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing student_id']);
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();
            
            $sql = "
                SELECT 
                    a.attendance_date,
                    a.status,
                    s.name AS subject_name,
                    sec.name AS section_name
                FROM attendance a
                LEFT JOIN subjects s ON a.subject_id = s.id
                LEFT JOIN sections sec ON a.section_id = sec.id
                WHERE a.student_id = ?
            ";
            
            $params = [$studentId];
            
            if ($sectionId) {
                $sql .= " AND a.section_id = ?";
                $params[] = $sectionId;
            }
            
            if ($subjectId) {
                $sql .= " AND a.subject_id = ?";
                $params[] = $subjectId;
            }
            
            $sql .= " ORDER BY a.attendance_date DESC LIMIT 100";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $history = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function studentProgress(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }
        $this->view->render('teacher/student-progress', [
            'title' => 'Student Progress',
            'user' => $user,
            'activeNav' => 'student-progress',
        ], 'layouts/dashboard');
    }

    public function communication(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }
        $this->view->render('teacher/communication', [
            'title' => 'Communication',
            'user' => $user,
            'activeNav' => 'communication',
        ], 'layouts/dashboard');
    }

    public function materials(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }
        $this->view->render('teacher/materials', [
            'title' => 'Teaching Materials',
            'user' => $user,
            'activeNav' => 'materials',
        ], 'layouts/dashboard');
    }

    // API Methods for AJAX requests
    public function getSectionDetails(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array($user['role'] ?? '', ['teacher', 'adviser'], true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $sectionId = $_GET['section_id'] ?? null;
        $subjectId = $_GET['subject_id'] ?? null;

        if (!$sectionId || !$subjectId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();
            $teacherId = $user['id'];

            // Verify teacher has access to this section
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM teacher_sections WHERE teacher_id = ? AND section_id = ? AND subject_id = ?");
            $stmt->execute([$teacherId, $sectionId, $subjectId]);
            if ($stmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied to this section']);
                return;
            }

            // Get detailed section information
            $stmt = $pdo->prepare("
                SELECT 
                    s.section_id,
                    s.class_name,
                    s.subject,
                    s.grade_level,
                    s.section,
                    s.room,
                    sub.name as subject_name,
                    COUNT(DISTINCT st.id) as student_count
                FROM sections s
                JOIN subjects sub ON sub.id = ?
                LEFT JOIN students st ON st.section_id = s.section_id
                WHERE s.section_id = ?
                GROUP BY s.section_id, s.class_name, s.subject, s.grade_level, s.section, s.room, sub.name
            ");
            $stmt->execute([$subjectId, $sectionId]);
            $section = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$section) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Section not found']);
                return;
            }

            // Get students with detailed information
            $stmt = $pdo->prepare("
                SELECT 
                    s.id as student_id,
                    u.name as student_name,
                    u.email as student_email,
                    s.lrn,
                    s.grade_level,
                    COALESCE(AVG(g.grade_value), 0) as avg_grade,
                    COUNT(g.id) as grades_count,
                    COUNT(CASE WHEN att.status = 'present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN att.status = 'late' THEN 1 END) as late_count,
                    COUNT(CASE WHEN att.status = 'absent' THEN 1 END) as absent_count,
                    COUNT(att.id) as total_attendance,
                    MAX(att.attendance_date) as last_attendance_date
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN grades g ON g.student_id = s.id AND g.section_id = ? AND g.subject_id = ?
                LEFT JOIN attendance att ON att.student_id = s.id AND att.section_id = ? AND att.subject_id = ?
                WHERE s.section_id = ?
                GROUP BY s.id, u.name, u.email, s.lrn, s.grade_level
                ORDER BY u.name
            ");
            $stmt->execute([$sectionId, $subjectId, $sectionId, $subjectId, $sectionId]);
            $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $section['students'] = $students;

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $section]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function logActivity(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array($user['role'] ?? '', ['teacher', 'adviser'], true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['activity_type'], $input['description'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();
            $teacherId = $user['id'];

            $stmt = $pdo->prepare("
                INSERT INTO teacher_activities (teacher_id, activity_type, description, target_type, target_id, metadata) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $teacherId,
                $input['activity_type'],
                $input['description'],
                $input['target_type'] ?? null,
                $input['target_id'] ?? null,
                isset($input['metadata']) ? json_encode($input['metadata']) : null
            ]);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Activity logged successfully']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Display list of students handled by the teacher
     */
    public function students(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            // Get teacher ID
            $stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ?');
            $stmt->execute([$user['id']]);
            $teacher = $stmt->fetch();
            
            if (!$teacher) {
                throw new \Exception('Teacher profile not found.');
            }

            // Get all students handled by this teacher through their classes
            $stmt = $pdo->prepare('
                SELECT DISTINCT 
                    s.id as student_id,
                    s.lrn,
                    s.first_name,
                    s.last_name,
                    s.middle_name,
                    CONCAT(
                        COALESCE(s.first_name, ""), 
                        CASE WHEN s.middle_name IS NOT NULL AND s.middle_name != "" THEN CONCAT(" ", s.middle_name) ELSE "" END,
                        CASE WHEN s.last_name IS NOT NULL AND s.last_name != "" THEN CONCAT(" ", s.last_name) ELSE "" END
                    ) as full_name,
                    s.grade_level,
                    sec.name as section_name,
                    sec.id as section_id,
                    GROUP_CONCAT(DISTINCT sub.name ORDER BY sub.name SEPARATOR ", ") as subjects,
                    GROUP_CONCAT(DISTINCT c.schedule ORDER BY c.schedule SEPARATOR ", ") as schedules,
                    COUNT(DISTINCT c.id) as total_classes
                FROM students s
                JOIN student_classes sc ON s.id = sc.student_id
                JOIN classes c ON sc.class_id = c.id
                JOIN sections sec ON c.section_id = sec.id
                JOIN subjects sub ON c.subject_id = sub.id
                WHERE c.teacher_id = ? AND c.is_active = 1 AND sc.status = "enrolled"
                GROUP BY s.id, s.lrn, s.first_name, s.last_name, s.middle_name, s.grade_level, sec.name, sec.id
                ORDER BY s.grade_level, s.last_name, s.first_name
            ');
            $stmt->execute([$teacher['id']]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sectionOptions = [];
            foreach ($students as $row) {
                $sectionId = (int)($row['section_id'] ?? 0);
                if ($sectionId && !isset($sectionOptions[$sectionId])) {
                    $sectionOptions[$sectionId] = $row['section_name'] ?? '';
                }
            }

            $sectionFilter = isset($_GET['section']) ? (int)$_GET['section'] : null;
            $search = trim((string)($_GET['q'] ?? ''));

            if ($sectionFilter) {
                $students = array_values(array_filter($students, static function ($student) use ($sectionFilter) {
                    return (int)($student['section_id'] ?? 0) === $sectionFilter;
                }));
            }

            if ($search !== '') {
                $students = array_values(array_filter($students, static function ($student) use ($search) {
                    $haystack = strtolower(($student['full_name'] ?? '') . ' ' . ($student['lrn'] ?? ''));
                    return strpos($haystack, strtolower($search)) !== false;
                }));
            }

            // Get statistics
            $totalStudents = count($students);
            $gradeLevels = array_unique(array_column($students, 'grade_level'));
            $filteredSections = array_unique(array_column($students, 'section_name'));

            $this->view->render('teacher/students', [
                'title' => 'My Students',
                'user' => $user,
                'activeNav' => 'students',
                'students' => $students,
                'statistics' => [
                    'total_students' => $totalStudents,
                    'grade_levels' => count($gradeLevels),
                    'sections' => count($filteredSections),
                    'grade_levels_list' => $gradeLevels,
                    'sections_list' => $sectionOptions,
                    'active_section_filter' => $sectionFilter,
                    'search_term' => $search,
                ]
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            $this->view->render('teacher/students', [
                'title' => 'My Students',
                'user' => $user,
                'activeNav' => 'students',
                'students' => [],
                'statistics' => [
                    'total_students' => 0,
                    'grade_levels' => 0,
                    'sections' => 0,
                    'grade_levels_list' => [],
                    'sections_list' => []
                ],
                'error' => $e->getMessage()
            ], 'layouts/dashboard');
        }
    }

    /**
     * Display advised sections for the teacher
     */
    public function advisedSections(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            // Get teacher ID
            $stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ?');
            $stmt->execute([$user['id']]);
            $teacher = $stmt->fetch();
            
            if (!$teacher) {
                throw new \Exception('Teacher profile not found.');
            }

            // Get all sections and subjects handled by this teacher
            $stmt = $pdo->prepare('
                SELECT 
                    c.id as class_id,
                    sec.id as section_id,
                    sec.name as section_name,
                    sec.grade_level,
                    sec.room as section_room,
                    sub.id as subject_id,
                    sub.name as subject_name,
                    c.schedule,
                    c.room as class_room,
                    COUNT(DISTINCT sc.student_id) as enrolled_students,
                    sec.max_students
                FROM classes c
                JOIN sections sec ON c.section_id = sec.id
                JOIN subjects sub ON c.subject_id = sub.id
                LEFT JOIN student_classes sc ON c.id = sc.class_id AND sc.status = "enrolled"
                WHERE c.teacher_id = ? AND c.is_active = 1 AND sec.is_active = 1
                GROUP BY c.id, sec.id, sec.name, sec.grade_level, sec.room, sub.id, sub.name, c.schedule, c.room, sec.max_students
                ORDER BY sec.grade_level, sec.name, sub.name
            ');
            $stmt->execute([$teacher['id']]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get statistics
            $totalClasses = count($sections);
            $totalStudents = array_sum(array_column($sections, 'enrolled_students'));
            $gradeLevels = array_unique(array_column($sections, 'grade_level'));

            $this->view->render('teacher/advised-sections', [
                'title' => 'My Advised Sections',
                'user' => $user,
                'activeNav' => 'advisory',
                'sections' => $sections,
                'statistics' => [
                    'total_classes' => $totalClasses,
                    'total_students' => $totalStudents,
                    'grade_levels' => count($gradeLevels),
                    'grade_levels_list' => $gradeLevels
                ]
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            $this->view->render('teacher/advised-sections', [
                'title' => 'My Advised Sections',
                'user' => $user,
                'activeNav' => 'advisory',
                'sections' => [],
                'statistics' => [
                    'total_classes' => 0,
                    'total_students' => 0,
                    'grade_levels' => 0,
                    'grade_levels_list' => []
                ],
                'error' => $e->getMessage()
            ], 'layouts/dashboard');
        }
    }

    /**
     * Display add students to section page
     */
    public function addStudentsToSection(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            // Get teacher ID
            $stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ?');
            $stmt->execute([$user['id']]);
            $teacher = $stmt->fetch();
            
            if (!$teacher) {
                throw new \Exception('Teacher profile not found.');
            }

            // Get sections handled by this teacher
            $stmt = $pdo->prepare('
                SELECT DISTINCT 
                    sec.id,
                    sec.name,
                    sec.grade_level,
                    sec.room,
                    COUNT(DISTINCT c.id) as total_classes,
                    COUNT(DISTINCT sc.student_id) as enrolled_students
                FROM sections sec
                JOIN classes c ON sec.id = c.section_id
                LEFT JOIN student_classes sc ON c.id = sc.class_id AND sc.status = "enrolled"
                WHERE c.teacher_id = ? AND c.is_active = 1 AND sec.is_active = 1
                GROUP BY sec.id, sec.name, sec.grade_level, sec.room
                ORDER BY sec.grade_level, sec.name
            ');
            $stmt->execute([$teacher['id']]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view->render('teacher/add-students', [
                'title' => 'Add Students to Sections',
                'user' => $user,
                'activeNav' => 'students',
                'sections' => $sections
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            $this->view->render('teacher/add-students', [
                'title' => 'Add Students to Sections',
                'user' => $user,
                'activeNav' => 'students',
                'sections' => [],
                'error' => $e->getMessage()
            ], 'layouts/dashboard');
        }
    }

    /**
     * Search student by LRN (AJAX)
     */
    public function searchStudentByLRN(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $lrn = $_GET['lrn'] ?? '';
        if (empty($lrn)) {
            http_response_code(400);
            echo json_encode(['error' => 'LRN is required']);
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            // Search for student by LRN
            $stmt = $pdo->prepare('
                SELECT 
                    s.id,
                    s.lrn,
                    s.first_name,
                    s.last_name,
                    s.middle_name,
                    CONCAT(
                        COALESCE(s.first_name, ""), 
                        CASE WHEN s.middle_name IS NOT NULL AND s.middle_name != "" THEN CONCAT(" ", s.middle_name) ELSE "" END,
                        CASE WHEN s.last_name IS NOT NULL AND s.last_name != "" THEN CONCAT(" ", s.last_name) ELSE "" END
                    ) as full_name,
                    s.grade_level,
                    s.contact_number,
                    s.address,
                    sec.name as current_section,
                    sec.id as current_section_id,
                    u.email,
                    u.status as user_status
                FROM students s
                LEFT JOIN sections sec ON s.section_id = sec.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.lrn = ? AND u.status = "active"
            ');
            $stmt->execute([$lrn]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                http_response_code(404);
                echo json_encode(['error' => 'Student not found']);
                return;
            }

            // Get teacher's sections for this student's grade level
            $stmt = $pdo->prepare('
                SELECT DISTINCT 
                    sec.id,
                    sec.name,
                    sec.grade_level,
                    sec.room,
                    COUNT(DISTINCT c.id) as total_classes
                FROM sections sec
                JOIN classes c ON sec.id = c.section_id
                JOIN teachers t ON c.teacher_id = t.id
                WHERE t.user_id = ? AND sec.grade_level = ? AND c.is_active = 1 AND sec.is_active = 1
                GROUP BY sec.id, sec.name, sec.grade_level, sec.room
                ORDER BY sec.name
            ');
            $stmt->execute([$user['id'], $student['grade_level']]);
            $availableSections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'student' => $student,
                'available_sections' => $availableSections
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Add student to section (AJAX)
     */
    public function addStudentToSection(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'teacher') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $sectionId = (int)($_POST['section_id'] ?? 0);

        if (!$studentId || !$sectionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Student ID and Section ID are required']);
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $pdo->beginTransaction();

            // Verify teacher has access to this section
            $stmt = $pdo->prepare('
                SELECT c.id FROM classes c
                JOIN teachers t ON c.teacher_id = t.id
                WHERE t.user_id = ? AND c.section_id = ? AND c.is_active = 1
                LIMIT 1
            ');
            $stmt->execute([$user['id'], $sectionId]);
            $class = $stmt->fetch();

            if (!$class) {
                throw new \Exception('You do not have access to this section.');
            }

            // Get student info
            $stmt = $pdo->prepare('SELECT lrn, first_name, last_name FROM students WHERE id = ?');
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();

            if (!$student) {
                throw new \Exception('Student not found.');
            }

            // Get section info
            $stmt = $pdo->prepare('SELECT name FROM sections WHERE id = ?');
            $stmt->execute([$sectionId]);
            $section = $stmt->fetch();

            if (!$section) {
                throw new \Exception('Section not found.');
            }

            // Get all classes for this section that the teacher handles
            $stmt = $pdo->prepare('
                SELECT c.id FROM classes c
                JOIN teachers t ON c.teacher_id = t.id
                WHERE t.user_id = ? AND c.section_id = ? AND c.is_active = 1
            ');
            $stmt->execute([$user['id'], $sectionId]);
            $classes = $stmt->fetchAll();

            // Enroll student in all classes
            foreach ($classes as $class) {
                $stmt = $pdo->prepare('
                    INSERT INTO student_classes (student_id, class_id, status) 
                    VALUES (?, ?, "enrolled")
                    ON DUPLICATE KEY UPDATE status = "enrolled"
                ');
                $stmt->execute([$studentId, $class['id']]);
            }

            // Update student's section if not already set
            $stmt = $pdo->prepare('UPDATE students SET section_id = ? WHERE id = ? AND section_id IS NULL');
            $stmt->execute([$sectionId, $studentId]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => "Student {$student['first_name']} {$student['last_name']} (LRN: {$student['lrn']}) has been successfully added to section {$section['name']}.",
                'student' => [
                    'id' => $studentId,
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'lrn' => $student['lrn']
                ],
                'section' => [
                    'id' => $sectionId,
                    'name' => $section['name']
                ]
            ]);

        } catch (\Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function getAttendanceSnapshot(\PDO $pdo, int $teacherId, int $sectionId, int $subjectId, string $date): array
    {
        try {
            // Get the class ID for this teacher, section, and subject
            $stmt = $pdo->prepare('SELECT id FROM classes WHERE teacher_id = ? AND section_id = ? AND subject_id = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$teacherId, $sectionId, $subjectId]);
            $class = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$class) {
                return [];
            }
            
            $classId = (int)$class['id'];

            // Get students enrolled in this class through student_classes table
            // Fallback to students directly assigned to the section if no enrollments found
            $stmt = $pdo->prepare('
                SELECT DISTINCT
                    s.id AS student_id,
                    u.name AS student_name,
                    s.lrn,
                    s.grade_level,
                    att.status AS attendance_status
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN student_classes sc ON s.id = sc.student_id AND sc.class_id = ? AND sc.status = "enrolled"
                LEFT JOIN attendance att ON att.student_id = s.id
                    AND att.section_id = ?
                    AND att.subject_id = ?
                    AND att.attendance_date = ?
                WHERE sc.id IS NOT NULL
                ORDER BY u.name
            ');
            $stmt->execute([$classId, $sectionId, $subjectId, $date]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // If no students found through enrollment, fallback to section-based lookup
            if (empty($rows)) {
                $stmt = $pdo->prepare('
                    SELECT DISTINCT
                        s.id AS student_id,
                        u.name AS student_name,
                        s.lrn,
                        s.grade_level,
                        att.status AS attendance_status
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN attendance att ON att.student_id = s.id
                        AND att.section_id = ?
                        AND att.subject_id = ?
                        AND att.attendance_date = ?
                    WHERE s.section_id = ?
                    ORDER BY u.name
                ');
                $stmt->execute([$sectionId, $subjectId, $date, $sectionId]);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            }

            return array_map(static function (array $row): array {
                return [
                    'student_id' => (int)$row['student_id'],
                    'student_name' => $row['student_name'],
                    'lrn' => $row['lrn'],
                    'grade_level' => (int)($row['grade_level'] ?? 0),
                    'attendance_status' => $row['attendance_status'] ?? 'absent',
                ];
            }, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * View a specific student's profile and performance
     */
    public function viewStudent(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array($user['role'] ?? '', ['teacher', 'adviser'], true)) {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        $studentId = (int)($_GET['id'] ?? 0);
        if ($studentId <= 0) {
            \Helpers\ErrorHandler::badRequest('Invalid student ID.');
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();
            $teacher = $this->resolveTeacher($pdo, (int)$user['id']);

            if (!$teacher) {
                \Helpers\ErrorHandler::notFound('Teacher profile not found.');
                return;
            }

            // Get student details
            $stmt = $pdo->prepare("
                SELECT 
                    s.*,
                    u.email,
                    u.name as full_name,
                    sec.name as section_name,
                    sec.grade_level as section_grade_level,
                    sec.room as section_room,
                    adv.id as adviser_user_id,
                    adv_user.name as adviser_name
                FROM students s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN sections sec ON s.section_id = sec.id
                LEFT JOIN teachers adv ON sec.adviser_id = adv.user_id
                LEFT JOIN users adv_user ON adv.user_id = adv_user.id
                WHERE s.id = ?
            ");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student not found.');
                return;
            }

            // Get student's enrolled classes
            $stmt = $pdo->prepare("
                SELECT 
                    c.id as class_id,
                    c.schedule,
                    c.room,
                    c.school_year,
                    c.semester,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    t_user.name as teacher_name,
                    sec.name as section_name,
                    sc.status as enrollment_status,
                    sc.enrollment_date
                FROM student_classes sc
                JOIN classes c ON sc.class_id = c.id
                JOIN subjects subj ON c.subject_id = subj.id
                JOIN sections sec ON c.section_id = sec.id
                LEFT JOIN teachers t ON c.teacher_id = t.user_id
                LEFT JOIN users t_user ON t.user_id = t_user.id
                WHERE sc.student_id = ? AND sc.status = 'enrolled'
                ORDER BY subj.name
            ");
            $stmt->execute([$studentId]);
            $enrolledClasses = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get student's grades with computed averages
            $stmt = $pdo->prepare("
                SELECT 
                    subj.id as subject_id,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    g.quarter,
                    g.academic_year,
                    -- Written Work Average
                    AVG(CASE WHEN g.grade_type = 'ww' THEN (g.grade_value / g.max_score * 100) END) as ww_avg,
                    -- Performance Task Average
                    AVG(CASE WHEN g.grade_type = 'pt' THEN (g.grade_value / g.max_score * 100) END) as pt_avg,
                    -- Quarterly Exam Average
                    AVG(CASE WHEN g.grade_type = 'qe' THEN (g.grade_value / g.max_score * 100) END) as qe_avg,
                    -- Count of grades per type
                    COUNT(CASE WHEN g.grade_type = 'ww' THEN 1 END) as ww_count,
                    COUNT(CASE WHEN g.grade_type = 'pt' THEN 1 END) as pt_count,
                    COUNT(CASE WHEN g.grade_type = 'qe' THEN 1 END) as qe_count
                FROM grades g
                JOIN subjects subj ON g.subject_id = subj.id
                WHERE g.student_id = ?
                GROUP BY subj.id, subj.name, subj.code, g.quarter, g.academic_year
                ORDER BY g.academic_year DESC, g.quarter DESC, subj.name
            ");
            $stmt->execute([$studentId]);
            $grades = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Compute final grades per subject/quarter
            foreach ($grades as &$grade) {
                $ww = $grade['ww_avg'] ?? 0;
                $pt = $grade['pt_avg'] ?? 0;
                $qe = $grade['qe_avg'] ?? 0;
                
                // Default weights: WW=20%, PT=50%, QE=20%, Attendance=10%
                // (We'll assume attendance is 100% if not tracking)
                $attendance = 100;
                
                $finalGrade = ($ww * 0.20) + ($pt * 0.50) + ($qe * 0.20) + ($attendance * 0.10);
                $grade['final_grade'] = round($finalGrade, 2);
                $grade['status'] = $finalGrade >= 75 ? 'Passed' : 'Failed';
            }

            // Get attendance summary
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
                FROM attendance
                WHERE student_id = ?
            ");
            $stmt->execute([$studentId]);
            $attendance = $stmt->fetch(\PDO::FETCH_ASSOC);

            $attendancePercentage = 0;
            if ($attendance && $attendance['total_days'] > 0) {
                $attendancePercentage = round(($attendance['present_days'] / $attendance['total_days']) * 100, 2);
            }

            $this->view->render('teacher/view-student', [
                'title' => 'View Student - ' . ($student['first_name'] . ' ' . $student['last_name']),
                'user' => $user,
                'activeNav' => 'students',
                'showBack' => true,
                'student' => $student,
                'enrolledClasses' => $enrolledClasses,
                'grades' => $grades,
                'attendance' => $attendance,
                'attendancePercentage' => $attendancePercentage,
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load student profile: ' . $e->getMessage());
        }
    }

    /**
     * View class roster with all students enrolled in a specific class
     */
    public function viewClass(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array($user['role'] ?? '', ['teacher', 'adviser'], true)) {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        $classId = (int)($_GET['id'] ?? 0);
        if ($classId <= 0) {
            \Helpers\ErrorHandler::badRequest('Invalid class ID.');
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();
            $teacher = $this->resolveTeacher($pdo, (int)$user['id']);

            if (!$teacher) {
                \Helpers\ErrorHandler::notFound('Teacher profile not found.');
                return;
            }

            // Get class details
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    subj.description as subject_description,
                    sec.name as section_name,
                    sec.grade_level,
                    sec.room as section_room,
                    sec.max_students,
                    t_user.name as teacher_name
                FROM classes c
                JOIN subjects subj ON c.subject_id = subj.id
                JOIN sections sec ON c.section_id = sec.id
                LEFT JOIN teachers t ON c.teacher_id = t.user_id
                LEFT JOIN users t_user ON t.user_id = t_user.id
                WHERE c.id = ? AND c.teacher_id = ?
            ");
            $stmt->execute([$classId, $teacher['user_id']]);
            $class = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$class) {
                \Helpers\ErrorHandler::forbidden('Class not found or you do not have permission to view it.');
                return;
            }

            // Get students enrolled in this class
            $stmt = $pdo->prepare("
                SELECT 
                    s.id as student_id,
                    s.lrn,
                    s.first_name,
                    s.last_name,
                    s.middle_name,
                    s.gender,
                    s.contact_number,
                    u.email,
                    sc.status as enrollment_status,
                    sc.enrollment_date,
                    -- Calculate average grade for this student in this class
                    (
                        SELECT 
                            ROUND(
                                (AVG(CASE WHEN g.grade_type = 'ww' THEN (g.grade_value / g.max_score * 100) END) * 0.20) +
                                (AVG(CASE WHEN g.grade_type = 'pt' THEN (g.grade_value / g.max_score * 100) END) * 0.50) +
                                (AVG(CASE WHEN g.grade_type = 'qe' THEN (g.grade_value / g.max_score * 100) END) * 0.20) +
                                (100 * 0.10),
                                2
                            )
                        FROM grades g
                        WHERE g.student_id = s.id 
                            AND g.subject_id = ?
                            AND g.academic_year = ?
                    ) as current_grade,
                    -- Attendance for this class/subject
                    (
                        SELECT COUNT(*)
                        FROM attendance a
                        WHERE a.student_id = s.id 
                            AND a.subject_id = ?
                            AND a.status = 'present'
                    ) as attendance_present,
                    (
                        SELECT COUNT(*)
                        FROM attendance a
                        WHERE a.student_id = s.id 
                            AND a.subject_id = ?
                    ) as attendance_total
                FROM student_classes sc
                JOIN students s ON sc.student_id = s.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE sc.class_id = ? AND sc.status = 'enrolled'
                ORDER BY s.last_name, s.first_name
            ");
            $stmt->execute([
                $class['subject_id'],
                $class['school_year'],
                $class['subject_id'],
                $class['subject_id'],
                $classId
            ]);
            $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // If no students through enrollment, fallback to section-based lookup
            if (empty($students)) {
                $stmt = $pdo->prepare("
                    SELECT 
                        s.id as student_id,
                        s.lrn,
                        s.first_name,
                        s.last_name,
                        s.middle_name,
                        s.gender,
                        s.contact_number,
                        u.email,
                        'enrolled' as enrollment_status,
                        s.created_at as enrollment_date,
                        NULL as current_grade,
                        0 as attendance_present,
                        0 as attendance_total
                    FROM students s
                    LEFT JOIN users u ON s.user_id = u.id
                    WHERE s.section_id = ?
                    ORDER BY s.last_name, s.first_name
                ");
                $stmt->execute([$class['section_id']]);
                $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            // Calculate attendance percentages
            foreach ($students as &$student) {
                $student['attendance_percentage'] = 0;
                if ($student['attendance_total'] > 0) {
                    $student['attendance_percentage'] = round(
                        ($student['attendance_present'] / $student['attendance_total']) * 100,
                        2
                    );
                }
                $student['full_name'] = trim($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']);
            }

            // Get class schedule details
            $stmt = $pdo->prepare("
                SELECT 
                    day_of_week,
                    start_time,
                    end_time
                FROM teacher_schedules
                WHERE class_id = ?
                ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
            ");
            $stmt->execute([$classId]);
            $schedules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view->render('teacher/view-class', [
                'title' => 'Class Roster - ' . $class['subject_name'],
                'user' => $user,
                'activeNav' => 'classes',
                'showBack' => true,
                'class' => $class,
                'students' => $students,
                'schedules' => $schedules,
                'studentCount' => count($students),
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load class roster: ' . $e->getMessage());
        }
    }

    /**
     * Display teaching loads overview
     */
    public function teachingLoads(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array($user['role'] ?? '', ['teacher', 'adviser'], true)) {
            \Helpers\ErrorHandler::forbidden('You need teacher privileges to access this page.');
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();
            $teacher = $this->resolveTeacher($pdo, (int)$user['id']);

            if (!$teacher) {
                \Helpers\ErrorHandler::notFound('Teacher profile not found.');
                return;
            }

            // Get all teaching loads (classes)
            $stmt = $pdo->prepare("
                SELECT 
                    c.id as class_id,
                    c.schedule,
                    c.room,
                    c.school_year,
                    c.semester,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    sec.name as section_name,
                    sec.grade_level,
                    sec.id as section_id,
                    -- Count enrolled students
                    (
                        SELECT COUNT(*)
                        FROM student_classes sc
                        WHERE sc.class_id = c.id AND sc.status = 'enrolled'
                    ) as enrolled_count,
                    -- Fallback to section students if no enrollments
                    (
                        SELECT COUNT(*)
                        FROM students s
                        WHERE s.section_id = sec.id
                    ) as section_student_count
                FROM classes c
                JOIN subjects subj ON c.subject_id = subj.id
                JOIN sections sec ON c.section_id = sec.id
                WHERE c.teacher_id = ? AND c.is_active = 1
                ORDER BY sec.grade_level, sec.name, subj.name
            ");
            $stmt->execute([$teacher['user_id']]);
            $classes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get advisory section (if teacher is an adviser)
            $stmt = $pdo->prepare("
                SELECT 
                    sec.id,
                    sec.name,
                    sec.grade_level,
                    sec.room,
                    sec.max_students,
                    sec.school_year,
                    (
                        SELECT COUNT(*)
                        FROM students s
                        WHERE s.section_id = sec.id
                    ) as student_count
                FROM sections sec
                WHERE sec.adviser_id = ?
            ");
            $stmt->execute([$teacher['user_id']]);
            $advisorySection = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Get schedule summary
            $stmt = $pdo->prepare("
                SELECT 
                    ts.day_of_week,
                    ts.start_time,
                    ts.end_time,
                    c.id as class_id,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    sec.name as section_name,
                    c.room
                FROM teacher_schedules ts
                JOIN classes c ON ts.class_id = c.id
                JOIN subjects subj ON c.subject_id = subj.id
                JOIN sections sec ON c.section_id = sec.id
                WHERE ts.teacher_id = ?
                ORDER BY 
                    FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                    ts.start_time
            ");
            $stmt->execute([$teacher['user_id']]);
            $schedules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate statistics
            $totalClasses = count($classes);
            $totalStudents = array_sum(array_map(function($class) {
                return max($class['enrolled_count'], $class['section_student_count']);
            }, $classes));
            $uniqueSubjects = count(array_unique(array_column($classes, 'subject_name')));
            $uniqueSections = count(array_unique(array_column($classes, 'section_id')));

            $this->view->render('teacher/teaching-loads', [
                'title' => 'My Teaching Loads',
                'user' => $user,
                'activeNav' => 'teaching-loads',
                'showBack' => false,
                'classes' => $classes,
                'advisorySection' => $advisorySection,
                'schedules' => $schedules,
                'stats' => [
                    'total_classes' => $totalClasses,
                    'total_students' => $totalStudents,
                    'unique_subjects' => $uniqueSubjects,
                    'unique_sections' => $uniqueSections,
                ],
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load teaching loads: ' . $e->getMessage());
        }
    }
}


