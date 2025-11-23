<div class="dashboard-header mb-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h1 class="h3 fw-bold mb-1">Students</h1>
      <p class="text-muted mb-0">Search and manage student records</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= \Helpers\Url::to('/admin/create-student') ?>" class="btn btn-primary">
        <svg width="16" height="16" fill="currentColor" class="me-1">
          <use href="#icon-plus"></use>
        </svg>
        Register New Student
      </a>
    </div>
  </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="surface p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-muted small">Total Students</div>
          <div class="h4 fw-bold mb-0"><?= number_format($stats['total_students'] ?? 0) ?></div>
        </div>
        <div class="icon-box bg-primary-subtle text-primary">
          <svg width="24" height="24" fill="currentColor">
            <use href="#icon-users"></use>
          </svg>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="surface p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-muted small">Enrolled</div>
          <div class="h4 fw-bold mb-0 text-success"><?= number_format($stats['enrolled'] ?? 0) ?></div>
        </div>
        <div class="icon-box bg-success-subtle text-success">
          <svg width="24" height="24" fill="currentColor">
            <use href="#icon-check-circle"></use>
          </svg>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="surface p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-muted small">Unassigned</div>
          <div class="h4 fw-bold mb-0 text-warning"><?= number_format($stats['unassigned'] ?? 0) ?></div>
        </div>
        <div class="icon-box bg-warning-subtle text-warning">
          <svg width="24" height="24" fill="currentColor">
            <use href="#icon-alert-circle"></use>
          </svg>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="surface p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-muted small">Transferred/Dropped</div>
          <div class="h4 fw-bold mb-0 text-muted"><?= number_format(($stats['transferred'] ?? 0) + ($stats['dropped'] ?? 0)) ?></div>
        </div>
        <div class="icon-box bg-secondary-subtle text-secondary">
          <svg width="24" height="24" fill="currentColor">
            <use href="#icon-user-x"></use>
          </svg>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Search and Filters -->
<div class="surface p-4 mb-4">
  <form method="get" action="<?= \Helpers\Url::to('/admin/students') ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label small text-muted">Search</label>
        <input type="text" class="form-control" name="search" 
               placeholder="Search by name, LRN, or email..." 
               value="<?= htmlspecialchars($search ?? '') ?>">
      </div>
      
      <div class="col-md-2">
        <label class="form-label small text-muted">Grade Level</label>
        <select class="form-select" name="grade">
          <option value="">All Grades</option>
          <?php for ($i = 7; $i <= 12; $i++): ?>
            <option value="<?= $i ?>" <?= ($gradeFilter ?? '') == $i ? 'selected' : '' ?>>
              Grade <?= $i ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      
      <div class="col-md-3">
        <label class="form-label small text-muted">Section</label>
        <select class="form-select" name="section">
          <option value="">All Sections</option>
          <?php foreach ($sections as $section): ?>
            <option value="<?= $section['id'] ?>" <?= ($sectionFilter ?? '') == $section['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($section['name']) ?> (Grade <?= $section['grade_level'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="col-md-2">
        <label class="form-label small text-muted">Status</label>
        <select class="form-select" name="status">
          <option value="">All Status</option>
          <option value="enrolled" <?= ($statusFilter ?? '') === 'enrolled' ? 'selected' : '' ?>>Enrolled</option>
          <option value="transferred" <?= ($statusFilter ?? '') === 'transferred' ? 'selected' : '' ?>>Transferred</option>
          <option value="dropped" <?= ($statusFilter ?? '') === 'dropped' ? 'selected' : '' ?>>Dropped</option>
          <option value="graduated" <?= ($statusFilter ?? '') === 'graduated' ? 'selected' : '' ?>>Graduated</option>
        </select>
      </div>
      
      <div class="col-md-1 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">
          <svg width="16" height="16" fill="currentColor">
            <use href="#icon-search"></use>
          </svg>
        </button>
      </div>
    </div>
  </form>
</div>

<!-- Students Table -->
<div class="surface p-0">
  <?php if (empty($students)): ?>
    <div class="p-5 text-center text-muted">
      <svg width="48" height="48" fill="currentColor" class="mb-3 opacity-50">
        <use href="#icon-users"></use>
      </svg>
      <p class="mb-0">No students found.</p>
      <?php if (!empty($search) || $gradeFilter || $sectionFilter || $statusFilter): ?>
        <a href="<?= \Helpers\Url::to('/admin/students') ?>" class="btn btn-sm btn-outline-secondary mt-2">
          Clear Filters
        </a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Student</th>
            <th>LRN</th>
            <th>Grade & Section</th>
            <th>Email</th>
            <th class="text-center">Avg. Grade</th>
            <th class="text-center">Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <?= strtoupper(substr($student['first_name'] ?? 'S', 0, 1)) ?>
                  </div>
                  <div>
                    <div class="fw-semibold">
                      <?= htmlspecialchars($student['full_name'] ?? ($student['first_name'] . ' ' . $student['last_name'])) ?>
                    </div>
                    <div class="small text-muted"><?= htmlspecialchars($student['school_year'] ?? '') ?></div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge bg-secondary-subtle text-secondary">
                  <?= htmlspecialchars($student['lrn'] ?? 'N/A') ?>
                </span>
              </td>
              <td>
                <div>Grade <?= $student['grade_level'] ?? 'N/A' ?></div>
                <div class="small text-muted">
                  <?= htmlspecialchars($student['section_name'] ?? 'Unassigned') ?>
                  <?php if ($student['room']): ?>
                    <span class="text-muted">• <?= htmlspecialchars($student['room']) ?></span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <small><?= htmlspecialchars($student['email'] ?? '') ?></small>
              </td>
              <td class="text-center">
                <?php if ($student['avg_grade']): ?>
                  <span class="badge <?= $student['avg_grade'] >= 75 ? 'bg-success' : 'bg-danger' ?>">
                    <?= number_format($student['avg_grade'], 1) ?>
                  </span>
                  <div class="small text-muted"><?= $student['total_grades'] ?> grades</div>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php
                  $statusClass = match($student['enrollment_status'] ?? 'enrolled') {
                    'enrolled' => 'success',
                    'transferred' => 'info',
                    'dropped' => 'danger',
                    'graduated' => 'primary',
                    default => 'secondary'
                  };
                  $statusIcon = match($student['enrollment_status'] ?? 'enrolled') {
                    'enrolled' => 'check-circle',
                    'transferred' => 'arrow-right-circle',
                    'dropped' => 'x-circle',
                    'graduated' => 'award',
                    default => 'circle'
                  };
                ?>
                <span class="badge bg-<?= $statusClass ?>-subtle text-<?= $statusClass ?>">
                  <svg width="12" height="12" fill="currentColor" class="me-1">
                    <use href="#icon-<?= $statusIcon ?>"></use>
                  </svg>
                  <?= ucfirst($student['enrollment_status'] ?? 'enrolled') ?>
                </span>
              </td>
              <td class="text-center">
                <div class="btn-group btn-group-sm">
                  <a href="<?= \Helpers\Url::to('/admin/view-student?id=' . $student['id']) ?>" 
                     class="btn btn-outline-primary" title="View Profile">
                    <svg width="14" height="14" fill="currentColor">
                      <use href="#icon-eye"></use>
                    </svg>
                  </a>
                  <a href="<?= \Helpers\Url::to('/admin/create-student?edit=' . $student['id']) ?>" 
                     class="btn btn-outline-secondary" title="Edit">
                    <svg width="14" height="14" fill="currentColor">
                      <use href="#icon-edit"></use>
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    
    <div class="p-3 border-top text-muted small">
      Showing <?= count($students) ?> student<?= count($students) !== 1 ? 's' : '' ?>
      <?php if (!empty($search) || $gradeFilter || $sectionFilter || $statusFilter): ?>
        <a href="<?= \Helpers\Url::to('/admin/students') ?>" class="ms-3">Clear all filters</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<style>
.surface {
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
}

.icon-box {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
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

.avatar-sm {
  width: 32px;
  height: 32px;
  font-size: 0.75rem;
}

tbody tr {
  transition: background-color 0.2s;
}

tbody tr:hover {
  background-color: var(--bs-secondary-bg);
}
</style>

