<?php
declare(strict_types=1);

$assignments = $assignments ?? [];
$sections = $sections ?? [];
$subjects = $subjects ?? [];
$stats = $stats ?? ['total_assignments' => 0, 'active_assignments' => 0, 'overdue_assignments' => 0, 'avg_completion' => 0];
$filters = $filters ?? ['section_id' => null, 'subject_id' => null, 'status' => null];

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
        <h1 class="h3 fw-bold mb-1">Assignment Management</h1>
        <p class="text-muted mb-0">Track assignments across your classes and monitor completion.</p>
    </div>
    <a class="btn btn-primary" href="#" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
        <svg width="16" height="16" fill="currentColor" class="me-2">
            <use href="#icon-plus"></use>
        </svg>
        New Assignment
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Total</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['total_assignments'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Active</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['active_assignments'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Overdue</div>
            <div class="h4 fw-bold mb-0 text-danger"><?= number_format($stats['overdue_assignments'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Avg. Completion</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['avg_completion'] ?? 0, 1) ?>%</div>
        </div>
    </div>
</div>

<div class="surface p-4 mb-3">
    <form method="get" class="row g-3">
        <div class="col-sm-4">
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
        <div class="col-sm-4">
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
        <div class="col-sm-4">
            <label class="form-label small text-muted">Status</label>
            <select class="form-select" name="status">
                <option value="">Any status</option>
                <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
            </select>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="<?= \Helpers\Url::to('/teacher/assignments') ?>">Reset</a>
            <button class="btn btn-primary" type="submit">Filter</button>
        </div>
    </form>
</div>

<?php if (empty($assignments)): ?>
    <div class="surface p-4 text-center text-muted">No assignments found for the selected criteria.</div>
<?php else: ?>
    <div class="surface p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Assignment</th>
                        <th>Section</th>
                        <th>Subject</th>
                        <th>Due Date</th>
                        <th class="text-end">Completion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($assignment['title'] ?? 'Untitled') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($assignment['assignment_type'] ?? '') ?></div>
                            </td>
                            <td><?= htmlspecialchars($assignment['class_name'] ?? '') ?> <?= htmlspecialchars($assignment['section'] ?? '') ?></td>
                            <td><?= htmlspecialchars($assignment['subject_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($assignment['due_date'] ?? 'TBA') ?></td>
                            <td class="text-end">
                                <?= number_format($assignment['completion_percentage'] ?? 0, 1) ?>%
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar" style="width: <?= min(100, (float)($assignment['completion_percentage'] ?? 0)) ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Placeholder modal for future assignment creation -->
<div class="modal fade" id="createAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-muted">
                Assignment creation workflow is not yet implemented.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

