<?php
declare(strict_types=1);

$alerts = $alerts ?? [];
$ai_analysis = $ai_analysis ?? null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">
      <svg width="24" height="24" fill="currentColor" class="me-2">
        <use href="#icon-alerts"></use>
      </svg>
      My Alerts & Performance Insights
    </h1>
    <p class="text-muted mb-0">AI-powered alerts and analytics for your academic performance.</p>
  </div>
</div>

<!-- AI Performance Overview Card -->
<?php if (!empty($ai_analysis)): ?>
<div class="surface p-4 mb-4 border-start border-4 <?= 
  $ai_analysis['risk_level'] === 'high' ? 'border-danger' : 
  ($ai_analysis['risk_level'] === 'medium' ? 'border-warning' : 'border-success') 
?>">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center">
      <div class="me-3">
        <div class="ai-glow-icon">
          <svg width="32" height="32" fill="currentColor" class="text-<?= 
            $ai_analysis['risk_level'] === 'high' ? 'danger' : 
            ($ai_analysis['risk_level'] === 'medium' ? 'warning' : 'success') 
          ?>">
            <use href="#icon-chart"></use>
          </svg>
        </div>
      </div>
      <div>
        <h2 class="h5 fw-bold mb-0">AI Performance Analysis</h2>
        <div class="text-muted small">Powered by intelligent analytics</div>
      </div>
    </div>
    <span class="badge bg-<?= 
      $ai_analysis['risk_level'] === 'high' ? 'danger' : 
      ($ai_analysis['risk_level'] === 'medium' ? 'warning' : 'success') 
    ?> px-3 py-2">
      <?= strtoupper($ai_analysis['risk_level'] ?? 'low') ?> RISK
    </span>
  </div>
  
  <div class="row g-4">
    <div class="col-12 col-md-6">
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted small fw-semibold">Overall Risk Score</span>
          <span class="h4 fw-bold mb-0 text-<?= 
            $ai_analysis['risk_level'] === 'high' ? 'danger' : 
            ($ai_analysis['risk_level'] === 'medium' ? 'warning' : 'success') 
          ?>"><?= number_format($ai_analysis['overall_risk_score'] ?? 0, 1) ?>/100</span>
        </div>
        <div class="progress ai-progress" style="height: 12px; border-radius: 6px; overflow: hidden;">
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
            ⚡ Monitor closely and seek support
          <?php else: ?>
            ✅ Performance is on track
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <div class="col-12 col-md-6">
      <div class="row g-3">
        <div class="col-6">
          <div class="text-center p-3 bg-light rounded">
            <div class="h3 fw-bold mb-0 text-<?= 
              ($ai_analysis['failing_subjects'] ?? 0) > 0 ? 'danger' : 'success' 
            ?>"><?= $ai_analysis['failing_subjects'] ?? 0 ?></div>
            <div class="text-muted small">Failing Subjects</div>
          </div>
        </div>
        <div class="col-6">
          <div class="text-center p-3 bg-light rounded">
            <div class="h3 fw-bold mb-0 text-<?= 
              count($ai_analysis['at_risk_subjects'] ?? []) > 0 ? 'warning' : 'success' 
            ?>"><?= count($ai_analysis['at_risk_subjects'] ?? []) ?></div>
            <div class="text-muted small">At-Risk Subjects</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <?php if (!empty($ai_analysis['at_risk_subjects'])): ?>
  <div class="mt-4">
    <h3 class="h6 fw-semibold mb-3">At-Risk Subjects Analysis with AI Predictions</h3>
    <div class="row g-3">
      <?php foreach ($ai_analysis['at_risk_subjects'] as $subject): ?>
      <div class="col-12 col-md-6">
        <div class="p-3 border rounded <?= 
          $subject['risk_level'] === 'high' ? 'border-danger bg-danger bg-opacity-10' : 
          'border-warning bg-warning bg-opacity-10' 
        ?>">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1">
              <div class="fw-bold"><?= htmlspecialchars($subject['subject_name'] ?? 'Subject') ?></div>
              <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                <div class="text-muted small">Current: <strong><?= number_format($subject['final_grade'] ?? 0, 1) ?>%</strong></div>
                <?php if (!empty($subject['prediction']) && $subject['prediction']['predicted_grade'] !== null): ?>
                <div class="text-muted small">•</div>
                <div class="small fw-bold text-<?= 
                  $subject['prediction']['predicted_grade'] < 75 ? 'danger' : 'success' 
                ?>">
                  AI Projected: <?= number_format($subject['prediction']['predicted_grade'], 1) ?>%
                </div>
                <div class="text-muted small">
                  (<?= number_format($subject['prediction']['confidence'], 0) ?>% confidence)
                </div>
                <?php endif; ?>
              </div>
            </div>
            <span class="badge bg-<?= $subject['risk_level'] === 'high' ? 'danger' : 'warning' ?>">
              <?= ucfirst($subject['risk_level']) ?>
            </span>
          </div>
          
          <!-- Trend Indicator -->
          <?php if (!empty($subject['prediction'])): ?>
          <div class="mb-2">
            <?php if ($subject['prediction']['trend'] === 'improving'): ?>
              <span class="badge bg-success">
                <svg width="14" height="14" fill="currentColor" class="me-1">
                  <use href="#icon-arrow-up"></use>
                </svg>
                Improving Trend
              </span>
            <?php elseif ($subject['prediction']['trend'] === 'declining'): ?>
              <span class="badge bg-danger">
                <svg width="14" height="14" fill="currentColor" class="me-1">
                  <use href="#icon-arrow-down"></use>
                </svg>
                Declining Trend
              </span>
            <?php else: ?>
              <span class="badge bg-secondary">Stable Performance</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          
          <?php if (!empty($subject['reasons'])): ?>
          <div class="small mb-2">
            <strong>Concerns:</strong>
            <ul class="mb-0 mt-1">
              <?php foreach (array_slice($subject['reasons'], 0, 2) as $reason): ?>
              <li><?= htmlspecialchars($reason) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
          
          <!-- Prediction Message -->
          <?php if (!empty($subject['prediction']['message'])): ?>
          <div class="alert alert-sm alert-<?= 
            $subject['prediction']['predicted_grade'] < 75 ? 'danger' : 'info' 
          ?> mb-0 mt-2">
            <small><strong>AI Insight:</strong> <?= htmlspecialchars($subject['prediction']['message']) ?></small>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  
  <?php if (!empty($ai_analysis['attendance_analysis']) && ($ai_analysis['attendance_analysis']['status'] ?? 'good') !== 'good'): ?>
  <div class="mt-4">
    <div class="alert alert-<?= ($ai_analysis['attendance_analysis']['status'] ?? 'good') === 'poor' ? 'danger' : 'warning' ?> d-flex align-items-center">
      <svg width="24" height="24" fill="currentColor" class="me-2">
        <use href="#icon-calendar"></use>
      </svg>
      <div>
        <strong>Attendance Concern:</strong> 
        Your attendance is <?= number_format($ai_analysis['attendance_analysis']['percentage'] ?? 100, 1) ?>%
        (<?= $ai_analysis['attendance_analysis']['present_days'] ?? 0 ?>/<?= $ai_analysis['attendance_analysis']['total_days'] ?? 0 ?> days present)
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Alerts List -->
<div class="surface p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="h5 fw-bold mb-1">Active Alerts</h2>
      <p class="text-muted small mb-0">AI-generated alerts based on your academic performance</p>
    </div>
    <?php if (!empty($alerts)): ?>
    <span class="badge bg-warning px-3 py-2"><?= count($alerts) ?> Active</span>
    <?php endif; ?>
  </div>
  
  <?php if (empty($alerts)): ?>
    <div class="text-center py-5">
      <svg width="64" height="64" fill="currentColor" class="text-muted mb-3">
        <use href="#icon-check-circle"></use>
      </svg>
      <h4 class="text-muted mb-2">No Active Alerts</h4>
      <p class="text-muted small mb-0">Great job! You're performing well. Keep up the good work!</p>
    </div>
  <?php else: ?>
    <div class="list-group">
      <?php foreach ($alerts as $alert): ?>
      <div class="list-group-item border-0 p-4 mb-3 rounded shadow-sm ai-alert-card">
        <div class="d-flex align-items-start">
          <div class="me-3">
            <div class="ai-alert-icon bg-<?= 
              ($alert['severity'] ?? 'medium') === 'high' ? 'danger' : 
              (($alert['severity'] ?? 'medium') === 'medium' ? 'warning' : 'info') 
            ?> bg-opacity-10 rounded-circle p-3">
              <svg width="24" height="24" fill="currentColor" class="text-<?= 
                ($alert['severity'] ?? 'medium') === 'high' ? 'danger' : 
                (($alert['severity'] ?? 'medium') === 'medium' ? 'warning' : 'info') 
              ?>">
                <use href="#icon-alerts"></use>
              </svg>
            </div>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <h3 class="h6 fw-bold mb-0"><?= htmlspecialchars($alert['title'] ?? 'Alert') ?></h3>
                  <span class="badge bg-<?= 
                    ($alert['severity'] ?? 'medium') === 'high' ? 'danger' : 
                    (($alert['severity'] ?? 'medium') === 'medium' ? 'warning' : 'info') 
                  ?>"><?= ucfirst($alert['severity'] ?? 'medium') ?> Priority</span>
                  <span class="badge bg-secondary">AI-Generated</span>
                </div>
                <div class="text-muted small">
                  <?= isset($alert['created_at']) ? date('F d, Y \a\t g:i A', strtotime($alert['created_at'])) : 'Unknown time' ?>
                </div>
              </div>
            </div>
            <div class="mb-3">
              <p class="mb-0"><?= htmlspecialchars($alert['description'] ?? '') ?></p>
            </div>
            <?php if (!empty($alert['subject_name'])): ?>
            <div class="d-flex align-items-center gap-3 mb-2">
              <span class="text-muted small">
                <svg width="16" height="16" fill="currentColor" class="me-1">
                  <use href="#icon-book"></use>
                </svg>
                Subject: <strong><?= htmlspecialchars($alert['subject_name']) ?></strong>
              </span>
            </div>
            <?php endif; ?>
            <div class="mt-3">
              <a href="<?= \Helpers\Url::to('/student/grades' . (!empty($alert['subject_id']) ? '?subject=' . $alert['subject_id'] : '')) ?>" 
                 class="btn btn-sm btn-primary">
                <svg width="16" height="16" fill="currentColor" class="me-1">
                  <use href="#icon-chart"></use>
                </svg>
                View Grades
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<style>
/* AI-Themed Styles */
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
}

.ai-alert-card {
  transition: all 0.3s ease;
  border-left: 4px solid;
}

.ai-alert-card:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.ai-alert-icon {
  animation: aiGlow 2s ease-in-out infinite;
}

@keyframes aiGlow {
  0%, 100% {
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
  }
  50% {
    box-shadow: 0 0 15px rgba(0, 123, 255, 0.6);
  }
}

[data-theme="dark"] .ai-alert-card {
  background: rgba(255, 255, 255, 0.05);
}

[data-theme="dark"] .ai-progress {
  background: rgba(255, 255, 255, 0.1);
}
</style>

