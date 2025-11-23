// Enhanced Create Parent Form with Advanced UX
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createParentForm');
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn ? submitBtn.querySelector('.btn-text') : null;
    const btnLoading = submitBtn ? submitBtn.querySelector('.btn-loading') : null;

    // Password visibility toggle
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('svg use');
            icon.setAttribute('href', type === 'password' ? '#icon-eye' : '#icon-eye-off');
        });
    }

    // Real-time password validation
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            validatePassword(this.value);
        });
    }

    // Enhanced searchable student selection
    const studentSearchInput = document.getElementById('student_search');
    const studentIdInput = document.getElementById('student_id');
    const searchResults = document.getElementById('student_search_results');
    const searchResultsList = document.getElementById('search_results_list');
    const searchResultsHeader = document.getElementById('results_count');
    const searchLoading = document.getElementById('search_loading');
    const searchNoResults = document.getElementById('search_no_results');
    const clearStudentBtn = document.getElementById('clearStudentSelection');
    
    let selectedStudent = null;
    let searchTimeout = null;
    
    if (studentSearchInput && typeof studentsData !== 'undefined') {
        // Search functionality
        studentSearchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Hide results if query is too short
            if (query.length < 2) {
                searchResults.style.display = 'none';
                searchResultsHeader.textContent = 'Start typing to search students...';
                return;
            }
            
            // Show loading state
            searchLoading.style.display = 'block';
            searchNoResults.style.display = 'none';
            searchResults.style.display = 'block';
            searchResultsList.innerHTML = '';
            
            // Debounce search
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!studentSearchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
        
        // Clear student selection
        if (clearStudentBtn) {
            clearStudentBtn.addEventListener('click', function() {
                clearStudentSelection();
            });
        }
        
        function performSearch(query) {
            const results = studentsData.filter(student => {
                const name = (student.name || '').toLowerCase();
                const lrn = (student.lrn || '').toLowerCase();
                const grade = String(student.grade_level || '').toLowerCase();
                const section = (student.section || '').toLowerCase();
                
                return name.includes(query) || 
                       lrn.includes(query) || 
                       grade.includes(query) || 
                       section.includes(query);
            });
            
            // Hide loading
            searchLoading.style.display = 'none';
            
            if (results.length === 0) {
                searchNoResults.style.display = 'block';
                searchResultsHeader.textContent = 'No results found';
                return;
            }
            
            // Show results count
            searchResultsHeader.textContent = `Found ${results.length} student${results.length !== 1 ? 's' : ''}`;
            
            // Limit to 50 results for performance
            const displayResults = results.slice(0, 50);
            if (results.length > 50) {
                searchResultsHeader.textContent += ` (showing first 50)`;
            }
            
            // Render results
            displayResults.forEach(student => {
                const item = document.createElement('div');
                item.className = 'list-group-item list-group-item-action cursor-pointer';
                item.style.cursor = 'pointer';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${escapeHtml(student.name || 'Unknown')}</div>
                            <div class="small text-muted">
                                LRN: ${escapeHtml(student.lrn || 'N/A')} | 
                                Grade ${student.grade_level || 'N/A'} | 
                                ${escapeHtml(student.section || 'No Section')}
                            </div>
                        </div>
                        <svg width="16" height="16" fill="currentColor" class="text-primary" viewBox="0 0 24 24">
                            <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                        </svg>
                    </div>
                `;
                
                item.addEventListener('click', function() {
                    selectStudent(student);
                });
                
                item.addEventListener('mouseenter', function() {
                    this.classList.add('active');
                });
                
                item.addEventListener('mouseleave', function() {
                    this.classList.remove('active');
                });
                
                searchResultsList.appendChild(item);
            });
        }
        
        function selectStudent(student) {
            selectedStudent = student;
            studentIdInput.value = student.id;
            studentSearchInput.value = student.name;
            
            // Hide search results
            searchResults.style.display = 'none';
            
            // Update student info display
            const studentInfo = document.getElementById('selectedStudentInfo');
            const guardianInfo = document.getElementById('guardianInfo');
            const syncWarning = document.getElementById('syncWarning');
            const syncCheckbox = document.getElementById('sync_to_student');
            
            document.getElementById('selectedStudentName').textContent = student.name || 'Unknown';
            document.getElementById('selectedStudentLRN').textContent = student.lrn || 'N/A';
            document.getElementById('selectedStudentGrade').textContent = student.grade_level || 'N/A';
            document.getElementById('selectedStudentSection').textContent = student.section || 'No Section';
            
            studentInfo.classList.remove('d-none');
            
            // Validate field
            studentSearchInput.classList.remove('is-invalid');
            studentSearchInput.classList.add('is-valid');
            
            // Show guardian info if exists
            const guardianName = student.guardian_name || '';
            const guardianContact = student.guardian_contact || '';
            const guardianRelationship = student.guardian_relationship || '';
            const syncInfo = document.getElementById('syncInfo');
            
            if (guardianName || guardianContact) {
                document.getElementById('currentGuardianName').textContent = 
                    guardianName ? `Name: ${guardianName}` : 'Name: Not provided';
                document.getElementById('currentGuardianContact').textContent = 
                    guardianContact ? `Contact: ${guardianContact}` : 'Contact: Not provided';
                if (guardianRelationship) {
                    document.getElementById('currentGuardianContact').textContent += ` | Relationship: ${guardianRelationship}`;
                }
                guardianInfo.style.display = 'block';
                if (syncWarning) syncWarning.style.display = 'inline';
                if (syncInfo) syncInfo.style.display = 'none';
                if (syncCheckbox) {
                    syncCheckbox.checked = false; // Uncheck by default if guardian exists
                    syncCheckbox.disabled = true; // Disable if guardian already exists
                }
            } else {
                guardianInfo.style.display = 'none';
                if (syncWarning) syncWarning.style.display = 'none';
                if (syncInfo) syncInfo.style.display = 'inline';
                if (syncCheckbox) {
                    syncCheckbox.checked = true; // Check by default if no guardian
                    syncCheckbox.disabled = false; // Enable if no guardian
                }
            }
        }
        
        function clearStudentSelection() {
            selectedStudent = null;
            studentIdInput.value = '';
            studentSearchInput.value = '';
            studentSearchInput.classList.remove('is-valid', 'is-invalid');
            
            const studentInfo = document.getElementById('selectedStudentInfo');
            const guardianInfo = document.getElementById('guardianInfo');
            const syncWarning = document.getElementById('syncWarning');
            
            studentInfo.classList.add('d-none');
            guardianInfo.style.display = 'none';
            if (syncWarning) syncWarning.style.display = 'none';
            
            studentSearchInput.focus();
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Keyboard navigation
        studentSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
                const items = searchResultsList.querySelectorAll('.list-group-item');
                if (items.length === 0) return;
                
                e.preventDefault();
                
                let currentIndex = -1;
                items.forEach((item, index) => {
                    if (item.classList.contains('active')) {
                        currentIndex = index;
                    }
                });
                
                if (e.key === 'ArrowDown') {
                    const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                    items.forEach(item => item.classList.remove('active'));
                    items[nextIndex].classList.add('active');
                    items[nextIndex].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                    items.forEach(item => item.classList.remove('active'));
                    items[prevIndex].classList.add('active');
                    items[prevIndex].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter') {
                    if (currentIndex >= 0) {
                        items[currentIndex].click();
                    }
                }
            } else if (e.key === 'Escape') {
                searchResults.style.display = 'none';
            }
        });
    }

    // Enhanced form validation
    if (form) {
        // Real-time validation on input
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    validateField(this);
                }
            });
        });

        // Form submission with loading state
        form.addEventListener('submit', function(e) {
            // Validate all fields
            let isValid = true;
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            // Also validate student selection
            if (studentIdInput && !studentIdInput.value) {
                isValid = false;
                if (studentSearchInput) {
                    studentSearchInput.classList.add('is-invalid');
                    studentSearchInput.focus();
                }
            }

            if (isValid) {
                showLoadingState();
                // Allow form to submit normally - don't prevent default
                // The loading state will show while form submits
            } else {
                e.preventDefault();
                showErrorMessage('Please fix the errors above before submitting.');
            }
        });
    }

    // Password validation function
    function validatePassword(password) {
        const requirements = {
            length: password.length >= 8,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            number: /\d/.test(password)
        };

        // Update requirement indicators
        Object.keys(requirements).forEach(requirement => {
            const element = document.querySelector(`[data-requirement="${requirement}"]`);
            if (element) {
                const icon = element.querySelector('svg use');
                if (requirements[requirement]) {
                    element.classList.add('text-success');
                    element.classList.remove('text-muted');
                    icon.setAttribute('href', '#icon-check');
                } else {
                    element.classList.add('text-muted');
                    element.classList.remove('text-success');
                    icon.setAttribute('href', '#icon-check');
                }
            }
        });

        return Object.values(requirements).every(req => req);
    }

    // Field validation function
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Remove existing validation classes
        field.classList.remove('is-valid', 'is-invalid');

        // Special handling for student search field
        if (field.id === 'student_search') {
            const studentIdValue = studentIdInput ? studentIdInput.value : '';
            if (!studentIdValue) {
                isValid = false;
                errorMessage = 'Please select a student.';
                field.classList.add('is-invalid');
                return isValid;
            } else {
                field.classList.add('is-valid');
                return true;
            }
        }

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required.';
        }

        // Pattern validation
        if (isValid && value && field.hasAttribute('pattern')) {
            const pattern = new RegExp(field.getAttribute('pattern'));
            if (!pattern.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid value.';
            }
        }

        // Email validation
        if (isValid && field.type === 'email' && value) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address.';
            }
        }

        // Password validation
        if (isValid && field.type === 'password' && value) {
            if (!validatePassword(value)) {
                isValid = false;
                errorMessage = 'Password must meet all requirements.';
            }
        }

        // Name validation
        if (isValid && field.id === 'name' && value) {
            if (value.length < 2 || value.length > 50) {
                isValid = false;
                errorMessage = 'Name must be between 2 and 50 characters.';
            }
        }

        // Apply validation classes
        if (value) { // Only show validation if field has content
            field.classList.add(isValid ? 'is-valid' : 'is-invalid');
        }

        return isValid;
    }

    // Loading state management
    function showLoadingState() {
        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');
    }

    function hideLoadingState() {
        submitBtn.disabled = false;
        btnText.classList.remove('d-none');
        btnLoading.classList.add('d-none');
    }

    // Success message
    function showSuccessMessage() {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <svg width="20" height="20" fill="currentColor" class="me-2">
                <use href="#icon-check"></use>
            </svg>
            <strong>Success!</strong> Parent account has been created successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        form.parentNode.insertBefore(alertDiv, form.nextSibling);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Error message
    function showErrorMessage(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <svg width="20" height="20" fill="currentColor" class="me-2">
                <use href="#icon-alerts"></use>
            </svg>
            <strong>Error!</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        form.parentNode.insertBefore(alertDiv, form.nextSibling);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Add smooth animations to form elements
    const formElements = form.querySelectorAll('.form-floating');
    formElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Add focus effects
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
});

// Add CSS for enhanced form styling
if (!document.querySelector('#create-parent-form-styles')) {
    const style = document.createElement('style');
    style.id = 'create-parent-form-styles';
    style.textContent = `
        .form-floating.focused {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }
        
        .password-requirements .requirement {
            transition: color 0.3s ease;
        }
        
        .password-requirements .requirement.text-success {
            color: #198754 !important;
        }
        
        .student-info {
            background: rgba(13, 110, 253, 0.05);
            border: 1px solid rgba(13, 110, 253, 0.1);
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        #student_search_results {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--bs-border-color);
            margin-top: 2px;
        }
        
        #student_search_results .list-group-item {
            border-left: none;
            border-right: none;
            padding: 0.75rem 1rem;
            transition: background-color 0.15s ease;
        }
        
        #student_search_results .list-group-item:hover,
        #student_search_results .list-group-item.active {
            background-color: var(--bs-primary-bg-subtle);
            color: var(--bs-primary-text-emphasis);
        }
        
        #student_search_results .list-group-item:first-child {
            border-top: none;
        }
        
        #student_search_results .list-group-item:last-child {
            border-bottom: none;
        }
        
        .cursor-pointer {
            cursor: pointer;
        }
        
        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
            border-color: #0d6efd;
        }
        
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        .alert {
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.1), rgba(25, 135, 84, 0.05));
            border-left: 4px solid #198754;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
            border-left: 4px solid #dc3545;
        }
    `;
    document.head.appendChild(style);
}
