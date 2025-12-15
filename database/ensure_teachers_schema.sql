-- ============================================================================
-- ENSURE TEACHERS TABLE SCHEMA IS CORRECT
-- ============================================================================
-- This script ensures the teachers table has the correct schema for reliable
-- teacher account creation. Run this if teacher accounts are not being saved.
-- ============================================================================

USE `student_monitoring`;

-- Step 1: Check current PRIMARY KEY
SELECT 
    'Current PRIMARY KEY Check' AS check_type,
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = 'student_monitoring' 
            AND TABLE_NAME = 'teachers' 
            AND CONSTRAINT_TYPE = 'PRIMARY KEY'
            AND CONSTRAINT_NAME = 'PRIMARY'
        ) THEN 'PRIMARY KEY exists'
        ELSE 'NO PRIMARY KEY'
    END AS status;

-- Step 2: Check if id column has AUTO_INCREMENT
SELECT 
    'AUTO_INCREMENT Check' AS check_type,
    COLUMN_NAME,
    EXTRA,
    CASE 
        WHEN EXTRA LIKE '%auto_increment%' THEN '✅ AUTO_INCREMENT is set'
        ELSE '❌ AUTO_INCREMENT NOT SET'
    END AS status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'student_monitoring'
AND TABLE_NAME = 'teachers'
AND COLUMN_NAME = 'id';

-- Step 3: Check UNIQUE constraint on user_id
SELECT 
    'UNIQUE Constraint Check' AS check_type,
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
            JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
                ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE tc.TABLE_SCHEMA = 'student_monitoring'
            AND tc.TABLE_NAME = 'teachers'
            AND tc.CONSTRAINT_TYPE = 'UNIQUE'
            AND kcu.COLUMN_NAME = 'user_id'
        ) THEN '✅ UNIQUE constraint exists on user_id'
        ELSE '❌ UNIQUE constraint NOT FOUND on user_id'
    END AS status;

-- Step 4: Fix invalid id values (id = 0)
UPDATE `teachers` SET `id` = NULL WHERE `id` = 0;

-- Step 5: Ensure id is AUTO_INCREMENT PRIMARY KEY
-- First, check if PRIMARY KEY is on user_id (old schema)
SET @pk_on_user_id = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'student_monitoring'
    AND TABLE_NAME = 'teachers'
    AND CONSTRAINT_NAME = 'PRIMARY'
    AND COLUMN_NAME = 'user_id'
);

-- If PRIMARY KEY is on user_id, we need to migrate it
SET @sql = IF(@pk_on_user_id > 0,
    'ALTER TABLE `teachers` DROP PRIMARY KEY',
    'SELECT "PRIMARY KEY is not on user_id" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure id is AUTO_INCREMENT and PRIMARY KEY
ALTER TABLE `teachers` 
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`id`);

-- Step 6: Add UNIQUE constraint on user_id if it doesn't exist
SET @unique_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
    JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
        ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
    WHERE tc.TABLE_SCHEMA = 'student_monitoring'
    AND tc.TABLE_NAME = 'teachers'
    AND tc.CONSTRAINT_TYPE = 'UNIQUE'
    AND kcu.COLUMN_NAME = 'user_id'
);

SET @sql = IF(@unique_exists = 0,
    'ALTER TABLE `teachers` ADD UNIQUE KEY `uniq_teachers_user_id` (`user_id`)',
    'SELECT "UNIQUE constraint already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 7: Set AUTO_INCREMENT to next available ID
SET @max_id = (SELECT COALESCE(MAX(id), 0) FROM `teachers`);
SET @next_id = @max_id + 1;
SET @sql = CONCAT('ALTER TABLE `teachers` AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 8: Verify final state
SELECT 
    'Final Verification' AS check_type,
    'PRIMARY KEY on id' AS check_item,
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = 'student_monitoring'
            AND TABLE_NAME = 'teachers'
            AND CONSTRAINT_NAME = 'PRIMARY'
            AND COLUMN_NAME = 'id'
        ) THEN '✅ PASS'
        ELSE '❌ FAIL'
    END AS status
UNION ALL
SELECT 
    'Final Verification',
    'UNIQUE constraint on user_id',
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
            JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
                ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE tc.TABLE_SCHEMA = 'student_monitoring'
            AND tc.TABLE_NAME = 'teachers'
            AND tc.CONSTRAINT_TYPE = 'UNIQUE'
            AND kcu.COLUMN_NAME = 'user_id'
        ) THEN '✅ PASS'
        ELSE '❌ FAIL'
    END
UNION ALL
SELECT 
    'Final Verification',
    'AUTO_INCREMENT on id',
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = 'student_monitoring'
            AND TABLE_NAME = 'teachers'
            AND COLUMN_NAME = 'id'
            AND EXTRA LIKE '%auto_increment%'
        ) THEN '✅ PASS'
        ELSE '❌ FAIL'
    END;

-- Step 9: Show final table structure
SHOW CREATE TABLE `teachers`;

