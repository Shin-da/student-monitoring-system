<div class="notifications-page">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title">Notifications</h1>
            <p class="text-muted mb-0">
                <?php if ($unreadCount > 0): ?>
                    You have <strong><?= $unreadCount ?></strong> unread notification<?= $unreadCount !== 1 ? 's' : '' ?>
                <?php else: ?>
                    All caught up! No unread notifications.
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($unreadCount > 0): ?>
                <form method="POST" action="<?= \Helpers\Url::to('/api/notifications/mark-read') ?>" id="mark-all-read-form" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="mark_all" value="1">
                    <button type="submit" class="btn btn-outline-primary">
                        <svg width="16" height="16" fill="currentColor" class="me-1">
                            <use href="#icon-check"></use>
                        </svg>
                        Mark all as read
                    </button>
                </form>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn btn-outline-secondary">
                <svg width="16" height="16" fill="currentColor" class="me-1">
                    <use href="#icon-dashboard"></use>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="notification-filters card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Filter by Status</label>
                    <select class="form-select" id="status-filter" onchange="window.location.href='<?= \Helpers\Url::to('/notifications') ?>?filter=' + this.value">
                        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Notifications</option>
                        <option value="unread" <?= $filter === 'unread' ? 'selected' : '' ?>>Unread Only</option>
                        <option value="read" <?= $filter === 'read' ? 'selected' : '' ?>>Read Only</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Type</label>
                    <select class="form-select" id="type-filter" onchange="filterByType(this.value)">
                        <option value="">All Types</option>
                        <option value="info" <?= $type === 'info' ? 'selected' : '' ?>>Info</option>
                        <option value="success" <?= $type === 'success' ? 'selected' : '' ?>>Success</option>
                        <option value="warning" <?= $type === 'warning' ? 'selected' : '' ?>>Warning</option>
                        <option value="error" <?= $type === 'error' ? 'selected' : '' ?>>Error</option>
                        <option value="grade" <?= $type === 'grade' ? 'selected' : '' ?>>Grades</option>
                        <option value="attendance" <?= $type === 'attendance' ? 'selected' : '' ?>>Attendance</option>
                        <option value="schedule" <?= $type === 'schedule' ? 'selected' : '' ?>>Schedule</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary w-100" onclick="refreshNotifications()">
                        <svg width="16" height="16" fill="currentColor" class="me-1">
                            <use href="#icon-refresh"></use>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="notifications-list">
        <?php if (empty($notifications)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <svg width="64" height="64" fill="currentColor" class="text-muted mb-3">
                        <use href="#icon-alerts"></use>
                    </svg>
                    <h5 class="text-muted">No notifications found</h5>
                    <p class="text-muted">
                        <?php if ($filter === 'unread'): ?>
                            You're all caught up! No unread notifications.
                        <?php elseif ($filter === 'read'): ?>
                            You don't have any read notifications.
                        <?php else: ?>
                            You don't have any notifications yet.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $isUnread = ($notification['is_read'] ?? 0) === 0;
                    $typeClass = $notification['type'] ?? 'info';
                    $priorityClass = match($notification['priority'] ?? 'normal') {
                        'urgent' => 'border-danger',
                        'high' => 'border-warning',
                        'low' => 'border-secondary',
                        default => 'border-primary'
                    };
                    $timeAgo = \Helpers\Notification::formatTimeAgo($notification['created_at'] ?? '');
                    ?>
                    <div class="list-group-item notification-item <?= $isUnread ? 'unread' : '' ?> border-start <?= $priorityClass ?> border-start-4" data-id="<?= $notification['id'] ?>">
                        <div class="d-flex align-items-start gap-3">
                            <div class="notification-icon notification-icon-<?= $typeClass ?> flex-shrink-0">
                                <?php
                                $iconMap = [
                                    'info' => 'icon-info',
                                    'success' => 'icon-check',
                                    'warning' => 'icon-alert',
                                    'error' => 'icon-alert',
                                    'grade' => 'icon-chart',
                                    'attendance' => 'icon-calendar',
                                    'schedule' => 'icon-clock',
                                ];
                                $iconId = $iconMap[$typeClass] ?? 'icon-alerts';
                                ?>
                                <svg width="24" height="24" fill="currentColor">
                                    <use href="#<?= $iconId ?>"></use>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="mb-0 <?= $isUnread ? 'fw-bold' : '' ?>">
                                        <?= htmlspecialchars($notification['title'] ?? 'Notification') ?>
                                    </h6>
                                    <div class="d-flex gap-2">
                                        <?php if ($isUnread): ?>
                                            <button class="btn btn-sm btn-link text-primary mark-read-btn" data-id="<?= $notification['id'] ?>" title="Mark as read">
                                                <svg width="16" height="16" fill="currentColor">
                                                    <use href="#icon-check"></use>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-link text-danger delete-notification-btn" data-id="<?= $notification['id'] ?>" title="Delete">
                                            <svg width="16" height="16" fill="currentColor">
                                                <use href="#icon-x"></use>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <p class="mb-2 text-muted"><?= htmlspecialchars($notification['message'] ?? '') ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <svg width="14" height="14" fill="currentColor" class="me-1">
                                            <use href="#icon-clock"></use>
                                        </svg>
                                        <?= htmlspecialchars($timeAgo) ?>
                                    </small>
                                    <?php if (!empty($notification['link'])): ?>
                                        <a href="<?= htmlspecialchars($notification['link']) ?>" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark all as read form
    const markAllForm = document.getElementById('mark-all-read-form');
    if (markAllForm) {
        markAllForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            try {
                const response = await fetch('<?= \Helpers\Url::to('/api/notifications/mark-read') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ mark_all: true })
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Failed to mark all as read:', error);
                alert('Failed to mark all as read. Please try again.');
            }
        });
    }
    
    // Mark single notification as read
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            try {
                const response = await fetch('<?= \Helpers\Url::to('/api/notifications/mark-read') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: parseInt(id) })
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Failed to mark as read:', error);
            }
        });
    });
    
    // Delete notification
    document.querySelectorAll('.delete-notification-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (!confirm('Are you sure you want to delete this notification?')) return;
            
            const id = this.dataset.id;
            try {
                const response = await fetch('<?= \Helpers\Url::to('/api/notifications/delete') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: parseInt(id) })
                });
                const data = await response.json();
                if (data.success) {
                    this.closest('.notification-item').remove();
                }
            } catch (error) {
                console.error('Failed to delete notification:', error);
                alert('Failed to delete notification. Please try again.');
            }
        });
    });
});

function filterByType(type) {
    const url = new URL(window.location.href);
    if (type) {
        url.searchParams.set('type', type);
    } else {
        url.searchParams.delete('type');
    }
    window.location.href = url.toString();
}

function refreshNotifications() {
    window.location.reload();
}
</script>

<style>
.notification-item.unread {
    background-color: rgba(13, 110, 253, 0.05);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-icon-info { background: #e3f2fd; color: #2196f3; }
.notification-icon-success { background: #e8f5e9; color: #4caf50; }
.notification-icon-warning { background: #fff3e0; color: #ff9800; }
.notification-icon-error { background: #ffebee; color: #f44336; }
.notification-icon-grade { background: #f3e5f5; color: #9c27b0; }
.notification-icon-attendance { background: #e1f5fe; color: #00bcd4; }
.notification-icon-schedule { background: #e0f2f1; color: #009688; }
</style>

