-- Create assignments table for teacher assignments
-- This table stores assignments that teachers create for their classes

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `assignments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `teacher_id` INT UNSIGNED NOT NULL,
  `section_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `assignment_type` ENUM('quiz','homework','project','exam','activity','other') DEFAULT 'homework',
  `max_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `due_date` DATE DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_teacher` (`teacher_id`),
  INDEX `idx_section_subject` (`section_id`, `subject_id`),
  INDEX `idx_due_date` (`due_date`),
  INDEX `idx_active` (`is_active`),
  CONSTRAINT `assignments_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_section_fk` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

