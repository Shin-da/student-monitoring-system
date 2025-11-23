<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;

class AdviserController extends Controller
{
    public function dashboard(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'adviser') {
            \Helpers\ErrorHandler::forbidden('You need adviser privileges to access this page.');
            return;
        }
        try {
            $pdo = \Core\Database::connection((require BASE_PATH . '/config/config.php')['database']);

            $sectionsStmt = $pdo->prepare('\n                SELECT\n                    sec.id,\n                    sec.name,\n                    sec.grade_level,\n                    sec.room,\n                    COUNT(st.id) AS student_count\n                FROM sections sec\n                LEFT JOIN students st ON st.section_id = sec.id\n                WHERE sec.adviser_id = ? AND sec.is_active = 1\n                GROUP BY sec.id, sec.name, sec.grade_level, sec.room\n                ORDER BY sec.grade_level, sec.name\n            ');
            $sectionsStmt->execute([(int)$user['id']]);
            $sections = $sectionsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $sectionIds = array_column($sections, 'id');
            $students = [];
            if (!empty($sectionIds)) {
                $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
                $studentsStmt = $pdo->prepare("\n                    SELECT \n                        st.id,\n                        st.lrn,\n                        st.grade_level,\n                        st.section_id,\n                        u.name AS student_name\n                    FROM students st\n                    JOIN users u ON st.user_id = u.id\n                    WHERE st.section_id IN ($placeholders)\n                    ORDER BY u.name\n                ");
                $studentsStmt->execute($sectionIds);
                $students = $studentsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            }

            $classStats = [
                'sections' => count($sections),
                'students' => array_sum(array_column($sections, 'student_count')),
            ];

            $recentActivities = [];
            try {
                $activityStmt = $pdo->prepare('\n                    SELECT action, target_type, target_id, created_at\n                    FROM audit_logs\n                    WHERE user_id = ?\n                    ORDER BY created_at DESC\n                    LIMIT 10\n                ');
                $activityStmt->execute([(int)$user['id']]);
                $recentActivities = $activityStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $inner) {
                $recentActivities = [];
            }

            $this->view->render('adviser/dashboard', [
                'title' => 'Class Adviser Dashboard',
                'user' => $user,
                'activeNav' => 'dashboard',
                'showBack' => false,
                'sections' => $sections,
                'students' => $students,
                'class_stats' => $classStats,
                'recent_activities' => $recentActivities,
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load adviser dashboard: ' . $e->getMessage());
        }
    }

    public function students(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'adviser') {
            \Helpers\ErrorHandler::forbidden('You need adviser privileges to access this page.');
            return;
        }
        $this->view->render('adviser/students', [
            'title' => 'Student Management',
            'user' => $user,
            'activeNav' => 'students',
        ], 'layouts/dashboard');
    }

    public function performance(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'adviser') {
            \Helpers\ErrorHandler::forbidden('You need adviser privileges to access this page.');
            return;
        }
        $this->view->render('adviser/performance', [
            'title' => 'Student Performance',
            'user' => $user,
            'activeNav' => 'performance',
        ], 'layouts/dashboard');
    }

    public function communication(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'adviser') {
            \Helpers\ErrorHandler::forbidden('You need adviser privileges to access this page.');
            return;
        }
        $this->view->render('adviser/communication', [
            'title' => 'Communication Center',
            'user' => $user,
            'activeNav' => 'communication',
        ], 'layouts/dashboard');
    }
}


