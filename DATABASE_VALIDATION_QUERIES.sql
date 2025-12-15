-- ============================================================================
-- POST-FIX VALIDATION QUERIES
-- ============================================================================
-- Run these queries AFTER executing DATABASE_SCHEMA_FIX.sql
-- to verify all relationships are correct and data is clean.
-- ============================================================================

-- ============================================================================
-- 1. VERIFY ALL TEACHERS ARE LINKED TO VALID USERS
-- ============================================================================
SELECT 
    t.id AS teacher_id,
    t.user_id,
    u.id AS user_exists,
    u.role,
    u.name AS user_name,
    CASE 
        WHEN u.id IS NULL THEN '❌ ORPHANED - User does not exist'
        WHEN u.role NOT IN ('teacher', 'adviser') THEN '⚠️ WARNING - User role is not teacher/adviser'
        ELSE '✅ VALID'
    END AS status
FROM teachers t
LEFT JOIN users u ON t.user_id = u.id
ORDER BY t.id;

-- Expected: All rows should show "✅ VALID"
-- If any show "❌ ORPHANED", there's a data integrity issue

-- ============================================================================
-- 2. VERIFY ALL STUDENTS ARE LINKED TO VALID USERS
-- ============================================================================
SELECT 
    s.id AS student_id,
    s.user_id,
    u.id AS user_exists,
    u.role,
    u.name AS user_name,
    CASE 
        WHEN u.id IS NULL THEN '❌ ORPHANED - User does not exist'
        WHEN u.role != 'student' THEN '⚠️ WARNING - User role is not student'
        ELSE '✅ VALID'
    END AS status
FROM students s
LEFT JOIN users u ON s.user_id = u.id
ORDER BY s.id;

-- Expected: All rows should show "✅ VALID"

-- ============================================================================
-- 3. VERIFY ALL CLASSES LINK TO VALID SECTION/SUBJECT/TEACHER
-- ============================================================================
SELECT 
    c.id AS class_id,
    c.section_id,
    c.subject_id,
    c.teacher_id,
    sec.name AS section_name,
    sub.name AS subject_name,
    t.user_id AS teacher_user_id,
    u.name AS teacher_name,
    CASE 
        WHEN sec.id IS NULL THEN '❌ INVALID - Section does not exist'
        WHEN sub.id IS NULL THEN '❌ INVALID - Subject does not exist'
        WHEN t.id IS NULL THEN '❌ INVALID - Teacher does not exist'
        ELSE '✅ VALID'
    END AS status
FROM classes c
LEFT JOIN sections sec ON c.section_id = sec.id
LEFT JOIN subjects sub ON c.subject_id = sub.id
LEFT JOIN teachers t ON c.teacher_id = t.id
LEFT JOIN users u ON t.user_id = u.id
ORDER BY c.id;

-- Expected: All rows should show "✅ VALID"

-- ============================================================================
-- 4. VERIFY ALL TEACHER SCHEDULES MAP PROPERLY TO CLASSES
-- ============================================================================
SELECT 
    ts.id AS schedule_id,
    ts.teacher_id,
    ts.class_id,
    t.user_id AS teacher_user_id,
    u.name AS teacher_name,
    c.id AS class_exists,
    c.section_id,
    sec.name AS section_name,
    CASE 
        WHEN t.id IS NULL THEN '❌ INVALID - Teacher does not exist'
        WHEN ts.class_id IS NOT NULL AND c.id IS NULL THEN '⚠️ WARNING - Class does not exist (class_id set to NULL)'
        ELSE '✅ VALID'
    END AS status
FROM teacher_schedules ts
LEFT JOIN teachers t ON ts.teacher_id = t.id
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN classes c ON ts.class_id = c.id
LEFT JOIN sections sec ON c.section_id = sec.id
ORDER BY ts.id;

-- Expected: All rows should show "✅ VALID" or "⚠️ WARNING" (if class_id is NULL, that's acceptable)

-- ============================================================================
-- 5. VERIFY ALL STUDENTS MAP PROPERLY TO SECTIONS AND USERS
-- ============================================================================
SELECT 
    s.id AS student_id,
    s.user_id,
    s.section_id,
    u.name AS student_name,
    u.role,
    sec.name AS section_name,
    CASE 
        WHEN u.id IS NULL THEN '❌ INVALID - User does not exist'
        WHEN s.section_id IS NOT NULL AND sec.id IS NULL THEN '⚠️ WARNING - Section does not exist (section_id should be NULL)'
        ELSE '✅ VALID'
    END AS status
FROM students s
LEFT JOIN users u ON s.user_id = u.id
LEFT JOIN sections sec ON s.section_id = sec.id
ORDER BY s.id;

-- Expected: All rows should show "✅ VALID" or "⚠️ WARNING" (if section_id is NULL, that's acceptable for unenrolled students)

-- ============================================================================
-- 6. VERIFY ALL STUDENT_CLASSES ENTRIES REFERENCE EXISTING CLASSES AND STUDENTS
-- ============================================================================
SELECT 
    sc.id AS enrollment_id,
    sc.student_id,
    sc.class_id,
    s.user_id AS student_user_id,
    u.name AS student_name,
    c.section_id,
    sec.name AS section_name,
    sub.name AS subject_name,
    CASE 
        WHEN s.id IS NULL THEN '❌ INVALID - Student does not exist'
        WHEN c.id IS NULL THEN '❌ INVALID - Class does not exist'
        ELSE '✅ VALID'
    END AS status
FROM student_classes sc
LEFT JOIN students s ON sc.student_id = s.id
LEFT JOIN users u ON s.user_id = u.id
LEFT JOIN classes c ON sc.class_id = c.id
LEFT JOIN sections sec ON c.section_id = sec.id
LEFT JOIN subjects sub ON c.subject_id = sub.id
ORDER BY sc.id;

-- Expected: All rows should show "✅ VALID"

-- ============================================================================
-- 7. VERIFY ALL GRADES REFERENCE VALID RECORDS
-- ============================================================================
SELECT 
    g.id AS grade_id,
    g.student_id,
    g.section_id,
    g.subject_id,
    g.teacher_id,
    s.user_id AS student_user_id,
    u.name AS student_name,
    sec.name AS section_name,
    sub.name AS subject_name,
    t.user_id AS teacher_user_id,
    ut.name AS teacher_name,
    CASE 
        WHEN s.id IS NULL THEN '❌ INVALID - Student does not exist'
        WHEN sec.id IS NULL THEN '❌ INVALID - Section does not exist'
        WHEN sub.id IS NULL THEN '❌ INVALID - Subject does not exist'
        WHEN t.id IS NULL THEN '❌ INVALID - Teacher does not exist'
        ELSE '✅ VALID'
    END AS status
FROM grades g
LEFT JOIN students s ON g.student_id = s.id
LEFT JOIN users u ON s.user_id = u.id
LEFT JOIN sections sec ON g.section_id = sec.id
LEFT JOIN subjects sub ON g.subject_id = sub.id
LEFT JOIN teachers t ON g.teacher_id = t.id
LEFT JOIN users ut ON t.user_id = ut.id
ORDER BY g.id;

-- Expected: All rows should show "✅ VALID"

-- ============================================================================
-- 8. VERIFY ALL ATTENDANCE RECORDS REFERENCE VALID RECORDS
-- ============================================================================
SELECT 
    a.id AS attendance_id,
    a.student_id,
    a.teacher_id,
    a.section_id,
    a.subject_id,
    s.user_id AS student_user_id,
    u.name AS student_name,
    t.user_id AS teacher_user_id,
    ut.name AS teacher_name,
    sec.name AS section_name,
    sub.name AS subject_name,
    CASE 
        WHEN s.id IS NULL THEN '❌ INVALID - Student does not exist'
        WHEN t.id IS NULL THEN '❌ INVALID - Teacher does not exist'
        WHEN sec.id IS NULL THEN '❌ INVALID - Section does not exist'
        WHEN sub.id IS NULL THEN '❌ INVALID - Subject does not exist'
        ELSE '✅ VALID'
    END AS status
FROM attendance a
LEFT JOIN students s ON a.student_id = s.id
LEFT JOIN users u ON s.user_id = u.id
LEFT JOIN teachers t ON a.teacher_id = t.id
LEFT JOIN users ut ON t.user_id = ut.id
LEFT JOIN sections sec ON a.section_id = sec.id
LEFT JOIN subjects sub ON a.subject_id = sub.id
ORDER BY a.id;

-- Expected: All rows should show "✅ VALID"

-- ============================================================================
-- 9. VERIFY NO DUPLICATE TEACHERS (same user_id)
-- ============================================================================
SELECT 
    user_id,
    COUNT(*) AS duplicate_count,
    GROUP_CONCAT(id ORDER BY id) AS teacher_ids,
    CASE 
        WHEN COUNT(*) > 1 THEN '❌ DUPLICATE FOUND'
        ELSE '✅ NO DUPLICATES'
    END AS status
FROM teachers
GROUP BY user_id
HAVING COUNT(*) > 1;

-- Expected: No rows returned (all duplicates removed)

-- ============================================================================
-- 10. VERIFY NO DUPLICATE USERS (same id or email)
-- ============================================================================
SELECT 
    id,
    email,
    COUNT(*) AS duplicate_count,
    CASE 
        WHEN COUNT(*) > 1 THEN '❌ DUPLICATE FOUND'
        ELSE '✅ NO DUPLICATES'
    END AS status
FROM users
GROUP BY id, email
HAVING COUNT(*) > 1;

-- Expected: No rows returned

-- ============================================================================
-- 11. VERIFY SECTIONS WITH ADVISERS HAVE VALID USER REFERENCES
-- ============================================================================
SELECT 
    s.id AS section_id,
    s.name AS section_name,
    s.adviser_id,
    u.id AS adviser_user_exists,
    u.role AS adviser_role,
    u.name AS adviser_name,
    CASE 
        WHEN s.adviser_id IS NOT NULL AND u.id IS NULL THEN '❌ INVALID - Adviser user does not exist'
        WHEN s.adviser_id IS NOT NULL AND u.role NOT IN ('teacher', 'adviser') THEN '⚠️ WARNING - Adviser role is not teacher/adviser'
        ELSE '✅ VALID'
    END AS status
FROM sections s
LEFT JOIN users u ON s.adviser_id = u.id
WHERE s.adviser_id IS NOT NULL
ORDER BY s.id;

-- Expected: All rows should show "✅ VALID"

-- ============================================================================
-- 12. VERIFY FOREIGN KEY CONSTRAINTS ARE PRESENT
-- ============================================================================
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    '✅ FOREIGN KEY EXISTS' AS status
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL
  AND CONSTRAINT_NAME != 'PRIMARY'
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- Expected: Should show all 22+ foreign key constraints

-- ============================================================================
-- 13. VERIFY AUTO_INCREMENT IS SET ON PRIMARY KEYS
-- ============================================================================
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    EXTRA,
    CASE 
        WHEN EXTRA LIKE '%auto_increment%' THEN '✅ AUTO_INCREMENT ENABLED'
        ELSE '❌ AUTO_INCREMENT MISSING'
    END AS status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND COLUMN_KEY = 'PRI'
  AND TABLE_NAME NOT IN ('final_grades_view', 'quarterly_grades_view', 'student_profiles')
ORDER BY TABLE_NAME;

-- Expected: All rows should show "✅ AUTO_INCREMENT ENABLED"

-- ============================================================================
-- 14. VERIFY VIEWS ARE WORKING
-- ============================================================================
-- Test quarterly_grades_view
SELECT COUNT(*) AS quarterly_grades_count FROM quarterly_grades_view;
-- Expected: Should return count > 0 if grades exist

-- Test final_grades_view
SELECT COUNT(*) AS final_grades_count FROM final_grades_view;
-- Expected: Should return count > 0 if grades exist

-- Test student_profiles view
SELECT COUNT(*) AS student_profiles_count FROM student_profiles;
-- Expected: Should return count matching number of students

-- ============================================================================
-- 15. SUMMARY VALIDATION
-- ============================================================================
SELECT 
    'Total Teachers' AS metric,
    COUNT(*) AS count,
    COUNT(DISTINCT user_id) AS unique_users,
    CASE 
        WHEN COUNT(*) = COUNT(DISTINCT user_id) THEN '✅ NO DUPLICATES'
        ELSE '❌ DUPLICATES FOUND'
    END AS status
FROM teachers
UNION ALL
SELECT 
    'Total Students',
    COUNT(*),
    COUNT(DISTINCT user_id),
    CASE 
        WHEN COUNT(*) = COUNT(DISTINCT user_id) THEN '✅ NO DUPLICATES'
        ELSE '❌ DUPLICATES FOUND'
    END
FROM students
UNION ALL
SELECT 
    'Total Classes',
    COUNT(*),
    COUNT(DISTINCT CONCAT(section_id, '-', subject_id, '-', teacher_id)),
    '✅ CHECKED' AS status
FROM classes
UNION ALL
SELECT 
    'Orphaned Grades',
    COUNT(*),
    0,
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ NO ORPHANS'
        ELSE '❌ ORPHANS FOUND'
    END
FROM grades g
LEFT JOIN students s ON g.student_id = s.id
WHERE s.id IS NULL
UNION ALL
SELECT 
    'Orphaned Classes',
    COUNT(*),
    0,
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ NO ORPHANS'
        ELSE '❌ ORPHANS FOUND'
    END
FROM classes c
LEFT JOIN teachers t ON c.teacher_id = t.id
WHERE t.id IS NULL;

-- ============================================================================
-- END OF VALIDATION QUERIES
-- ============================================================================

