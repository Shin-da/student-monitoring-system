<?php
declare(strict_types=1);

$hasSection = $hasSection ?? $has_section ?? false;
$attendanceRecords = $attendanceRecords ?? $attendance_records ?? [];
$summary = $summary ?? [];
$attendanceStats = $attendance_stats ?? [
    'total_days' => 0,
    'present' => 0,
    'late' => 0,
    'excused' => 0,
    'absent' => 0,
    'attendance_rate' => 0
];
$subjects = $subjects ?? [];
$filters = $filters ?? ['subject_id' => null, 'quarter' => null, 'date_from' => null, 'date_to' => null];
$studentId = $student_id ?? 0;
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">My Attendance</h1>
        <p class="text-muted mb-0">View your attendance records and track your participation.</p>
    </div>
    <?php if ($attendanceStats['attendance_rate'] > 0): ?>
        <div class="text-end">
            <div class="text-muted small">Overall Attendance Rate</div>
            <div class="h4 fw-bold mb-0 <?= $attendanceStats['attendance_rate'] >= 90 ? 'text-success' : ($attendanceStats['attendance_rate'] >= 75 ? 'text-warning' : 'text-danger') ?>">
                <?= number_format($attendanceStats['attendance_rate'], 1) ?>%
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!$hasSection): ?>
    <div class="surface p-5 text-center">
        <svg width="64" height="64" fill="currentColor" class="text-muted mb-3">
            <use href="#icon-user"></use>
        </svg>
        <h4 class="text-muted mb-2">You are not yet assigned to any section.</h4>
        <p class="text-muted mb-0">Please wait for enrollment.</p>
    </div>
<?php elseif (empty($attendanceRecords)): ?>
    <div class="surface p-5 text-center">
        <svg width="64" height="64" fill="currentColor" class="text-muted mb-3">
            <use href="#icon-calendar"></use>
        </svg>
        <h4 class="text-muted mb-2">No attendance records yet.</h4>
        <p class="text-muted mb-0">Your attendance will appear here once your teacher starts marking it.</p>
    </div>
<?php else: ?>

    <!-- Attendance Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="surface p-3 h-100 text-center border-start border-3 border-success">
                <div class="text-muted text-uppercase small">Present</div>
                <div class="h4 fw-bold mb-0 text-success"><?= number_format($attendanceStats['present'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="surface p-3 h-100 text-center border-start border-3 border-warning">
                <div class="text-muted text-uppercase small">Late</div>
                <div class="h4 fw-bold mb-0 text-warning"><?= number_format($attendanceStats['late'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="surface p-3 h-100 text-center border-start border-3 border-info">
                <div class="text-muted text-uppercase small">Excused</div>
                <div class="h4 fw-bold mb-0 text-info"><?= number_format($attendanceStats['excused'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="surface p-3 h-100 text-center border-start border-3 border-danger">
                <div class="text-muted text-uppercase small">Absent</div>
                <div class="h4 fw-bold mb-0 text-danger"><?= number_format($attendanceStats['absent'] ?? 0) ?></div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="surface p-4 mb-3">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted">Subject</label>
                <select class="form-select" name="subject">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= (int)$subject['id'] ?>" <?= ((int)($filters['subject_id'] ?? 0) === (int)$subject['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Date From</label>
                <input class="form-control" type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Date To</label>
                <input class="form-control" type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="<?= \Helpers\Url::to('/student/attendance') ?>">Reset</a>
                    <button class="btn btn-primary" type="submit">Apply</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Attendance Records -->
    <?php if (empty($attendanceRecords)): ?>
        <div class="surface p-4 text-center text-muted">
            <svg width="48" height="48" fill="currentColor" class="mb-2 opacity-50">
                <use href="#icon-calendar"></use>
            </svg>
            <div>No attendance records found.</div>
            <?php if (!empty($filters['subject_id']) || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                <small class="d-block mt-2">Try adjusting your filters.</small>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="surface p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Section</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceRecords as $record): ?>
                            <?php
                            $status = $record['status'] ?? 'absent';
                            $badgeMap = [
                                'present' => 'success',
                                'late' => 'warning',
                                'excused' => 'info',
                                'absent' => 'danger',
                            ];
                            $badgeClass = $badgeMap[$status] ?? 'secondary';
                            $date = new DateTime($record['attendance_date']);
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= $date->format('M d, Y') ?></div>
                                    <small class="text-muted"><?= $date->format('l') ?></small>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($record['subject_name'] ?? 'N/A') ?></div>
                                    <?php if (!empty($record['subject_code'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($record['subject_code']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($record['section_name'] ?? 'N/A') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if (count($attendanceRecords) >= 500): ?>
            <div class="alert alert-info mt-3">
                <small>Showing the 500 most recent records. Use filters to narrow down your search.</small>
            </div>
        <?php endif; ?>
    <?php endif; ?>

<?php endif; ?>

