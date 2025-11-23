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

            $studentInfo = [
                'class_name' => $profile['section_name'] ?? 'Unassigned',
                'lrn' => $profile['lrn'],
                'grade_level' => $profile['grade_level'],
                'school_year' => $profile['school_year'],
            ];

            // Basic academic stats placeholders (real grades integration later)
            $subjectsCount = 0;
            try {
                $sub = $pdo->prepare('SELECT COUNT(*) AS c FROM subjects WHERE is_active = 1 AND (grade_level = ? OR grade_level IS NULL)');
                $sub->execute([$profile['grade_level']]);
                $subjectsCount = (int)($sub->fetch()['c'] ?? 0);
            } catch (\Throwable $e) {
                $subjectsCount = 0;
            }

            $academicStats = [
                'overall_average' => 0,
                'passing_subjects' => 0,
                'total_subjects' => $subjectsCount,
                'improvement' => 0,
                'subjects_count' => $subjectsCount,
                'grade_level' => $profile['grade_level'],
                'school_year' => $profile['school_year']
            ];

            $classes = [];
            try {
                if (!empty($profile['student_id'])) {
                    $stmt = $pdo->prepare('\n                        SELECT\n                            c.id AS class_id,\n                            sub.name AS subject_name,\n                            sub.code AS subject_code,\n                            c.schedule,\n                            c.room,\n                            u.name AS teacher_name\n                        FROM student_classes sc\n                        JOIN classes c ON sc.class_id = c.id\n                        JOIN subjects sub ON c.subject_id = sub.id\n                        JOIN teachers t ON c.teacher_id = t.id\n                        JOIN users u ON t.user_id = u.id\n                        WHERE sc.student_id = ? AND sc.status = "enrolled"\n                        ORDER BY sub.name\n                    ');
                    $stmt->execute([$profile['student_id']]);
                    $classes = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                }

                if (empty($classes) && !empty($profile['section_id'])) {
                    $stmt = $pdo->prepare('\n                        SELECT\n                            c.id AS class_id,\n                            sub.name AS subject_name,\n                            sub.code AS subject_code,\n                            c.schedule,\n                            c.room,\n                            u.name AS teacher_name\n                        FROM classes c\n                        JOIN subjects sub ON c.subject_id = sub.id\n                        JOIN teachers t ON c.teacher_id = t.id\n                        JOIN users u ON t.user_id = u.id\n                        WHERE c.section_id = ? AND c.is_active = 1\n                        ORDER BY sub.name\n                    ');
                    $stmt->execute([$profile['section_id']]);
                    $classes = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                }
            } catch (\Throwable $inner) {
                $classes = [];
            }

            $this->view->render('student/dashboard', [
                'title' => 'Student Dashboard',
                'user' => $user,
                'activeNav' => 'dashboard',
                'showBack' => false,
                'student_info' => $studentInfo,
                'academic_stats' => $academicStats,
                // Placeholders until grades module is live
                'recent_grades' => [],
                'upcoming_assignments' => [],
                'classes' => $classes,
            ], 'layouts/dashboard');

        } catch (\Throwable $e) {
            \Helpers\ErrorHandler::internalServerError('Unable to load student dashboard.');
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

            // Get student ID
            $stmt = $pdo->prepare("SELECT id, section_id, grade_level FROM students WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
            }

            $studentId = (int)$student['id'];
            $gradeModel = new \Models\GradeModel();
            $academicYear = $gradeModel->getCurrentAcademicYear();
            $quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : 1;

            // Get all subjects for this grade level
            $stmt = $pdo->prepare("
                SELECT id, name, code, ww_percent, pt_percent, qe_percent 
                FROM subjects 
                WHERE grade_level = ? AND is_active = 1 
                ORDER BY name
            ");
            $stmt->execute([$student['grade_level']]);
            $subjects = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get quarterly grades for all subjects
            $quarterlyGrades = [];
            $overallSum = 0;
            $overallCount = 0;
            $passingCount = 0;
            $needsImprovementCount = 0;

            foreach ($subjects as $subject) {
                $grade = $gradeModel->calculateQuarterlyGrade(
                    $studentId,
                    (int)$subject['id'],
                    $quarter,
                    $academicYear
                );

                if ($grade) {
                    $quarterlyGrades[] = [
                        'subject_id' => (int)$subject['id'],
                        'subject_name' => $subject['name'],
                        'subject_code' => $subject['code'],
                        'ww_average' => $grade['ww_average'],
                        'pt_average' => $grade['pt_average'],
                        'qe_average' => $grade['qe_average'],
                        'final_grade' => $grade['final_grade'],
                        'status' => $grade['status'],
                    ];

                    $overallSum += $grade['final_grade'];
                    $overallCount++;
                    
                    if ($grade['final_grade'] >= 75) {
                        $passingCount++;
                    } else {
                        $needsImprovementCount++;
                    }
                } else {
                    // Subject with no grades yet
                    $quarterlyGrades[] = [
                        'subject_id' => (int)$subject['id'],
                        'subject_name' => $subject['name'],
                        'subject_code' => $subject['code'],
                        'ww_average' => null,
                        'pt_average' => null,
                        'qe_average' => null,
                        'final_grade' => null,
                        'status' => 'No Grades',
                    ];
                }
            }

            // Calculate overall average
            $overallAverage = $overallCount > 0 ? round($overallSum / $overallCount, 2) : 0;

            // Get available academic years from grades
            $stmt = $pdo->prepare("
                SELECT DISTINCT academic_year 
                FROM grades 
                WHERE student_id = ? 
                ORDER BY academic_year DESC
            ");
            $stmt->execute([$studentId]);
            $academicYears = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            if (empty($academicYears)) {
                $academicYears = [$academicYear];
            }

            $this->view->render('student/grades', [
                'title' => 'My Grades',
                'user' => $user,
                'activeNav' => 'grades',
                'student_id' => $studentId,
                'subjects' => $subjects,
                'quarterly_grades' => $quarterlyGrades,
                'current_quarter' => $quarter,
                'current_academic_year' => $academicYear,
                'academic_years' => $academicYears,
                'stats' => [
                    'overall_average' => $overallAverage,
                    'passing_subjects' => $passingCount,
                    'needs_improvement' => $needsImprovementCount,
                    'total_subjects' => count($subjects),
                ],
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
        $this->view->render('student/assignments', [
            'title' => 'My Assignments',
            'user' => $user,
            'activeNav' => 'assignments',
        ], 'layouts/dashboard');
    }

    public function profile(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }

        try {
            // Get database connection
            $config = require BASE_PATH . '/config/config.php';
            $db = \Core\Database::connection($config['database']);
            
            // Fetch complete student data with enhanced structure using the student_profiles view
            $userStmt = $db->prepare("
                SELECT * FROM student_profiles 
                WHERE user_id = ? AND user_status = 'active'
            ");
            $userStmt->execute([$user['id']]);
            $studentData = $userStmt->fetch();
            
            if (!$studentData) {
                \Helpers\ErrorHandler::notFound('Student profile not found.');
                return;
            }

            // Get section information with adviser details
            $sectionInfo = null;
            if ($studentData['section_id']) {
                try {
                    $sectionStmt = $db->prepare("
                        SELECT s.*, u.name as adviser_name, u.email as adviser_email
                        FROM sections s
                        LEFT JOIN teachers t ON s.adviser_id = t.id
                        LEFT JOIN users u ON t.user_id = u.id
                        WHERE s.id = ?
                    ");
                    $sectionStmt->execute([$studentData['section_id']]);
                    $sectionInfo = $sectionStmt->fetch();
                } catch (\Exception $e) {
                    // Fallback to basic section info
                    $sectionStmt = $db->prepare("SELECT * FROM sections WHERE id = ?");
                    $sectionStmt->execute([$studentData['section_id']]);
                    $sectionInfo = $sectionStmt->fetch();
                }
            }

            // Get subjects for the student's grade level
            $subjects = [];
            if ($studentData['grade_level']) {
                try {
                    $subjectsStmt = $db->prepare("
                        SELECT * FROM subjects 
                        WHERE grade_level = ? AND is_active = 1 
                        ORDER BY name
                    ");
                    $subjectsStmt->execute([$studentData['grade_level']]);
                    $subjects = $subjectsStmt->fetchAll();
                } catch (\Exception $e) {
                    // Subjects table might not have data yet
                    $subjects = [];
                }
            }

            // Get academic stats (enhanced with real data)
            $academicStats = [
                'overall_average' => 0,
                'passing_subjects' => 0,
                'total_subjects' => count($subjects),
                'improvement' => 0,
                'subjects_count' => count($subjects),
                'grade_level' => $studentData['grade_level'],
                'school_year' => $studentData['school_year']
            ];

            // Get enrollment information
            $enrollmentInfo = [
                'date_enrolled' => $studentData['date_enrolled'],
                'school_year' => $studentData['school_year'],
                'status' => $studentData['student_status'],
                'lrn' => $studentData['lrn']
            ];

            $this->view->render('student/profile', [
                'title' => 'My Profile',
                'user' => $user,
                'activeNav' => 'profile',
                'student_data' => $studentData,
                'section_info' => $sectionInfo,
                'subjects' => $subjects,
                'academic_stats' => $academicStats,
                'enrollment_info' => $enrollmentInfo,
            ], 'layouts/dashboard');
            
        } catch (\Exception $e) {
            error_log("Error fetching student profile: " . $e->getMessage());
            \Helpers\ErrorHandler::internalServerError('Unable to load profile data. Please try again later.');
        }
    }

    public function attendance(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }
        $this->view->render('student/attendance', [
            'title' => 'My Attendance',
            'user' => $user,
            'activeNav' => 'attendance',
        ], 'layouts/dashboard');
    }

    public function alerts(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }
        $this->view->render('student/alerts', [
            'title' => 'My Alerts',
            'user' => $user,
            'activeNav' => 'alerts',
        ], 'layouts/dashboard');
    }

    public function schedule(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }
        $this->view->render('student/schedule', [
            'title' => 'My Schedule',
            'user' => $user,
            'activeNav' => 'schedule',
        ], 'layouts/dashboard');
    }

    public function resources(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'student') {
            \Helpers\ErrorHandler::forbidden('You need student privileges to access this page.');
            return;
        }
        $this->view->render('student/resources', [
            'title' => 'Learning Resources',
            'user' => $user,
            'activeNav' => 'resources',
        ], 'layouts/dashboard');
    }
}


