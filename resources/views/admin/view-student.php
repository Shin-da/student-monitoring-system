<div class="dashboard-header mb-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-2">
        <a href="<?= \Helpers\Url::to('/admin/students') ?>" class="btn btn-sm btn-outline-secondary">
          <svg width="14" height="14" fill="currentColor">
            <use href="#icon-arrow-left"></use>
          </svg>
        </a>
        <h1 class="h3 fw-bold mb-0">Student Profile</h1>
      </div>
      <p class="text-muted mb-0">Detailed student information and academic records</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary" onclick="window.print()">
        <svg width="16" height="16" fill="currentColor" class="me-1">
          <use href="#icon-printer"></use>
        </svg>
        Print Profile
      </button>
      <a href="<?= \Helpers\Url::to('/admin/create-student?edit=' . $student['id']) ?>" class="btn btn-primary">
        <svg width="16" height="16" fill="currentColor" class="me-1">
          <use href="#icon-edit"></use>
        </svg>
        Edit Profile
      </a>
    </div>
  </div>
</div>

<div class="row">
  <!-- Left Column - Personal Information -->
  <div class="col-lg-4">
    <!-- Basic Info Card -->
    <div class="surface p-4 mb-4">
      <div class="text-center mb-4">
        <div class="avatar-lg mb-3 mx-auto">
          <?= strtoupper(substr($student['first_name'] ?? 'S', 0, 1) . substr($student['last_name'] ?? 'T', 0, 1)) ?>
        </div>
        <h5 class="mb-1">
          <?= htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))) ?>
        </h5>
        <div class="text-muted small mb-2"><?= htmlspecialchars($student['email'] ?? '') ?></div>
        <?php
          $statusClass = match($student['enrollment_status'] ?? 'enrolled') {
            'enrolled' => 'success',
            'transferred' => 'info',
            'dropped' => 'danger',
            'graduated' => 'primary',
            default => 'secondary'
          };
        ?>
        <span class="badge bg-<?= $statusClass ?>-subtle text-<?= $statusClass ?>">
          <?= ucfirst($student['enrollment_status'] ?? 'Enrolled') ?>
        </span>
      </div>

      <div class="border-top pt-3">
        <div class="info-row">
          <span class="info-label">LRN</span>
          <span class="info-value"><?= htmlspecialchars($student['lrn'] ?? 'N/A') ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Student ID</span>
          <span class="info-value">#<?= $student['id'] ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Grade Level</span>
          <span class="info-value">Grade <?= $student['grade_level'] ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Section</span>
          <span class="info-value">
            <?= htmlspecialchars($student['section_name'] ?? 'Unassigned') ?>
            <?php if ($student['section_room']): ?>
              <span class="text-muted">(<?= htmlspecialchars($student['section_room']) ?>)</span>
            <?php endif; ?>
          </span>
        </div>
        <div class="info-row">
          <span class="info-label">School Year</span>
          <span class="info-value"><?= htmlspecialchars($student['school_year'] ?? '') ?></span>
        </div>
      </div>
    </div>

    <!-- Academic Performance Card -->
    <div class="surface p-4 mb-4">
      <h6 class="fw-bold mb-3">
        <svg width="16" height="16" fill="currentColor" class="me-2">
          <use href="#icon-trending-up"></use>
        </svg>
        Academic Performance
      </h6>
      
      <div class="text-center mb-4">
        <div class="display-4 fw-bold <?= $overallGPA >= 75 ? 'text-success' : 'text-danger' ?>">
          <?= number_format($overallGPA, 2) ?>
        </div>
        <div class="text-muted small">Overall Average</div>
      </div>

      <div class="d-flex justify-content-around text-center">
        <div>
          <div class="h5 fw-bold mb-0"><?= count($gradesSummary) ?></div>
          <div class="small text-muted">Subjects</div>
        </div>
        <div class="vr"></div>
        <div>
          <div class="h5 fw-bold mb-0"><?= $attendanceRate ?>%</div>
          <div class="small text-muted">Attendance</div>
        </div>
      </div>
    </div>

    <!-- Attendance Stats Card -->
    <div class="surface p-4 mb-4">
      <h6 class="fw-bold mb-3">
        <svg width="16" height="16" fill="currentColor" class="me-2">
          <use href="#icon-calendar"></use>
        </svg>
        Attendance Statistics
      </h6>
      
      <div class="progress mb-3" style="height: 30px;">
        <?php
          $total = $attendanceStats['total_records'] > 0 ? $attendanceStats['total_records'] : 1;
          $presentPercent = ($attendanceStats['present'] / $total) * 100;
          $latePercent = ($attendanceStats['late'] / $total) * 100;
          $absentPercent = ($attendanceStats['absent'] / $total) * 100;
          $excusedPercent = ($attendanceStats['excused'] / $total) * 100;
        ?>
        <div class="progress-bar bg-success" style="width: <?= $presentPercent ?>%" 
             title="Present: <?= $attendanceStats['present'] ?>"></div>
        <div class="progress-bar bg-warning" style="width: <?= $latePercent ?>%" 
             title="Late: <?= $attendanceStats['late'] ?>"></div>
        <div class="progress-bar bg-danger" style="width: <?= $absentPercent ?>%" 
             title="Absent: <?= $attendanceStats['absent'] ?>"></div>
        <div class="progress-bar bg-info" style="width: <?= $excusedPercent ?>%" 
             title="Excused: <?= $attendanceStats['excused'] ?>"></div>
      </div>

      <div class="row g-2 text-center small">
        <div class="col-6">
          <div class="p-2 rounded bg-success-subtle text-success">
            <div class="fw-bold"><?= $attendanceStats['present'] ?></div>
            <div>Present</div>
          </div>
        </div>
        <div class="col-6">
          <div class="p-2 rounded bg-warning-subtle text-warning">
            <div class="fw-bold"><?= $attendanceStats['late'] ?></div>
            <div>Late</div>
          </div>
        </div>
        <div class="col-6">
          <div class="p-2 rounded bg-danger-subtle text-danger">
            <div class="fw-bold"><?= $attendanceStats['absent'] ?></div>
            <div>Absent</div>
          </div>
        </div>
        <div class="col-6">
          <div class="p-2 rounded bg-info-subtle text-info">
            <div class="fw-bold"><?= $attendanceStats['excused'] ?></div>
            <div>Excused</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Personal Information Card -->
    <div class="surface p-4 mb-4">
      <h6 class="fw-bold mb-3">
        <svg width="16" height="16" fill="currentColor" class="me-2">
          <use href="#icon-user"></use>
        </svg>
        Personal Information
      </h6>
      
      <div class="info-row">
        <span class="info-label">Birth Date</span>
        <span class="info-value">
          <?= $student['birth_date'] ? date('F j, Y', strtotime($student['birth_date'])) : 'Not provided' ?>
        </span>
      </div>
      <div class="info-row">
        <span class="info-label">Gender</span>
        <span class="info-value"><?= ucfirst($student['gender'] ?? 'Not provided') ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Contact Number</span>
        <span class="info-value"><?= htmlspecialchars($student['contact_number'] ?? 'Not provided') ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Address</span>
        <span class="info-value"><?= htmlspecialchars($student['address'] ?? 'Not provided') ?></span>
      </div>
      <?php if ($student['previous_school']): ?>
      <div class="info-row">
        <span class="info-label">Previous School</span>
        <span class="info-value"><?= htmlspecialchars($student['previous_school']) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($student['date_enrolled']): ?>
      <div class="info-row">
        <span class="info-label">Date Enrolled</span>
        <span class="info-value"><?= date('F j, Y', strtotime($student['date_enrolled'])) ?></span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Health Information Card -->
    <?php if ($student['medical_conditions'] || $student['allergies']): ?>
    <div class="surface p-4 mb-4">
      <h6 class="fw-bold mb-3">
        <svg width="16" height="16" fill="currentColor" class="me-2">
          <use href="#icon-heart"></use>
        </svg>
        Health Information
      </h6>
      
      <?php if ($student['medical_conditions']): ?>
      <div class="mb-3">
        <div class="small text-muted fw-semibold mb-1">Medical Conditions</div>
        <div class="text-wrap"><?= nl2br(htmlspecialchars($student['medical_conditions'])) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($student['allergies']): ?>
      <div <?= $student['medical_conditions'] ? 'class="border-top pt-3"' : '' ?>>
        <div class="small text-muted fw-semibold mb-1">Allergies</div>
        <div class="text-wrap"><?= nl2br(htmlspecialchars($student['allergies'])) ?></div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Admin Notes Card (Admin Only) -->
    <?php if (($user['role'] ?? '') === 'admin' && $student['notes']): ?>
    <div class="surface p-4 mb-4 border-warning">
      <h6 class="fw-bold mb-3 text-warning">
        <svg width="16" height="16" fill="currentColor" class="me-2">
          <use href="#icon-file-text"></use>
        </svg>
        Admin Notes (Private)
      </h6>
      <div class="text-wrap"><?= nl2br(htmlspecialchars($student['notes'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Guardian Information Card -->
    <?php if ($student['guardian_name'] || $student['emergency_contact_name']): ?>
    <div class="surface p-4 mb-4">
      <h6 class="fw-bold mb-3">
        <svg width="16" height="16" fill="currentColor" class="me-2">
          <use href="#icon-users"></use>
        </svg>
        Guardian & Emergency Contact
      </h6>
      
      <?php if ($student['guardian_name']): ?>
        <div class="mb-3">
          <div class="small text-muted">Guardian</div>
          <div class="fw-semibold"><?= htmlspecialchars($student['guardian_name']) ?></div>
          <?php if ($student['guardian_contact']): ?>
            <div class="small"><?= htmlspecialchars($student['guardian_contact']) ?></div>
          <?php endif; ?>
          <?php if ($student['guardian_relationship']): ?>
            <div class="small text-muted"><?= ucfirst($student['guardian_relationship']) ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($student['emergency_contact_name']): ?>
        <div class="border-top pt-3">
          <div class="small text-muted">Emergency Contact</div>
          <div class="fw-semibold"><?= htmlspecialchars($student['emergency_contact_name']) ?></div>
          <?php if ($student['emergency_contact_number']): ?>
            <div class="small"><?= htmlspecialchars($student['emergency_contact_number']) ?></div>
          <?php endif; ?>
          <?php if ($student['emergency_contact_relationship']): ?>
            <div class="small text-muted"><?= ucfirst($student['emergency_contact_relationship']) ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right Column - Academic Records -->
  <div class="col-lg-8">
    <!-- Classes/Subjects Card -->
    <div class="surface p-4 mb-4">
      <h5 class="fw-bold mb-3">
        <svg width="20" height="20" fill="currentColor" class="me-2">
          <use href="#icon-book"></use>
        </svg>
        Enrolled Classes
        <span class="badge bg-primary-subtle text-primary ms-2"><?= count($classes) ?></span>
      </h5>

      <?php if (empty($classes)): ?>
        <div class="text-center py-5 text-muted">
          <svg width="48" height="48" fill="currentColor" class="mb-3 opacity-50">
            <use href="#icon-book-open"></use>
          </svg>
          <p class="mb-0">No classes enrolled yet.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Subject</th>
                <th>Section</th>
                <th>Teacher</th>
                <th>Schedule</th>
                <th>Room</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($classes as $class): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($class['subject_name']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($class['subject_code']) ?></div>
                  </td>
                  <td><?= htmlspecialchars($class['section_name']) ?></td>
                  <td>
                    <div><?= htmlspecialchars($class['teacher_name']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($class['teacher_email']) ?></div>
                  </td>
                  <td><small><?= htmlspecialchars($class['schedule'] ?? 'TBA') ?></small></td>
                  <td><small><?= htmlspecialchars($class['room'] ?? 'TBA') ?></small></td>
                  <td class="text-center">
                    <span class="badge bg-<?= $class['enrollment_status'] === 'enrolled' ? 'success' : 'secondary' ?>">
                      <?= ucfirst($class['enrollment_status'] ?? 'enrolled') ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Grades Summary Card -->
    <div class="surface p-4 mb-4">
      <h5 class="fw-bold mb-3">
        <svg width="20" height="20" fill="currentColor" class="me-2">
          <use href="#icon-bar-chart"></use>
        </svg>
        Grades Summary
      </h5>

      <?php if (empty($gradesSummary)): ?>
        <div class="text-center py-5 text-muted">
          <svg width="48" height="48" fill="currentColor" class="mb-3 opacity-50">
            <use href="#icon-file-text"></use>
          </svg>
          <p class="mb-0">No grades recorded yet.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Subject</th>
                <th class="text-center">Average Grade</th>
                <th class="text-center">Total Grades</th>
                <th class="text-center">Remarks</th>
                <th>Last Graded</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($gradesSummary as $grade): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($grade['subject_name']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($grade['subject_code']) ?></div>
                  </td>
                  <td class="text-center">
                    <span class="badge <?= $grade['average_grade'] >= 75 ? 'bg-success' : 'bg-danger' ?> fs-6">
                      <?= number_format($grade['average_grade'], 2) ?>
                    </span>
                  </td>
                  <td class="text-center"><?= $grade['total_grades'] ?></td>
                  <td class="text-center">
                    <?php if ($grade['average_grade'] >= 90): ?>
                      <span class="badge bg-success">Outstanding</span>
                    <?php elseif ($grade['average_grade'] >= 85): ?>
                      <span class="badge bg-info">Very Satisfactory</span>
                    <?php elseif ($grade['average_grade'] >= 80): ?>
                      <span class="badge bg-primary">Satisfactory</span>
                    <?php elseif ($grade['average_grade'] >= 75): ?>
                      <span class="badge bg-warning">Fairly Satisfactory</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Needs Improvement</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <small><?= $grade['last_graded'] ? date('M j, Y', strtotime($grade['last_graded'])) : 'N/A' ?></small>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Adviser Information (if exists) -->
    <?php if ($student['adviser_name']): ?>
    <div class="surface p-4 mb-4">
      <h6 class="fw-bold mb-3">
        <svg width="16" height="16" fill="currentColor" class="me-2">
          <use href="#icon-user-check"></use>
        </svg>
        Adviser
      </h6>
      
      <div class="d-flex align-items-center">
        <div class="avatar me-3">
          <?= strtoupper(substr($student['adviser_name'], 0, 1)) ?>
        </div>
        <div>
          <div class="fw-semibold"><?= htmlspecialchars($student['adviser_name']) ?></div>
          <div class="small text-muted"><?= htmlspecialchars($student['adviser_email'] ?? '') ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<style>
@media print {
  .btn, .dashboard-header a, nav, aside {
    display: none !important;
  }
}

.surface {
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
}

.avatar-lg {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--bs-primary), var(--bs-info));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 2rem;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--bs-primary-bg-subtle);
  color: var(--bs-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--bs-border-color);
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  color: var(--bs-secondary-color);
  font-size: 0.875rem;
}

.info-value {
  font-weight: 500;
  text-align: right;
}

.progress {
  border-radius: 1rem;
}

.vr {
  opacity: 0.25;
}
</style>

