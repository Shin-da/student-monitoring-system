-- Create performance_alerts table for AI-powered early warning system
-- This table stores automatically generated alerts when students are at risk

CREATE TABLE IF NOT EXISTS `performance_alerts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL COMMENT 'User ID of teacher/adviser',
  `section_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NULL COMMENT 'NULL for overall/attendance alerts',
  `alert_type` enum('academic_risk','overall_risk','attendance','grade_drop','trend_declining') NOT NULL DEFAULT 'academic_risk',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` enum('active','resolved','dismissed') NOT NULL DEFAULT 'active',
  `quarter` tinyint(3) UNSIGNED NOT NULL COMMENT '1=1st, 2=2nd, 3=3rd, 4=4th',
  `academic_year` varchar(20) NOT NULL,
  `metadata` json DEFAULT NULL COMMENT 'Additional data (risk_score, reasons, etc.)',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(10) UNSIGNED NULL COMMENT 'User ID who resolved the alert',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_teacher` (`teacher_id`),
  KEY `idx_section` (`section_id`),
  KEY `idx_subject` (`subject_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  KEY `idx_student_quarter` (`student_id`, `quarter`, `academic_year`, `status`),
  CONSTRAINT `fk_performance_alerts_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_performance_alerts_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_performance_alerts_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_performance_alerts_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_performance_alerts_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI-generated performance alerts for at-risk students';

