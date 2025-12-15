-- =====================================================
-- FIX GRADES TABLE AUTO_INCREMENT
-- =====================================================
-- This script fixes the AUTO_INCREMENT issue in the grades table
-- that causes "Duplicate entry '0' for key 'PRIMARY'" errors
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Ensure the id column is AUTO_INCREMENT
ALTER TABLE `grades` 
  MODIFY COLUMN `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;

-- Get the maximum ID from the table (or use 0 if empty)
SELECT COALESCE(MAX(id), 0) INTO @max_id FROM `grades`;

-- Set AUTO_INCREMENT to the next available ID (or 1 if table is empty)
SET @next_id = @max_id + 1;
SET @sql = CONCAT('ALTER TABLE `grades` AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- Verify the fix - shows current AUTO_INCREMENT value
SELECT 
    'grades' AS table_name,
    AUTO_INCREMENT,
    'Fixed: AUTO_INCREMENT is now set correctly' AS status
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'grades';

