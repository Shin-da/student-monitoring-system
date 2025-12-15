# FULL DATABASE DIAGNOSTIC REPORT
## Student Monitoring System - Schema Analysis

**Analysis Date**: Based on `student_monitoring (3).sql`  
**Database Engine**: MariaDB 10.4.32  
**Status**: 🔴 **CRITICAL ISSUES DETECTED**

---

## 1. MISSING FOREIGN KEY CONSTRAINTS

### CRITICAL (System Breaking)
1. ❌ **teachers.user_id → users.id** - NO CONSTRAINT
   - Impact: If user deleted, teacher record orphaned. ALL teacher features break.
   - Severity: CRITICAL

2. ❌ **students.user_id → users.id** - NO CONSTRAINT
   - Impact: If user deleted, student record orphaned. Student features break.
   - Severity: CRITICAL

3. ❌ **classes.teacher_id → teachers.id** - NO CONSTRAINT
   - Impact: If teacher deleted, classes become orphaned. Class management breaks.
   - Severity: CRITICAL

4. ❌ **classes.section_id → sections.id** - NO CONSTRAINT
   - Impact: If section deleted, classes become orphaned.
   - Severity: HIGH

5. ❌ **classes.subject_id → subjects.id** - NO CONSTRAINT
   - Impact: If subject deleted, classes become orphaned.
   - Severity: HIGH

6. ❌ **grades.student_id → students.id** - NO CONSTRAINT
   - Impact: If student deleted, grades become orphaned. Grade calculations fail.
   - Severity: CRITICAL

7. ❌ **grades.teacher_id → teachers.id** - NO CONSTRAINT
   - Impact: If teacher deleted, grades become orphaned.
   - Severity: HIGH

8. ❌ **grades.section_id → sections.id** - NO CONSTRAINT
   - Impact: If section deleted, grades become orphaned.
   - Severity: HIGH

9. ❌ **grades.subject_id → subjects.id** - NO CONSTRAINT
   - Impact: If subject deleted, grades become orphaned.
   - Severity: HIGH

### HIGH PRIORITY
10. ❌ **sections.adviser_id → users.id** - NO CONSTRAINT
    - Impact: If adviser user deleted, section.adviser_id becomes invalid.
    - Severity: MEDIUM

11. ❌ **student_classes.student_id → students.id** - NO CONSTRAINT
    - Impact: If student deleted, enrollment records orphaned.
    - Severity: MEDIUM

12. ❌ **student_classes.class_id → classes.id** - NO CONSTRAINT
    - Impact: If class deleted, enrollment records orphaned.
    - Severity: MEDIUM

13. ❌ **teacher_schedules.teacher_id → teachers.id** - NO CONSTRAINT
    - Impact: If teacher deleted, schedules orphaned.
    - Severity: MEDIUM

14. ❌ **teacher_schedules.class_id → classes.id** - NO CONSTRAINT
    - Impact: If class deleted, schedules orphaned.
    - Severity: MEDIUM

15. ❌ **assignments.teacher_id → teachers.id** - NO CONSTRAINT
    - Impact: If teacher deleted, assignments orphaned.
    - Severity: LOW

16. ❌ **assignments.section_id → sections.id** - NO CONSTRAINT
    - Impact: If section deleted, assignments orphaned.
    - Severity: LOW

17. ❌ **assignments.subject_id → subjects.id** - NO CONSTRAINT
    - Impact: If subject deleted, assignments orphaned.
    - Severity: LOW

18. ❌ **users.approved_by → users.id** - NO CONSTRAINT
    - Impact: If approver deleted, approved_by becomes invalid.
    - Severity: LOW

19. ❌ **users.linked_student_user_id → users.id** - NO CONSTRAINT
    - Impact: If linked student deleted, parent link breaks.
    - Severity: LOW

20. ❌ **audit_logs.user_id → users.id** - NO CONSTRAINT
    - Impact: If user deleted, audit log orphaned (but should preserve history).
    - Severity: LOW

21. ❌ **user_requests.user_id → users.id** - NO CONSTRAINT
    - Impact: If user deleted, request orphaned.
    - Severity: LOW

22. ❌ **user_requests.processed_by → users.id** - NO CONSTRAINT
    - Impact: If processor deleted, processed_by becomes invalid.
    - Severity: LOW

**Total Missing Foreign Keys**: 22

**Only 4 Foreign Keys Present**: All in `attendance` table (this is the model to follow)

---

## 2. DUPLICATE ROWS DETECTED

### CRITICAL DUPLICATES
1. **teachers table**: 
   - Row with `id=1, user_id=2` appears **TWICE** (lines 496-497)
   - Impact: Violates UNIQUE constraint on user_id, causes query issues

2. **teacher_schedules table**:
   - Multiple duplicate rows (id=0,9,10,11,12 all duplicated)
   - Impact: Schedule conflicts, incorrect counts

3. **subjects table**:
   - All 8 subjects duplicated (ids 1-8 appear twice)
   - Impact: Violates PRIMARY KEY, causes constraint errors

4. **users table**:
   - Multiple users duplicated (ids 1,2,3,4,5,6,7,19,20,21 all duplicated)
   - Impact: Violates PRIMARY KEY and UNIQUE email constraint

5. **user_requests table**:
   - Multiple requests duplicated (ids 1-10 all duplicated multiple times)
   - Impact: Violates PRIMARY KEY

6. **audit_logs_backup table**:
   - Multiple audit logs duplicated
   - Impact: Data integrity issues

**Total Duplicate Rows**: Hundreds across multiple tables

---

## 3. INVALID DATA DETECTED

### Invalid Primary Keys (id = 0)
1. **classes table**: `id = 0` (line 226)
   - Impact: Invalid primary key, breaks AUTO_INCREMENT

2. **grades table**: `id = 0` (line 270)
   - Impact: Invalid primary key, breaks AUTO_INCREMENT

3. **grades table**: `student_id = 0` (line 273)
   - Impact: Orphaned grade, references non-existent student

4. **teacher_schedules table**: `id = 0` (lines 522, 527)
   - Impact: Invalid primary key, breaks AUTO_INCREMENT

5. **teacher_schedules table**: `class_id = 0` (lines 522, 527)
   - Impact: References non-existent class

### Orphaned Data
1. **grades.student_id = 8**: Student with id=8 doesn't exist in students table
2. **grades.student_id = 0**: Invalid reference
3. **attendance.student_id = 6**: Need to verify exists
4. **attendance.student_id = 8**: Need to verify exists

---

## 4. MISSING AUTO_INCREMENT SETTINGS

**Tables Missing AUTO_INCREMENT**:
- `assignments.id`
- `audit_logs.id`
- `classes.id`
- `sections.id`
- `students.id`
- `student_classes.id`
- `subjects.id`
- `teachers.id`
- `teacher_schedules.id`
- `users.id`
- `user_requests.id`

**Only 2 tables have AUTO_INCREMENT**:
- `attendance.id` (AUTO_INCREMENT=7)
- `grades.id` (AUTO_INCREMENT=11)

---

## 5. BROKEN VIEW DEFINITIONS

### CRITICAL VIEW ERRORS
1. **quarterly_grades_view**: 
   - Error: Double `academic_year` in GROUP BY clause
   - Line 580: `GROUP BY ... academic_year``academic_year`
   - Impact: View creation fails

2. **student_profiles view**:
   - Error: Malformed WHERE clause
   - Line 656: `WHERE u.role = 'student\'student\'student\'student\'student\'student\'student\'student'`
   - Impact: View returns no results or fails

3. **final_grades_view**:
   - Error: Depends on `quarterly_grades_view` which is broken
   - Impact: View creation fails

---

## 6. INCONSISTENT UNIQUE CONSTRAINTS

### Issues Found
1. **classes table**: 
   - Unique constraint: `unique_class_section_subject` uses `(section_id, subject_id, semester, school_year)`
   - Missing `teacher_id` in constraint (allows same teacher to teach same subject in same section)
   - Should be: `(section_id, subject_id, teacher_id, semester, school_year)`

2. **teachers table**:
   - Has UNIQUE on `user_id` (correct)
   - Has UNIQUE on `employee_id` (correct, but allows NULL)
   - But duplicate rows exist violating these constraints

---

## 7. ORPHANED DATA ANALYSIS

### Students Table
- All students have valid `user_id` references (3,4,5,6,7,19,20)
- All students have valid `section_id` references (1,2,11,12)

### Teachers Table
- Teacher id=1 has valid `user_id=2` reference
- Teacher id=2 has valid `user_id=22` reference
- BUT: Duplicate row exists

### Classes Table
- All classes have valid `section_id` (1,11)
- All classes have valid `subject_id` (2,4,6,7)
- All classes have valid `teacher_id` (1)
- BUT: class id=0 is invalid

### Grades Table
- Some grades reference `student_id=0` (invalid)
- Some grades reference `student_id=8` (doesn't exist)
- All other references appear valid

### Attendance Table
- References `student_id=3,6` (need to verify)
- All references to `teacher_id=1`, `section_id=1`, `subject_id=2,4,7` appear valid

---

## 8. INDEX ANALYSIS

### Missing Indexes for Foreign Keys
All foreign key columns have indexes (good), but since foreign keys don't exist, indexes are not enforced.

### Redundant Indexes
- `assignments_subject_fk` and `idx_section_subject` - both index subject_id
- `attendance_subject_fk` and `idx_section_subject` - both index subject_id
- `grades_subject_fk` and `idx_section_subject` - both index subject_id

These are not critical but could be optimized.

---

## 9. DATA TYPE CONSISTENCY

### Issues Found
1. **users table**: `parent_relationship` is ENUM - correct
2. **students table**: `lrn` allows NULL but has UNIQUE constraint - should allow NULL in UNIQUE
3. All other data types appear consistent

---

## 10. SUMMARY STATISTICS

- **Total Tables**: 13 core tables
- **Tables with Foreign Keys**: 1 (attendance only)
- **Tables Needing Foreign Keys**: 12
- **Missing Foreign Key Constraints**: 22
- **Duplicate Rows**: Hundreds across 6+ tables
- **Invalid Primary Keys (id=0)**: 4 instances
- **Orphaned Records**: ~10+ instances
- **Broken Views**: 3
- **Tables Missing AUTO_INCREMENT**: 11

---

## 11. CRITICAL BREAKAGE POINTS

### If User with role='teacher' is deleted:
- ❌ Teacher record remains orphaned
- ❌ All classes referencing teacher break
- ❌ All grades referencing teacher break
- ❌ All schedules referencing teacher break
- ❌ Teacher dashboard completely fails

### If Student is deleted:
- ❌ Student record remains orphaned
- ❌ All grades remain orphaned
- ❌ All attendance records remain orphaned
- ❌ All enrollments remain orphaned

### If Section is deleted:
- ❌ All classes remain orphaned
- ❌ All students.section_id become invalid
- ❌ All grades remain orphaned

### If Teacher record is missing for user:
- ❌ Class creation fails
- ❌ Grade encoding fails
- ❌ Teacher dashboard fails
- ❌ All teacher dropdowns empty

---

## 12. DATA INTEGRITY RISK LEVEL

**🔴 CRITICAL RISK**

The database currently has:
- No referential integrity enforcement
- Hundreds of duplicate rows
- Invalid primary keys
- Broken views
- Orphaned data

**System is vulnerable to data corruption and feature breakage.**

---

*End of Diagnostic Report*

