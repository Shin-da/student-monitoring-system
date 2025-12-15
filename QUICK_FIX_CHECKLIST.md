# Quick Fix Checklist - Database Schema Fix

## ⚡ Quick Reference

### 1. BACKUP (5 minutes)
- [ ] Open phpMyAdmin: `http://localhost/phpmyadmin`
- [ ] Select `student_monitoring` database
- [ ] Click **Export** → **Quick** → **Go**
- [ ] Save as `backup_YYYY-MM-DD.sql`
- [ ] ✅ Backup file downloaded

### 2. RUN FIX (5-15 minutes)
- [ ] Open phpMyAdmin → Select `student_monitoring` → **SQL** tab
- [ ] Open `DATABASE_SCHEMA_FIX.sql` file
- [ ] Copy entire contents → Paste into SQL text area
- [ ] Click **Go**
- [ ] ✅ Script executed (may show some "already exists" errors - that's OK)

### 3. VERIFY (2 minutes)
```sql
-- Run this in SQL tab:
SELECT COUNT(*) as fk_count 
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'student_monitoring' 
AND REFERENCED_TABLE_NAME IS NOT NULL;
-- Should show 22+ foreign keys
```
- [ ] ✅ Foreign keys present (22+)

### 4. TEST CLASS CREATION (2 minutes)
- [ ] Login as admin → Go to Class Management
- [ ] Create new class (fill all fields)
- [ ] Click Submit
- [ ] Check database:
  ```sql
  SELECT * FROM teacher_schedules ORDER BY id DESC LIMIT 1;
  ```
- [ ] ✅ New schedule entry exists

### 5. TEST DROPDOWN (1 minute)
- [ ] Go to Class Creation page
- [ ] Select teacher from dropdown
- [ ] ✅ Schedule displays below dropdown

### 6. TEST DUPLICATE VALIDATION (2 minutes)
- [ ] Create class: Section A, Math, 1st sem
- [ ] Try to create duplicate: Section A, Math, 1st sem
- [ ] ✅ Duplicate blocked
- [ ] Try valid: Section A, English, 1st sem
- [ ] ✅ Valid class created

### 7. FIX PRELOAD WARNINGS (5 minutes)
- [ ] Open browser console (F12)
- [ ] Note preload warning URLs
- [ ] Find `<link rel="preload">` tags in HTML
- [ ] Fix paths or remove unused preloads
- [ ] ✅ Warnings gone

---

## 🚨 If Something Goes Wrong

### Restore Backup
```sql
-- In phpMyAdmin:
-- 1. Drop database: DROP DATABASE student_monitoring;
-- 2. Create database: CREATE DATABASE student_monitoring;
-- 3. Import tab → Select backup file → Go
```

### Check Errors
- PHP logs: `C:\xampp\php\logs\php_error_log`
- App logs: `logs/activity.log`
- Browser console: F12 → Console tab

---

## ✅ Success Criteria

- [ ] 22+ foreign keys in database
- [ ] No duplicate rows in teachers/classes
- [ ] Class creation saves schedules
- [ ] Teacher dropdown shows schedules
- [ ] Duplicate validation works correctly
- [ ] No preload warnings in console

---

**Total Time: ~20-30 minutes**

For detailed instructions, see `STEP_BY_STEP_FIX_GUIDE.md`

