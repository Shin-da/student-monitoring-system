<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use PDO;
use PDOException;

/**
 * Centralized Notification Manager
 * Handles creation, routing, and delivery of notifications to users
 */
class NotificationManager
{
    private PDO $pdo;
    
    // Notification type mappings
    private const TYPE_CONFIG = [
        'info' => ['icon' => 'fas fa-info-circle', 'priority' => 'normal'],
        'success' => ['icon' => 'fas fa-check-circle', 'priority' => 'normal'],
        'warning' => ['icon' => 'fas fa-exclamation-triangle', 'priority' => 'high'],
        'error' => ['icon' => 'fas fa-times-circle', 'priority' => 'urgent'],
        'grade' => ['icon' => 'fas fa-graduation-cap', 'priority' => 'normal'],
        'attendance' => ['icon' => 'fas fa-calendar-check', 'priority' => 'high'],
        'assignment' => ['icon' => 'fas fa-file-alt', 'priority' => 'normal'],
        'schedule' => ['icon' => 'fas fa-clock', 'priority' => 'high'],
        'user' => ['icon' => 'fas fa-user', 'priority' => 'normal'],
        'system' => ['icon' => 'fas fa-bell', 'priority' => 'normal'],
    ];
    
    public function __construct(?PDO $pdo = null)
    {
        if ($pdo === null) {
            $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
            $this->pdo = Database::connection($config['database']);
        } else {
            $this->pdo = $pdo;
        }
    }
    
    /**
     * Create and route a notification to one or more users
     * 
     * @param array|int $recipientIds User ID(s) to receive the notification
     * @param string $type Notification type (info, success, warning, error, etc.)
     * @param string $category Notification category for filtering
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $options Additional options (link, metadata, priority, expires_at, created_by, icon)
     * @return array Created notification IDs
     */
    public function notify(
        array|int $recipientIds,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): array {
        // Normalize recipient IDs
        $userIds = is_array($recipientIds) ? $recipientIds : [$recipientIds];
        
        // Get type configuration
        $typeConfig = self::TYPE_CONFIG[$type] ?? self::TYPE_CONFIG['info'];
        
        // Prepare notification data
        $icon = $options['icon'] ?? $typeConfig['icon'];
        $priority = $options['priority'] ?? $typeConfig['priority'] ?? 'normal';
        $link = $options['link'] ?? null;
        $metadata = $options['metadata'] ?? null;
        $expiresAt = $options['expires_at'] ?? null;
        $createdBy = $options['created_by'] ?? null;
        
        // Validate recipients exist and are active
        $validUserIds = $this->validateRecipients($userIds);
        
        if (empty($validUserIds)) {
            return [];
        }
        
        $notificationIds = [];
        
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare('
                INSERT INTO notifications (
                    user_id, type, category, title, message, icon, link, 
                    priority, metadata, expires_at, created_by
                ) VALUES (
                    :user_id, :type, :category, :title, :message, :icon, :link,
                    :priority, :metadata, :expires_at, :created_by
                )
            ');
            
            foreach ($validUserIds as $userId) {
                $stmt->execute([
                    'user_id' => $userId,
                    'type' => $type,
                    'category' => $category,
                    'title' => $title,
                    'message' => $message,
                    'icon' => $icon,
                    'link' => $link,
                    'priority' => $priority,
                    'metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
                    'expires_at' => $expiresAt,
                    'created_by' => $createdBy,
                ]);
                
                $notificationIds[] = (int)$this->pdo->lastInsertId();
            }
            
            $this->pdo->commit();
            return $notificationIds;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("NotificationManager error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Route notification to users by role
     * 
     * @param array|string $roles Role(s) to notify
     * @param string $type Notification type
     * @param string $category Notification category
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $options Additional options
     * @return array Created notification IDs
     */
    public function notifyByRole(
        array|string $roles,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): array {
        $roleArray = is_array($roles) ? $roles : [$roles];
        $userIds = $this->getUserIdsByRole($roleArray);
        
        if (empty($userIds)) {
            return [];
        }
        
        return $this->notify($userIds, $type, $category, $title, $message, $options);
    }
    
    /**
     * Route notification to section members (students and adviser)
     * 
     * @param int $sectionId Section ID
     * @param string $type Notification type
     * @param string $category Notification category
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $options Additional options
     * @return array Created notification IDs
     */
    public function notifySection(
        int $sectionId,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): array {
        $userIds = $this->getSectionMemberIds($sectionId);
        
        if (empty($userIds)) {
            return [];
        }
        
        return $this->notify($userIds, $type, $category, $title, $message, $options);
    }
    
    /**
     * Route notification to class members (students and teacher)
     * 
     * @param int $classId Class ID
     * @param string $type Notification type
     * @param string $category Notification category
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $options Additional options
     * @return array Created notification IDs
     */
    public function notifyClass(
        int $classId,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): array {
        $userIds = $this->getClassMemberIds($classId);
        
        if (empty($userIds)) {
            return [];
        }
        
        return $this->notify($userIds, $type, $category, $title, $message, $options);
    }
    
    /**
     * Route notification to parents of a student
     * 
     * @param int $studentId Student ID
     * @param string $type Notification type
     * @param string $category Notification category
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $options Additional options
     * @return array Created notification IDs
     */
    public function notifyParents(
        int $studentId,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): array {
        $userIds = $this->getParentIdsByStudent($studentId);
        
        if (empty($userIds)) {
            return [];
        }
        
        return $this->notify($userIds, $type, $category, $title, $message, $options);
    }
    
    /**
     * Get notifications for a user
     * 
     * @param int $userId User ID
     * @param array $filters Filters (is_read, type, category, limit, offset)
     * @return array Notifications
     */
    public function getUserNotifications(int $userId, array $filters = []): array
    {
        $isRead = $filters['is_read'] ?? null;
        $type = $filters['type'] ?? null;
        $category = $filters['category'] ?? null;
        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        
        $sql = '
            SELECT n.*, 
                   u.name as created_by_name,
                   CASE 
                       WHEN n.expires_at IS NOT NULL AND n.expires_at < NOW() THEN 1
                       ELSE 0
                   END as is_expired
            FROM notifications n
            LEFT JOIN users u ON n.created_by = u.id
            WHERE n.user_id = :user_id
        ';
        
        $params = ['user_id' => $userId];
        
        if ($isRead !== null) {
            $sql .= ' AND n.is_read = :is_read';
            $params['is_read'] = $isRead ? 1 : 0;
        }
        
        if ($type !== null) {
            $sql .= ' AND n.type = :type';
            $params['type'] = $type;
        }
        
        if ($category !== null) {
            $sql .= ' AND n.category = :category';
            $params['category'] = $category;
        }
        
        $sql .= ' AND (n.expires_at IS NULL OR n.expires_at > NOW())';
        $sql .= ' ORDER BY n.created_at DESC';
        $sql .= ' LIMIT :limit OFFSET :offset';
        
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode metadata JSON
            foreach ($notifications as &$notification) {
                if (!empty($notification['metadata'])) {
                    $notification['metadata'] = json_decode($notification['metadata'], true);
                }
            }
            
            return $notifications;
        } catch (PDOException $e) {
            error_log("NotificationManager getUserNotifications error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*) 
                FROM notifications 
                WHERE user_id = :user_id 
                AND is_read = 0
                AND (expires_at IS NULL OR expires_at > NOW())
            ');
            $stmt->execute(['user_id' => $userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("NotificationManager getUnreadCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark notification(s) as read
     */
    public function markAsRead(array|int $notificationIds, int $userId): bool
    {
        $ids = is_array($notificationIds) ? $notificationIds : [$notificationIds];
        
        if (empty($ids)) {
            return false;
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id IN ($placeholders) 
                AND user_id = ?
            ");
            
            $params = array_merge($ids, [$userId]);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("NotificationManager markAsRead error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = :user_id AND is_read = 0
            ');
            $stmt->execute(['user_id' => $userId]);
            return true;
        } catch (PDOException $e) {
            error_log("NotificationManager markAllAsRead error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete notification(s)
     */
    public function delete(array|int $notificationIds, int $userId): bool
    {
        $ids = is_array($notificationIds) ? $notificationIds : [$notificationIds];
        
        if (empty($ids)) {
            return false;
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE id IN ($placeholders) 
                AND user_id = ?
            ");
            
            $params = array_merge($ids, [$userId]);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("NotificationManager delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up expired notifications
     */
    public function cleanupExpired(): int
    {
        try {
            $stmt = $this->pdo->prepare('
                DELETE FROM notifications 
                WHERE expires_at IS NOT NULL 
                AND expires_at < NOW()
            ');
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("NotificationManager cleanupExpired error: " . $e->getMessage());
            return 0;
        }
    }
    
    // ============================================
    // Helper Methods
    // ============================================
    
    /**
     * Validate that recipient user IDs exist and are active
     */
    private function validateRecipients(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $stmt = $this->pdo->prepare("
                SELECT id 
                FROM users 
                WHERE id IN ($placeholders) 
                AND status = 'active'
            ");
            $stmt->execute($userIds);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("NotificationManager validateRecipients error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user IDs by role
     */
    private function getUserIdsByRole(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($roles), '?'));
            $stmt = $this->pdo->prepare("
                SELECT id 
                FROM users 
                WHERE role IN ($placeholders) 
                AND status = 'active'
            ");
            $stmt->execute($roles);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("NotificationManager getUserIdsByRole error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get section member user IDs (students and adviser)
     */
    private function getSectionMemberIds(int $sectionId): array
    {
        try {
            // Get students in section
            $stmt = $this->pdo->prepare('
                SELECT DISTINCT s.user_id
                FROM students s
                WHERE s.section_id = :section_id
                AND s.user_id IS NOT NULL
            ');
            $stmt->execute(['section_id' => $sectionId]);
            $studentUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get adviser user ID
            $stmt = $this->pdo->prepare('
                SELECT sec.adviser_id
                FROM sections sec
                WHERE sec.id = :section_id
                AND sec.adviser_id IS NOT NULL
            ');
            $stmt->execute(['section_id' => $sectionId]);
            $adviserUserId = $stmt->fetchColumn();
            
            $userIds = array_merge($studentUserIds, $adviserUserId ? [$adviserUserId] : []);
            
            // Validate they're active users
            return $this->validateRecipients(array_unique($userIds));
        } catch (PDOException $e) {
            error_log("NotificationManager getSectionMemberIds error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get class member user IDs (students and teacher)
     */
    private function getClassMemberIds(int $classId): array
    {
        try {
            // Get teacher user ID
            $stmt = $this->pdo->prepare('
                SELECT t.user_id
                FROM classes c
                JOIN teachers t ON c.teacher_id = t.id
                WHERE c.id = :class_id
            ');
            $stmt->execute(['class_id' => $classId]);
            $teacherUserId = $stmt->fetchColumn();
            
            // Get students in class
            $stmt = $this->pdo->prepare('
                SELECT DISTINCT s.user_id
                FROM student_classes sc
                JOIN students s ON sc.student_id = s.id
                WHERE sc.class_id = :class_id
                AND sc.status = "enrolled"
                AND s.user_id IS NOT NULL
            ');
            $stmt->execute(['class_id' => $classId]);
            $studentUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $userIds = array_merge(
                $studentUserIds,
                $teacherUserId ? [$teacherUserId] : []
            );
            
            // Validate they're active users
            return $this->validateRecipients(array_unique($userIds));
        } catch (PDOException $e) {
            error_log("NotificationManager getClassMemberIds error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get parent user IDs for a student
     */
    private function getParentIdsByStudent(int $studentId): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT DISTINCT p.user_id
                FROM parents p
                WHERE p.student_id = :student_id
            ');
            $stmt->execute(['student_id' => $studentId]);
            $parentUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Validate they're active users
            return $this->validateRecipients($parentUserIds);
        } catch (PDOException $e) {
            error_log("NotificationManager getParentIdsByStudent error: " . $e->getMessage());
            return [];
        }
    }
}

