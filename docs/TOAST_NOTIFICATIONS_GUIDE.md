# Toast Notifications & SweetAlert2 Guide

## Overview

We use a **hybrid approach**:
- **Bootstrap 5 Toasts** for simple notifications (login success, info messages, etc.)
- **SweetAlert2** for confirmations and complex dialogs (logout, delete confirmations, etc.)

## Why This Approach?

✅ **Bootstrap Toasts**: 
- Already loaded (no extra dependency)
- Lightweight (~2KB)
- Native Bootstrap styling
- Perfect for simple notifications

✅ **SweetAlert2**:
- Beautiful, modern UI
- Better UX than native `confirm()`
- Rich features (inputs, timers, etc.)
- Only loads when needed (~50KB, but worth it for confirmations)

## Usage

### Bootstrap Toasts (Simple Notifications)

```javascript
// Success toast
toast.success('Login successful!');
// or
toastNotifications.success('Welcome back!');

// Error toast
toast.error('Failed to save data');

// Warning toast
toast.warning('Please check your input');

// Info toast
toast.info('New features available');

// With custom options
toast.success('Saved!', {
    title: 'Success',
    duration: 3000,
    icon: '<custom-icon>'
});
```

### SweetAlert2 (Confirmations)

```javascript
// Simple confirmation
confirmAction({
    title: 'Delete Student?',
    text: 'This action cannot be undone.',
    icon: 'warning'
}).then((result) => {
    if (result.isConfirmed) {
        // User clicked "Yes"
        deleteStudent();
    }
});

// Success message
showSuccess('Student deleted successfully!');

// Error message
showError('Failed to delete student', 'Please try again.');

// Info message
showInfo('Information', 'Your changes have been saved.');

// Warning message
showWarning('Warning', 'This action may have consequences.');
```

### PHP Flash Messages (Automatic)

Flash messages from PHP are automatically converted to toasts:

```php
use Helpers\Notification;

// In your controller
Notification::success('Student created successfully!');
// This will automatically show as a Bootstrap toast on the next page load
```

## Examples

### Delete Confirmation

```javascript
// In your JavaScript
function deleteStudent(studentId) {
    confirmAction({
        title: 'Delete Student?',
        text: 'This will permanently delete the student record. This action cannot be undone.',
        icon: 'warning',
        confirmButtonText: 'Yes, delete it',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            // Make API call
            fetch(`/api/students/${studentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': getCsrfToken()
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Student deleted successfully!');
                    // Refresh list
                    loadStudents();
                } else {
                    showError('Failed to delete student', data.message);
                }
            });
        }
    });
}
```

### Form Submission Success

```javascript
// After successful form submission
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const response = await fetch('/api/students', {
        method: 'POST',
        body: new FormData(form)
    });
    
    const data = await response.json();
    
    if (data.success) {
        toast.success('Student created successfully!');
        form.reset();
    } else {
        toast.error(data.message || 'Failed to create student');
    }
});
```

### Logout Confirmation

Already implemented! The logout button automatically uses SweetAlert2 for confirmation.

## Customization

### Toast Position

Toasts appear in the top-right corner by default. To change:

```javascript
// In toast-notifications.js, modify the container position
this.container.className = 'toast-container position-fixed top-0 start-0 p-3'; // Top-left
// or
this.container.className = 'toast-container position-fixed bottom-0 end-0 p-3'; // Bottom-right
```

### Toast Duration

```javascript
// Show for 3 seconds
toast.success('Message', { duration: 3000 });

// Show until manually closed
toast.info('Important message', { duration: 0 });
```

### SweetAlert2 Themes

SweetAlert2 automatically matches your Bootstrap theme. For custom styling:

```javascript
Swal.fire({
    title: 'Custom Styled',
    customClass: {
        popup: 'my-custom-popup',
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-secondary'
    }
});
```

## Best Practices

1. **Use Toasts for**: Success messages, info updates, warnings that don't require action
2. **Use SweetAlert2 for**: Confirmations, critical errors, complex dialogs
3. **Keep messages short**: Toast messages should be concise
4. **Don't overuse**: Too many notifications can be annoying
5. **Provide context**: Include enough information for the user to understand

## Migration from Custom System

If you have existing code using the custom notification system:

```javascript
// Old way
notificationSystem.success('Message');

// New way (same API, but uses Bootstrap)
toast.success('Message');
```

The API is similar, so migration is easy!

