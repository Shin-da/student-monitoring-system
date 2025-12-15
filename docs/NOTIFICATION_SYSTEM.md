# Centralized Notification System

## Overview

The notification system provides a unified way to send alerts, toasts, and persistent notifications across all user panels (Admin, Teacher, Student, Parent). It ensures accurate routing so each notification reaches the correct intended user or group.

## Features

- **Persistent Notifications**: Stored in database, visible across sessions
- **Flash Messages**: Session-based temporary notifications
- **Smart Routing**: Route to users, roles, sections, classes, or parents
- **Real-time Updates**: Polling system for live notification updates
- **Notification Center**: UI component with bell icon and dropdown
- **Unread Badge**: Visual indicator for unread notifications
- **Automatic Cleanup**: Expired notifications are automatically cleaned

## Database Schema

The `notifications` table stores all persistent notifications:

```sql
CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('info','success','warning','error','grade','attendance','assignment','schedule','user','system'),
  category VARCHAR(50),
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  icon VARCHAR(50),
  link VARCHAR(500),
  is_read TINYINT(1) DEFAULT 0,
  read_at TIMESTAMP NULL,
  priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
  metadata JSON,
  created_by INT UNSIGNED,
  expires_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Usage

### Flash Messages (Temporary, Session-based)

For immediate feedback after actions:

```php
use Helpers\Notification;

// Success message
Notification::success('Student created successfully!');

// Error message
Notification::error('Failed to create student. Please try again.');

// Warning message
Notification::warning('Schedule conflict detected!');

// Info message
Notification::info('Your profile has been updated.');
```

### Persistent Notifications (Database-stored)

#### Notify Specific User(s)

```php
use Helpers\Notification;

Notification::create(
    recipientIds: 123, // Single user ID
    type: 'success',
    category: 'user_management',
    title: 'Account Approved',
    message: 'Your account has been approved. You can now log in.',
    options: [
        'link' => '/dashboard',
        'priority' => 'high',
        'created_by' => $adminUserId,
    ]
);

// Multiple users
Notification::create(
    recipientIds: [123, 456, 789],
    type: 'info',
    category: 'system_alert',
    title: 'System Maintenance',
    message: 'Scheduled maintenance on Saturday at 2 AM.',
    options: [
        'expires_at' => '2025-12-01 00:00:00',
    ]
);
```

#### Notify by Role

```php
// Notify all teachers
Notification::createByRole(
    roles: 'teacher',
    type: 'info',
    category: 'schedule_change',
    title: 'New Class Assignment',
    message: 'You have been assigned to a new class.',
    options: ['link' => '/teacher/classes']
);

// Notify multiple roles
Notification::createByRole(
    roles: ['admin', 'teacher'],
    type: 'warning',
    category: 'system_alert',
    title: 'Database Backup Required',
    message: 'Please complete the weekly backup.',
);
```

#### Notify Section Members

```php
// Notify all students and adviser in a section
Notification::createForSection(
    sectionId: 5,
    type: 'assignment',
    category: 'assignment_new',
    title: 'New Assignment Posted',
    message: 'A new homework assignment has been posted for Mathematics.',
    options: [
        'link' => '/student/assignments',
        'metadata' => ['assignment_id' => 42, 'subject' => 'Mathematics'],
    ]
);
```

#### Notify Class Members

```php
// Notify all students and teacher in a class
Notification::createForClass(
    classId: 10,
    type: 'grade',
    category: 'grade_submitted',
    title: 'Grades Updated',
    message: 'New grades have been posted for Quarter 1.',
    options: ['link' => '/student/grades']
);
```

#### Notify Parents

```php
// Notify all parents of a student
Notification::createForParents(
    studentId: 25,
    type: 'grade',
    category: 'low_grade_alert',
    title: 'Grade Alert',
    message: 'Your child\'s grade in Mathematics has dropped below 75%.',
    options: [
        'link' => '/parent/grades',
        'priority' => 'urgent',
        'metadata' => ['subject' => 'Mathematics', 'grade' => 72],
    ]
);
```

## Notification Scenarios

### User Management (Admin)

#### User Registration Approval

```php
// In AdminController::approveUser()
Notification::create(
    recipientIds: $approvedUserId,
    type: 'success',
    category: 'approval_request',
    title: 'Account Approved',
    message: "Your registration has been approved. Welcome to the system!",
    options: ['link' => '/dashboard', 'created_by' => $adminId]
);

// Also notify admins
Notification::createByRole(
    roles: 'admin',
    type: 'info',
    category: 'user_management',
    title: 'User Approved',
    message: "User {$userName} has been approved.",
);
```

#### User Rejection

```php
Notification::create(
    recipientIds: $rejectedUserId,
    type: 'error',
    category: 'approval_request',
    title: 'Registration Rejected',
    message: "Your registration has been rejected. Reason: {$reason}",
    options: ['created_by' => $adminId]
);
```

#### User Suspension

```php
Notification::create(
    recipientIds: $suspendedUserId,
    type: 'error',
    category: 'user_management',
    title: 'Account Suspended',
    message: 'Your account has been suspended. Please contact the administrator.',
    options: ['priority' => 'urgent', 'created_by' => $adminId]
);
```

### Grade Management (Teacher)

#### Grade Submitted

```php
// In TeacherController when submitting grades
Notification::createForClass(
    classId: $classId,
    type: 'grade',
    category: 'grade_submitted',
    title: 'New Grade Posted',
    message: "A new {$gradeType} grade has been posted for {$subjectName}.",
    options: [
        'link' => "/student/grades?subject={$subjectId}",
        'metadata' => ['class_id' => $classId, 'subject_id' => $subjectId],
        'created_by' => $teacherId,
    ]
);

// Notify parents if grade is low
if ($gradeValue < 75) {
    Notification::createForParents(
        studentId: $studentId,
        type: 'grade',
        category: 'low_grade_alert',
        title: 'Low Grade Alert',
        message: "{$studentName}'s grade in {$subjectName} is {$gradeValue}%.",
        options: ['priority' => 'high', 'link' => '/parent/grades']
    );
}
```

### Assignment Management (Teacher)

#### Assignment Created

```php
// In TeacherController when creating assignment
Notification::createForClass(
    classId: $classId,
    type: 'assignment',
    category: 'assignment_new',
    title: 'New Assignment',
    message: "New {$assignmentType}: {$assignmentTitle}. Due: {$dueDate}",
    options: [
        'link' => "/student/assignments/{$assignmentId}",
        'metadata' => ['assignment_id' => $assignmentId],
        'created_by' => $teacherId,
    ]
);
```

#### Assignment Due Soon

```php
// In a scheduled job/cron task
$assignmentsDueSoon = getAssignmentsDueInDays(2); // Due in 2 days

foreach ($assignmentsDueSoon as $assignment) {
    Notification::createForClass(
        classId: $assignment['class_id'],
        type: 'warning',
        category: 'assignment_due',
        title: 'Assignment Due Soon',
        message: "{$assignment['title']} is due in 2 days.",
        options: ['link' => "/student/assignments/{$assignment['id']}"]
    );
}
```

### Attendance Management (Teacher)

#### Attendance Marked

```php
// After marking attendance
Notification::createForParents(
    studentId: $studentId,
    type: 'attendance',
    category: 'attendance_marked',
    title: 'Attendance Recorded',
    message: "{$studentName} was marked as {$status} on {$date}.",
    options: ['link' => '/parent/attendance']
);
```

#### Excessive Absences

```php
// In a scheduled check
if ($absenceCount >= 5) {
    Notification::createForParents(
        studentId: $studentId,
        type: 'attendance',
        category: 'attendance_alert',
        title: 'Attendance Alert',
        message: "{$studentName} has {$absenceCount} absences. Please contact the school.",
        options: ['priority' => 'urgent', 'link' => '/parent/attendance']
    );
    
    // Also notify adviser
    Notification::create(
        recipientIds: $adviserUserId,
        type: 'attendance',
        category: 'attendance_alert',
        title: 'Student Absence Alert',
        message: "{$studentName} has {$absenceCount} absences.",
        options: ['link' => "/teacher/attendance?student={$studentId}"]
    );
}
```

### Schedule Management (Admin)

#### Schedule Conflict Detected

```php
// In AdminController::createClass() when conflict detected
Notification::create(
    recipientIds: $adminId,
    type: 'error',
    category: 'schedule_change',
    title: 'Schedule Conflict',
    message: "Cannot create class: Teacher {$teacherName} has a conflict at {$schedule}.",
    options: ['priority' => 'high']
);
```

#### Class Created

```php
// After successfully creating a class
Notification::createForSection(
    sectionId: $sectionId,
    type: 'schedule',
    category: 'class_created',
    title: 'New Class Added',
    message: "New class: {$subjectName} with {$teacherName}. Schedule: {$schedule}",
    options: ['link' => "/student/schedule"]
);
```

### Section Management (Admin)

#### Student Assigned to Section

```php
// In AdminController::assignStudentToSection()
Notification::create(
    recipientIds: $studentUserId,
    type: 'success',
    category: 'section_assignment',
    title: 'Section Assignment',
    message: "You have been assigned to {$sectionName}.",
    options: ['link' => '/student/dashboard']
);

// Notify parents
Notification::createForParents(
    studentId: $studentId,
    type: 'info',
    category: 'section_assignment',
    title: 'Section Assignment',
    message: "{$studentName} has been assigned to {$sectionName}.",
);
```

### System Notifications

#### System Maintenance

```php
Notification::createByRole(
    roles: ['admin', 'teacher', 'student', 'parent'],
    type: 'system',
    category: 'system_alert',
    title: 'Scheduled Maintenance',
    message: 'The system will be unavailable on Saturday, Dec 1, 2025 from 2:00 AM to 4:00 AM.',
    options: [
        'expires_at' => '2025-12-01 04:00:00',
        'priority' => 'high',
    ]
);
```

## Frontend Integration

### Include Notification Center

Add to your layout file (e.g., `resources/views/layouts/dashboard.php`):

```php
<script src="<?= \Helpers\Url::asset('assets/notification-center.js') ?>"></script>
```

The notification center will automatically:
- Add a bell icon to the user menu area
- Show unread count badge
- Poll for new notifications every 30 seconds
- Display notifications in a dropdown

### Display Flash Messages

Flash messages are automatically rendered if you include:

```php
<?php if (\Helpers\Notification::has()): ?>
    <div class="notification-container">
        <?= \Helpers\Notification::renderHtml() ?>
    </div>
<?php endif; ?>
```

## API Endpoints

### Get Notifications
```
GET /api/notifications?limit=20&is_read=0&type=grade&category=grade_submitted
```

### Get Unread Count
```
GET /api/notifications/unread-count
```

### Mark as Read
```
POST /api/notifications/mark-read
Body: { "notification_id": 123 }
or
Body: { "mark_all": true }
```

### Delete Notification
```
POST /api/notifications/delete
Body: { "notification_id": 123 }
```

## Notification Categories

- `user_management` - User account actions
- `grade_update` - Grade changes
- `assignment_new` - New assignments
- `assignment_due` - Assignment deadlines
- `attendance_alert` - Attendance warnings
- `schedule_change` - Schedule modifications
- `system_alert` - System-wide alerts
- `approval_request` - Approval notifications
- `section_assignment` - Section changes
- `class_created` - New class notifications
- `grade_submitted` - Grade postings
- `attendance_marked` - Attendance records
- `low_grade_alert` - Low grade warnings

## Best Practices

1. **Use appropriate priorities**: Use 'urgent' sparingly, only for critical alerts
2. **Include links**: Always provide links so users can navigate to related content
3. **Set expiration dates**: For time-sensitive notifications, set `expires_at`
4. **Use categories**: Categorize notifications for better filtering
5. **Notify relevant users**: Use routing methods (section, class, parents) instead of manual user lists
6. **Clean up expired**: Run cleanup periodically (via cron) to remove expired notifications
7. **Combine with flash messages**: Use flash for immediate feedback, persistent for important events

## Maintenance

### Cleanup Expired Notifications

Create a cron job or scheduled task:

```php
use Services\NotificationManager;

$manager = new NotificationManager();
$deleted = $manager->cleanupExpired();
echo "Cleaned up {$deleted} expired notifications\n";
```

### Monitoring

Monitor notification table size and implement archiving for old notifications if needed.

