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
    
    content.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading...</p></div>';
    modal.show();

    fetch(`<?= \Helpers\Url::to('/admin/api/section-details') ?>?section_id=${sectionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.section) {
                const section = data.section;
                const enrolled = parseInt(section.enrolled_students) || 0;
                const max = parseInt(section.max_students) || 0;
                const available = parseInt(section.available_slots) || 0;
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
                            <h5 class="mb-0">${escapeHtml(section.name)}</h5>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Grade Level</h6>
                            <h5 class="mb-0"><span class="badge bg-info">Grade ${section.grade_level}</span></h5>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Room</h6>
                            <p class="mb-0">${escapeHtml(section.room || 'N/A')}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">School Year</h6>
                            <p class="mb-0">${escapeHtml(section.school_year)}</p>
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
                    ` : ''}

                    <div class="alert alert-info">
                        <small>Section ID: ${section.id} | Status: ${section.is_active ? 'Active' : 'Inactive'}</small>
                    </div>
                `;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load section details.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="alert alert-danger">Error loading section details.</div>';
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

    document.getElementById('edit_section_id').value = sectionId;
    maxInput.value = currentMax;
    maxInput.min = enrolled;

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
                <div class="fw-bold text-${currentMax - enrolled <= 0 ? 'danger' : 'success'}">
                    ${currentMax - enrolled} slots
                </div>
            </div>
        </div>
        <div class="alert alert-warning mt-2 mb-0">
            <small>⚠️ The new maximum cannot be less than ${enrolled} (current enrolled students).</small>
        </div>
    `;

    maxInput.addEventListener('input', function() {
        const newMax = parseInt(this.value) || 0;
        if (newMax < enrolled) {
            this.setCustomValidity(`Must be at least ${enrolled}`);
        } else {
            this.setCustomValidity('');
        }
    });

    // Fetch current room value
    fetch(`<?= \Helpers\Url::to('/admin/api/section-details') ?>?section_id=${sectionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.section) {
                document.getElementById('edit_room').value = data.section.room || '';
            }
        })
        .catch(error => console.error('Error loading section:', error));

    modal.show();
}

/**
 * Assign student to section
 */
function assignStudentToSection(sectionId, sectionName) {
    const modal = new bootstrap.Modal(document.getElementById('assignStudentModal'));
    const sectionNameDisplay = document.getElementById('sectionNameDisplay');
    const sectionInfo = document.getElementById('sectionInfo');
    
    document.getElementById('assign_section_id').value = sectionId;
    sectionNameDisplay.textContent = sectionName;

    // Show section info with capacity
    fetch(`<?= \Helpers\Url::to('/admin/api/section-details') ?>?section_id=${sectionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.section) {
                const section = data.section;
                const enrolled = parseInt(section.enrolled_students) || 0;
                const max = parseInt(section.max_students) || 0;
                const available = parseInt(section.available_slots) || 0;

                sectionInfo.innerHTML = `
                    <strong>Section:</strong> ${escapeHtml(section.name)}<br>
                    <strong>Capacity:</strong> ${enrolled}/${max} (${available} available)
                `;
            }
        })
        .catch(error => console.error('Error:', error));

    // Load unassigned students
    loadUnassignedStudents();

    modal.show();
}

/**
 * Load unassigned students
 */
function loadUnassignedStudents(search = '') {
    const tbody = document.getElementById('studentsTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm me-2"></div>Loading...</td></tr>';

    const url = `<?= \Helpers\Url::to('/admin/api/unassigned-students') ?>` + 
                (search ? `&search=${encodeURIComponent(search)}` : '');

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.students) {
                if (data.students.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No unassigned students found.</td></tr>';
                    return;
                }

                tbody.innerHTML = data.students.map(student => `
                    <tr>
                        <td>${escapeHtml(student.name)}</td>
                        <td><code>${escapeHtml(student.lrn || 'N/A')}</code></td>
                        <td><span class="badge bg-info">Grade ${student.grade_level || 'N/A'}</span></td>
                        <td><small>${escapeHtml(student.email)}</small></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" 
                                    onclick="confirmAssignStudent(${student.id}, ${document.getElementById('assign_section_id').value})">
                                <svg width="14" height="14" fill="currentColor">
                                    <use href="#icon-check"></use>
                                </svg>
                                Assign
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load students.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading students.</td></tr>';
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
    if (!confirm('Are you sure you want to assign this student to the selected section?')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= \Helpers\Url::to('/admin/assign-student-to-section') ?>';
    
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="${csrfToken}">
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
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Section created successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(data.error || 'Failed to create section', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            });
        });
    }

    // Edit capacity form
    const editForm = document.getElementById('editCapacityForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Section updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(data.error || 'Failed to update section', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            });
        });
    }
});

