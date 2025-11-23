<?php
declare(strict_types=1);

$child = $child_info ?? null;
$relationship = $parent_relationship ?? 'guardian';
$recentActivities = $recent_activities ?? [];
$upcomingEvents = $upcoming_events ?? [];
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Parent Portal</h1>
        <p class="text-muted mb-0">Stay informed about your child's progress and upcoming school events.</p>
    </div>
</div>

<div class="surface p-4 mb-3">
    <?php if (!$child): ?>
        <div class="text-muted">Your account is not yet linked to a student. Please contact the school registrar for assistance.</div>
    <?php else: ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h2 class="h5 fw-semibold mb-1"><?= htmlspecialchars($child['student_name'] ?? 'Student') ?></h2>
                <div class="text-muted small">LRN: <?= htmlspecialchars($child['lrn'] ?? '') ?></div>
                <div class="text-muted small">Grade <?= htmlspecialchars((string)($child['grade_level'] ?? '')) ?> • Section <?= htmlspecialchars($child['section_name'] ?? 'Unassigned') ?></div>
            </div>
            <span class="badge bg-light text-muted">Relationship: <?= htmlspecialchars(ucfirst($relationship)) ?></span>
        </div>
    <?php endif; ?>
</div>

<?php if ($child): ?>
    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="surface p-4 h-100">
                <h2 class="h6 fw-semibold mb-3">Recent Updates</h2>
                <?php if (empty($recentActivities)): ?>
                    <div class="text-muted small">No alerts or updates recorded.</div>
                <?php else: ?>
                    <ul class="list-unstyled mb-0 small">
                        <?php foreach ($recentActivities as $activity): ?>
                            <li class="mb-2">
                                <div><?= htmlspecialchars($activity['description'] ?? 'Update') ?></div>
                                <div class="text-muted">
                                    <?= isset($activity['created_at']) ? date('M d, Y g:i A', strtotime($activity['created_at'])) : 'Unknown time' ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="surface p-4 h-100">
                <h2 class="h6 fw-semibold mb-3">Upcoming Deadlines</h2>
                <?php if (empty($upcomingEvents)): ?>
                    <div class="text-muted small">No scheduled assignments or events.</div>
                <?php else: ?>
                    <ul class="list-unstyled mb-0 small">
                        <?php foreach ($upcomingEvents as $event): ?>
                            <li class="mb-2">
                                <div class="fw-semibold"><?= htmlspecialchars($event['title'] ?? 'Event') ?></div>
                                <div class="text-muted">Due <?= htmlspecialchars($event['due_date'] ?? 'TBA') ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

