<?php
declare(strict_types=1);

$students = $students ?? [];
$stats = $statistics ?? ['total_students' => 0, 'grade_levels' => 0, 'sections' => 0, 'grade_levels_list' => [], 'sections_list' => [], 'active_section_filter' => null, 'search_term' => ''];
$activeSection = $stats['active_section_filter'] ?? null;
$sectionOptions = $stats['sections_list'] ?? [];
$searchTerm = $stats['search_term'] ?? '';
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">My Students</h1>
        <p class="text-muted mb-0">These learners are enrolled in your classes. Filter by section to focus on a single group.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= \Helpers\Url::to('/teacher/add-students') ?>">
        <svg width="16" height="16" fill="currentColor" class="me-2">
            <use href="#icon-plus"></use>
        </svg>
        Add Students to a Section
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Students</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['total_students'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Grade Levels</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['grade_levels'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Sections</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['sections'] ?? 0) ?></div>
        </div>
    </div>
</div>

<div class="surface p-4 mb-3">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-sm-6 col-md-4">
            <label class="form-label small text-muted" for="section_filter">Section</label>
            <select class="form-select" id="section_filter" name="section">
                <option value="">All sections</option>
                <?php foreach ($sectionOptions as $sectionId => $sectionName): ?>
                    <option value="<?= htmlspecialchars((string)$sectionId) ?>" <?= ($activeSection && (string)$sectionId === (string)$activeSection) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sectionName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-4">
            <label class="form-label small text-muted" for="search">Search</label>
            <input class="form-control" id="search" name="q" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search by name or LRN">
        </div>
        <div class="col-sm-12 col-md-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn btn-outline-secondary" href="<?= \Helpers\Url::to('/teacher/students') ?>">Reset</a>
        </div>
    </form>
</div>

<?php if (empty($students)): ?>
    <div class="surface p-4 text-center text-muted">
        No students found for the selected filters.
    </div>
<?php else: ?>
    <div class="surface p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>LRN</th>
                        <th>Grade</th>
                        <th>Section</th>
                        <th>Subjects</th>
                        <th>Schedules</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['full_name'] ?? '') ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($student['lrn'] ?? '') ?></span></td>
                            <td>Grade <?= htmlspecialchars((string)($student['grade_level'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($student['section_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($student['subjects'] ?? '') ?></td>
                            <td><?= htmlspecialchars($student['schedules'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

