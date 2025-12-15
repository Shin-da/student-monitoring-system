/**
 * Teacher Dropdown Refresh Utility
 * 
 * Provides a reusable mechanism to refresh teacher/adviser dropdowns
 * across all modules when a new teacher or adviser is created.
 * 
 * Usage:
 *   - Call refreshTeacherDropdown(selectElement) to refresh a specific dropdown
 *   - Or use autoRefreshTeacherDropdown(selectElement) to automatically refresh
 *     when a teacher is created (listens for 'teacherCreated' event)
 */

(function() {
    'use strict';

    /**
     * Refresh a teacher dropdown by fetching the latest list from the API
     * @param {HTMLSelectElement} selectElement - The select element to populate
     * @param {Object} options - Optional configuration
     * @param {string} options.apiUrl - Custom API URL (defaults to /api/admin/list-teachers.php)
     * @param {string} options.placeholder - Placeholder text (defaults to "Select Teacher")
     * @param {Function} options.onSuccess - Callback when refresh succeeds
     * @param {Function} options.onError - Callback when refresh fails
     * @returns {Promise<boolean>} - Returns true if successful, false otherwise
     */
    window.refreshTeacherDropdown = function(selectElement, options) {
        if (!selectElement || selectElement.tagName !== 'SELECT') {
            console.error('refreshTeacherDropdown: Invalid select element provided');
            return Promise.resolve(false);
        }

        options = options || {};
        const base = (window.__BASE_PATH__ || '').replace(/\/$/, '');
        const apiUrl = options.apiUrl || (base + '/api/admin/list-teachers.php');
        const placeholder = options.placeholder || 'Select Teacher';
        const onSuccess = options.onSuccess || function() {};
        const onError = options.onError || function(err) { console.error('Teacher dropdown refresh error:', err); };

        // Store the currently selected value to restore it if possible
        const currentValue = selectElement.value;
        const currentText = selectElement.options[selectElement.selectedIndex]?.textContent || '';

        // Show loading state
        const originalDisabled = selectElement.disabled;
        selectElement.disabled = true;
        if (selectElement.options.length > 0 && selectElement.options[0].value === '') {
            selectElement.options[0].textContent = 'Loading teachers...';
        } else {
            selectElement.innerHTML = '<option value="">Loading teachers...</option>';
        }

        return fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            },
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch teachers');
            }

            // Clear existing options
            selectElement.innerHTML = '';

            // Add placeholder option
            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = placeholder;
            selectElement.appendChild(placeholderOption);

            // Add teacher options
            if (data.teachers && Array.isArray(data.teachers)) {
                data.teachers.forEach(function(teacher) {
                    const option = document.createElement('option');
                    option.value = teacher.id || teacher.teacher_id;
                    const department = teacher.department || 'General Education';
                    option.textContent = teacher.name + ' (' + department + ')';
                    option.setAttribute('data-teacher-id', teacher.id || teacher.teacher_id);
                    option.setAttribute('data-teacher-name', teacher.name);
                    option.setAttribute('data-department', department);
                    selectElement.appendChild(option);
                });
            }

            // Try to restore the previously selected value
            if (currentValue && selectElement.querySelector('option[value="' + currentValue + '"]')) {
                selectElement.value = currentValue;
            } else if (currentText) {
                // Try to find by text match
                const matchingOption = Array.from(selectElement.options).find(function(opt) {
                    return opt.textContent === currentText;
                });
                if (matchingOption) {
                    selectElement.value = matchingOption.value;
                }
            }

            // Restore disabled state
            selectElement.disabled = originalDisabled;

            onSuccess(data);
            return true;
        })
        .catch(function(error) {
            // Restore original state on error
            selectElement.disabled = originalDisabled;
            if (selectElement.options.length === 0 || selectElement.options[0].textContent === 'Loading teachers...') {
                selectElement.innerHTML = '<option value="">Error loading teachers</option>';
            }
            onError(error);
            return false;
        });
    };

    /**
     * Automatically refresh a teacher dropdown when a new teacher is created
     * @param {HTMLSelectElement} selectElement - The select element to auto-refresh
     * @param {Object} options - Optional configuration (same as refreshTeacherDropdown)
     * @returns {Function} - Unsubscribe function to stop auto-refreshing
     */
    window.autoRefreshTeacherDropdown = function(selectElement, options) {
        if (!selectElement || selectElement.tagName !== 'SELECT') {
            console.error('autoRefreshTeacherDropdown: Invalid select element provided');
            return function() {}; // Return no-op unsubscribe function
        }

        const handler = function(event) {
            // Only refresh if the event is for a teacher or adviser
            if (event.detail && (event.detail.role === 'teacher' || event.detail.role === 'adviser')) {
                console.log('Teacher/Adviser created, refreshing dropdown...', event.detail);
                refreshTeacherDropdown(selectElement, options);
            }
        };

        document.addEventListener('teacherCreated', handler);

        // Return unsubscribe function
        return function() {
            document.removeEventListener('teacherCreated', handler);
        };
    };

    /**
     * Refresh all teacher dropdowns on the page
     * @param {Object} options - Optional configuration
     * @returns {Promise<Array>} - Array of promises for each dropdown refresh
     */
    window.refreshAllTeacherDropdowns = function(options) {
        const teacherSelects = document.querySelectorAll('select[id*="teacher"], select[name*="teacher"], select[data-teacher-dropdown="true"]');
        const promises = [];

        teacherSelects.forEach(function(select) {
            promises.push(refreshTeacherDropdown(select, options));
        });

        return Promise.all(promises);
    };

    // Auto-initialize: Find all teacher dropdowns with data-auto-refresh attribute and set them up
    document.addEventListener('DOMContentLoaded', function() {
        const autoRefreshSelects = document.querySelectorAll('select[data-auto-refresh-teachers="true"]');
        autoRefreshSelects.forEach(function(select) {
            autoRefreshTeacherDropdown(select);
        });
    });

})();

