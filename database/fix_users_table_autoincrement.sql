-- ============================================================================
-- Fix Users Table AUTO_INCREMENT
-- ============================================================================
-- This script ensures the users table has proper AUTO_INCREMENT on the id column
-- AUTO_INCREMENT is ESSENTIAL to prevent ID conflicts and ensure accurate data saving
--
-- Why AUTO_INCREMENT is critical:
-- 1. Prevents duplicate ID conflicts
-- 2. Ensures unique sequential IDs
-- 3. Allows lastInsertId() to work correctly
-- 4. Prevents manual ID assignment errors
-- 5. Maintains data integrity
--
-- Run this script in phpMyAdmin or MySQL command line
-- ============================================================================

USE `student_monitoring`;

-- Step 1: Check current structure (for reference)
-- Uncomment to see current state:
-- SELECT COLUMN_NAME, COLUMN_TYPE, EXTRA 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'student_monitoring' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'id';

-- Step 2: Ensure PRIMARY KEY exists first (required for AUTO_INCREMENT)
ALTER TABLE `users` 
ADD PRIMARY KEY (`id`) IF NOT EXISTS;

-- Step 3: Set id column to AUTO_INCREMENT
-- This will work even if the column already has AUTO_INCREMENT
ALTER TABLE `users` 
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- Step 4: Set AUTO_INCREMENT to the next available ID
-- This ensures no conflicts with existing data
-- Find the maximum ID currently in use
SET @max_id = (SELECT COALESCE(MAX(id), 0) FROM `users`);

-- Set AUTO_INCREMENT to start from the next available ID
SET @next_id = @max_id + 1;
SET @sql = CONCAT('ALTER TABLE `users` AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 5: Verify the fix
-- Check that AUTO_INCREMENT is properly set
SELECT 
    TABLE_NAME,
    AUTO_INCREMENT,
    CASE 
        WHEN AUTO_INCREMENT IS NOT NULL THEN '✅ AUTO_INCREMENT is set correctly'
        ELSE '❌ AUTO_INCREMENT is NOT set'
    END AS status
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'student_monitoring' 
AND TABLE_NAME = 'users';

-- Step 6: Verify column structure
-- This should show 'auto_increment' in the Extra column
SHOW COLUMNS FROM `users` WHERE Field = 'id';

-- Step 7: Test query (optional - shows next ID that will be assigned)
SELECT 
    'Next user ID will be:' AS info,
    AUTO_INCREMENT AS next_id
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'student_monitoring' 
AND TABLE_NAME = 'users';

