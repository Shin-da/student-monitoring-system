<?php
$title = 'My Grades';

$hasSection = $hasSection ?? $has_section ?? false;
$grades = $grades ?? [];
$quarterlyGrades = $quarterlyGrades ?? $quarterly_grades ?? $grades ?? [];
$stats = $stats ?? ['overall_average' => 0, 'passing_subjects' => 0, 'needs_improvement' => 0, 'total_subjects' => 0];
$currentQuarter = $currentQuarter ?? $current_quarter ?? 1;
$currentAcademicYear = $currentAcademicYear ?? $current_academic_year ?? '2024-2025';
$academicYears = $academicYears ?? $academic_years ?? [$currentAcademicYear];
$studentId = $student_id ?? 0;
?>

<!-- Student Grades Header -->
<div class="dashboard-header mb-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h1 class="h3 mb-1 text-primary">My Academic Performance</h1>
      <p class="text-muted mb-0">View your grades by quarter</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= \Helpers\Url::to('/grades/sf10?student_id=' . $studentId . '&quarter=' . $currentQuarter) ?>" 
         class="btn btn-outline-primary" target="_blank">
        <svg class="icon me-2" width="16" height="16" fill="currentColor">
          <use href="#icon-download"></use>
        </svg>
        Download SF10
      </a>
      <a href="<?= \Helpers\Url::to('/grades/sf9?student_id=' . $studentId) ?>" 
         class="btn btn-primary" target="_blank">
        <svg class="icon me-2" width="16" height="16" fill="currentColor">
          <use href="#icon-report"></use>
        </svg>
        Download SF9
      </a>
    </div>
  </div>
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
<?php elseif (empty($quarterlyGrades) && empty($grades)): ?>
<!-- Empty State: No Grades Yet -->
<div class="surface p-5 text-center">
    <svg width="64" height="64" fill="currentColor" class="text-muted mb-3">
        <use href="#icon-chart"></use>
    </svg>
    <h4 class="text-muted mb-2">No grades recorded yet.</h4>
    <p class="text-muted mb-0">Your teacher hasn't posted any grades for this quarter yet.</p>
</div>
<?php else: ?>

<!-- Grade Summary Cards -->
<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="surface p-3 stat-card">
      <div class="d-flex align-items-center">
        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
          <svg class="icon text-primary" width="24" height="24" fill="currentColor">
            <use href="#icon-chart"></use>
          </svg>
        </div>
        <div>
          <div class="h4 fw-bold text-primary mb-0"><?= number_format($stats['overall_average'], 2) ?></div>
          <div class="text-muted small">Overall Average</div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="surface p-3 stat-card">
      <div class="d-flex align-items-center">
        <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
          <svg class="icon text-success" width="24" height="24" fill="currentColor">
            <use href="#icon-star"></use>
          </svg>
        </div>
        <div>
          <div class="h4 fw-bold text-success mb-0"><?= $stats['passing_subjects'] ?></div>
          <div class="text-muted small">Passing Subjects</div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="surface p-3 stat-card">
      <div class="d-flex align-items-center">
        <div class="bg-warning bg-opacity-10 rounded-circle p-3 me-3">
          <svg class="icon text-warning" width="24" height="24" fill="currentColor">
            <use href="#icon-alerts"></use>
          </svg>
        </div>
        <div>
          <div class="h4 fw-bold text-warning mb-0"><?= $stats['needs_improvement'] ?></div>
          <div class="text-muted small">Needs Improvement</div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="surface p-3 stat-card">
      <div class="d-flex align-items-center">
        <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
          <svg class="icon text-info" width="24" height="24" fill="currentColor">
            <use href="#icon-performance"></use>
          </svg>
        </div>
        <div>
          <div class="h4 fw-bold text-info mb-0"><?= $stats['total_subjects'] ?></div>
          <div class="text-muted small">Total Subjects</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Grade Filters -->
<div class="surface p-3 mb-4">
  <form method="get" class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Academic Year</label>
      <select class="form-select" name="academic_year" onchange="this.form.submit()">
        <?php foreach ($academicYears as $year): ?>
          <option value="<?= htmlspecialchars($year) ?>" <?= $year === $currentAcademicYear ? 'selected' : '' ?>>
            <?= htmlspecialchars($year) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Quarter</label>
      <div class="btn-group w-100" role="group">
        <input type="radio" class="btn-check" name="quarter" id="q1" value="1" <?= $currentQuarter === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
        <label class="btn btn-outline-primary" for="q1">Q1</label>
        <input type="radio" class="btn-check" name="quarter" id="q2" value="2" <?= $currentQuarter === 2 ? 'checked' : '' ?> onchange="this.form.submit()">
        <label class="btn btn-outline-primary" for="q2">Q2</label>
        <input type="radio" class="btn-check" name="quarter" id="q3" value="3" <?= $currentQuarter === 3 ? 'checked' : '' ?> onchange="this.form.submit()">
        <label class="btn btn-outline-primary" for="q3">Q3</label>
        <input type="radio" class="btn-check" name="quarter" id="q4" value="4" <?= $currentQuarter === 4 ? 'checked' : '' ?> onchange="this.form.submit()">
        <label class="btn btn-outline-primary" for="q4">Q4</label>
      </div>
    </div>
    <div class="col-md-4">
      <a href="<?= \Helpers\Url::to('/grades/sf10/view?student_id=' . $studentId . '&quarter=' . $currentQuarter) ?>" 
         class="btn btn-outline-secondary w-100" target="_blank">
        <svg class="icon me-2" width="16" height="16" fill="currentColor">
          <use href="#icon-report"></use>
        </svg>
        Print Report Card
      </a>
    </div>
  </form>
</div>

<!-- Grades Table -->
<div class="surface p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Quarter <?= $currentQuarter ?> Grades</h5>
    <div class="text-muted small">Academic Year: <?= htmlspecialchars($currentAcademicYear) ?></div>
  </div>
  
  <?php if (empty($quarterlyGrades)): ?>
    <div class="text-center py-5">
      <svg width="48" height="48" fill="currentColor" class="text-muted mb-3">
        <use href="#icon-chart"></use>
      </svg>
      <h6 class="text-muted">No grades available</h6>
      <p class="text-muted small">Grades will appear here once your teachers have entered them.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-light">
          <tr>
            <th>Subject</th>
            <th class="text-center">Written Work</th>
            <th class="text-center">Performance Task</th>
            <th class="text-center">Quarterly Exam</th>
            <th class="text-center">Attendance</th>
            <th class="text-center">Final Grade</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($quarterlyGrades as $grade): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($grade['subject_name']) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($grade['subject_code'] ?? '') ?></div>
              </td>
              <td class="text-center">
                <?php 
                $wwAvg = $grade['ww_avg'] ?? $grade['ww_average'] ?? null;
                if ($wwAvg !== null && $wwAvg !== ''): ?>
                  <span class="fw-semibold"><?= number_format((float)$wwAvg, 2) ?></span>
                  <?php if (isset($grade['ww_count']) && $grade['ww_count'] > 0): ?>
                    <br><small class="text-muted">(<?= $grade['ww_count'] ?>)</small>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php 
                $ptAvg = $grade['pt_avg'] ?? $grade['pt_average'] ?? null;
                if ($ptAvg !== null && $ptAvg !== ''): ?>
                  <span class="fw-semibold"><?= number_format((float)$ptAvg, 2) ?></span>
                  <?php if (isset($grade['pt_count']) && $grade['pt_count'] > 0): ?>
                    <br><small class="text-muted">(<?= $grade['pt_count'] ?>)</small>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php 
                $qeAvg = $grade['qe_avg'] ?? $grade['qe_average'] ?? null;
                if ($qeAvg !== null && $qeAvg !== ''): ?>
                  <span class="fw-semibold"><?= number_format((float)$qeAvg, 2) ?></span>
                  <?php if (isset($grade['qe_count']) && $grade['qe_count'] > 0): ?>
                    <br><small class="text-muted">(<?= $grade['qe_count'] ?>)</small>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php 
                $attendanceAvg = $grade['attendance_average'] ?? $grade['attendance_avg'] ?? null;
                if ($attendanceAvg !== null && $attendanceAvg !== ''): ?>
                  <span class="fw-semibold"><?= number_format((float)$attendanceAvg, 2) ?>%</span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php 
                $finalGrade = isset($grade['final_grade']) ? (float)$grade['final_grade'] : null;
                if ($finalGrade !== null): ?>
                  <span class="fw-semibold <?= $finalGrade >= 75 ? 'text-success' : 'text-danger' ?>">
                    <?= number_format($finalGrade, 2) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php 
                $status = $grade['status'] ?? null;
                if ($status === 'Passed'): ?>
                  <span class="badge bg-success">Passed</span>
                <?php elseif ($status === 'Failed'): ?>
                  <span class="badge bg-danger">Failed</span>
                <?php elseif ($status): ?>
                  <span class="badge bg-secondary"><?= htmlspecialchars($status) ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<style>
.stat-card {
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
</style>
