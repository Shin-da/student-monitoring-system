-- =====================================================
-- TEACHER TABLE REPAIR SCRIPT
-- =====================================================
-- This script fixes issues with the teachers table:
-- 1. Removes duplicate teacher records
-- 2. Ensures AUTO_INCREMENT is set correctly
-- 3. Creates missing teacher records for active teacher/adviser users
-- 4. Fixes any orphaned teacher records
-- =====================================================

-- Step 1: Remove duplicate teacher records (keep the one with the lowest id)
-- This removes duplicates where the same user_id has multiple teacher records
DELETE t1 FROM teachers t1
INNER JOIN teachers t2 
WHERE t1.user_id = t2.user_id 
AND t1.id > t2.id;

-- Step 1b: Fix invalid/duplicate IDs (e.g., id = 0) before enabling AUTO_INCREMENT
-- Temporarily allow NULL ids, then clear any 0 values so MySQL can reassign them
ALTER TABLE `teachers` MODIFY `id` int(10) UNSIGNED NULL;
UPDATE teachers SET id = NULL WHERE id = 0;

-- Step 2: Ensure AUTO_INCREMENT and PRIMARY KEY are set on teachers.id
ALTER TABLE `teachers` 
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (`id`);

-- Step 2b: Enforce one teacher row per user_id
ALTER TABLE `teachers`
ADD UNIQUE KEY `uniq_teachers_user` (`user_id`);

-- Step 3: Create missing teacher records for active teacher/adviser users
-- This ensures every active teacher/adviser user has a corresponding teachers table entry
INSERT INTO teachers (user_id, is_adviser, created_at)
SELECT 
    u.id AS user_id,
    CASE WHEN u.role = 'adviser' THEN 1 ELSE 0 END AS is_adviser,
    NOW() AS created_at
FROM users u
WHERE u.role IN ('teacher', 'adviser')
  AND u.status = 'active'
  AND NOT EXISTS (
      SELECT 1 FROM teachers t WHERE t.user_id = u.id
  );

-- Step 4: Remove orphaned teacher records (teachers without corresponding users)
DELETE FROM teachers
WHERE user_id NOT IN (SELECT id FROM users);

-- Step 5: Verify the repair
-- Check for any remaining duplicates
SELECT 
    user_id, 
    COUNT(*) as count,
    GROUP_CONCAT(id ORDER BY id) as teacher_ids
FROM teachers
GROUP BY user_id
HAVING count > 1;

-- Step 6: Show summary of teachers table
SELECT 
    COUNT(*) as total_teachers,
    COUNT(DISTINCT user_id) as unique_user_ids,
    SUM(CASE WHEN is_adviser = 1 THEN 1 ELSE 0 END) as advisers,
    SUM(CASE WHEN is_adviser = 0 THEN 1 ELSE 0 END) as regular_teachers
FROM teachers;

-- Step 7: Show all teachers with their user info
SELECT 
    t.id as teacher_id,
    t.user_id,
    u.name,
    u.email,
    u.role,
    u.status,
    t.is_adviser,
    t.department,
    t.created_at
FROM teachers t
JOIN users u ON t.user_id = u.id
ORDER BY u.name;

