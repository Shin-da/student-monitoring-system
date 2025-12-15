-- =====================================================
-- FIX UNIQUE_CLASS_ASSIGNMENT CONSTRAINT
-- =====================================================
-- This script fixes the unique_class_assignment constraint
-- to allow multiple classes with different subjects for the same section
-- 
-- Issue: The current constraint likely doesn't include subject_id,
-- preventing multiple subjects from being assigned to the same section
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- STEP 1: Check if the constraint exists and drop it
-- =====================================================

-- Drop the existing unique_class_assignment constraint if it exists
-- Note: The constraint might be on (section_id, grade_level, semester, school_year)
-- We need to find it first

-- First, let's check what constraints exist on the classes table
-- (This is for reference - MySQL doesn't support IF EXISTS for ALTER TABLE DROP INDEX directly)

-- Try to drop the constraint if it exists
-- Note: This may fail if the constraint doesn't exist, which is okay
SET @constraint_exists = (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE table_schema = DATABASE() 
    AND table_name = 'classes' 
    AND index_name = 'unique_class_assignment'
);

-- If constraint exists, drop it
SET @sql = IF(@constraint_exists > 0,
    'ALTER TABLE `classes` DROP INDEX `unique_class_assignment`',
    'SELECT "Constraint unique_class_assignment does not exist" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- STEP 2: Create a new unique constraint that includes subject_id
-- =====================================================

-- Create a new unique constraint that includes subject_id
-- This allows multiple classes with different subjects for the same section
-- but prevents duplicate classes with the same section, subject, semester, and school_year

-- First, check if a similar constraint already exists
SET @new_constraint_exists = (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE table_schema = DATABASE() 
    AND table_name = 'classes' 
    AND index_name = 'unique_class_section_subject'
);

-- Create the new constraint only if it doesn't exist
SET @sql2 = IF(@new_constraint_exists = 0,
    'ALTER TABLE `classes` ADD UNIQUE KEY `unique_class_section_subject` (`section_id`, `subject_id`, `semester`, `school_year`)',
    'SELECT "Constraint unique_class_section_subject already exists" AS message'
);

PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- =====================================================
-- VERIFICATION
-- =====================================================

-- Show the current constraints on the classes table
SELECT 
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS COLUMNS
FROM information_schema.STATISTICS
WHERE table_schema = DATABASE()
AND table_name = 'classes'
AND INDEX_NAME LIKE 'unique%'
GROUP BY INDEX_NAME;

COMMIT;

-- =====================================================
-- NOTES
-- =====================================================
-- 
-- After running this script:
-- 1. You should be able to create multiple classes with different subjects
--    for the same section, semester, and school year
-- 2. You will still not be able to create duplicate classes with the exact
--    same section, subject, semester, and school year combination
-- 3. If you encounter any issues, you can manually run:
--    
--    -- Drop old constraint:
--    ALTER TABLE `classes` DROP INDEX `unique_class_assignment`;
--    
--    -- Add new constraint:
--    ALTER TABLE `classes` ADD UNIQUE KEY `unique_class_section_subject` 
--        (`section_id`, `subject_id`, `semester`, `school_year`);
-- 
-- =====================================================
