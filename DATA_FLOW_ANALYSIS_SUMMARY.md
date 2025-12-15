# Complete Data Flow Analysis - Student Monitoring System

## Executive Summary

This document provides a comprehensive map of how data flows through the student monitoring system, identifying all features, table relationships, foreign key dependencies, and critical data integrity issues.

**Key Finding**: Only the `attendance` table has proper foreign key constraints. All other tables lack foreign key constraints, creating significant data integrity risks.

---

## 1. Core Features Identified

### User Management
1. **create_user** - Admin creates user (teacher/adviser/student/parent)
2. **approve_user** - Admin approves pending user registration
3. **register_student** - Admin registers student with full details

### Teacher Management
4. **create_teacher** - Admin creates teacher/adviser account
5. **assign_adviser** - Admin assigns teacher as section adviser

### Section & Class Management
6. **create_section** - Admin/Teacher creates a new section (class)
7. **create_class** - Admin creates class (assigns teacher to teach subject in section)
8. **enroll_student** - Admin/Teacher enrolls student in section

### Dashboards & Views
9. **teacher_dashboard** - Teacher views their classes, sections, and students
10. **student_dashboard** - Student views their schedule, grades, and academic stats

### Academic Operations
11. **submit_grade** - Teacher submits grade for student
12. **view_grades** - Student/Teacher views grades
13. **record_attendance** - Teacher records student attendance
14. **create_assignment** - Teacher creates assignment for section/subject

---

## 2. Feature-by-Feature Data Flow

### Feature: create_user
**Flow**: `users` → (conditionally) `students` / `teachers` / `parents`

**Tables Written**:
- `users` (always)
- `students` (if role = 'student')
- `teachers` (if role = 'teacher' or 'adviser')
- `parents` (if role = 'parent', table may not exist)

**Key Relationships**:
- `students.user_id → users.id` (NO FK CONSTRAINT)
- `teachers.user_id → users.id` (NO FK CONSTRAINT)

**Status**: ✅ CENTRALIZED - Flow works correctly via `api/create_user.php` and `TeacherProfileHelper`

**Issues**:
- No foreign key constraints
- If `TeacherProfileHelper::save()` fails, user is created but teachers record missing → CRITICAL BREAKAGE

---

### Feature: create_class
**Flow**: `sections` + `subjects` + `teachers` → `classes` → `teacher_schedules` + `student_classes`

**Tables Written**:
- `classes`
- `teacher_schedules`
- `student_classes` (auto-enrolls all students in section)

**Key Relationships**:
- `classes.section_id → sections.id` (NO FK CONSTRAINT)
- `classes.subject_id → subjects.id` (NO FK CONSTRAINT)
- `classes.teacher_id → teachers.id` (NO FK CONSTRAINT) ⚠️ CRITICAL

**Status**: ✅ CENTRALIZED - Flow works correctly

**Issues**:
- No foreign key constraints
- If `teachers.id` doesn't exist, class creation fails (correct behavior)
- Unique constraint prevents duplicates: `(section_id, subject_id, teacher_id, school_year)`

---

### Feature: enroll_student
**Flow**: `students` → update `section_id` → auto-enroll in all `classes` via `student_classes`

**Tables Written**:
- `students` (updates `section_id`)
- `student_classes` (creates enrollment records)

**Key Relationships**:
- `students.section_id → sections.id` (NO FK CONSTRAINT)
- `student_classes.student_id → students.id` (NO FK CONSTRAINT)
- `student_classes.class_id → classes.id` (NO FK CONSTRAINT)

**Status**: ✅ CENTRALIZED - Flow works correctly

**Issues**:
- No foreign key constraints
- Section capacity check is performed (good)
- If section is deleted, `students.section_id` becomes invalid

---

### Feature: submit_grade
**Flow**: `teachers` (resolve from `users.id`) → verify via `classes` → insert into `grades`

**Tables Written**:
- `grades`

**Key Relationships**:
- `grades.student_id → students.id` (NO FK CONSTRAINT)
- `grades.section_id → sections.id` (NO FK CONSTRAINT)
- `grades.subject_id → subjects.id` (NO FK CONSTRAINT)
- `grades.teacher_id → teachers.id` (NO FK CONSTRAINT) ⚠️ CRITICAL

**Status**: ✅ CENTRALIZED - Flow works correctly with permission checks

**Issues**:
- No foreign key constraints
- Permission check via `classes` table is correct
- If any referenced record is deleted, grade becomes orphaned

---

### Feature: record_attendance
**Flow**: `teachers` + `students` + `sections` + `subjects` → `attendance`

**Tables Written**:
- `attendance`

**Key Relationships**:
- `attendance.student_id → students.id` ✅ HAS FK: ON DELETE CASCADE
- `attendance.teacher_id → teachers.id` ✅ HAS FK: ON DELETE CASCADE
- `attendance.section_id → sections.id` ✅ HAS FK: ON DELETE CASCADE
- `attendance.subject_id → subjects.id` ✅ HAS FK: ON DELETE CASCADE

**Status**: ✅ CENTRALIZED - Flow works correctly

**Issues**: ✅ NONE - This is the ONLY table with proper foreign key constraints!

---

## 3. System Wiring Diagram

```
users (id)
  │
  ├─→ students (user_id) [NO FK]
  │   ├─→ grades (student_id) [NO FK]
  │   ├─→ attendance (student_id) [HAS FK: CASCADE]
  │   └─→ student_classes (student_id) [NO FK]
  │
  ├─→ teachers (user_id) [NO FK, UNIQUE]
  │   ├─→ classes (teacher_id) [NO FK] ⚠️ CRITICAL
  │   │   ├─→ student_classes (class_id) [NO FK]
  │   │   └─→ teacher_schedules (class_id) [NO FK]
  │   │
  │   ├─→ grades (teacher_id) [NO FK] ⚠️ CRITICAL
  │   ├─→ attendance (teacher_id) [HAS FK: CASCADE]
  │   ├─→ assignments (teacher_id) [NO FK]
  │   └─→ teacher_schedules (teacher_id) [NO FK]
  │
  └─→ sections (adviser_id) [NO FK]
      ├─→ students (section_id) [NO FK]
      ├─→ classes (section_id) [NO FK]
      ├─→ attendance (section_id) [HAS FK: CASCADE]
      └─→ assignments (section_id) [NO FK]

subjects (id)
  ├─→ classes (subject_id) [NO FK]
  ├─→ grades (subject_id) [NO FK]
  ├─→ attendance (subject_id) [HAS FK: CASCADE]
  └─→ assignments (subject_id) [NO FK]
```

---

## 4. Critical Data Flow Paths

### Path 1: User Creation → Teacher Features
```
1. INSERT INTO users (role='teacher')
2. TeacherProfileHelper::save() → INSERT INTO teachers (user_id)
3. ✅ If step 2 fails, user exists but teachers record missing → ALL TEACHER FEATURES BREAK
```

**Breakage Points**:
- Teacher dashboard: `SELECT id FROM teachers WHERE user_id = ?` returns NULL
- Class creation: Cannot resolve `teacher_id` from `users.id`
- Grade encoding: Cannot find teacher record
- All dropdowns: No teachers to display

---

### Path 2: Class Creation → Student Enrollment
```
1. INSERT INTO classes (section_id, subject_id, teacher_id)
2. Auto-enroll: INSERT INTO student_classes (student_id, class_id) for all students in section
3. ✅ If section_id invalid, class creation fails (good)
4. ⚠️ If teacher_id invalid, class creation fails (good, but no FK constraint)
```

**Breakage Points**:
- If `teachers.id` is deleted but `classes.teacher_id` still references it → Orphaned classes
- If `sections.id` is deleted but `classes.section_id` still references it → Orphaned classes

---

### Path 3: Grade Encoding → Grade Viewing
```
1. Teacher submits grade: INSERT INTO grades (student_id, section_id, subject_id, teacher_id)
2. Student views grades: SELECT FROM grades JOIN students, sections, subjects, teachers
3. ✅ If any join fails, grade viewing breaks
```

**Breakage Points**:
- If `students.id` is deleted → Grades become orphaned, joins fail
- If `teachers.id` is deleted → Grades become orphaned, joins fail
- If `sections.id` is deleted → Grades become orphaned, joins fail
- If `subjects.id` is deleted → Grades become orphaned, joins fail

---

## 5. Missing Foreign Key Constraints

### CRITICAL (System Breaking)
1. ❌ `teachers.user_id → users.id` - If missing, ALL teacher features break
2. ❌ `classes.teacher_id → teachers.id` - If missing, classes become orphaned
3. ❌ `grades.teacher_id → teachers.id` - If missing, grades become orphaned

### HIGH (Data Integrity)
4. ❌ `students.user_id → users.id` - If missing, students become orphaned
5. ❌ `students.section_id → sections.id` - If missing, enrollment breaks
6. ❌ `classes.section_id → sections.id` - If missing, classes become orphaned
7. ❌ `classes.subject_id → subjects.id` - If missing, classes become orphaned
8. ❌ `grades.student_id → students.id` - If missing, grades become orphaned
9. ❌ `grades.section_id → sections.id` - If missing, grades become orphaned
10. ❌ `grades.subject_id → subjects.id` - If missing, grades become orphaned

### MEDIUM (Feature Breaking)
11. ❌ `sections.adviser_id → users.id` - If missing, adviser assignment breaks
12. ❌ `student_classes.student_id → students.id` - If missing, enrollment records orphaned
13. ❌ `student_classes.class_id → classes.id` - If missing, enrollment records orphaned
14. ❌ `teacher_schedules.teacher_id → teachers.id` - If missing, schedules orphaned
15. ❌ `teacher_schedules.class_id → classes.id` - If missing, schedules orphaned

### LOW (Data Quality)
16. ❌ `assignments.teacher_id → teachers.id` - If missing, assignments orphaned
17. ❌ `assignments.section_id → sections.id` - If missing, assignments orphaned
18. ❌ `assignments.subject_id → subjects.id` - If missing, assignments orphaned

---

## 6. Code Issues

### Issue 1: Non-existent 'advisers' Table
**Location**: `app/Controllers/AdminController.php` lines 241-248

**Problem**: Code tries to `INSERT INTO advisers` when approving user with role='adviser', but this table doesn't exist in schema.

**Impact**: Will cause SQL errors when approving adviser users.

**Fix**: Remove the code block that references 'advisers' table.

---

### Issue 2: Missing Teachers Record Validation
**Location**: `api/create_user.php` line 281-299

**Problem**: If `TeacherProfileHelper::save()` fails, transaction is rolled back (good), but error handling could be improved.

**Impact**: If teacher profile creation fails silently, user might be created without teachers record.

**Fix**: Ensure transaction rollback and proper error reporting.

---

## 7. Recommended Fixes (Priority Order)

### Priority: CRITICAL
1. Add FK: `teachers.user_id → users.id ON DELETE CASCADE`
2. Add FK: `classes.teacher_id → teachers.id ON DELETE RESTRICT`
3. Add FK: `grades.teacher_id → teachers.id ON DELETE RESTRICT`
4. Ensure teachers record is ALWAYS created when user role = 'teacher'/'adviser'

### Priority: HIGH
5. Add FK: `students.user_id → users.id ON DELETE CASCADE`
6. Add FK: `students.section_id → sections.id ON DELETE SET NULL`
7. Add FK: `classes.section_id → sections.id ON DELETE RESTRICT`
8. Add FK: `classes.subject_id → subjects.id ON DELETE RESTRICT`
9. Add FK: `grades.student_id → students.id ON DELETE CASCADE`
10. Add FK: `grades.section_id → sections.id ON DELETE RESTRICT`
11. Add FK: `grades.subject_id → subjects.id ON DELETE RESTRICT`

### Priority: MEDIUM
12. Add FK: `sections.adviser_id → users.id ON DELETE SET NULL`
13. Add FK: `student_classes.student_id → students.id ON DELETE CASCADE`
14. Add FK: `student_classes.class_id → classes.id ON DELETE CASCADE`
15. Add FK: `teacher_schedules.teacher_id → teachers.id ON DELETE CASCADE`
16. Add FK: `teacher_schedules.class_id → classes.id ON DELETE CASCADE`
17. Remove reference to non-existent 'advisers' table

### Priority: LOW
18. Add FK constraints to `assignments` table

---

## 8. What Works Well

✅ **Attendance Table**: Has ALL foreign key constraints properly defined. This is the model to follow.

✅ **User Creation Flow**: `api/create_user.php` properly creates role-specific records via `TeacherProfileHelper`.

✅ **Permission Checks**: Grade encoding properly verifies teacher access via `classes` table.

✅ **Capacity Checks**: Student enrollment properly checks section capacity before assignment.

✅ **Unique Constraints**: Proper unique constraints prevent duplicate classes, enrollments, and schedules.

---

## 9. What Will Break

### If `teachers` record is missing for user with role='teacher':
- ❌ Teacher dashboard fails
- ❌ Class creation fails
- ❌ Grade encoding fails
- ❌ All teacher dropdowns empty

### If foreign key relationships are broken:
- ❌ Grade calculations may fail (joins return NULL)
- ❌ Student schedule may show invalid classes
- ❌ Teacher dashboard may show orphaned classes
- ❌ Data integrity completely compromised

---

## 10. Summary Statistics

- **Total Features Analyzed**: 14
- **Tables with FK Constraints**: 1 (attendance)
- **Tables without FK Constraints**: 12+
- **Critical Missing Links**: 3
- **High Priority Missing Links**: 7
- **Medium Priority Missing Links**: 5
- **Low Priority Missing Links**: 3

**Data Integrity Risk Level**: 🔴 **CRITICAL**

---

## Next Steps

1. Review this analysis
2. Approve recommended fixes
3. Apply foreign key constraints in priority order
4. Test all features after each fix
5. Update code to remove 'advisers' table reference
6. Add validation to ensure teachers record is always created

---

*Analysis completed: Comprehensive data flow mapping with identification of all missing relational links and potential breakage points.*

