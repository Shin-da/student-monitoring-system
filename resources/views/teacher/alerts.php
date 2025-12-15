<?php /** @var array $alerts */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">
      <div class="d-flex align-items-center">
        <div class="ai-glow-icon me-2">
          <svg width="28" height="28" fill="currentColor" class="text-warning">
            <use href="#icon-alerts"></use>
          </svg>
        </div>
        <div>
          <div>AI-Generated Performance Alerts</div>
          <div class="text-muted small fw-normal">Intelligent early warning system for at-risk students</div>
        </div>
      </div>
    </h1>
  </div>
  <?php if (!empty($alerts)): ?>
  <span class="badge bg-warning px-3 py-2">
    <?= count(array_filter($alerts, fn($a) => ($a['status'] ?? 'active') === 'active')) ?> Active
  </span>
  <?php endif; ?>
</div>

<div class="table-surface p-3">
  <div class="row g-2 align-items-center filters mb-2">
    <div class="col">
      <input class="form-control" placeholder="Search by student or remarks...">
    </div>
    <div class="col-auto">
      <select class="form-select">
        <option>All Subjects</option>
        <option>Math</option>
        <option>History</option>
      </select>
    </div>
    <div class="col-auto">
      <select class="form-select">
        <option>All Types</option>
        <option selected>Warning</option>
        <option>Info</option>
      </select>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th scope="col">Date</th>
          <th scope="col">Student Name</th>
          <th scope="col">Subject</th>
          <th scope="col">Alert Type</th>
          <th scope="col">Remarks</th>
          <th scope="col" class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($alerts)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-5">
              <div class="ai-empty-state">
                <svg width="64" height="64" fill="currentColor" class="text-success mb-3">
                  <use href="#icon-check-circle"></use>
                </svg>
                <h4 class="text-muted mb-2">No Active Alerts</h4>
                <p class="text-muted small mb-0">All students are performing well. Alerts will appear here automatically when AI detects at-risk students.</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($alerts as $alert): ?>
            <tr class="ai-alert-row">
              <td>
                <div class="fw-semibold small"><?= isset($alert['created_at']) ? date('M d, Y', strtotime($alert['created_at'])) : 'N/A' ?></div>
                <div class="text-muted small"><?= isset($alert['created_at']) ? date('g:i A', strtotime($alert['created_at'])) : '' ?></div>
              </td>
              <td>
                <div class="fw-bold"><?= htmlspecialchars($alert['student_name'] ?? 'Unknown') ?></div>
                <div class="text-muted small"><?= htmlspecialchars($alert['section_name'] ?? 'N/A') ?></div>
              </td>
              <td>
                <?php if (!empty($alert['subject_name'])): ?>
                <div class="fw-semibold"><?= htmlspecialchars($alert['subject_name']) ?></div>
                <?php else: ?>
                <span class="badge bg-secondary">Overall</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                $severity = $alert['severity'] ?? 'medium';
                $badgeClass = match($severity) {
                  'high' => 'badge bg-danger',
                  'medium' => 'badge bg-warning',
                  'low' => 'badge bg-info',
                  default => 'badge bg-secondary'
                };
                ?>
                <div class="d-flex align-items-center gap-2">
                  <span class="<?= $badgeClass ?>"><?= ucfirst($severity) ?></span>
                  <span class="badge bg-dark">AI</span>
                </div>
              </td>
              <td>
                <div class="fw-semibold small mb-1"><?= htmlspecialchars($alert['title'] ?? 'Alert') ?></div>
                <div class="text-muted small"><?= htmlspecialchars($alert['description'] ?? '') ?></div>
              </td>
              <td class="text-end">
                <?php if (($alert['status'] ?? 'active') === 'active'): ?>
                  <button class="btn btn-sm btn-primary" onclick="resolveAlert(<?= $alert['id'] ?>)">
                    <svg width="14" height="14" fill="currentColor" class="me-1">
                      <use href="#icon-check"></use>
                    </svg>
                    Resolve
                  </button>
                <?php else: ?>
                  <span class="badge bg-success">
                    <svg width="12" height="12" fill="currentColor" class="me-1">
                      <use href="#icon-check-circle"></use>
                    </svg>
                    Resolved
                  </span>
                  <div class="text-muted small mt-1">
                    <?= isset($alert['resolved_at']) ? date('M d', strtotime($alert['resolved_at'])) : '' ?>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>


