/**
 * Notification Center Component
 * Handles persistent notifications, polling, and UI updates
 */
class NotificationCenter {
    constructor(options = {}) {
        // Get base path from window (set by layout) or infer from current URL if missing
        let basePath = '';
        if (typeof window !== 'undefined' && window.__BASE_PATH__) {
            basePath = String(window.__BASE_PATH__).replace(/\/$/, '');
        } else if (typeof window !== 'undefined') {
            // Fallback: infer base path from current location (handles /student-monitoring/public/*)
            try {
                const path = window.location.pathname;
                // Match patterns like /student-monitoring/admin/sections or /admin/sections
                const match = path.match(/^(\/[^\/]+(?:\/[^\/]+)?)/);
                if (match && match[1]) {
                    basePath = match[1];
                    // If it includes /admin, /teacher, etc., extract the base
                    if (basePath.includes('/admin') || basePath.includes('/teacher') || basePath.includes('/student') || basePath.includes('/parent')) {
                        basePath = basePath.split('/').slice(0, -1).join('/');
                    }
                }
                // If no match or empty, try another pattern
                if (!basePath) {
                    const altMatch = path.match(/^(.*?)(?:\/public)?\/(?:index\.php)?/);
                    basePath = altMatch && altMatch[1] ? altMatch[1].replace(/\/$/, '') : '';
                }
            } catch (e) {
                basePath = '';
            }
        }
        this.apiBase = options.apiBase || (basePath ? `${basePath}/api/notifications` : '/api/notifications');
        this.basePath = basePath || '';
        this.pollInterval = options.pollInterval || 30000; // 30 seconds
        this.pollTimer = null;
        this.container = null;
        this.badge = null;
        this.isOpen = false;
        this.notifications = [];
        this.unreadCount = 0;
        this.lastPollTime = null;
        
        this.init();
    }
    
    init() {
        this.createContainer();
        // Don't load notifications on init - wait until dropdown is opened
        // this.loadNotifications();
        this.startPolling();
        this.setupEventListeners();
        
        // Load unread count for badge only
        this.loadUnreadCount();
    }
    
    async loadUnreadCount() {
        try {
            // Ensure we have the correct base path
            let apiUrl;
            
            // First, try using the configured apiBase
            if (this.apiBase && this.apiBase.endsWith('/api/notifications')) {
                apiUrl = `${this.apiBase}/unread-count`;
            } else if (this.basePath) {
                // Ensure basePath doesn't have trailing slash
                const base = this.basePath.replace(/\/$/, '');
                apiUrl = `${base}/api/notifications/unread-count`;
            } else {
                // Fallback: use window.__BASE_PATH__ if available
                const basePath = (typeof window !== 'undefined' && window.__BASE_PATH__) 
                    ? String(window.__BASE_PATH__).replace(/\/$/, '')
                    : '';
                apiUrl = basePath ? `${basePath}/api/notifications/unread-count` : '/api/notifications/unread-count';
            }
            
            const response = await fetch(apiUrl);
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.unreadCount = data.count || 0;
                    this.updateBadge();
                    return;
                }
            }
            
            // If 404, try alternative paths
            if (response.status === 404) {
                // Try with /student-monitoring prefix if not already included
                if (!apiUrl.includes('/student-monitoring')) {
                    const altUrl = `/student-monitoring${apiUrl.startsWith('/') ? '' : '/'}${apiUrl}`;
                    const altResponse = await fetch(altUrl);
                    if (altResponse.ok) {
                        const altData = await altResponse.json();
                        if (altData.success) {
                            this.unreadCount = altData.count || 0;
                            this.updateBadge();
                            // Update apiBase for future calls
                            this.apiBase = altUrl.replace('/unread-count', '');
                            return;
                        }
                    }
                }
                
                // Try absolute path from current location
                const currentBase = window.location.origin + (this.basePath || '');
                const absUrl = `${currentBase}/api/notifications/unread-count`;
                const absResponse = await fetch(absUrl);
                if (absResponse.ok) {
                    const absData = await absResponse.json();
                    if (absData.success) {
                        this.unreadCount = absData.count || 0;
                        this.updateBadge();
                        return;
                    }
                }
                
                console.warn('Notification API not found. Tried:', apiUrl);
            }
        } catch (error) {
            console.error('Failed to load unread count:', error);
        }
    }
    
    createContainer() {
        // Find existing notification bell (should be in navbar by default now)
        const existingBell = document.getElementById('notification-bell');
        if (!existingBell) {
            // Fallback: Look for navbar or header area
            const navbar = document.querySelector('.dashboard-navbar, .navbar');
            const navbarRight = navbar?.querySelector('.d-flex.align-items-center, .navbar-nav');
            if (navbarRight) {
                const bell = document.createElement('div');
                bell.id = 'notification-bell';
                bell.className = 'notification-bell-container';
                const basePath = this.basePath || '';
                bell.innerHTML = `
                    <button class="btn btn-outline-secondary notification-bell-btn" id="notification-bell-btn" type="button" aria-label="Notifications" title="Notifications">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                        </svg>
                        <span class="notification-badge" id="notification-badge">0</span>
                    </button>
                    <div class="notification-dropdown" id="notification-dropdown">
                        <div class="notification-dropdown-header">
                            <h6>Notifications</h6>
                            <button class="btn-link btn-sm" id="mark-all-read-btn">Mark all as read</button>
                        </div>
                        <div class="notification-dropdown-body" id="notification-list">
                            <div class="notification-loading">Loading...</div>
                        </div>
                        <div class="notification-dropdown-footer">
                            <a href="${basePath}/notifications" class="btn-link btn-sm" id="view-all-notifications">View all notifications</a>
                        </div>
                    </div>
                `;
                // Insert before theme toggle if it exists, otherwise at the end
                const themeToggle = navbarRight.querySelector('.theme-toggle');
                if (themeToggle) {
                    navbarRight.insertBefore(bell, themeToggle);
                } else {
                    navbarRight.appendChild(bell);
                }
            }
        }
        
        this.container = document.getElementById('notification-dropdown');
        this.badge = document.getElementById('notification-badge');
        
        this.addStyles();
    }
    
    addStyles() {
        if (document.querySelector('#notification-center-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'notification-center-styles';
        style.textContent = `
            .notification-bell-container {
                position: relative;
                display: inline-block;
            }
            
            .notification-bell-container {
                position: relative;
                display: inline-block;
            }
            
            .notification-bell-btn {
                position: relative;
                padding: 6px 10px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(255, 255, 255, 0.2);
                transition: all 0.2s ease;
            }
            
            .notification-bell-btn:hover {
                background: rgba(255, 255, 255, 0.1);
                border-color: rgba(255, 255, 255, 0.3);
            }
            
            .notification-bell-btn svg {
                width: 18px;
                height: 18px;
            }
            
            .notification-badge {
                position: absolute;
                top: -2px;
                right: -2px;
                background: #dc3545;
                color: white;
                border-radius: 10px;
                padding: 2px 6px;
                font-size: 10px;
                font-weight: bold;
                min-width: 18px;
                height: 18px;
                text-align: center;
                display: none;
                line-height: 14px;
                border: 2px solid var(--navbar-bg, #1a1a2e);
            }
            
            .notification-badge.show {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .notification-dropdown {
                position: absolute;
                top: calc(100% + 8px);
                right: 0;
                width: 380px;
                max-width: calc(100vw - 20px);
                max-height: 500px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                display: none;
                flex-direction: column;
                z-index: 1050;
            }
            
            @media (prefers-color-scheme: dark) {
                .notification-dropdown {
                    background: #1a1a2e;
                    border-color: #333;
                    color: #fff;
                }
            }
            
            [data-theme="dark"] .notification-dropdown {
                background: #1a1a2e;
                border-color: #333;
                color: #fff;
            }
            
            .notification-dropdown.show {
                display: flex;
            }
            
            .notification-dropdown-header {
                padding: 12px 16px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .notification-dropdown-header h6 {
                margin: 0;
                font-weight: 600;
            }
            
            .notification-dropdown-body {
                flex: 1;
                overflow-y: auto;
                max-height: 400px;
            }
            
            .notification-dropdown-footer {
                padding: 8px 16px;
                border-top: 1px solid #eee;
                text-align: center;
            }
            
            .notification-item {
                padding: 12px 16px;
                border-bottom: 1px solid #f0f0f0;
                cursor: pointer;
                transition: background 0.2s ease;
                display: flex;
                gap: 12px;
                align-items: flex-start;
            }
            
            .notification-item:hover {
                background: #f8f9fa;
            }
            
            .notification-item.unread {
                background: #f0f7ff;
            }
            
            .notification-item.unread:hover {
                background: #e6f2ff;
            }
            
            .notification-icon {
                flex-shrink: 0;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
            }
            
            .notification-icon.info { background: #e3f2fd; color: #2196f3; }
            .notification-icon.success { background: #e8f5e9; color: #4caf50; }
            .notification-icon.warning { background: #fff3e0; color: #ff9800; }
            .notification-icon.error { background: #ffebee; color: #f44336; }
            .notification-icon.grade { background: #f3e5f5; color: #9c27b0; }
            .notification-icon.attendance { background: #e1f5fe; color: #00bcd4; }
            .notification-icon.assignment { background: #fff9c4; color: #fbc02d; }
            
            .notification-content {
                flex: 1;
                min-width: 0;
            }
            
            .notification-title {
                font-weight: 600;
                font-size: 14px;
                margin-bottom: 4px;
                color: #333;
            }
            
            .notification-message {
                font-size: 13px;
                color: #666;
                line-height: 1.4;
                margin-bottom: 4px;
            }
            
            .notification-time {
                font-size: 11px;
                color: #999;
            }
            
            .notification-actions {
                display: flex;
                gap: 4px;
            }
            
            .notification-delete {
                background: none;
                border: none;
                color: #999;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                font-size: 12px;
            }
            
            .notification-delete:hover {
                background: #f0f0f0;
                color: #dc3545;
            }
            
            .notification-loading,
            .notification-empty {
                padding: 40px 20px;
                text-align: center;
                color: #999;
            }
            
            @media (max-width: 768px) {
                .notification-dropdown {
                    width: 320px;
                    right: -10px;
                }
            }
            
            [data-theme="dark"] .notification-dropdown {
                background: #1e1e1e;
                border-color: #333;
            }
            
            [data-theme="dark"] .notification-item {
                border-color: #333;
            }
            
            [data-theme="dark"] .notification-item:hover {
                background: #2a2a2a;
            }
            
            [data-theme="dark"] .notification-item.unread {
                background: #1a2332;
            }
            
            [data-theme="dark"] .notification-title {
                color: #e0e0e0;
            }
            
            [data-theme="dark"] .notification-message {
                color: #b0b0b0;
            }
        `;
        document.head.appendChild(style);
    }
    
    setupEventListeners() {
        const bellBtn = document.getElementById('notification-bell-btn');
        const markAllReadBtn = document.getElementById('mark-all-read-btn');
        const viewAllLink = document.getElementById('view-all-notifications');
        
        if (bellBtn) {
            bellBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });
        }
        
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.markAllAsRead();
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.container?.contains(e.target) && !bellBtn?.contains(e.target)) {
                this.closeDropdown();
            }
        });
        
        // Handle visibility change (poll when tab becomes visible)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.loadNotifications();
            }
        });
    }
    
    async loadNotifications() {
        const listContainer = document.getElementById('notification-list');
        
        if (!listContainer) {
            console.warn('Notification list container not found');
            return;
        }
        
        // Show loading state only if dropdown is visible
        if (this.isOpen && listContainer.innerHTML.includes('Loading')) {
            // Already showing loading, don't change
        } else if (!this.isOpen) {
            // Don't show loading if dropdown is closed - will load when opened
            return;
        }
        
        try {
            const response = await fetch(`${this.apiBase}?limit=20`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications || [];
                this.unreadCount = data.unread_count || 0;
                this.lastPollTime = Date.now();
                
                this.renderNotifications();
                this.updateBadge();
            } else {
                // Handle API error response
                console.error('API error:', data.error || data.message || 'Unknown error');
                listContainer.innerHTML = '<div class="notification-empty">Unable to load notifications</div>';
            }
        } catch (error) {
            console.error('Failed to load notifications:', error);
            listContainer.innerHTML = '<div class="notification-empty">Unable to load notifications. Please refresh the page.</div>';
        }
    }
    
    renderNotifications() {
        const listContainer = document.getElementById('notification-list');
        if (!listContainer) return;
        
        if (this.notifications.length === 0) {
            listContainer.innerHTML = '<div class="notification-empty">No new notifications</div>';
            return;
        }
        
        listContainer.innerHTML = this.notifications.map(notif => {
            const timeAgo = this.formatTimeAgo(notif.created_at);
            const iconClass = notif.type || 'info';
            const iconHtml = notif.icon ? `<i class="${notif.icon}"></i>` : '<i class="fas fa-bell"></i>';
            const unreadClass = notif.is_read === 0 ? 'unread' : '';
            const linkAttr = notif.link ? `onclick="window.location.href='${notif.link}'"` : '';
            
            return `
                <div class="notification-item ${unreadClass}" data-id="${notif.id}" ${linkAttr}>
                    <div class="notification-icon ${iconClass}">${iconHtml}</div>
                    <div class="notification-content">
                        <div class="notification-title">${this.escapeHtml(notif.title)}</div>
                        <div class="notification-message">${this.escapeHtml(notif.message)}</div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                    <div class="notification-actions">
                        <button class="notification-delete" onclick="notificationCenter.deleteNotification(${notif.id}); event.stopPropagation();" title="Delete">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        
        // Add click handlers for marking as read
        listContainer.querySelectorAll('.notification-item.unread').forEach(item => {
            item.addEventListener('click', () => {
                const id = parseInt(item.dataset.id);
                this.markAsRead(id);
            });
        });
    }
    
    updateBadge() {
        if (!this.badge) return;
        
        if (this.unreadCount > 0) {
            this.badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount.toString();
            this.badge.classList.add('show');
        } else {
            this.badge.classList.remove('show');
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`${this.apiBase}/mark-read`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            });
            
            const data = await response.json();
            if (data.success) {
                // Update local state
                const notif = this.notifications.find(n => n.id === notificationId);
                if (notif) {
                    notif.is_read = 1;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
                
                this.renderNotifications();
                this.updateBadge();
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch(`${this.apiBase}/mark-read`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mark_all: true })
            });
            
            const data = await response.json();
            if (data.success) {
                // Update local state
                this.notifications.forEach(n => n.is_read = 1);
                this.unreadCount = 0;
                
                this.renderNotifications();
                this.updateBadge();
            }
        } catch (error) {
            console.error('Failed to mark all as read:', error);
        }
    }
    
    async deleteNotification(notificationId) {
        try {
            const response = await fetch(`${this.apiBase}/delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            });
            
            const data = await response.json();
            if (data.success) {
                // Remove from local state
                this.notifications = this.notifications.filter(n => n.id !== notificationId);
                const wasUnread = this.notifications.find(n => n.id === notificationId)?.is_read === 0;
                if (wasUnread) {
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
                
                this.renderNotifications();
                this.updateBadge();
            }
        } catch (error) {
            console.error('Failed to delete notification:', error);
        }
    }
    
    toggleDropdown() {
        if (this.isOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }
    
    openDropdown() {
        if (this.container) {
            this.container.classList.add('show');
            this.isOpen = true;
            // Show loading state immediately
            const listContainer = document.getElementById('notification-list');
            if (listContainer) {
                listContainer.innerHTML = '<div class="notification-loading">Loading...</div>';
            }
            this.loadNotifications(); // Refresh when opening
        }
    }
    
    closeDropdown() {
        if (this.container) {
            this.container.classList.remove('show');
            this.isOpen = false;
        }
    }
    
    startPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
        }
        
        this.pollTimer = setInterval(() => {
            // Only poll if tab is visible
            if (!document.hidden) {
                // Only update unread count, not full notifications (unless dropdown is open)
                if (this.isOpen) {
                    this.loadNotifications();
                } else {
                    this.loadUnreadCount();
                }
            }
        }, this.pollInterval);
    }
    
    stopPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    }
    
    formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = Math.floor((now - time) / 1000);
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
        
        return time.toLocaleDateString();
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize notification center when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.notificationCenter = new NotificationCenter();
    });
} else {
    window.notificationCenter = new NotificationCenter();
}

