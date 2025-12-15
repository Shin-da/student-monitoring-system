/**
 * SweetAlert2 Integration
 * For beautiful confirmations and complex dialogs
 * 
 * Usage:
 * - Simple confirm: Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true })
 * - Success: Swal.fire('Success!', 'Operation completed', 'success')
 * - Error: Swal.fire('Error!', 'Something went wrong', 'error')
 */

// Check if SweetAlert2 is loaded, if not, load it
(function() {
    'use strict';
    
    if (typeof Swal === 'undefined') {
        // Load SweetAlert2 from CDN
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css';
        document.head.appendChild(link);
        
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js';
        script.onload = function() {
            // Configure default settings
            Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary',
                    denyButton: 'btn btn-danger'
                },
                buttonsStyling: false,
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                denyButtonText: 'No'
            });
        };
        document.body.appendChild(script);
    }
    
    // Helper functions for common use cases
    window.confirmAction = function(options) {
        const defaults = {
            title: 'Are you sure?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        };
        
        return Swal.fire({ ...defaults, ...options });
    };
    
    window.showSuccess = function(title, text = '') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'success',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        });
    };
    
    window.showError = function(title, text = '') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'error',
            confirmButtonText: 'OK'
        });
    };
    
    window.showInfo = function(title, text = '') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'info',
            confirmButtonText: 'OK'
        });
    };
    
    window.showWarning = function(title, text = '') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            confirmButtonText: 'OK'
        });
    };
})();

