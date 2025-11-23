<?php
/**
 * Student: My Classes View
 * Displays all enrolled classes with details
 */

$enrolledClasses = $enrolledClasses ?? [];
$studentGradeLevel = $studentGradeLevel ?? 'N/A';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1"><i class="fas fa-book-open me-2"></i>My Classes</h2>
        <p class="text-muted">View all your enrolled subjects and teachers</p>
    </div>

    <?php if (!empty($enrolledClasses)): ?>
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">Total Classes</div>
                                <div class="h3 mb-0 text-primary"><?= count($enrolledClasses) ?></div>
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
                                <div class="text-muted small">Passing</div>
                                <?php 
                                $passingCount = count(array_filter($enrolledClasses, fn($c) => ($c['current_grade'] ?? 0) >= 75));
                                ?>
                                <div class="h3 mb-0 text-success"><?= $passingCount ?></div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-2x"></i>
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
                                <div class="text-muted small">Need Attention</div>
                                <?php 
                                $needsAttention = count(array_filter($enrolledClasses, fn($c) => ($c['current_grade'] ?? 0) > 0 && ($c['current_grade'] ?? 0) < 75));
                                ?>
                                <div class="h3 mb-0 text-warning"><?= $needsAttention ?></div>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
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
                                <div class="text-muted small">Average Grade</div>
                                <?php 
                                $gradedClasses = array_filter($enrolledClasses, fn($c) => !empty($c['current_grade']));
                                $averageGrade = !empty($gradedClasses) ? 
                                    array_sum(array_column($gradedClasses, 'current_grade')) / count($gradedClasses) : 0;
                                ?>
                                <div class="h3 mb-0 text-info"><?= number_format($averageGrade, 2) ?></div>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classes Grid -->
        <div class="row">
            <?php foreach ($enrolledClasses as $class): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm h-100 border-top border-primary border-3" style="cursor: pointer; transition: transform 0.2s;" onclick="window.location.href='<?= \Helpers\Url::to('/student/view-subject?class_id=' . $class['class_id'] . '&subject_id=' . $class['subject_id']) ?>'" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card-body">
                            <!-- Subject Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <?= htmlspecialchars($class['subject_name']) ?>
                                    </h5>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($class['subject_code']) ?></span>
                                </div>
                                <?php if (!empty($class['current_grade'])): ?>
                                    <div class="text-end">
                                        <div class="h4 mb-0 text-<?= $class['current_grade'] >= 75 ? 'success' : 'danger' ?>">
                                            <?= number_format($class['current_grade'], 2) ?>
                                        </div>
                                        <small class="text-muted">Grade</small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Description -->
                            <?php if (!empty($class['subject_description'])): ?>
                                <p class="text-muted small mb-3">
                                    <?= htmlspecialchars(substr($class['subject_description'], 0, 100)) ?>
                                    <?= strlen($class['subject_description']) > 100 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>

                            <!-- Teacher Info -->
                            <div class="mb-2">
                                <i class="fas fa-chalkboard-teacher text-primary me-2"></i>
                                <strong>Teacher:</strong> <?= htmlspecialchars($class['teacher_name'] ?? 'N/A') ?>
                            </div>

                            <!-- Section Info -->
                            <div class="mb-2">
                                <i class="fas fa-users text-success me-2"></i>
                                <strong>Section:</strong> <?= htmlspecialchars($class['section_name'] ?? 'N/A') ?>
                            </div>

                            <!-- Schedule Info -->
                            <?php if (!empty($class['schedule'])): ?>
                                <div class="mb-2">
                                    <i class="fas fa-clock text-info me-2"></i>
                                    <strong>Schedule:</strong> <small><?= htmlspecialchars($class['schedule']) ?></small>
                                </div>
                            <?php endif; ?>

                            <!-- Room Info -->
                            <?php if (!empty($class['room'])): ?>
                                <div class="mb-2">
                                    <i class="fas fa-door-open text-warning me-2"></i>
                                    <strong>Room:</strong> <?= htmlspecialchars($class['room']) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Graded Items Count -->
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-tasks me-1"></i>
                                        <?= $class['grade_count'] ?? 0 ?> graded item(s)
                                    </small>
                                    <span class="badge bg-<?= $class['enrollment_status'] === 'enrolled' ? 'success' : 'secondary' ?>">
                                        <?= htmlspecialchars(ucfirst($class['enrollment_status'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= htmlspecialchars($class['school_year'] ?? 'N/A') ?> 
                                    (<?= htmlspecialchars($class['semester'] ?? 'N/A') ?>)
                                </small>
                                <?php if (!empty($class['teacher_email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($class['teacher_email']) ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="Email Teacher">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Information Card -->
        <div class="card shadow-sm border-start border-info border-4 mt-4">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-info-circle text-info me-2"></i>About Your Classes</h5>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="mb-0">
                            <li class="mb-2">View your current grade for each subject</li>
                            <li class="mb-2">Check your teacher's contact information</li>
                            <li>See your class schedule and room assignments</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="mb-0">
                            <li class="mb-2">Grades are computed as: WW (20%) + PT (50%) + QE (20%) + Attendance (10%)</li>
                            <li class="mb-2">Passing grade is 75 or above</li>
                            <li>Contact your teacher if you have questions</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-book-open fa-4x text-muted"></i>
                </div>
                <h4 class="text-muted mb-3">No Classes Yet</h4>
                <p class="text-muted mb-4">
                    You are not enrolled in any classes yet. Please wait for the admin or your teacher to assign you to classes.
                </p>
                <div class="alert alert-info d-inline-block">
                    <i class="fas fa-info-circle me-2"></i>
                    If you believe this is an error, please contact your teacher or school administrator.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

