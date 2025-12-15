/**
 * Admin Sections Management JavaScript
 * Handles real-time updates, filtering, and section management operations
 */

// Global variables
let unassignedStudentsCache = null;
let lastRefresh = null;

/**
 * Refresh section data from server
 */
function refreshSectionData() {
    const refreshBtn = document.querySelector('button[onclick="refreshSectionData()"]');
    if (refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Refreshing...';
    }

    const rows = Array.from(document.querySelectorAll('#sectionsTable tbody tr'));
    if (rows.length === 0) {
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<svg width="16" height="16" fill="currentColor"><use href="#icon-refresh"></use></svg><span class="d-none d-md-inline ms-1">Refresh</span>';
        }
        return;
    }

    let completed = 0;
    rows.forEach(row => {
        const sectionId = row.getAttribute('data-section-id');
        fetch(`/api/admin/getSectionSlots.php?section_id=${encodeURIComponent(sectionId)}`)
            .then(r => r.json())
            .then(data => {
                if (data && data.success && data.capacity) {
                    const enrolled = parseInt(data.capacity.current_students) || 0;
                    const max = parseInt(data.capacity.max_students) || 0;
                    const available = Math.max(0, max - enrolled);
                    const percentage = max > 0 ? (enrolled / max) * 100 : 0;

                    // Update enrolled (5th column), available (6th), status badge (7th)
                    const enrolledCell = row.querySelector('td:nth-child(5) span');
                    const availableCell = row.querySelector('td:nth-child(6) span');
                    const statusCell = row.querySelector('td:nth-child(7)');

                    if (enrolledCell) enrolledCell.textContent = enrolled;
                    if (availableCell) {
                        availableCell.textContent = available;
                        availableCell.classList.toggle('text-danger', available <= 0);
                        availableCell.classList.toggle('text-success', available > 0);
                    }

                    let statusClass = 'bg-success';
                    let statusText = 'Available';
                    let statusKey = 'available';
                    if (percentage >= 100) { statusClass = 'bg-danger'; statusText = 'Full'; statusKey = 'full'; }
                    else if (percentage >= 80) { statusClass = 'bg-warning'; statusText = 'Nearly Full'; statusKey = 'nearly_full'; }

                    row.setAttribute('data-status', statusKey);

                    if (statusCell) {
                        const badge = statusCell.querySelector('.badge');
                        const bar = statusCell.querySelector('.progress-bar');
                        if (badge) {
                            badge.className = `badge ${statusClass}`;
                            badge.textContent = statusText;
                        }
                        if (bar) {
                            bar.className = `progress-bar ${statusClass === 'bg-danger' ? 'bg-danger' : (statusClass === 'bg-warning' ? 'bg-warning' : 'bg-success')}`;
                            bar.style.width = `${Math.min(100, percentage)}%`;
                        }
                    }
                }
            })
            .catch(() => {})
            .finally(() => {
                completed++;
                if (completed === rows.length) {
                    updateSectionCounts();
                    if (refreshBtn) {
                        refreshBtn.disabled = false;
                        refreshBtn.innerHTML = '<svg width="16" height="16" fill="currentColor"><use href="#icon-refresh"></use></svg><span class="d-none d-md-inline ms-1">Refresh</span>';
                    }
                }
            });
    });
}

/**
 * Filter sections based on search and filters
 */
function filterSections() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const gradeFilter = document.getElementById('gradeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#sectionsTable tbody tr');

    let visibleCount = 0;

    rows.forEach(row => {
        const sectionName = row.querySelector('td:nth-child(1) h6').textContent.toLowerCase();
        const room = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        const grade = row.getAttribute('data-grade');
        const status = row.getAttribute('data-status');

        // Check search filter
        const matchesSearch = !searchTerm || 
            sectionName.includes(searchTerm) || 
            room.includes(searchTerm) ||
            grade === searchTerm;

        // Check grade filter
        const matchesGrade = !gradeFilter || grade === gradeFilter;

        // Check status filter
        const matchesStatus = !statusFilter || status === statusFilter;

        if (matchesSearch && matchesGrade && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Update total sections count
    updateSectionCounts();
}

/**
 * Clear all filters
 */
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('gradeFilter').value = '';
    document.getElementById('statusFilter').value = '';
    filterSections();
}

/**
 * Update section count statistics
 */
function updateSectionCounts() {
    const rows = Array.from(document.querySelectorAll('#sectionsTable tbody tr:not([style*="display: none"])'));
    
    const totalSections = rows.length;
    const availableSections = rows.filter(row => row.getAttribute('data-status') === 'available').length;
    const fullSections = rows.filter(row => row.getAttribute('data-status') === 'full').length;

    const totalEl = document.getElementById('totalSections');
    const availableEl = document.getElementById('availableSections');
    const fullEl = document.getElementById('fullSections');

    if (totalEl) totalEl.textContent = totalSections;
    if (availableEl) availableEl.textContent = availableSections;
    if (fullEl) fullEl.textContent = fullSections;
}

/**
 * View section details
 */
function viewSectionDetails(sectionId) {
    const modal = new bootstrap.Modal(document.getElementById('sectionDetailsModal'));
    const content = document.getElementById('sectionDetailsContent');
    
    content.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading section details...</p></div>';
    modal.show();

    // Get base URL from the page
    const baseUrl = window.location.origin + (window.location.pathname.includes('/student-monitoring') ? '/student-monitoring' : '');
    const apiUrl = `${baseUrl}/admin/api/section-details?section_id=${encodeURIComponent(sectionId)}`;

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.section) {
                const section = data.section;
                const enrolled = parseInt(section.enrolled_students) || 0;
                const max = parseInt(section.max_students) || 0;
                const available = Math.max(0, parseInt(section.available_slots) || (max - enrolled));
                const percentage = max > 0 ? (enrolled / max) * 100 : 0;

                let statusClass = 'bg-success';
                let statusText = 'Available';
                if (percentage >= 100) {
                    statusClass = 'bg-danger';
                    statusText = 'Full';
                } else if (percentage >= 80) {
                    statusClass = 'bg-warning';
                    statusText = 'Nearly Full';
                }

                content.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Section Name</h6>
                            <h5 class="mb-0">${escapeHtml(section.name || 'N/A')}</h5>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Grade Level</h6>
                            <h5 class="mb-0"><span class="badge bg-info">Grade ${section.grade_level || 'N/A'}</span></h5>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Room</h6>
                            <p class="mb-0">${escapeHtml(section.room || 'N/A')}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">School Year</h6>
                            <p class="mb-0">${escapeHtml(section.school_year || 'N/A')}</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Capacity</h6>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Enrolled: <strong>${enrolled}</strong></span>
                            <span>Maximum: <strong>${max}</strong></span>
                            <span>Available: <strong class="${available <= 0 ? 'text-danger' : 'text-success'}">${available}</strong></span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar ${statusClass}" role="progressbar" 
                                 style="width: ${Math.min(100, percentage)}%" 
                                 aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100">
                                ${percentage.toFixed(1)}%
                            </div>
                        </div>
                        <span class="badge ${statusClass} mt-2">${statusText}</span>
                    </div>

                    ${section.description ? `
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Description</h6>
                        <p class="mb-0">${escapeHtml(section.description)}</p>
                    </div>
                    ` : ''}

                    ${section.adviser_name ? `
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Adviser</h6>
                        <p class="mb-0">${escapeHtml(section.adviser_name)}</p>
                    </div>
                    ` : '<div class="mb-3"><h6 class="text-muted mb-1">Adviser</h6><p class="mb-0 text-muted">No adviser assigned</p></div>'}

                    <div class="alert alert-info">
                        <small><strong>Section ID:</strong> ${section.id} | <strong>Status:</strong> ${section.is_active ? 'Active' : 'Inactive'}</small>
                    </div>
                `;
            } else {
                content.innerHTML = `<div class="alert alert-danger">
                    <strong>Error:</strong> ${data.error || 'Failed to load section details.'}
                </div>`;
            }
        })
        .catch(error => {
            console.error('Error loading section details:', error);
            content.innerHTML = `<div class="alert alert-danger">
                <strong>Error:</strong> Unable to load section details. Please try again.
                <br><small>${error.message}</small>
            </div>`;
        });
}

/**
 * Edit section capacity
 */
function editSectionCapacity(sectionId, currentMax, enrolled) {
    const modal = new bootstrap.Modal(document.getElementById('editCapacityModal'));
    const form = document.getElementById('editCapacityForm');
    const info = document.getElementById('capacityInfo');
    const maxInput = document.getElementById('edit_max_students');

    if (!form || !info || !maxInput) {
        console.error('Required elements not found for edit capacity modal');
        return;
    }

    document.getElementById('edit_section_id').value = sectionId;
    maxInput.value = currentMax;
    maxInput.min = Math.max(1, enrolled); // Ensure minimum is at least enrolled count

    const available = Math.max(0, currentMax - enrolled);
    info.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <small class="text-muted">Currently Enrolled:</small>
                <div class="fw-bold">${enrolled} students</div>
            </div>
            <div class="col-md-4">
                <small class="text-muted">Current Maximum:</small>
                <div class="fw-bold">${currentMax} students</div>
            </div>
            <div class="col-md-4">
                <small class="text-muted">Available Slots:</small>
                <div class="fw-bold text-${available <= 0 ? 'danger' : 'success'}">
                    ${available} slots
                </div>
            </div>
        </div>
        <div class="alert alert-warning mt-2 mb-0">
            <small>⚠️ The new maximum cannot be less than ${enrolled} (current enrolled students).</small>
        </div>
    `;

    // Remove existing event listeners by cloning the input
    const newMaxInput = maxInput.cloneNode(true);
    maxInput.parentNode.replaceChild(newMaxInput, maxInput);
    const updatedMaxInput = document.getElementById('edit_max_students');
    
    updatedMaxInput.addEventListener('input', function() {
        const newMax = parseInt(this.value) || 0;
        if (newMax < enrolled) {
            this.setCustomValidity(`Must be at least ${enrolled} (current enrolled students)`);
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
        }
    });

    // Fetch current room value and other section details
    const baseUrl = window.location.origin + (window.location.pathname.includes('/student-monitoring') ? '/student-monitoring' : '');
    const apiUrl = `${baseUrl}/admin/api/section-details?section_id=${encodeURIComponent(sectionId)}`;
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.section) {
                const roomInput = document.getElementById('edit_room');
                if (roomInput) {
                    roomInput.value = data.section.room || '';
                }
                // Update max if needed (in case data changed)
                if (updatedMaxInput) {
                    const currentEnrolled = parseInt(data.section.enrolled_students) || 0;
                    updatedMaxInput.min = Math.max(1, currentEnrolled);
                }
            }
        })
        .catch(error => {
            console.error('Error loading section details:', error);
            // Continue anyway - we have the basic info
        });

    modal.show();
}

/**
 * Assign student to section
 */
function assignStudentToSection(sectionId, sectionName) {
    const modal = new bootstrap.Modal(document.getElementById('assignStudentModal'));
    const sectionNameDisplay = document.getElementById('sectionNameDisplay');
    const sectionInfo = document.getElementById('sectionInfo');
    const sectionIdInput = document.getElementById('assign_section_id');
    
    if (!sectionIdInput || !sectionNameDisplay || !sectionInfo) {
        console.error('Required elements not found for assign student modal');
        return;
    }
    
    sectionIdInput.value = sectionId;
    sectionNameDisplay.textContent = sectionName || 'Unknown Section';

    // Show section info with capacity
    const baseUrl = window.location.origin + (window.location.pathname.includes('/student-monitoring') ? '/student-monitoring' : '');
    const apiUrl = `${baseUrl}/admin/api/section-details?section_id=${encodeURIComponent(sectionId)}`;
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.section) {
                const section = data.section;
                const enrolled = parseInt(section.enrolled_students) || 0;
                const max = parseInt(section.max_students) || 0;
                const available = Math.max(0, parseInt(section.available_slots) || (max - enrolled));

                sectionInfo.innerHTML = `
                    <strong>Section:</strong> ${escapeHtml(section.name || sectionName)}<br>
                    <strong>Grade Level:</strong> Grade ${section.grade_level || 'N/A'}<br>
                    <strong>Capacity:</strong> ${enrolled}/${max} (${available} available)
                    ${available <= 0 ? '<br><span class="text-danger"><small>⚠️ Section is full!</small></span>' : ''}
                `;
            } else {
                sectionInfo.innerHTML = `
                    <strong>Section:</strong> ${escapeHtml(sectionName)}<br>
                    <span class="text-muted">Loading capacity information...</span>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading section details:', error);
            sectionInfo.innerHTML = `
                <strong>Section:</strong> ${escapeHtml(sectionName)}<br>
                <span class="text-warning">Could not load capacity information</span>
            `;
        });

    // Load unassigned students
    loadUnassignedStudents();

    modal.show();
}

/**
 * Load unassigned students
 */
function loadUnassignedStudents(search = '') {
    const tbody = document.getElementById('studentsTableBody');
    if (!tbody) {
        console.error('studentsTableBody not found');
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm me-2"></div>Loading students...</td></tr>';

    const baseUrl = window.location.origin + (window.location.pathname.includes('/student-monitoring') ? '/student-monitoring' : '');
    const url = `${baseUrl}/admin/api/unassigned-students` + 
                (search ? `?search=${encodeURIComponent(search)}` : '');

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.students) {
                if (data.students.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No unassigned students found.</td></tr>';
                    return;
                }

                const sectionIdInput = document.getElementById('assign_section_id');
                const sectionId = sectionIdInput ? sectionIdInput.value : 0;

                tbody.innerHTML = data.students.map(student => `
                    <tr>
                        <td>${escapeHtml(student.name || 'Unknown')}</td>
                        <td><code>${escapeHtml(student.lrn || 'N/A')}</code></td>
                        <td><span class="badge bg-info">Grade ${student.grade_level || 'N/A'}</span></td>
                        <td><small>${escapeHtml(student.email || 'N/A')}</small></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" 
                                    onclick="confirmAssignStudent(${student.id}, ${sectionId})"
                                    title="Assign to section">
                                <svg width="14" height="14" fill="currentColor">
                                    <use href="#icon-check"></use>
                                </svg>
                                Assign
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">
                    ${data.error || 'Failed to load students.'}
                </td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading unassigned students:', error);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Error loading students. Please try again.</td></tr>';
        });
}

/**
 * Search unassigned students
 */
function searchUnassignedStudents() {
    const search = document.getElementById('studentSearch').value;
    loadUnassignedStudents(search);
}

/**
 * Confirm and assign student
 */
function confirmAssignStudent(studentId, sectionId) {
    if (!studentId || !sectionId) {
        showNotification('Invalid student or section ID', 'error');
        return;
    }

    if (!confirm('Are you sure you want to assign this student to the selected section?')) {
        return;
    }

    const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
    if (!csrfTokenInput) {
        showNotification('CSRF token not found. Please refresh the page.', 'error');
        return;
    }

    const baseUrl = window.location.origin + (window.location.pathname.includes('/student-monitoring') ? '/student-monitoring' : '');
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${baseUrl}/admin/assign-student-to-section`;
    
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="${csrfTokenInput.value}">
        <input type="hidden" name="student_id" value="${studentId}">
        <input type="hidden" name="section_id" value="${sectionId}">
    `;

    document.body.appendChild(form);
    form.submit();
}

/**
 * Show notification
 */
function showNotification(message, type = 'success') {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'check-circle' : 'alert-circle';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alert.style.zIndex = '9999';
    alert.style.maxWidth = '500px';
    alert.innerHTML = `
        <svg width="16" height="16" fill="currentColor" class="me-2">
            <use href="#icon-${icon}"></use>
        </svg>
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Handle form submissions with AJAX
 */
document.addEventListener('DOMContentLoaded', function() {
    // Create section form
    const createForm = document.getElementById('createSectionForm');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
            }
            
            const formData = new FormData(this);
            const baseUrl = window.location.origin + (window.location.pathname.includes('/student-monitoring') ? '/student-monitoring' : '');
            const actionUrl = this.action.startsWith('http') ? this.action : baseUrl + this.action;
            
            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is JSON or HTML redirect
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // If it's a redirect, reload the page
                    if (response.redirected || response.status === 302 || response.status === 200) {
                        return { success: true, message: 'Section created successfully!' };
                    }
                    throw new Error('Unexpected response format');
                }
            })
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Section created successfully!', 'success');
                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createSectionModal'));
                    if (modal) {
                        modal.hide();
                        // Reset form after modal is hidden
                        setTimeout(() => {
                            const form = document.getElementById('createSectionForm');
                            if (form) form.reset();
                        }, 300);
                    }
                    // Reload page after short delay to show new section
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification(data.error || 'Failed to create section', 'error');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Create Section';
                    }
                }
            })
            .catch(error => {
                console.error('Error creating section:', error);
                showNotification('An error occurred. Please try again.', 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Section';
                }
            });
        });
    }

    // Edit capacity form
    const editForm = document.getElementById('editCapacityForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
            }
            
            const formData = new FormData(this);
            const baseUrl = window.location.origin + (window.location.pathname.includes('/student-monitoring') ? '/student-monitoring' : '');
            const actionUrl = this.action.startsWith('http') ? this.action : baseUrl + this.action;
            
            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    if (response.redirected || response.status === 302 || response.status === 200) {
                        return { success: true, message: 'Section updated successfully!' };
                    }
                    throw new Error('Unexpected response format');
                }
            })
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Section updated successfully!', 'success');
                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editCapacityModal'));
                    if (modal) {
                        modal.hide();
                        // Reset form after modal is hidden
                        setTimeout(() => {
                            const form = document.getElementById('editCapacityForm');
                            if (form) form.reset();
                        }, 300);
                    }
                    // Reload page after short delay to show updated section
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification(data.error || 'Failed to update section', 'error');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Update Capacity';
                    }
                }
            })
            .catch(error => {
                console.error('Error updating section:', error);
                showNotification('An error occurred. Please try again.', 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Update Capacity';
                }
            });
        });
    }
});

// Explicitly ensure functions are globally available
(function() {
    'use strict';
    if (typeof window !== 'undefined') {
        // Ensure critical functions are available on window object
        if (typeof viewSectionDetails === 'function') {
            window.viewSectionDetails = viewSectionDetails;
        }
        if (typeof editSectionCapacity === 'function') {
            window.editSectionCapacity = editSectionCapacity;
        }
        if (typeof assignStudentToSection === 'function') {
            window.assignStudentToSection = assignStudentToSection;
        }
        if (typeof refreshSectionData === 'function') {
            window.refreshSectionData = refreshSectionData;
        }
        if (typeof filterSections === 'function') {
            window.filterSections = filterSections;
        }
        if (typeof loadUnassignedStudents === 'function') {
            window.loadUnassignedStudents = loadUnassignedStudents;
        }
        if (typeof searchUnassignedStudents === 'function') {
            window.searchUnassignedStudents = searchUnassignedStudents;
        }
        
        console.log('admin-sections.js: Functions registered globally');
    }
})();

