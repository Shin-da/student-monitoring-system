<?php
/**
 * Parent: Child's Schedule View
 * Displays weekly class schedule in a visual timetable
 */

$schedules = $schedules ?? [];
$schedulesByDay = $schedulesByDay ?? [];
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1"><i class="fas fa-calendar-alt me-2"></i>Child's Schedule</h2>
        <p class="text-muted">Your child's weekly class timetable</p>
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
                                                <div class="text-center text-muted" style="min-height: 60px; display: flex; align-items: center; justify-content: center;">
                                                    <small>-</small>
                                                </div>
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
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <svg width="64" height="64" fill="currentColor" class="text-muted mb-3">
                    <use href="#icon-calendar"></use>
                </svg>
                <h5 class="text-muted">No schedule available</h5>
                <p class="text-muted mb-0">Your child's schedule will appear here once classes are assigned.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

