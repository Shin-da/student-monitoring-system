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
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h2 class="h5 fw-semibold mb-1"><?= htmlspecialchars($child['student_name'] ?? 'Student') ?></h2>
                <div class="text-muted small">LRN: <?= htmlspecialchars($child['lrn'] ?? '') ?></div>
                <div class="text-muted small">Grade <?= htmlspecialchars((string)($child['grade_level'] ?? '')) ?> • Section <?= htmlspecialchars($child['section_name'] ?? 'Unassigned') ?></div>
            </div>
            <div class="d-flex flex-column flex-md-row gap-2 align-items-md-center">
                <span class="badge bg-light text-muted">Relationship: <?= htmlspecialchars(ucfirst($relationship)) ?></span>
                <?php 
                $studentId = $child['id'] ?? 0;
                $currentAcademicYear = date('Y') . '-' . (date('Y') + 1); // Default to current academic year
                $currentQuarter = 1; // Default to first quarter
                ?>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= \Helpers\Url::to('/grades/sf10?student_id=' . $studentId . '&quarter=' . $currentQuarter . '&academic_year=' . urlencode($currentAcademicYear)) ?>" 
                       class="btn btn-sm btn-outline-primary" target="_blank" title="Download Report Card">
                        <svg class="icon me-1" width="14" height="14" fill="currentColor">
                            <use href="#icon-download"></use>
                        </svg>
                        Download SF10
                    </a>
                    <a href="<?= \Helpers\Url::to('/grades/sf9?student_id=' . $studentId . '&academic_year=' . urlencode($currentAcademicYear)) ?>" 
                       class="btn btn-sm btn-primary" target="_blank" title="Download Permanent Record">
                        <svg class="icon me-1" width="14" height="14" fill="currentColor">
                            <use href="#icon-download"></use>
                        </svg>
                        Download SF9
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($child): ?>
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
                <h2 class="h6 fw-bold mb-0">Child's Performance Analytics</h2>
                <div class="text-muted small">AI-powered insights</div>
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
                ⚠️ Immediate support recommended
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
                        <span class="badge bg-success">↑</span>
                      <?php elseif ($subject['prediction']['trend'] === 'declining'): ?>
                        <span class="badge bg-danger">↓</span>
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
                Attendance Pattern Detection
              </div>
              <?php if (!empty($patternAnalysis['overall_assessment']['severity']) && 
                        $patternAnalysis['overall_assessment']['severity'] !== 'low'): ?>
              <span class="badge bg-<?= 
                $patternAnalysis['overall_assessment']['severity'] === 'high' ? 'danger' : 'warning' 
              ?>"><?= ucfirst($patternAnalysis['overall_assessment']['severity']) ?></span>
              <?php endif; ?>
            </div>
            <div class="small">
              <?php foreach (array_slice($patterns, 0, 2) as $pattern): ?>
              <div class="mb-1">
                <strong><?= htmlspecialchars($pattern['description']) ?></strong>
                <?php if (!empty($pattern['details']['day'])): ?>
                <div class="text-muted small mt-1">
                  Pattern: Frequently absent on <?= htmlspecialchars($pattern['details']['day']) ?>s
                </div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
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
                <div class="text-muted small">Real-time monitoring</div>
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
              <div class="text-muted small">No active alerts. Your child is doing well! 🎉</div>
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
              <a href="<?= \Helpers\Url::to('/parent/grades') ?>" class="btn btn-sm btn-primary">
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

<style>
/* AI-Themed Styles for Parent Dashboard */
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
<!-- Performance Charts Section -->
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
            Child's Grade Trends (Quarterly)
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
            Child's Attendance Trends
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
            Child's Subject Performance (Current Quarter)
          </h3>
        </div>
        <div class="chart-container" style="height: 300px; position: relative;">
          <canvas id="subjectPerformanceChart"></canvas>
        </div>
      </div>
    </div>
    <?php endif; ?>
</div>

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
                        }
                    },
                    x: {
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
<?php endif; ?>

