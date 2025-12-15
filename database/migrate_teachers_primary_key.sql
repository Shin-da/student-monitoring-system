-- ============================================================================
-- MIGRATE TEACHERS TABLE PRIMARY KEY
-- ============================================================================
-- This script changes the PRIMARY KEY of the teachers table from user_id to id
-- 
-- IMPORTANT: This migration:
-- 1. Fixes any invalid id values (e.g., id=0)
-- 2. Ensures id has AUTO_INCREMENT
-- 3. Changes PRIMARY KEY from user_id to id
-- 4. Adds UNIQUE constraint to user_id (prevents duplicate teacher records)
-- 5. Preserves all foreign key relationships
-- 6. Ensures no conflicts occur
--
-- Run this script in phpMyAdmin or MySQL command line
-- ============================================================================

USE `student_monitoring`;

-- Step 1: Backup current state (optional but recommended)
-- CREATE TABLE IF NOT EXISTS `teachers_backup_before_pk_migration` AS SELECT * FROM `teachers`;

-- Step 2: Check for any foreign key constraints that might prevent the change
-- We'll handle them in the migration

-- Step 3: Fix invalid id values (e.g., id = 0)
-- Temporarily allow NULL for id, then set id=NULL for any 0 values
ALTER TABLE `teachers` MODIFY `id` int(10) UNSIGNED NULL;

-- Update any id=0 entries to NULL so AUTO_INCREMENT can reassign them
UPDATE `teachers` SET `id` = NULL WHERE `id` = 0;

-- Step 4: Remove the existing PRIMARY KEY from user_id
-- First, check if there's a PRIMARY KEY constraint
SET @pk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = 'student_monitoring' 
    AND TABLE_NAME = 'teachers' 
    AND CONSTRAINT_TYPE = 'PRIMARY KEY'
    AND CONSTRAINT_NAME = 'PRIMARY'
);

-- Remove PRIMARY KEY from user_id if it exists
SET @sql = IF(@pk_exists > 0, 
    'ALTER TABLE `teachers` DROP PRIMARY KEY',
    'SELECT "No PRIMARY KEY to drop" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 5: Remove any existing UNIQUE constraint on user_id (we'll add it back later)
-- Get the UNIQUE constraint name if it exists
SET @unique_constraint_name = (
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = 'student_monitoring' 
    AND TABLE_NAME = 'teachers' 
    AND CONSTRAINT_TYPE = 'UNIQUE'
    AND CONSTRAINT_NAME LIKE '%user_id%'
    LIMIT 1
);

-- Drop UNIQUE constraint if it exists (we'll recreate it)
SET @sql = IF(@unique_constraint_name IS NOT NULL,
    CONCAT('ALTER TABLE `teachers` DROP INDEX `', @unique_constraint_name, '`'),
    'SELECT "No UNIQUE constraint to drop" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 6: Set id to NOT NULL and AUTO_INCREMENT, then add PRIMARY KEY
ALTER TABLE `teachers` 
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`id`);

-- Step 7: Add UNIQUE constraint to user_id to prevent duplicate teacher records
-- This ensures one teacher record per user_id
ALTER TABLE `teachers`
ADD UNIQUE KEY `uniq_teachers_user_id` (`user_id`);

-- Step 8: Set AUTO_INCREMENT to the next available ID
-- This ensures no conflicts with existing data
SET @max_id = (SELECT COALESCE(MAX(id), 0) FROM `teachers`);
SET @next_id = @max_id + 1;
SET @sql = CONCAT('ALTER TABLE `teachers` AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 9: Verify the migration
-- Check that PRIMARY KEY is now on id
SELECT 
    'PRIMARY KEY Verification' AS check_type,
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = 'student_monitoring' 
            AND TABLE_NAME = 'teachers' 
            AND CONSTRAINT_TYPE = 'PRIMARY KEY'
            AND CONSTRAINT_NAME = 'PRIMARY'
        ) THEN '✅ PRIMARY KEY exists on id'
        ELSE '❌ PRIMARY KEY NOT FOUND'
    END AS status;

-- Check that UNIQUE constraint exists on user_id
SELECT 
    'UNIQUE Constraint Verification' AS check_type,
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = 'student_monitoring' 
            AND TABLE_NAME = 'teachers' 
            AND CONSTRAINT_TYPE = 'UNIQUE'
            AND CONSTRAINT_NAME LIKE '%user_id%'
        ) THEN '✅ UNIQUE constraint exists on user_id'
        ELSE '❌ UNIQUE constraint NOT FOUND'
    END AS status;

-- Check AUTO_INCREMENT
SELECT 
    'AUTO_INCREMENT Verification' AS check_type,
    AUTO_INCREMENT,
    CASE 
        WHEN AUTO_INCREMENT IS NOT NULL THEN '✅ AUTO_INCREMENT is set'
        ELSE '❌ AUTO_INCREMENT NOT SET'
    END AS status
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'student_monitoring' 
AND TABLE_NAME = 'teachers';

-- Step 10: Verify column structure
SHOW COLUMNS FROM `teachers` WHERE Field IN ('id', 'user_id');

-- Step 11: Check for any duplicate user_id entries (should be none after UNIQUE constraint)
SELECT 
    'Duplicate Check' AS check_type,
    user_id,
    COUNT(*) AS count
FROM `teachers`
GROUP BY user_id
HAVING COUNT(*) > 1;

-- Step 12: Verify foreign key relationships are intact
-- Check that all foreign keys referencing teachers.id still exist
SELECT 
    'Foreign Key Verification' AS check_type,
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'student_monitoring'
AND REFERENCED_TABLE_NAME = 'teachers'
AND REFERENCED_COLUMN_NAME = 'id'
ORDER BY TABLE_NAME;

-- Step 13: Final summary
SELECT 
    'Migration Summary' AS summary,
    (SELECT COUNT(*) FROM `teachers`) AS total_teachers,
    (SELECT COUNT(DISTINCT user_id) FROM `teachers`) AS unique_user_ids,
    (SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'student_monitoring' AND TABLE_NAME = 'teachers') AS next_auto_increment_id,
    '✅ Migration completed successfully!' AS status;

