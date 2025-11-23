<?php
/**
 * Teacher: View Student Profile
 * Displays detailed student information, grades, and performance
 */

$student = $student ?? [];
$enrolledClasses = $enrolledClasses ?? [];
$grades = $grades ?? [];
$attendance = $attendance ?? [];
$attendancePercentage = $attendancePercentage ?? 0;

$fullName = trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
?>

<div class="container-fluid py-4">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="<?= url('/teacher/students') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Students
        </a>
    </div>

    <!-- Student Header Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar-circle-lg">
                        <?php if (!empty($student['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($student['profile_picture']) ?>" 
                                 alt="<?= htmlspecialchars($fullName) ?>" 
                                 class="rounded-circle" 
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar-circle-lg bg-primary text-white d-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px; font-size: 2rem;">
                                <?= strtoupper(substr($student['first_name'] ?? 'S', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col">
                    <h2 class="mb-1"><?= htmlspecialchars($fullName) ?></h2>
                    <div class="text-muted">
                        <span class="me-3"><i class="fas fa-id-card me-2"></i>LRN: <?= htmlspecialchars($student['lrn'] ?? 'N/A') ?></span>
                        <span class="me-3"><i class="fas fa-school me-2"></i><?= htmlspecialchars($student['section_name'] ?? 'No Section') ?></span>
                        <span class="me-3"><i class="fas fa-layer-group me-2"></i>Grade <?= htmlspecialchars($student['grade_level'] ?? 'N/A') ?></span>
                    </div>
                </div>
                <div class="col-auto">
                    <span class="badge bg-<?= ($student['enrollment_status'] ?? 'enrolled') === 'enrolled' ? 'success' : 'secondary' ?> fs-6">
                        <?= htmlspecialchars(ucfirst($student['enrollment_status'] ?? 'Enrolled')) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Student Information -->
        <div class="col-lg-4">
            <!-- Personal Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Email</label>
                        <div><?= htmlspecialchars($student['email'] ?? 'N/A') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Birth Date</label>
                        <div><?= !empty($student['birth_date']) ? date('F j, Y', strtotime($student['birth_date'])) : 'N/A' ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Gender</label>
                        <div><?= htmlspecialchars(ucfirst($student['gender'] ?? 'N/A')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Contact Number</label>
                        <div><?= htmlspecialchars($student['contact_number'] ?? 'N/A') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Address</label>
                        <div><?= htmlspecialchars($student['address'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Section</label>
                        <div><?= htmlspecialchars($student['section_name'] ?? 'N/A') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Grade Level</label>
                        <div>Grade <?= htmlspecialchars($student['grade_level'] ?? 'N/A') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">School Year</label>
                        <div><?= htmlspecialchars($student['school_year'] ?? 'N/A') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Adviser</label>
                        <div><?= htmlspecialchars($student['adviser_name'] ?? 'N/A') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Date Enrolled</label>
                        <div><?= !empty($student['date_enrolled']) ? date('F j, Y', strtotime($student['date_enrolled'])) : 'N/A' ?></div>
                    </div>
                </div>
            </div>

            <!-- Attendance Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Attendance Summary</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="display-4 text-<?= $attendancePercentage >= 90 ? 'success' : ($attendancePercentage >= 75 ? 'warning' : 'danger') ?>">
                            <?= number_format($attendancePercentage, 1) ?>%
                        </div>
                        <div class="text-muted">Attendance Rate</div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="h4 text-success"><?= $attendance['present_days'] ?? 0 ?></div>
                            <div class="small text-muted">Present</div>
                        </div>
                        <div class="col-4">
                            <div class="h4 text-danger"><?= $attendance['absent_days'] ?? 0 ?></div>
                            <div class="small text-muted">Absent</div>
                        </div>
                        <div class="col-4">
                            <div class="h4 text-warning"><?= $attendance['late_days'] ?? 0 ?></div>
                            <div class="small text-muted">Late</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Grades and Classes -->
        <div class="col-lg-8">
            <!-- Enrolled Classes -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-book me-2"></i>Enrolled Classes</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($enrolledClasses)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Section</th>
                                        <th>Teacher</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrolledClasses as $class): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($class['subject_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($class['subject_code']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($class['section_name']) ?></td>
                                            <td><?= htmlspecialchars($class['teacher_name'] ?? 'N/A') ?></td>
                                            <td><small><?= htmlspecialchars($class['schedule'] ?? 'N/A') ?></small></td>
                                            <td>
                                                <span class="badge bg-<?= $class['enrollment_status'] === 'enrolled' ? 'success' : 'secondary' ?>">
                                                    <?= htmlspecialchars(ucfirst($class['enrollment_status'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This student is not currently enrolled in any classes.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Grades -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Academic Performance</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($grades)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Quarter</th>
                                        <th>SY</th>
                                        <th class="text-center">WW Avg</th>
                                        <th class="text-center">PT Avg</th>
                                        <th class="text-center">QE Avg</th>
                                        <th class="text-center">Final Grade</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grades as $grade): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($grade['subject_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($grade['subject_code']) ?></small>
                                            </td>
                                            <td>Q<?= htmlspecialchars($grade['quarter']) ?></td>
                                            <td><small><?= htmlspecialchars($grade['academic_year']) ?></small></td>
                                            <td class="text-center">
                                                <?= $grade['ww_count'] > 0 ? number_format($grade['ww_avg'], 2) : '-' ?>
                                                <br><small class="text-muted">(<?= $grade['ww_count'] ?>)</small>
                                            </td>
                                            <td class="text-center">
                                                <?= $grade['pt_count'] > 0 ? number_format($grade['pt_avg'], 2) : '-' ?>
                                                <br><small class="text-muted">(<?= $grade['pt_count'] ?>)</small>
                                            </td>
                                            <td class="text-center">
                                                <?= $grade['qe_count'] > 0 ? number_format($grade['qe_avg'], 2) : '-' ?>
                                                <br><small class="text-muted">(<?= $grade['qe_count'] ?>)</small>
                                            </td>
                                            <td class="text-center">
                                                <strong class="text-<?= $grade['final_grade'] >= 75 ? 'success' : 'danger' ?>">
                                                    <?= number_format($grade['final_grade'], 2) ?>
                                                </strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $grade['status'] === 'Passed' ? 'success' : 'danger' ?>">
                                                    <?= htmlspecialchars($grade['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3">
                            <small>
                                <strong>Grading System:</strong> WW (Written Work) = 20%, PT (Performance Task) = 50%, QE (Quarterly Exam) = 20%, Attendance = 10%
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No grades recorded yet for this student.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

