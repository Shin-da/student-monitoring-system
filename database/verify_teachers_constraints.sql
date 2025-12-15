-- ============================================================================
-- VERIFY TEACHERS TABLE CONSTRAINTS
-- ============================================================================
-- This script verifies that the teachers table has the correct constraints
-- after the PRIMARY KEY migration
-- ============================================================================

USE `student_monitoring`;

-- Check PRIMARY KEY
SELECT 
    'PRIMARY KEY Check' AS check_type,
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

-- Check UNIQUE constraint on user_id
SELECT 
    'UNIQUE Constraint Check' AS check_type,
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = 'student_monitoring' 
            AND TABLE_NAME = 'teachers' 
            AND CONSTRAINT_TYPE = 'UNIQUE'
            AND CONSTRAINT_NAME LIKE '%user_id%'
        ) THEN '✅ UNIQUE constraint exists on user_id'
        ELSE '❌ UNIQUE constraint NOT FOUND on user_id'
    END AS status;

-- Show all constraints on teachers table
SELECT 
    CONSTRAINT_NAME,
    CONSTRAINT_TYPE,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'student_monitoring'
AND TABLE_NAME = 'teachers'
ORDER BY CONSTRAINT_TYPE, CONSTRAINT_NAME;

-- Show table structure
SHOW CREATE TABLE `teachers`;

-- Check AUTO_INCREMENT
SELECT 
    'AUTO_INCREMENT Check' AS check_type,
    AUTO_INCREMENT,
    CASE 
        WHEN AUTO_INCREMENT IS NOT NULL THEN '✅ AUTO_INCREMENT is set'
        ELSE '❌ AUTO_INCREMENT NOT SET'
    END AS status
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'student_monitoring' 
AND TABLE_NAME = 'teachers';

