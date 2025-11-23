-- =====================================================
-- CREATE ATTENDANCE TABLE
-- =====================================================
-- This script creates the attendance table for tracking
-- student attendance per subject and section
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Create attendance table
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `teacher_id` INT UNSIGNED NOT NULL,
  `section_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'absent',
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_student_section_subject_date` (`student_id`, `section_id`, `subject_id`, `attendance_date`),
  INDEX `idx_teacher` (`teacher_id`),
  INDEX `idx_section_subject` (`section_id`, `subject_id`),
  INDEX `idx_date` (`attendance_date`),
  UNIQUE KEY `unique_attendance` (`student_id`, `section_id`, `subject_id`, `attendance_date`),
  CONSTRAINT `attendance_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_section_fk` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

