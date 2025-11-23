<?php
/**
 * Teacher: View Class Roster
 * Displays all students enrolled in a specific class with their performance
 */

$class = $class ?? [];
$students = $students ?? [];
$schedules = $schedules ?? [];
$studentCount = $studentCount ?? 0;
?>

<div class="container-fluid py-4">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="<?= url('/teacher/classes') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Classes
        </a>
    </div>

    <!-- Class Header Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="mb-1">
                        <i class="fas fa-book-open me-2 text-primary"></i>
                        <?= htmlspecialchars($class['subject_name'] ?? 'Class') ?>
                    </h2>
                    <div class="text-muted">
                        <span class="me-3"><i class="fas fa-code me-2"></i><?= htmlspecialchars($class['subject_code'] ?? 'N/A') ?></span>
                        <span class="me-3"><i class="fas fa-users me-2"></i><?= htmlspecialchars($class['section_name'] ?? 'N/A') ?></span>
                        <span class="me-3"><i class="fas fa-layer-group me-2"></i>Grade <?= htmlspecialchars($class['grade_level'] ?? 'N/A') ?></span>
                        <span class="me-3"><i class="fas fa-door-open me-2"></i>Room <?= htmlspecialchars($class['room'] ?? $class['section_room'] ?? 'N/A') ?></span>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="text-end">
                        <div class="h3 mb-0 text-primary"><?= $studentCount ?></div>
                        <div class="small text-muted">Students</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Class Information -->
        <div class="col-lg-4">
            <!-- Class Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Class Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Subject</label>
                        <div><strong><?= htmlspecialchars($class['subject_name'] ?? 'N/A') ?></strong></div>
                        <small class="text-muted"><?= htmlspecialchars($class['subject_code'] ?? '') ?></small>
                    </div>
                    <?php if (!empty($class['subject_description'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small">Description</label>
                            <div><?= htmlspecialchars($class['subject_description']) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="text-muted small">Section</label>
                        <div><?= htmlspecialchars($class['section_name'] ?? 'N/A') ?> (Grade <?= htmlspecialchars($class['grade_level'] ?? 'N/A') ?>)</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Room</label>
                        <div><?= htmlspecialchars($class['room'] ?? $class['section_room'] ?? 'N/A') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">School Year</label>
                        <div><?= htmlspecialchars($class['school_year'] ?? 'N/A') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Semester</label>
                        <div><?= htmlspecialchars($class['semester'] ?? 'N/A') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Capacity</label>
                        <div><?= $studentCount ?> / <?= htmlspecialchars($class['max_students'] ?? 'N/A') ?> students</div>
                        <?php if (!empty($class['max_students']) && $studentCount >= $class['max_students']): ?>
                            <small class="text-danger">Class is at full capacity</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Class Schedule -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Class Schedule</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($schedules)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($schedules as $schedule): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($schedule['day_of_week']) ?></strong>
                                        </div>
                                        <div class="text-muted">
                                            <?= date('g:i A', strtotime($schedule['start_time'])) ?> - 
                                            <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (!empty($class['schedule'])): ?>
                        <div class="alert alert-info mb-0">
                            <?= htmlspecialchars($class['schedule']) ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No schedule set for this class.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= url('/teacher/submit-grade?class_id=' . ($class['id'] ?? 0)) ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Submit Grades
                        </a>
                        <a href="<?= url('/teacher/attendance?class_id=' . ($class['id'] ?? 0)) ?>" class="btn btn-info text-white">
                            <i class="fas fa-calendar-check me-2"></i>Mark Attendance
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Student List -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Class Roster</h5>
                    <div>
                        <button class="btn btn-light btn-sm" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($students)): ?>
                        <!-- Search/Filter -->
                        <div class="mb-3">
                            <input type="text" 
                                   id="studentSearch" 
                                   class="form-control" 
                                   placeholder="Search by name, LRN, or email...">
                        </div>

                        <!-- Student Table -->
                        <div class="table-responsive">
                            <table class="table table-hover" id="studentTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>LRN</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th class="text-center">Current Grade</th>
                                        <th class="text-center">Attendance</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($student['lrn']) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($student['full_name']) ?></strong>
                                                <?php if ($student['gender'] === 'male'): ?>
                                                    <i class="fas fa-mars text-primary"></i>
                                                <?php elseif ($student['gender'] === 'female'): ?>
                                                    <i class="fas fa-venus text-danger"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?= htmlspecialchars($student['email'] ?? 'N/A') ?></small></td>
                                            <td class="text-center">
                                                <?php if ($student['current_grade']): ?>
                                                    <span class="badge bg-<?= $student['current_grade'] >= 75 ? 'success' : 'danger' ?> fs-6">
                                                        <?= number_format($student['current_grade'], 2) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($student['attendance_total'] > 0): ?>
                                                    <div class="progress" style="height: 20px; min-width: 80px;">
                                                        <div class="progress-bar bg-<?= $student['attendance_percentage'] >= 90 ? 'success' : ($student['attendance_percentage'] >= 75 ? 'warning' : 'danger') ?>" 
                                                             role="progressbar" 
                                                             style="width: <?= $student['attendance_percentage'] ?>%"
                                                             aria-valuenow="<?= $student['attendance_percentage'] ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            <?= number_format($student['attendance_percentage'], 0) ?>%
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= url('/teacher/view-student?id=' . $student['student_id']) ?>" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="View Profile">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Stats -->
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <div class="h4 text-primary"><?= $studentCount ?></div>
                                        <div class="small text-muted">Total Students</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <?php 
                                        $passingCount = count(array_filter($students, fn($s) => ($s['current_grade'] ?? 0) >= 75));
                                        ?>
                                        <div class="h4 text-success"><?= $passingCount ?></div>
                                        <div class="small text-muted">Passing</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <?php 
                                        $averageGrade = 0;
                                        $gradedStudents = array_filter($students, fn($s) => !empty($s['current_grade']));
                                        if (!empty($gradedStudents)) {
                                            $averageGrade = array_sum(array_column($gradedStudents, 'current_grade')) / count($gradedStudents);
                                        }
                                        ?>
                                        <div class="h4 text-info"><?= number_format($averageGrade, 2) ?></div>
                                        <div class="small text-muted">Class Average</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <?php 
                                        $averageAttendance = 0;
                                        $studentsWithAttendance = array_filter($students, fn($s) => $s['attendance_total'] > 0);
                                        if (!empty($studentsWithAttendance)) {
                                            $averageAttendance = array_sum(array_column($studentsWithAttendance, 'attendance_percentage')) / count($studentsWithAttendance);
                                        }
                                        ?>
                                        <div class="h4 text-warning"><?= number_format($averageAttendance, 1) ?>%</div>
                                        <div class="small text-muted">Avg Attendance</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No students enrolled in this class yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Student search functionality
    const searchInput = document.getElementById('studentSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('studentTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>

