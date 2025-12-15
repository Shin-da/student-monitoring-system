<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;

class StudentController extends Controller
{
    public function dashboard(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Profile via view (centralized)
            $stmt = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
            $profile = $stmt->fetch();

            // Fallback for unassigned students or if view isn't updated yet: LEFT JOIN sections
           if (!$profile) {
                $fallback = $pdo->prepare("
                    SELECT 
                        s.id AS student_id,
                        s.user_id,
                        u.email,
                        u.name AS full_name,
                        u.status AS user_status,
                        s.lrn,
                        s.first_name,
                        s.last_name,
                        s.middle_name,
                        CONCAT(s.first_name, ' ', IFNULL(s.middle_name, ''), ' ', s.last_name) AS full_name_display,
                        s.grade_level,
                        s.section_id,
                        sec.name AS section_name,
                        sec.room AS section_room,
                        s.school_year,
                        s.enrollment_status,
                        s.status AS student_status,
                        s.created_at,
                        s.updated_at
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN sections sec ON s.section_id = sec.id
                    WHERE u.role = 'student' AND s.user_id = ?
                    LIMIT 1
                ");
                $fallback->execute([$user['id']]);
                $profile = $fallback->fetch();
            }

            if (!$profile) {
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
            }

            // Check if student has a section assigned
            $hasSection = !empty($profile['section_id']);

            $studentInfo = [
                'class_name' => $profile['section_name'] ?? 'Unassigned',
                'lrn' => $profile['lrn'],
                'grade_level' => $profile['grade_level'],
                'school_year' => $profile['school_year'],
                'section_id' => $profile['section_id'] ?? null,
            ];

            // If no section assigned, show empty state
            if (!$hasSection) {
                $this->view->render('student/dashboard', [
                    'title' => 'Student Dashboard',
                    'user' => $user,
                    'activeNav' => 'dashboard',
                    'showBack' => false,
                    'student_info' => $studentInfo,
                    'has_section' => false,
                    'academic_stats' => [],
                    'recent_grades' => [],
                    'upcoming_assignments' => [],
                    'classes' => [],
                ], 'layouts/dashboard');
                return;
            }

            // Student has section - load real data
            $studentId = (int)($profile['student_id'] ?? $profile['id'] ?? 0);
            
            // Load real academic stats from grades
            $academicStats = [
                'overall_average' => 0,
                'passing_subjects' => 0,
                'total_subjects' => 0,
                'attendance_rate' => 0,
            ];

            // Calculate overall average from grades
            // First get final grade per subject, then average those
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

            // Calculate attendance rate
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
                FROM attendance
                WHERE student_id = ?
            ");
            $stmt->execute([$studentId]);
            $attendance = $stmt->fetch();
            if ($attendance && $attendance['total_days'] > 0) {
                $academicStats['attendance_rate'] = round(((int)$attendance['present_days'] / (int)$attendance['total_days']) * 100, 1);
            }

            // Get recent grades (last 5)
            $stmt = $pdo->prepare("
                SELECT 
                    g.*,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    (g.grade_value / g.max_score * 100) as percentage
                FROM grades g
                JOIN subjects subj ON g.subject_id = subj.id
                WHERE g.student_id = ?
                ORDER BY g.graded_at DESC, g.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$studentId]);
            $recentGrades = $stmt->fetchAll();

            // Get enrolled classes (with fallback to section-based classes)
            $stmt = $pdo->prepare("
                SELECT 
                    c.id as class_id,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    sec.name as section_name,
                    t_user.name as teacher_name,
                    c.schedule,
                    c.room
                FROM student_classes sc
                JOIN classes c ON sc.class_id = c.id
                JOIN subjects subj ON c.subject_id = subj.id
                JOIN sections sec ON c.section_id = sec.id
                LEFT JOIN teachers t ON c.teacher_id = t.user_id
                LEFT JOIN users t_user ON t.user_id = t_user.id
                WHERE sc.student_id = ? AND sc.status = 'enrolled'
                ORDER BY subj.name
                LIMIT 4
            ");
            $stmt->execute([$studentId]);
            $classes = $stmt->fetchAll();

            // If no enrollments, fallback to section-based classes
            if (empty($classes) && $hasSection && !empty($profile['section_id'])) {
                $stmt = $pdo->prepare("
                    SELECT 
                        c.id as class_id,
                        subj.name as subject_name,
                        subj.code as subject_code,
                        sec.name as section_name,
                        t_user.name as teacher_name,
                        c.schedule,
                        c.room
                    FROM classes c
                    JOIN subjects subj ON c.subject_id = subj.id
                    JOIN sections sec ON c.section_id = sec.id
                    LEFT JOIN teachers t ON c.teacher_id = t.user_id
                    LEFT JOIN users t_user ON t.user_id = t_user.id
                    WHERE c.section_id = ? AND c.is_active = 1
                    ORDER BY subj.name
                    LIMIT 4
                ");
                $stmt->execute([$profile['section_id']]);
                $classes = $stmt->fetchAll();
            }

            // Get AI performance analysis
            $aiAnalysis = null;
            $studentAlerts = [];
            try {
                $analyzer = new \Services\PerformanceAnalyzer($pdo);
                $academicYear = (new \Models\GradeModel())->getCurrentAcademicYear();
                $quarter = $this->getCurrentQuarter();
                
                $aiAnalysis = $analyzer->analyzeStudent(
                    $studentId,
                    (int)$profile['section_id'],
                    $quarter,
                    $academicYear
                );
                
                // Get alerts for this student
                $alertStmt = $pdo->prepare("
                    SELECT 
                        pa.id,
                        pa.alert_type,
                        pa.title,
                        pa.description,
                        pa.severity,
                        pa.status,
                        pa.created_at,
                        sub.name as subject_name
                    FROM performance_alerts pa
                    LEFT JOIN subjects sub ON pa.subject_id = sub.id
                    WHERE pa.student_id = ? AND pa.status = 'active'
                    ORDER BY pa.created_at DESC
                    LIMIT 5
                ");
                $alertStmt->execute([$studentId]);
                $studentAlerts = $alertStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Exception $e) {
                error_log("Student dashboard AI analysis error: " . $e->getMessage());
            }
            
            // Get chart data for grade trends
            $gradeTrendData = $this->getGradeTrendData($pdo, $studentId, $academicYear);
            
            // Get chart data for attendance trends
            $attendanceTrendData = $this->getAttendanceTrendData($pdo, $studentId, (int)$profile['section_id'], $academicYear);
            
            // Get chart data for subject performance
            $subjectPerformanceData = $this->getSubjectPerformanceData($pdo, $studentId, $academicYear, $quarter);

            $this->view->render('student/dashboard', [
                'title' => 'Student Dashboard',
                'user' => $user,
                'activeNav' => 'dashboard',
                'showBack' => false,
                'student_info' => $studentInfo,
                'has_section' => true,
                'academic_stats' => $academicStats,
                'recent_grades' => $recentGrades,
                'upcoming_assignments' => [], // TODO: Implement assignments
                'classes' => $classes,
                'ai_analysis' => $aiAnalysis,
                'alerts' => $studentAlerts,
                'chart_data' => [
                    'grade_trends' => $gradeTrendData,
                    'attendance_trends' => $attendanceTrendData,
                    'subject_performance' => $subjectPerformanceData,
                ],
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load dashboard: ' . $e->getMessage());
        }
    }

    public function grades(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            $stmt = $pdo->prepare('SELECT id, section_id, school_year FROM students WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
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
                $stmt->execute([$student['id']]);
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
            $stmt->execute([$student['id'], $quarter, $academicYear]);
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
            $stmt->execute([$student['id']]);
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

            $this->view->render('student/grades', [
                'title' => 'My Grades',
                'user' => $user,
                'activeNav' => 'grades',
                'hasSection' => $hasSection,
                'grades' => $grades,
                'quarterlyGrades' => $grades, // View expects this name
                'quarter' => $quarter,
                'currentQuarter' => $quarter,
                'currentAcademicYear' => $academicYear,
                'academicYears' => $academicYears,
                'student_id' => $student['id'],
                'stats' => $stats,
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load grades: ' . $e->getMessage());
        }
    }

    public function assignments(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Get student ID and section
            $stmt = $pdo->prepare('SELECT id, section_id FROM students WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
            $student = $stmt->fetch();

            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
            }

            $hasSection = !empty($student['section_id']);

            // TODO: Fetch actual assignments when assignments table is implemented
            $assignments = [];

            $this->view->render('student/assignments', [
                'title' => 'My Assignments',
                'user' => $user,
                'activeNav' => 'assignments',
                'hasSection' => $hasSection,
                'assignments' => $assignments,
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load assignments: ' . $e->getMessage());
        }
    }

    public function profile(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

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
                WHERE s.user_id = ?
            ");
            $stmt->execute([$user['id']]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Get ALL linked parent accounts (not just one)
            $parentStmt = $pdo->prepare("
                SELECT 
                    id as parent_user_id,
                    name as parent_account_name,
                    email as parent_account_email,
                    parent_relationship as parent_account_relationship
                FROM users 
                WHERE linked_student_user_id = ? 
                AND role = 'parent'
                ORDER BY 
                    FIELD(parent_relationship, 'mother', 'father', 'guardian', 'grandparent', 'sibling', 'other'),
                    name
            ");
            $parentStmt->execute([$user['id']]);
            $linkedParents = $parentStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Add parent data to student array for backward compatibility
            if (!empty($linkedParents)) {
                $firstParent = $linkedParents[0];
                $student['parent_user_id'] = $firstParent['parent_user_id'];
                $student['parent_account_name'] = $firstParent['parent_account_name'];
                $student['parent_account_email'] = $firstParent['parent_account_email'];
                $student['parent_account_relationship'] = $firstParent['parent_account_relationship'];
            }

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
            $stmt->execute([$student['id']]);
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
                'improvement' => 0, // Can be calculated later if needed
            ];

            if (!empty($student['id'])) {
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
                $stmt->execute([$student['id']]);
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
                $stmt->execute([$student['id']]);
                $passing = $stmt->fetch();
                $academicStats['passing_subjects'] = (int)($passing['passing_count'] ?? 0);
            }

            // Get subjects for the student's grade level
            $subjects = [];
            $gradeLevel = $student['grade_level'] ?? $student['section_grade_level'] ?? null;
            if ($gradeLevel) {
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
                // Try to get subjects from student's section first
                if (!empty($student['section_id'])) {
                    $stmt->execute([$student['section_id']]);
                    $subjects = $stmt->fetchAll();
                }
                
                // If no subjects from section, try to get by grade level (if there's a grade_level field in subjects)
                // For now, we'll just use section-based subjects
            }

            $this->view->render('student/profile', [
                'title' => 'My Profile',
                'user' => $user,
                'activeNav' => 'profile',
                'showBack' => false,
                'student' => $student,
                'linkedParents' => $linkedParents ?? [],
                'attendance' => $attendance,
                'attendancePercentage' => $attendancePercentage,
                'academic_stats' => $academicStats,
                'subjects' => $subjects,
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load profile: ' . $e->getMessage());
        }
    }

    public function attendance(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Get student ID and section
            $stmt = $pdo->prepare('SELECT id, section_id FROM students WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
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
            $subjectId = !empty($_GET['subject']) ? (int)$_GET['subject'] : null;

            // Fetch subjects for the filter dropdown
            $subjects = [];
            if ($hasSection && !empty($student['section_id'])) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT
                        subj.id,
                        subj.name,
                        subj.code
                    FROM classes c
                    JOIN subjects subj ON c.subject_id = subj.id
                    WHERE c.section_id = ? AND c.is_active = 1
                    ORDER BY subj.name
                ");
                $stmt->execute([$student['section_id']]);
                $subjects = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            // Build WHERE conditions for queries
            $whereConditions = "a.student_id = ? AND a.attendance_date BETWEEN ? AND ?";
            $params = [$student['id'], $dateFrom, $dateTo];
            
            if ($subjectId) {
                $whereConditions .= " AND a.subject_id = ?";
                $params[] = $subjectId;
            }

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
                WHERE {$whereConditions}
                ORDER BY a.attendance_date DESC, subj.name
            ");
            $stmt->execute($params);
            $attendanceRecords = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get summary statistics - count by status (must match the same filters)
            $statsWhereConditions = "student_id = ? AND attendance_date BETWEEN ? AND ?";
            $statsParams = [$student['id'], $dateFrom, $dateTo];
            
            if ($subjectId) {
                $statsWhereConditions .= " AND subject_id = ?";
                $statsParams[] = $subjectId;
            }

            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_days,
                    COALESCE(SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END), 0) as present_days,
                    COALESCE(SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END), 0) as absent_days,
                    COALESCE(SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END), 0) as late_days,
                    COALESCE(SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END), 0) as excused_days
                FROM attendance
                WHERE {$statsWhereConditions}
            ");
            $stmt->execute($statsParams);
            $summary = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Initialize with defaults if query fails
            if (!$summary) {
                $summary = [
                    'total_days' => 0,
                    'present_days' => 0,
                    'absent_days' => 0,
                    'late_days' => 0,
                    'excused_days' => 0
                ];
            }

            // Calculate attendance rate
            $attendanceRate = 0;
            $totalDays = (int)($summary['total_days'] ?? 0);
            if ($totalDays > 0) {
                $presentDays = (int)($summary['present_days'] ?? 0);
                $attendanceRate = round(($presentDays / $totalDays) * 100, 1);
            }

            $attendanceStats = [
                'total_days' => $totalDays,
                'present' => (int)($summary['present_days'] ?? 0),
                'late' => (int)($summary['late_days'] ?? 0),
                'excused' => (int)($summary['excused_days'] ?? 0),
                'absent' => (int)($summary['absent_days'] ?? 0),
                'attendance_rate' => $attendanceRate,
            ];

            $this->view->render('student/attendance', [
                'title' => 'My Attendance',
                'user' => $user,
                'activeNav' => 'attendance',
                'hasSection' => $hasSection,
                'attendanceRecords' => $attendanceRecords,
                'attendanceStats' => $attendanceStats,
                'summary' => $summary,
                'subjects' => $subjects,
                'filters' => [
                    'quarter' => $quarter,
                    'subject_id' => $subjectId,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load attendance page: ' . $e->getMessage());
        }
    }

    public function alerts(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }
        
        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            
            // Get student profile
            $stmt = $pdo->prepare("
                SELECT s.id, s.section_id, s.user_id
                FROM students s
                WHERE s.user_id = ?
                LIMIT 1
            ");
            $stmt->execute([(int)$user['id']]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
            }
            
            $studentId = (int)$student['id'];
            $sectionId = (int)($student['section_id'] ?? 0);
            
            // Get AI performance analysis
            $aiAnalysis = null;
            try {
                $analyzer = new \Services\PerformanceAnalyzer($pdo);
                $academicYear = (new \Models\GradeModel())->getCurrentAcademicYear();
                $quarter = $this->getCurrentQuarter();
                
                $aiAnalysis = $analyzer->analyzeStudent(
                    $studentId,
                    $sectionId,
                    $quarter,
                    $academicYear
                );
            } catch (\Exception $e) {
                error_log("Student alerts AI analysis error: " . $e->getMessage());
            }
            
            // Get all alerts for this student
            $stmt = $pdo->prepare("
                SELECT 
                    pa.id,
                    pa.alert_type,
                    pa.title,
                    pa.description,
                    pa.severity,
                    pa.status,
                    pa.created_at,
                    pa.subject_id,
                    sub.name as subject_name
                FROM performance_alerts pa
                LEFT JOIN subjects sub ON pa.subject_id = sub.id
                WHERE pa.student_id = ? AND pa.status = 'active'
                ORDER BY 
                    CASE pa.severity
                        WHEN 'high' THEN 1
                        WHEN 'medium' THEN 2
                        WHEN 'low' THEN 3
                    END,
                    pa.created_at DESC
            ");
            $stmt->execute([$studentId]);
            $alerts = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            
            $this->view->render('student/alerts', [
                'title' => 'My Alerts & Performance Insights',
                'user' => $user,
                'activeNav' => 'alerts',
                'showBack' => true,
                'alerts' => $alerts,
                'ai_analysis' => $aiAnalysis,
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load alerts: ' . $e->getMessage());
        }
    }

    public function schedule(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Get student ID
            $stmt = $pdo->prepare('SELECT id, section_id FROM students WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
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
            $stmt->execute([$student['id']]);
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

            $this->view->render('student/schedule', [
                'title' => 'My Schedule',
                'user' => $user,
                'activeNav' => 'schedule',
                'schedules' => $schedules,
                'schedulesByDay' => $schedulesByDay,
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load schedule: ' . $e->getMessage());
        }
    }

    /**
     * Display student's enrolled classes
     */
    public function myClasses(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Get student ID
            $stmt = $pdo->prepare('SELECT id, section_id, grade_level FROM students WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
            $student = $stmt->fetch();

            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
            }

            // Get enrolled classes with teacher and grade information
            $stmt = $pdo->prepare("
                SELECT 
                    c.id as class_id,
                    c.schedule,
                    c.room,
                    c.school_year,
                    c.semester,
                    subj.id as subject_id,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    subj.description as subject_description,
                    sec.name as section_name,
                    t_user.name as teacher_name,
                    t_user.email as teacher_email,
                    sc.status as enrollment_status,
                    sc.enrollment_date,
                    -- Calculate current grade
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
                        WHERE g.student_id = ? 
                            AND g.subject_id = subj.id
                            AND g.academic_year COLLATE utf8mb4_unicode_ci = c.school_year COLLATE utf8mb4_unicode_ci
                    ) as current_grade,
                    -- Count assignments/grades
                    (
                        SELECT COUNT(*)
                        FROM grades g
                        WHERE g.student_id = ? 
                            AND g.subject_id = subj.id
                    ) as grade_count
                FROM student_classes sc
                JOIN classes c ON sc.class_id = c.id
                JOIN subjects subj ON c.subject_id = subj.id
                JOIN sections sec ON c.section_id = sec.id
                LEFT JOIN teachers t ON c.teacher_id = t.user_id
                LEFT JOIN users t_user ON t.user_id = t_user.id
                WHERE sc.student_id = ? AND sc.status = 'enrolled'
                ORDER BY subj.name
            ");
            $stmt->execute([$student['id'], $student['id'], $student['id']]);
            $enrolledClasses = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // If no enrollments, fallback to section-based classes
            if (empty($enrolledClasses) && !empty($student['section_id'])) {
                $stmt = $pdo->prepare("
                    SELECT 
                        c.id as class_id,
                        c.schedule,
                        c.room,
                        c.school_year,
                        c.semester,
                        subj.id as subject_id,
                        subj.name as subject_name,
                        subj.code as subject_code,
                        subj.description as subject_description,
                        sec.name as section_name,
                        t_user.name as teacher_name,
                        t_user.email as teacher_email,
                        'enrolled' as enrollment_status,
                        c.created_at as enrollment_date,
                        NULL as current_grade,
                        0 as grade_count
                    FROM classes c
                    JOIN subjects subj ON c.subject_id = subj.id
                    JOIN sections sec ON c.section_id = sec.id
                    LEFT JOIN teachers t ON c.teacher_id = t.user_id
                    LEFT JOIN users t_user ON t.user_id = t_user.id
                    WHERE c.section_id = ? AND c.is_active = 1
                    ORDER BY subj.name
                ");
                $stmt->execute([$student['section_id']]);
                $enrolledClasses = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            $this->view->render('student/classes', [
                'title' => 'My Classes',
                'user' => $user,
                'activeNav' => 'classes',
                'enrolledClasses' => $enrolledClasses,
                'studentGradeLevel' => $student['grade_level'],
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load classes: ' . $e->getMessage());
        }
    }

    /**
     * View detailed subject/class information
     */
    public function viewSubject(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }

        $classId = (int)($_GET['class_id'] ?? 0);
        $subjectId = (int)($_GET['subject_id'] ?? 0);

        if ($classId <= 0 && $subjectId <= 0) {
            \Helpers\ErrorHandler::badRequest('Invalid class or subject ID.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Get student ID
            $stmt = $pdo->prepare('SELECT id, section_id FROM students WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
            $student = $stmt->fetch();

            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
            }

            // Get class/subject details
            $stmt = $pdo->prepare("
                SELECT 
                    c.id as class_id,
                    c.schedule,
                    c.room,
                    c.school_year,
                    c.semester,
                    subj.id as subject_id,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    subj.description as subject_description,
                    subj.ww_percent,
                    subj.pt_percent,
                    subj.qe_percent,
                    subj.attendance_percent,
                    sec.name as section_name,
                    sec.grade_level,
                    t.user_id as teacher_id,
                    t_user.name as teacher_name,
                    t_user.email as teacher_email,
                    sc.status as enrollment_status,
                    sc.enrollment_date
                FROM classes c
                JOIN subjects subj ON c.subject_id = subj.id
                JOIN sections sec ON c.section_id = sec.id
                LEFT JOIN student_classes sc ON sc.class_id = c.id AND sc.student_id = ?
                LEFT JOIN teachers t ON c.teacher_id = t.user_id
                LEFT JOIN users t_user ON t.user_id = t_user.id
                WHERE c.id = ? OR (c.section_id = ? AND subj.id = ?)
                LIMIT 1
            ");
            $stmt->execute([$student['id'], $classId, $student['section_id'], $subjectId]);
            $classInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$classInfo) {
                \Helpers\ErrorHandler::notFound('Class or subject not found.');
                return;
            }

            // Get all grades for this subject
            $stmt = $pdo->prepare("
                SELECT 
                    g.*,
                    (g.grade_value / g.max_score * 100) as percentage,
                    t_user.name as graded_by
                FROM grades g
                LEFT JOIN users t_user ON g.teacher_id = t_user.id
                WHERE g.student_id = ? AND g.subject_id = ?
                ORDER BY g.quarter, g.grade_type, g.graded_at DESC
            ");
            $stmt->execute([$student['id'], $classInfo['subject_id']]);
            $allGrades = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Group grades by quarter and type
            $gradesByQuarter = [];
            foreach ($allGrades as $grade) {
                $quarter = $grade['quarter'];
                $type = strtoupper($grade['grade_type']);
                
                if (!isset($gradesByQuarter[$quarter])) {
                    $gradesByQuarter[$quarter] = [
                        'WW' => [],
                        'PT' => [],
                        'QE' => [],
                    ];
                }
                
                $gradesByQuarter[$quarter][$type][] = $grade;
            }

            // Calculate averages per quarter
            $quarterSummaries = [];
            foreach ($gradesByQuarter as $quarter => $types) {
                $ww_avg = 0;
                $pt_avg = 0;
                $qe_avg = 0;
                
                if (!empty($types['WW'])) {
                    $ww_avg = array_sum(array_column($types['WW'], 'percentage')) / count($types['WW']);
                }
                if (!empty($types['PT'])) {
                    $pt_avg = array_sum(array_column($types['PT'], 'percentage')) / count($types['PT']);
                }
                if (!empty($types['QE'])) {
                    $qe_avg = array_sum(array_column($types['QE'], 'percentage')) / count($types['QE']);
                }
                
                $attendance = 100; // Default
                $finalGrade = ($ww_avg * ($classInfo['ww_percent'] / 100)) +
                             ($pt_avg * ($classInfo['pt_percent'] / 100)) +
                             ($qe_avg * ($classInfo['qe_percent'] / 100)) +
                             ($attendance * ($classInfo['attendance_percent'] / 100));
                
                $quarterSummaries[$quarter] = [
                    'ww_avg' => round($ww_avg, 2),
                    'pt_avg' => round($pt_avg, 2),
                    'qe_avg' => round($qe_avg, 2),
                    'ww_count' => count($types['WW']),
                    'pt_count' => count($types['PT']),
                    'qe_count' => count($types['QE']),
                    'final_grade' => round($finalGrade, 2),
                    'status' => $finalGrade >= 75 ? 'Passed' : 'Failed',
                ];
            }

            // Get class schedule
            $stmt = $pdo->prepare("
                SELECT 
                    day_of_week,
                    start_time,
                    end_time
                FROM teacher_schedules
                WHERE class_id = ?
                ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
            ");
            $stmt->execute([$classInfo['class_id']]);
            $schedules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get attendance for this subject
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
                FROM attendance
                WHERE student_id = ? AND subject_id = ?
            ");
            $stmt->execute([$student['id'], $classInfo['subject_id']]);
            $attendance = $stmt->fetch(\PDO::FETCH_ASSOC);

            $attendancePercentage = 0;
            if ($attendance && $attendance['total_days'] > 0) {
                $attendancePercentage = round(((int)$attendance['present_days'] / (int)$attendance['total_days']) * 100, 2);
            }

            $this->view->render('student/view-subject', [
                'title' => $classInfo['subject_name'],
                'user' => $user,
                'activeNav' => 'classes',
                'showBack' => true,
                'classInfo' => $classInfo,
                'gradesByQuarter' => $gradesByQuarter,
                'quarterSummaries' => $quarterSummaries,
                'schedules' => $schedules,
                'attendance' => $attendance,
                'attendancePercentage' => $attendancePercentage,
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load subject details: ' . $e->getMessage());
        }
    }

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
     * Get grade trend data for charts
     */
    private function getGradeTrendData($pdo, int $studentId, string $academicYear): array
    {
        try {
            // Get quarterly grades for all quarters
            $stmt = $pdo->prepare("
                SELECT 
                    g.quarter,
                    AVG(ROUND((g.grade_value / NULLIF(g.max_score, 0)) * 100, 2)) as avg_percentage,
                    COUNT(DISTINCT g.subject_id) as subject_count
                FROM grades g
                WHERE g.student_id = ? 
                  AND g.academic_year = ?
                GROUP BY g.quarter
                ORDER BY g.quarter
            ");
            $stmt->execute([$studentId, $academicYear]);
            $quarterlyData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Format for chart
            $labels = [];
            $data = [];
            $subjectCounts = [];
            
            for ($q = 1; $q <= 4; $q++) {
                $labels[] = "Q{$q}";
                $quarterData = array_filter($quarterlyData, fn($d) => (int)$d['quarter'] === $q);
                if (!empty($quarterData)) {
                    $quarter = reset($quarterData);
                    $data[] = round((float)$quarter['avg_percentage'], 2);
                    $subjectCounts[] = (int)$quarter['subject_count'];
                } else {
                    $data[] = null;
                    $subjectCounts[] = 0;
                }
            }
            
            return [
                'labels' => $labels,
                'data' => $data,
                'subject_counts' => $subjectCounts,
            ];
        } catch (\Exception $e) {
            error_log("getGradeTrendData error: " . $e->getMessage());
            return ['labels' => ['Q1', 'Q2', 'Q3', 'Q4'], 'data' => [], 'subject_counts' => []];
        }
    }
    
    /**
     * Get attendance trend data for charts
     */
    private function getAttendanceTrendData($pdo, int $studentId, int $sectionId, string $academicYear): array
    {
        try {
            // Extract year from academic year
            $yearParts = explode('-', $academicYear);
            $startYear = (int)($yearParts[0] ?? date('Y'));
            
            // Get monthly attendance data
            $stmt = $pdo->prepare("
                SELECT 
                    DATE_FORMAT(attendance_date, '%Y-%m') as month,
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status IN ('present', 'late', 'excused') THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
                FROM attendance
                WHERE student_id = ?
                  AND section_id = ?
                  AND YEAR(attendance_date) BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(attendance_date, '%Y-%m')
                ORDER BY month
                LIMIT 12
            ");
            $stmt->execute([$studentId, $sectionId, $startYear, $startYear + 1]);
            $monthlyData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $labels = [];
            $attendanceRates = [];
            $presentData = [];
            $absentData = [];
            
            foreach ($monthlyData as $month) {
                $monthName = date('M Y', strtotime($month['month'] . '-01'));
                $labels[] = $monthName;
                
                $total = (int)$month['total_days'];
                $present = (int)$month['present_days'];
                $absent = (int)$month['absent_days'];
                
                $rate = $total > 0 ? round(($present / $total) * 100, 1) : 100;
                $attendanceRates[] = $rate;
                $presentData[] = $present;
                $absentData[] = $absent;
            }
            
            return [
                'labels' => $labels,
                'attendance_rates' => $attendanceRates,
                'present_days' => $presentData,
                'absent_days' => $absentData,
            ];
        } catch (\Exception $e) {
            error_log("getAttendanceTrendData error: " . $e->getMessage());
            return ['labels' => [], 'attendance_rates' => [], 'present_days' => [], 'absent_days' => []];
        }
    }
    
    /**
     * Get subject performance data for charts
     */
    private function getSubjectPerformanceData($pdo, int $studentId, string $academicYear, int $quarter): array
    {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    s.name as subject_name,
                    AVG(ROUND((g.grade_value / NULLIF(g.max_score, 0)) * 100, 2)) as avg_grade
                FROM grades g
                JOIN subjects s ON g.subject_id = s.id
                WHERE g.student_id = ?
                  AND g.academic_year = ?
                  AND g.quarter = ?
                GROUP BY g.subject_id, s.name
                ORDER BY avg_grade ASC
                LIMIT 10
            ");
            $stmt->execute([$studentId, $academicYear, $quarter]);
            $subjectData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $labels = [];
            $data = [];
            $colors = [];
            
            foreach ($subjectData as $subject) {
                $labels[] = $subject['subject_name'];
                $grade = round((float)$subject['avg_grade'], 1);
                $data[] = $grade;
                
                // Color based on grade
                if ($grade >= 75) {
                    $colors[] = 'rgba(25, 135, 84, 0.8)'; // Green
                } elseif ($grade >= 70) {
                    $colors[] = 'rgba(255, 193, 7, 0.8)'; // Yellow
                } else {
                    $colors[] = 'rgba(220, 53, 69, 0.8)'; // Red
                }
            }
            
            return [
                'labels' => $labels,
                'data' => $data,
                'colors' => $colors,
            ];
        } catch (\Exception $e) {
            error_log("getSubjectPerformanceData error: " . $e->getMessage());
            return ['labels' => [], 'data' => [], 'colors' => []];
        }
    }
}
