-- Centralization Migration for Student Monitoring System
-- Run this script against the `student_monitoring` database

SET FOREIGN_KEY_CHECKS = 0;

-- 1) Add Admin Ownership Tracking on sections
ALTER TABLE sections
    ADD COLUMN IF NOT EXISTS created_by INT(10) UNSIGNED NULL AFTER adviser_id;

-- MySQL prior to 8.0.19 doesn't support IF NOT EXISTS for FKs; drop if exists then create
SET @fk_name := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sections' AND COLUMN_NAME = 'created_by' 
                 AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1);
SET @sql := IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE sections DROP FOREIGN KEY ', @fk_name), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE sections
    ADD CONSTRAINT sections_created_by_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- 2) Strengthen Data Integrity for students -> sections
-- Drop existing FK if any then recreate with desired actions
SET @fk_name2 := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'section_id' 
                  AND REFERENCED_TABLE_NAME = 'sections' LIMIT 1);
SET @sql2 := IF(@fk_name2 IS NOT NULL, CONCAT('ALTER TABLE students DROP FOREIGN KEY ', @fk_name2), 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

ALTER TABLE students
    ADD CONSTRAINT students_section_fk FOREIGN KEY (section_id)
        REFERENCES sections(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE;

-- 3) Views for centralization
DROP VIEW IF EXISTS student_profiles;
CREATE VIEW student_profiles AS
SELECT 
    s.id AS student_id,
    s.user_id,
    u.email,
    u.name AS full_name,
    u.status AS user_status,
    s.lrn,
    s.first_name,
    s.last_name,
    s.middle_name,
    CONCAT(s.first_name, ' ', IFNULL(s.middle_name, ''), ' ', s.last_name) AS full_name_display,
    s.grade_level,
    s.section_id,
    sec.name AS section_name,
    sec.room AS section_room,
    s.school_year,
    s.enrollment_status,
    s.status AS student_status,
    s.created_at,
    s.updated_at
FROM students s
JOIN users u ON s.user_id = u.id
JOIN sections sec ON s.section_id = sec.id
WHERE u.role = 'student';

DROP VIEW IF EXISTS section_capacity;
CREATE VIEW section_capacity AS
SELECT 
    sec.id AS section_id,
    sec.name AS section_name,
    sec.max_students,
    COUNT(st.id) AS current_students,
    (sec.max_students - COUNT(st.id)) AS available_slots
FROM sections sec
LEFT JOIN students st ON sec.id = st.section_id
GROUP BY sec.id;

-- 4) Future Grades table (no data yet)
CREATE TABLE IF NOT EXISTS grades (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  teacher_id INT UNSIGNED NOT NULL,
  quarter ENUM('1st','2nd','3rd','4th') NOT NULL,
  score DECIMAL(5,2) NOT NULL,
  remarks VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT grades_student_fk FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT grades_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT grades_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- 5) Remove sample data (id > 1 safeguard is project-specific; adjust as needed)
-- Comment out if you prefer manual cleanup:
-- DELETE FROM students;
-- DELETE FROM users WHERE role = 'student';
-- DELETE FROM sections WHERE id > 1;

SET FOREIGN_KEY_CHECKS = 1;


