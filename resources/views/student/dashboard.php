<?php
declare(strict_types=1);

$studentInfo = $student_info ?? [];
$academicStats = $academic_stats ?? ['overall_average' => 0, 'subjects_count' => 0];
$recentGrades = $recent_grades ?? [];
$upcomingAssignments = $upcoming_assignments ?? [];
$hasSection = $has_section ?? false;
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1">Student Dashboard</h1>
        <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($user['name'] ?? 'Student') ?>.</p>
        </div>
    <?php if (!empty($studentInfo['school_year'])): ?>
        <span class="badge bg-light text-muted">School Year <?= htmlspecialchars($studentInfo['school_year']) ?></span>
    <?php endif; ?>
</div>

<?php if (!$hasSection): ?>
<!-- Empty State: No Section Assigned -->
<div class="surface p-5 text-center">
    <svg width="64" height="64" fill="currentColor" class="text-muted mb-3">
        <use href="#icon-user"></use>
    </svg>
    <h4 class="text-muted mb-2">You are not yet assigned to any section.</h4>
    <p class="text-muted mb-0">Please wait for enrollment.</p>
</div>
<?php else: ?>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="surface p-4 h-100">
            <div class="text-muted small">LRN</div>
            <div class="h5 fw-bold mb-0"><?= htmlspecialchars($studentInfo['lrn'] ?? 'Not set') ?></div>
        </div>
      </div>
    <div class="col-12 col-md-4">
        <div class="surface p-4 h-100">
            <div class="text-muted small">Grade Level</div>
            <div class="h5 fw-bold mb-0">Grade <?= htmlspecialchars((string)($studentInfo['grade_level'] ?? '')) ?></div>
    </div>
  </div>
    <div class="col-12 col-md-4">
        <div class="surface p-4 h-100">
            <div class="text-muted small">Section</div>
            <div class="h5 fw-bold mb-0"><?= htmlspecialchars($studentInfo['class_name'] ?? 'Unassigned') ?></div>
      </div>
    </div>
  </div>
  
<div class="surface p-4 mb-4">
    <h2 class="h6 fw-semibold mb-3">Academic Snapshot</h2>
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="text-muted small">Overall Average</div>
            <div class="h4 fw-bold mb-0"><?= number_format($academicStats['overall_average'] ?? 0, 1) ?></div>
          </div>
        <div class="col-6 col-lg-3">
            <div class="text-muted small">Subjects</div>
            <div class="h4 fw-bold mb-0"><?= number_format($academicStats['subjects_count'] ?? 0) ?></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="text-muted small">Passing</div>
            <div class="h4 fw-bold mb-0"><?= number_format($academicStats['passing_subjects'] ?? 0) ?></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="text-muted small">Attendance</div>
            <div class="h4 fw-bold mb-0"><?= number_format($academicStats['attendance_rate'] ?? 0, 1) ?>%</div>
          </div>
        </div>
      </div>
      
<div class="surface p-4 mb-4">
    <h2 class="h6 fw-semibold mb-3">My Subjects</h2>
    <?php 
    // Use $classes from controller, fallback to $classesList for compatibility
    $classesList = $classes ?? $classesList ?? [];
    if (empty($classesList)): ?>
        <div class="text-muted small">You are not yet enrolled in any classes.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Schedule</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classesList as $class): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($class['subject_name'] ?? '') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($class['subject_code'] ?? '') ?></div>
                            </td>
                            <td><?= htmlspecialchars($class['teacher_name'] ?? 'TBA') ?></td>
                            <td><?= htmlspecialchars($class['schedule'] ?? 'TBA') ?></td>
                            <td><?= htmlspecialchars($class['room'] ?? 'TBD') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

      <div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="surface p-4 h-100">
            <h2 class="h6 fw-semibold mb-3">Recent Grades</h2>
            <?php if (empty($recentGrades)): ?>
                <div class="text-muted small">No grades posted yet.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($recentGrades as $grade): ?>
                        <li class="mb-2">
                            <div class="fw-semibold">
                                <?= htmlspecialchars($grade['subject_name'] ?? $grade['subject_code'] ?? 'Subject') ?> 
                                <?php if (!empty($grade['description'])): ?>
                                    — <?= htmlspecialchars($grade['description']) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?= number_format((float)($grade['grade_value'] ?? 0), 1) ?>/
                                <?= number_format((float)($grade['max_score'] ?? 100), 1) ?> 
                                <?php if (!empty($grade['percentage'])): ?>
                                    (<?= number_format((float)$grade['percentage'], 1) ?>%)
                                <?php endif; ?>
                                • 
                                <?php 
                                $date = $grade['graded_at'] ?? $grade['created_at'] ?? '';
                                if ($date) {
                                    echo date('M d, Y', strtotime($date));
                                }
                                ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
                  </div>
                </div>
    <div class="col-12 col-lg-6">
        <div class="surface p-4 h-100">
            <h2 class="h6 fw-semibold mb-3">Upcoming Assignments</h2>
            <?php if (empty($upcomingAssignments)): ?>
                <div class="text-muted small">No pending assignments.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($upcomingAssignments as $assignment): ?>
                        <li class="mb-2">
                            <div class="fw-semibold"><?= htmlspecialchars($assignment['subject'] ?? '') ?> — <?= htmlspecialchars($assignment['title'] ?? '') ?></div>
                            <div>Due <?= htmlspecialchars($assignment['due_date'] ?? '') ?> (<?= htmlspecialchars((string)($assignment['days_remaining'] ?? '')) ?> days left)</div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

