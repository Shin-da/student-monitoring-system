<?php
declare(strict_types=1);

$sections = $sections ?? [];
$students = $students ?? [];
$classStats = $class_stats ?? ['sections' => 0, 'students' => 0];
$recentActivities = $recent_activities ?? [];
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Class Adviser</h1>
        <p class="text-muted mb-0">Monitor sections under your supervision and keep track of advisory students.</p>
    </div>
    <span class="badge bg-light text-muted">Sections: <?= number_format($classStats['sections'] ?? 0) ?> • Students: <?= number_format($classStats['students'] ?? 0) ?></span>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
        <div class="surface p-4 h-100">
            <h2 class="h6 fw-semibold mb-3">Assigned Sections</h2>
            <?php if (empty($sections)): ?>
                <div class="text-muted small">No advisory sections assigned.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($sections as $section): ?>
                        <li class="mb-3">
                            <div class="fw-semibold"><?= htmlspecialchars($section['name']) ?> (Grade <?= htmlspecialchars((string)($section['grade_level'] ?? '')) ?>)</div>
                            <div class="text-muted">Room <?= htmlspecialchars($section['room'] ?? 'TBD') ?> • <?= number_format($section['student_count'] ?? 0) ?> students</div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="surface p-4 h-100">
            <h2 class="h6 fw-semibold mb-3">Recent Activity</h2>
            <?php if (empty($recentActivities)): ?>
                <div class="text-muted small">No recent activity recorded.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($recentActivities as $activity): ?>
                        <li class="mb-3">
                            <div class="fw-semibold text-primary"><?= htmlspecialchars($activity['action'] ?? 'Action') ?></div>
                            <div class="text-muted">
                                <?= htmlspecialchars($activity['target_type'] ?? '') ?> #<?= htmlspecialchars((string)($activity['target_id'] ?? '')) ?>
                            </div>
                            <div class="text-muted">
                                <?= isset($activity['created_at']) ? date('M d, Y g:i A', strtotime($activity['created_at'])) : 'Unknown time' ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="surface p-4">
    <h2 class="h6 fw-semibold mb-3">Students</h2>
    <?php if (empty($students)): ?>
        <div class="text-muted small">No students linked to your advisory sections.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>LRN</th>
                        <th>Section</th>
                        <th class="text-end">Grade Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php
                        $sectionName = '';
                        foreach ($sections as $section) {
                            if ((int)$section['id'] === (int)$student['section_id']) {
                                $sectionName = $section['name'];
                                break;
                            }
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($student['student_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($student['lrn'] ?? '') ?></td>
                            <td><?= htmlspecialchars($sectionName) ?></td>
                            <td class="text-end">Grade <?= htmlspecialchars((string)($student['grade_level'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

