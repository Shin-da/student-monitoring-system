# AI Features Setup Instructions

**Quick Start Guide for AI-Powered Performance Analytics**

---

## 🚀 Installation Steps

### Step 1: Create Database Table

Run the SQL migration to create the `performance_alerts` table:

```sql
-- Option 1: Via phpMyAdmin or MySQL client
SOURCE database/create_performance_alerts_table.sql;

-- Option 2: Copy and paste the SQL from the file
-- See: database/create_performance_alerts_table.sql
```

**Verify Installation:**
```sql
SHOW TABLES LIKE 'performance_alerts';
DESCRIBE performance_alerts;
```

---

### Step 2: Verify Files Are in Place

Check that these files exist:
- ✅ `app/Services/PerformanceAnalyzer.php`
- ✅ `app/Services/AlertService.php`
- ✅ `app/Services/analyze-performance-batch.php`
- ✅ `database/create_performance_alerts_table.sql`

---

### Step 3: Test the System

#### Test 1: Manual Grade Entry
1. Log in as a teacher
2. Submit a grade for a student with score < 75
3. Check if alert appears in `/teacher/alerts`
4. Check if student receives notification

#### Test 2: Batch Processing
```bash
cd C:\xampp\htdocs\student-monitoring
php app/Services/analyze-performance-batch.php
```

Expected output:
```
[2025-01-27 10:00:00] Starting performance analysis...
Analyzed X students
At-risk students: Y
High-risk students: Z
Alerts generated: N
[2025-01-27 10:00:05] Analysis complete.
```

---

### Step 4: Set Up Daily Batch Processing (Optional but Recommended)

#### Windows (Task Scheduler)

1. Open Task Scheduler
2. Create Basic Task
3. Set trigger: Daily at 2:00 AM
4. Action: Start a program
5. Program: `C:\xampp\php\php.exe`
6. Arguments: `C:\xampp\htdocs\student-monitoring\app\Services\analyze-performance-batch.php`
7. Start in: `C:\xampp\htdocs\student-monitoring`

#### Linux/Mac (Cron)

Add to crontab:
```bash
crontab -e
```

Add this line:
```
0 2 * * * cd /path/to/student-monitoring && php app/Services/analyze-performance-batch.php >> logs/performance-analysis.log 2>&1
```

---

## ✅ Verification Checklist

- [ ] Database table `performance_alerts` exists
- [ ] All service files are in place
- [ ] Grade submission triggers analysis (check logs)
- [ ] Attendance entry triggers analysis (check logs)
- [ ] Batch script runs without errors
- [ ] Alerts appear in teacher dashboard
- [ ] Notifications sent to students
- [ ] Notifications sent to parents

---

## 🔍 Troubleshooting

### Issue: "Table 'performance_alerts' doesn't exist"

**Solution:** Run the SQL migration file:
```sql
SOURCE database/create_performance_alerts_table.sql;
```

### Issue: "Class 'Services\PerformanceAnalyzer' not found"

**Solution:** Check autoloader includes Services namespace. Verify file exists at:
```
app/Services/PerformanceAnalyzer.php
```

### Issue: No alerts being generated

**Check:**
1. Students have grades < 75?
2. Check PHP error logs for errors
3. Verify database connection
4. Run batch script manually to see errors

### Issue: Batch script fails

**Check:**
1. PHP path is correct
2. Database credentials in config.php
3. File permissions (read/write)
4. Check error output

---

## 📊 Monitoring

### Check Alert Generation

```sql
-- View recent alerts
SELECT * FROM performance_alerts 
ORDER BY created_at DESC 
LIMIT 10;

-- Count active alerts
SELECT COUNT(*) FROM performance_alerts 
WHERE status = 'active';

-- Count by severity
SELECT severity, COUNT(*) 
FROM performance_alerts 
WHERE status = 'active'
GROUP BY severity;
```

### Check Analysis Logs

If using cron with logging:
```bash
tail -f logs/performance-analysis.log
```

---

## 🎯 What Happens Now?

### Automatic (Real-Time)

1. **When teacher submits grade:**
   - Grade saved → Analysis runs → Alert generated if risk detected → Notifications sent

2. **When teacher marks attendance:**
   - Attendance saved → Analysis runs → Alert generated if attendance poor → Notifications sent

### Scheduled (Daily)

1. **Batch script runs (if configured):**
   - Analyzes all students → Generates alerts → Updates existing alerts

---

## 📝 Next Steps

1. ✅ **Installation Complete** - System is ready
2. 📊 **Monitor** - Watch for alerts in first few days
3. 🔧 **Tune** - Adjust risk thresholds if needed (see `PerformanceAnalyzer.php`)
4. 📈 **Analyze** - Review alert patterns and effectiveness

---

## 🆘 Support

If you encounter issues:

1. Check PHP error logs
2. Check database for `performance_alerts` table
3. Verify all files are in place
4. Test batch script manually
5. Review `docs/AI_IMPLEMENTATION_GUIDE.md` for details

---

**Status:** ✅ Ready to Use!

