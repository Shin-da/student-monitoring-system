<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use Helpers\Csrf;
use Helpers\Notification;
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
                    // Teacher profiles are created and kept in sync by centralized helpers
                    // (create_user.php / ensureTeacherProfiles). Do not insert here to avoid
                    // duplicate teacher rows or conflicting IDs.
                    break;

                case 'adviser':
                    // Same as teacher: avoid creating duplicate teacher rows here.
                    // Adviser-specific linkage can be handled elsewhere when a section
                    // adviser is explicitly assigned.
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

            // Flash message for admin
            Notification::success('User approved successfully');

            // Notify the approved user (persistent notification)
            Notification::create(
                recipientIds: $userId,
                type: 'success',
                category: 'approval_request',
                title: 'Account Approved',
                message: "Your registration has been approved! You can now log in to your account.",
                options: [
                    'link' => '/login',
                    'created_by' => $user['id'],
                    'priority' => 'high'
                ]
            );

            // Notify all admins (except the one who approved) - optional, can be removed if too noisy
            // Get admin user IDs excluding current admin
            $stmt = $pdo->prepare('SELECT id FROM users WHERE role = "admin" AND id != :admin_id AND status = "active"');
            $stmt->execute(['admin_id' => $user['id']]);
            $otherAdminIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($otherAdminIds)) {
                Notification::create(
                    recipientIds: $otherAdminIds,
                    type: 'info',
                    category: 'user_management',
                    title: 'User Approved',
                    message: "User {$userToApprove['name']} ({$userToApprove['email']}) has been approved by {$user['name']}.",
                    options: [
                        'link' => '/admin/users',
                        'created_by' => $user['id'],
                        'metadata' => ['approved_user_id' => $userId, 'approved_role' => $role]
                    ]
                );
            }

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

            // Flash message for admin
            Notification::success('User rejected successfully');

            // Notify the rejected user (if user still exists and has email access)
            // Note: User is deleted, but we can still create notification before deletion
            // In this case, we'll notify admins only since user is being deleted
            
            // Notify all admins
            Notification::createByRole(
                roles: 'admin',
                type: 'info',
                category: 'user_management',
                title: 'User Registration Rejected',
                message: "Registration for {$userToReject['name']} ({$userToReject['email']}) has been rejected. Reason: {$rejectionReason}",
                options: [
                    'link' => '/admin/users',
                    'created_by' => $user['id'],
                    'metadata' => [
                        'rejected_user_email' => $userToReject['email'],
                        'rejection_reason' => $rejectionReason
                    ]
                ]
            );

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

        // Get user info before activating
        $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $userToActivate = $stmt->fetch(PDO::FETCH_ASSOC);

        // Activate user
        $stmt = $pdo->prepare('UPDATE users SET status = "active" WHERE id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        // Flash message for admin
        Notification::success('User activated successfully');

        // Notify the activated user
        if ($userToActivate) {
            Notification::create(
                recipientIds: $userId,
                type: 'success',
                category: 'user_management',
                title: 'Account Reactivated',
                message: 'Your account has been reactivated. You can now log in again.',
                options: [
                    'link' => '/login',
                    'created_by' => $user['id'],
                    'priority' => 'high'
                ]
            );
        }

        // Notify all admins
        if ($userToActivate) {
            Notification::createByRole(
                roles: 'admin',
                type: 'info',
                category: 'user_management',
                title: 'User Reactivated',
                message: "User {$userToActivate['name']} ({$userToActivate['email']}) has been reactivated by {$user['name']}.",
                options: [
                    'link' => '/admin/users',
                    'created_by' => $user['id']
                ]
            );
        }

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

    public function createUser(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->view->render('admin/create-user', [
                'title' => 'Create New User',
                'user' => $user,
                'activeNav' => 'users',
                'csrf_token' => Csrf::generateToken(),
            ], 'layouts/dashboard');
            return;
        }

        // POST request - form submission is handled via AJAX to /api/create_user.php
        // This is just a fallback in case JavaScript is disabled
        if (!Csrf::check($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            header('Location: ' . \Helpers\Url::to('/admin/create-user'));
            return;
        }

        // Redirect back to form (AJAX handles the actual submission)
        header('Location: ' . \Helpers\Url::to('/admin/create-user'));
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
                    SELECT 
                        u.id, 
                        u.name, 
                        s.lrn, 
                        s.grade_level,
                        s.guardian_name,
                        s.guardian_contact,
                        s.guardian_relationship,
                        sec.name as section
                    FROM users u 
                    LEFT JOIN students s ON s.user_id = u.id 
                    LEFT JOIN sections sec ON s.section_id = sec.id
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
            exit;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $contact = trim((string)($_POST['contact'] ?? ''));
        $studentId = (int)($_POST['student_id'] ?? 0);
        $relationship = trim((string)($_POST['relationship'] ?? 'guardian'));
        $syncToStudent = isset($_POST['sync_to_student']) && $_POST['sync_to_student'] === '1';

        // Get students list for form
        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);
        $stmt = $pdo->prepare('
            SELECT u.id, u.name, s.lrn, s.grade_level 
            FROM users u 
            LEFT JOIN students s ON s.user_id = u.id 
            WHERE u.role = "student" AND u.status = "active" 
            ORDER BY u.name
        ');
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Validate inputs
        $errors = [];
        if (!\Helpers\Validator::required($name)) {
            $errors[] = 'Name is required';
        }
        if (!\Helpers\Validator::email($email)) {
            $errors[] = 'Valid email is required';
        }
        if (!\Helpers\Validator::minLength($password, 8)) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (!$studentId) {
            $errors[] = 'Please select a student';
        }
        if (!in_array($relationship, ['father', 'mother', 'guardian', 'grandparent', 'sibling', 'other'])) {
            $errors[] = 'Please select a valid relationship';
        }
        
        if (!empty($errors)) {
            $this->view->render('admin/create-parent', [
                'title' => 'Create Parent Account',
                'user' => $user,
                'activeNav' => 'users',
                'students' => $students,
                'error' => implode('. ', $errors) . '.'
            ], 'layouts/dashboard');
            return;
        }

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

        // Verify student user exists and get student record
        $stmt = $pdo->prepare('
            SELECT u.id as user_id, s.id as student_id, s.guardian_name, s.guardian_contact, s.guardian_relationship
            FROM users u 
            JOIN students s ON s.user_id = u.id
            WHERE u.id = :id AND u.role = "student" 
            LIMIT 1
        ');
        $stmt->execute(['id' => $studentId]);
        $studentData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$studentData) {
            $this->view->render('admin/create-parent', [
                'title' => 'Create Parent Account',
                'user' => $user,
                'activeNav' => 'users',
                'students' => $students ?? [],
                'error' => 'Selected student does not exist or has no student record.'
            ], 'layouts/dashboard');
            return;
        }
        
        $studentRecordId = (int)$studentData['student_id'];
        $hasExistingGuardian = !empty($studentData['guardian_name']) || !empty($studentData['guardian_contact']);
        
        // Check if a parent account with this relationship already exists for this student
        $checkExistingParent = $pdo->prepare('
            SELECT id, name, email 
            FROM users 
            WHERE linked_student_user_id = :student_user_id 
            AND role = "parent" 
            AND parent_relationship = :relationship
            LIMIT 1
        ');
        $checkExistingParent->execute([
            'student_user_id' => $studentId,
            'relationship' => $relationship
        ]);
        $existingParent = $checkExistingParent->fetch(PDO::FETCH_ASSOC);
        
        if ($existingParent) {
            $this->view->render('admin/create-parent', [
                'title' => 'Create Parent Account',
                'user' => $user,
                'activeNav' => 'users',
                'students' => $students ?? [],
                'error' => "A parent account with relationship '{$relationship}' already exists for this student: {$existingParent['name']} ({$existingParent['email']}). You can link multiple parents, but each relationship type should be unique."
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
            
            // Verify the update worked
            if ($update->rowCount() === 0) {
                throw new \Exception('Failed to link parent to student. Parent user ID: ' . $parentUserId);
            }

            // Sync parent information to student's guardian fields
            // IMPORTANT: Only sync if guardian fields are completely empty (first parent only)
            // This prevents overwriting existing guardian info when multiple parents are added
            if ($syncToStudent && !$hasExistingGuardian) {
                // Map relationship values (parent relationship might have more options)
                $guardianRelationship = $relationship;
                if (!in_array($relationship, ['father', 'mother', 'guardian', 'grandparent', 'sibling', 'other'])) {
                    $guardianRelationship = 'guardian';
                }
                
                // Only update if ALL guardian fields are empty
                $updateStudent = $pdo->prepare('
                    UPDATE students 
                    SET guardian_name = :guardian_name,
                        guardian_contact = COALESCE(:guardian_contact, guardian_contact),
                        guardian_relationship = :guardian_relationship
                    WHERE id = :student_id
                    AND (guardian_name IS NULL OR guardian_name = "")
                    AND (guardian_contact IS NULL OR guardian_contact = "")
                ');
                $updateStudent->execute([
                    'guardian_name' => $name,
                    'guardian_contact' => !empty($contact) ? $contact : null,
                    'guardian_relationship' => $guardianRelationship,
                    'student_id' => $studentRecordId,
                ]);
                
                if ($updateStudent->rowCount() > 0) {
                    error_log("Student guardian info synced (first parent): StudentID={$studentRecordId}, Guardian={$name}");
                } else {
                    error_log("Guardian info NOT synced - fields already populated: StudentID={$studentRecordId}");
                }
            } elseif ($syncToStudent && $hasExistingGuardian) {
                // Log that sync was requested but skipped due to existing guardian info
                error_log("Sync requested but skipped - guardian info already exists: StudentID={$studentRecordId}, Existing={$studentData['guardian_name']}, New={$name}");
            }

            $pdo->commit();
            
            // Log success
            error_log("Parent created successfully: ID={$parentUserId}, Email={$email}, StudentUserID={$studentId}, StudentRecordID={$studentRecordId}, SyncedToStudent=" . ($shouldUpdateStudent ? 'Yes' : 'No'));
            
            // Redirect to users page with success message
            $redirectUrl = \Helpers\Url::to('/admin/users?success=parent_created');
            header('Location: ' . $redirectUrl);
            exit;

        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMsg = 'Parent creation failed: ' . $e->getMessage();
            error_log('Parent creation PDO error: ' . $errorMsg . ' | Code: ' . $e->getCode());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            $this->view->render('admin/create-parent', [
                'title' => 'Create Parent Account',
                'user' => $user,
                'activeNav' => 'users',
                'students' => $students ?? [],
                'error' => 'Database error: ' . htmlspecialchars($e->getMessage()) . ' (Error code: ' . $e->getCode() . ')'
            ], 'layouts/dashboard');
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Parent creation error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            $this->view->render('admin/create-parent', [
                'title' => 'Create Parent Account',
                'user' => $user,
                'activeNav' => 'users',
                'students' => $students ?? [],
                'error' => 'Error: ' . htmlspecialchars($e->getMessage())
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
                    c.room as room,
                    c.semester,
                    c.school_year,
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

            // Ensure every teacher/adviser user has a matching teacher profile
            $this->ensureTeacherProfiles($pdo);

            // Get all teachers for dropdown
            $stmt = $pdo->prepare('
                SELECT 
                    t.id, 
                    t.user_id,
                    u.name, 
                    u.email, 
                    COALESCE(NULLIF(t.department, \'\'), \'General Education\') AS department
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

            // Validate required fields (treat "0" as a value; only null/empty-string are missing)
            $requiredFields = ['section_id', 'subject_id', 'teacher_id', 'schedule', 'room'];
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $_POST)) {
                    throw new \Exception("Missing required field: {$field}");
                }
                $value = is_string($_POST[$field]) ? trim($_POST[$field]) : $_POST[$field];
                if ($value === '' || $value === null) {
                    throw new \Exception("Missing required field: {$field}");
                }
            }

            $sectionId = (int)$_POST['section_id'];
            $subjectId = (int)$_POST['subject_id'];
            $teacherId = (int)$_POST['teacher_id'];
            $schedule = $_POST['schedule'];
            $room = $_POST['room'];
            $schoolYear = $_POST['school_year'] ?? '2025-2026';
            $semester = $_POST['semester'] ?? '1st';

            // Parse schedule to check for conflicts
            $scheduleData = $this->parseSchedule($schedule);
            if (!$scheduleData) {
                throw new \Exception('Invalid schedule format. Use format like "M 8:00 AM-9:00 AM" or "TH 10:00 AM-11:00 AM"');
            }

            // Check for exact duplicates and conflicts across ALL teachers (not just selected teacher)
            $uniqueDays = array_values(array_unique($scheduleData['days']));
            foreach ($uniqueDays as $day) {
                // Check if ANY teacher already has this exact schedule (same day, same time)
                $duplicateCheck = $pdo->prepare('
                    SELECT ts.id, ts.teacher_id, ts.class_id, u.name as teacher_name
                    FROM teacher_schedules ts
                    LEFT JOIN teachers t ON ts.teacher_id = t.id
                    LEFT JOIN users u ON t.user_id = u.id
                    WHERE ts.day_of_week = ? 
                    AND ts.start_time = ? 
                    AND ts.end_time = ?
                    LIMIT 1
                ');
                $duplicateCheck->execute([$day, $scheduleData['start_time'], $scheduleData['end_time']]);
                $existing = $duplicateCheck->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    $existingTeacherName = $existing['teacher_name'] ?? 'Another teacher';
                    throw new \Exception("Schedule conflict: {$existingTeacherName} already has a class scheduled on {$day} from {$scheduleData['start_ampm']} to {$scheduleData['end_ampm']}. Please choose a different time or day.");
                }
                
                // Check if ANY teacher has overlapping time on this day
                $overlapCheck = $pdo->prepare('
                    SELECT ts.id, ts.teacher_id, u.name as teacher_name, ts.start_time, ts.end_time
                    FROM teacher_schedules ts
                    LEFT JOIN teachers t ON ts.teacher_id = t.id
                    LEFT JOIN users u ON t.user_id = u.id
                    WHERE ts.day_of_week = ? 
                    AND (
                        -- Overlapping: new start < existing end AND new end > existing start
                        (ts.start_time < ? AND ts.end_time > ?)
                    )
                    LIMIT 1
                ');
                $overlapCheck->execute([$day, $scheduleData['end_time'], $scheduleData['start_time']]);
                $overlapping = $overlapCheck->fetch(PDO::FETCH_ASSOC);
                
                if ($overlapping) {
                    $overlapTeacherName = $overlapping['teacher_name'] ?? 'Another teacher';
                    $overlapStart = date('g:i A', strtotime($overlapping['start_time']));
                    $overlapEnd = date('g:i A', strtotime($overlapping['end_time']));
                    throw new \Exception("Schedule conflict: {$overlapTeacherName} already has a class scheduled on {$day} from {$overlapStart} to {$overlapEnd}, which overlaps with your selected time ({$scheduleData['start_ampm']} to {$scheduleData['end_ampm']}). Please choose a different time.");
                }
            }
            
            // Check for overlapping time conflicts
            $conflicts = $this->checkScheduleConflicts($pdo, $teacherId, $scheduleData['days'], $scheduleData['start_time'], $scheduleData['end_time']);
            if (!empty($conflicts)) {
                // Get teacher and subject info for notification
                $stmt = $pdo->prepare('
                    SELECT u.name as teacher_name, sub.name as subject_name, sec.name as section_name
                    FROM users u
                    JOIN teachers t ON u.id = t.user_id
                    CROSS JOIN subjects sub ON sub.id = ?
                    CROSS JOIN sections sec ON sec.id = ?
                    WHERE t.id = ?
                    LIMIT 1
                ');
                $stmt->execute([$subjectId, $sectionId, $teacherId]);
                $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Notify admin about conflict
                Notification::create(
                    recipientIds: $user['id'],
                    type: 'error',
                    category: 'schedule_change',
                    title: 'Schedule Conflict Detected',
                    message: "Cannot create class: {$classInfo['teacher_name']} already has a conflicting class. Please choose a different time or teacher.",
                    options: [
                        'priority' => 'high',
                        'link' => '/admin/create-class',
                        'metadata' => ['conflicts' => $conflicts, 'teacher_id' => $teacherId]
                    ]
                );
                
                throw new \Exception('Schedule conflict detected. Teacher already has classes during this time.');
            }

            // Check if a class with the same section, subject, semester, and school year already exists
            $stmt = $pdo->prepare('
                SELECT c.id, c.schedule, c.room,
                       sub.name as subject_name, sub.code as subject_code,
                       sec.name as section_name, sec.grade_level,
                       u.name as teacher_name
                FROM classes c
                JOIN subjects sub ON c.subject_id = sub.id
                JOIN sections sec ON c.section_id = sec.id
                JOIN teachers t ON c.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
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
                $errorDetails = "This class already exists:\n\n";
                $errorDetails .= "📚 Subject: {$existingClass['subject_name']} ({$existingClass['subject_code']})\n";
                $errorDetails .= "🏫 Section: {$existingClass['section_name']} (Grade {$existingClass['grade_level']})\n";
                $errorDetails .= "📅 Semester: {$semester} Semester, {$schoolYear}\n";
                $errorDetails .= "👨‍🏫 Current Teacher: {$existingClass['teacher_name']}\n";
                $errorDetails .= "🕐 Schedule: {$existingClass['schedule']}\n";
                $errorDetails .= "🚪 Room: {$existingClass['room']}\n\n";
                $errorDetails .= "💡 You can either:\n";
                $errorDetails .= "• Edit the existing class (Class ID: {$existingClass['id']})\n";
                $errorDetails .= "• Choose a different subject for this section\n";
                $errorDetails .= "• Choose a different section for this subject\n";
                $errorDetails .= "• Create this class for a different semester";
                
                throw new \Exception($errorDetails);
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

            // Sync section adviser/teacher linkage
            $this->linkTeacherToSection($pdo, $sectionId, $teacherId);

            // Get class details for notifications
            $stmt = $pdo->prepare('
                SELECT sub.name as subject_name, sec.name as section_name, u.name as teacher_name
                FROM classes c
                JOIN subjects sub ON c.subject_id = sub.id
                JOIN sections sec ON c.section_id = sec.id
                JOIN teachers t ON c.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                WHERE c.id = ?
            ');
            $stmt->execute([$classId]);
            $classDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            $pdo->commit();

            // Flash message for admin
            Notification::success('Class created successfully');

            // Notify teacher
            $stmt = $pdo->prepare('SELECT user_id FROM teachers WHERE id = ?');
            $stmt->execute([$teacherId]);
            $teacherUserId = $stmt->fetchColumn();
            
            if ($teacherUserId && $classDetails) {
                Notification::create(
                    recipientIds: (int)$teacherUserId,
                    type: 'schedule',
                    category: 'class_created',
                    title: 'New Class Assignment',
                    message: "You have been assigned to teach {$classDetails['subject_name']} for {$classDetails['section_name']}. Schedule: {$schedule}",
                    options: [
                        'link' => "/teacher/classes?class={$classId}",
                        'metadata' => ['class_id' => $classId, 'subject' => $classDetails['subject_name']],
                        'created_by' => $user['id']
                    ]
                );
            }

            // Notify section members (students and adviser)
            if ($classDetails) {
                Notification::createForSection(
                    sectionId: $sectionId,
                    type: 'schedule',
                    category: 'class_created',
                    title: 'New Class Added',
                    message: "New class: {$classDetails['subject_name']} with {$classDetails['teacher_name']}. Schedule: {$schedule}, Room: {$room}",
                    options: ['link' => '/student/schedule', 'created_by' => $user['id']]
                );
            }

            header('Location: ' . \Helpers\Url::to('/admin/classes?success=class_created'));
            exit();

        } catch (\PDOException $e) {
            $pdo->rollBack();
            
            // Handle unique constraint violations
            if ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                // Try to get the existing class details for a better error message
                try {
                    $stmt = $pdo->prepare('
                        SELECT c.id, c.schedule, c.room,
                               sub.name as subject_name, sub.code as subject_code,
                               sec.name as section_name, sec.grade_level,
                               u.name as teacher_name
                        FROM classes c
                        JOIN subjects sub ON c.subject_id = sub.id
                        JOIN sections sec ON c.section_id = sec.id
                        JOIN teachers t ON c.teacher_id = t.id
                        JOIN users u ON t.user_id = u.id
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
                        $errorMessage = "❌ Duplicate Class Detected!\n\n";
                        $errorMessage .= "This class already exists in the system:\n\n";
                        $errorMessage .= "📚 {$existingClass['subject_name']} ({$existingClass['subject_code']})\n";
                        $errorMessage .= "🏫 {$existingClass['section_name']} (Grade {$existingClass['grade_level']})\n";
                        $errorMessage .= "📅 {$semester} Semester, {$schoolYear}\n";
                        $errorMessage .= "👨‍🏫 Teacher: {$existingClass['teacher_name']}\n";
                        $errorMessage .= "🕐 Schedule: {$existingClass['schedule']} | Room: {$existingClass['room']}\n\n";
                        $errorMessage .= "💡 Suggestions:\n";
                        $errorMessage .= "• Edit the existing class if you need to change details\n";
                        $errorMessage .= "• Choose a different subject, section, or semester\n";
                        $errorMessage .= "• Check if you meant to assign a different teacher to an existing time slot";
                    } else {
                        $errorMessage = "❌ Cannot create class: A class with this exact combination (section, subject, semester, and school year) already exists.\n\n";
                        $errorMessage .= "Please ensure each class has a unique combination.";
                    }
                } catch (\Exception $detailException) {
                    // Fallback to generic message if we can't fetch details
                    $errorMessage = "❌ Cannot create class: A class with this combination (section, subject, semester, and school year) already exists.\n\n";
                    $errorMessage .= "Please check the existing classes and try again.";
                }
                
                header('Location: ' . \Helpers\Url::to('/admin/classes?error=' . urlencode($errorMessage)));
            } else {
                // Other database errors
                $errorMessage = "❌ Database Error: Unable to create class.\n\n";
                $errorMessage .= "Technical details: " . $e->getMessage() . "\n\n";
                $errorMessage .= "Please contact your system administrator if this persists.";
                header('Location: ' . \Helpers\Url::to('/admin/classes?error=' . urlencode($errorMessage)));
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
        // Remove duplicate days
        $uniqueDays = array_values(array_unique($days));
        $placeholders = str_repeat('?,', count($uniqueDays) - 1) . '?';

        // Check for:
        // 1. Exact duplicates (same day, same time)
        // 2. Overlapping times (new start < existing end AND new end > existing start)
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                ts.id,
                ts.day_of_week,
                ts.start_time,
                ts.end_time,
                ts.class_id,
                c.schedule,
                sec.name as section_name,
                sub.name as subject_name
            FROM teacher_schedules ts
            LEFT JOIN classes c ON ts.class_id = c.id
            LEFT JOIN sections sec ON c.section_id = sec.id
            LEFT JOIN subjects sub ON c.subject_id = sub.id
            WHERE ts.teacher_id = ? 
            AND ts.day_of_week IN ($placeholders)
            AND (
                -- Exact duplicate check
                (ts.start_time = ? AND ts.end_time = ?)
                OR
                -- Overlapping time check: schedules overlap if new start < existing end AND new end > existing start
                (ts.start_time < ? AND ts.end_time > ?)
            )
            ORDER BY ts.day_of_week, ts.start_time
        ");
        
        // Parameters: teacherId, days..., startTime (for exact), endTime (for exact), endTime (for overlap), startTime (for overlap)
        $params = array_merge([$teacherId], $uniqueDays, [$startTime, $endTime, $endTime, $startTime]);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function createTeacherSchedules($pdo, $teacherId, $classId, $days, $startTime, $endTime): void
    {
        // Remove duplicate days to prevent inserting the same schedule multiple times
        $uniqueDays = array_values(array_unique($days));
        
        // Use INSERT IGNORE to prevent duplicate key errors (handles unique constraint gracefully)
        // The unique constraint is: (teacher_id, day_of_week, start_time, end_time)
        $stmt = $pdo->prepare('
            INSERT IGNORE INTO teacher_schedules (teacher_id, day_of_week, start_time, end_time, class_id)
            VALUES (?, ?, ?, ?, ?)
        ');

        foreach ($uniqueDays as $day) {
            // Check for exact duplicate before inserting
            $checkStmt = $pdo->prepare('
                SELECT id FROM teacher_schedules 
                WHERE teacher_id = ? 
                AND day_of_week = ? 
                AND start_time = ? 
                AND end_time = ?
                LIMIT 1
            ');
            $checkStmt->execute([$teacherId, $day, $startTime, $endTime]);
            
            if ($checkStmt->fetch()) {
                // Exact duplicate exists, skip this day
                continue;
            }
            
            // Insert the schedule
            $stmt->execute([$teacherId, $day, $startTime, $endTime, $classId]);
        }
    }

    private function linkTeacherToSection(PDO $pdo, int $sectionId, int $teacherId): void
    {
        try {
            $stmt = $pdo->prepare('SELECT user_id FROM teachers WHERE id = ? LIMIT 1');
            $stmt->execute([$teacherId]);
            $teacherUserId = (int)$stmt->fetchColumn();

            if (!$teacherUserId) {
                return;
            }

            $stmt = $pdo->prepare('
                UPDATE sections 
                SET adviser_id = :adviser_id 
                WHERE id = :section_id 
                  AND (adviser_id IS NULL OR adviser_id = :adviser_id)
            ');
            $stmt->execute([
                'adviser_id' => $teacherUserId,
                'section_id' => $sectionId,
            ]);

            $stmt = $pdo->prepare('UPDATE teachers SET is_adviser = 1 WHERE id = :teacher_id');
            $stmt->execute(['teacher_id' => $teacherId]);
        } catch (\Throwable $e) {
            // Keep class creation resilient even if sync fails
        }
    }

    private function ensureTeacherProfiles(PDO $pdo): void
    {
        try {
            $columns = $this->getTableColumns($pdo, 'teachers');
            if (empty($columns)) {
                return;
            }

            $insertColumns = ['user_id'];
            $selectColumns = ['u.id'];
            $updateAssignments = [];

            if (isset($columns['teacher_name'])) {
                $insertColumns[] = 'teacher_name';
                $selectColumns[] = 'u.name';
                $updateAssignments[] = 'teacher_name = VALUES(teacher_name)';
            }

            $insertColumns[] = 'is_adviser';
            $selectColumns[] = '0';
            $updateAssignments[] = 'is_adviser = GREATEST(is_adviser, VALUES(is_adviser))';

            if (isset($columns['created_at'])) {
                $insertColumns[] = 'created_at';
                $selectColumns[] = 'NOW()';
            }

            if (isset($columns['updated_at'])) {
                $insertColumns[] = 'updated_at';
                $selectColumns[] = 'NOW()';
                $updateAssignments[] = 'updated_at = NOW()';
            }

            $sql = sprintf(
                'INSERT INTO teachers (%s)
                 SELECT %s
                 FROM users u
                 LEFT JOIN teachers t ON t.user_id = u.id
                 WHERE t.user_id IS NULL
                   AND u.role IN ("teacher","adviser")
                   AND u.status = "active"
                 ON DUPLICATE KEY UPDATE %s',
                implode(', ', $insertColumns),
                implode(', ', $selectColumns),
                implode(', ', $updateAssignments)
            );

            $pdo->exec($sql);
        } catch (\Throwable $e) {
            // Non-fatal: fallback data still works
        }
    }

    private function getTableColumns(PDO $pdo, string $table): array
    {
        $safeName = preg_replace('/[^a-z0-9_]/i', '', $table);
        if ($safeName === '') {
            return [];
        }

        $stmt = $pdo->query(sprintf('SHOW COLUMNS FROM `%s`', $safeName));
        $columns = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[strtolower($column['Field'])] = $column;
        }

        return $columns;
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

        // Validate required fields with friendly names
        $requiredFields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email Address',
            'password' => 'Password',
            'grade_level' => 'Grade Level',
            'section_id' => 'Section'
        ];
        
        foreach ($requiredFields as $field => $fieldName) {
            if (empty($_POST[$field])) {
                $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
                $pdo = Database::connection($config['database']);
                $stmt = $pdo->query('SELECT id, name, grade_level, room, max_students, 
                    (SELECT COUNT(*) FROM students WHERE section_id = sections.id) as enrolled_students,
                    (max_students - (SELECT COUNT(*) FROM students WHERE section_id = sections.id)) as available_slots,
                    CASE 
                        WHEN (SELECT COUNT(*) FROM students WHERE section_id = sections.id) >= max_students THEN "full"
                        WHEN (SELECT COUNT(*) FROM students WHERE section_id = sections.id) >= max_students * 0.9 THEN "nearly_full"
                        ELSE "available"
                    END as status
                    FROM sections WHERE is_active = 1 ORDER BY grade_level, name');
                $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $this->view->render('admin/create-student', [
                    'title' => 'Register New Student',
                    'user' => $user,
                    'activeNav' => 'users',
                    'sections' => $sections,
                    'form_data' => $_POST,
                    'csrf_token' => Csrf::generateToken(),
                    'error' => "❌ {$fieldName} is required. Please fill in this field."
                ], 'layouts/dashboard');
                return;
            }
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $pdo->beginTransaction();

            // Check if email already exists
            $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $_POST['email']]);
            $existingUser = $stmt->fetch();
            if ($existingUser) {
                throw new \Exception("❌ Email address '{$_POST['email']}' is already registered in the system. Please use a different email address.");
            }

            // Validate and check if LRN already exists (if provided)
            if (!empty($_POST['lrn'])) {
                $lrn = trim($_POST['lrn']);
                
                // Validate LRN format (must be exactly 12 digits)
                if (!preg_match('/^\d{12}$/', $lrn)) {
                    throw new \Exception('❌ Invalid LRN format. LRN must be exactly 12 digits (e.g., 108423080569).');
                }
                
                $stmt = $pdo->prepare('SELECT id, first_name, last_name FROM students WHERE lrn = :lrn LIMIT 1');
                $stmt->execute(['lrn' => $lrn]);
                $existingStudent = $stmt->fetch();
                if ($existingStudent) {
                    throw new \Exception("❌ LRN '{$lrn}' is already assigned to {$existingStudent['first_name']} {$existingStudent['last_name']}. Please use a different LRN or leave empty to auto-generate.");
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
                    throw new \Exception('❌ Selected section not found or is no longer active. Please choose a different section.');
                }
                
                $enrolled = (int)$section['enrolled_students'];
                $maxStudents = (int)$section['max_students'];
                
                if ($enrolled >= $maxStudents) {
                    throw new \Exception("❌ Section '{$section['name']}' is full ({$enrolled}/{$maxStudents} students). Please choose another section or contact administrator to increase section capacity.");
                }
            }

            // Validate password strength
            $password = $_POST['password'];
            if (strlen($password) < 8) {
                throw new \Exception('❌ Password must be at least 8 characters long.');
            }
            if (!preg_match('/[A-Z]/', $password)) {
                throw new \Exception('❌ Password must contain at least one uppercase letter.');
            }
            if (!preg_match('/[a-z]/', $password)) {
                throw new \Exception('❌ Password must contain at least one lowercase letter.');
            }
            if (!preg_match('/\d/', $password)) {
                throw new \Exception('❌ Password must contain at least one number.');
            }
            if (!preg_match('/[@$!%*?&]/', $password)) {
                throw new \Exception('❌ Password must contain at least one special character (@$!%*?&).');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
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

            // Generate LRN systematically if not provided
            $lrn = $_POST['lrn'] ?? null;
            if (empty($lrn)) {
                // Generate systematic LRN: YYYYSSSSSSSS (Year + 8-digit sequential number)
                // Format: School year (4 digits) + Sequential number based on max existing LRN (8 digits)
                $currentYear = date('Y');
                $schoolYearPrefix = substr($_POST['school_year'] ?? $currentYear . '-' . ($currentYear + 1), 0, 4);
                
                // Get the highest LRN starting with this year
                $stmt = $pdo->prepare("SELECT MAX(CAST(lrn AS UNSIGNED)) as max_lrn FROM students WHERE lrn REGEXP '^[0-9]+$'");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $maxLrn = $result['max_lrn'] ?? 0;
                
                // If max LRN exists and starts with current year prefix, increment from there
                // Otherwise, start from year + 00000001
                if ($maxLrn > 0 && substr((string)$maxLrn, 0, 4) === $schoolYearPrefix) {
                    $nextNumber = $maxLrn + 1;
                } else {
                    $nextNumber = (int)($schoolYearPrefix . '00000001');
                }
                
                $lrn = (string)$nextNumber;
            }


            // Create student profile
            $stmt = $pdo->prepare('
                INSERT INTO students (
                    user_id, lrn, first_name, last_name, middle_name,
                    birth_date, gender, contact_number, address, grade_level, section_id,
                    guardian_name, guardian_contact, guardian_relationship, school_year,
                    enrollment_status, previous_school, medical_conditions, allergies,
                    emergency_contact_name, emergency_contact_number, emergency_contact_relationship,
                    notes, date_enrolled, status
                ) VALUES (
                    :user_id, :lrn, :first_name, :last_name, :middle_name,
                    :birth_date, :gender, :contact_number, :address, :grade_level, :section_id,
                    :guardian_name, :guardian_contact, :guardian_relationship, :school_year,
                    :enrollment_status, :previous_school, :medical_conditions, :allergies,
                    :emergency_contact_name, :emergency_contact_number, :emergency_contact_relationship,
                    :notes, CURDATE(), :status
                )
            ');

            $enrollmentStatus = $_POST['enrollment_status'] ?? 'enrolled';
            
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
                'enrollment_status' => $enrollmentStatus,
                'previous_school' => $_POST['previous_school'] ?? null,
                'medical_conditions' => $_POST['medical_conditions'] ?? null,
                'allergies' => $_POST['allergies'] ?? null,
                'emergency_contact_name' => $_POST['emergency_contact_name'] ?? null,
                'emergency_contact_number' => $_POST['emergency_contact_number'] ?? null,
                'emergency_contact_relationship' => $_POST['emergency_contact_relationship'] ?? null,
                'notes' => $_POST['notes'] ?? null,
                'status' => $enrollmentStatus  // Sync status with enrollment_status
            ]);

            // Get the student ID that was just created
            $studentId = (int)$pdo->lastInsertId();
            
            // Optionally create parent account if guardian info is provided
            $parentCreated = false;
            $parentPassword = null;
            $guardianName = $_POST['guardian_name'] ?? null;
            $guardianEmail = $_POST['guardian_email'] ?? null;
            $guardianContact = $_POST['guardian_contact'] ?? null;
            $guardianRelationship = $_POST['guardian_relationship'] ?? null;
            $createParentAccount = isset($_POST['create_parent_account']) && $_POST['create_parent_account'] === '1';
            
            if ($createParentAccount && !empty($guardianName) && !empty($guardianEmail) && \Helpers\Validator::email($guardianEmail)) {
                // Check if email already exists
                $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $checkEmail->execute(['email' => $guardianEmail]);
                
                if (!$checkEmail->fetch()) {
                    // Generate a random password
                    $parentPassword = bin2hex(random_bytes(8)); // 16 character password
                    $parentPasswordHash = password_hash($parentPassword, PASSWORD_DEFAULT);
                    
                    // Create parent user account
                    $insertParent = $pdo->prepare('
                        INSERT INTO users (role, email, password_hash, name, status, approved_by, approved_at, linked_student_user_id, parent_relationship) 
                        VALUES ("parent", :email, :hash, :name, "active", :approved_by, NOW(), :student_user_id, :relationship)
                    ');
                    $insertParent->execute([
                        'email' => $guardianEmail,
                        'hash' => $parentPasswordHash,
                        'name' => $guardianName,
                        'approved_by' => $user['id'],
                        'student_user_id' => $userId,
                        'relationship' => $guardianRelationship ?? 'guardian'
                    ]);
                    
                    $parentCreated = true;
                    error_log("Parent account auto-created during student registration: Email={$guardianEmail}, StudentID={$studentId}");
                }
            }

            // Log the student creation
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                VALUES (:admin_id, "student_created", "student", :student_id, :details, :ip, :user_agent)
            ');
            $stmt->execute([
                'admin_id' => $user['id'],
                'student_id' => $studentId,
                'details' => json_encode([
                    'student_name' => $fullName,
                    'student_email' => $_POST['email'],
                    'student_id' => $studentId,
                    'user_id' => $userId,
                    'lrn' => $lrn,
                    'grade_level' => $_POST['grade_level'],
                    'section_id' => $_POST['section_id'],
                    'parent_account_created' => $parentCreated,
                    'parent_email' => $parentCreated ? $guardianEmail : null
                ]),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $pdo->commit();

            // Store success message with details in session
            $_SESSION['success_message'] = "✅ Student registered successfully!";
            $_SESSION['success_details'] = [
                'name' => $fullName,
                'email' => $_POST['email'],
                'lrn' => $lrn,
                'lrn_generated' => empty($_POST['lrn']),
                'grade_level' => $_POST['grade_level'],
                'user_id' => $userId,
                'student_id' => $studentId,
                'parent_created' => $parentCreated,
                'parent_email' => $parentCreated ? $guardianEmail : null,
                'parent_password' => $parentCreated ? $parentPassword : null
            ];

            // Redirect to success page or users list
            $redirectUrl = \Helpers\Url::to('/admin/users?success=student_created&student_id=' . $userId);
            if ($parentCreated) {
                $redirectUrl .= '&parent_created=1';
            }
            header('Location: ' . $redirectUrl);
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

            // Flash message for admin
            Notification::success('Adviser assigned successfully');

            // Notify the assigned adviser
            Notification::create(
                recipientIds: $adviserId,
                type: 'success',
                category: 'section_assignment',
                title: 'Adviser Assignment',
                message: "You have been assigned as the adviser for {$section['name']}.",
                options: [
                    'link' => '/teacher/sections',
                    'created_by' => $user['id'],
                    'metadata' => ['section_id' => $sectionId, 'section_name' => $section['name']]
                ]
            );

            // Notify all students in the section
            Notification::createForSection(
                sectionId: $sectionId,
                type: 'info',
                category: 'section_assignment',
                title: 'New Section Adviser',
                message: "{$adviser['name']} is now your section adviser for {$section['name']}.",
                options: ['link' => '/student/dashboard', 'created_by' => $user['id']]
            );

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
            // Include all active sections regardless of school year, but prioritize current year
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
                    s.description,
                    COUNT(DISTINCT st.id) as enrolled_students,
                    u.name as adviser_name,
                    u.email as adviser_email,
                    s.adviser_id,
                    CASE 
                        WHEN COUNT(DISTINCT st.id) >= s.max_students THEN "full"
                        WHEN COUNT(DISTINCT st.id) >= s.max_students * 0.8 THEN "nearly_full"
                        ELSE "available"
                    END as status
                FROM sections s
                LEFT JOIN students st ON st.section_id = s.id AND (st.status = "enrolled" OR st.status IS NULL)
                LEFT JOIN users u ON s.adviser_id = u.id
                WHERE s.is_active = 1
                GROUP BY s.id, s.name, s.grade_level, s.room, s.max_students, s.school_year, s.is_active, s.created_at, s.description, u.name, u.email, s.adviser_id
                ORDER BY s.school_year DESC, s.grade_level, s.name
            ');
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ensure numeric values are properly cast
            foreach ($sections as &$section) {
                $section['enrolled_students'] = (int)($section['enrolled_students'] ?? 0);
                $section['max_students'] = (int)($section['max_students'] ?? 0);
                $section['grade_level'] = (int)($section['grade_level'] ?? 0);
            }
            unset($section);

            // Get unassigned students count
            $stmt = $pdo->prepare('
                SELECT COUNT(*) as count
                FROM students s
                WHERE s.section_id IS NULL
            ');
            $stmt->execute();
            $unassignedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Get all active teachers for adviser dropdown
            $stmt = $pdo->prepare('
                SELECT u.id, u.name, u.email, u.role, t.is_adviser
                FROM users u
                LEFT JOIN teachers t ON u.id = t.user_id
                WHERE (u.role = "teacher" OR u.role = "adviser") AND u.status = "active"
                ORDER BY u.name
            ');
            $stmt->execute();
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view->render('admin/sections', [
                'title' => 'Section Management',
                'user' => $user,
                'activeNav' => 'sections',
                'sections' => $sections,
                'unassignedCount' => $unassignedCount,
                'teachers' => $teachers,
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

            // Get adviser_id if provided
            $adviserId = isset($_POST['adviser_id']) && !empty($_POST['adviser_id']) ? (int)$_POST['adviser_id'] : null;
            
            // Validate adviser if provided
            if ($adviserId !== null) {
                $stmt = $pdo->prepare('SELECT id, role, status FROM users WHERE id = ? AND (role = "teacher" OR role = "adviser") AND status = "active"');
                $stmt->execute([$adviserId]);
                $adviser = $stmt->fetch();
                if (!$adviser) {
                    throw new \Exception('Selected adviser is not valid or inactive.');
                }
            }

            $stmt = $pdo->prepare('
                INSERT INTO sections (name, grade_level, room, max_students, school_year, description, adviser_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ');
            $stmt->execute([$name, $gradeLevel, $room, $maxStudents, $schoolYear, $description, $adviserId]);
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

            // Always return JSON for AJAX requests, or check Accept header
            $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
            $acceptsJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
            
            if ($isAjax || $acceptsJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Section created successfully',
                    'section_id' => $sectionId,
                    'section_name' => $name
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

            // Always return JSON for AJAX requests, or check Accept header
            $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
            $acceptsJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
            
            if ($isAjax || $acceptsJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Section updated successfully',
                    'section_id' => $sectionId
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

            // Get student user ID and name
            $stmt = $pdo->prepare('
                SELECT s.user_id, u.name as student_name
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ?
            ');
            $stmt->execute([$studentId]);
            $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            // Flash message for admin
            Notification::success('Student assigned to section successfully');

            // Notify student
            if ($studentInfo && $studentInfo['user_id']) {
                Notification::create(
                    recipientIds: (int)$studentInfo['user_id'],
                    type: 'success',
                    category: 'section_assignment',
                    title: 'Section Assignment',
                    message: "You have been assigned to {$section['name']}. Check your schedule for details.",
                    options: [
                        'link' => '/student/dashboard',
                        'created_by' => $user['id'],
                        'metadata' => ['section_id' => $sectionId, 'section_name' => $section['name']]
                    ]
                );

                // Notify parents of the student
                Notification::createForParents(
                    studentId: $studentId,
                    type: 'info',
                    category: 'section_assignment',
                    title: 'Section Assignment',
                    message: "{$studentInfo['student_name']} has been assigned to {$section['name']} for the current school year.",
                    options: ['link' => '/parent/profile', 'created_by' => $user['id']]
                );

                // Notify section adviser if exists
                $stmt = $pdo->prepare('SELECT adviser_id FROM sections WHERE id = ?');
                $stmt->execute([$sectionId]);
                $adviserId = $stmt->fetchColumn();
                
                if ($adviserId) {
                    Notification::create(
                        recipientIds: (int)$adviserId,
                        type: 'info',
                        category: 'section_assignment',
                        title: 'New Student in Section',
                        message: "{$studentInfo['student_name']} has been added to your section: {$section['name']}.",
                        options: ['link' => "/teacher/sections?section={$sectionId}", 'created_by' => $user['id']]
                    );
                }
            }

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
                    s.created_at,
                    COUNT(DISTINCT st.id) as enrolled_students,
                    GREATEST(0, s.max_students - COUNT(DISTINCT st.id)) as available_slots,
                    u.name as adviser_name,
                    u.email as adviser_email,
                    s.adviser_id
                FROM sections s
                LEFT JOIN students st ON st.section_id = s.id AND st.status = "enrolled"
                LEFT JOIN users u ON s.adviser_id = u.id
                WHERE s.id = ?
                GROUP BY s.id, s.name, s.grade_level, s.room, s.max_students, s.school_year, s.description, s.is_active, s.created_at, u.name, u.email, s.adviser_id
            ');
            $stmt->execute([$sectionId]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$section) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Section not found']);
                return;
            }

            // Ensure numeric values are properly cast
            $section['enrolled_students'] = (int)($section['enrolled_students'] ?? 0);
            $section['max_students'] = (int)($section['max_students'] ?? 0);
            $section['available_slots'] = (int)($section['available_slots'] ?? 0);
            $section['grade_level'] = (int)($section['grade_level'] ?? 0);

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
     * Delete a section
     */
    public function deleteSection(): void
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
            header('Location: ' . \Helpers\Url::to('/admin/sections?error=' . urlencode('Invalid security token')));
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            $sectionId = (int)($_POST['section_id'] ?? 0);
            if (!$sectionId) {
                throw new \Exception('Invalid section ID.');
            }

            $pdo->beginTransaction();

            // Get section information for logging
            $stmt = $pdo->prepare('
                SELECT s.id, s.name, s.grade_level, s.adviser_id, 
                       COUNT(DISTINCT st.id) as student_count,
                       COUNT(DISTINCT c.id) as class_count
                FROM sections s
                LEFT JOIN students st ON st.section_id = s.id
                LEFT JOIN classes c ON c.section_id = s.id
                WHERE s.id = ?
                GROUP BY s.id, s.name, s.grade_level, s.adviser_id
            ');
            $stmt->execute([$sectionId]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$section) {
                throw new \Exception('Section not found.');
            }

            $studentCount = (int)($section['student_count'] ?? 0);
            $classCount = (int)($section['class_count'] ?? 0);

            // Check if section has active classes - prevent deletion if it does
            if ($classCount > 0) {
                throw new \Exception("Cannot delete section '{$section['name']}'. It has {$classCount} active class(es). Please delete or reassign the classes first.");
            }

            // Get affected students BEFORE unassigning them (for notifications)
            $affectedStudents = [];
            if ($studentCount > 0) {
                $stmt = $pdo->prepare('SELECT id, user_id FROM students WHERE section_id = ?');
                $stmt->execute([$sectionId]);
                $affectedStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // 1. Remove students from section (set section_id to NULL)
            if ($studentCount > 0) {
                $stmt = $pdo->prepare('UPDATE students SET section_id = NULL WHERE section_id = ?');
                $stmt->execute([$sectionId]);
            }

            // 2. Remove student enrollments in classes for this section (via student_classes)
            $stmt = $pdo->prepare('
                DELETE sc FROM student_classes sc
                INNER JOIN classes c ON sc.class_id = c.id
                WHERE c.section_id = ?
            ');
            $stmt->execute([$sectionId]);

            // 3. Delete assignments for this section
            $stmt = $pdo->prepare('DELETE FROM assignments WHERE section_id = ?');
            $stmt->execute([$sectionId]);

            // 4. Delete attendance records for this section (if not cascading)
            $stmt = $pdo->prepare('DELETE FROM attendance WHERE section_id = ?');
            $stmt->execute([$sectionId]);

            // 5. Delete grades for this section (if any exist)
            $stmt = $pdo->prepare('DELETE FROM grades WHERE section_id = ?');
            $stmt->execute([$sectionId]);

            // 6. Delete performance alerts for this section
            $stmt = $pdo->prepare('DELETE FROM performance_alerts WHERE section_id = ?');
            $stmt->execute([$sectionId]);

            // 7. Handle adviser assignment before deleting section
            if ($section['adviser_id']) {
                // Check if this adviser is assigned to any other sections (before we delete this one)
                $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM sections WHERE adviser_id = ? AND id != ?');
                $stmt->execute([$section['adviser_id'], $sectionId]);
                $otherAssignments = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // If no other assignments, remove adviser flag from teacher record
                if ($otherAssignments['count'] == 0) {
                    $stmt = $pdo->prepare('UPDATE teachers SET is_adviser = 0 WHERE user_id = ?');
                    $stmt->execute([$section['adviser_id']]);
                }
            }

            // 8. Permanently delete the section from the database
            $stmt = $pdo->prepare('DELETE FROM sections WHERE id = ?');
            $result = $stmt->execute([$sectionId]);
            
            // Check if deletion actually affected any rows
            $rowsAffected = $stmt->rowCount();
            if ($rowsAffected === 0) {
                throw new \Exception('Section deletion failed: No rows were deleted. Section may not exist or may have already been deleted.');
            }
            
            // Verify deletion was successful by checking if section still exists
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM sections WHERE id = ?');
            $stmt->execute([$sectionId]);
            $verifyDelete = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($verifyDelete['count'] > 0) {
                throw new \Exception('Failed to delete section. Section still exists in database after deletion attempt.');
            }
            
            // Log successful deletion for debugging
            error_log("Section {$sectionId} ({$section['name']}) successfully deleted from database. Rows affected: {$rowsAffected}");

            // 8. Log the deletion (do not fail the whole operation if logging breaks)
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) 
                    VALUES (:admin_id, "section_deleted", "section", :section_id, :details, :ip, :user_agent)
                ');
                $stmt->execute([
                    'admin_id' => $user['id'],
                    'section_id' => $sectionId,
                    'details' => json_encode([
                        'section_name' => $section['name'],
                        'section_id' => $sectionId,
                        'grade_level' => $section['grade_level'],
                        'students_affected' => $studentCount,
                        'adviser_id' => $section['adviser_id']
                    ]),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
            } catch (\PDOException $logEx) {
                // If the audit log table is misconfigured (e.g., non-AUTO_INCREMENT id), do not block deletion
                error_log('Audit log insert failed after section delete: ' . $logEx->getMessage());
            }

            $pdo->commit();

            // Send notifications to affected students and parents
            if (!empty($affectedStudents)) {
                foreach ($affectedStudents as $student) {
                    if ($student['user_id']) {
                        Notification::create(
                            (int)$student['user_id'],
                            'Section Removed',
                            "You have been removed from section '{$section['name']}' as it has been deleted.",
                            'warning',
                            'academic'
                        );
                    }
                    
                    // Notify parents
                    $parentStmt = $pdo->prepare('SELECT user_id FROM parents WHERE student_id = ?');
                    $parentStmt->execute([$student['id']]);
                    $parents = $parentStmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($parents as $parent) {
                        Notification::create(
                            (int)$parent['user_id'],
                            'Child\'s Section Removed',
                            "Your child has been removed from section '{$section['name']}' as it has been deleted.",
                            'warning',
                            'academic'
                        );
                    }
                }
            }

            // Return JSON response for AJAX
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Section '{$section['name']}' has been deleted successfully."
                ]);
                return;
            }

            // Redirect for traditional form submission
            header('Location: ' . \Helpers\Url::to('/admin/sections?success=section_deleted&section=' . urlencode($section['name'])));
            return;

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            // Log the error for debugging
            error_log("Section deletion error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Return JSON response for AJAX
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                return;
            }
            
            // Redirect for traditional form submission
            header('Location: ' . \Helpers\Url::to('/admin/sections?error=' . urlencode($e->getMessage())));
        } catch (\Throwable $e) {
            // Catch any other errors (PDO exceptions, etc.)
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            // Log the error for debugging
            error_log("Section deletion fatal error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Return JSON response for AJAX
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'An unexpected error occurred while deleting the section. Please check the server logs.'
                ]);
                return;
            }
            
            // Redirect for traditional form submission
            header('Location: ' . \Helpers\Url::to('/admin/sections?error=' . urlencode('An unexpected error occurred')));
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

    /**
     * Display students list with search functionality
     */
    public function students(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need administrator privileges to access this page.');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            // Get search and filter parameters
            $search = trim((string)($_GET['search'] ?? ''));
            $gradeFilter = isset($_GET['grade']) ? (int)$_GET['grade'] : null;
            $sectionFilter = isset($_GET['section']) ? (int)$_GET['section'] : null;
            $statusFilter = $_GET['status'] ?? '';

            // Build query
            $query = '
                SELECT 
                    s.id,
                    s.user_id,
                    s.lrn,
                    s.first_name,
                    s.last_name,
                    s.middle_name,
                    CONCAT_WS(" ", s.first_name, s.middle_name, s.last_name) as full_name,
                    s.grade_level,
                    s.section_id,
                    s.enrollment_status,
                    s.school_year,
                    u.email,
                    u.status as user_status,
                    sec.name as section_name,
                    sec.room,
                    (SELECT AVG(g.grade_value) FROM grades g WHERE g.student_id = s.id) as avg_grade,
                    (SELECT COUNT(*) FROM grades g WHERE g.student_id = s.id) as total_grades
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN sections sec ON s.section_id = sec.id
                WHERE 1=1
            ';

            $params = [];

            // Add search conditions
            if (!empty($search)) {
                $query .= ' AND (
                    s.first_name LIKE ? OR 
                    s.last_name LIKE ? OR 
                    s.middle_name LIKE ? OR 
                    s.lrn LIKE ? OR 
                    u.email LIKE ? OR
                    CONCAT_WS(" ", s.first_name, s.middle_name, s.last_name) LIKE ?
                )';
                $searchParam = "%{$search}%";
                $params = array_fill(0, 6, $searchParam);
            }

            // Add filters
            if ($gradeFilter) {
                $query .= ' AND s.grade_level = ?';
                $params[] = $gradeFilter;
            }

            if ($sectionFilter) {
                $query .= ' AND s.section_id = ?';
                $params[] = $sectionFilter;
            }

            if ($statusFilter) {
                $query .= ' AND s.enrollment_status = ?';
                $params[] = $statusFilter;
            }

            $query .= ' ORDER BY s.grade_level, s.last_name, s.first_name LIMIT 100';

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get filter options
            $sectionsStmt = $pdo->query('
                SELECT id, name, grade_level, room 
                FROM sections 
                WHERE is_active = 1 
                ORDER BY grade_level, name
            ');
            $sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get statistics
            $statsStmt = $pdo->query('
                SELECT 
                    COUNT(*) as total_students,
                    COUNT(CASE WHEN enrollment_status = "enrolled" THEN 1 END) as enrolled,
                    COUNT(CASE WHEN enrollment_status = "transferred" THEN 1 END) as transferred,
                    COUNT(CASE WHEN enrollment_status = "dropped" THEN 1 END) as dropped,
                    COUNT(CASE WHEN section_id IS NULL THEN 1 END) as unassigned
                FROM students
            ');
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            $this->view->render('admin/students', [
                'title' => 'Students',
                'user' => $user,
                'activeNav' => 'students',
                'students' => $students,
                'sections' => $sections,
                'stats' => $stats,
                'search' => $search,
                'gradeFilter' => $gradeFilter,
                'sectionFilter' => $sectionFilter,
                'statusFilter' => $statusFilter
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Error loading students: ' . $e->getMessage());
        }
    }

    /**
     * View detailed student profile
     */
    public function viewStudent(): void
    {
        $user = Session::get('user');
        if (!$user || !in_array(($user['role'] ?? ''), ['admin', 'teacher', 'adviser'], true)) {
            \Helpers\ErrorHandler::forbidden('You need appropriate privileges to access this page.');
            return;
        }

        $studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$studentId) {
            \Helpers\ErrorHandler::notFound('Student ID not provided.');
            return;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $pdo = Database::connection($config['database']);

        try {
            // Get student information
            $stmt = $pdo->prepare('
                SELECT 
                    s.*,
                    u.email,
                    u.status as user_status,
                    u.name as full_name_alt,
                    u.created_at as user_created_at,
                    sec.name as section_name,
                    sec.grade_level as section_grade,
                    sec.room as section_room,
                    adv_u.name as adviser_name,
                    adv_u.email as adviser_email
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN sections sec ON s.section_id = sec.id
                LEFT JOIN teachers adv_t ON sec.adviser_id = adv_t.id
                LEFT JOIN users adv_u ON adv_t.user_id = adv_u.id
                WHERE s.id = ?
            ');
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student not found.');
                return;
            }

            // Get classes and teachers
            $classesStmt = $pdo->prepare('
                SELECT 
                    c.id as class_id,
                    sub.name as subject_name,
                    sub.code as subject_code,
                    sec.name as section_name,
                    c.schedule,
                    c.room,
                    u.name as teacher_name,
                    u.email as teacher_email,
                    sc.status as enrollment_status
                FROM student_classes sc
                JOIN classes c ON sc.class_id = c.id
                JOIN subjects sub ON c.subject_id = sub.id
                JOIN sections sec ON c.section_id = sec.id
                JOIN teachers t ON c.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                WHERE sc.student_id = ? AND c.is_active = 1
                ORDER BY sub.name
            ');
            $classesStmt->execute([$studentId]);
            $classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get grades summary
            $gradesStmt = $pdo->prepare('
                SELECT 
                    sub.name as subject_name,
                    sub.code as subject_code,
                    AVG(g.grade_value) as average_grade,
                    COUNT(g.id) as total_grades,
                    MAX(g.graded_at) as last_graded
                FROM grades g
                JOIN subjects sub ON g.subject_id = sub.id
                WHERE g.student_id = ?
                GROUP BY sub.id, sub.name, sub.code
                ORDER BY sub.name
            ');
            $gradesStmt->execute([$studentId]);
            $gradesSummary = $gradesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get attendance statistics
            $attendanceStmt = $pdo->prepare('
                SELECT 
                    COUNT(*) as total_records,
                    COUNT(CASE WHEN status = "present" THEN 1 END) as present,
                    COUNT(CASE WHEN status = "late" THEN 1 END) as late,
                    COUNT(CASE WHEN status = "absent" THEN 1 END) as absent,
                    COUNT(CASE WHEN status = "excused" THEN 1 END) as excused
                FROM attendance
                WHERE student_id = ?
            ');
            $attendanceStmt->execute([$studentId]);
            $attendanceStats = $attendanceStmt->fetch(PDO::FETCH_ASSOC);

            // Calculate attendance rate
            $attendanceRate = 0;
            if ($attendanceStats['total_records'] > 0) {
                $attendanceRate = round(($attendanceStats['present'] / $attendanceStats['total_records']) * 100, 1);
            }

            // Calculate overall GPA
            $overallGPA = 0;
            $totalGrades = 0;
            foreach ($gradesSummary as $grade) {
                if ($grade['average_grade']) {
                    $overallGPA += $grade['average_grade'];
                    $totalGrades++;
                }
            }
            if ($totalGrades > 0) {
                $overallGPA = round($overallGPA / $totalGrades, 2);
            }

            $this->view->render('admin/view-student', [
                'title' => 'Student Profile - ' . ($student['first_name'] . ' ' . $student['last_name']),
                'user' => $user,
                'activeNav' => 'students',
                'student' => $student,
                'classes' => $classes,
                'gradesSummary' => $gradesSummary,
                'attendanceStats' => $attendanceStats,
                'attendanceRate' => $attendanceRate,
                'overallGPA' => $overallGPA
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Error loading student profile: ' . $e->getMessage());
        }
    }

    /**
     * Edit student information
     */
    public function editStudent(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need admin privileges to access this page.');
            return;
        }

        $studentId = (int)($_GET['id'] ?? 0);
        if ($studentId <= 0) {
            \Helpers\ErrorHandler::badRequest('Invalid student ID.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Get student details
            $stmt = $pdo->prepare("
                SELECT s.*, u.email, u.name as full_name
                FROM students s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.id = ?
            ");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$student) {
                \Helpers\ErrorHandler::notFound('Student not found.');
                return;
            }

            // Get all sections for dropdown
            $stmt = $pdo->query("
                SELECT id, name, grade_level, room, max_students,
                    (SELECT COUNT(*) FROM students WHERE section_id = sections.id) as current_students
                FROM sections
                WHERE is_active = 1
                ORDER BY grade_level, name
            ");
            $sections = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view->render('admin/edit-student', [
                'title' => 'Edit Student - ' . ($student['first_name'] . ' ' . $student['last_name']),
                'user' => $user,
                'activeNav' => 'students',
                'showBack' => true,
                'student' => $student,
                'sections' => $sections,
                'csrf_token' => \Helpers\Csrf::generateToken(),
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load student: ' . $e->getMessage());
        }
    }

    /**
     * Update student information
     */
    public function updateStudent(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need admin privileges.');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            \Helpers\ErrorHandler::badRequest('Invalid request method.');
            return;
        }

        try {
            // CSRF validation
            if (!isset($_POST['csrf_token']) || !\Helpers\Csrf::validateToken($_POST['csrf_token'])) {
                throw new \Exception('Invalid CSRF token.');
            }

            $studentId = (int)($_POST['student_id'] ?? 0);
            if ($studentId <= 0) {
                throw new \Exception('Invalid student ID.');
            }

            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Validate required fields
            $required = ['first_name', 'last_name', 'lrn', 'grade_level', 'gender'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new \Exception("Field '{$field}' is required.");
                }
            }

            // Check if LRN is unique (excluding current student)
            $stmt = $pdo->prepare("SELECT id FROM students WHERE lrn = ? AND id != ?");
            $stmt->execute([$_POST['lrn'], $studentId]);
            if ($stmt->fetch()) {
                throw new \Exception('LRN already exists for another student.');
            }

            // Update student record
            $stmt = $pdo->prepare("
                UPDATE students SET
                    lrn = ?,
                    first_name = ?,
                    last_name = ?,
                    middle_name = ?,
                    birth_date = ?,
                    gender = ?,
                    contact_number = ?,
                    address = ?,
                    guardian_name = ?,
                    guardian_contact = ?,
                    guardian_relationship = ?,
                    grade_level = ?,
                    section_id = ?,
                    school_year = ?,
                    enrollment_status = ?,
                    previous_school = ?,
                    medical_conditions = ?,
                    allergies = ?,
                    emergency_contact_name = ?,
                    emergency_contact_number = ?,
                    emergency_contact_relationship = ?,
                    notes = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $stmt->execute([
                $_POST['lrn'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['middle_name'] ?? null,
                $_POST['birth_date'] ?? null,
                $_POST['gender'],
                $_POST['contact_number'] ?? null,
                $_POST['address'] ?? null,
                $_POST['guardian_name'] ?? null,
                $_POST['guardian_contact'] ?? null,
                $_POST['guardian_relationship'] ?? null,
                $_POST['grade_level'],
                !empty($_POST['section_id']) ? $_POST['section_id'] : null,
                $_POST['school_year'] ?? date('Y') . '-' . (date('Y') + 1),
                $_POST['enrollment_status'] ?? 'enrolled',
                $_POST['previous_school'] ?? null,
                $_POST['medical_conditions'] ?? null,
                $_POST['allergies'] ?? null,
                $_POST['emergency_contact_name'] ?? null,
                $_POST['emergency_contact_number'] ?? null,
                $_POST['emergency_contact_relationship'] ?? null,
                $_POST['notes'] ?? null,
                $studentId
            ]);

            // Update email if changed
            if (!empty($_POST['email'])) {
                $stmt = $pdo->prepare("
                    UPDATE users SET email = ? 
                    WHERE id = (SELECT user_id FROM students WHERE id = ?)
                ");
                $stmt->execute([$_POST['email'], $studentId]);
            }

            // Audit log
            $auditStmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent, created_at)
                VALUES (?, 'update', 'student', ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $auditStmt->execute([
                $user['id'],
                $studentId,
                json_encode(['updated_by' => 'admin', 'lrn' => $_POST['lrn']]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            Session::set('success', 'Student information updated successfully!');
            header('Location: ' . url('/admin/view-student?id=' . $studentId));
            exit;

        } catch (\Exception $e) {
            Session::set('error', 'Failed to update student: ' . $e->getMessage());
            header('Location: ' . url('/admin/edit-student?id=' . ($studentId ?? 0)));
            exit;
        }
    }

    /**
     * Teacher management - List all teachers
     */
    public function teachers(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need admin privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Get search and filter parameters
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';

            // Build query
            $sql = "
                SELECT 
                    t.id as teacher_id,
                    t.user_id,
                    u.name,
                    u.email,
                    u.status as account_status,
                    u.created_at,
                    -- Count teaching loads
                    (SELECT COUNT(*) FROM classes WHERE teacher_id = t.user_id AND is_active = 1) as class_count,
                    -- Count advisory sections
                    (SELECT COUNT(*) FROM sections WHERE adviser_id = t.user_id) as advisory_count
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                WHERE u.role IN ('teacher', 'adviser')
            ";

            $params = [];

            if (!empty($search)) {
                $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if (!empty($status)) {
                $sql .= " AND u.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY u.name";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $teachers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get statistics
            $stats = [
                'total' => count($teachers),
                'active' => count(array_filter($teachers, fn($t) => $t['account_status'] === 'active')),
                'with_classes' => count(array_filter($teachers, fn($t) => $t['class_count'] > 0)),
                'advisers' => count(array_filter($teachers, fn($t) => $t['advisory_count'] > 0)),
            ];

            $this->view->render('admin/teachers', [
                'title' => 'Teacher Management',
                'user' => $user,
                'activeNav' => 'teachers',
                'showBack' => false,
                'teachers' => $teachers,
                'stats' => $stats,
                'search' => $search,
                'status' => $status,
                'csrf_token' => \Helpers\Csrf::generateToken(),
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load teachers: ' . $e->getMessage());
        }
    }

    /**
     * View teacher details
     */
    public function viewTeacher(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need admin privileges to access this page.');
            return;
        }

        $teacherId = (int)($_GET['id'] ?? 0);
        if ($teacherId <= 0) {
            \Helpers\ErrorHandler::badRequest('Invalid teacher ID.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Get teacher details
            $stmt = $pdo->prepare("
                SELECT t.*, u.name, u.email, u.status, u.created_at
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                WHERE t.user_id = ?
            ");
            $stmt->execute([$teacherId]);
            $teacher = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$teacher) {
                \Helpers\ErrorHandler::notFound('Teacher not found.');
                return;
            }

            // Get teaching loads (classes)
            $stmt = $pdo->prepare("
                SELECT 
                    c.id as class_id,
                    c.schedule,
                    c.room,
                    c.school_year,
                    subj.name as subject_name,
                    subj.code as subject_code,
                    sec.name as section_name,
                    sec.grade_level,
                    (SELECT COUNT(*) FROM student_classes WHERE class_id = c.id AND status = 'enrolled') as student_count
                FROM classes c
                JOIN subjects subj ON c.subject_id = subj.id
                JOIN sections sec ON c.section_id = sec.id
                WHERE c.teacher_id = ? AND c.is_active = 1
                ORDER BY sec.grade_level, sec.name, subj.name
            ");
            $stmt->execute([$teacherId]);
            $classes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get advisory section
            $stmt = $pdo->prepare("
                SELECT 
                    sec.*,
                    (SELECT COUNT(*) FROM students WHERE section_id = sec.id) as student_count
                FROM sections sec
                WHERE sec.adviser_id = ?
            ");
            $stmt->execute([$teacherId]);
            $advisorySection = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Get schedule
            $stmt = $pdo->prepare("
                SELECT 
                    ts.*,
                    c.room,
                    subj.name as subject_name,
                    sec.name as section_name
                FROM teacher_schedules ts
                JOIN classes c ON ts.class_id = c.id
                JOIN subjects subj ON c.subject_id = subj.id
                JOIN sections sec ON c.section_id = sec.id
                WHERE ts.teacher_id = ?
                ORDER BY 
                    FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                    ts.start_time
            ");
            $stmt->execute([$teacherId]);
            $schedules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view->render('admin/view-teacher', [
                'title' => 'View Teacher - ' . $teacher['name'],
                'user' => $user,
                'activeNav' => 'teachers',
                'showBack' => true,
                'teacher' => $teacher,
                'classes' => $classes,
                'advisorySection' => $advisorySection,
                'schedules' => $schedules,
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load teacher: ' . $e->getMessage());
        }
    }

    /**
     * Subject management - List all subjects
     */
    public function subjects(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need admin privileges to access this page.');
            return;
        }

        try {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Get all subjects
            $stmt = $pdo->query("
                SELECT 
                    s.*,
                    (SELECT COUNT(*) FROM classes WHERE subject_id = s.id AND is_active = 1) as class_count
                FROM subjects s
                ORDER BY s.grade_level, s.name
            ");
            $subjects = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view->render('admin/subjects', [
                'title' => 'Subject Management',
                'user' => $user,
                'activeNav' => 'subjects',
                'showBack' => false,
                'subjects' => $subjects,
                'csrf_token' => \Helpers\Csrf::generateToken(),
            ], 'layouts/dashboard');

        } catch (\Exception $e) {
            \Helpers\ErrorHandler::internalServerError('Failed to load subjects: ' . $e->getMessage());
        }
    }

    /**
     * Create new subject
     */
    public function createSubject(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            \Helpers\ErrorHandler::forbidden('You need admin privileges.');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            \Helpers\ErrorHandler::badRequest('Invalid request method.');
            return;
        }

        try {
            // CSRF validation
            if (!isset($_POST['csrf_token']) || !\Helpers\Csrf::validateToken($_POST['csrf_token'])) {
                throw new \Exception('Invalid CSRF token.');
            }

            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);

            // Validate required fields
            if (empty($_POST['name']) || empty($_POST['code'])) {
                throw new \Exception('Subject name and code are required.');
            }

            // Check if code is unique
            $stmt = $pdo->prepare("SELECT id FROM subjects WHERE code = ?");
            $stmt->execute([$_POST['code']]);
            if ($stmt->fetch()) {
                throw new \Exception('Subject code already exists.');
            }

            // Insert subject
            $stmt = $pdo->prepare("
                INSERT INTO subjects (name, code, grade_level, description, 
                    ww_percent, pt_percent, qe_percent, attendance_percent, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
            ");

            $stmt->execute([
                $_POST['name'],
                $_POST['code'],
                $_POST['grade_level'] ?? null,
                $_POST['description'] ?? null,
                $_POST['ww_percent'] ?? 20,
                $_POST['pt_percent'] ?? 50,
                $_POST['qe_percent'] ?? 20,
                $_POST['attendance_percent'] ?? 10,
            ]);

            Session::set('success', 'Subject created successfully!');
            header('Location: ' . url('/admin/subjects'));
            exit;

        } catch (\Exception $e) {
            Session::set('error', 'Failed to create subject: ' . $e->getMessage());
            header('Location: ' . url('/admin/subjects'));
            exit;
        }
    }
}


