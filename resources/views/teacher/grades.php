<?php
declare(strict_types=1);

$sections = $sections ?? [];
$subjects = $subjects ?? [];
$stats = $stats ?? ['total_students' => 0, 'grades_entered' => 0, 'pending_grades' => 0, 'avg_grade' => 0];
$grades = $grades ?? [];
$assignments = $assignments ?? [];
$filters = $filters ?? ['section_id' => null, 'subject_id' => null, 'grade_type' => null, 'student_id' => null];
$selectedStudent = $selectedStudent ?? null;
$studentQuarterlyGrades = $studentQuarterlyGrades ?? [];
$students = $students ?? [];

$sectionOptions = array_map(static function ($section) {
    return [
        'id' => $section['section_id'],
        'label' => $section['section_name'] ?? '',
    ];
}, $sections);

$subjectOptions = array_map(static function ($subject) {
    return [
        'id' => $subject['id'],
        'label' => $subject['name'] ?? '',
    ];
}, $subjects);
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Grade Management</h1>
        <p class="text-muted mb-0">Enter and review student grades for your classes.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGradeModal">
            <svg width="16" height="16" fill="currentColor" class="me-2">
                <use href="#icon-plus"></use>
            </svg>
            Add Grade
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Students</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['total_students'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Grades Submitted</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['grades_entered'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Pending Grades</div>
            <div class="h4 fw-bold mb-0 text-danger"><?= number_format($stats['pending_grades'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Average Grade</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['avg_grade'] ?? 0, 1) ?></div>
        </div>
    </div>
</div>

<div class="surface p-4 mb-3">
    <form method="get" class="row g-3">
        <div class="col-sm-6 col-md-3">
            <label class="form-label small text-muted">Student <span class="text-primary">*</span></label>
            <select class="form-select" name="student" id="studentFilter" required>
                <option value="">Select a student...</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= (int)$student['id'] ?>" <?= ((int)($filters['student_id'] ?? 0) === (int)$student['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($student['name'] ?? '') ?> 
                        <?php if (!empty($student['lrn'])): ?>
                            (<?= htmlspecialchars($student['lrn'] ?? '') ?>)
                        <?php endif; ?>
                        <?php if (!empty($student['section_name'])): ?>
                            - <?= htmlspecialchars($student['section_name']) ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">
                <small class="text-muted">Select a student to view their grades</small>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label small text-muted">Section</label>
            <select class="form-select" name="section">
                <option value="">All sections</option>
                <?php foreach ($sectionOptions as $option): ?>
                    <option value="<?= (int)$option['id'] ?>" <?= ((int)$filters['section_id'] === (int)$option['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($option['label'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label small text-muted">Subject</label>
            <select class="form-select" name="subject">
                <option value="">All subjects</option>
                <?php foreach ($subjectOptions as $option): ?>
                    <option value="<?= (int)$option['id'] ?>" <?= ((int)$filters['subject_id'] === (int)$option['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($option['label'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label small text-muted">Grade Type</label>
            <select class="form-select" name="grade_type">
                <option value="">All types</option>
                <option value="ww" <?= ($filters['grade_type'] ?? '') === 'ww' ? 'selected' : '' ?>>Written Work (WW)</option>
                <option value="pt" <?= ($filters['grade_type'] ?? '') === 'pt' ? 'selected' : '' ?>>Performance Task (PT)</option>
                <option value="qe" <?= ($filters['grade_type'] ?? '') === 'qe' ? 'selected' : '' ?>>Quarterly Exam (QE)</option>
            </select>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="<?= \Helpers\Url::to('/teacher/grades') ?>">Reset</a>
            <button class="btn btn-primary" type="submit">Filter</button>
        </div>
    </form>
</div>

<?php if ($selectedStudent): ?>
    <!-- Per-Student Grade View -->
    <div class="surface p-4 mb-3">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1"><?= htmlspecialchars($selectedStudent['name'] ?? '') ?></h2>
                <div class="text-muted small">
                    LRN: <?= htmlspecialchars($selectedStudent['lrn'] ?? 'N/A') ?> | 
                    Grade <?= htmlspecialchars((string)($selectedStudent['grade_level'] ?? '')) ?> | 
                    Section: <?= htmlspecialchars($selectedStudent['section_name'] ?? 'N/A') ?>
                </div>
            </div>
            <a href="<?= \Helpers\Url::to('/teacher/grades') ?>" class="btn btn-outline-secondary btn-sm">
                View All Students
            </a>
        </div>

        <?php if (empty($studentQuarterlyGrades)): ?>
            <div class="text-center py-5 text-muted">
                <p>No quarterly grades available for this student yet.</p>
                <p class="small">Grades will appear here once they are entered.</p>
            </div>
        <?php else: ?>
            <!-- Group grades by subject and quarter -->
            <?php
            $groupedGrades = [];
            foreach ($studentQuarterlyGrades as $grade) {
                $subjectId = $grade['subject_id'] ?? 0;
                $quarter = $grade['quarter'] ?? 0;
                if (!isset($groupedGrades[$subjectId])) {
                    $groupedGrades[$subjectId] = [];
                }
                $groupedGrades[$subjectId][$quarter] = $grade;
            }
            
            // Get subject names from quarterly grades
            $subjectNames = [];
            foreach ($studentQuarterlyGrades as $grade) {
                if (isset($grade['subject_id']) && isset($grade['subject_name'])) {
                    $subjectNames[$grade['subject_id']] = $grade['subject_name'];
                }
            }
            ?>

            <div class="row g-3">
                <?php foreach ($groupedGrades as $subjectId => $quarters): ?>
                    <div class="col-12">
                        <div class="border rounded-3 p-4">
                            <h5 class="fw-bold mb-3"><?= htmlspecialchars($subjectNames[$subjectId] ?? 'Subject #' . $subjectId) ?></h5>
                            
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Quarter</th>
                                            <th class="text-center">Written Work</th>
                                            <th class="text-center">Performance Task</th>
                                            <th class="text-center">Quarterly Exam</th>
                                            <th class="text-center">Attendance</th>
                                            <th class="text-center">Final Grade</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($q = 1; $q <= 4; $q++): ?>
                                            <?php $quarterGrade = $quarters[$q] ?? null; ?>
                                            <tr>
                                                <td class="fw-semibold">Q<?= $q ?></td>
                                                <td class="text-center">
                                                    <?php if ($quarterGrade && isset($quarterGrade['ww_average'])): ?>
                                                        <span><?= number_format($quarterGrade['ww_average'], 2) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($quarterGrade && isset($quarterGrade['pt_average'])): ?>
                                                        <span><?= number_format($quarterGrade['pt_average'], 2) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($quarterGrade && isset($quarterGrade['qe_average'])): ?>
                                                        <span><?= number_format($quarterGrade['qe_average'], 2) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($quarterGrade && isset($quarterGrade['attendance_average'])): ?>
                                                        <span><?= number_format($quarterGrade['attendance_average'], 2) ?>%</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($quarterGrade && isset($quarterGrade['final_grade'])): ?>
                                                        <span class="fw-bold <?= $quarterGrade['final_grade'] >= 75 ? 'text-success' : 'text-danger' ?>">
                                                            <?= number_format($quarterGrade['final_grade'], 2) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($quarterGrade && isset($quarterGrade['status'])): ?>
                                                        <span class="badge bg-<?= $quarterGrade['status'] === 'Passed' ? 'success' : 'danger' ?>">
                                                            <?= htmlspecialchars($quarterGrade['status']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Individual Grade Items Table -->
            <div class="mt-4">
                <h5 class="fw-bold mb-3">Individual Grade Items</h5>
    <?php if (empty($grades)): ?>
                    <div class="text-center py-3 text-muted">
                        <p class="small">No individual grade items recorded yet.</p>
                    </div>
    <?php else: ?>
        <div class="table-responsive">
                        <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Subject</th>
                        <th>Type</th>
                                    <th>Description</th>
                                    <th>Quarter</th>
                        <th class="text-end">Score</th>
                        <th class="text-end">%</th>
                                    <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $grade): ?>
                        <tr>
                            <td><?= htmlspecialchars($grade['subject_name'] ?? '') ?></td>
                            <td>
                                <?php 
                                            $typeLabels = ['ww' => 'WW', 'pt' => 'PT', 'qe' => 'QE'];
                                $type = $grade['grade_type'] ?? '';
                                echo htmlspecialchars($typeLabels[$type] ?? ucfirst($type));
                                ?>
                            </td>
                                        <td><?= htmlspecialchars($grade['description'] ?? '-') ?></td>
                                        <td>Q<?= htmlspecialchars((string)($grade['quarter'] ?? '')) ?></td>
                            <td class="text-end">
                                <?= number_format((float)($grade['grade_value'] ?? 0), 2) ?> / <?= number_format((float)($grade['max_score'] ?? 100), 2) ?>
                            </td>
                            <td class="text-end"><?= number_format((float)($grade['percentage'] ?? 0), 1) ?>%</td>
                                        <td>
                                            <?php if (!empty($grade['graded_at'])): ?>
                                                <?= date('M d, Y', strtotime($grade['graded_at'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- No Student Selected View -->
    <div class="surface p-5 text-center">
        <svg width="64" height="64" fill="currentColor" class="text-muted mb-3">
            <use href="#icon-user"></use>
        </svg>
        <h5 class="fw-bold mb-2">Select a Student to View Grades</h5>
        <p class="text-muted mb-0">Choose a student from the dropdown above to see their grade breakdown organized by subject and quarter.</p>
    </div>
<?php endif; ?>

<!-- Add Grade Modal -->
<div class="modal fade" id="addGradeModal" tabindex="-1" aria-labelledby="addGradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGradeModalLabel">Add New Grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addGradeForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="studentSelect" class="form-label">Student <span class="text-danger">*</span></label>
                            <select class="form-select" id="studentSelect" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php 
                                $students = $students ?? [];
                                if (empty($students)): ?>
                                    <option value="" disabled>No students found. Make sure students are assigned to your sections.</option>
                                <?php else:
                                    foreach ($students as $student): 
                                ?>
                                    <option value="<?= (int)$student['id'] ?>" data-section-id="<?= (int)($student['section_id'] ?? 0) ?>" data-section="<?= htmlspecialchars($student['section_name'] ?? '') ?>">
                                        <?= htmlspecialchars($student['name'] ?? '') ?> - <?= htmlspecialchars($student['section_name'] ?? '') ?>
                                        <?php if (!empty($student['lrn'])): ?>
                                            (LRN: <?= htmlspecialchars($student['lrn'] ?? '') ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php 
                                    endforeach;
                                endif; 
                                ?>
                            </select>
                            <?php if (empty($students)): ?>
                                <div class="form-text text-warning">
                                    <small>No students available. Students must be assigned to sections that you teach.</small>
                                </div>
                            <?php endif; ?>
                            <div class="invalid-feedback">Please select a student.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="subjectSelect" class="form-label">Subject <span class="text-danger">*</span></label>
                            <select class="form-select" id="subjectSelect" name="subject_id" required disabled>
                                <option value="">Select a student first</option>
                            </select>
                            <div class="form-text">
                                <small class="text-muted">Subjects will appear after you select a student.</small>
                            </div>
                            <div class="invalid-feedback">Please select a subject.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="gradeType" class="form-label">Grade Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="gradeType" name="grade_type" required>
                                <option value="">Select Type</option>
                                <option value="ww">Written Work (WW)</option>
                                <option value="pt">Performance Task (PT)</option>
                                <option value="qe">Quarterly Exam (QE)</option>
                            </select>
                            <div class="invalid-feedback">Please select a grade type.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="quarter" class="form-label">Quarter <span class="text-danger">*</span></label>
                            <select class="form-select" id="quarter" name="quarter" required>
                                <option value="">Select Quarter</option>
                                <option value="1">1st Quarter</option>
                                <option value="2">2nd Quarter</option>
                                <option value="3">3rd Quarter</option>
                                <option value="4">4th Quarter</option>
                            </select>
                            <div class="invalid-feedback">Please select a quarter.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="maxScore" class="form-label">Max Score</label>
                            <input type="number" class="form-control" id="maxScore" name="max_score" 
                                   min="0" step="0.01" value="100" placeholder="100.00">
                        </div>
                        <div class="col-md-6">
                            <label for="gradeValue" class="form-label">Grade Value <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="gradeValue" name="grade_value" 
                                   min="0" step="0.01" placeholder="0.00" required>
                            <div class="invalid-feedback">Please enter a valid grade.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   placeholder="e.g., Quiz #1, Assignment #2">
                        </div>
                        <div class="col-12">
                            <label for="remarks" class="form-label">Remarks (Optional)</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2" 
                                      placeholder="Additional notes about this grade..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" fill="currentColor" class="me-1">
                            <use href="#icon-plus"></use>
                        </svg>
                        Add Grade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit when student filter changes
    const studentFilter = document.getElementById('studentFilter');
    if (studentFilter) {
        studentFilter.addEventListener('change', function() {
            if (this.value) {
                // Build URL with student parameter
                const url = new URL(window.location.href);
                url.searchParams.set('student', this.value);
                // Preserve other filters
                const form = this.closest('form');
                if (form) {
                    const section = form.querySelector('[name="section"]')?.value;
                    const subject = form.querySelector('[name="subject"]')?.value;
                    const gradeType = form.querySelector('[name="grade_type"]')?.value;
                    if (section) url.searchParams.set('section', section);
                    if (subject) url.searchParams.set('subject', subject);
                    if (gradeType) url.searchParams.set('grade_type', gradeType);
                }
                window.location.href = url.toString();
            }
        });
    }
    
    const form = document.getElementById('addGradeForm');
    if (!form) return;
    
    const studentSelect = document.getElementById('studentSelect');
    const subjectSelect = document.getElementById('subjectSelect');
    
    // Load subjects when student is selected
    studentSelect.addEventListener('change', async function() {
        const selectedOption = this.options[this.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            // Reset subject dropdown
            subjectSelect.innerHTML = '<option value="">Select a student first</option>';
            subjectSelect.disabled = true;
            return;
        }
        
        const studentId = parseInt(selectedOption.value);
        const sectionId = selectedOption.getAttribute('data-section-id');
        
        if (!sectionId) {
            console.error('No section_id found for student');
            subjectSelect.innerHTML = '<option value="">Student has no section assigned</option>';
            subjectSelect.disabled = true;
            return;
        }
        
        // Show loading state
        subjectSelect.disabled = true;
        subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
        
        try {
            // Use section_id primarily, student_id as fallback
            const url = `<?= \Helpers\Url::to('/api/teacher/get-subjects.php') ?>?section_id=${sectionId}${studentId ? '&student_id=' + studentId : ''}`;
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success && result.data && result.data.length > 0) {
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                result.data.forEach(function(subject) {
                    const option = document.createElement('option');
                    option.value = subject.id;
                    option.textContent = subject.name;
                    subjectSelect.appendChild(option);
                });
                subjectSelect.disabled = false;
            } else {
                subjectSelect.innerHTML = '<option value="">No subjects found for this student</option>';
                subjectSelect.disabled = true;
            }
        } catch (error) {
            console.error('Error loading subjects:', error);
            subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
            subjectSelect.disabled = true;
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        
        // Get section_id from selected student
        const selectedOption = studentSelect.options[studentSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            alert('Please select a student');
            return;
        }
        
        if (!subjectSelect.value) {
            alert('Please select a subject');
            return;
        }
        
        const sectionId = selectedOption.getAttribute('data-section-id');
        
        const data = {
            student_id: parseInt(formData.get('student_id')),
            subject_id: parseInt(formData.get('subject_id')),
            section_id: sectionId ? parseInt(sectionId) : null,
            grade_type: formData.get('grade_type'),
            quarter: parseInt(formData.get('quarter')),
            grade_value: parseFloat(formData.get('grade_value')),
            max_score: parseFloat(formData.get('max_score')) || 100,
            description: formData.get('description') || null,
            remarks: formData.get('remarks') || null
        };

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';

        try {
            const response = await fetch('<?= \Helpers\Url::to('/api/teacher/submit-grade.php') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            // Restore button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;

            if (result.success) {
                // Check for AI anomaly detection warnings
                if (result.anomaly_detection && result.anomaly_detection.severity !== 'none') {
                    // Show anomaly warning modal
                    showAnomalyWarning(result.anomaly_detection);
                } else {
                    // Show success message
                    if (typeof showNotification === 'function') {
                        showNotification('Grade added successfully!', { type: 'success' });
                    } else {
                        alert('Grade added successfully!');
                    }
                }
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addGradeModal'));
                if (modal) modal.hide();
                
                // Reset form
                form.reset();
                
                // Reload page to show new grade
                setTimeout(() => {
                    window.location.reload();
                }, result.anomaly_detection ? 3000 : 1000); // Give more time if showing warning
            } else {
                throw new Error(result.message || 'Failed to add grade');
            }
        } catch (error) {
            console.error('Error:', error);
            
            // Restore button
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<svg width="16" height="16" fill="currentColor" class="me-1"><use href="#icon-plus"></use></svg>Add Grade';
            }
            
            if (typeof showNotification === 'function') {
                showNotification('Error: ' + error.message, { type: 'error' });
            } else {
                alert('Error: ' + error.message);
            }
        }
    });
    
    // Function to show AI anomaly detection warning
    function showAnomalyWarning(anomalyData) {
        const severity = anomalyData.severity || 'medium';
        const severityClass = severity === 'high' ? 'danger' : 'warning';
        const severityIcon = severity === 'high' ? '⚠️' : '⚡';
        
        let warningHtml = `
            <div class="alert alert-${severityClass} border-start border-4 mb-3">
                <div class="d-flex align-items-start">
                    <div class="me-3">
                        <div class="h4 mb-0">${severityIcon}</div>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading">AI Anomaly Detection Alert</h5>
                        <p class="mb-2"><strong>${anomalyData.message || 'Unusual patterns detected in this grade.'}</strong></p>
        `;
        
        if (anomalyData.anomalies && anomalyData.anomalies.length > 0) {
            warningHtml += '<div class="mb-2"><strong>Anomalies Detected:</strong><ul class="mb-0 small">';
            anomalyData.anomalies.forEach(anomaly => {
                warningHtml += `<li>${anomaly.description}</li>`;
            });
            warningHtml += '</ul></div>';
        }
        
        if (anomalyData.warnings && anomalyData.warnings.length > 0) {
            warningHtml += '<div class="mb-2"><strong>Warnings:</strong><ul class="mb-0 small">';
            anomalyData.warnings.forEach(warning => {
                warningHtml += `<li>${warning.description}</li>`;
            });
            warningHtml += '</ul></div>';
        }
        
        if (anomalyData.suggestions && anomalyData.suggestions.length > 0) {
            warningHtml += '<div class="mb-2"><strong>Suggestions:</strong><ul class="mb-0 small">';
            anomalyData.suggestions.forEach(suggestion => {
                warningHtml += `<li>${suggestion}</li>`;
            });
            warningHtml += '</ul></div>';
        }
        
        warningHtml += `
                        <div class="mt-3">
                            <button class="btn btn-sm btn-${severityClass}" onclick="this.closest('.alert').remove()">
                                Acknowledge & Continue
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Show notification with warning
        if (typeof showNotification === 'function') {
            showNotification('Grade added, but AI detected unusual patterns. Please review.', { 
                type: severity === 'high' ? 'error' : 'warning',
                duration: 5000
            });
        }
        
        // Insert warning at top of page
        const container = document.querySelector('.container-fluid, .container, main') || document.body;
        const warningDiv = document.createElement('div');
        warningDiv.innerHTML = warningHtml;
        container.insertBefore(warningDiv.firstElementChild, container.firstChild);
    }
});
</script>

