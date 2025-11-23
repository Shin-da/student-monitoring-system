# Class Management Constraint Fix

## Issue Description

When creating classes and adding different subjects to the same section, you encountered this error:

```
Fatal error: Uncaught PDOException: SQLSTATE[23000]: Integrity constraint violation: 
1062 Duplicate entry '1-7-1-2025-2026' for key 'unique_class_assignment'
```

## Root Cause

The database has a unique constraint named `unique_class_assignment` that likely includes:
- `section_id`
- `grade_level` (or derived from section)
- `semester`
- `school_year`

**However, it does NOT include `subject_id`**, which prevents creating multiple classes with different subjects for the same section.

## Solution

### 1. Code Improvements (Already Applied)

The `AdminController::createClass()` method has been updated to:
- Check for duplicate classes before insertion
- Provide clear, user-friendly error messages
- Handle `PDOException` specifically for constraint violations
- Explain the constraint issue when it occurs

### 2. Database Fix

You need to update the database constraint to include `subject_id`. This can be done in two ways:

#### Option A: Run the PHP Fix Script (Recommended)

```bash
php database/fix_class_constraint.php
```

This script will:
1. Drop the old `unique_class_assignment` constraint
2. Create a new `unique_class_section_subject` constraint that includes `subject_id`
3. Verify the changes

#### Option B: Run the SQL Script Manually

1. Open phpMyAdmin or your MySQL client
2. Select the `student_monitoring` database
3. Run the SQL script: `database/fix_unique_class_assignment_constraint.sql`

Or run these commands manually:

```sql
-- Drop the old constraint
ALTER TABLE `classes` DROP INDEX `unique_class_assignment`;

-- Create the new constraint with subject_id
ALTER TABLE `classes` 
ADD UNIQUE KEY `unique_class_section_subject` 
(`section_id`, `subject_id`, `semester`, `school_year`);
```

## What This Fixes

After applying the database fix:
- ✅ You can create multiple classes with **different subjects** for the same section
- ✅ Each section can have multiple classes (e.g., Math, English, Science, etc.)
- ✅ Duplicate classes with the exact same section + subject + semester + school year are still prevented
- ✅ The constraint ensures data integrity while allowing the flexibility you need

## Verification

After running the fix, verify it worked:

1. Try creating a class with subject A for section 1
2. Try creating another class with subject B for the same section 1
3. Both should succeed without constraint violations

## Error Messages

The improved error handling will now show:
- Clear messages when duplicates are detected
- Specific guidance when the constraint prevents multiple subjects
- Helpful suggestions for resolving the issue

## Notes

- The fix is backward compatible
- Existing classes are not affected
- The new constraint is more flexible and allows the intended functionality
- All unique constraint violations are now caught and handled gracefully

## Support

If you encounter any issues:
1. Check that the constraint was properly updated
2. Verify database connection settings
3. Check the application logs for detailed error messages
