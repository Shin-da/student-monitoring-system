# Notification System Integration Examples

This document provides concrete examples of how to integrate notifications into your controllers.

## AdminController Integration

### User Approval

```php
// In AdminController::approveUser()
use Helpers\Notification;

public function approveUser(): void
{
    // ... existing approval logic ...
    
    if ($approved) {
        // Flash message for immediate feedback
        Notification::success('User approved successfully');
        
        // Persistent notification to the approved user
        Notification::create(
            recipientIds: $userId,
            type: 'success',
            category: 'approval_request',
            title: 'Account Approved',
            message: "Your registration has been approved. You can now log in to the system.",
            options: [
                'link' => '/login',
                'created_by' => $user['id'],
            ]
        );
        
        // Notify admins
        Notification::createByRole(
            roles: 'admin',
            type: 'info',
            category: 'user_management',
            title: 'User Approved',
            message: "User {$userName} ({$userEmail}) has been approved by {$adminName}.",
        );
    }
}
```

### Student Assignment to Section

```php
// In AdminController::assignStudentToSection()
use Helpers\Notification;

public function assignStudentToSection(): void
{
    // ... existing assignment logic ...
    
    if ($assigned) {
        Notification::success("Student assigned to {$sectionName}");
        
        // Notify student
        $studentUser = getUserById($studentUserId);
        Notification::create(
            recipientIds: $studentUserId,
            type: 'success',
            category: 'section_assignment',
            title: 'Section Assignment',
            message: "You have been assigned to {$sectionName}. Check your schedule for details.",
            options: [
                'link' => '/student/dashboard',
                'metadata' => ['section_id' => $sectionId],
                'created_by' => $user['id'],
            ]
        );
        
        // Notify parents
        Notification::createForParents(
            studentId: $studentId,
            type: 'info',
            category: 'section_assignment',
            title: 'Section Assignment',
            message: "{$studentName} has been assigned to {$sectionName} for the current school year.",
            options: ['link' => '/parent/profile']
        );
        
        // Notify section adviser if exists
        if ($adviserId) {
            Notification::create(
                recipientIds: $adviserId,
                type: 'info',
                category: 'section_assignment',
                title: 'New Student in Section',
                message: "{$studentName} has been added to your section: {$sectionName}.",
                options: ['link' => "/teacher/sections?section={$sectionId}"]
            );
        }
    }
}
```

## TeacherController Integration

### Grade Submission

```php
// In TeacherController or GradeController when submitting grades
use Helpers\Notification;

public function submitGrade(): void
{
    // ... existing grade submission logic ...
    
    if ($gradeSaved) {
        Notification::success('Grade submitted successfully');
        
        // Get subject and class info
        $subject = getSubjectById($subjectId);
        $class = getClassById($classId);
        $gradeTypeLabel = match($gradeType) {
            'ww' => 'Written Work',
            'pt' => 'Performance Task',
            'qe' => 'Quarterly Exam',
            default => 'Grade',
        };
        
        // Notify student
        Notification::create(
            recipientIds: $studentUserId,
            type: 'grade',
            category: 'grade_submitted',
            title: 'New Grade Posted',
            message: "A new {$gradeTypeLabel} grade has been posted for {$subject['name']}: {$gradeValue}/{$maxScore}",
            options: [
                'link' => "/student/grades?subject={$subjectId}",
                'metadata' => [
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                    'grade_type' => $gradeType,
                    'grade_value' => $gradeValue,
                    'max_score' => $maxScore,
                ],
                'created_by' => $teacherUserId,
            ]
        );
        
        // Notify parents if grade is concerning
        $percentage = ($gradeValue / $maxScore) * 100;
        if ($percentage < 75) {
            Notification::createForParents(
                studentId: $studentId,
                type: 'grade',
                category: 'low_grade_alert',
                title: 'Low Grade Alert',
                message: "{$studentName}'s {$gradeTypeLabel} grade in {$subject['name']} is {$percentage}% ({$gradeValue}/{$maxScore}).",
                options: [
                    'priority' => 'high',
                    'link' => '/parent/grades',
                    'metadata' => [
                        'subject' => $subject['name'],
                        'grade' => $percentage,
                        'grade_type' => $gradeTypeLabel,
                    ],
                ]
            );
        }
    }
}
```

### Assignment Creation

```php
// In TeacherController when creating assignment
use Helpers\Notification;

public function createAssignment(): void
{
    // ... existing assignment creation logic ...
    
    if ($assignmentCreated) {
        Notification::success('Assignment created successfully');
        
        // Get class and subject info
        $class = getClassById($classId);
        $subject = getSubjectById($subjectId);
        $dueDateFormatted = date('F j, Y', strtotime($dueDate));
        
        // Notify all students in the class
        Notification::createForClass(
            classId: $classId,
            type: 'assignment',
            category: 'assignment_new',
            title: 'New Assignment Posted',
            message: "New {$assignmentType}: {$title}. Due date: {$dueDateFormatted}",
            options: [
                'link' => "/student/assignments/{$assignmentId}",
                'priority' => $isUrgent ? 'high' : 'normal',
                'metadata' => [
                    'assignment_id' => $assignmentId,
                    'subject_id' => $subjectId,
                    'due_date' => $dueDate,
                    'assignment_type' => $assignmentType,
                ],
                'created_by' => $teacherUserId,
            ]
        );
        
        // Also notify parents if it's a major assignment
        if ($assignmentType === 'exam' || $assignmentType === 'project') {
            $students = getStudentsInClass($classId);
            foreach ($students as $student) {
                Notification::createForParents(
                    studentId: $student['id'],
                    type: 'assignment',
                    category: 'assignment_new',
                    title: 'Important Assignment Posted',
                    message: "{$student['name']} has a new {$assignmentType} in {$subject['name']}: {$title}. Due: {$dueDateFormatted}",
                    options: ['link' => '/parent/dashboard']
                );
            }
        }
    }
}
```

### Attendance Marking

```php
// In TeacherController when marking attendance
use Helpers\Notification;

public function markAttendance(): void
{
    // ... existing attendance logic ...
    
    if ($attendanceRecorded) {
        // Get attendance summary for the day
        $absentStudents = getAbsentStudentsForDate($sectionId, $subjectId, $date);
        
        foreach ($absentStudents as $student) {
            // Notify parents of absences
            Notification::createForParents(
                studentId: $student['id'],
                type: 'attendance',
                category: 'attendance_marked',
                title: 'Attendance Recorded',
                message: "{$student['name']} was marked as absent on " . date('F j, Y', strtotime($date)) . " for {$subjectName}.",
                options: [
                    'link' => '/parent/attendance',
                    'metadata' => [
                        'date' => $date,
                        'status' => 'absent',
                        'subject' => $subjectName,
                    ],
                ]
            );
        }
        
        // Check for excessive absences and alert
        foreach ($absentStudents as $student) {
            $totalAbsences = getTotalAbsences($student['id'], $subjectId);
            if ($totalAbsences >= 5) {
                Notification::createForParents(
                    studentId: $student['id'],
                    type: 'attendance',
                    category: 'attendance_alert',
                    title: 'Attendance Alert',
                    message: "{$student['name']} has {$totalAbsences} absences in {$subjectName}. Please contact the school.",
                    options: [
                        'priority' => 'urgent',
                        'link' => '/parent/attendance',
                    ]
                );
                
                // Also notify adviser
                $adviserId = getSectionAdviser($sectionId);
                if ($adviserId) {
                    Notification::create(
                        recipientIds: $adviserId,
                        type: 'attendance',
                        category: 'attendance_alert',
                        title: 'Student Absence Alert',
                        message: "{$student['name']} has {$totalAbsences} absences in {$subjectName}.",
                        options: [
                            'priority' => 'high',
                            'link' => "/teacher/attendance?student={$student['id']}",
                        ]
                    );
                }
            }
        }
    }
}
```

## AdminController - Schedule Conflicts

```php
// In AdminController::createClass()
use Helpers\Notification;

public function createClass(): void
{
    // ... existing class creation logic ...
    
    // Check for schedule conflicts
    $conflicts = checkScheduleConflicts($teacherId, $days, $startTime, $endTime);
    
    if (!empty($conflicts)) {
        Notification::error('Schedule conflict detected. Teacher already has classes during this time.');
        
        // Notify admin about the conflict
        Notification::create(
            recipientIds: $user['id'],
            type: 'error',
            category: 'schedule_change',
            title: 'Schedule Conflict Detected',
            message: "Cannot create class: {$teacherName} has a conflicting class at {$conflictSchedule}.",
            options: [
                'priority' => 'high',
                'link' => "/admin/create-class",
                'metadata' => [
                    'teacher_id' => $teacherId,
                    'conflict_details' => $conflicts,
                ],
            ]
        );
        
        return;
    }
    
    // If successful
    if ($classCreated) {
        Notification::success('Class created successfully');
        
        // Notify teacher
        Notification::create(
            recipientIds: $teacherUserId,
            type: 'schedule',
            category: 'class_created',
            title: 'New Class Assignment',
            message: "You have been assigned to teach {$subjectName} for {$sectionName}. Schedule: {$schedule}",
            options: [
                'link' => "/teacher/classes?class={$classId}",
                'metadata' => ['class_id' => $classId],
                'created_by' => $user['id'],
            ]
        );
        
        // Notify section members
        Notification::createForSection(
            sectionId: $sectionId,
            type: 'schedule',
            category: 'class_created',
            title: 'New Class Added',
            message: "New class: {$subjectName} with {$teacherName}. Schedule: {$schedule}, Room: {$room}",
            options: ['link' => '/student/schedule']
        );
    }
}
```

## Scheduled Tasks / Cron Jobs

### Assignment Due Reminders

Create a scheduled task (e.g., `app/Cron/AssignmentReminders.php`):

```php
<?php
namespace Cron;

use Services\NotificationManager;

class AssignmentReminders
{
    public function sendDueReminders(): void
    {
        $manager = new NotificationManager();
        $pdo = getDatabaseConnection();
        
        // Get assignments due in 2 days
        $stmt = $pdo->prepare('
            SELECT a.*, c.id as class_id, sub.name as subject_name
            FROM assignments a
            JOIN classes c ON a.class_id = c.id
            JOIN subjects sub ON a.subject_id = sub.id
            WHERE a.is_active = 1
            AND a.due_date = DATE_ADD(CURDATE(), INTERVAL 2 DAY)
        ');
        $stmt->execute();
        $assignments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($assignments as $assignment) {
            $manager->notifyClass(
                classId: $assignment['class_id'],
                type: 'warning',
                category: 'assignment_due',
                title: 'Assignment Due Soon',
                message: "{$assignment['title']} is due in 2 days ({$assignment['due_date']}).",
                options: [
                    'link' => "/student/assignments/{$assignment['id']}",
                    'priority' => 'high',
                ]
            );
        }
    }
}
```

### Cleanup Expired Notifications

```php
<?php
namespace Cron;

use Services\NotificationManager;

class NotificationCleanup
{
    public function cleanupExpired(): void
    {
        $manager = new NotificationManager();
        $deleted = $manager->cleanupExpired();
        
        error_log("Cleaned up {$deleted} expired notifications");
    }
}
```

## Displaying Flash Messages in Views

Add to your view templates (e.g., at the top of `resources/views/admin/dashboard.php`):

```php
<?php if (\Helpers\Notification::has()): ?>
    <div class="container-fluid mb-3">
        <?= \Helpers\Notification::renderHtml() ?>
    </div>
<?php endif; ?>
```

This will automatically display flash messages with proper styling and auto-dismiss functionality.

