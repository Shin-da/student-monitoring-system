# COMPLETE REPAIR PLAN
## Student Monitoring System - Database Schema Fix

**Objective**: Fix all missing foreign keys, remove duplicates, fix invalid data, repair views, and ensure complete referential integrity.

---

## EXECUTION ORDER

### PHASE 1: PREPARATION (Before Running Script)
1. ✅ **BACKUP DATABASE** - Create full backup of `student_monitoring` database
2. ✅ **VERIFY BACKUP** - Test backup restoration on test environment
3. ✅ **MAINTENANCE WINDOW** - Schedule downtime if production
4. ✅ **NOTIFY USERS** - Inform users of maintenance window

### PHASE 2: DATA CLEANUP (Must Execute First)
**Why First**: Foreign keys cannot be added if duplicate or invalid data exists.

1. **Remove Duplicate Rows**
   - Remove duplicate teachers (keep first occurrence per user_id)
   - Remove duplicate teacher_schedules
   - Remove duplicate subjects
   - Remove duplicate users
   - Remove duplicate user_requests
   - Remove duplicate audit_logs_backup

2. **Remove Invalid Data**
   - Delete classes with id=0
   - Delete grades with id=0 or student_id=0
   - Delete teacher_schedules with id=0
   - Update teacher_schedules with class_id=0 to NULL

3. **Remove Orphaned Data**
   - Delete grades referencing non-existent students/teachers/sections/subjects
   - Delete attendance referencing non-existent records
   - Delete classes referencing non-existent sections/subjects/teachers
   - Delete student_classes referencing non-existent students/classes
   - Delete teacher_schedules referencing non-existent teachers/classes
   - Delete assignments referencing non-existent teachers/sections/subjects
   - Delete students referencing non-existent users
   - Delete teachers referencing non-existent users

4. **Fix Invalid References**
   - Set sections.adviser_id to NULL if user doesn't exist
   - Set users.approved_by to NULL if user doesn't exist
   - Set users.linked_student_user_id to NULL if user doesn't exist
   - Set audit_logs.user_id to NULL if user doesn't exist

**Estimated Impact**: 
- ~100-200 rows deleted (duplicates and orphans)
- ~5-10 rows updated (invalid references set to NULL)

### PHASE 3: FIX AUTO_INCREMENT
**Why Before Foreign Keys**: Ensures primary keys are properly sequenced.

1. Add AUTO_INCREMENT to all primary key columns:
   - assignments.id
   - audit_logs.id
   - classes.id
   - grades.id (already has it, but verify)
   - sections.id
   - students.id
   - student_classes.id
   - subjects.id
   - teachers.id
   - teacher_schedules.id
   - users.id
   - user_requests.id

**Estimated Impact**: No data loss, only structure change

### PHASE 4: ADD FOREIGN KEY CONSTRAINTS
**Why After Cleanup**: Foreign keys will enforce integrity going forward.

**Order Matters**: Add constraints in dependency order:

1. **Base Tables First** (no dependencies):
   - users (self-references added last)

2. **Role Tables** (depend on users):
   - students.user_id → users.id (CASCADE)
   - teachers.user_id → users.id (CASCADE)

3. **Reference Tables** (depend on base tables):
   - sections.adviser_id → users.id (SET NULL)
   - classes.section_id → sections.id (RESTRICT)
   - classes.subject_id → subjects.id (RESTRICT)
   - classes.teacher_id → teachers.id (RESTRICT)

4. **Junction Tables** (depend on multiple tables):
   - student_classes.student_id → students.id (CASCADE)
   - student_classes.class_id → classes.id (CASCADE)
   - teacher_schedules.teacher_id → teachers.id (CASCADE)
   - teacher_schedules.class_id → classes.id (SET NULL)

5. **Data Tables** (depend on multiple tables):
   - grades.student_id → students.id (CASCADE)
   - grades.section_id → sections.id (RESTRICT)
   - grades.subject_id → subjects.id (RESTRICT)
   - grades.teacher_id → teachers.id (RESTRICT)
   - attendance.* (already has constraints, verify)

6. **Other Tables**:
   - assignments.* → teachers/sections/subjects (CASCADE)
   - user_requests.* → users (CASCADE/SET NULL)
   - audit_logs.user_id → users.id (SET NULL)

7. **Self-References** (users table):
   - users.approved_by → users.id (SET NULL)
   - users.linked_student_user_id → users.id (SET NULL)

**Estimated Impact**: All future data must comply with constraints

### PHASE 5: FIX BROKEN VIEWS
**Why After Foreign Keys**: Views depend on table structure being correct.

1. Drop broken views:
   - final_grades_view
   - quarterly_grades_view
   - student_profiles

2. Recreate views with correct syntax:
   - Fix quarterly_grades_view GROUP BY clause
   - Fix student_profiles WHERE clause
   - Recreate final_grades_view

**Estimated Impact**: Views will work correctly

### PHASE 6: VERIFICATION
**Why Last**: Ensure everything works.

Run validation queries (see POST-FIX VALIDATION section)

---

## ON DELETE / ON UPDATE BEHAVIORS

### CASCADE (Delete child when parent deleted)
**Used For**:
- `students.user_id → users.id` - If user deleted, student deleted
- `teachers.user_id → users.id` - If user deleted, teacher deleted
- `student_classes.student_id → students.id` - If student deleted, enrollments deleted
- `student_classes.class_id → classes.id` - If class deleted, enrollments deleted
- `teacher_schedules.teacher_id → teachers.id` - If teacher deleted, schedules deleted
- `grades.student_id → students.id` - If student deleted, grades deleted
- `attendance.*` - If referenced record deleted, attendance deleted
- `assignments.*` - If referenced record deleted, assignments deleted
- `user_requests.user_id → users.id` - If user deleted, requests deleted

**Reason**: These are dependent records that have no meaning without parent.

### SET NULL (Set foreign key to NULL when parent deleted)
**Used For**:
- `sections.adviser_id → users.id` - If adviser deleted, section can exist without adviser
- `users.approved_by → users.id` - If approver deleted, preserve approval record
- `users.linked_student_user_id → users.id` - If student deleted, parent link removed
- `teacher_schedules.class_id → classes.id` - If class deleted, schedule can remain for reference
- `audit_logs.user_id → users.id` - If user deleted, preserve audit history
- `user_requests.processed_by → users.id` - If processor deleted, preserve request record

**Reason**: These records should be preserved even if parent is deleted.

### RESTRICT (Prevent deletion if children exist)
**Used For**:
- `classes.section_id → sections.id` - Cannot delete section with active classes
- `classes.subject_id → subjects.id` - Cannot delete subject with active classes
- `classes.teacher_id → teachers.id` - Cannot delete teacher with active classes
- `grades.section_id → sections.id` - Cannot delete section with grades
- `grades.subject_id → subjects.id` - Cannot delete subject with grades
- `grades.teacher_id → teachers.id` - Cannot delete teacher with grades

**Reason**: These are critical relationships that must be maintained.

---

## INDEXES TO ADD/VERIFY

### Already Present (Good)
All foreign key columns already have indexes:
- students.user_id ✓
- teachers.user_id ✓
- classes.section_id, subject_id, teacher_id ✓
- grades.student_id, section_id, subject_id, teacher_id ✓
- student_classes.student_id, class_id ✓
- teacher_schedules.teacher_id, class_id ✓
- assignments.teacher_id, section_id, subject_id ✓
- sections.adviser_id ✓
- users.approved_by, linked_student_user_id ✓
- audit_logs.user_id ✓
- user_requests.user_id, processed_by ✓

**No additional indexes needed** - All foreign key columns are already indexed.

---

## TABLE DEFINITIONS TO ALTER

### No Table Structure Changes Needed
All table definitions are correct. Only need to:
1. Add AUTO_INCREMENT to primary keys
2. Add foreign key constraints
3. Fix views

---

## DATA CLEANUP QUERIES SUMMARY

### Duplicate Removal
- Teachers: Remove duplicates keeping first per user_id
- Teacher Schedules: Remove duplicates keeping first
- Subjects: Remove duplicates keeping first per id
- Users: Remove duplicates keeping first per id
- User Requests: Remove duplicates keeping first per id
- Audit Logs Backup: Remove duplicates keeping first per id

### Invalid Data Removal
- Delete classes with id=0
- Delete grades with id=0 or student_id=0
- Delete teacher_schedules with id=0
- Update teacher_schedules.class_id=0 to NULL

### Orphaned Data Removal
- Delete all records referencing non-existent parents
- Update invalid foreign key references to NULL where appropriate

---

## EXPECTED RESULTS AFTER FIX

### Data Integrity
✅ All foreign key relationships enforced
✅ No duplicate rows
✅ No orphaned data
✅ No invalid primary keys
✅ All views working correctly

### Feature Support
✅ User creation → automatically creates teachers/students records
✅ Class creation → validates all relationships
✅ Grade encoding → validates teacher has access
✅ Teacher dashboard → shows correct classes
✅ Student dashboard → shows correct schedule and grades
✅ Adviser assignment → validates user is teacher/adviser
✅ Student enrollment → validates section capacity

### System Reliability
✅ No more "class already exists" false errors
✅ Dropdowns always show correct entries
✅ No orphaned records
✅ Data deletion properly cascades
✅ Referential integrity maintained

---

## ROLLBACK PLAN

If issues occur after running the fix:

1. **Restore from backup** (recommended)
2. **Manual rollback** (if backup unavailable):
   - Drop all foreign key constraints
   - Restore duplicate data from audit logs
   - Revert AUTO_INCREMENT changes

**Backup is critical** - Always backup before running schema changes.

---

## ESTIMATED EXECUTION TIME

- **Small Database** (< 1000 rows): 1-2 minutes
- **Medium Database** (1000-10000 rows): 2-5 minutes
- **Large Database** (> 10000 rows): 5-15 minutes

**Most time spent on**: Duplicate removal and orphaned data cleanup

---

*End of Repair Plan*

