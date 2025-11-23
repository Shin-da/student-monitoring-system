<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;

class ParentController extends Controller
{
    /**
     * Get the linked student ID for the current parent user
     * Returns null if not linked or not a parent
     */
    private function getLinkedStudentId($pdo, int $parentUserId): ?int
    {
        $stmt = $pdo->prepare('SELECT linked_student_user_id FROM users WHERE id = ? AND role = "parent"');
        $stmt->execute([$parentUserId]);
        $parentData = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$parentData || empty($parentData['linked_student_user_id'])) {
            return null;
        }
        
        // Get student ID from student record
        $stmt = $pdo->prepare('SELECT id FROM students WHERE user_id = ? LIMIT 1');
        $stmt->execute([(int)$parentData['linked_student_user_id']]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $student ? (int)$student['id'] : null;
    }

    /**
     * Validate that the parent is linked to the given student
     */
    private function validateParentStudentRelationship($pdo, int $parentUserId, int $studentId): bool
    {
        $stmt = $pdo->prepare("
            SELECT s.id 
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE s.id = ? 
                AND u.id = (
                    SELECT linked_student_user_id 
                    FROM users 
                    WHERE id = ? AND role = 'parent'
                )
        ");
        $stmt->execute([$studentId, $parentUserId]);
        return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function dashboard(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'parent') {
            \Helpers\ErrorHandler::forbidden('You need parent privileges to access this page.');
            return;
        }
        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            $parentStmt = $pdo->prepare('SELECT linked_student_user_id, parent_relationship FROM users WHERE id = ?');
            $parentStmt->execute([(int)$user['id']]);
            $parentData = $parentStmt->fetch(\PDO::FETCH_ASSOC);

            $childInfo = null;
            $recentActivities = [];
            $upcomingEvents = [];

            if ($parentData && !empty($parentData['linked_student_user_id'])) {
                $studentStmt = $pdo->prepare("
                    SELECT
                        st.id,
                        st.lrn,
                        st.grade_level,
                        st.section_id,
                        u.name AS student_name,
                        sec.name AS section_name
                    FROM students st
                    JOIN users u ON st.user_id = u.id
                    LEFT JOIN sections sec ON st.section_id = sec.id
                    WHERE st.user_id = ?
                    LIMIT 1
                ");
                $studentStmt->execute([(int)$parentData['linked_student_user_id']]);
                $childInfo = $studentStmt->fetch(\PDO::FETCH_ASSOC) ?: null;

                if ($childInfo) {
                    try {
                        $activityStmt = $pdo->prepare("
                            SELECT description, created_at
                            FROM performance_alerts
                            WHERE student_id = ?
                            ORDER BY created_at DESC
                            LIMIT 5
                        ");
                        $activityStmt->execute([(int)$childInfo['id']]);
                        $recentActivities = $activityStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    } catch (\Throwable $inner) {
                        $recentActivities = [];
                    }

                    try {
                        $eventStmt = $pdo->prepare("
                            SELECT title, due_date
                            FROM assignments
                            WHERE section_id = ? AND subject_id IS NOT NULL
                            ORDER BY due_date ASC
                            LIMIT 5
                        ");
                        $eventStmt->execute([(int)($childInfo['section_id'] ?? 0)]);
                        $upcomingEvents = $eventStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    } catch (\Throwable $inner) {
                        $upcomingEvents = [];
                    }
                }
            }

            $this->view->render('parent/dashboard', [
                'title' => 'Parent Dashboard',
                'user' => $user,
                'activeNav' => 'dashboard',
                'showBack' => false,
                'child_info' => $childInfo,
                'parent_relationship' => $parentData['parent_relationship'] ?? null,
                'recent_activities' => $recentActivities,
                'upcoming_events' => $upcomingEvents,
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load parent dashboard: ' . $e->getMessage());
        }
    }

    /**
     * View child's grades
     */
    public function viewChildGrades(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'parent') {
            \Helpers\ErrorHandler::forbidden('You need parent privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            $studentId = $this->getLinkedStudentId($pdo, (int)$user['id']);
            if (!$studentId) {
                \Helpers\ErrorHandler::notFound('No student linked to your account. Please contact the administrator.');
                return;
            }

            $stmt = $pdo->prepare('SELECT id, section_id, school_year FROM students WHERE id = ? LIMIT 1');
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();

            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
            }

            $hasSection = !empty($student['section_id']);
            $quarter = (int)($_GET['quarter'] ?? 1);
            $academicYear = $_GET['academic_year'] ?? $student['school_year'] ?? null;

            // If no academic year specified, get the most recent one with grades
            if (!$academicYear) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT academic_year 
                    FROM grades 
                    WHERE student_id = ? 
                    ORDER BY academic_year DESC 
                    LIMIT 1
                ");
                $stmt->execute([$studentId]);
                $yearResult = $stmt->fetch();
                $academicYear = $yearResult['academic_year'] ?? $student['school_year'] ?? date('Y') . '-' . (date('Y') + 1);
            }

            // Fetch grades grouped by subject with computed averages
            $stmt = $pdo->prepare("
                SELECT 
                    subj.id as subject_id,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    g.quarter,
                    g.academic_year,
                    AVG(CASE WHEN g.grade_type = 'ww' THEN (g.grade_value / g.max_score * 100) END) as ww_avg,
                    AVG(CASE WHEN g.grade_type = 'pt' THEN (g.grade_value / g.max_score * 100) END) as pt_avg,
                    AVG(CASE WHEN g.grade_type = 'qe' THEN (g.grade_value / g.max_score * 100) END) as qe_avg,
                    COUNT(CASE WHEN g.grade_type = 'ww' THEN 1 END) as ww_count,
                    COUNT(CASE WHEN g.grade_type = 'pt' THEN 1 END) as pt_count,
                    COUNT(CASE WHEN g.grade_type = 'qe' THEN 1 END) as qe_count
                FROM grades g
                JOIN subjects subj ON g.subject_id = subj.id
                WHERE g.student_id = ? AND g.quarter = ? AND g.academic_year = ?
                GROUP BY subj.id, subj.name, subj.code, g.quarter, g.academic_year
                ORDER BY subj.name
            ");
            $stmt->execute([$studentId, $quarter, $academicYear]);
            $grades = $stmt->fetchAll();

            // Compute final grades
            foreach ($grades as &$grade) {
                $ww = (float)($grade['ww_avg'] ?? 0);
                $pt = (float)($grade['pt_avg'] ?? 0);
                $qe = (float)($grade['qe_avg'] ?? 0);
                $attendance = 100; // Default attendance
                
                $finalGrade = ($ww * 0.20) + ($pt * 0.50) + ($qe * 0.20) + ($attendance * 0.10);
                $grade['final_grade'] = round($finalGrade, 2);
                $grade['status'] = $finalGrade >= 75 ? 'Passed' : 'Failed';
            }

            // Get all available academic years for this student
            $stmt = $pdo->prepare("
                SELECT DISTINCT academic_year 
                FROM grades 
                WHERE student_id = ? 
                ORDER BY academic_year DESC
            ");
            $stmt->execute([$studentId]);
            $academicYears = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // If no grades found, use student's school_year
            if (empty($academicYears) && !empty($student['school_year'])) {
                $academicYears = [$student['school_year']];
            }

            // Calculate statistics
            $stats = [
                'overall_average' => 0,
                'passing_subjects' => 0,
                'needs_improvement' => 0,
                'total_subjects' => count($grades),
            ];

            if (!empty($grades)) {
                $totalFinalGrade = 0;
                $passingCount = 0;
                $needsImprovementCount = 0;
                
                foreach ($grades as $grade) {
                    $finalGrade = (float)($grade['final_grade'] ?? 0);
                    $totalFinalGrade += $finalGrade;
                    
                    if ($finalGrade >= 75) {
                        $passingCount++;
                    } elseif ($finalGrade > 0 && $finalGrade < 75) {
                        $needsImprovementCount++;
                    }
                }
                
                $stats['overall_average'] = count($grades) > 0 ? round($totalFinalGrade / count($grades), 2) : 0;
                $stats['passing_subjects'] = $passingCount;
                $stats['needs_improvement'] = $needsImprovementCount;
            }

            $this->view->render('parent/grades', [
                'title' => 'Child\'s Grades',
                'user' => $user,
                'activeNav' => 'grades',
                'hasSection' => $hasSection,
                'grades' => $grades,
                'quarterlyGrades' => $grades,
                'quarter' => $quarter,
                'currentQuarter' => $quarter,
                'currentAcademicYear' => $academicYear,
                'academicYears' => $academicYears,
                'student_id' => $studentId,
                'stats' => $stats,
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load grades: ' . $e->getMessage());
        }
    }

    /**
     * View child's attendance
     */
    public function viewChildAttendance(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'parent') {
            \Helpers\ErrorHandler::forbidden('You need parent privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            $studentId = $this->getLinkedStudentId($pdo, (int)$user['id']);
            if (!$studentId) {
                \Helpers\ErrorHandler::notFound('No student linked to your account. Please contact the administrator.');
                return;
            }

            $stmt = $pdo->prepare('SELECT id, section_id FROM students WHERE id = ? LIMIT 1');
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();

            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
            }

            $hasSection = !empty($student['section_id']);

            // Get filters
            $quarter = (int)($_GET['quarter'] ?? 1);
            $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
            $dateTo = $_GET['date_to'] ?? date('Y-m-t');

            // Fetch attendance records
            $stmt = $pdo->prepare("
                SELECT 
                    a.attendance_date,
                    a.status,
                    a.remarks,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    sec.name as section_name
                FROM attendance a
                LEFT JOIN subjects subj ON a.subject_id = subj.id
                LEFT JOIN sections sec ON a.section_id = sec.id
                WHERE a.student_id = ?
                    AND a.attendance_date BETWEEN ? AND ?
                ORDER BY a.attendance_date DESC, subj.name
            ");
            $stmt->execute([$studentId, $dateFrom, $dateTo]);
            $attendanceRecords = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get summary statistics
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
                FROM attendance
                WHERE student_id = ?
                    AND attendance_date BETWEEN ? AND ?
            ");
            $stmt->execute([$studentId, $dateFrom, $dateTo]);
            $summary = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Calculate attendance rate
            $attendanceRate = 0;
            if ($summary && $summary['total_days'] > 0) {
                $attendanceRate = round(((int)$summary['present_days'] / (int)$summary['total_days']) * 100, 1);
            }

            $attendanceStats = [
                'total_days' => (int)($summary['total_days'] ?? 0),
                'present' => (int)($summary['present_days'] ?? 0),
                'late' => (int)($summary['late_days'] ?? 0),
                'excused' => 0,
                'absent' => (int)($summary['absent_days'] ?? 0),
                'attendance_rate' => $attendanceRate,
            ];

            $this->view->render('parent/attendance', [
                'title' => 'Child\'s Attendance',
                'user' => $user,
                'activeNav' => 'attendance',
                'hasSection' => $hasSection,
                'attendanceRecords' => $attendanceRecords,
                'attendanceStats' => $attendanceStats,
                'summary' => $summary,
                'filters' => [
                    'quarter' => $quarter,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load attendance page: ' . $e->getMessage());
        }
    }

    /**
     * View child's profile
     */
    public function viewChildProfile(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'parent') {
            \Helpers\ErrorHandler::forbidden('You need parent privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            $studentId = $this->getLinkedStudentId($pdo, (int)$user['id']);
            if (!$studentId) {
                \Helpers\ErrorHandler::notFound('No student linked to your account. Please contact the administrator.');
                return;
            }

            // Get complete student profile
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
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
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
                $attendancePercentage = round(((int)$attendance['present_days'] / (int)$attendance['total_days']) * 100, 2);
            }

            // Calculate academic stats
            $academicStats = [
                'overall_average' => 0,
                'passing_subjects' => 0,
                'total_subjects' => 0,
                'improvement' => 0,
            ];

            if (!empty($studentId)) {
                // Calculate overall average from grades
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(DISTINCT subject_id) as total_subjects,
                        AVG(final_grade) as overall_average
                    FROM (
                        SELECT 
                            subject_id,
                            (
                                (AVG(CASE WHEN grade_type = 'ww' THEN (grade_value / max_score * 100) END) * 0.20) +
                                (AVG(CASE WHEN grade_type = 'pt' THEN (grade_value / max_score * 100) END) * 0.50) +
                                (AVG(CASE WHEN grade_type = 'qe' THEN (grade_value / max_score * 100) END) * 0.20) +
                                (100 * 0.10)
                            ) as final_grade
                        FROM grades
                        WHERE student_id = ?
                        GROUP BY subject_id
                    ) as subject_grades
                ");
                $stmt->execute([$studentId]);
                $stats = $stmt->fetch();
                
                if ($stats) {
                    $academicStats['overall_average'] = round((float)($stats['overall_average'] ?? 0), 2);
                    $academicStats['total_subjects'] = (int)($stats['total_subjects'] ?? 0);
                }

                // Count passing subjects
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT subject_id) as passing_count
                    FROM (
                        SELECT 
                            subject_id,
                            (
                                (AVG(CASE WHEN grade_type = 'ww' THEN (grade_value / max_score * 100) END) * 0.20) +
                                (AVG(CASE WHEN grade_type = 'pt' THEN (grade_value / max_score * 100) END) * 0.50) +
                                (AVG(CASE WHEN grade_type = 'qe' THEN (grade_value / max_score * 100) END) * 0.20) +
                                (100 * 0.10)
                            ) as final_grade
                        FROM grades
                        WHERE student_id = ?
                        GROUP BY subject_id
                        HAVING final_grade >= 75
                    ) as passing_grades
                ");
                $stmt->execute([$studentId]);
                $passing = $stmt->fetch();
                $academicStats['passing_subjects'] = (int)($passing['passing_count'] ?? 0);
            }

            // Get subjects for the student's grade level
            $subjects = [];
            $gradeLevel = $student['grade_level'] ?? $student['section_grade_level'] ?? null;
            if ($gradeLevel && !empty($student['section_id'])) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT
                        subj.id,
                        subj.name,
                        subj.code,
                        subj.description
                    FROM classes c
                    JOIN subjects subj ON c.subject_id = subj.id
                    WHERE c.section_id = ? AND c.is_active = 1
                    ORDER BY subj.name
                ");
                $stmt->execute([$student['section_id']]);
                $subjects = $stmt->fetchAll();
            }

            $this->view->render('parent/profile', [
                'title' => 'Child\'s Profile',
                'user' => $user,
                'activeNav' => 'profile',
                'showBack' => false,
                'student' => $student,
                'attendance' => $attendance,
                'attendancePercentage' => $attendancePercentage,
                'academic_stats' => $academicStats,
                'subjects' => $subjects,
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load profile: ' . $e->getMessage());
        }
    }

    /**
     * View child's schedule
     */
    public function viewChildSchedule(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'parent') {
            \Helpers\ErrorHandler::forbidden('You need parent privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            $studentId = $this->getLinkedStudentId($pdo, (int)$user['id']);
            if (!$studentId) {
                \Helpers\ErrorHandler::notFound('No student linked to your account. Please contact the administrator.');
                return;
            }

            // Get student ID and section
            $stmt = $pdo->prepare('SELECT id, section_id FROM students WHERE id = ? LIMIT 1');
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();

            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
            }

            // Get weekly schedule from enrolled classes
            $stmt = $pdo->prepare("
                SELECT 
                    ts.day_of_week,
                    ts.start_time,
                    ts.end_time,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    sec.name as section_name,
                    c.room,
                    t_user.name as teacher_name
                FROM student_classes sc
                JOIN classes c ON sc.class_id = c.id
                JOIN teacher_schedules ts ON c.id = ts.class_id
                JOIN subjects subj ON c.subject_id = subj.id
                JOIN sections sec ON c.section_id = sec.id
                LEFT JOIN teachers t ON c.teacher_id = t.user_id
                LEFT JOIN users t_user ON t.user_id = t_user.id
                WHERE sc.student_id = ? AND sc.status = 'enrolled'
                ORDER BY 
                    FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                    ts.start_time
            ");
            $stmt->execute([$studentId]);
            $schedules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // If no enrollments, fallback to section-based schedule
            if (empty($schedules) && !empty($student['section_id'])) {
                $stmt = $pdo->prepare("
                    SELECT 
                        ts.day_of_week,
                        ts.start_time,
                        ts.end_time,
                        subj.name as subject_name,
                        subj.code as subject_code,
                        sec.name as section_name,
                        c.room,
                        t_user.name as teacher_name
                    FROM classes c
                    JOIN teacher_schedules ts ON c.id = ts.class_id
                    JOIN subjects subj ON c.subject_id = subj.id
                    JOIN sections sec ON c.section_id = sec.id
                    LEFT JOIN teachers t ON c.teacher_id = t.user_id
                    LEFT JOIN users t_user ON t.user_id = t_user.id
                    WHERE c.section_id = ? AND c.is_active = 1
                    ORDER BY 
                        FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                        ts.start_time
                ");
                $stmt->execute([$student['section_id']]);
                $schedules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            // Group by day of week
            $schedulesByDay = [];
            foreach ($schedules as $schedule) {
                $day = $schedule['day_of_week'];
                if (!isset($schedulesByDay[$day])) {
                    $schedulesByDay[$day] = [];
                }
                $schedulesByDay[$day][] = $schedule;
            }

            $this->view->render('parent/schedule', [
                'title' => 'Child\'s Schedule',
                'user' => $user,
                'activeNav' => 'schedule',
                'schedules' => $schedules,
                'schedulesByDay' => $schedulesByDay,
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load schedule: ' . $e->getMessage());
        }
    }
}


