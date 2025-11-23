<?php
declare(strict_types=1);

$sections = $sections ?? [];
$statistics = $statistics ?? ['sections' => 0, 'students' => 0, 'subjects' => 0, 'advisory_sections' => 0];
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">My Sections</h1>
        <p class="text-muted mb-0">Overview of your advisory and teaching assignments.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= \Helpers\Url::to('/teacher/assignments') ?>">
        <svg width="16" height="16" fill="currentColor" class="me-2">
            <use href="#icon-plus"></use>
        </svg>
        Create Assignment
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="surface p-4 h-100 text-center">
            <div class="text-muted small text-uppercase">Sections</div>
            <div class="display-6 fw-bold"><?= number_format($statistics['sections'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="surface p-4 h-100 text-center">
            <div class="text-muted small text-uppercase">Students</div>
            <div class="display-6 fw-bold"><?= number_format($statistics['students'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="surface p-4 h-100 text-center">
            <div class="text-muted small text-uppercase">Subjects</div>
            <div class="display-6 fw-bold"><?= number_format($statistics['subjects'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="surface p-4 h-100 text-center">
            <div class="text-muted small text-uppercase">Advisory</div>
            <div class="display-6 fw-bold"><?= number_format($statistics['advisory_sections'] ?? 0) ?></div>
        </div>
    </div>
</div>

<?php if (empty($sections)): ?>
    <div class="surface p-4 text-center text-muted">No sections assigned yet.</div>
<?php else: ?>
    <div class="surface p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Section</th>
                        <th>Subject</th>
                        <th>Schedule</th>
                        <th>Room</th>
                        <th class="text-end">Students</th>
                        <th class="text-end">Attendance Records</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $section): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($section['section_name']) ?>
                                    <?php if (!empty($section['is_adviser'])): ?>
                                        <span class="badge bg-success-subtle text-success ms-2">Adviser</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small">Grade <?= htmlspecialchars((string)($section['grade_level'] ?? '')) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($section['subject_name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($section['subject_code'] ?? '') ?></div>
                            </td>
                            <td><?= htmlspecialchars($section['schedule'] ?? 'TBA') ?></td>
                            <td><?= htmlspecialchars($section['room'] ?? 'TBD') ?></td>
                            <td class="text-end"><?= number_format($section['student_count'] ?? 0) ?></td>
                            <td class="text-end"><?= number_format($section['attendance_records'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

