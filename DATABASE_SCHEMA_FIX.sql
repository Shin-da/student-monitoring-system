-- ============================================================================
-- COMPLETE DATABASE SCHEMA FIX FOR STUDENT MONITORING SYSTEM
-- ============================================================================
-- This script fixes all missing foreign keys, data inconsistencies, 
-- duplicate rows, broken views, and ensures proper relational integrity.
--
-- EXECUTION ORDER:
-- 1. Disable foreign key checks temporarily
-- 2. Clean up duplicate and invalid data
-- 3. Fix AUTO_INCREMENT settings
-- 4. Add all missing foreign key constraints
-- 5. Recreate broken views
-- 6. Re-enable foreign key checks
--
-- BACKUP YOUR DATABASE BEFORE RUNNING THIS SCRIPT!
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

-- ============================================================================
-- SECTION 1: DATA CLEANUP - Remove duplicates and invalid data
-- ============================================================================

-- 1.1 Remove duplicate teacher rows (keep the first one for each user_id)
DELETE t1 FROM teachers t1
INNER JOIN teachers t2 
WHERE t1.user_id = t2.user_id 
  AND t1.id > t2.id;

-- 1.2 Remove duplicate teacher_schedules rows
DELETE t1 FROM teacher_schedules t1
INNER JOIN teacher_schedules t2 
WHERE t1.teacher_id = t2.teacher_id 
  AND t1.day_of_week = t2.day_of_week
  AND t1.start_time = t2.start_time
  AND t1.end_time = t2.end_time
  AND t1.class_id = t2.class_id
  AND t1.id > t2.id;

-- 1.3 Remove duplicate subjects (keep first occurrence of each id)
DELETE s1 FROM subjects s1
INNER JOIN subjects s2 
WHERE s1.id = s2.id 
  AND s1.name = s2.name
  AND s1.code = s2.code
  AND s1.created_at > s2.created_at;

-- 1.4 Remove duplicate users (keep first occurrence)
DELETE u1 FROM users u1
INNER JOIN users u2 
WHERE u1.id = u2.id 
  AND u1.email = u2.email
  AND u1.created_at > u2.created_at;

-- 1.5 Remove duplicate user_requests (keep first occurrence)
DELETE ur1 FROM user_requests ur1
INNER JOIN user_requests ur2 
WHERE ur1.id = ur2.id 
  AND ur1.user_id = ur2.user_id
  AND ur1.requested_at > ur2.requested_at;

-- 1.6 Remove duplicate audit_logs_backup (keep first occurrence)
DELETE a1 FROM audit_logs_backup a1
INNER JOIN audit_logs_backup a2 
WHERE a1.id = a2.id 
  AND a1.created_at > a2.created_at;

-- 1.7 Fix invalid class_id = 0 in classes table (delete or update)
-- Check if class_id=0 is referenced, if so, we'll handle it later
DELETE FROM classes WHERE id = 0;

-- 1.8 Fix invalid grades with student_id = 0 or id = 0
DELETE FROM grades WHERE id = 0 OR student_id = 0;

-- 1.9 Fix invalid teacher_schedules with id = 0 or class_id = 0
DELETE FROM teacher_schedules WHERE id = 0;
-- Update class_id = 0 to NULL (if class doesn't exist, set to NULL)
UPDATE teacher_schedules SET class_id = NULL WHERE class_id = 0;
UPDATE teacher_schedules SET class_id = NULL WHERE class_id IS NOT NULL AND class_id NOT IN (SELECT id FROM classes);

-- 1.10 Remove orphaned grades (student_id doesn't exist in students)
DELETE FROM grades WHERE student_id NOT IN (SELECT id FROM students);

-- 1.11 Remove orphaned grades (teacher_id doesn't exist in teachers)
DELETE FROM grades WHERE teacher_id NOT IN (SELECT id FROM teachers);

-- 1.12 Remove orphaned grades (section_id doesn't exist in sections)
DELETE FROM grades WHERE section_id NOT IN (SELECT id FROM sections);

-- 1.13 Remove orphaned grades (subject_id doesn't exist in subjects)
DELETE FROM grades WHERE subject_id NOT IN (SELECT id FROM subjects);

-- 1.14 Remove orphaned attendance records
DELETE FROM attendance WHERE student_id NOT IN (SELECT id FROM students);
DELETE FROM attendance WHERE teacher_id NOT IN (SELECT id FROM teachers);
DELETE FROM attendance WHERE section_id NOT IN (SELECT id FROM sections);
DELETE FROM attendance WHERE subject_id NOT IN (SELECT id FROM subjects);

-- 1.15 Remove orphaned classes
DELETE FROM classes WHERE section_id NOT IN (SELECT id FROM sections);
DELETE FROM classes WHERE subject_id NOT IN (SELECT id FROM subjects);
DELETE FROM classes WHERE teacher_id NOT IN (SELECT id FROM teachers);

-- 1.16 Remove orphaned student_classes
DELETE FROM student_classes WHERE student_id NOT IN (SELECT id FROM students);
DELETE FROM student_classes WHERE class_id NOT IN (SELECT id FROM classes);

-- 1.17 Remove orphaned teacher_schedules
DELETE FROM teacher_schedules WHERE teacher_id NOT IN (SELECT id FROM teachers);
DELETE FROM teacher_schedules WHERE class_id IS NOT NULL AND class_id NOT IN (SELECT id FROM classes);

-- 1.18 Remove orphaned assignments
DELETE FROM assignments WHERE teacher_id NOT IN (SELECT id FROM teachers);
DELETE FROM assignments WHERE section_id NOT IN (SELECT id FROM sections);
DELETE FROM assignments WHERE subject_id NOT IN (SELECT id FROM subjects);

-- 1.19 Remove orphaned students
DELETE FROM students WHERE user_id NOT IN (SELECT id FROM users);

-- 1.20 Remove orphaned teachers
DELETE FROM teachers WHERE user_id NOT IN (SELECT id FROM users);

-- 1.21 Fix sections with invalid adviser_id
UPDATE sections SET adviser_id = NULL WHERE adviser_id IS NOT NULL AND adviser_id NOT IN (SELECT id FROM users);

-- 1.22 Fix users with invalid approved_by
UPDATE users SET approved_by = NULL WHERE approved_by IS NOT NULL AND approved_by NOT IN (SELECT id FROM users);

-- 1.23 Fix users with invalid linked_student_user_id
UPDATE users SET linked_student_user_id = NULL WHERE linked_student_user_id IS NOT NULL AND linked_student_user_id NOT IN (SELECT id FROM users);

-- 1.24 Fix audit_logs with invalid user_id
UPDATE audit_logs SET user_id = NULL WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users);

-- ============================================================================
-- SECTION 2: FIX AUTO_INCREMENT SETTINGS
-- ============================================================================

-- 2.1 Fix AUTO_INCREMENT for all primary key tables
ALTER TABLE `assignments` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `attendance` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `audit_logs` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `classes` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `grades` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `sections` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `students` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `student_classes` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `subjects` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `teachers` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `teacher_schedules` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_requests` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ============================================================================
-- SECTION 3: ADD MISSING FOREIGN KEY CONSTRAINTS
-- ============================================================================

-- 3.1 Drop existing foreign keys from attendance table (if they exist)
-- These may already exist from previous schema, we'll recreate them with proper names
-- Note: If constraints don't exist, these will error - that's OK, we'll recreate them anyway
-- For safety, wrap in error handling or run manually if needed

-- 3.2 Students → Users (CASCADE: if user deleted, student deleted)
ALTER TABLE `students` 
  ADD CONSTRAINT `fk_students_user` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.3 Teachers → Users (CASCADE: if user deleted, teacher deleted)
ALTER TABLE `teachers` 
  ADD CONSTRAINT `fk_teachers_user` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.4 Sections → Users (SET NULL: if adviser user deleted, set adviser_id to NULL)
ALTER TABLE `sections` 
  ADD CONSTRAINT `fk_sections_adviser` 
  FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- 3.5 Users → Users (self-reference for approved_by)
ALTER TABLE `users` 
  ADD CONSTRAINT `fk_users_approved_by` 
  FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- 3.6 Users → Users (self-reference for linked_student_user_id)
ALTER TABLE `users` 
  ADD CONSTRAINT `fk_users_linked_student` 
  FOREIGN KEY (`linked_student_user_id`) REFERENCES `users` (`id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- 3.7 Audit Logs → Users (SET NULL: preserve audit trail even if user deleted)
ALTER TABLE `audit_logs` 
  ADD CONSTRAINT `fk_audit_logs_user` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- 3.8 User Requests → Users (CASCADE: if user deleted, requests deleted)
ALTER TABLE `user_requests` 
  ADD CONSTRAINT `fk_user_requests_user` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.9 User Requests → Users (SET NULL: preserve request even if processor deleted)
ALTER TABLE `user_requests` 
  ADD CONSTRAINT `fk_user_requests_processed_by` 
  FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- 3.10 Students → Sections (SET NULL: if section deleted, student can be reassigned)
ALTER TABLE `students` 
  ADD CONSTRAINT `fk_students_section` 
  FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- 3.11 Classes → Sections (RESTRICT: cannot delete section with active classes)
ALTER TABLE `classes` 
  ADD CONSTRAINT `fk_classes_section` 
  FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) 
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;

-- 3.12 Classes → Subjects (RESTRICT: cannot delete subject with active classes)
ALTER TABLE `classes` 
  ADD CONSTRAINT `fk_classes_subject` 
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) 
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;

-- 3.13 Classes → Teachers (RESTRICT: cannot delete teacher with active classes)
ALTER TABLE `classes` 
  ADD CONSTRAINT `fk_classes_teacher` 
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) 
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;

-- 3.14 Student Classes → Students (CASCADE: if student deleted, enrollments deleted)
ALTER TABLE `student_classes` 
  ADD CONSTRAINT `fk_student_classes_student` 
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.15 Student Classes → Classes (CASCADE: if class deleted, enrollments deleted)
ALTER TABLE `student_classes` 
  ADD CONSTRAINT `fk_student_classes_class` 
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.16 Teacher Schedules → Teachers (CASCADE: if teacher deleted, schedules deleted)
ALTER TABLE `teacher_schedules` 
  ADD CONSTRAINT `fk_teacher_schedules_teacher` 
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.17 Teacher Schedules → Classes (SET NULL: if class deleted, schedule can remain for reference)
ALTER TABLE `teacher_schedules` 
  ADD CONSTRAINT `fk_teacher_schedules_class` 
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- 3.18 Grades → Students (CASCADE: if student deleted, grades deleted)
ALTER TABLE `grades` 
  ADD CONSTRAINT `fk_grades_student` 
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.19 Grades → Sections (RESTRICT: cannot delete section with grades)
ALTER TABLE `grades` 
  ADD CONSTRAINT `fk_grades_section` 
  FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) 
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;

-- 3.20 Grades → Subjects (RESTRICT: cannot delete subject with grades)
ALTER TABLE `grades` 
  ADD CONSTRAINT `fk_grades_subject` 
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) 
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;

-- 3.21 Grades → Teachers (RESTRICT: cannot delete teacher with grades)
ALTER TABLE `grades` 
  ADD CONSTRAINT `fk_grades_teacher` 
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) 
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;

-- 3.22 Attendance → Students (CASCADE: if student deleted, attendance deleted)
-- Note: If constraint already exists, it will error - manually drop first if needed:
-- ALTER TABLE `attendance` DROP FOREIGN KEY `attendance_student_fk`;
ALTER TABLE `attendance` 
  ADD CONSTRAINT `attendance_student_fk` 
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.23 Attendance → Teachers (CASCADE: if teacher deleted, attendance deleted)
-- Note: If constraint already exists, it will error - manually drop first if needed:
-- ALTER TABLE `attendance` DROP FOREIGN KEY `attendance_teacher_fk`;
ALTER TABLE `attendance` 
  ADD CONSTRAINT `attendance_teacher_fk` 
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.24 Attendance → Sections (CASCADE: if section deleted, attendance deleted)
-- Note: If constraint already exists, it will error - manually drop first if needed:
-- ALTER TABLE `attendance` DROP FOREIGN KEY `attendance_section_fk`;
ALTER TABLE `attendance` 
  ADD CONSTRAINT `attendance_section_fk` 
  FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.25 Attendance → Subjects (CASCADE: if subject deleted, attendance deleted)
-- Note: If constraint already exists, it will error - manually drop first if needed:
-- ALTER TABLE `attendance` DROP FOREIGN KEY `attendance_subject_fk`;
ALTER TABLE `attendance` 
  ADD CONSTRAINT `attendance_subject_fk` 
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.26 Assignments → Teachers (CASCADE: if teacher deleted, assignments deleted)
ALTER TABLE `assignments` 
  ADD CONSTRAINT `fk_assignments_teacher` 
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.27 Assignments → Sections (CASCADE: if section deleted, assignments deleted)
ALTER TABLE `assignments` 
  ADD CONSTRAINT `fk_assignments_section` 
  FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- 3.28 Assignments → Subjects (CASCADE: if subject deleted, assignments deleted)
ALTER TABLE `assignments` 
  ADD CONSTRAINT `fk_assignments_subject` 
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- ============================================================================
-- SECTION 4: FIX BROKEN VIEWS
-- ============================================================================

-- 4.1 Drop broken views
DROP VIEW IF EXISTS `final_grades_view`;
DROP VIEW IF EXISTS `quarterly_grades_view`;
DROP VIEW IF EXISTS `student_profiles`;

-- 4.2 Recreate quarterly_grades_view (fixes the double academic_year in GROUP BY)
CREATE OR REPLACE VIEW `quarterly_grades_view` AS
SELECT 
    `g`.`student_id` AS `student_id`,
    `g`.`section_id` AS `section_id`,
    `g`.`subject_id` AS `subject_id`,
    `g`.`quarter` AS `quarter`,
    `g`.`academic_year` AS `academic_year`,
    AVG(CASE WHEN `g`.`grade_type` = 'ww' THEN (`g`.`grade_value` / NULLIF(`g`.`max_score`, 0)) * 100 ELSE NULL END) AS `ww_average`,
    AVG(CASE WHEN `g`.`grade_type` = 'pt' THEN (`g`.`grade_value` / NULLIF(`g`.`max_score`, 0)) * 100 ELSE NULL END) AS `pt_average`,
    AVG(CASE WHEN `g`.`grade_type` = 'qe' THEN (`g`.`grade_value` / NULLIF(`g`.`max_score`, 0)) * 100 ELSE NULL END) AS `qe_average`,
    COUNT(CASE WHEN `g`.`grade_type` = 'ww' THEN 1 END) AS `ww_count`,
    COUNT(CASE WHEN `g`.`grade_type` = 'pt' THEN 1 END) AS `pt_count`,
    COUNT(CASE WHEN `g`.`grade_type` = 'qe' THEN 1 END) AS `qe_count`
FROM `grades` AS `g`
GROUP BY `g`.`student_id`, `g`.`section_id`, `g`.`subject_id`, `g`.`quarter`, `g`.`academic_year`;

-- 4.3 Recreate final_grades_view
CREATE OR REPLACE VIEW `final_grades_view` AS
SELECT 
    `qg`.`student_id` AS `student_id`,
    `qg`.`section_id` AS `section_id`,
    `qg`.`subject_id` AS `subject_id`,
    `qg`.`quarter` AS `quarter`,
    `qg`.`academic_year` AS `academic_year`,
    `qg`.`ww_average` AS `ww_average`,
    `qg`.`pt_average` AS `pt_average`,
    `qg`.`qe_average` AS `qe_average`,
    `s`.`ww_percent` AS `ww_percent`,
    `s`.`pt_percent` AS `pt_percent`,
    `s`.`qe_percent` AS `qe_percent`,
    `s`.`attendance_percent` AS `attendance_percent`,
    ROUND(
        COALESCE(`qg`.`ww_average`, 0) * COALESCE(`s`.`ww_percent`, 20) / 100 + 
        COALESCE(`qg`.`pt_average`, 0) * COALESCE(`s`.`pt_percent`, 50) / 100 + 
        COALESCE(`qg`.`qe_average`, 0) * COALESCE(`s`.`qe_percent`, 20) / 100,
        2
    ) AS `final_grade_without_attendance`,
    CASE 
        WHEN ROUND(
            COALESCE(`qg`.`ww_average`, 0) * COALESCE(`s`.`ww_percent`, 20) / 100 + 
            COALESCE(`qg`.`pt_average`, 0) * COALESCE(`s`.`pt_percent`, 50) / 100 + 
            COALESCE(`qg`.`qe_average`, 0) * COALESCE(`s`.`qe_percent`, 20) / 100,
            2
        ) >= 75 THEN 'Passed' 
        ELSE 'Failed' 
    END AS `status_without_attendance`
FROM `quarterly_grades_view` `qg`
JOIN `subjects` `s` ON `qg`.`subject_id` = `s`.`id`;

-- 4.4 Recreate student_profiles view (fixes the malformed WHERE clause)
CREATE OR REPLACE VIEW `student_profiles` AS
SELECT 
    `s`.`id` AS `student_id`,
    `s`.`user_id` AS `user_id`,
    `u`.`email` AS `email`,
    `u`.`name` AS `full_name`,
    `u`.`status` AS `user_status`,
    `u`.`created_at` AS `user_created_at`,
    `u`.`updated_at` AS `user_updated_at`,
    `s`.`lrn` AS `lrn`,
    `s`.`first_name` AS `first_name`,
    `s`.`last_name` AS `last_name`,
    `s`.`middle_name` AS `middle_name`,
    CONCAT(
        COALESCE(`s`.`first_name`, ''),
        CASE 
            WHEN `s`.`middle_name` IS NOT NULL AND `s`.`middle_name` <> '' 
            THEN CONCAT(' ', `s`.`middle_name`) 
            ELSE '' 
        END,
        CASE 
            WHEN `s`.`last_name` IS NOT NULL AND `s`.`last_name` <> '' 
            THEN CONCAT(' ', `s`.`last_name`) 
            ELSE '' 
        END
    ) AS `full_name_display`,
    `s`.`birth_date` AS `birth_date`,
    `s`.`gender` AS `gender`,
    `s`.`contact_number` AS `contact_number`,
    `s`.`address` AS `address`,
    `s`.`guardian_name` AS `guardian_name`,
    `s`.`guardian_contact` AS `guardian_contact`,
    `s`.`guardian_relationship` AS `guardian_relationship`,
    `s`.`grade_level` AS `grade_level`,
    `s`.`section_id` AS `section_id`,
    `sec`.`name` AS `section_name`,
    `sec`.`room` AS `section_room`,
    `s`.`school_year` AS `school_year`,
    `s`.`enrollment_status` AS `enrollment_status`,
    `s`.`previous_school` AS `previous_school`,
    `s`.`medical_conditions` AS `medical_conditions`,
    `s`.`allergies` AS `allergies`,
    `s`.`emergency_contact_name` AS `emergency_contact_name`,
    `s`.`emergency_contact_number` AS `emergency_contact_number`,
    `s`.`emergency_contact_relationship` AS `emergency_contact_relationship`,
    `s`.`profile_picture` AS `profile_picture`,
    `s`.`notes` AS `notes`,
    `s`.`date_enrolled` AS `date_enrolled`,
    `s`.`date_graduated` AS `date_graduated`,
    `s`.`status` AS `student_status`,
    `s`.`created_at` AS `created_at`,
    `s`.`updated_at` AS `updated_at`
FROM `students` `s`
LEFT JOIN `users` `u` ON `s`.`user_id` = `u`.`id`
LEFT JOIN `sections` `sec` ON `s`.`section_id` = `sec`.`id`
WHERE `u`.`role` = 'student';

-- ============================================================================
-- SECTION 5: RE-ENABLE CONSTRAINTS AND FINAL SETTINGS
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- ============================================================================
-- END OF SCHEMA FIX SCRIPT
-- ============================================================================

