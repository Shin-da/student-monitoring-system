<?php
declare(strict_types=1);

$sections = $sections ?? [];
$stats = $stats ?? ['sections_count' => 0, 'students_count' => 0, 'subjects_count' => 0, 'alerts_count' => 0];
$activities = $activities ?? [];
$alerts = $alerts ?? [];

$advisorySections = array_filter($sections, static fn($section) => !empty($section['is_adviser']));
$teachingSections = array_filter($sections, static fn($section) => empty($section['is_adviser']));
?>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Teacher Dashboard</h1>
        <p class="text-muted mb-0">
            Hello, <span class="fw-semibold"><?= htmlspecialchars($user['name'] ?? 'Teacher') ?></span>. Here's an overview of your classes and students.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="<?= \Helpers\Url::to('/teacher/grades') ?>">
            <svg width="16" height="16" fill="currentColor" class="me-2">
                <use href="#icon-chart"></use>
            </svg>
            Manage Grades
        </a>
        <a class="btn btn-outline-secondary" href="<?= \Helpers\Url::to('/teacher/attendance') ?>">
            <svg width="16" height="16" fill="currentColor" class="me-2">
                <use href="#icon-calendar"></use>
            </svg>
            Attendance
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['label' => 'Sections', 'value' => $stats['sections_count'] ?? 0, 'variant' => 'primary', 'icon' => '#icon-sections'],
        ['label' => 'Students', 'value' => $stats['students_count'] ?? 0, 'variant' => 'success', 'icon' => '#icon-students'],
        ['label' => 'Subjects', 'value' => $stats['subjects_count'] ?? 0, 'variant' => 'info', 'icon' => '#icon-subjects'],
        ['label' => 'Active Alerts', 'value' => $stats['alerts_count'] ?? 0, 'variant' => 'warning', 'icon' => '#icon-alerts'],
    ];

    foreach ($statCards as $card):
    ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="surface p-4 h-100 border-start border-3 border-<?= $card['variant'] ?>">
                <div class="d-flex align-items-center">
                    <div class="me-3 text-<?= $card['variant'] ?>">
                        <svg width="28" height="28" fill="currentColor">
                            <use href="<?= $card['icon'] ?>"></use>
                        </svg>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase small"><?= htmlspecialchars($card['label']) ?></span>
                        <div class="h4 fw-bold mb-0"><?= number_format((int)$card['value']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="surface p-4 h-100 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-semibold mb-0">Advisory Section<?= count($advisorySections) !== 1 ? 's' : '' ?></h2>
            </div>
            <?php if (empty($advisorySections)): ?>
                <div class="text-muted small">No advisory sections assigned.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Subject</th>
                                <th>Schedule</th>
                                <th>Room</th>
                                <th class="text-end">Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($advisorySections as $section): ?>
                                <tr>
                                    <td><?= htmlspecialchars($section['section_name']) ?></td>
                                    <td><?= htmlspecialchars($section['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($section['schedule'] ?? 'TBA') ?></td>
                                    <td><?= htmlspecialchars($section['room'] ?? 'TBD') ?></td>
                                    <td class="text-end"><?= number_format($section['student_count'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="surface p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-semibold mb-0">Teaching Load</h2>
                <a class="btn btn-sm btn-outline-secondary" href="<?= \Helpers\Url::to('/teacher/sections') ?>">View all sections</a>
            </div>
            <?php if (empty($teachingSections)): ?>
                <div class="text-muted small">No assigned classes yet.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($teachingSections as $section): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($section['section_name']) ?></div>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars($section['subject_name']) ?> • <?= htmlspecialchars($section['schedule'] ?? 'TBA') ?>
                                    </div>
                                </div>
                                <div class="text-muted small text-end">
                                    <?= number_format($section['student_count'] ?? 0) ?> students
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="surface p-4 mb-3">
            <h2 class="h6 fw-semibold mb-3">Recent Activity</h2>
            <?php if (empty($activities)): ?>
                <div class="text-muted small">No activity recorded yet.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($activities as $activity): ?>
                        <li class="mb-3">
                            <div class="fw-semibold text-primary"><?= htmlspecialchars($activity['activity_type'] ?? 'Activity') ?></div>
                            <div><?= htmlspecialchars($activity['description'] ?? 'No description') ?></div>
                            <div class="text-muted">
                                <?= isset($activity['created_at']) ? date('M d, Y g:i A', strtotime($activity['created_at'])) : 'Unknown time' ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="surface p-4">
            <h2 class="h6 fw-semibold mb-3">Alerts</h2>
            <?php if (empty($alerts)): ?>
                <div class="text-muted small">No active alerts.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($alerts as $alert): ?>
                        <li class="mb-3">
                            <div class="fw-semibold text-warning"><?= htmlspecialchars($alert['title'] ?? 'Alert') ?></div>
                            <div><?= htmlspecialchars($alert['description'] ?? '') ?></div>
                            <div class="text-muted">
                                <?= htmlspecialchars($alert['student_name'] ?? 'Student') ?> • <?= htmlspecialchars($alert['section_name'] ?? 'Section') ?>
                            </div>
                            <div class="text-muted">
                                <?= isset($alert['created_at']) ? date('M d, Y g:i A', strtotime($alert['created_at'])) : 'Unknown time' ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

