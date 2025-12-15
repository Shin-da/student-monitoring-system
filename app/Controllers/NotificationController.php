<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Services\NotificationManager;
use Helpers\ErrorHandler;

/**
 * Notification API Controller
 * Handles notification-related API endpoints
 */
class NotificationController extends Controller
{
    private NotificationManager $notificationManager;
    
    public function __construct()
    {
        parent::__construct();
        $this->notificationManager = new NotificationManager();
    }
    
    /**
     * Get user's notifications (API endpoint)
     */
    public function getNotifications(): void
    {
        header('Content-Type: application/json');
        
        $user = Session::get('user');
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $userId = (int)$user['id'];
        
        // Handle is_read parameter - can be '0', '1', or omitted
        $isRead = null;
        if (isset($_GET['is_read'])) {
            $isReadParam = $_GET['is_read'];
            if ($isReadParam === '1' || $isReadParam === 'true' || $isReadParam === 1) {
                $isRead = true;
            } elseif ($isReadParam === '0' || $isReadParam === 'false' || $isReadParam === 0) {
                $isRead = false;
            }
        }
        
        $type = $_GET['type'] ?? null;
        $category = $_GET['category'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        try {
            $filters = [
                'is_read' => $isRead,
                'type' => $type,
                'category' => $category,
                'limit' => $limit,
                'offset' => $offset,
            ];
            
            $notifications = $this->notificationManager->getUserNotifications($userId, $filters);
            $unreadCount = $this->notificationManager->getUnreadCount($userId);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load notifications',
                'message' => $e->getMessage()
            ]);
            error_log('NotificationController error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get unread notification count (API endpoint)
     */
    public function getUnreadCount(): void
    {
        header('Content-Type: application/json');
        
        $user = Session::get('user');
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $userId = (int)$user['id'];
        $count = $this->notificationManager->getUnreadCount($userId);
        
        echo json_encode([
            'success' => true,
            'count' => $count,
        ]);
    }
    
    /**
     * Mark notification(s) as read (API endpoint)
     */
    public function markAsRead(): void
    {
        header('Content-Type: application/json');
        
        $user = Session::get('user');
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $userId = (int)$user['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['notification_id'])) {
            // Single notification
            $notificationIds = [(int)$input['notification_id']];
        } elseif (isset($input['notification_ids']) && is_array($input['notification_ids'])) {
            // Multiple notifications
            $notificationIds = array_map('intval', $input['notification_ids']);
        } elseif (isset($input['mark_all']) && $input['mark_all'] === true) {
            // Mark all as read
            $success = $this->notificationManager->markAllAsRead($userId);
            echo json_encode([
                'success' => $success,
            ]);
            return;
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            return;
        }
        
        $success = $this->notificationManager->markAsRead($notificationIds, $userId);
        
        echo json_encode([
            'success' => $success,
        ]);
    }
    
    /**
     * Delete notification(s) (API endpoint)
     */
    public function delete(): void
    {
        header('Content-Type: application/json');
        
        $user = Session::get('user');
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $userId = (int)$user['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['notification_id'])) {
            $notificationIds = [(int)$input['notification_id']];
        } elseif (isset($input['notification_ids']) && is_array($input['notification_ids'])) {
            $notificationIds = array_map('intval', $input['notification_ids']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            return;
        }
        
        $success = $this->notificationManager->delete($notificationIds, $userId);
        
        echo json_encode([
            'success' => $success,
        ]);
    }
    
    /**
     * Display notifications page
     */
    public function index(): void
    {
        $user = Session::get('user');
        if (!$user) {
            \Helpers\ErrorHandler::unauthorized('You must be logged in to view notifications.');
            return;
        }
        
        $userId = (int)$user['id'];
        $userRole = $user['role'] ?? 'user';
        
        // Get filter parameters
        $filter = $_GET['filter'] ?? 'all'; // all, unread, read
        $type = $_GET['type'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $filters = [
            'is_read' => $filter === 'unread' ? false : ($filter === 'read' ? true : null),
            'type' => $type,
            'limit' => $limit,
            'offset' => $offset,
        ];
        
        try {
            $notifications = $this->notificationManager->getUserNotifications($userId, $filters);
            $unreadCount = $this->notificationManager->getUnreadCount($userId);
            
            // Get dashboard URL based on role
            $dashboardUrl = \Helpers\Url::to('/' . $userRole);
            
            $this->view->render('notifications/index', [
                'title' => 'Notifications',
                'user' => $user,
                'activeNav' => 'notifications',
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
                'filter' => $filter,
                'type' => $type,
                'dashboardUrl' => $dashboardUrl,
                'csrf_token' => \Helpers\Csrf::generateToken(),
            ], 'layouts/dashboard-optimized');
        } catch (\Exception $e) {
            error_log('NotificationController::index error: ' . $e->getMessage());
            \Helpers\ErrorHandler::internalServerError('Failed to load notifications. Please try again later.');
        }
    }
}

