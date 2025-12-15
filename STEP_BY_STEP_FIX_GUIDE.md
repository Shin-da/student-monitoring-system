# Step-by-Step Guide: Running Database Fix & Testing

## 📋 Prerequisites

- Access to phpMyAdmin or MySQL command line
- Admin access to your database
- Backup capability

---

## STEP 1: BACKUP YOUR DATABASE

### Option A: Using phpMyAdmin (Recommended for Beginners)

1. **Open phpMyAdmin**
   - Navigate to: `http://localhost/phpmyadmin` (or your phpMyAdmin URL)
   - Login with your database credentials

2. **Select Database**
   - Click on `student_monitoring` in the left sidebar

3. **Export Database**
   - Click the **"Export"** tab at the top
   - Select **"Quick"** export method
   - Format: **SQL**
   - Click **"Go"** button
   - Save the file as `student_monitoring_backup_YYYY-MM-DD.sql` (replace with today's date)

4. **Verify Backup**
   - Check that the file was downloaded
   - File size should be similar to your current database size (check `student_monitoring (3).sql` size)

### Option B: Using Command Line (Advanced)

```bash
# Navigate to your project directory
cd C:\xampp\htdocs\student-monitoring

# Create backup (adjust username, password, and database name as needed)
mysqldump -u root -p student_monitoring > database_backup_$(date +%Y%m%d_%H%M%S).sql

# Enter your MySQL password when prompted
```

### Option C: Using XAMPP Control Panel

1. Open **XAMPP Control Panel**
2. Click **"Shell"** button
3. Run:
   ```bash
   cd C:\xampp\mysql\bin
   mysqldump -u root -p student_monitoring > C:\xampp\htdocs\student-monitoring\database_backup.sql
   ```
4. Enter MySQL password when prompted

---

## STEP 2: VERIFY BACKUP (IMPORTANT!)

### Test Backup Restoration (Optional but Recommended)

1. **Create Test Database**
   ```sql
   CREATE DATABASE student_monitoring_test;
   ```

2. **Import Backup to Test Database**
   - In phpMyAdmin: Select `student_monitoring_test` → Import → Choose backup file → Go
   - Or command line:
     ```bash
     mysql -u root -p student_monitoring_test < database_backup.sql
     ```

3. **Verify Data**
   - Check that tables exist
   - Check that data is present
   - If successful, you can proceed with confidence

---

## STEP 3: RUN DATABASE_SCHEMA_FIX.SQL

### Option A: Using phpMyAdmin (Recommended)

1. **Open phpMyAdmin**
   - Navigate to: `http://localhost/phpmyadmin`
   - Select `student_monitoring` database

2. **Open SQL Tab**
   - Click the **"SQL"** tab at the top

3. **Load Fix Script**
   - Click **"Import files"** button
   - OR copy the entire contents of `DATABASE_SCHEMA_FIX.sql`
   - Paste into the SQL text area

4. **Execute Script**
   - Click **"Go"** button
   - Wait for execution to complete (may take 1-5 minutes depending on data size)

5. **Check Results**
   - Look for success messages
   - Check for any errors (should be minimal - some "constraint already exists" errors are OK)
   - If you see errors, note them down

### Option B: Using Command Line

```bash
# Navigate to project directory
cd C:\xampp\htdocs\student-monitoring

# Run the fix script
mysql -u root -p student_monitoring < DATABASE_SCHEMA_FIX.sql

# Enter MySQL password when prompted
```

### Option C: Using MySQL Workbench

1. Open MySQL Workbench
2. Connect to your database
3. File → Open SQL Script → Select `DATABASE_SCHEMA_FIX.sql`
4. Click the ⚡ (Execute) button
5. Wait for completion

---

## STEP 4: VERIFY FIX WAS SUCCESSFUL

### Run Validation Queries

1. **Open phpMyAdmin SQL Tab**
   - Select `student_monitoring` database
   - Click **"SQL"** tab

2. **Run Validation Queries**
   - Open `DATABASE_VALIDATION_QUERIES.sql`
   - Copy and paste queries one by one, or run the entire file
   - Check results:
     - ✅ All teachers should be linked to valid users
     - ✅ No duplicate rows should exist
     - ✅ All foreign keys should be present
     - ✅ All AUTO_INCREMENT should be enabled

### Quick Verification Queries

```sql
-- Check foreign keys were added
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'student_monitoring'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;

-- Should show 22+ foreign key constraints

-- Check no duplicate teachers
SELECT user_id, COUNT(*) as count
FROM teachers
GROUP BY user_id
HAVING count > 1;
-- Should return 0 rows

-- Check no duplicate classes
SELECT section_id, subject_id, semester, school_year, COUNT(*) as count
FROM classes
GROUP BY section_id, subject_id, semester, school_year
HAVING count > 1;
-- Should return 0 rows
```

---

## STEP 5: TEST CLASS CREATION (Issue #1 Fix)

### Test Steps:

1. **Login as Admin**
   - Navigate to your admin dashboard
   - Go to Class Management page

2. **Create a New Class**
   - Fill in all required fields:
     - Section: Select any section
     - Subject: Select any subject
     - Teacher: Select any teacher
     - Schedule: Enter "M 8:00 AM-9:00 AM" (or similar)
     - Room: Enter "Room 101"
   - Click **"Create Class"** or **"Submit"**

3. **Verify Schedule Was Saved**
   - Open phpMyAdmin
   - Select `student_monitoring` database
   - Run this query:
     ```sql
     SELECT ts.*, t.user_id, u.name as teacher_name, c.schedule as class_schedule
     FROM teacher_schedules ts
     JOIN teachers t ON ts.teacher_id = t.id
     JOIN users u ON t.user_id = u.id
     LEFT JOIN classes c ON ts.class_id = c.id
     ORDER BY ts.id DESC
     LIMIT 5;
     ```
   - ✅ You should see the new schedule entry
   - ✅ `teacher_id` should match the teacher you selected
   - ✅ `class_id` should match the newly created class

4. **Expected Result**
   - ✅ Class created successfully
   - ✅ No errors about schedule not saving
   - ✅ Schedule appears in `teacher_schedules` table

---

## STEP 6: TEST TEACHER DROPDOWN (Issue #2 Fix)

### Test Steps:

1. **Go to Class Creation Page**
   - Navigate to admin → Class Management
   - Click "Create New Class" or similar button

2. **Select a Teacher from Dropdown**
   - Click the **"Teacher"** dropdown
   - Select any teacher

3. **Verify Schedule Loads**
   - ✅ A schedule display should appear below the dropdown
   - ✅ You should see the teacher's existing schedules
   - ✅ Schedules should show:
     - Day of week
     - Time (start - end)
     - Subject name
     - Section name
     - Class ID (if linked to a class)

4. **Test with Different Teachers**
   - Select different teachers
   - ✅ Each teacher's schedule should load correctly
   - ✅ Schedules should be different for each teacher

5. **Verify in Database**
   ```sql
   -- Check teacher schedules are linked correctly
   SELECT 
       ts.id,
       ts.teacher_id,
       t.user_id,
       u.name as teacher_name,
       ts.day_of_week,
       ts.start_time,
       ts.end_time,
       ts.class_id,
       c.schedule as class_schedule
   FROM teacher_schedules ts
   JOIN teachers t ON ts.teacher_id = t.id
   JOIN users u ON t.user_id = u.id
   LEFT JOIN classes c ON ts.class_id = c.id
   WHERE ts.teacher_id = ?;  -- Replace ? with a teacher_id
   ```
   - ✅ All schedules should have valid `teacher_id`
   - ✅ All schedules should link to valid teachers

---

## STEP 7: TEST DUPLICATE VALIDATION (Issue #3 Fix)

### Test Steps:

1. **Create First Class**
   - Section: "Grade 7 - Section A"
   - Subject: "Mathematics"
   - Semester: "1st"
   - School Year: "2025-2026"
   - Teacher: Any teacher
   - Click **"Create Class"**
   - ✅ Should succeed

2. **Try to Create Duplicate Class**
   - Same Section: "Grade 7 - Section A"
   - Same Subject: "Mathematics"
   - Same Semester: "1st"
   - Same School Year: "2025-2026"
   - Different Teacher: (try different teacher)
   - Click **"Create Class"**
   - ✅ Should be BLOCKED with error: "A class for Mathematics in Grade 7 - Section A already exists..."

3. **Try to Create Valid Different Class**
   - Same Section: "Grade 7 - Section A"
   - Different Subject: "English"
   - Same Semester: "1st"
   - Same School Year: "2025-2026"
   - Any Teacher
   - Click **"Create Class"**
   - ✅ Should SUCCEED (different subject = valid)

4. **Try Another Valid Combination**
   - Same Section: "Grade 7 - Section A"
   - Same Subject: "Mathematics"
   - Different Semester: "2nd"
   - Same School Year: "2025-2026"
   - Any Teacher
   - Click **"Create Class"**
   - ✅ Should SUCCEED (different semester = valid)

5. **Expected Results**
   - ✅ Only truly duplicate classes are blocked
   - ✅ Valid combinations (different subject/semester) are allowed
   - ✅ No false positives

---

## STEP 8: FIX PRELOAD WARNINGS (Issue #4)

### Step 1: Identify the Warnings

1. **Open Browser Developer Tools**
   - Press `F12` or `Ctrl+Shift+I` (Windows) / `Cmd+Option+I` (Mac)
   - Go to **Console** tab

2. **Look for Preload Warnings**
   - Warnings will look like:
     ```
     The resource <URL> was preloaded using link preload but not used within a few seconds of the window's load event.
     ```
   - Note down the resource URLs that are failing

### Step 2: Find Preload Tags in HTML

1. **Open Your HTML Files**
   - Check `resources/views/layouts/dashboard.php` or similar layout files
   - Look for `<head>` section

2. **Search for Preload Tags**
   ```html
   <!-- Look for tags like these: -->
   <link rel="preload" href="/path/to/resource.css" as="style">
   <link rel="preload" href="/path/to/resource.js" as="script">
   <link rel="prefetch" href="/path/to/resource">
   ```

### Step 3: Fix Preload Issues

**Option A: Remove Unused Preloads**
```html
<!-- If resource is not actually used, remove the preload tag -->
<!-- BEFORE: -->
<link rel="preload" href="/assets/css/unused.css" as="style">

<!-- AFTER: -->
<!-- Remove the line entirely -->
```

**Option A: Fix Incorrect Paths**
```html
<!-- If path is wrong, fix it -->
<!-- BEFORE: -->
<link rel="preload" href="/wrong/path/style.css" as="style">

<!-- AFTER: -->
<link rel="preload" href="/assets/css/style.css" as="style">
```

**Option C: Add Missing Resources**
- If preload points to a resource that doesn't exist:
  - Either create the resource
  - Or remove the preload tag

### Step 4: Verify Fix

1. **Clear Browser Cache**
   - Press `Ctrl+Shift+Delete` (Windows) / `Cmd+Shift+Delete` (Mac)
   - Clear cached images and files

2. **Reload Page**
   - Press `F5` or `Ctrl+R`
   - Check console again
   - ✅ Preload warnings should be gone

### Common Preload Issues:

1. **CSS Files Not Loaded**
   - Check if CSS file exists at the path
   - Verify file permissions
   - Check if CSS is actually used on the page

2. **JavaScript Files Not Loaded**
   - Check if JS file exists at the path
   - Verify file is actually needed
   - Check for JavaScript errors preventing execution

3. **Font Files Not Loaded**
   - Verify font files exist
   - Check font-face declarations match preload paths

---

## STEP 9: TROUBLESHOOTING

### If Database Fix Fails:

1. **Check Error Messages**
   - Note the exact error message
   - Check which step failed

2. **Common Issues:**
   - **"Foreign key constraint fails"**: Some orphaned data still exists
     - Solution: Run data cleanup queries manually first
   - **"Duplicate entry"**: Constraint already exists
     - Solution: This is OK, the constraint is already there
   - **"Table doesn't exist"**: Wrong database selected
     - Solution: Make sure you selected `student_monitoring` database

3. **Partial Fix Recovery**
   - If fix partially completed, restore from backup
   - Try running fix again
   - Or run sections of the fix script individually

### If Class Creation Still Fails:

1. **Check Error Logs**
   - Check PHP error logs: `C:\xampp\php\logs\php_error_log`
   - Check application logs: `logs/activity.log`

2. **Verify Foreign Keys**
   ```sql
   -- Check if teacher exists
   SELECT * FROM teachers WHERE id = ?;  -- Replace ? with teacher_id
   
   -- Check if section exists
   SELECT * FROM sections WHERE id = ?;  -- Replace ? with section_id
   
   -- Check if subject exists
   SELECT * FROM subjects WHERE id = ?;  -- Replace ? with subject_id
   ```

3. **Check Teacher Schedules Table**
   ```sql
   -- Check if schedules are being created
   SELECT * FROM teacher_schedules ORDER BY id DESC LIMIT 10;
   ```

### If Dropdown Still Doesn't Work:

1. **Check Browser Console**
   - Open Developer Tools (F12)
   - Check for JavaScript errors
   - Check Network tab for failed API requests

2. **Test API Directly**
   - Navigate to: `http://localhost/student-monitoring/api/admin/teacher-schedule.php?teacher_id=1`
   - Should return JSON with schedules
   - If error, check PHP error logs

3. **Verify Teacher ID**
   ```sql
   -- Check teacher exists
   SELECT t.id, t.user_id, u.name 
   FROM teachers t 
   JOIN users u ON t.user_id = u.id 
   WHERE t.id = ?;  -- Replace ? with teacher_id from dropdown
   ```

---

## STEP 10: FINAL VERIFICATION CHECKLIST

After completing all steps, verify:

- [ ] Database backup created and verified
- [ ] Database fix script executed successfully
- [ ] All foreign keys are present (22+ constraints)
- [ ] No duplicate rows in teachers, classes, teacher_schedules
- [ ] Class creation saves teacher schedules
- [ ] Teacher dropdown loads schedules correctly
- [ ] Duplicate validation only blocks real duplicates
- [ ] Preload warnings fixed (if applicable)
- [ ] No PHP errors in logs
- [ ] No JavaScript errors in browser console
- [ ] All features working as expected

---

## QUICK REFERENCE COMMANDS

### Backup Database
```bash
mysqldump -u root -p student_monitoring > backup.sql
```

### Run Fix Script
```bash
mysql -u root -p student_monitoring < DATABASE_SCHEMA_FIX.sql
```

### Check Foreign Keys
```sql
SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'student_monitoring' 
AND REFERENCED_TABLE_NAME IS NOT NULL;
-- Should return 22+
```

### Check Duplicates
```sql
-- Teachers
SELECT user_id, COUNT(*) FROM teachers GROUP BY user_id HAVING COUNT(*) > 1;
-- Should return 0 rows

-- Classes
SELECT section_id, subject_id, semester, school_year, COUNT(*) 
FROM classes 
GROUP BY section_id, subject_id, semester, school_year 
HAVING COUNT(*) > 1;
-- Should return 0 rows
```

---

## SUPPORT

If you encounter issues:

1. Check error messages carefully
2. Review `DATABASE_DIAGNOSTIC_REPORT.md` for context
3. Review `TEACHER_SCHEDULE_ISSUES_ANALYSIS.md` for issue-specific help
4. Check PHP and MySQL error logs
5. Verify database state with validation queries

---

*End of Step-by-Step Guide*

