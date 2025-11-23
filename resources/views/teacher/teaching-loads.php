<?php
/**
 * Teacher: Teaching Loads Overview
 * Displays all classes assigned to the teacher with schedule
 */

$classes = $classes ?? [];
$advisorySection = $advisorySection ?? null;
$schedules = $schedules ?? [];
$stats = $stats ?? [];
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-chalkboard-teacher me-2"></i>My Teaching Loads</h2>
            <p class="text-muted">Overview of all your assigned classes and schedules</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Total Classes</div>
                            <div class="h3 mb-0 text-primary"><?= $stats['total_classes'] ?? 0 ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-book fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Total Students</div>
                            <div class="h3 mb-0 text-success"><?= $stats['total_students'] ?? 0 ?></div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Subjects</div>
                            <div class="h3 mb-0 text-info"><?= $stats['unique_subjects'] ?? 0 ?></div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-book-open fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Sections</div>
                            <div class="h3 mb-0 text-warning"><?= $stats['unique_sections'] ?? 0 ?></div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-door-open fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Classes List -->
        <div class="col-lg-8">
            <!-- Advisory Section (if applicable) -->
            <?php if ($advisorySection): ?>
                <div class="card shadow-sm mb-4 border-start border-success border-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Advisory Section</h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-1"><?= htmlspecialchars($advisorySection['name']) ?></h4>
                                <div class="text-muted">
                                    <span class="me-3"><i class="fas fa-layer-group me-2"></i>Grade <?= htmlspecialchars($advisorySection['grade_level']) ?></span>
                                    <span class="me-3"><i class="fas fa-door-open me-2"></i>Room <?= htmlspecialchars($advisorySection['room'] ?? 'N/A') ?></span>
                                    <span class="me-3"><i class="fas fa-users me-2"></i><?= $advisorySection['student_count'] ?> / <?= $advisorySection['max_students'] ?> students</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <a href="<?= url('/teacher/section?id=' . $advisorySection['id']) ?>" class="btn btn-success">
                                    <i class="fas fa-eye me-2"></i>View Section
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Classes List -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-book me-2"></i>My Classes</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($classes)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Section</th>
                                        <th class="text-center">Students</th>
                                        <th>Schedule</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <?php 
                                        $studentCount = max($class['enrolled_count'], $class['section_student_count']);
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($class['subject_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($class['subject_code']) ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($class['section_name']) ?><br>
                                                <small class="text-muted">Grade <?= htmlspecialchars($class['grade_level']) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info fs-6"><?= $studentCount ?></span>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($class['schedule'] ?? 'N/A') ?></small><br>
                                                <small class="text-muted">Room: <?= htmlspecialchars($class['room'] ?? 'N/A') ?></small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= url('/teacher/view-class?id=' . $class['class_id']) ?>" 
                                                       class="btn btn-outline-primary"
                                                       title="View Roster">
                                                        <i class="fas fa-users"></i>
                                                    </a>
                                                    <a href="<?= url('/teacher/submit-grade?class_id=' . $class['class_id']) ?>" 
                                                       class="btn btn-outline-success"
                                                       title="Submit Grades">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= url('/teacher/attendance?class_id=' . $class['class_id']) ?>" 
                                                       class="btn btn-outline-info"
                                                       title="Mark Attendance">
                                                        <i class="fas fa-calendar-check"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No teaching loads assigned yet. Please contact the admin to assign classes.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Weekly Schedule -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Weekly Schedule</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($schedules)): ?>
                        <?php
                        // Group schedules by day
                        $schedulesByDay = [];
                        foreach ($schedules as $schedule) {
                            $day = $schedule['day_of_week'];
                            if (!isset($schedulesByDay[$day])) {
                                $schedulesByDay[$day] = [];
                            }
                            $schedulesByDay[$day][] = $schedule;
                        }

                        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        ?>

                        <?php foreach ($daysOfWeek as $day): ?>
                            <?php if (isset($schedulesByDay[$day])): ?>
                                <div class="mb-3">
                                    <h6 class="text-primary mb-2">
                                        <i class="fas fa-calendar-day me-2"></i><?= $day ?>
                                    </h6>
                                    <?php foreach ($schedulesByDay[$day] as $schedule): ?>
                                        <div class="card bg-light mb-2">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong><?= htmlspecialchars($schedule['subject_name']) ?></strong><br>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($schedule['section_name']) ?> 
                                                            • Room <?= htmlspecialchars($schedule['room'] ?? 'N/A') ?>
                                                        </small>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-muted">
                                                            <?= date('g:i A', strtotime($schedule['start_time'])) ?><br>
                                                            <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <!-- Legend -->
                        <div class="alert alert-info mt-3">
                            <small>
                                <i class="fas fa-info-circle me-2"></i>
                                Click on class actions to view roster, submit grades, or mark attendance.
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No schedule set yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2 text-warning"></i>Quick Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li class="mb-2">Click <i class="fas fa-users text-primary"></i> to view class roster</li>
                        <li class="mb-2">Click <i class="fas fa-edit text-success"></i> to submit grades</li>
                        <li class="mb-2">Click <i class="fas fa-calendar-check text-info"></i> to mark attendance</li>
                        <li>Use the dashboard for quick access to recent activities</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

