<?php
/**
 * Student: My Schedule View
 * Displays weekly class schedule in a visual timetable
 */

$schedules = $schedules ?? [];
$schedulesByDay = $schedulesByDay ?? [];
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1"><i class="fas fa-calendar-alt me-2"></i>My Schedule</h2>
        <p class="text-muted">Your weekly class timetable</p>
    </div>

    <?php if (!empty($schedules)): ?>
        <!-- Weekly Calendar View -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Weekly Schedule</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 120px;">Time</th>
                                <th>Monday</th>
                                <th>Tuesday</th>
                                <th>Wednesday</th>
                                <th>Thursday</th>
                                <th>Friday</th>
                                <th>Saturday</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get all unique time slots
                            $timeSlots = [];
                            foreach ($schedules as $schedule) {
                                $timeKey = $schedule['start_time'] . '-' . $schedule['end_time'];
                                if (!isset($timeSlots[$timeKey])) {
                                    $timeSlots[$timeKey] = [
                                        'start' => $schedule['start_time'],
                                        'end' => $schedule['end_time']
                                    ];
                                }
                            }
                            // Sort by start time
                            uasort($timeSlots, function($a, $b) {
                                return strcmp($a['start'], $b['start']);
                            });

                            $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                            foreach ($timeSlots as $timeSlot):
                                $startTime = $timeSlot['start'];
                                $endTime = $timeSlot['end'];
                            ?>
                                <tr>
                                    <td class="text-center align-middle bg-light">
                                        <strong><?= date('g:i A', strtotime($startTime)) ?></strong><br>
                                        <small class="text-muted"><?= date('g:i A', strtotime($endTime)) ?></small>
                                    </td>
                                    <?php foreach ($daysOfWeek as $day): ?>
                                        <td class="p-2">
                                            <?php
                                            // Find class for this day and time
                                            $classForSlot = null;
                                            if (isset($schedulesByDay[$day])) {
                                                foreach ($schedulesByDay[$day] as $schedule) {
                                                    if ($schedule['start_time'] === $startTime && $schedule['end_time'] === $endTime) {
                                                        $classForSlot = $schedule;
                                                        break;
                                                    }
                                                }
                                            }
                                            ?>
                                            <?php if ($classForSlot): ?>
                                                <div class="card bg-primary bg-opacity-10 border-primary border-start border-3 h-100">
                                                    <div class="card-body p-2">
                                                        <div class="fw-bold text-primary small">
                                                            <?= htmlspecialchars($classForSlot['subject_name']) ?>
                                                        </div>
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            <?= htmlspecialchars($classForSlot['subject_code']) ?>
                                                        </div>
                                                        <div class="mt-1" style="font-size: 0.75rem;">
                                                            <i class="fas fa-door-open me-1"></i>
                                                            <?= htmlspecialchars($classForSlot['room'] ?? 'N/A') ?>
                                                        </div>
                                                        <div style="font-size: 0.75rem;">
                                                            <i class="fas fa-user me-1"></i>
                                                            <?= htmlspecialchars($classForSlot['teacher_name'] ?? 'N/A') ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center text-muted small">-</div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Day-by-Day List View -->
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Schedule by Day</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($daysOfWeek as $day): ?>
                        <?php if (isset($schedulesByDay[$day]) && !empty($schedulesByDay[$day])): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="fas fa-calendar-day me-2"></i><?= $day ?>
                                </h6>
                                <?php foreach ($schedulesByDay[$day] as $schedule): ?>
                                    <div class="card bg-light mb-2">
                                        <div class="card-body py-2 px-3">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <strong class="text-primary"><?= htmlspecialchars($schedule['subject_name']) ?></strong>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($schedule['subject_code']) ?></span>
                                            </div>
                                            <div class="small text-muted">
                                                <div><i class="fas fa-clock me-1"></i>
                                                    <?= date('g:i A', strtotime($schedule['start_time'])) ?> - 
                                                    <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                                                </div>
                                                <div><i class="fas fa-door-open me-1"></i>Room: <?= htmlspecialchars($schedule['room'] ?? 'N/A') ?></div>
                                                <div><i class="fas fa-chalkboard-teacher me-1"></i><?= htmlspecialchars($schedule['teacher_name'] ?? 'N/A') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Legend & Info -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-info-circle text-info me-2"></i>Schedule Information</h6>
                        <ul class="mb-0 small">
                            <li class="mb-2">Classes are scheduled from Monday to Saturday</li>
                            <li class="mb-2">Check room assignments before each class</li>
                            <li class="mb-2">Be present at least 5 minutes before class starts</li>
                            <li>Contact your teacher if you have schedule conflicts</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-calendar-check text-success me-2"></i>Quick Stats</h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h4 text-primary"><?= count($schedules) ?></div>
                                    <div class="small text-muted">Total Classes/Week</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h4 text-success"><?= count(array_unique(array_column($schedules, 'subject_code'))) ?></div>
                                    <div class="small text-muted">Subjects</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Button -->
        <div class="text-center mt-4">
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i>Print Schedule
            </button>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-calendar-times fa-4x text-muted"></i>
                </div>
                <h4 class="text-muted mb-3">No Schedule Available</h4>
                <p class="text-muted mb-4">
                    You don't have any scheduled classes yet. Please wait for your classes to be scheduled.
                </p>
                <div class="alert alert-info d-inline-block">
                    <i class="fas fa-info-circle me-2"></i>
                    Schedule will appear here once you are enrolled in classes and they are scheduled.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .card-header,
    button,
    .btn,
    nav,
    aside {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        page-break-inside: avoid;
    }
}
</style>

