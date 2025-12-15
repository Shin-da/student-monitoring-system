-- =====================================================
-- UPDATE GRADE COMPUTATION SYSTEM
-- =====================================================
-- This script updates the grade computation to match new requirements:
-- 50% - Performance Task (PT)
-- 20% - Final Examination (QE)
-- 20% - Written Works (WW)
-- 10% - Attendance
-- =====================================================
-- NOTE: Make sure to run create_attendance_table.sql first
-- if the attendance table doesn't exist
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Add attendance_percent column to subjects table
ALTER TABLE `subjects` 
  ADD COLUMN IF NOT EXISTS `attendance_percent` TINYINT(3) UNSIGNED DEFAULT 10 AFTER `qe_percent`;

-- Update default percentages for all existing subjects
UPDATE `subjects` 
SET 
  `ww_percent` = 20,
  `pt_percent` = 50,
  `qe_percent` = 20,
  `attendance_percent` = 10
WHERE `attendance_percent` IS NULL OR `attendance_percent` = 0;

-- Update the quarterly_grades_view (attendance will be calculated in PHP)
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

-- Update the final_grades_view (note: attendance calculation is done in PHP, not in this view)
-- This view provides base calculations, but final grade with attendance should use GradeModel
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
  s.attendance_percent,
  -- Calculate final grade using subject weights (without attendance - attendance added in PHP)
  -- Note: This view is for reference. Use GradeModel::calculateQuarterlyGrade() for accurate calculations
  ROUND(
    (COALESCE(qg.ww_average, 0) * COALESCE(s.ww_percent, 20) / 100) +
    (COALESCE(qg.pt_average, 0) * COALESCE(s.pt_percent, 50) / 100) +
    (COALESCE(qg.qe_average, 0) * COALESCE(s.qe_percent, 20) / 100),
    2
  ) AS final_grade_without_attendance,
  CASE 
    WHEN ROUND(
      (COALESCE(qg.ww_average, 0) * COALESCE(s.ww_percent, 20) / 100) +
      (COALESCE(qg.pt_average, 0) * COALESCE(s.pt_percent, 50) / 100) +
      (COALESCE(qg.qe_average, 0) * COALESCE(s.qe_percent, 20) / 100),
      2
    ) >= 75 THEN 'Passed'
    ELSE 'Failed'
  END AS status_without_attendance
FROM `quarterly_grades_view` qg
JOIN `subjects` s ON qg.subject_id = s.id;

SET FOREIGN_KEY_CHECKS = 1;

