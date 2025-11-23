<?php
$title = 'Child\'s Profile';
$student_data = $student ?? $student_data ?? [];
$academic_stats = $academic_stats ?? [
    'overall_average' => 0,
    'passing_subjects' => 0,
    'total_subjects' => 0,
    'improvement' => 0,
];
$subjects = $subjects ?? [];
$attendance = $attendance ?? [];
$attendancePercentage = $attendancePercentage ?? 0;
?>

<!-- Parent Profile Header -->
<div class="dashboard-header">
  <div>
    <h1 class="h3 mb-1 text-primary">Child's Profile</h1>
    <p class="text-muted mb-0">View your child's information and academic performance</p>
  </div>
</div>

<!-- Profile Overview -->
<div class="row g-4 mb-4">
  <div class="col-lg-4">
    <div class="surface p-4">
      <div class="text-center">
        <div class="position-relative d-inline-block mb-3">
          <?php if ($student_data['profile_picture'] ?? null): ?>
            <img src="<?= \Helpers\Url::basePath() . $student_data['profile_picture'] ?>" alt="Profile Picture" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
          <?php else: ?>
            <div class="bg-primary bg-opacity-10 rounded-circle p-4" style="width: 120px; height: 120px;">
              <svg class="icon text-primary" width="48" height="48" fill="currentColor">
                <use href="#icon-user"></use>
              </svg>
            </div>
          <?php endif; ?>
        </div>
        <h5 class="mb-1"><?= htmlspecialchars($student_data['full_name'] ?? $student_data['name'] ?? 'Student Name') ?></h5>
        <p class="text-muted mb-2">
          <?php
            $grade = $student_data['grade_level'] ?? '';
            $section = $student_data['section_name'] ?? '';
            if ($grade && $section) {
              echo 'Grade ' . htmlspecialchars($grade) . ' - ' . htmlspecialchars($section);
            } elseif ($grade) {
              echo 'Grade ' . htmlspecialchars($grade);
            } elseif ($section) {
              echo htmlspecialchars($section);
            }
          ?>
        </p>
        <p class="text-muted small mb-0">LRN: <?= htmlspecialchars($student_data['lrn'] ?? 'N/A') ?></p>
      </div>
    </div>
  </div>
  
  <div class="col-lg-8">
    <div class="row g-3">
      <!-- Academic Stats -->
      <div class="col-md-6">
        <div class="surface p-3 stat-card">
          <div class="d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
              <svg class="icon text-primary" width="24" height="24" fill="currentColor">
                <use href="#icon-chart"></use>
              </svg>
            </div>
            <div>
              <div class="h4 fw-bold text-primary mb-0"><?= number_format($academic_stats['overall_average'], 2) ?></div>
              <div class="text-muted small">Overall Average</div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="surface p-3 stat-card">
          <div class="d-flex align-items-center">
            <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
              <svg class="icon text-success" width="24" height="24" fill="currentColor">
                <use href="#icon-star"></use>
              </svg>
            </div>
            <div>
              <div class="h4 fw-bold text-success mb-0"><?= $academic_stats['passing_subjects'] ?> / <?= $academic_stats['total_subjects'] ?></div>
              <div class="text-muted small">Passing Subjects</div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="surface p-3 stat-card">
          <div class="d-flex align-items-center">
            <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
              <svg class="icon text-info" width="24" height="24" fill="currentColor">
                <use href="#icon-calendar"></use>
              </svg>
            </div>
            <div>
              <div class="h4 fw-bold text-info mb-0"><?= number_format($attendancePercentage, 1) ?>%</div>
              <div class="text-muted small">Attendance Rate</div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="surface p-3 stat-card">
          <div class="d-flex align-items-center">
            <div class="bg-warning bg-opacity-10 rounded-circle p-3 me-3">
              <svg class="icon text-warning" width="24" height="24" fill="currentColor">
                <use href="#icon-performance"></use>
              </svg>
            </div>
            <div>
              <div class="h4 fw-bold text-warning mb-0"><?= $academic_stats['total_subjects'] ?></div>
              <div class="text-muted small">Total Subjects</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Student Information -->
<div class="surface p-4 mb-4">
  <h5 class="mb-3">Student Information</h5>
  <div class="row g-3">
    <div class="col-md-6">
      <div class="mb-3">
        <label class="form-label text-muted small">Full Name</label>
        <div class="fw-semibold"><?= htmlspecialchars($student_data['full_name'] ?? $student_data['name'] ?? 'N/A') ?></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label class="form-label text-muted small">LRN</label>
        <div class="fw-semibold"><?= htmlspecialchars($student_data['lrn'] ?? 'N/A') ?></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label class="form-label text-muted small">Email</label>
        <div class="fw-semibold"><?= htmlspecialchars($student_data['email'] ?? 'N/A') ?></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label class="form-label text-muted small">Section</label>
        <div class="fw-semibold"><?= htmlspecialchars($student_data['section_name'] ?? 'Unassigned') ?></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label class="form-label text-muted small">Grade Level</label>
        <div class="fw-semibold"><?= htmlspecialchars($student_data['grade_level'] ?? 'N/A') ?></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label class="form-label text-muted small">School Year</label>
        <div class="fw-semibold"><?= htmlspecialchars($student_data['school_year'] ?? 'N/A') ?></div>
      </div>
    </div>
    <?php if ($student_data['adviser_name'] ?? null): ?>
    <div class="col-md-6">
      <div class="mb-3">
        <label class="form-label text-muted small">Adviser</label>
        <div class="fw-semibold"><?= htmlspecialchars($student_data['adviser_name']) ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Subjects -->
<?php if (!empty($subjects)): ?>
<div class="surface p-4">
  <h5 class="mb-3">Enrolled Subjects</h5>
  <div class="row g-2">
    <?php foreach ($subjects as $subject): ?>
      <div class="col-md-6 col-lg-4">
        <div class="p-3 border rounded">
          <div class="fw-semibold"><?= htmlspecialchars($subject['name']) ?></div>
          <?php if (!empty($subject['code'])): ?>
            <div class="text-muted small"><?= htmlspecialchars($subject['code']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
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

