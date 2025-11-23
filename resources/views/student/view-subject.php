<?php
/**
 * Student: View Subject/Class Details
 * Displays comprehensive information about a specific subject
 */

$classInfo = $classInfo ?? [];
$gradesByQuarter = $gradesByQuarter ?? [];
$quarterSummaries = $quarterSummaries ?? [];
$schedules = $schedules ?? [];
$attendance = $attendance ?? [];
$attendancePercentage = $attendancePercentage ?? 0;
?>

<div class="container-fluid py-4">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="<?= \Helpers\Url::to('/student/classes') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to My Classes
        </a>
    </div>

    <!-- Subject Header Card -->
    <div class="card shadow-sm mb-4 border-top border-primary border-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-primary me-2" style="font-size: 1rem;">
                            <?= htmlspecialchars($classInfo['subject_code'] ?? 'N/A') ?>
                        </span>
                        <h2 class="mb-0"><?= htmlspecialchars($classInfo['subject_name'] ?? 'Subject') ?></h2>
                    </div>
                    <?php if (!empty($classInfo['subject_description'])): ?>
                        <p class="text-muted mb-2"><?= htmlspecialchars($classInfo['subject_description']) ?></p>
                    <?php endif; ?>
                    <div class="text-muted">
                        <span class="me-3"><i class="fas fa-users me-2"></i><?= htmlspecialchars($classInfo['section_name'] ?? 'N/A') ?></span>
                        <span class="me-3"><i class="fas fa-layer-group me-2"></i>Grade <?= htmlspecialchars($classInfo['grade_level'] ?? 'N/A') ?></span>
                        <span class="me-3"><i class="fas fa-calendar me-2"></i><?= htmlspecialchars($classInfo['school_year'] ?? 'N/A') ?> (<?= htmlspecialchars($classInfo['semester'] ?? 'N/A') ?>)</span>
                    </div>
                </div>
                <div class="col-auto">
                    <span class="badge bg-<?= ($classInfo['enrollment_status'] ?? 'enrolled') === 'enrolled' ? 'success' : 'secondary' ?> fs-5">
                        <?= htmlspecialchars(ucfirst($classInfo['enrollment_status'] ?? 'Enrolled')) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Teacher & Schedule Info -->
        <div class="col-lg-4">
            <!-- Teacher Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-circle-lg bg-primary text-white mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                            <?= strtoupper(substr($classInfo['teacher_name'] ?? 'T', 0, 1)) ?>
                        </div>
                        <h5 class="mb-1"><?= htmlspecialchars($classInfo['teacher_name'] ?? 'Not Assigned') ?></h5>
                        <?php if (!empty($classInfo['teacher_email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($classInfo['teacher_email']) ?>" class="text-muted">
                                <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($classInfo['teacher_email']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <hr>
                    <div class="d-grid">
                        <?php if (!empty($classInfo['teacher_email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($classInfo['teacher_email']) ?>" class="btn btn-primary">
                                <i class="fas fa-envelope me-2"></i>Email Teacher
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Class Schedule -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Class Schedule</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($schedules)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($schedules as $schedule): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="text-primary"><?= htmlspecialchars($schedule['day_of_week']) ?></strong>
                                        </div>
                                        <div class="text-muted">
                                            <?= date('g:i A', strtotime($schedule['start_time'])) ?> - 
                                            <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (!empty($classInfo['schedule'])): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <?= htmlspecialchars($classInfo['schedule']) ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No schedule set for this class.
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($classInfo['room'])): ?>
                        <div class="mt-3 text-center">
                            <i class="fas fa-door-open me-2 text-muted"></i>
                            <strong>Room:</strong> <?= htmlspecialchars($classInfo['room']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Attendance Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>My Attendance</h5>
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

            <!-- Grading System -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Grading System</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><strong>Written Work (WW):</strong></span>
                            <span class="badge bg-primary"><?= $classInfo['ww_percent'] ?? 20 ?>%</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><strong>Performance Task (PT):</strong></span>
                            <span class="badge bg-success"><?= $classInfo['pt_percent'] ?? 50 ?>%</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><strong>Quarterly Exam (QE):</strong></span>
                            <span class="badge bg-warning text-dark"><?= $classInfo['qe_percent'] ?? 20 ?>%</span>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-between">
                            <span><strong>Attendance:</strong></span>
                            <span class="badge bg-info"><?= $classInfo['attendance_percent'] ?? 10 ?>%</span>
                        </div>
                    </div>
                    <hr>
                    <small class="text-muted">Passing grade: 75%</small>
                </div>
            </div>
        </div>

        <!-- Right Column: Grades & Activities -->
        <div class="col-lg-8">
            <!-- Quarter Summaries -->
            <?php if (!empty($quarterSummaries)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Grade Summary by Quarter</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Quarter</th>
                                        <th class="text-center">WW Avg</th>
                                        <th class="text-center">PT Avg</th>
                                        <th class="text-center">QE Avg</th>
                                        <th class="text-center">Final Grade</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quarterSummaries as $quarter => $summary): ?>
                                        <tr>
                                            <td><strong>Quarter <?= $quarter ?></strong></td>
                                            <td class="text-center">
                                                <?= $summary['ww_count'] > 0 ? number_format($summary['ww_avg'], 2) : '-' ?>
                                                <br><small class="text-muted">(<?= $summary['ww_count'] ?>)</small>
                                            </td>
                                            <td class="text-center">
                                                <?= $summary['pt_count'] > 0 ? number_format($summary['pt_avg'], 2) : '-' ?>
                                                <br><small class="text-muted">(<?= $summary['pt_count'] ?>)</small>
                                            </td>
                                            <td class="text-center">
                                                <?= $summary['qe_count'] > 0 ? number_format($summary['qe_avg'], 2) : '-' ?>
                                                <br><small class="text-muted">(<?= $summary['qe_count'] ?>)</small>
                                            </td>
                                            <td class="text-center">
                                                <strong class="text-<?= $summary['final_grade'] >= 75 ? 'success' : 'danger' ?> fs-5">
                                                    <?= number_format($summary['final_grade'], 2) ?>
                                                </strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $summary['status'] === 'Passed' ? 'success' : 'danger' ?>">
                                                    <?= htmlspecialchars($summary['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Detailed Grades by Quarter -->
            <?php if (!empty($gradesByQuarter)): ?>
                <?php foreach ($gradesByQuarter as $quarter => $types): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Quarter <?= $quarter ?> - Detailed Grades</h5>
                        </div>
                        <div class="card-body">
                            <!-- Written Work -->
                            <?php if (!empty($types['WW'])): ?>
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-pen me-2"></i>Written Work (<?= count($types['WW']) ?> items)
                                </h6>
                                <div class="table-responsive mb-4">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Description</th>
                                                <th class="text-center">Score</th>
                                                <th class="text-center">Max</th>
                                                <th class="text-center">Percentage</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($types['WW'] as $grade): ?>
                                                <tr>
                                                    <td>
                                                        <?= htmlspecialchars($grade['description'] ?? 'Written Work') ?>
                                                        <?php if (!empty($grade['remarks'])): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($grade['remarks']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center"><strong><?= $grade['grade_value'] ?></strong></td>
                                                    <td class="text-center"><?= $grade['max_score'] ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-<?= $grade['percentage'] >= 75 ? 'success' : 'warning' ?>">
                                                            <?= number_format($grade['percentage'], 2) ?>%
                                                        </span>
                                                    </td>
                                                    <td><small><?= date('M d, Y', strtotime($grade['graded_at'] ?? $grade['created_at'])) ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <!-- Performance Tasks -->
                            <?php if (!empty($types['PT'])): ?>
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-tasks me-2"></i>Performance Tasks (<?= count($types['PT']) ?> items)
                                </h6>
                                <div class="table-responsive mb-4">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Description</th>
                                                <th class="text-center">Score</th>
                                                <th class="text-center">Max</th>
                                                <th class="text-center">Percentage</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($types['PT'] as $grade): ?>
                                                <tr>
                                                    <td>
                                                        <?= htmlspecialchars($grade['description'] ?? 'Performance Task') ?>
                                                        <?php if (!empty($grade['remarks'])): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($grade['remarks']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center"><strong><?= $grade['grade_value'] ?></strong></td>
                                                    <td class="text-center"><?= $grade['max_score'] ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-<?= $grade['percentage'] >= 75 ? 'success' : 'warning' ?>">
                                                            <?= number_format($grade['percentage'], 2) ?>%
                                                        </span>
                                                    </td>
                                                    <td><small><?= date('M d, Y', strtotime($grade['graded_at'] ?? $grade['created_at'])) ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <!-- Quarterly Exam -->
                            <?php if (!empty($types['QE'])): ?>
                                <h6 class="text-warning mb-3">
                                    <i class="fas fa-file-alt me-2"></i>Quarterly Exam (<?= count($types['QE']) ?> item)
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Description</th>
                                                <th class="text-center">Score</th>
                                                <th class="text-center">Max</th>
                                                <th class="text-center">Percentage</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($types['QE'] as $grade): ?>
                                                <tr>
                                                    <td>
                                                        <?= htmlspecialchars($grade['description'] ?? 'Quarterly Exam') ?>
                                                        <?php if (!empty($grade['remarks'])): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($grade['remarks']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center"><strong><?= $grade['grade_value'] ?></strong></td>
                                                    <td class="text-center"><?= $grade['max_score'] ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-<?= $grade['percentage'] >= 75 ? 'success' : 'warning' ?>">
                                                            <?= number_format($grade['percentage'], 2) ?>%
                                                        </span>
                                                    </td>
                                                    <td><small><?= date('M d, Y', strtotime($grade['graded_at'] ?? $grade['created_at'])) ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($types['WW']) && empty($types['PT']) && empty($types['QE'])): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No grades recorded yet for this quarter.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-clipboard fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No Grades Yet</h5>
                        <p class="text-muted">Your teacher hasn't posted any grades for this subject yet.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

