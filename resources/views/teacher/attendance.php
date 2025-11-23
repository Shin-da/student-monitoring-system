<?php
declare(strict_types=1);

$sections = $sections ?? [];
$students = $students ?? [];
$summary = $summary ?? ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
$filters = $filters ?? ['date' => date('Y-m-d'), 'section_id' => null, 'subject_id' => null];

// Build section options (deduplicate sections)
$sectionMap = [];
$sectionSubjectMap = []; // Map section_id to array of subjects
foreach ($sections as $section) {
    $secId = (int)$section['section_id'];
    $subjId = (int)$section['subject_id'];
    
    // Store unique sections
    if (!isset($sectionMap[$secId])) {
        $sectionMap[$secId] = [
            'section_id' => $secId,
            'label' => $section['section_name'],
        ];
    }
    
    // Map subjects to sections
    if (!isset($sectionSubjectMap[$secId])) {
        $sectionSubjectMap[$secId] = [];
    }
    $sectionSubjectMap[$secId][] = [
        'subject_id' => $subjId,
        'subject_name' => $section['subject_name'],
    ];
}

$sectionOptions = array_values($sectionMap);
$currentSectionId = $filters['section_id'];
$subjectOptions = $currentSectionId && isset($sectionSubjectMap[$currentSectionId]) 
    ? $sectionSubjectMap[$currentSectionId] 
    : [];
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Attendance</h1>
        <p class="text-muted mb-0">Record attendance and monitor participation for every class.</p>
    </div>
    <span class="badge bg-light text-muted">Date: <?= htmlspecialchars($filters['date']) ?></span>
</div>

<div class="surface p-4 mb-3">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-sm-4">
            <label class="form-label small text-muted">Date</label>
            <input class="form-control" type="date" name="date" value="<?= htmlspecialchars($filters['date']) ?>">
        </div>
        <div class="col-sm-4">
            <label class="form-label small text-muted">Section</label>
            <select class="form-select" name="section" id="sectionSelect">
                <option value="">Select Section</option>
                <?php foreach ($sectionOptions as $option): ?>
                    <option value="<?= (int)$option['section_id'] ?>" <?= ((int)$filters['section_id'] === (int)$option['section_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($option['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4">
            <label class="form-label small text-muted">Subject</label>
            <select class="form-select" name="subject" id="subjectSelect">
                <option value="">Select Subject</option>
                <?php foreach ($subjectOptions as $option): ?>
                    <option value="<?= (int)$option['subject_id'] ?>" <?= ((int)$filters['subject_id'] === (int)$option['subject_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($option['subject_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="<?= \Helpers\Url::to('/teacher/attendance') ?>">Reset</a>
            <button class="btn btn-primary" type="submit">Apply</button>
        </div>
    </form>
</div>

<?php if (!empty($students)): ?>
<div class="surface p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Search Student</label>
            <div class="input-group">
                <span class="input-group-text">
                    <svg width="16" height="16" fill="currentColor">
                        <use href="#icon-search"></use>
                    </svg>
                </span>
                <input type="text" class="form-control" id="studentSearch" placeholder="Search by name or LRN...">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Filter by Status</label>
            <select class="form-select" id="statusFilter">
                <option value="">All Status</option>
                <option value="present">Present</option>
                <option value="late">Late</option>
                <option value="excused">Excused</option>
                <option value="absent">Absent</option>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label small text-muted mb-1">Bulk Actions</label>
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-outline-success btn-sm" id="bulkPresent" title="Mark all visible as Present">
                    <svg width="14" height="14" fill="currentColor" class="me-1">
                        <use href="#icon-check"></use>
                    </svg>
                    All Present
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm" id="bulkLate" title="Mark all visible as Late">
                    All Late
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="bulkAbsent" title="Mark all visible as Absent">
                    All Absent
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <?php foreach (['present' => 'success', 'absent' => 'danger', 'late' => 'warning', 'excused' => 'info'] as $status => $variant): ?>
        <div class="col-6 col-md-3">
            <div class="surface p-3 h-100 text-center border-start border-3 border-<?= $variant ?>">
                <div class="text-muted text-uppercase small"><?= ucfirst($status) ?></div>
                <div class="h4 fw-bold mb-0"><?= number_format($summary[$status] ?? 0) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($students)): ?>
    <div class="surface p-4 text-center text-muted">No students found for the selected class or no attendance recorded yet.</div>
<?php else: ?>
    <div class="surface p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>LRN</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <?php foreach ($students as $student): ?>
                        <?php
                        $status = $student['attendance_status'] ?? 'absent';
                        $badgeMap = [
                            'present' => 'success',
                            'late' => 'warning',
                            'excused' => 'info',
                            'absent' => 'danger',
                        ];
                        $badgeClass = $badgeMap[$status] ?? 'secondary';
                        ?>
                        <tr data-student-id="<?= (int)($student['student_id'] ?? 0) ?>" 
                            data-student-name="<?= htmlspecialchars(strtolower($student['student_name'] ?? '')) ?>"
                            data-student-lrn="<?= htmlspecialchars(strtolower($student['lrn'] ?? '')) ?>"
                            data-attendance-status="<?= htmlspecialchars($status) ?>">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?= htmlspecialchars($student['student_name'] ?? '') ?>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-muted" 
                                            onclick="viewAttendanceHistory(<?= (int)($student['student_id'] ?? 0) ?>, '<?= htmlspecialchars($student['student_name'] ?? '') ?>')"
                                            title="View attendance history">
                                        <svg width="14" height="14" fill="currentColor">
                                            <use href="#icon-eye"></use>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($student['lrn'] ?? '') ?></td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Attendance status">
                                    <input type="radio" class="btn-check" name="attendance_<?= (int)($student['student_id'] ?? 0) ?>" 
                                           id="present_<?= (int)($student['student_id'] ?? 0) ?>" 
                                           value="present" 
                                           data-status="present"
                                           <?= $status === 'present' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="present_<?= (int)($student['student_id'] ?? 0) ?>">Present</label>

                                    <input type="radio" class="btn-check" name="attendance_<?= (int)($student['student_id'] ?? 0) ?>" 
                                           id="late_<?= (int)($student['student_id'] ?? 0) ?>" 
                                           value="late" 
                                           data-status="late"
                                           <?= $status === 'late' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-warning" for="late_<?= (int)($student['student_id'] ?? 0) ?>">Late</label>

                                    <input type="radio" class="btn-check" name="attendance_<?= (int)($student['student_id'] ?? 0) ?>" 
                                           id="excused_<?= (int)($student['student_id'] ?? 0) ?>" 
                                           value="excused" 
                                           data-status="excused"
                                           <?= $status === 'excused' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-info" for="excused_<?= (int)($student['student_id'] ?? 0) ?>">Excused</label>

                                    <input type="radio" class="btn-check" name="attendance_<?= (int)($student['student_id'] ?? 0) ?>" 
                                           id="absent_<?= (int)($student['student_id'] ?? 0) ?>" 
                                           value="absent" 
                                           data-status="absent"
                                           <?= $status === 'absent' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-danger" for="absent_<?= (int)($student['student_id'] ?? 0) ?>">Absent</label>
                                </div>
                                <span class="attendance-save-indicator ms-2" style="display: none;">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
(function() {
    const saveUrl = '<?= \Helpers\Url::to('/teacher/api/attendance/save') ?>';
    
    // Section to subjects mapping
    const sectionSubjectMap = <?= json_encode($sectionSubjectMap ?? []) ?>;
    
    // Get form elements
    const sectionSelect = document.getElementById('sectionSelect');
    const subjectSelect = document.getElementById('subjectSelect');
    const dateInput = document.querySelector('input[name="date"]');
    
    // Function to get current filter values
    function getCurrentFilters() {
        return {
            sectionId: parseInt(sectionSelect?.value || '0'),
            subjectId: parseInt(subjectSelect?.value || '0'),
            date: dateInput?.value || ''
        };
    }
    
    // Update subject dropdown when section changes
    if (sectionSelect) {
        sectionSelect.addEventListener('change', function() {
            const sectionId = parseInt(this.value || '0');
            const currentSubjectId = parseInt(subjectSelect?.value || '0');
            
            // Clear subject dropdown
            if (subjectSelect) {
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                
                // Populate subjects for selected section
                if (sectionId && sectionSubjectMap[sectionId]) {
                    sectionSubjectMap[sectionId].forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.subject_id;
                        option.textContent = subject.subject_name;
                        // Try to preserve selected subject if it exists in new list
                        if (currentSubjectId === subject.subject_id) {
                            option.selected = true;
                        }
                        subjectSelect.appendChild(option);
                    });
                }
            }
        });
    }
    
    // Summary counters
    const summaryCounters = {
        present: document.querySelector('.border-success .h4'),
        absent: document.querySelector('.border-danger .h4'),
        late: document.querySelector('.border-warning .h4'),
        excused: document.querySelector('.border-info .h4')
    };
    
    // Update summary counter
    function updateSummary(status, increment) {
        const counterMap = {
            'present': summaryCounters.present,
            'absent': summaryCounters.absent,
            'late': summaryCounters.late,
            'excused': summaryCounters.excused
        };
        
        const counter = counterMap[status];
        if (counter) {
            const current = parseInt(counter.textContent.replace(/,/g, '')) || 0;
            counter.textContent = (current + increment).toLocaleString();
        }
    }
    
    // Handle attendance status change
    document.querySelectorAll('input[type="radio"][name^="attendance_"]').forEach(radio => {
        radio.addEventListener('change', async function() {
            if (!this.checked) return;
            
            const filters = getCurrentFilters();
            
            // Validate section and subject are selected
            if (!filters.sectionId || !filters.subjectId) {
                alert('Please select a section and subject first.');
                // Revert to previous status
                const row = this.closest('tr');
                const previousStatus = row.dataset.previousStatus || 'absent';
                const previousRadio = row.querySelector(`input[value="${previousStatus}"]`);
                if (previousRadio) {
                    previousRadio.checked = true;
                }
                return;
            }
            
            const studentId = parseInt(this.name.replace('attendance_', ''));
            const status = this.value;
            const row = this.closest('tr');
            const indicator = row.querySelector('.attendance-save-indicator');
            
            // Show loading indicator
            indicator.style.display = 'inline-block';
            
            // Get previous status to update summary
            const previousStatus = row.dataset.previousStatus || 'absent';
            row.dataset.previousStatus = status;
            
            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        section_id: filters.sectionId,
                        subject_id: filters.subjectId,
                        date: filters.date,
                        status: status
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update summary (decrement old, increment new)
                    if (previousStatus !== status) {
                        updateSummary(previousStatus, -1);
                        updateSummary(status, 1);
                    }
                    
                    // Show success indicator
                    indicator.innerHTML = '<svg width="16" height="16" fill="currentColor" class="text-success"><use href="#icon-check"></use></svg>';
                    setTimeout(() => {
                        indicator.style.display = 'none';
                    }, 2000);
                } else {
                    throw new Error(result.message || 'Failed to save attendance');
                }
            } catch (error) {
                console.error('Error saving attendance:', error);
                
                // Revert to previous status
                const previousRadio = row.querySelector(`input[value="${previousStatus}"]`);
                if (previousRadio) {
                    previousRadio.checked = true;
                }
                
                // Show error indicator
                indicator.innerHTML = '<span class="text-danger fw-bold">✗</span>';
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 3000);
                
                // Show error message
                alert('Failed to save attendance: ' + (error.message || 'Unknown error'));
            }
        });
    });
    
    // Store initial status for each row
    document.querySelectorAll('tbody tr').forEach(row => {
        const checkedRadio = row.querySelector('input[type="radio"]:checked');
        if (checkedRadio) {
            row.dataset.previousStatus = checkedRadio.value;
        }
    });
    
    // Student search and filter functionality
    const studentSearch = document.getElementById('studentSearch');
    const statusFilter = document.getElementById('statusFilter');
    const tableBody = document.getElementById('studentsTableBody');
    
    function filterStudents() {
        if (!tableBody) return;
        
        const searchTerm = (studentSearch?.value || '').toLowerCase().trim();
        const statusValue = statusFilter?.value || '';
        const rows = tableBody.querySelectorAll('tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const studentName = row.dataset.studentName || '';
            const studentLrn = row.dataset.studentLrn || '';
            const attendanceStatus = row.dataset.attendanceStatus || '';
            
            // Check search term match
            const matchesSearch = !searchTerm || 
                studentName.includes(searchTerm) || 
                studentLrn.includes(searchTerm);
            
            // Check status filter match
            const matchesStatus = !statusValue || attendanceStatus === statusValue;
            
            // Show/hide row
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show message if no results
        let noResultsMsg = tableBody.querySelector('.no-results-message');
        if (visibleCount === 0 && (searchTerm || statusValue)) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('tr');
                noResultsMsg.className = 'no-results-message';
                noResultsMsg.innerHTML = `
                    <td colspan="3" class="text-center text-muted py-4">
                        <svg width="48" height="48" fill="currentColor" class="mb-2 opacity-50">
                            <use href="#icon-search"></use>
                        </svg>
                        <div>No students found matching your search criteria.</div>
                    </td>
                `;
                tableBody.appendChild(noResultsMsg);
            }
            noResultsMsg.style.display = '';
        } else if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
    }
    
    // Add event listeners
    if (studentSearch) {
        studentSearch.addEventListener('input', filterStudents);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterStudents);
    }
    
    // Bulk operations
    const bulkPresent = document.getElementById('bulkPresent');
    const bulkLate = document.getElementById('bulkLate');
    const bulkAbsent = document.getElementById('bulkAbsent');
    
    async function bulkMarkStatus(status) {
        if (!tableBody) return;
        
        const visibleRows = Array.from(tableBody.querySelectorAll('tr')).filter(row => 
            row.style.display !== 'none' && !row.classList.contains('no-results-message')
        );
        
        if (visibleRows.length === 0) {
            alert('No visible students to mark.');
            return;
        }
        
        if (!confirm(`Mark ${visibleRows.length} visible student(s) as ${status.toUpperCase()}?`)) {
            return;
        }
        
        const filters = getCurrentFilters();
        if (!filters.sectionId || !filters.subjectId) {
            alert('Please select a section and subject first.');
            return;
        }
        
        let successCount = 0;
        let failCount = 0;
        
        // Mark each visible student
        for (const row of visibleRows) {
            const studentId = parseInt(row.dataset.studentId || '0');
            if (!studentId) continue;
            
            const radio = row.querySelector(`input[value="${status}"]`);
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
                
                // Wait a bit for the save to complete
                await new Promise(resolve => setTimeout(resolve, 100));
                successCount++;
            } else {
                failCount++;
            }
        }
        
        // Show summary
        if (successCount > 0) {
            const statusMsg = failCount > 0 
                ? `Marked ${successCount} student(s) as ${status}. ${failCount} failed.`
                : `Successfully marked ${successCount} student(s) as ${status}.`;
            alert(statusMsg);
        }
    }
    
    if (bulkPresent) {
        bulkPresent.addEventListener('click', () => bulkMarkStatus('present'));
    }
    if (bulkLate) {
        bulkLate.addEventListener('click', () => bulkMarkStatus('late'));
    }
    if (bulkAbsent) {
        bulkAbsent.addEventListener('click', () => bulkMarkStatus('absent'));
    }
})();

// Attendance History Modal
function viewAttendanceHistory(studentId, studentName) {
    const modal = new bootstrap.Modal(document.getElementById('attendanceHistoryModal'));
    document.getElementById('attendanceHistoryModalLabel').textContent = `Attendance History - ${studentName}`;
    document.getElementById('attendanceHistoryContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading attendance history...</p></div>';
    modal.show();
    
    const sectionId = <?= (int)($filters['section_id'] ?? 0) ?>;
    const subjectId = <?= (int)($filters['subject_id'] ?? 0) ?>;
    
    fetch(`<?= \Helpers\Url::to('/teacher/api/attendance/history') ?>?student_id=${studentId}&section_id=${sectionId}&subject_id=${subjectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAttendanceHistory(data.history || []);
            } else {
                document.getElementById('attendanceHistoryContent').innerHTML = 
                    '<div class="text-center text-muted py-4">No attendance records found.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('attendanceHistoryContent').innerHTML = 
                '<div class="text-center text-danger py-4">Error loading attendance history.</div>';
        });
}

function displayAttendanceHistory(history) {
    const content = document.getElementById('attendanceHistoryContent');
    
    if (history.length === 0) {
        content.innerHTML = '<div class="text-center text-muted py-4">No attendance records found.</div>';
        return;
    }
    
    // Group by date
    const grouped = {};
    history.forEach(record => {
        const date = record.attendance_date;
        if (!grouped[date]) {
            grouped[date] = [];
        }
        grouped[date].push(record);
    });
    
    // Sort dates descending
    const dates = Object.keys(grouped).sort((a, b) => new Date(b) - new Date(a));
    
    let html = '<div class="table-responsive"><table class="table table-sm align-middle">';
    html += '<thead class="table-light"><tr><th>Date</th><th>Status</th><th>Subject</th><th>Section</th></tr></thead><tbody>';
    
    dates.forEach(date => {
        grouped[date].forEach(record => {
            const statusBadges = {
                'present': 'success',
                'late': 'warning',
                'excused': 'info',
                'absent': 'danger'
            };
            const badgeClass = statusBadges[record.status] || 'secondary';
            html += `
                <tr>
                    <td>${new Date(date).toLocaleDateString()}</td>
                    <td><span class="badge bg-${badgeClass}">${record.status.charAt(0).toUpperCase() + record.status.slice(1)}</span></td>
                    <td>${record.subject_name || 'N/A'}</td>
                    <td>${record.section_name || 'N/A'}</td>
                </tr>
            `;
        });
    });
    
    html += '</tbody></table></div>';
    content.innerHTML = html;
}
</script>

<!-- Attendance History Modal -->
<div class="modal fade" id="attendanceHistoryModal" tabindex="-1" aria-labelledby="attendanceHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceHistoryModalLabel">Attendance History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="attendanceHistoryContent">
                <!-- Content loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

