/**
 * Toast Notification System using Bootstrap 5 Toasts
 * Lightweight, no dependencies (Bootstrap already loaded)
 */
class ToastNotifications {
    constructor() {
        this.container = null;
        this.init();
    }
    
    init() {
        this.createContainer();
    }
    
    createContainer() {
        // Check if container already exists
        this.container = document.getElementById('toast-container');
        if (this.container) return;
        
        // Create toast container
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.className = 'toast-container position-fixed top-0 end-0 p-3';
        this.container.style.zIndex = '1055';
        document.body.appendChild(this.container);
    }
    
    /**
     * Show a toast notification
     * @param {string} message - Message to display
     * @param {string} type - Type: 'success', 'error', 'warning', 'info'
     * @param {object} options - Additional options
     */
    show(message, type = 'info', options = {}) {
        const {
            title = null,
            duration = 5000,
            icon = null,
            position = 'top-end'
        } = options;
        
        // Generate unique ID
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        // Get icon and colors based on type
        const config = this.getTypeConfig(type);
        
        // Create toast element
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = 'toast';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.setAttribute('data-bs-autohide', duration > 0 ? 'true' : 'false');
        toast.setAttribute('data-bs-delay', duration);
        
        // Build toast content
        let toastHeader = '';
        if (title) {
            toastHeader = `
                <div class="toast-header ${config.headerClass}">
                    ${config.icon}
                    <strong class="me-auto">${this.escapeHtml(title)}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
        }
        
        toast.innerHTML = `
            ${toastHeader}
            <div class="toast-body ${config.bodyClass}">
                ${config.icon && !title ? config.icon : ''}
                ${this.escapeHtml(message)}
            </div>
        `;
        
        // Append to container
        this.container.appendChild(toast);
        
        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: duration > 0,
            delay: duration
        });
        
        // Show toast
        bsToast.show();
        
        // Remove element after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
        
        return toastId;
    }
    
    /**
     * Get configuration for notification type
     */
    getTypeConfig(type) {
        const configs = {
            success: {
                icon: '<svg class="bi flex-shrink-0 me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>',
                headerClass: 'bg-success text-white',
                bodyClass: 'text-success'
            },
            error: {
                icon: '<svg class="bi flex-shrink-0 me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/></svg>',
                headerClass: 'bg-danger text-white',
                bodyClass: 'text-danger'
            },
            warning: {
                icon: '<svg class="bi flex-shrink-0 me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>',
                headerClass: 'bg-warning text-dark',
                bodyClass: 'text-warning'
            },
            info: {
                icon: '<svg class="bi flex-shrink-0 me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>',
                headerClass: 'bg-info text-white',
                bodyClass: 'text-info'
            }
        };
        
        return configs[type] || configs.info;
    }
    
    /**
     * Convenience methods
     */
    success(message, options = {}) {
        return this.show(message, 'success', { title: 'Success', ...options });
    }
    
    error(message, options = {}) {
        return this.show(message, 'error', { title: 'Error', ...options });
    }
    
    warning(message, options = {}) {
        return this.show(message, 'warning', { title: 'Warning', ...options });
    }
    
    info(message, options = {}) {
        return this.show(message, 'info', { title: 'Information', ...options });
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize global toast system
window.toastNotifications = new ToastNotifications();

// Also create aliases for convenience
window.toast = window.toastNotifications;
window.showToast = (message, type, options) => window.toastNotifications.show(message, type, options);

