<?php
declare(strict_types=1);

$roleCounts = [
    'student' => 0,
    'teacher' => 0,
    'adviser' => 0,
    'parent' => 0,
    'admin' => 0,
];

foreach (($userStats ?? []) as $stat) {
    $role = $stat['role'] ?? null;
    if ($role !== null && array_key_exists($role, $roleCounts)) {
        $roleCounts[$role] = (int)($stat['count'] ?? 0);
    }
}

$totalUsers = array_sum($roleCounts);

$sectionsCount = (int)($systemStats['sections'] ?? 0);
$classesCount = (int)($systemStats['classes'] ?? 0);
$subjectsCount = (int)($systemStats['subjects'] ?? 0);
$unassignedStudents = (int)($systemStats['unassigned_students'] ?? 0);
?>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Admin Dashboard</h1>
        <p class="text-muted mb-0">
            Welcome back, <span class="fw-semibold"><?= htmlspecialchars($user['name'] ?? 'Administrator') ?></span>.
            Here's what's happening across the school today.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="<?= \Helpers\Url::to('/admin/create-student') ?>">
            <svg width="16" height="16" fill="currentColor" class="me-2">
                <use href="#icon-plus"></use>
            </svg>
            Add Student
        </a>
        <a class="btn btn-outline-secondary" href="<?= \Helpers\Url::to('/admin/sections') ?>">
            <svg width="16" height="16" fill="currentColor" class="me-2">
                <use href="#icon-sections-admin"></use>
            </svg>
            Manage Sections
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['label' => 'Students', 'count' => $roleCounts['student'], 'icon' => '#icon-students', 'variant' => 'primary'],
        ['label' => 'Teachers', 'count' => $roleCounts['teacher'], 'icon' => '#icon-teachers', 'variant' => 'success'],
        ['label' => 'Parents', 'count' => $roleCounts['parent'], 'icon' => '#icon-user', 'variant' => 'info'],
        ['label' => 'Pending Approvals', 'count' => (int)($pendingCount ?? 0), 'icon' => '#icon-alerts', 'variant' => 'warning'],
    ];
    foreach ($statCards as $card):
    ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="surface p-4 h-100 border-start border-3 border-<?= $card['variant'] ?>">
                <div class="d-flex align-items-center mb-2">
                    <div class="me-3 text-<?= $card['variant'] ?>">
                        <svg width="28" height="28" fill="currentColor">
                            <use href="<?= $card['icon'] ?>"></use>
                        </svg>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase small"><?= htmlspecialchars($card['label']) ?></span>
                        <div class="h4 fw-bold mb-0"><?= number_format($card['count']) ?></div>
                    </div>
                </div>
                <?php if ($card['label'] === 'Pending Approvals' && $card['count'] > 0): ?>
                    <a href="<?= \Helpers\Url::to('/admin/users') ?>" class="link-<?= $card['variant'] ?> small">Review requests</a>
                <?php else: ?>
                    <span class="small text-muted">Updated in real time</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <div class="surface p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-semibold mb-0">Recent Activity</h2>
                <a class="btn btn-sm btn-outline-secondary" href="<?= \Helpers\Url::to('/admin/logs') ?>">View logs</a>
            </div>
            <?php if (!empty($recentActivity)): ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($recentActivity as $entry): ?>
                        <li class="d-flex align-items-start gap-3 py-3 border-bottom">
                            <div class="text-primary pt-1">
                                <svg width="20" height="20" fill="currentColor">
                                    <use href="#icon-report"></use>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($entry['actor_name'] ?? 'System') ?>
                                    <span class="text-muted">performed</span>
                                    <span class="text-primary"><?= htmlspecialchars($entry['action'] ?? 'an action') ?></span>
                                </div>
                                <?php if (!empty($entry['details'])): ?>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars(json_encode($entry['details'])) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="text-muted small">
                                    <?= $entry['created_at'] ? date('M d, Y g:i A', strtotime($entry['created_at'])) : 'Timestamp unavailable' ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="text-muted small">No audit activity has been recorded yet.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="surface p-4 h-100">
            <h2 class="h5 fw-semibold mb-3">At a Glance</h2>
            <dl class="row mb-0">
                <dt class="col-6 text-muted">Total Users</dt>
                <dd class="col-6 text-end fw-semibold"><?= number_format($totalUsers) ?></dd>

                <dt class="col-8 text-muted">Active Sections</dt>
                <dd class="col-4 text-end fw-semibold"><?= number_format($sectionsCount) ?></dd>

                <dt class="col-8 text-muted">Active Classes</dt>
                <dd class="col-4 text-end fw-semibold"><?= number_format($classesCount) ?></dd>

                <dt class="col-8 text-muted">Active Subjects</dt>
                <dd class="col-4 text-end fw-semibold"><?= number_format($subjectsCount) ?></dd>

                <dt class="col-8 text-muted">Students Without Section</dt>
                <dd class="col-4 text-end fw-semibold"><?= number_format($unassignedStudents) ?></dd>
            </dl>
            <?php if ($unassignedStudents > 0): ?>
                <a class="btn btn-warning btn-sm w-100 mt-3" href="<?= \Helpers\Url::to('/admin/sections') ?>">
                    Assign unplaced students
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="surface p-4">
    <h2 class="h5 fw-semibold mb-3">Quick Actions</h2>
    <div class="row g-3">
        <div class="col-12 col-md-6 col-lg-3">
            <a class="btn btn-outline-primary w-100" href="<?= \Helpers\Url::to('/admin/users') ?>">
                Manage Users
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <a class="btn btn-outline-success w-100" href="<?= \Helpers\Url::to('/admin/create-student') ?>">
                Register Student
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <a class="btn btn-outline-info w-100" href="<?= \Helpers\Url::to('/admin/classes') ?>">
                Build Class
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <a class="btn btn-outline-secondary w-100" href="<?= \Helpers\Url::to('/admin/reports') ?>">
                Reports &amp; Analytics
            </a>
        </div>
    </div>
</div>

