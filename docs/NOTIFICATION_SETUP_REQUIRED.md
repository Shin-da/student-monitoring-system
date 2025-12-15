# ⚠️ IMPORTANT: Notification Table Setup Required

The notification system requires the `notifications` table to exist in your database. If you're seeing errors like "Unable to load notifications", the table may not exist.

## Quick Setup

Run this command in your terminal from the project root:

```bash
php database/setup_notifications_table.php
```

Or manually run the SQL file in phpMyAdmin or your database client:

```bash
database/create_notifications_table.sql
```

## Verify Table Exists

You can verify the table exists by running:

```sql
SHOW TABLES LIKE 'notifications';
```

If it returns 0 rows, you need to create the table using one of the methods above.

## After Setup

1. The notification dropdown should work
2. The `/notifications` page should be accessible
3. Notifications will start appearing as actions occur

## Troubleshooting

If notifications still don't load after creating the table:

1. Check browser console for JavaScript errors
2. Check network tab to see if `/api/notifications` returns data
3. Check PHP error logs for database errors
4. Verify your database connection in `config/config.php`

