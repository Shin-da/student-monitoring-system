-- Enhance grades table to support complete grading system
-- This migration adds all necessary fields for WW, PT, QE components and SF9/SF10 generation

SET FOREIGN_KEY_CHECKS = 0;

-- Check if grades table exists, if not create it with full structure
CREATE TABLE IF NOT EXISTS `grades` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `section_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `teacher_id` INT UNSIGNED NOT NULL,
  `grade_type` ENUM('ww','pt','qe') NOT NULL COMMENT 'Written Work, Performance Task, Quarterly Exam',
  `quarter` TINYINT UNSIGNED NOT NULL COMMENT '1=1st, 2=2nd, 3=3rd, 4=4th',
  `academic_year` VARCHAR(20) NOT NULL DEFAULT '2024-2025',
  `grade_value` DECIMAL(5,2) NOT NULL COMMENT 'Actual score',
  `max_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00 COMMENT 'Maximum possible score',
  `description` VARCHAR(255) DEFAULT NULL COMMENT 'Assignment/activity name',
  `remarks` TEXT DEFAULT NULL,
  `graded_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_student_subject_quarter` (`student_id`, `subject_id`, `quarter`, `academic_year`),
  INDEX `idx_section_subject` (`section_id`, `subject_id`),
  INDEX `idx_teacher` (`teacher_id`),
  INDEX `idx_quarter_year` (`quarter`, `academic_year`),
  CONSTRAINT `grades_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grades_section_fk` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grades_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grades_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If table exists, add missing columns
ALTER TABLE `grades` 
  ADD COLUMN IF NOT EXISTS `section_id` INT UNSIGNED NOT NULL AFTER `student_id`,
  ADD COLUMN IF NOT EXISTS `grade_type` ENUM('ww','pt','qe') NOT NULL AFTER `subject_id`,
  ADD COLUMN IF NOT EXISTS `quarter` TINYINT UNSIGNED NOT NULL AFTER `grade_type`,
  ADD COLUMN IF NOT EXISTS `academic_year` VARCHAR(20) NOT NULL DEFAULT '2024-2025' AFTER `quarter`,
  ADD COLUMN IF NOT EXISTS `grade_value` DECIMAL(5,2) NOT NULL AFTER `academic_year`,
  ADD COLUMN IF NOT EXISTS `max_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00 AFTER `grade_value`,
  ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) DEFAULT NULL AFTER `max_score`,
  ADD COLUMN IF NOT EXISTS `graded_at` TIMESTAMP NULL DEFAULT NULL AFTER `remarks`,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Remove old score column if it exists
ALTER TABLE `grades` DROP COLUMN IF EXISTS `score`;

-- Create quarterly_grades view for easy access to calculated grades
DROP VIEW IF EXISTS `quarterly_grades_view`;
CREATE VIEW `quarterly_grades_view` AS
SELECT 
  g.student_id,
  g.section_id,
  g.subject_id,
  g.quarter,
  g.academic_year,
  -- Written Work Average
  AVG(CASE WHEN g.grade_type = 'ww' THEN (g.grade_value / NULLIF(g.max_score, 0)) * 100 ELSE NULL END) AS ww_average,
  -- Performance Task Average
  AVG(CASE WHEN g.grade_type = 'pt' THEN (g.grade_value / NULLIF(g.max_score, 0)) * 100 ELSE NULL END) AS pt_average,
  -- Quarterly Exam Average
  AVG(CASE WHEN g.grade_type = 'qe' THEN (g.grade_value / NULLIF(g.max_score, 0)) * 100 ELSE NULL END) AS qe_average,
  -- Count of each type
  COUNT(CASE WHEN g.grade_type = 'ww' THEN 1 END) AS ww_count,
  COUNT(CASE WHEN g.grade_type = 'pt' THEN 1 END) AS pt_count,
  COUNT(CASE WHEN g.grade_type = 'qe' THEN 1 END) AS qe_count
FROM `grades` g
GROUP BY g.student_id, g.section_id, g.subject_id, g.quarter, g.academic_year;

-- Create final_grades view with weighted calculations
DROP VIEW IF EXISTS `final_grades_view`;
CREATE VIEW `final_grades_view` AS
SELECT 
  qg.student_id,
  qg.section_id,
  qg.subject_id,
  qg.quarter,
  qg.academic_year,
  qg.ww_average,
  qg.pt_average,
  qg.qe_average,
  s.ww_percent,
  s.pt_percent,
  s.qe_percent,
  -- Calculate final grade using subject weights
  -- Note: Defaults updated to WW=20%, PT=50%, QE=20% (attendance=10% handled in PHP)
  ROUND(
    (COALESCE(qg.ww_average, 0) * COALESCE(s.ww_percent, 20) / 100) +
    (COALESCE(qg.pt_average, 0) * COALESCE(s.pt_percent, 50) / 100) +
    (COALESCE(qg.qe_average, 0) * COALESCE(s.qe_percent, 20) / 100),
    2
  ) AS final_grade,
  CASE 
    WHEN ROUND(
      (COALESCE(qg.ww_average, 0) * COALESCE(s.ww_percent, 20) / 100) +
      (COALESCE(qg.pt_average, 0) * COALESCE(s.pt_percent, 50) / 100) +
      (COALESCE(qg.qe_average, 0) * COALESCE(s.qe_percent, 20) / 100),
      2
    ) >= 75 THEN 'Passed'
    ELSE 'Failed'
  END AS status
FROM `quarterly_grades_view` qg
JOIN `subjects` s ON qg.subject_id = s.id;

SET FOREIGN_KEY_CHECKS = 1;

