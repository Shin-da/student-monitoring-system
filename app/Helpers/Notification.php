<?php
declare(strict_types=1);

namespace Helpers;

use Services\NotificationManager;

/**
 * Notification Helper
 * Provides both flash messages (session-based) and persistent notifications (database)
 */
class Notification
{
    /**
     * Flash a success message (session-based, temporary)
     */
    public static function success(string $message): void
    {
        self::set('success', $message);
    }
    
    /**
     * Flash an error message (session-based, temporary)
     */
    public static function error(string $message): void
    {
        self::set('error', $message);
    }
    
    /**
     * Flash a warning message (session-based, temporary)
     */
    public static function warning(string $message): void
    {
        self::set('warning', $message);
    }
    
    /**
     * Flash an info message (session-based, temporary)
     */
    public static function info(string $message): void
    {
        self::set('info', $message);
    }
    
    /**
     * Create a persistent notification (database-stored)
     * 
     * @param array|int $recipientIds User ID(s) to receive the notification
     * @param string $type Notification type (info, success, warning, error, etc.)
     * @param string $category Notification category
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $options Additional options
     * @return array Created notification IDs
     */
    public static function create(
        array|int $recipientIds,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): array {
        $manager = new NotificationManager();
        return $manager->notify($recipientIds, $type, $category, $title, $message, $options);
    }
    
    /**
     * Create notification by role
     */
    public static function createByRole(
        array|string $roles,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): array {
        $manager = new NotificationManager();
        return $manager->notifyByRole($roles, $type, $category, $title, $message, $options);
    }
    
    /**
     * Create notification for section members
     */
    public static function createForSection(
        int $sectionId,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): array {
        $manager = new NotificationManager();
        return $manager->notifySection($sectionId, $type, $category, $title, $message, $options);
    }
    
    /**
     * Create notification for class members
     */
    public static function createForClass(
        int $classId,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): array {
        $manager = new NotificationManager();
        return $manager->notifyClass($classId, $type, $category, $title, $message, $options);
    }
    
    /**
     * Create notification for student's parents
     */
    public static function createForParents(
        int $studentId,
        string $type,
        string $category,
        string $title,
        string $message,
        array $options = []
    ): array {
        $manager = new NotificationManager();
        return $manager->notifyParents($studentId, $type, $category, $title, $message, $options);
    }
    
    public static function get(string $type = null): array
    {
        if ($type) {
            $messages = $_SESSION['notifications'][$type] ?? [];
            unset($_SESSION['notifications'][$type]);
            return $messages;
        }
        
        $messages = $_SESSION['notifications'] ?? [];
        unset($_SESSION['notifications']);
        return $messages;
    }
    
    public static function has(string $type = null): bool
    {
        if ($type) {
            return !empty($_SESSION['notifications'][$type]);
        }
        
        return !empty($_SESSION['notifications']);
    }
    
    public static function flash(string $type, string $message): void
    {
        self::set($type, $message);
    }
    
    public static function getFlashed(): array
    {
        return self::get();
    }
    
    public static function renderHtml(): string
    {
        $html = '';
        $notifications = self::get();
        
        foreach ($notifications as $type => $messages) {
            if (!is_array($messages)) {
                $messages = [$messages];
            }
            
            foreach ($messages as $message) {
                $iconClass = match($type) {
                    'success' => 'fas fa-check-circle',
                    'error' => 'fas fa-exclamation-triangle',
                    'warning' => 'fas fa-exclamation-circle',
                    'info' => 'fas fa-info-circle',
                    default => 'fas fa-bell'
                };
                
                $alertClass = match($type) {
                    'success' => 'alert-success',
                    'error' => 'alert-danger',
                    'warning' => 'alert-warning',
                    'info' => 'alert-info',
                    default => 'alert-secondary'
                };
                
                $html .= sprintf(
                    '<div class="alert %s alert-dismissible fade show" role="alert" data-auto-dismiss="5000">
                        <i class="%s me-2" aria-hidden="true"></i>
                        <span>%s</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close notification"></button>
                    </div>',
                    $alertClass,
                    $iconClass,
                    htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
                );
            }
        }
        
        return $html;
    }
    
    public static function renderJson(): array
    {
        $notifications = self::get();
        $result = [];
        
        foreach ($notifications as $type => $messages) {
            if (!is_array($messages)) {
                $messages = [$messages];
            }
            
            foreach ($messages as $message) {
                $result[] = [
                    'type' => $type,
                    'message' => $message,
                    'timestamp' => time()
                ];
            }
        }
        
        return $result;
    }
    
    private static function set(string $type, string $message): void
    {
        if (!isset($_SESSION['notifications'])) {
            $_SESSION['notifications'] = [];
        }
        
        if (!isset($_SESSION['notifications'][$type])) {
            $_SESSION['notifications'][$type] = [];
        }
        
        $_SESSION['notifications'][$type][] = $message;
    }
    
    /**
     * Format timestamp as relative time (e.g., "2 hours ago")
     */
    public static function formatTimeAgo(string $timestamp): string
    {
        $time = strtotime($timestamp);
        if (!$time) {
            return 'Unknown time';
        }
        
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . 'm ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . 'h ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . 'd ago';
        } else {
            return date('M j, Y', $time);
        }
    }
}