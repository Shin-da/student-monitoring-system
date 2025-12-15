/**
 * Logout Confirmation Handler
 * Adds confirmation dialog before logout
 */
(function() {
    'use strict';
    
    // Check if Bootstrap is available for modal
    const hasBootstrap = typeof bootstrap !== 'undefined';
    
    // Create confirmation dialog
    function createLogoutModal() {
        const modalId = 'logout-confirmation-modal';
        
        // Check if modal already exists
        if (document.getElementById(modalId)) {
            return modalId;
        }
        
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = modalId;
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-labelledby', 'logoutModalLabel');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="logoutModalLabel">
                            <svg width="20" height="20" fill="currentColor" class="me-2 text-warning">
                                <use href="#icon-alert"></use>
                            </svg>
                            Confirm Logout
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Are you sure you want to log out? You will need to log in again to access your account.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirm-logout-btn">Yes, Logout</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        return modalId;
    }
    
    // Simple confirmation using native browser confirm (fallback)
    function showLogoutConfirmation() {
        // Try SweetAlert2 first, fallback to native confirm
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                title: 'Confirm Logout',
                text: 'Are you sure you want to log out? You will need to log in again to access your account.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                reverseButtons: true
            }).then((result) => {
                return result.isConfirmed;
            });
        }
        return confirm('Are you sure you want to log out? You will need to log in again to access your account.');
    }
    
    // Initialize logout confirmation
    function initLogoutConfirmation() {
        // Handle sidebar logout buttons
        const logoutButtons = document.querySelectorAll('#logout-btn, button[type="submit"].sidebar-logout, .logout-form button');
        const logoutForms = document.querySelectorAll('.logout-form, form[action*="logout"]');
        
        logoutButtons.forEach(button => {
            // Make sure it's not already handled
            if (button.dataset.logoutInitialized) return;
            button.dataset.logoutInitialized = 'true';
            
            // Change type to button to prevent immediate submission
            if (button.type === 'submit') {
                button.type = 'button';
            }
            
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const form = button.closest('form');
                if (!form) return;
                
                // Use SweetAlert2 if available (best UX), then Bootstrap modal, then native confirm
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Confirm Logout',
                        text: 'Are you sure you want to log out? You will need to log in again to access your account.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Logout',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#dc3545',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                } else if (hasBootstrap) {
                    const modalId = createLogoutModal();
                    const modalElement = document.getElementById(modalId);
                    const modal = new bootstrap.Modal(modalElement);
                    
                    // Handle confirm button
                    const confirmBtn = document.getElementById('confirm-logout-btn');
                    confirmBtn.onclick = function() {
                        modal.hide();
                        form.submit();
                    };
                    
                    // Show modal
                    modal.show();
                } else {
                    // Fallback to native confirm
                    if (confirm('Are you sure you want to log out? You will need to log in again to access your account.')) {
                        form.submit();
                    }
                }
            });
        });
        
        // Also handle forms directly (for other logout links)
        logoutForms.forEach(form => {
            if (form.dataset.logoutInitialized) return;
            form.dataset.logoutInitialized = 'true';
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Use Bootstrap modal if available, otherwise use native confirm
                if (hasBootstrap) {
                    const modalId = createLogoutModal();
                    const modalElement = document.getElementById(modalId);
                    const modal = new bootstrap.Modal(modalElement);
                    
                    // Handle confirm button
                    const confirmBtn = document.getElementById('confirm-logout-btn');
                    confirmBtn.onclick = function() {
                        modal.hide();
                        // Remove preventDefault and submit
                        form.removeEventListener('submit', arguments.callee);
                        form.submit();
                    };
                    
                    // Show modal
                    modal.show();
                } else {
                    // Fallback to native confirm
                    if (showLogoutConfirmation()) {
                        form.removeEventListener('submit', arguments.callee);
                        form.submit();
                    }
                }
            });
        });
        
        // Handle dropdown logout buttons (in app.php layout)
        const dropdownLogoutButtons = document.querySelectorAll('form[action*="logout"] button[type="submit"]');
        dropdownLogoutButtons.forEach(button => {
            if (button.dataset.logoutInitialized) return;
            button.dataset.logoutInitialized = 'true';
            
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const form = button.closest('form');
                if (!form) return;
                
                // Use SweetAlert2 if available (best UX), then Bootstrap modal, then native confirm
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Confirm Logout',
                        text: 'Are you sure you want to log out? You will need to log in again to access your account.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Logout',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#dc3545',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                } else if (hasBootstrap) {
                    const modalId = createLogoutModal();
                    const modalElement = document.getElementById(modalId);
                    const modal = new bootstrap.Modal(modalElement);
                    
                    // Handle confirm button
                    const confirmBtn = document.getElementById('confirm-logout-btn');
                    confirmBtn.onclick = function() {
                        modal.hide();
                        form.submit();
                    };
                    
                    // Show modal
                    modal.show();
                } else {
                    // Fallback to native confirm
                    if (confirm('Are you sure you want to log out? You will need to log in again to access your account.')) {
                        form.submit();
                    }
                }
            });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLogoutConfirmation);
    } else {
        initLogoutConfirmation();
    }
})();

