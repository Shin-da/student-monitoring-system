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

      <!-- AI Analytics & Alerts Section -->
      <?php if (!empty($ai_analysis) || !empty($alerts)): ?>
      <div class="row g-3 mb-4">
        <!-- AI Performance Analytics -->
        <?php if (!empty($ai_analysis)): ?>
        <div class="col-12 col-lg-6">
          <div class="surface p-4 h-100 border-start border-4 ai-analytics-card <?= 
            $ai_analysis['risk_level'] === 'high' ? 'border-danger' : 
            ($ai_analysis['risk_level'] === 'medium' ? 'border-warning' : 'border-success') 
          ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="d-flex align-items-center">
                <div class="ai-glow-icon me-2">
                  <svg width="24" height="24" fill="currentColor" class="text-<?= 
                    $ai_analysis['risk_level'] === 'high' ? 'danger' : 
                    ($ai_analysis['risk_level'] === 'medium' ? 'warning' : 'success') 
                  ?>">
                    <use href="#icon-chart"></use>
                  </svg>
                </div>
                <div>
                  <h2 class="h6 fw-bold mb-0">AI Performance Analytics</h2>
                  <div class="text-muted small">Powered by intelligent analytics</div>
                </div>
              </div>
              <span class="badge bg-<?= 
                $ai_analysis['risk_level'] === 'high' ? 'danger' : 
                ($ai_analysis['risk_level'] === 'medium' ? 'warning' : 'success') 
              ?> px-3 py-2">
                <?= strtoupper($ai_analysis['risk_level'] ?? 'low') ?>
              </span>
            </div>
            
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Overall Risk Score</span>
                <span class="h5 fw-bold mb-0 text-<?= 
                  $ai_analysis['risk_level'] === 'high' ? 'danger' : 
                  ($ai_analysis['risk_level'] === 'medium' ? 'warning' : 'success') 
                ?>"><?= number_format($ai_analysis['overall_risk_score'] ?? 0, 1) ?>/100</span>
              </div>
              <div class="progress ai-progress" style="height: 10px; border-radius: 5px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-<?= 
                  $ai_analysis['risk_level'] === 'high' ? 'danger' : 
                  ($ai_analysis['risk_level'] === 'medium' ? 'warning' : 'success') 
                ?>" 
                role="progressbar" 
                style="width: <?= min(100, ($ai_analysis['overall_risk_score'] ?? 0)) ?>%"></div>
              </div>
              <div class="text-muted small mt-1">
                <?php if ($ai_analysis['risk_level'] === 'high'): ?>
                  ⚠️ Immediate attention recommended
                <?php elseif ($ai_analysis['risk_level'] === 'medium'): ?>
                  ⚡ Monitor closely
                <?php else: ?>
                  ✅ Performance on track
                <?php endif; ?>
              </div>
            </div>
            
            <?php if (!empty($ai_analysis['at_risk_subjects'])): ?>
            <div class="mb-3">
              <div class="text-muted small mb-2">At-Risk Subjects (<?= count($ai_analysis['at_risk_subjects']) ?>)</div>
              <ul class="list-unstyled mb-0 small">
                <?php foreach (array_slice($ai_analysis['at_risk_subjects'], 0, 3) as $subject): ?>
                <li class="mb-2">
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                      <span class="fw-semibold"><?= htmlspecialchars($subject['subject_name'] ?? 'Subject') ?></span>
                      <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="text-muted small">Current: <?= number_format($subject['final_grade'] ?? 0, 1) ?>%</span>
                        <?php if (!empty($subject['prediction']) && $subject['prediction']['predicted_grade'] !== null): ?>
                        <span class="text-muted small">•</span>
                        <span class="small fw-semibold text-<?= 
                          $subject['prediction']['predicted_grade'] < 75 ? 'danger' : 'success' 
                        ?>">
                          Projected: <?= number_format($subject['prediction']['predicted_grade'], 1) ?>%
                        </span>
                        <?php if ($subject['prediction']['trend'] === 'improving'): ?>
                          <span class="badge bg-success">↑ Improving</span>
                        <?php elseif ($subject['prediction']['trend'] === 'declining'): ?>
                          <span class="badge bg-danger">↓ Declining</span>
                        <?php endif; ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    <span class="badge bg-<?= $subject['risk_level'] === 'high' ? 'danger' : 'warning' ?> ms-2">
                      <?= ucfirst($subject['risk_level']) ?>
                    </span>
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($ai_analysis['attendance_analysis']) && ($ai_analysis['attendance_analysis']['status'] ?? 'good') !== 'good'): ?>
            <div class="alert alert-<?= ($ai_analysis['attendance_analysis']['status'] ?? 'good') === 'poor' ? 'danger' : 'warning' ?> alert-sm mb-0">
              <small>
                <strong>Attendance:</strong> <?= number_format($ai_analysis['attendance_analysis']['percentage'] ?? 100, 1) ?>%
                (<?= $ai_analysis['attendance_analysis']['present_days'] ?? 0 ?>/<?= $ai_analysis['attendance_analysis']['total_days'] ?? 0 ?> days)
              </small>
            </div>
            <?php endif; ?>
            
            <!-- Attendance Pattern Analysis -->
            <?php if (!empty($ai_analysis['attendance_pattern_analysis']) && 
                      !empty($ai_analysis['attendance_pattern_analysis']['patterns_detected'])): 
              $patternAnalysis = $ai_analysis['attendance_pattern_analysis'];
              $patterns = $patternAnalysis['patterns_detected'] ?? [];
            ?>
            <div class="mt-3 p-3 bg-light rounded border-start border-3 border-warning">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="small fw-bold text-warning">
                  <svg width="16" height="16" fill="currentColor" class="me-1">
                    <use href="#icon-calendar"></use>
                  </svg>
                  AI Attendance Pattern Detection
                </div>
                <?php if (!empty($patternAnalysis['overall_assessment']['severity']) && 
                          $patternAnalysis['overall_assessment']['severity'] !== 'low'): ?>
                <span class="badge bg-<?= 
                  $patternAnalysis['overall_assessment']['severity'] === 'high' ? 'danger' : 'warning' 
                ?>"><?= ucfirst($patternAnalysis['overall_assessment']['severity']) ?> Priority</span>
                <?php endif; ?>
              </div>
              <div class="small">
                <?php foreach (array_slice($patterns, 0, 2) as $pattern): ?>
                <div class="mb-1">
                  <strong><?= htmlspecialchars($pattern['description']) ?></strong>
                  <?php if (!empty($pattern['details']['day'])): ?>
                  <div class="text-muted small mt-1">
                    Pattern: Frequently absent on <?= htmlspecialchars($pattern['details']['day']) ?>s
                    (<?= number_format($pattern['details']['absent_rate'] ?? 0, 1) ?>% absence rate)
                  </div>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($patternAnalysis['predictive_analysis']['projected_absences_next_2_weeks'])): ?>
                <div class="mt-2 text-muted small">
                  <strong>AI Prediction:</strong> 
                  Projected <?= number_format($patternAnalysis['predictive_analysis']['projected_absences_next_2_weeks'], 1) ?> 
                  absences in next 2 weeks
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
            
            <!-- AI Predictions Summary -->
            <?php if (!empty($ai_analysis['predictions'])): 
              $predictions = array_filter($ai_analysis['predictions'], fn($p) => $p['predicted_grade'] !== null);
              if (!empty($predictions)): 
                $avgPredicted = array_sum(array_column($predictions, 'predicted_grade')) / count($predictions);
                $improvingCount = count(array_filter($predictions, fn($p) => $p['trend'] === 'improving'));
                $decliningCount = count(array_filter($predictions, fn($p) => $p['trend'] === 'declining'));
            ?>
            <div class="mt-3 p-3 bg-light rounded border-start border-3 border-info">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="small fw-bold text-info">
                  <svg width="16" height="16" fill="currentColor" class="me-1">
                    <use href="#icon-chart"></use>
                  </svg>
                  AI Grade Predictions
                </div>
                <span class="badge bg-info"><?= count($predictions) ?> Subjects</span>
              </div>
              <div class="small">
                <div class="mb-1">
                  <strong>Average Projected Grade:</strong> 
                  <span class="fw-bold text-<?= $avgPredicted < 75 ? 'danger' : 'success' ?>">
                    <?= number_format($avgPredicted, 1) ?>%
                  </span>
                </div>
                <div class="d-flex gap-3 text-muted">
                  <?php if ($improvingCount > 0): ?>
                  <span class="text-success">↑ <?= $improvingCount ?> Improving</span>
                  <?php endif; ?>
                  <?php if ($decliningCount > 0): ?>
                  <span class="text-danger">↓ <?= $decliningCount ?> Declining</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endif; endif; ?>
            
            <div class="mt-3">
              <a href="<?= \Helpers\Url::to('/student/alerts') ?>" class="btn btn-sm btn-primary">
                <svg width="16" height="16" fill="currentColor" class="me-1">
                  <use href="#icon-chart"></use>
                </svg>
                View Full AI Analysis →
              </a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- Alerts Widget -->
        <div class="col-12 col-lg-6">
          <div class="surface p-4 h-100 ai-alerts-widget">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="d-flex align-items-center">
                <div class="ai-glow-icon me-2">
                  <svg width="24" height="24" fill="currentColor" class="text-warning">
                    <use href="#icon-alerts"></use>
                  </svg>
                </div>
                <div>
                  <h2 class="h6 fw-bold mb-0">AI-Generated Alerts</h2>
                  <div class="text-muted small">Real-time performance monitoring</div>
                </div>
              </div>
              <?php if (!empty($alerts)): ?>
              <span class="badge bg-warning px-3 py-2"><?= count($alerts) ?></span>
              <?php endif; ?>
            </div>
            <?php if (empty($alerts)): ?>
              <div class="text-center py-3">
                <svg width="48" height="48" fill="currentColor" class="text-success mb-2">
                  <use href="#icon-check-circle"></use>
                </svg>
                <div class="text-muted small">No active alerts. Keep up the great work! 🎉</div>
              </div>
            <?php else: ?>
              <ul class="list-unstyled mb-0 small">
                <?php foreach (array_slice($alerts, 0, 3) as $alert): ?>
                <li class="mb-3 pb-3 border-bottom ai-alert-item">
                  <div class="d-flex align-items-start">
                    <div class="ai-alert-icon-small bg-<?= 
                      ($alert['severity'] ?? 'medium') === 'high' ? 'danger' : 
                      (($alert['severity'] ?? 'medium') === 'medium' ? 'warning' : 'info') 
                    ?> bg-opacity-10 rounded-circle p-2 me-3">
                      <svg width="16" height="16" fill="currentColor" class="text-<?= 
                        ($alert['severity'] ?? 'medium') === 'high' ? 'danger' : 
                        (($alert['severity'] ?? 'medium') === 'medium' ? 'warning' : 'info') 
                      ?>">
                        <use href="#icon-alerts"></use>
                      </svg>
                    </div>
                    <div class="flex-grow-1">
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="fw-bold"><?= htmlspecialchars($alert['title'] ?? 'Alert') ?></div>
                        <span class="badge bg-<?= 
                          ($alert['severity'] ?? 'medium') === 'high' ? 'danger' : 
                          (($alert['severity'] ?? 'medium') === 'medium' ? 'warning' : 'info') 
                        ?>"><?= ucfirst($alert['severity'] ?? 'medium') ?></span>
                        <span class="badge bg-secondary">AI</span>
                      </div>
                      <div class="text-muted small mb-1"><?= htmlspecialchars($alert['description'] ?? '') ?></div>
                      <?php if (!empty($alert['subject_name'])): ?>
                      <div class="text-muted small">
                        <svg width="14" height="14" fill="currentColor" class="me-1">
                          <use href="#icon-book"></use>
                        </svg>
                        <?= htmlspecialchars($alert['subject_name']) ?>
                      </div>
                      <?php endif; ?>
                      <div class="text-muted small mt-1">
                        <?= isset($alert['created_at']) ? date('M d, Y', strtotime($alert['created_at'])) : '' ?>
                      </div>
                    </div>
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
              <?php if (count($alerts) > 3): ?>
              <div class="mt-3">
                <a href="<?= \Helpers\Url::to('/student/alerts') ?>" class="btn btn-sm btn-primary">
                  <svg width="16" height="16" fill="currentColor" class="me-1">
                    <use href="#icon-alerts"></use>
                  </svg>
                  View All Alerts (<?= count($alerts) ?>) →
                </a>
              </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
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
  
  <!-- Performance Charts Section -->
  <?php if (!empty($chart_data)): ?>
  <div class="row g-3 mb-4">
    <!-- Grade Trend Chart -->
    <?php if (!empty($chart_data['grade_trends']['data']) && count(array_filter($chart_data['grade_trends']['data'])) > 0): ?>
    <div class="col-12 col-lg-6">
      <div class="surface p-4 h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3 class="h6 fw-bold mb-0">
            <svg width="20" height="20" fill="currentColor" class="me-2">
              <use href="#icon-line-chart"></use>
            </svg>
            Grade Trends (Quarterly)
          </h3>
        </div>
        <div class="chart-container" style="height: 250px; position: relative;">
          <canvas id="gradeTrendChart"></canvas>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- Attendance Trend Chart -->
    <?php if (!empty($chart_data['attendance_trends']['labels'])): ?>
    <div class="col-12 col-lg-6">
      <div class="surface p-4 h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3 class="h6 fw-bold mb-0">
            <svg width="20" height="20" fill="currentColor" class="me-2">
              <use href="#icon-calendar"></use>
            </svg>
            Attendance Trends
          </h3>
        </div>
        <div class="chart-container" style="height: 250px; position: relative;">
          <canvas id="attendanceTrendChart"></canvas>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- Subject Performance Chart -->
    <?php if (!empty($chart_data['subject_performance']['labels'])): ?>
    <div class="col-12">
      <div class="surface p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3 class="h6 fw-bold mb-0">
            <svg width="20" height="20" fill="currentColor" class="me-2">
              <use href="#icon-chart"></use>
            </svg>
            Subject Performance (Current Quarter)
          </h3>
        </div>
        <div class="chart-container" style="height: 300px; position: relative;">
          <canvas id="subjectPerformanceChart"></canvas>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<style>
/* AI-Themed Styles for Dashboard */
.ai-analytics-card {
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.ai-analytics-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, 
    transparent, 
    rgba(0, 123, 255, 0.5), 
    transparent
  );
  animation: aiShimmer 3s ease-in-out infinite;
}

@keyframes aiShimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.ai-glow-icon {
  position: relative;
  animation: aiPulse 2s ease-in-out infinite;
}

@keyframes aiPulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(1.05);
  }
}

.ai-progress {
  background: rgba(0, 0, 0, 0.1);
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.ai-alerts-widget {
  border-left: 3px solid #ffc107;
}

.ai-alert-item {
  transition: all 0.2s ease;
  padding-left: 0.5rem;
  margin-left: -0.5rem;
  border-radius: 4px;
}

.ai-alert-item:hover {
  background: rgba(0, 123, 255, 0.05);
  padding-left: 0.75rem;
}

.ai-alert-icon-small {
  animation: aiGlow 2s ease-in-out infinite;
}

@keyframes aiGlow {
  0%, 100% {
    box-shadow: 0 0 3px rgba(0, 123, 255, 0.3);
  }
  50% {
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.6);
  }
}

[data-theme="dark"] .ai-progress {
  background: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .ai-alert-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.chart-container {
  position: relative;
  width: 100%;
}
</style>

<?php if (!empty($chart_data)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($chart_data['grade_trends']['data']) && count(array_filter($chart_data['grade_trends']['data'])) > 0): ?>
    // Grade Trend Chart
    const gradeTrendCtx = document.getElementById('gradeTrendChart');
    if (gradeTrendCtx && typeof Chart !== 'undefined') {
        new Chart(gradeTrendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_data['grade_trends']['labels']) ?>,
                datasets: [{
                    label: 'Average Grade (%)',
                    data: <?= json_encode($chart_data['grade_trends']['data']) ?>,
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(13, 110, 253)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Average: ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if (!empty($chart_data['attendance_trends']['labels'])): ?>
    // Attendance Trend Chart
    const attendanceTrendCtx = document.getElementById('attendanceTrendChart');
    if (attendanceTrendCtx && typeof Chart !== 'undefined') {
        new Chart(attendanceTrendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_data['attendance_trends']['labels']) ?>,
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: <?= json_encode($chart_data['attendance_trends']['attendance_rates']) ?>,
                    borderColor: 'rgb(25, 135, 84)',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(25, 135, 84)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Attendance: ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if (!empty($chart_data['subject_performance']['labels'])): ?>
    // Subject Performance Chart
    const subjectPerformanceCtx = document.getElementById('subjectPerformanceChart');
    if (subjectPerformanceCtx && typeof Chart !== 'undefined') {
        new Chart(subjectPerformanceCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_data['subject_performance']['labels']) ?>,
                datasets: [{
                    label: 'Average Grade (%)',
                    data: <?= json_encode($chart_data['subject_performance']['data']) ?>,
                    backgroundColor: <?= json_encode($chart_data['subject_performance']['colors']) ?>,
                    borderColor: <?= json_encode(array_map(fn($c) => str_replace('0.8', '1', $c), $chart_data['subject_performance']['colors'])) ?>,
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>
<?php endif; ?>

