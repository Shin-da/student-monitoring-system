-- =====================================================
-- TEACHER SCHEDULES TABLE CREATION
-- =====================================================
-- This script creates the teacher_schedules table for
-- managing teacher schedules and conflict detection
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- CREATE TEACHER SCHEDULES TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `teacher_schedules` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `teacher_id` INT UNSIGNED NOT NULL,
  `day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `class_id` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_schedule` (`teacher_id`, `day_of_week`, `start_time`, `end_time`),
  KEY `idx_teacher_day` (`teacher_id`, `day_of_week`),
  KEY `idx_time_range` (`start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- INSERT SAMPLE SCHEDULE DATA
-- =====================================================

-- Get the first teacher ID for sample data
SET @teacher_id = (SELECT id FROM teachers LIMIT 1);

-- Insert sample schedule data if teacher exists
INSERT IGNORE INTO `teacher_schedules` (`teacher_id`, `day_of_week`, `start_time`, `end_time`, `class_id`)
SELECT 
  @teacher_id,
  'Monday',
  '08:00:00',
  '09:00:00',
  c.id
FROM classes c
WHERE c.teacher_id = @teacher_id
LIMIT 1;

INSERT IGNORE INTO `teacher_schedules` (`teacher_id`, `day_of_week`, `start_time`, `end_time`, `class_id`)
SELECT 
  @teacher_id,
  'Wednesday',
  '08:00:00',
  '09:00:00',
  c.id
FROM classes c
WHERE c.teacher_id = @teacher_id
LIMIT 1;

INSERT IGNORE INTO `teacher_schedules` (`teacher_id`, `day_of_week`, `start_time`, `end_time`, `class_id`)
SELECT 
  @teacher_id,
  'Friday',
  '08:00:00',
  '09:00:00',
  c.id
FROM classes c
WHERE c.teacher_id = @teacher_id
LIMIT 1;

COMMIT;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check if table was created successfully
SELECT 'teacher_schedules table created successfully' as status;

-- Show sample data
SELECT 
  ts.id,
  u.name as teacher_name,
  ts.day_of_week,
  ts.start_time,
  ts.end_time,
  c.id as class_id,
  sec.name as section_name,
  sub.name as subject_name
FROM teacher_schedules ts
JOIN teachers t ON ts.teacher_id = t.id
JOIN users u ON t.user_id = u.id
LEFT JOIN classes c ON ts.class_id = c.id
LEFT JOIN sections sec ON c.section_id = sec.id
LEFT JOIN subjects sub ON c.subject_id = sub.id
ORDER BY ts.teacher_id, ts.day_of_week, ts.start_time;
