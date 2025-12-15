# Notification System Setup Guide

## Quick Setup

### 1. Database Setup

Run the SQL script to create the notifications table:

```bash
mysql -u your_user -p your_database < database/create_notifications_table.sql
```

Or manually execute the SQL in `database/create_notifications_table.sql`.

### 2. Verify Files Are Created

Ensure these files exist:
- ✅ `app/Services/NotificationManager.php`
- ✅ `app/Controllers/NotificationController.php`
- ✅ `app/Helpers/Notification.php` (enhanced)
- ✅ `public/assets/notification-center.js`
- ✅ `database/create_notifications_table.sql`

### 3. Verify Routes

Check that routes are added in `routes/web.php`:
```php
$router->get('/api/notifications', [NotificationController::class, 'getNotifications']);
$router->get('/api/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
$router->post('/api/notifications/mark-read', [NotificationController::class, 'markAsRead']);
$router->post('/api/notifications/delete', [NotificationController::class, 'delete']);
```

### 4. Verify Frontend Integration

Ensure `notification-center.js` is loaded in your layouts:
- `resources/views/layouts/dashboard.php`
- `resources/views/layouts/dashboard-optimized.php`

The script should be included before the closing `</body>` tag.

### 5. Test the System

#### Test Flash Messages

Add to any controller:
```php
use Helpers\Notification;

Notification::success('Test flash message');
```

#### Test Persistent Notification

Add to any controller:
```php
use Helpers\Notification;
use Core\Session;

$user = Session::get('user');
if ($user) {
    Notification::create(
        recipientIds: $user['id'],
        type: 'info',
        category: 'system_alert',
        title: 'Welcome!',
        message: 'The notification system is working!',
        options: ['link' => '/dashboard']
    );
}
```

### 6. Verify Notification Center UI

1. Log in to the dashboard
2. Look for a bell icon in the sidebar footer or header
3. Click the bell icon
4. You should see a dropdown with notifications

## Notification Scenarios Checklist

### User Management
- [ ] User registration approval → Notify student
- [ ] User rejection → Notify student
- [ ] User suspension → Notify user
- [ ] User activation → Notify user
- [ ] Student assigned to section → Notify student + parents

### Grade Management
- [ ] Grade submitted → Notify student + parents (if low)
- [ ] Low grade alert → Notify parents + adviser

### Assignment Management
- [ ] Assignment created → Notify class
- [ ] Assignment due soon → Notify students (cron job)

### Attendance Management
- [ ] Attendance marked → Notify parents (if absent)
- [ ] Excessive absences → Notify parents + adviser

### Schedule Management
- [ ] Schedule conflict → Notify admin
- [ ] Class created → Notify teacher + section

### System Notifications
- [ ] System maintenance → Notify all roles

## Integration Checklist

When integrating into a controller:

1. ✅ Import the Notification helper: `use Helpers\Notification;`
2. ✅ Add flash message for immediate feedback: `Notification::success('Message');`
3. ✅ Add persistent notification for important events: `Notification::create(...)`
4. ✅ Use appropriate routing (user, role, section, class, parents)
5. ✅ Include relevant links and metadata
6. ✅ Set appropriate priority and expiration if needed

## Troubleshooting

### Notification center not appearing
- Check browser console for JavaScript errors
- Verify `notification-center.js` is loaded
- Check that user menu area exists in layout

### Notifications not saving
- Verify database table exists
- Check database connection
- Review PHP error logs

### Notifications not showing
- Check API endpoint is accessible: `/api/notifications`
- Verify user is logged in
- Check browser network tab for API errors

### Unread count not updating
- Check polling interval (default 30 seconds)
- Verify `/api/notifications/unread-count` endpoint
- Check browser console for errors

## Performance Considerations

- The notification center polls every 30 seconds by default
- Only polls when browser tab is visible
- Database queries are indexed for performance
- Consider implementing WebSocket for real-time updates (future enhancement)

## Maintenance

### Cleanup Expired Notifications

Create a cron job to run daily:

```bash
0 2 * * * php /path/to/your/app/scripts/cleanup-notifications.php
```

Create `app/scripts/cleanup-notifications.php`:
```php
<?php
require_once __DIR__ . '/../bootstrap.php';

use Services\NotificationManager;

$manager = new NotificationManager();
$deleted = $manager->cleanupExpired();
echo "Cleaned up {$deleted} expired notifications\n";
```

## Next Steps

1. Integrate notifications into your controllers (see `NOTIFICATION_INTEGRATION_EXAMPLES.md`)
2. Test all notification scenarios
3. Customize notification messages for your school
4. Set up scheduled tasks for assignment reminders
5. Consider email notifications (future enhancement)
6. Consider push notifications (future enhancement)

