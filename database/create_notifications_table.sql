-- Create notifications table for persistent notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Recipient user ID',
  `type` enum('info','success','warning','error','grade','attendance','assignment','schedule','user','system') NOT NULL DEFAULT 'info',
  `category` enum('user_management','grade_update','assignment_new','assignment_due','attendance_alert','schedule_change','system_alert','approval_request','section_assignment','class_created','grade_submitted','attendance_marked','low_grade_alert') DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL COMMENT 'Optional link to related page',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional data in JSON format',
  `created_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'User who triggered this notification',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Optional expiration date',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_type` (`type`),
  KEY `idx_category` (`category`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_read` (`user_id`, `is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for faster queries (run separately to avoid errors if they already exist)
-- CREATE INDEX `idx_user_unread_count` ON `notifications` (`user_id`, `is_read`, `created_at`);
-- CREATE INDEX `idx_expires_at` ON `notifications` (`expires_at`);
CREATE INDEX IF NOT EXISTS `idx_expires_at` ON `notifications` (`expires_at`);

