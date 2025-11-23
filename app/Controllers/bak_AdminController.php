<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use Helpers\Csrf;
use PDO;
use Exception;

class AdminController extends Controller
{
    public function dashboard(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        // Aggregate user statistics by role using live data
        $stmt = $pdo->prepare('
            SELECT role, COUNT(*) AS count
            FROM users
            WHERE status = "active"
            GROUP BY role
        ');
        $stmt->execute();
        $userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pendingCount = $this->fetchCount($pdo, 'SELECT COUNT(*) FROM users WHERE status = "pending"');

        $systemStats = [
            'sections' => $this->fetchCount($pdo, 'SELECT COUNT(*) FROM sections WHERE is_active = 1', [], 'SELECT COUNT(*) FROM sections'),
            'classes' => $this->fetchCount($pdo, 'SELECT COUNT(*) FROM classes WHERE is_active = 1', [], 'SELECT COUNT(*) FROM classes'),
            'subjects' => $this->fetchCount($pdo, 'SELECT COUNT(*) FROM subjects WHERE is_active = 1', [], 'SELECT COUNT(*) FROM subjects'),
            'unassigned_students' => $this->fetchCount($pdo, 'SELECT COUNT(*) FROM students WHERE section_id IS NULL'),
        ];

        $recentActivity = $this->fetchRecentActivity($pdo);

        $this->view->render('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'user' => $user,
            'activeNav' => 'dashboard',
            'showBack' => false,
            'pendingCount' => $pendingCount,
            'userStats' => $userStats,
            'systemStats' => $systemStats,
            'recentActivity' => $recentActivity,
        ], 'layouts/dashboard');
    }

    private function fetchCount(PDO $pdo, string $sql, array $params = [], ?string $fallbackSql = null): int
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            if ($fallbackSql !== null && trim($fallbackSql) !== trim($sql)) {
                return $this->fetchCount($pdo, $fallbackSql, $params);
            }
        }

        return 0;
    }

    private function fetchRecentActivity(PDO $pdo, int $limit = 6): array
    {
        try {
            $stmt = $pdo->prepare('
                SELECT 
                    al.id,
                    al.user_id,
                    al.action,
                    al.target_type,
                    al.target_id,
                    al.details,
                    al.created_at,
                    u.name AS actor_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT :limit
            ');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return array_map(static function (array $row): array {
                $details = null;
                if (!empty($row['details'])) {
                    $decoded = json_decode((string)$row['details'], true);
                    $details = is_array($decoded) ? $decoded : null;
                }

                return [
                    'id' => (int)$row['id'],
                    'user_id' => isset($row['user_id']) ? (int)$row['user_id'] : null,
                    'actor_name' => $row['actor_name'] ?? 'System',
                    'action' => $row['action'] ?? 'activity',
                    'target_type' => $row['target_type'] ?? null,
                    'target_id' => isset($row['target_id']) ? (int)$row['target_id'] : null,
                    'details' => $details,
                    'created_at' => $row['created_at'] ?? null,
                ];
            }, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function users(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        // Get all users with their status
        $stmt = $pdo->prepare('
            SELECT u.*, 
                   approver.name as approved_by_name
            FROM users u 
            LEFT JOIN users approver ON u.approved_by = approver.id 
            ORDER BY u.created_at DESC
        ');
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view->render('admin/users', [
            'title' => 'User Management',
            'user' => $user,
            'activeNav' => 'users',
            'users' => $users,
            'csrf_token' => \Helpers\Csrf::generateToken(),
        ], 'layouts/dashboard');
    }

    public function approveUser(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF token mismatch']);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));
            return;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $pdo->beginTransaction();

            // Get user details
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :user_id AND status = "pending"');
            $stmt->execute(['user_id' => $userId]);
            $userToApprove = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userToApprove) {
                throw new \Exception('User not found or not pending approval');
            }

            // Update user status to active and set approval info
            $stmt = $pdo->prepare('
                UPDATE users 
                SET status = "active", 
                    approved_by = :admin_id, 
                    approved_at = NOW() 
                WHERE id = :user_id AND status = "pending"
            ');
            $stmt->execute([
                'admin_id' => $user['id'],
                'user_id' => $userId
            ]);

            // Create role-specific entry
            $role = $userToApprove['role'];
            switch ($role) {
                case 'student':
                    $stmt = $pdo->prepare('
                        INSERT INTO students (user_id, lrn, grade_level, section_id) 
                        VALUES (:user_id, :lrn, :grade_level, :section_id)
                    ');
                    $stmt->execute([
                        'user_id' => $userId,
                        'lrn' => 'LRN' . str_pad((string)$userId, 6, '0', STR_PAD_LEFT),
                        'grade_level' => 7, // Default grade level
                        'section_id' => 1   // Default section
                    ]);
                    break;

                case 'teacher':
                    $stmt = $pdo->prepare('
                        INSERT INTO teachers (user_id, is_adviser) 
                        VALUES (:user_id, 0)
                    ');
                    $stmt->execute(['user_id' => $userId]);
                    break;

                case 'adviser':
                    $stmt = $pdo->prepare('
                        INSERT INTO teachers (user_id, is_adviser) 
                        VALUES (:user_id, 1)
                    ');
                    $stmt->execute(['user_id' => $userId]);
                    
                    // Also create adviser entry
                    $stmt = $pdo->prepare('
                        INSERT INTO advisers (user_id, section_id) 
                        VALUES (:user_id, :section_id)
                    ');
                    $stmt->execute([
                        'user_id' => $userId,
                        'section_id' => 1 // Default section
                    ]);
                    break;

                case 'parent':
                    // Parents don't need a separate table entry initially
                    // They can be linked to students later
                    break;
            }

            // Update user request status
            $stmt = $pdo->prepare('
                UPDATE user_requests 
                SET status = "approved", 
                    processed_at = NOW(), 
                    processed_by = :admin_id 
                WHERE user_id = :user_id AND status = "pending"
            ');
            $stmt->execute([
                'admin_id' => $user['id'],
                'user_id' => $userId
            ]);

            // Log the approval action
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                VALUES (:admin_id, "user_approved", "user", :target_id, :details, :ip, :user_agent)
            ');
            $stmt->execute([
                'admin_id' => $user['id'],
                'target_id' => $userId,
                'details' => json_encode([
                    'approved_role' => $role,
                    'user_email' => $userToApprove['email'],
                    'user_name' => $userToApprove['name']
                ]),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $pdo->commit();

            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'status' => 'active', 'message' => 'User approved successfully']);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));

        } catch (\Exception $e) {
            $pdo->rollBack();
            
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));
        }
    }

    public function rejectUser(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF token mismatch']);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));
            return;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $rejectionReason = trim($_POST['rejection_reason'] ?? 'No reason provided');
        
        if (!$userId) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $pdo->beginTransaction();

            // Get user details before deletion
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :user_id AND status = "pending"');
            $stmt->execute(['user_id' => $userId]);
            $userToReject = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userToReject) {
                throw new \Exception('User not found or not pending approval');
            }

            // Update user request status to rejected
            $stmt = $pdo->prepare('
                UPDATE user_requests 
                SET status = "rejected", 
                    processed_at = NOW(), 
                    processed_by = :admin_id,
                    rejection_reason = :rejection_reason
                WHERE user_id = :user_id AND status = "pending"
            ');
            $stmt->execute([
                'admin_id' => $user['id'],
                'user_id' => $userId,
                'rejection_reason' => $rejectionReason
            ]);

            // Delete pending user
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :user_id AND status = "pending"');
            $stmt->execute(['user_id' => $userId]);

            // Log the rejection action
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                VALUES (:admin_id, "user_rejected", "user", :target_id, :details, :ip, :user_agent)
            ');
            $stmt->execute([
                'admin_id' => $user['id'],
                'target_id' => $userId,
                'details' => json_encode([
                    'rejected_role' => $userToReject['role'],
                    'user_email' => $userToReject['email'],
                    'user_name' => $userToReject['name'],
                    'rejection_reason' => $rejectionReason
                ]),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $pdo->commit();

            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'deleted' => true, 'message' => 'User rejected successfully']);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));

        } catch (\Exception $e) {
            $pdo->rollBack();
            
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));
        }
    }

    public function suspendUser(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            header('Location: ' . \Helpers\Url::to('/admin/users'));
            return;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) {
            header('Location: ' . \Helpers\Url::to('/admin/users'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        // Suspend user
        $stmt = $pdo->prepare('UPDATE users SET status = "suspended" WHERE id = :user_id AND id != :admin_id');
        $stmt->execute([
            'user_id' => $userId,
            'admin_id' => $user['id'] // Prevent admin from suspending themselves
        ]);

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'status' => 'suspended']);
            return;
        }
        header('Location: ' . \Helpers\Url::to('/admin/users'));
    }

    public function activateUser(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            header('Location: ' . \Helpers\Url::to('/admin/users'));
            return;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) {
            header('Location: ' . \Helpers\Url::to('/admin/users'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        // Activate user
        $stmt = $pdo->prepare('UPDATE users SET status = "active" WHERE id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'status' => 'active']);
            return;
        }
        header('Location: ' . \Helpers\Url::to('/admin/users'));
    }

    public function deleteUser(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        if (!\Helpers\Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF token mismatch']);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));
            return;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId || $userId === (int)$user['id']) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Cannot delete yourself or invalid user ID']);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $pdo->beginTransaction();

            // Get user details before deletion
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userToDelete) {
                throw new \Exception('User not found');
            }

            // Delete role-specific entries first (due to foreign key constraints)
            $role = $userToDelete['role'];
            switch ($role) {
                case 'student':
                    $stmt = $pdo->prepare('DELETE FROM students WHERE user_id = :user_id');
                    $stmt->execute(['user_id' => $userId]);
                    break;

                case 'teacher':
                case 'adviser':
                case 'parent':
                case 'admin':
                    // These roles don't have separate tables in the current schema
                    // They only exist in the users table
                    break;
            }

            // Delete the user
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :user_id');
            $stmt->execute(['user_id' => $userId]);

            // Log the deletion action
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                VALUES (:admin_id, "user_deleted", "user", :target_id, :details, :ip, :user_agent)
            ');
            $stmt->execute([
                'admin_id' => $user['id'],
                'target_id' => $userId,
                'details' => json_encode([
                    'deleted_role' => $role,
                    'user_email' => $userToDelete['email'],
                    'user_name' => $userToDelete['name']
                ]),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $pdo->commit();

            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'deleted' => true, 'message' => 'User deleted successfully']);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));

        } catch (\Exception $e) {
            $pdo->rollBack();
            
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/users'));
        }
    }

    public function createParent(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
            $pdo = Database::connection($config['database']);

            // Get all active student users for parent linking (centralized)
                $stmt = $pdo->prepare('
                    SELECT u.id, u.name, s.lrn, s.grade_level 
                    FROM users u 
                    LEFT JOIN students s ON s.user_id = u.id 
                    WHERE u.role = "student" AND u.status = "active" 
                    ORDER BY u.name
                ');
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view->render('admin/create-parent', [
                'title' => 'Create Parent Account',
                'user' => $user,
                'activeNav' => 'users',
                'students' => $students,
            ], 'layouts/dashboard');
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            header('Location: ' . \Helpers\Url::to('/admin/create-parent'));
            return;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $studentId = (int)($_POST['student_id'] ?? 0);
        $relationship = trim((string)($_POST['relationship'] ?? 'guardian'));

        if (!\Helpers\Validator::required($name) || !\Helpers\Validator::email($email) || 
            !\Helpers\Validator::minLength($password, 8) || !$studentId) {
            $this->view->render('admin/create-parent', [
                'title' => 'Create Parent Account',
                'user' => $user,
                'activeNav' => 'users',
                'error' => 'Please provide valid details.'
            ], 'layouts/dashboard');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        // Check uniqueness of email
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $this->view->render('admin/create-parent', [
                'title' => 'Create Parent Account',
                'user' => $user,
                'activeNav' => 'users',
                'error' => 'Email already in use.'
            ], 'layouts/dashboard');
            return;
        }

        // Verify student user exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = "student" LIMIT 1');
        $stmt->execute(['id' => $studentId]);
        if (!$stmt->fetch()) {
            $this->view->render('admin/create-parent', [
                'title' => 'Create Parent Account',
                'user' => $user,
                'activeNav' => 'users',
                'error' => 'Selected student does not exist.'
            ], 'layouts/dashboard');
            return;
        }

        try {
            $pdo->beginTransaction();

            // Create parent user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('
                INSERT INTO users (role, email, password_hash, name, status, approved_by, approved_at) 
                VALUES ("parent", :email, :hash, :name, "active", :approved_by, NOW())
            ');
            $insert->execute([
                'email' => $email,
                'hash' => $hash,
                'name' => $name,
                'approved_by' => $user['id']
            ]);

            $parentUserId = $pdo->lastInsertId();

            // Link parent to student in centralized users table
            $update = $pdo->prepare('
                UPDATE users 
                SET linked_student_user_id = :student_user_id,
                    parent_relationship = :relationship
                WHERE id = :parent_user_id
            ');
            $update->execute([
                'student_user_id' => $studentId,
                'relationship' => $relationship,
                'parent_user_id' => $parentUserId,
            ]);

            $pdo->commit();
            header('Location: ' . \Helpers\Url::to('/admin/users'));

        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->view->render('admin/create-parent', [
                'title' => 'Create Parent Account',
                'user' => $user,
                'activeNav' => 'users',
                'error' => 'Parent creation failed. Please try again.'
            ], 'layouts/dashboard');
        }
    }

    public function settings(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        $this->view->render('admin/settings', [
            'title' => 'System Settings',
            'user' => $user,
            'activeNav' => 'settings',
        ], 'layouts/dashboard');
    }

    public function classes(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            // Get all classes with their details
            $stmt = $pdo->prepare('
                SELECT 
                    c.id,
                    c.schedule,
                    c.room as class_room,
                    c.is_active,
                    c.created_at,
                    sec.id as section_id,
                    sec.name as section_name,
                    sec.grade_level,
                    sec.room as section_room,
                    sub.id as subject_id,
                    sub.name as subject_name,
                    sub.code as subject_code,
                    t.id as teacher_id,
                    u.name as teacher_name,
                    u.email as teacher_email
                FROM classes c
                JOIN sections sec ON c.section_id = sec.id
                JOIN subjects sub ON c.subject_id = sub.id
                JOIN teachers t ON c.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                WHERE c.school_year = "2025-2026"
                ORDER BY sec.grade_level, sec.name, sub.name
            ');
            $stmt->execute();
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get all sections for dropdown
            $stmt = $pdo->prepare('
                SELECT id, name, grade_level, room 
                FROM sections 
                WHERE school_year = "2025-2026" AND is_active = 1
                ORDER BY grade_level, name
            ');
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get all subjects for dropdown
            $stmt = $pdo->prepare('
                SELECT id, name, code, grade_level 
                FROM subjects 
                WHERE is_active = 1
                ORDER BY grade_level, name
            ');
            $stmt->execute();
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get all teachers for dropdown
            $stmt = $pdo->prepare('
                SELECT t.id, u.name, u.email, t.department
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                WHERE u.status = "active"
                ORDER BY u.name
            ');
            $stmt->execute();
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view->render('admin/classes', [
                'title' => 'Class Management',
                'user' => $user,
                'activeNav' => 'classes',
                'classes' => $classes,
                'sections' => $sections,
                'subjects' => $subjects,
                'teachers' => $teachers,
                'csrf_token' => \Helpers\Csrf::generateToken(),
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load class management page: ' . $e->getMessage());
        }
    }

    public function createClass(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->classes();
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            header('Location: ' . \Helpers\Url::to('/admin/classes'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $pdo->beginTransaction();

            // Validate required fields
            $requiredFields = ['section_id', 'subject_id', 'teacher_id', 'schedule', 'room'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new \Exception("Missing required field: {$field}");
                }
            }

            $sectionId = $_POST['section_id'];
            $subjectId = $_POST['subject_id'];
            $teacherId = $_POST['teacher_id'];
            $schedule = $_POST['schedule'];
            $room = $_POST['room'];
            $schoolYear = $_POST['school_year'] ?? '2025-2026';
            $semester = $_POST['semester'] ?? '1st';

            // Parse schedule to check for conflicts
            $scheduleData = $this->parseSchedule($schedule);
            if (!$scheduleData) {
                throw new \Exception('Invalid schedule format. Use format like "M 8:00 AM-9:00 AM" or "TH 10:00 AM-11:00 AM"');
            }

            // Check for conflicts
            $conflicts = $this->checkScheduleConflicts($pdo, $teacherId, $scheduleData['days'], $scheduleData['start_time'], $scheduleData['end_time']);
            if (!empty($conflicts)) {
                throw new \Exception('Schedule conflict detected. Teacher already has classes during this time.');
            }

            // Check if a class with the same section, subject, semester, and school year already exists
            $stmt = $pdo->prepare('
                SELECT c.id, c.schedule, sub.name as subject_name, sec.name as section_name
                FROM classes c
                JOIN subjects sub ON c.subject_id = sub.id
                JOIN sections sec ON c.section_id = sec.id
                WHERE c.section_id = ? 
                AND c.subject_id = ? 
                AND c.semester = ? 
                AND c.school_year = ?
                AND c.is_active = 1
                LIMIT 1
            ');
            $stmt->execute([$sectionId, $subjectId, $semester, $schoolYear]);
            $existingClass = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingClass) {
                throw new \Exception("A class for {$existingClass['subject_name']} in {$existingClass['section_name']} already exists for {$semester} semester, {$school_year}. Please edit the existing class or choose a different subject.");
            }
            
            // Check if the unique constraint on (section_id, grade_level, semester, school_year) would be violated
            // This constraint may exist without subject_id, preventing multiple subjects per section
            $stmt = $pdo->prepare('
                SELECT sec.grade_level, COUNT(*) as existing_classes
                FROM sections sec
                LEFT JOIN classes c ON c.section_id = sec.id 
                    AND c.semester = ? 
                    AND c.school_year = ?
                    AND c.is_active = 1
                WHERE sec.id = ?
                GROUP BY sec.id, sec.grade_level
            ');
            $stmt->execute([$semester, $schoolYear, $sectionId]);
            $sectionInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Note: If the database has a unique constraint on (section_id, grade_level, semester, school_year)
            // without subject_id, it will prevent multiple classes with different subjects.
            // The actual constraint violation will be caught by the PDOException handler below.

            // Create the class
            $stmt = $pdo->prepare('
                INSERT INTO classes (section_id, subject_id, teacher_id, school_year, semester, schedule, room, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ');
            $stmt->execute([$sectionId, $subjectId, $teacherId, $schoolYear, $semester, $schedule, $room]);
            $classId = $pdo->lastInsertId();

            // Create teacher schedule entries
            $this->createTeacherSchedules($pdo, $teacherId, $classId, $scheduleData['days'], $scheduleData['start_time'], $scheduleData['end_time']);

            $pdo->commit();

            header('Location: ' . \Helpers\Url::to('/admin/classes?success=class_created'));
            exit();

        } catch (\PDOException $e) {
            $pdo->rollBack();
            
            // Handle unique constraint violations
            if ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errorMessage = 'Cannot create class: ';
                
                // Check if it's the unique_class_assignment constraint
                if (strpos($e->getMessage(), 'unique_class_assignment') !== false) {
                    // This constraint likely doesn't include subject_id, preventing multiple subjects per section
                    $errorMessage .= 'The database constraint prevents creating multiple classes for the same section, semester, and school year. ';
                    $errorMessage .= 'If you need to add different subjects to the same section, the database constraint may need to be updated to include the subject_id field.';
                } else {
                    // Generic duplicate entry error
                    $errorMessage .= 'A class with this combination already exists. ';
                    if (preg_match("/Duplicate entry '([^']+)' for key '([^']+)'/", $e->getMessage(), $matches)) {
                        $errorMessage .= "Please ensure each class has a unique combination of section, subject, semester, and school year.";
                    } else {
                        $errorMessage .= 'Each combination of section, subject, semester, and school year must be unique.';
                    }
                }
                
                header('Location: ' . \Helpers\Url::to('/admin/classes?error=' . urlencode($errorMessage)));
            } else {
                header('Location: ' . \Helpers\Url::to('/admin/classes?error=' . urlencode('Database error: ' . $e->getMessage())));
            }
            exit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: ' . \Helpers\Url::to('/admin/classes?error=' . urlencode($e->getMessage())));
            exit();
        }
    }

    private function parseSchedule($schedule): ?array
    {
        // Parse schedule like "M 8:00 AM-9:00 AM" or "TH 10:00 AM-11:00 AM"
        if (!preg_match('/^([MTWFS]+)\s+(\d{1,2}:\d{2}\s+[AP]M)-(\d{1,2}:\d{2}\s+[AP]M)$/', $schedule, $matches)) {
            return null;
        }

        $dayCodes = $matches[1];
        $startTimeAMPM = $matches[2];
        $endTimeAMPM = $matches[3];

        // Convert AM/PM to 24-hour format
        $startTime = date('H:i:s', strtotime($startTimeAMPM));
        $endTime = date('H:i:s', strtotime($endTimeAMPM));

        // Convert day codes to full day names
        $dayMap = [
            'M' => 'Monday',
            'T' => 'Tuesday',
            'W' => 'Wednesday',
            'F' => 'Friday',
            'S' => 'Saturday'
        ];

        $days = [];
        for ($i = 0; $i < strlen($dayCodes); $i++) {
            $code = $dayCodes[$i];
            if ($code === 'T' && isset($dayCodes[$i + 1]) && $dayCodes[$i + 1] === 'H') {
                $days[] = 'Thursday';
                $i++; // Skip next H
            } else {
                $days[] = $dayMap[$code] ?? null;
            }
        }

        $days = array_filter($days);
        if (empty($days)) {
            return null;
        }

        return [
            'days' => $days,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_ampm' => $startTimeAMPM,
            'end_ampm' => $endTimeAMPM
        ];
    }

    private function checkScheduleConflicts($pdo, $teacherId, $days, $startTime, $endTime): array
    {
        $placeholders = str_repeat('?,', count($days) - 1) . '?';
        $params = array_merge([$teacherId], $days, [$endTime, $startTime]);

        $stmt = $pdo->prepare("
            SELECT ts.*, c.schedule, sec.name as section_name, sub.name as subject_name
            FROM teacher_schedules ts
            LEFT JOIN classes c ON ts.class_id = c.id
            LEFT JOIN sections sec ON c.section_id = sec.id
            LEFT JOIN subjects sub ON c.subject_id = sub.id
            WHERE ts.teacher_id = ? 
            AND ts.day_of_week IN ($placeholders)
            AND (ts.start_time < ? AND ts.end_time > ?)
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function createTeacherSchedules($pdo, $teacherId, $classId, $days, $startTime, $endTime): void
    {
        $stmt = $pdo->prepare('
            INSERT INTO teacher_schedules (teacher_id, day_of_week, start_time, end_time, class_id)
            VALUES (?, ?, ?, ?, ?)
        ');

        foreach ($days as $day) {
            $stmt->execute([$teacherId, $day, $startTime, $endTime, $classId]);
        }
    }

    public function reports(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        // Get user statistics for reports
        $stmt = $pdo->prepare('SELECT role, COUNT(*) as count FROM users WHERE status = "active" GROUP BY role');
        $stmt->execute();
        $userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate totals
        $totalUsers = array_sum(array_column($userStats, 'count'));
        $studentCount = 0;
        $teacherCount = 0;
        $parentCount = 0;

        foreach ($userStats as $stat) {
            switch ($stat['role']) {
                case 'student':
                    $studentCount = (int)$stat['count'];
                    break;
                case 'teacher':
                    $teacherCount = (int)$stat['count'];
                    break;
                case 'parent':
                    $parentCount = (int)$stat['count'];
                    break;
            }
        }

        $this->view->render('admin/reports', [
            'title' => 'Reports & Analytics',
            'user' => $user,
            'activeNav' => 'reports',
            'totalUsers' => $totalUsers,
            'studentCount' => $studentCount,
            'teacherCount' => $teacherCount,
            'parentCount' => $parentCount,
        ], 'layouts/dashboard');
    }

    public function logs(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        $this->view->render('admin/logs', [
            'title' => 'System Logs & Audit Trail',
            'user' => $user,
            'activeNav' => 'logs',
        ], 'layouts/dashboard');
    }

    public function createStudent(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
            $pdo = Database::connection($config['database']);

            // Get available sections for dropdown with capacity info
            $stmt = $pdo->prepare('
                SELECT 
                    s.id, 
                    s.name, 
                    s.grade_level, 
                    s.room,
                    s.max_students,
                    COUNT(st.id) as enrolled_students,
                    (s.max_students - COUNT(st.id)) as available_slots,
                    CASE 
                        WHEN COUNT(st.id) >= s.max_students THEN "full"
                        WHEN COUNT(st.id) >= s.max_students * 0.8 THEN "nearly_full"
                        ELSE "available"
                    END as status
                FROM sections s
                LEFT JOIN students st ON st.section_id = s.id
                WHERE s.school_year = "2025-2026" AND s.is_active = 1
                GROUP BY s.id, s.name, s.grade_level, s.room, s.max_students
                ORDER BY s.grade_level, s.name
            ');
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view->render('admin/create-student', [
                'title' => 'Register New Student',
                'user' => $user,
                'activeNav' => 'users',
                'sections' => $sections,
                'csrf_token' => \Helpers\Csrf::generateToken(),
            ], 'layouts/dashboard');
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            header('Location: ' . \Helpers\Url::to('/admin/create-student'));
            return;
        }

        // Validate required fields
        $requiredFields = [
            'first_name', 'last_name', 'email', 'password', 'grade_level', 'section_id'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $this->view->render('admin/create-student', [
                    'title' => 'Register New Student',
                    'user' => $user,
                    'activeNav' => 'users',
                    'error' => "Please fill in all required fields. Missing: {$field}"
                ], 'layouts/dashboard');
                return;
            }
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $pdo->beginTransaction();

            // Check if email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $_POST['email']]);
            if ($stmt->fetch()) {
                throw new \Exception('Email already exists in the system.');
            }


            // Check if LRN already exists (if provided)
            if (!empty($_POST['lrn'])) {
                $stmt = $pdo->prepare('SELECT id FROM students WHERE lrn = :lrn LIMIT 1');
                $stmt->execute(['lrn' => $_POST['lrn']]);
                if ($stmt->fetch()) {
                    throw new \Exception('LRN already exists in the system.');
                }
            }

            // Create user account
            // Check section capacity before creating student
            if (!empty($_POST['section_id'])) {
                $sectionId = (int)$_POST['section_id'];
                $stmt = $pdo->prepare('
                    SELECT s.id, s.name, s.max_students, COUNT(st.id) as enrolled_students
                    FROM sections s
                    LEFT JOIN students st ON st.section_id = s.id
                    WHERE s.id = ? AND s.is_active = 1
                    GROUP BY s.id, s.name, s.max_students
                ');
                $stmt->execute([$sectionId]);
                $section = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$section) {
                    throw new \Exception('Selected section not found or inactive.');
                }
                
                $enrolled = (int)$section['enrolled_students'];
                $maxStudents = (int)$section['max_students'];
                
                if ($enrolled >= $maxStudents) {
                    throw new \Exception("Section '{$section['name']}' is full ({$enrolled}/{$maxStudents}). Please choose another section or increase the section capacity.");
                }
            }

            $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $fullName = trim($_POST['first_name'] . ' ' . ($_POST['middle_name'] ?? '') . ' ' . $_POST['last_name']);
            
            $stmt = $pdo->prepare('
                INSERT INTO users (role, email, password_hash, name, status, approved_by, approved_at) 
                VALUES ("student", :email, :password_hash, :name, "active", :approved_by, NOW())
            ');
            $stmt->execute([
                'email' => $_POST['email'],
                'password_hash' => $passwordHash,
                'name' => $fullName,
                'approved_by' => $user['id']
            ]);

            $userId = $pdo->lastInsertId();

            // Generate LRN if not provided
            $lrn = $_POST['lrn'] ?? 'LRN' . str_pad((string)$userId, 6, '0', STR_PAD_LEFT);


            // Create student profile
            $stmt = $pdo->prepare('
                INSERT INTO students (
                    user_id, lrn, first_name, last_name, middle_name,
                    birth_date, gender, contact_number, address, grade_level, section_id,
                    guardian_name, guardian_contact, guardian_relationship, school_year,
                    enrollment_status, previous_school, medical_conditions, allergies,
                    emergency_contact_name, emergency_contact_number, emergency_contact_relationship,
                    notes
                ) VALUES (
                    :user_id, :lrn, :first_name, :last_name, :middle_name,
                    :birth_date, :gender, :contact_number, :address, :grade_level, :section_id,
                    :guardian_name, :guardian_contact, :guardian_relationship, :school_year,
                    :enrollment_status, :previous_school, :medical_conditions, :allergies,
                    :emergency_contact_name, :emergency_contact_number, :emergency_contact_relationship,
                    :notes
                )
            ');

            $stmt->execute([
                'user_id' => $userId,
                'lrn' => $lrn,
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'middle_name' => $_POST['middle_name'] ?? null,
                'birth_date' => $_POST['birth_date'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'contact_number' => $_POST['contact_number'] ?? null,
                'address' => $_POST['address'] ?? null,
                'grade_level' => (int)$_POST['grade_level'],
                'section_id' => (int)$_POST['section_id'],
                'guardian_name' => $_POST['guardian_name'] ?? null,
                'guardian_contact' => $_POST['guardian_contact'] ?? null,
                'guardian_relationship' => $_POST['guardian_relationship'] ?? null,
                'school_year' => $_POST['school_year'] ?? '2025-2026',
                'enrollment_status' => $_POST['enrollment_status'] ?? 'enrolled',
                'previous_school' => $_POST['previous_school'] ?? null,
                'medical_conditions' => $_POST['medical_conditions'] ?? null,
                'allergies' => $_POST['allergies'] ?? null,
                'emergency_contact_name' => $_POST['emergency_contact_name'] ?? null,
                'emergency_contact_number' => $_POST['emergency_contact_number'] ?? null,
                'emergency_contact_relationship' => $_POST['emergency_contact_relationship'] ?? null,
                'notes' => $_POST['notes'] ?? null
            ]);

            // Log the student creation
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                VALUES (:admin_id, "student_created", "student", :student_id, :details, :ip, :user_agent)
            ');
            $stmt->execute([
                'admin_id' => $user['id'],
                'student_id' => $userId,
                'details' => json_encode([
                    'student_name' => $fullName,
                    'student_email' => $_POST['email'],
                    'student_number' => $studentNumber,
                    'lrn' => $lrn,
                    'grade_level' => $_POST['grade_level'],
                    'section_id' => $_POST['section_id']
                ]),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $pdo->commit();

            // Redirect to success page or users list
            header('Location: ' . \Helpers\Url::to('/admin/users?success=student_created&student_id=' . $userId));
            return;

        } catch (\Exception $e) {
            $pdo->rollBack();
            
            // Get sections again for form re-render
            $stmt = $pdo->prepare('SELECT id, name, grade_level, room FROM sections WHERE school_year = "2025-2026" ORDER BY grade_level, name');
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view->render('admin/create-student', [
                'title' => 'Register New Student',
                'user' => $user,
                'activeNav' => 'users',
                'sections' => $sections,
                'error' => $e->getMessage(),
                'form_data' => $_POST // Preserve form data for user convenience
            ], 'layouts/dashboard');
        }
    }

    /**
     * Display adviser assignment page
     */
    public function assignAdvisers(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'admin') {
            header('Location: ' . \Helpers\Url::to('/login'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            // Get all sections with their current advisers
            $stmt = $pdo->prepare('
                SELECT s.id, s.name, s.grade_level, s.room, s.adviser_id,
                       u.name as adviser_name, u.email as adviser_email
                FROM sections s
                LEFT JOIN users u ON s.adviser_id = u.id
                WHERE s.is_active = 1 AND s.school_year = "2025-2026"
                ORDER BY s.grade_level, s.name
            ');
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get all available advisers (users with adviser role)
            $stmt = $pdo->prepare('
                SELECT u.id, u.name, u.email, t.is_adviser
                FROM users u
                LEFT JOIN teachers t ON u.id = t.user_id
                WHERE u.role = "adviser" AND u.status = "active"
                ORDER BY u.name
            ');
            $stmt->execute();
            $advisers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get teachers who can be assigned as advisers
            $stmt = $pdo->prepare('
                SELECT u.id, u.name, u.email, t.is_adviser
                FROM users u
                LEFT JOIN teachers t ON u.id = t.user_id
                WHERE u.role = "teacher" AND u.status = "active"
                ORDER BY u.name
            ');
            $stmt->execute();
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view->render('admin/assign-advisers', [
                'title' => 'Assign Advisers to Sections',
                'user' => $user,
                'activeNav' => 'sections',
                'sections' => $sections,
                'advisers' => $advisers,
                'teachers' => $teachers
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            $this->view->render('admin/assign-advisers', [
                'title' => 'Assign Advisers to Sections',
                'user' => $user,
                'activeNav' => 'sections',
                'sections' => [],
                'advisers' => [],
                'teachers' => [],
                'error' => $e->getMessage()
            ], 'layouts/dashboard');
        }
    }

    /**
     * Handle adviser assignment (POST)
     */
    public function assignAdviser(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'admin') {
            header('Location: ' . \Helpers\Url::to('/login'));
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            header('Location: ' . \Helpers\Url::to('/admin/assign-advisers?error=csrf_invalid'));
            return;
        }

        $sectionId = (int)($_POST['section_id'] ?? 0);
        $adviserId = (int)($_POST['adviser_id'] ?? 0);

        if (!$sectionId || !$adviserId) {
            header('Location: ' . \Helpers\Url::to('/admin/assign-advisers?error=missing_data'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $pdo->beginTransaction();

            // Check if section exists
            $stmt = $pdo->prepare('SELECT id, name FROM sections WHERE id = ? AND is_active = 1');
            $stmt->execute([$sectionId]);
            $section = $stmt->fetch();
            if (!$section) {
                throw new \Exception('Section not found.');
            }

            // Check if adviser exists and is active
            $stmt = $pdo->prepare('SELECT id, name, role FROM users WHERE id = ? AND status = "active"');
            $stmt->execute([$adviserId]);
            $adviser = $stmt->fetch();
            if (!$adviser) {
                throw new \Exception('Adviser not found or inactive.');
            }

            // Ensure user has adviser role
            if ($adviser['role'] !== 'adviser') {
                // Update user role to adviser if they're a teacher
                if ($adviser['role'] === 'teacher') {
                    $stmt = $pdo->prepare('UPDATE users SET role = "adviser" WHERE id = ?');
                    $stmt->execute([$adviserId]);
                } else {
                    throw new \Exception('User must be a teacher or adviser to be assigned as section adviser.');
                }
            }

            // Check if adviser is already assigned to another section
            $stmt = $pdo->prepare('SELECT id, name FROM sections WHERE adviser_id = ? AND id != ? AND is_active = 1');
            $stmt->execute([$adviserId, $sectionId]);
            $existingAssignment = $stmt->fetch();
            if ($existingAssignment) {
                throw new \Exception('This adviser is already assigned to section: ' . $existingAssignment['name']);
            }

            // Remove current adviser from this section (if any)
            $stmt = $pdo->prepare('UPDATE sections SET adviser_id = NULL WHERE id = ?');
            $stmt->execute([$sectionId]);

            // Assign new adviser to section
            $stmt = $pdo->prepare('UPDATE sections SET adviser_id = ? WHERE id = ?');
            $stmt->execute([$adviserId, $sectionId]);

            // Update teacher record to mark as adviser
            $stmt = $pdo->prepare('UPDATE teachers SET is_adviser = 1 WHERE user_id = ?');
            $stmt->execute([$adviserId]);

            // Log the assignment
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                VALUES (:admin_id, "adviser_assigned", "section", :section_id, :details, :ip, :user_agent)
            ');
            $stmt->execute([
                'admin_id' => $user['id'],
                'section_id' => $sectionId,
                'details' => json_encode([
                    'section_name' => $section['name'],
                    'adviser_name' => $adviser['name'],
                    'adviser_id' => $adviserId
                ]),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $pdo->commit();

            header('Location: ' . \Helpers\Url::to('/admin/assign-advisers?success=adviser_assigned&section=' . urlencode($section['name']) . '&adviser=' . urlencode($adviser['name'])));

        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: ' . \Helpers\Url::to('/admin/assign-advisers?error=' . urlencode($e->getMessage())));
        }
    }

    /**
     * Remove adviser from section
     */
    public function removeAdviser(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'admin') {
            header('Location: ' . \Helpers\Url::to('/login'));
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            header('Location: ' . \Helpers\Url::to('/admin/assign-advisers?error=csrf_invalid'));
            return;
        }

        $sectionId = (int)($_POST['section_id'] ?? 0);

        if (!$sectionId) {
            header('Location: ' . \Helpers\Url::to('/admin/assign-advisers?error=missing_section'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $pdo->beginTransaction();

            // Get section and current adviser info
            $stmt = $pdo->prepare('
                SELECT s.id, s.name, s.adviser_id, u.name as adviser_name
                FROM sections s
                LEFT JOIN users u ON s.adviser_id = u.id
                WHERE s.id = ? AND s.is_active = 1
            ');
            $stmt->execute([$sectionId]);
            $section = $stmt->fetch();
            
            if (!$section) {
                throw new \Exception('Section not found.');
            }

            if (!$section['adviser_id']) {
                throw new \Exception('No adviser assigned to this section.');
            }

            // Remove adviser from section
            $stmt = $pdo->prepare('UPDATE sections SET adviser_id = NULL WHERE id = ?');
            $stmt->execute([$sectionId]);

            // Check if this adviser is assigned to any other sections
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM sections WHERE adviser_id = ? AND is_active = 1');
            $stmt->execute([$section['adviser_id']]);
            $otherAssignments = $stmt->fetch();

            // If no other assignments, remove adviser flag from teacher record
            if ($otherAssignments['count'] == 0) {
                $stmt = $pdo->prepare('UPDATE teachers SET is_adviser = 0 WHERE user_id = ?');
                $stmt->execute([$section['adviser_id']]);
            }

            // Log the removal
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                VALUES (:admin_id, "adviser_removed", "section", :section_id, :details, :ip, :user_agent)
            ');
            $stmt->execute([
                'admin_id' => $user['id'],
                'section_id' => $sectionId,
                'details' => json_encode([
                    'section_name' => $section['name'],
                    'adviser_name' => $section['adviser_name'],
                    'adviser_id' => $section['adviser_id']
                ]),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $pdo->commit();

            header('Location: ' . \Helpers\Url::to('/admin/assign-advisers?success=adviser_removed&section=' . urlencode($section['name']) . '&adviser=' . urlencode($section['adviser_name'])));

        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: ' . \Helpers\Url::to('/admin/assign-advisers?error=' . urlencode($e->getMessage())));
        }
    }

    /**
     * Display sections management page
     */
    public function sections(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            // Get all sections with student counts and capacity info
            $stmt = $pdo->prepare('
                SELECT 
                    s.id,
                    s.name,
                    s.grade_level,
                    s.room,
                    s.max_students,
                    s.school_year,
                    s.is_active,
                    s.created_at,
                    COUNT(st.id) as enrolled_students,
                    u.name as adviser_name,
                    u.email as adviser_email,
                    CASE 
                        WHEN COUNT(st.id) >= s.max_students THEN "full"
                        WHEN COUNT(st.id) >= s.max_students * 0.8 THEN "nearly_full"
                        ELSE "available"
                    END as status
                FROM sections s
                LEFT JOIN students st ON st.section_id = s.id
                LEFT JOIN users u ON s.adviser_id = u.id
                WHERE s.school_year = "2025-2026"
                GROUP BY s.id, s.name, s.grade_level, s.room, s.max_students, s.school_year, s.is_active, s.created_at, u.name, u.email
                ORDER BY s.grade_level, s.name
            ');
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get unassigned students count
            $stmt = $pdo->prepare('
                SELECT COUNT(*) as count
                FROM students s
                WHERE s.section_id IS NULL
            ');
            $stmt->execute();
            $unassignedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $this->view->render('admin/sections', [
                'title' => 'Section Management',
                'user' => $user,
                'activeNav' => 'sections',
                'sections' => $sections,
                'unassignedCount' => $unassignedCount,
                'csrf_token' => \Helpers\Csrf::generateToken(),
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load sections: ' . $e->getMessage());
        }
    }

    /**
     * Create a new section (POST)
     */
    public function createSection(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }
            \Helpers\ErrorHandler::forbidden('You need administrator privileges.');
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF token mismatch']);
                return;
            }
            http_response_code(419);
            header('Location: ' . \Helpers\Url::to('/admin/sections'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $name = trim((string)($_POST['name'] ?? ''));
            $gradeLevel = (int)($_POST['grade_level'] ?? 0);
            $room = trim((string)($_POST['room'] ?? ''));
            $maxStudents = (int)($_POST['max_students'] ?? 50);
            $schoolYear = trim((string)($_POST['school_year'] ?? '2025-2026'));
            $description = trim((string)($_POST['description'] ?? ''));

            if (empty($name) || $gradeLevel < 1 || $gradeLevel > 12) {
                throw new \Exception('Section name and valid grade level (1-12) are required.');
            }

            if ($maxStudents < 1 || $maxStudents > 100) {
                throw new \Exception('Maximum students must be between 1 and 100.');
            }

            // Check if section with same name and grade level exists
            $stmt = $pdo->prepare('
                SELECT id FROM sections 
                WHERE name = ? AND grade_level = ? AND school_year = ?
            ');
            $stmt->execute([$name, $gradeLevel, $schoolYear]);
            if ($stmt->fetch()) {
                throw new \Exception("A section with name '{$name}' for grade {$gradeLevel} already exists for {$schoolYear}.");
            }

            $stmt = $pdo->prepare('
                INSERT INTO sections (name, grade_level, room, max_students, school_year, description, is_active)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ');
            $stmt->execute([$name, $gradeLevel, $room, $maxStudents, $schoolYear, $description]);
            $sectionId = $pdo->lastInsertId();

            // Log the action
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                VALUES (?, "section_created", "section", ?, ?, ?, ?)
            ');
            $stmt->execute([
                $user['id'],
                $sectionId,
                json_encode(['section_name' => $name, 'grade_level' => $gradeLevel]),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Section created successfully',
                    'section_id' => $sectionId
                ]);
                return;
            }

            header('Location: ' . \Helpers\Url::to('/admin/sections?success=section_created'));

        } catch (\Exception $e) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/sections?error=' . urlencode($e->getMessage())));
        }
    }

    /**
     * Update section capacity or details (POST)
     */
    public function updateSection(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }
            \Helpers\ErrorHandler::forbidden('You need administrator privileges.');
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF token mismatch']);
                return;
            }
            http_response_code(419);
            header('Location: ' . \Helpers\Url::to('/admin/sections'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $sectionId = (int)($_POST['section_id'] ?? 0);
            if (!$sectionId) {
                throw new \Exception('Invalid section ID.');
            }

            // Get current enrolled students count
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM students WHERE section_id = ?');
            $stmt->execute([$sectionId]);
            $enrolledCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Build update query based on provided fields
            $updateFields = [];
            $updateValues = [];

            if (isset($_POST['max_students'])) {
                $maxStudents = (int)$_POST['max_students'];
                if ($maxStudents < $enrolledCount) {
                    throw new \Exception("Cannot set maximum students to {$maxStudents}. Section already has {$enrolledCount} enrolled students.");
                }
                if ($maxStudents < 1 || $maxStudents > 100) {
                    throw new \Exception('Maximum students must be between 1 and 100.');
                }
                $updateFields[] = 'max_students = ?';
                $updateValues[] = $maxStudents;
            }

            if (isset($_POST['room'])) {
                $updateFields[] = 'room = ?';
                $updateValues[] = trim((string)$_POST['room']);
            }

            if (isset($_POST['description'])) {
                $updateFields[] = 'description = ?';
                $updateValues[] = trim((string)$_POST['description']);
            }

            if (empty($updateFields)) {
                throw new \Exception('No fields to update.');
            }

            $updateValues[] = $sectionId;
            $stmt = $pdo->prepare('UPDATE sections SET ' . implode(', ', $updateFields) . ' WHERE id = ?');
            $stmt->execute($updateValues);

            // Log the action
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                VALUES (?, "section_updated", "section", ?, ?, ?, ?)
            ');
            $stmt->execute([
                $user['id'],
                $sectionId,
                json_encode($_POST),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Section updated successfully'
                ]);
                return;
            }

            header('Location: ' . \Helpers\Url::to('/admin/sections?success=section_updated'));

        } catch (\Exception $e) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/sections?error=' . urlencode($e->getMessage())));
        }
    }

    /**
     * Assign student to section (POST)
     */
    public function assignStudentToSection(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }
            \Helpers\ErrorHandler::forbidden('You need administrator privileges.');
            return;
        }

        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF token mismatch']);
                return;
            }
            http_response_code(419);
            header('Location: ' . \Helpers\Url::to('/admin/sections'));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $studentId = (int)($_POST['student_id'] ?? 0);
            $sectionId = (int)($_POST['section_id'] ?? 0);

            if (!$studentId || !$sectionId) {
                throw new \Exception('Student ID and Section ID are required.');
            }

            // Check if section exists and get capacity
            $stmt = $pdo->prepare('
                SELECT s.id, s.name, s.max_students, COUNT(st.id) as enrolled_students
                FROM sections s
                LEFT JOIN students st ON st.section_id = s.id
                WHERE s.id = ? AND s.is_active = 1
                GROUP BY s.id, s.name, s.max_students
            ');
            $stmt->execute([$sectionId]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$section) {
                throw new \Exception('Section not found or inactive.');
            }

            // Check if section is full
            if ($section['enrolled_students'] >= $section['max_students']) {
                throw new \Exception("Section '{$section['name']}' is full ({$section['enrolled_students']}/{$section['max_students']}). Please increase capacity or choose another section.");
            }

            // Check if student exists
            $stmt = $pdo->prepare('SELECT id, user_id FROM students WHERE id = ?');
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                throw new \Exception('Student not found.');
            }

            // Update student's section
            $stmt = $pdo->prepare('UPDATE students SET section_id = ? WHERE id = ?');
            $stmt->execute([$sectionId, $studentId]);

            // Remove enrollments for other sections
            $stmt = $pdo->prepare('
                DELETE sc FROM student_classes sc
                JOIN classes c ON sc.class_id = c.id
                WHERE sc.student_id = ? AND c.section_id <> ?
            ');
            $stmt->execute([$studentId, $sectionId]);

            // Enroll student in all active classes for the section
            $stmt = $pdo->prepare('SELECT id FROM classes WHERE section_id = ? AND is_active = 1');
            $stmt->execute([$sectionId]);
            $sectionClasses = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            if (!empty($sectionClasses)) {
                $enrollStmt = $pdo->prepare('
                    INSERT INTO student_classes (student_id, class_id, status)
                    VALUES (?, ?, "enrolled")
                    ON DUPLICATE KEY UPDATE status = VALUES(status)
                ');

                foreach ($sectionClasses as $classId) {
                    $enrollStmt->execute([$studentId, (int)$classId]);
                }
            }

            // Log the action
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                VALUES (?, "student_assigned_to_section", "student", ?, ?, ?, ?)
            ');
            $stmt->execute([
                $user['id'],
                $studentId,
                json_encode(['section_id' => $sectionId, 'section_name' => $section['name']]),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Student assigned to section successfully'
                ]);
                return;
            }

            header('Location: ' . \Helpers\Url::to('/admin/sections?success=student_assigned'));

        } catch (\Exception $e) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                return;
            }
            header('Location: ' . \Helpers\Url::to('/admin/sections?error=' . urlencode($e->getMessage())));
        }
    }

    /**
     * Get section details via API (GET)
     */
    public function getSectionDetails(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $sectionId = (int)($_GET['section_id'] ?? 0);
        if (!$sectionId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Section ID required']);
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $stmt = $pdo->prepare('
                SELECT 
                    s.id,
                    s.name,
                    s.grade_level,
                    s.room,
                    s.max_students,
                    s.school_year,
                    s.description,
                    s.is_active,
                    COUNT(st.id) as enrolled_students,
                    (s.max_students - COUNT(st.id)) as available_slots,
                    u.name as adviser_name
                FROM sections s
                LEFT JOIN students st ON st.section_id = s.id
                LEFT JOIN users u ON s.adviser_id = u.id
                WHERE s.id = ?
                GROUP BY s.id, s.name, s.grade_level, s.room, s.max_students, s.school_year, s.description, s.is_active, u.name
            ');
            $stmt->execute([$sectionId]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$section) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Section not found']);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'section' => $section
            ]);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Get unassigned students via API (GET)
     */
    public function getUnassignedStudents(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $search = trim((string)($_GET['search'] ?? ''));
            
            $query = '
                SELECT 
                    s.id,
                    s.lrn,
                    s.grade_level,
                    u.name,
                    u.email,
                    u.status
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.section_id IS NULL
            ';

            $params = [];
            if (!empty($search)) {
                $query .= ' AND (u.name LIKE ? OR s.lrn LIKE ? OR u.email LIKE ?)';
                $searchParam = "%{$search}%";
                $params = [$searchParam, $searchParam, $searchParam];
            }

            $query .= ' ORDER BY u.name LIMIT 50';

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'students' => $students
            ]);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}


