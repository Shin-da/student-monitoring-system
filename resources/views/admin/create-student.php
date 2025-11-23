<div class="dashboard-header mb-4">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <h1 class="h3 fw-bold mb-1">Register New Student</h1>
      <p class="text-muted mb-0">Add a new student to the system with comprehensive information</p>
    </div>
    <div>
      <a href="<?= \Helpers\Url::to('/admin/users') ?>" class="btn btn-outline-secondary btn-sm">
        <svg width="16" height="16" fill="currentColor">
          <use href="#icon-arrow-left"></use>
        </svg>
        <span class="d-none d-md-inline ms-1">Back to Users</span>
      </a>
    </div>
  </div>
</div>

<?php if (isset($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <svg width="16" height="16" fill="currentColor" class="me-2">
      <use href="#icon-alert-circle"></use>
    </svg>
    <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-8">
    <form method="post" action="<?= \Helpers\Url::to('/admin/create-student') ?>" id="createStudentForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      
      <!-- Basic Information Section -->
      <div class="surface p-4 mb-4">
        <h5 class="fw-bold mb-3">
          <svg width="20" height="20" fill="currentColor" class="me-2">
            <use href="#icon-user"></use>
          </svg>
          Basic Information
        </h5>
        
        <div class="row g-3">
          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" class="form-control" id="first_name" name="first_name" 
                     placeholder="Juan" value="<?= htmlspecialchars($form_data['first_name'] ?? '') ?>" required>
              <label for="first_name">First Name *</label>
              <div class="invalid-feedback">Please enter the student's first name.</div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" class="form-control" id="middle_name" name="middle_name" 
                     placeholder="Santos" value="<?= htmlspecialchars($form_data['middle_name'] ?? '') ?>">
              <label for="middle_name">Middle Name</label>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" class="form-control" id="last_name" name="last_name" 
                     placeholder="Dela Cruz" value="<?= htmlspecialchars($form_data['last_name'] ?? '') ?>" required>
              <label for="last_name">Last Name *</label>
              <div class="invalid-feedback">Please enter the student's last name.</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <input type="date" class="form-control" id="birth_date" name="birth_date" 
                     value="<?= htmlspecialchars($form_data['birth_date'] ?? '') ?>">
              <label for="birth_date">Birth Date</label>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <select class="form-select" id="gender" name="gender">
                <option value="">Select Gender</option>
                <option value="male" <?= ($form_data['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= ($form_data['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other" <?= ($form_data['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
              </select>
              <label for="gender">Gender</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Academic Information Section -->
      <div class="surface p-4 mb-4">
        <h5 class="fw-bold mb-3">
          <svg width="20" height="20" fill="currentColor" class="me-2">
            <use href="#icon-book"></use>
          </svg>
          Academic Information
        </h5>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" class="form-control" id="lrn" name="lrn" 
                     placeholder="108423080569" maxlength="12" pattern="[0-9]{12}"
                     value="<?= htmlspecialchars($form_data['lrn'] ?? '') ?>">
              <label for="lrn">LRN (Learner Reference Number)</label>
              <div class="form-text">
                <svg width="14" height="14" fill="currentColor" class="me-1">
                  <use href="#icon-info"></use>
                </svg>
                12-digit number. Leave empty to generate systematically (e.g., <?= date('Y') ?>00000001)
              </div>
              <div class="invalid-feedback">LRN must be exactly 12 digits</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <select class="form-select" id="grade_level" name="grade_level" required>
                <option value="">Select Grade Level *</option>
                <?php for ($i = 7; $i <= 12; $i++): ?>
                  <option value="<?= $i ?>" <?= ($form_data['grade_level'] ?? '') == $i ? 'selected' : '' ?>>
                    Grade <?= $i ?>
                  </option>
                <?php endfor; ?>
              </select>
              <label for="grade_level">Grade Level *</label>
              <div class="invalid-feedback">Please select a grade level.</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <select class="form-select" id="section_id" name="section_id" required onchange="updateSectionInfo()">
                <option value="">Select Section *</option>
                <?php foreach ($sections as $section): ?>
                  <?php
                    $enrolled = (int)($section['enrolled_students'] ?? 0);
                    $max = (int)($section['max_students'] ?? 0);
                    $available = (int)($section['available_slots'] ?? 0);
                    $status = $section['status'] ?? 'available';
                    $statusIcon = $status === 'full' ? '🔴' : ($status === 'nearly_full' ? '🟠' : '🟢');
                  ?>
                  <option value="<?= $section['id'] ?>" 
                          data-grade="<?= $section['grade_level'] ?>"
                          data-enrolled="<?= $enrolled ?>"
                          data-max="<?= $max ?>"
                          data-available="<?= $available ?>"
                          data-status="<?= $status ?>"
                          <?= ($form_data['section_id'] ?? '') == $section['id'] ? 'selected' : '' ?>
                          <?= $status === 'full' ? 'disabled' : '' ?>>
                    <?= $statusIcon ?> <?= htmlspecialchars($section['name']) ?> 
                    (<?= htmlspecialchars($section['room'] ?: 'No Room') ?>) 
                    - <?= $enrolled ?>/<?= $max ?> (<?= $available ?> available)
                  </option>
                <?php endforeach; ?>
              </select>
              <label for="section_id">Section *</label>
              <div class="invalid-feedback">Please select a section.</div>
              <div id="sectionInfo" class="form-text mt-2"></div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <select class="form-select" id="enrollment_status" name="enrollment_status">
                <option value="enrolled" <?= ($form_data['enrollment_status'] ?? 'enrolled') === 'enrolled' ? 'selected' : '' ?>>Enrolled</option>
                <option value="transferred" <?= ($form_data['enrollment_status'] ?? '') === 'transferred' ? 'selected' : '' ?>>Transferred</option>
                <option value="dropped" <?= ($form_data['enrollment_status'] ?? '') === 'dropped' ? 'selected' : '' ?>>Dropped</option>
                <option value="graduated" <?= ($form_data['enrollment_status'] ?? '') === 'graduated' ? 'selected' : '' ?>>Graduated</option>
              </select>
              <label for="enrollment_status">Enrollment Status</label>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" class="form-control" id="school_year" name="school_year" 
                     placeholder="2025-2026" value="<?= htmlspecialchars($form_data['school_year'] ?? '2025-2026') ?>">
              <label for="school_year">School Year</label>
            </div>
          </div>
          
          <div class="col-12">
            <div class="form-floating">
              <input type="text" class="form-control" id="previous_school" name="previous_school" 
                     placeholder="Previous School Name" value="<?= htmlspecialchars($form_data['previous_school'] ?? '') ?>">
              <label for="previous_school">Previous School</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact Information Section -->
      <div class="surface p-4 mb-4">
        <h5 class="fw-bold mb-3">
          <svg width="20" height="20" fill="currentColor" class="me-2">
            <use href="#icon-phone"></use>
          </svg>
          Contact Information
          <span class="badge bg-secondary ms-2" style="font-size: 0.75rem; font-weight: 400;">Optional - Can be added later</span>
        </h5>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-floating">
              <input type="email" class="form-control" id="email" name="email" 
                     placeholder="student@example.com" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
              <label for="email">Email Address *</label>
              <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                     placeholder="+63 912 345 6789" value="<?= htmlspecialchars($form_data['contact_number'] ?? '') ?>">
              <label for="contact_number">Contact Number</label>
            </div>
          </div>
          
          <div class="col-12">
            <div class="form-floating">
              <textarea class="form-control" id="address" name="address" 
                        placeholder="Complete address" style="height: 100px"><?= htmlspecialchars($form_data['address'] ?? '') ?></textarea>
              <label for="address">Address</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Guardian Information Section -->
      <div class="surface p-4 mb-4">
        <h5 class="fw-bold mb-3">
          <svg width="20" height="20" fill="currentColor" class="me-2">
            <use href="#icon-users"></use>
          </svg>
          Guardian Information
          <span class="badge bg-secondary ms-2" style="font-size: 0.75rem; font-weight: 400;">Optional - Can be added later</span>
        </h5>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" class="form-control" id="guardian_name" name="guardian_name" 
                     placeholder="Guardian Full Name" value="<?= htmlspecialchars($form_data['guardian_name'] ?? '') ?>">
              <label for="guardian_name">Guardian Name</label>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <input type="tel" class="form-control" id="guardian_contact" name="guardian_contact" 
                     placeholder="+63 912 345 6789" value="<?= htmlspecialchars($form_data['guardian_contact'] ?? '') ?>">
              <label for="guardian_contact">Guardian Contact</label>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <select class="form-select" id="guardian_relationship" name="guardian_relationship">
                <option value="">Select Relationship</option>
                <option value="father" <?= ($form_data['guardian_relationship'] ?? '') === 'father' ? 'selected' : '' ?>>Father</option>
                <option value="mother" <?= ($form_data['guardian_relationship'] ?? '') === 'mother' ? 'selected' : '' ?>>Mother</option>
                <option value="grandfather" <?= ($form_data['guardian_relationship'] ?? '') === 'grandfather' ? 'selected' : '' ?>>Grandfather</option>
                <option value="grandmother" <?= ($form_data['guardian_relationship'] ?? '') === 'grandmother' ? 'selected' : '' ?>>Grandmother</option>
                <option value="uncle" <?= ($form_data['guardian_relationship'] ?? '') === 'uncle' ? 'selected' : '' ?>>Uncle</option>
                <option value="aunt" <?= ($form_data['guardian_relationship'] ?? '') === 'aunt' ? 'selected' : '' ?>>Aunt</option>
                <option value="guardian" <?= ($form_data['guardian_relationship'] ?? '') === 'guardian' ? 'selected' : '' ?>>Guardian</option>
                <option value="other" <?= ($form_data['guardian_relationship'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
              </select>
              <label for="guardian_relationship">Relationship</label>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <input type="email" class="form-control" id="guardian_email" name="guardian_email" 
                     placeholder="guardian@example.com" value="<?= htmlspecialchars($form_data['guardian_email'] ?? '') ?>">
              <label for="guardian_email">Guardian Email (Optional)</label>
              <div class="form-text">Required if you want to create a parent account for this guardian</div>
            </div>
          </div>
          
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="create_parent_account" name="create_parent_account" value="1"
                     <?= !empty($form_data['guardian_name']) && !empty($form_data['guardian_email']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="create_parent_account">
                <strong>Create parent account for this guardian</strong>
              </label>
              <div class="form-text">
                <small class="text-muted">
                  If checked and guardian email is provided, a parent account will be created automatically. 
                  The parent will receive login credentials via email (if email service is configured).
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Emergency Contact Section -->
      <div class="surface p-4 mb-4">
        <h5 class="fw-bold mb-3">
          <svg width="20" height="20" fill="currentColor" class="me-2">
            <use href="#icon-alert-triangle"></use>
          </svg>
          Emergency Contact
          <span class="badge bg-secondary ms-2" style="font-size: 0.75rem; font-weight: 400;">Optional - Can be added later</span>
        </h5>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                     placeholder="Emergency Contact Name" value="<?= htmlspecialchars($form_data['emergency_contact_name'] ?? '') ?>">
              <label for="emergency_contact_name">Emergency Contact Name</label>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <input type="tel" class="form-control" id="emergency_contact_number" name="emergency_contact_number" 
                     placeholder="+63 912 345 6789" value="<?= htmlspecialchars($form_data['emergency_contact_number'] ?? '') ?>">
              <label for="emergency_contact_number">Emergency Contact Number</label>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <select class="form-select" id="emergency_contact_relationship" name="emergency_contact_relationship">
                <option value="">Select Relationship</option>
                <option value="father" <?= ($form_data['emergency_contact_relationship'] ?? '') === 'father' ? 'selected' : '' ?>>Father</option>
                <option value="mother" <?= ($form_data['emergency_contact_relationship'] ?? '') === 'mother' ? 'selected' : '' ?>>Mother</option>
                <option value="grandfather" <?= ($form_data['emergency_contact_relationship'] ?? '') === 'grandfather' ? 'selected' : '' ?>>Grandfather</option>
                <option value="grandmother" <?= ($form_data['emergency_contact_relationship'] ?? '') === 'grandmother' ? 'selected' : '' ?>>Grandmother</option>
                <option value="uncle" <?= ($form_data['emergency_contact_relationship'] ?? '') === 'uncle' ? 'selected' : '' ?>>Uncle</option>
                <option value="aunt" <?= ($form_data['emergency_contact_relationship'] ?? '') === 'aunt' ? 'selected' : '' ?>>Aunt</option>
                <option value="guardian" <?= ($form_data['emergency_contact_relationship'] ?? '') === 'guardian' ? 'selected' : '' ?>>Guardian</option>
                <option value="other" <?= ($form_data['emergency_contact_relationship'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
              </select>
              <label for="emergency_contact_relationship">Relationship</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Health Information Section -->
      <div class="surface p-4 mb-4">
        <h5 class="fw-bold mb-3">
          <svg width="20" height="20" fill="currentColor" class="me-2">
            <use href="#icon-heart"></use>
          </svg>
          Health Information
          <span class="badge bg-secondary ms-2" style="font-size: 0.75rem; font-weight: 400;">Optional - Can be added later</span>
        </h5>
        
        <div class="row g-3">
          <div class="col-12">
            <div class="form-floating">
              <textarea class="form-control" id="medical_conditions" name="medical_conditions" 
                        placeholder="Any medical conditions or health concerns" style="height: 80px"><?= htmlspecialchars($form_data['medical_conditions'] ?? '') ?></textarea>
              <label for="medical_conditions">Medical Conditions</label>
            </div>
          </div>
          
          <div class="col-12">
            <div class="form-floating">
              <textarea class="form-control" id="allergies" name="allergies" 
                        placeholder="Any known allergies" style="height: 80px"><?= htmlspecialchars($form_data['allergies'] ?? '') ?></textarea>
              <label for="allergies">Allergies</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Account Information Section -->
      <div class="surface p-4 mb-4">
        <h5 class="fw-bold mb-3">
          <svg width="20" height="20" fill="currentColor" class="me-2">
            <use href="#icon-lock"></use>
          </svg>
          Account Information
        </h5>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-floating position-relative">
              <input type="password" class="form-control" id="password" name="password" 
                     placeholder="Password" required>
              <label for="password">Password *</label>
              <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-3" 
                      id="togglePassword" style="z-index: 10; border: none; background: none;">
                <svg width="16" height="16" fill="currentColor" id="passwordIcon">
                  <use href="#icon-eye"></use>
                </svg>
              </button>
              <div class="form-text">Minimum 8 characters with mixed case, numbers, and symbols</div>
              <div class="invalid-feedback">Please enter a strong password.</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                     placeholder="Confirm Password" required>
              <label for="confirm_password">Confirm Password *</label>
              <div class="invalid-feedback">Passwords do not match.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Additional Notes Section -->
      <div class="surface p-4 mb-4">
        <h5 class="fw-bold mb-3">
          <svg width="20" height="20" fill="currentColor" class="me-2">
            <use href="#icon-file-text"></use>
          </svg>
          Additional Notes
          <span class="badge bg-secondary ms-2" style="font-size: 0.75rem; font-weight: 400;">Optional</span>
        </h5>
        
        <div class="form-floating">
          <textarea class="form-control" id="notes" name="notes" 
                    placeholder="Any additional notes or comments" style="height: 100px"><?= htmlspecialchars($form_data['notes'] ?? '') ?></textarea>
          <label for="notes">Notes</label>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <span class="btn-text">
            <svg width="16" height="16" fill="currentColor">
              <use href="#icon-plus"></use>
            </svg>
            <span class="ms-1">Register Student</span>
          </span>
          <span class="btn-loading" style="display: none;">
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
            Registering Student...
          </span>
        </button>
        <a href="<?= \Helpers\Url::to('/admin/users') ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <!-- Sidebar with Help Information -->
  <div class="col-lg-4">
    <div class="surface p-4">
      <h6 class="fw-bold mb-3">Registration Guidelines</h6>
      
      <div class="mb-3">
        <h6 class="small fw-semibold text-primary">Required Fields</h6>
        <ul class="small text-muted mb-0">
          <li>First Name and Last Name</li>
          <li>Email Address</li>
          <li>Password</li>
          <li>Grade Level</li>
          <li>Section</li>
        </ul>
      </div>
      
      <div class="mb-3">
        <h6 class="small fw-semibold text-success">Systematic LRN Generation</h6>
        <ul class="small text-muted mb-0">
          <li><strong>Manual Input:</strong> Enter 12-digit LRN (e.g., 108423080569)</li>
          <li><strong>Auto-Generated:</strong> Leave empty for systematic generation</li>
          <li><strong>Format:</strong> Year (4 digits) + Sequential (8 digits)</li>
          <li><strong>Example:</strong> 202500000001, 202500000002, etc.</li>
        </ul>
      </div>
      
      <div class="mb-3">
        <h6 class="small fw-semibold text-info">Optional Information</h6>
        <p class="small text-muted mb-2">These can be added later through student profile:</p>
        <ul class="small text-muted mb-0">
          <li>Birth date & Gender</li>
          <li>Contact number & Address</li>
          <li>Guardian information</li>
          <li>Emergency contacts</li>
          <li>Health & medical information</li>
          <li>Previous school details</li>
          <li>Additional notes</li>
        </ul>
      </div>
      
      <div class="alert alert-info small">
        <svg width="16" height="16" fill="currentColor" class="me-2">
          <use href="#icon-info"></use>
        </svg>
        <strong>Note:</strong> Students will be able to log in immediately after registration with the provided credentials.
      </div>
    </div>
  </div>
</div>

<script>
// Update section info when selection changes
function updateSectionInfo() {
  const select = document.getElementById('section_id');
  const info = document.getElementById('sectionInfo');
  const selectedOption = select.options[select.selectedIndex];
  
  if (!selectedOption || !selectedOption.value) {
    info.innerHTML = '';
    return;
  }
  
  const enrolled = parseInt(selectedOption.dataset.enrolled || 0);
  const max = parseInt(selectedOption.dataset.max || 0);
  const available = parseInt(selectedOption.dataset.available || 0);
  const status = selectedOption.dataset.status || 'available';
  
  let statusClass = 'text-success';
  let statusText = 'Available';
  if (status === 'full') {
    statusClass = 'text-danger';
    statusText = 'Full';
  } else if (status === 'nearly_full') {
    statusClass = 'text-warning';
    statusText = 'Nearly Full';
  }
  
  info.innerHTML = `
    <div class="small">
      <strong>Capacity:</strong> ${enrolled}/${max} enrolled (${available} available slots)
      <span class="badge ${statusClass === 'text-success' ? 'bg-success' : (statusClass === 'text-warning' ? 'bg-warning' : 'bg-danger')} ms-2">
        ${statusText}
      </span>
      ${status === 'full' ? '<br><span class="text-danger"><small>⚠️ This section is full. Please select another section.</small></span>' : ''}
    </div>
  `;
  
  if (status === 'full') {
    select.setCustomValidity('This section is full. Please select another section.');
  } else {
    select.setCustomValidity('');
  }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  updateSectionInfo();
  document.getElementById('section_id').addEventListener('change', updateSectionInfo);
  
  // Filter sections by grade level
  const gradeSelect = document.getElementById('grade_level');
  const sectionSelect = document.getElementById('section_id');
  
  if (gradeSelect && sectionSelect) {
    gradeSelect.addEventListener('change', function() {
      const selectedGrade = this.value;
      Array.from(sectionSelect.options).forEach(option => {
        if (option.value && option.dataset.grade !== selectedGrade) {
          option.style.display = 'none';
        } else {
          option.style.display = '';
        }
      });
      sectionSelect.value = ''; // Reset selection
      updateSectionInfo();
    });
  }
});
</script>

<script>
// Enhanced Student Registration Form JavaScript
class StudentRegistrationForm {
  constructor() {
    this.form = document.getElementById('createStudentForm');
    this.passwordField = document.getElementById('password');
    this.confirmPasswordField = document.getElementById('confirm_password');
    this.gradeLevelField = document.getElementById('grade_level');
    this.sectionField = document.getElementById('section_id');
    this.togglePasswordBtn = document.getElementById('togglePassword');
    this.passwordIcon = document.getElementById('passwordIcon');
    this.submitBtn = document.getElementById('submitBtn');
    
    this.init();
  }

  init() {
    this.bindEvents();
    this.setupPasswordValidation();
    this.setupSectionFiltering();
  }

  bindEvents() {
    // Form submission
    this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    
    // Real-time validation
    this.form.addEventListener('input', (e) => this.validateField(e.target));
    
    // Password toggle
    this.togglePasswordBtn.addEventListener('click', () => this.togglePassword());
    
    // Grade level change - filter sections
    this.gradeLevelField.addEventListener('change', () => this.filterSections());
    
    // Password confirmation
    this.confirmPasswordField.addEventListener('input', () => this.validatePasswordMatch());
  }

  setupPasswordValidation() {
    this.passwordField.addEventListener('input', () => {
      this.validatePassword();
      this.validatePasswordMatch();
    });
    
    // Setup LRN validation
    const lrnField = document.getElementById('lrn');
    if (lrnField) {
      lrnField.addEventListener('input', () => this.validateLRN(lrnField));
    }
  }

  validateLRN(field) {
    const lrn = field.value.trim();
    if (lrn === '') {
      // Empty is valid (will be auto-generated)
      field.setCustomValidity('');
      field.classList.remove('is-invalid');
      return true;
    }
    
    // Must be exactly 12 digits
    if (!/^\d{12}$/.test(lrn)) {
      field.setCustomValidity('LRN must be exactly 12 digits');
      field.classList.add('is-invalid');
      return false;
    }
    
    field.setCustomValidity('');
    field.classList.remove('is-invalid');
    return true;
  }

  validatePassword() {
    const password = this.passwordField.value;
    const requirements = {
      length: password.length >= 8,
      lowercase: /[a-z]/.test(password),
      uppercase: /[A-Z]/.test(password),
      number: /\d/.test(password),
      special: /[@$!%*?&]/.test(password)
    };

    const isValid = Object.values(requirements).every(req => req);
    
    // Create detailed error message
    if (!isValid) {
      const missing = [];
      if (!requirements.length) missing.push('at least 8 characters');
      if (!requirements.uppercase) missing.push('uppercase letter');
      if (!requirements.lowercase) missing.push('lowercase letter');
      if (!requirements.number) missing.push('number');
      if (!requirements.special) missing.push('special character (@$!%*?&)');
      
      this.passwordField.setCustomValidity('Password must contain: ' + missing.join(', '));
    } else {
      this.passwordField.setCustomValidity('');
    }
    
    return isValid;
  }

  validatePasswordMatch() {
    const password = this.passwordField.value;
    const confirmPassword = this.confirmPasswordField.value;
    
    if (confirmPassword && password !== confirmPassword) {
      this.confirmPasswordField.setCustomValidity('Passwords do not match');
    } else {
      this.confirmPasswordField.setCustomValidity('');
    }
  }

  setupSectionFiltering() {
    this.filterSections();
  }

  filterSections() {
    const selectedGrade = this.gradeLevelField.value;
    const options = this.sectionField.querySelectorAll('option');
    
    options.forEach(option => {
      if (option.value === '') {
        option.style.display = 'block';
        return;
      }
      
      const optionGrade = option.dataset.grade;
      if (selectedGrade && optionGrade !== selectedGrade) {
        option.style.display = 'none';
        if (option.selected) {
          option.selected = false;
        }
      } else {
        option.style.display = 'block';
      }
    });
  }

  togglePassword() {
    const type = this.passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    this.passwordField.setAttribute('type', type);
    
    const icon = this.passwordIcon.querySelector('use');
    icon.setAttribute('href', type === 'password' ? '#icon-eye' : '#icon-eye-off');
  }

  validateField(field) {
    const isValid = field.checkValidity();
    
    if (field.value.trim() !== '') {
      field.classList.remove('is-invalid');
      if (isValid) {
        field.classList.add('is-valid');
      } else {
        field.classList.add('is-invalid');
      }
    } else {
      field.classList.remove('is-valid', 'is-invalid');
    }
  }

  handleSubmit(e) {
    e.preventDefault();
    
    // Collect validation errors
    const errors = [];
    
    // Validate all required fields
    const requiredFields = this.form.querySelectorAll('input[required], select[required]');
    let firstInvalidField = null;
    
    requiredFields.forEach(field => {
      this.validateField(field);
      if (!field.checkValidity()) {
        if (!firstInvalidField) firstInvalidField = field;
        const label = field.labels && field.labels[0] ? field.labels[0].textContent : field.name;
        errors.push(`${label} is required`);
      }
    });

    // Special password validation
    const passwordValid = this.validatePassword();
    this.validatePasswordMatch();
    
    if (!this.passwordField.checkValidity()) {
      if (!firstInvalidField) firstInvalidField = this.passwordField;
      errors.push('Password does not meet requirements');
    }
    
    if (!this.confirmPasswordField.checkValidity()) {
      if (!firstInvalidField) firstInvalidField = this.confirmPasswordField;
      errors.push('Passwords do not match');
    }
    
    // Validate LRN format if provided
    const lrnField = document.getElementById('lrn');
    if (lrnField && lrnField.value.trim() !== '') {
      if (!this.validateLRN(lrnField)) {
        if (!firstInvalidField) firstInvalidField = lrnField;
        errors.push('LRN must be exactly 12 digits');
      }
    }

    if (errors.length > 0) {
      // Show first error
      this.showNotification(errors[0], 'error');
      // Scroll to first invalid field
      if (firstInvalidField) {
        firstInvalidField.focus();
        firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    } else {
      this.submitForm();
    }
  }

  submitForm() {
    // Show loading state
    this.submitBtn.disabled = true;
    const textEl = this.submitBtn.querySelector('.btn-text');
    const loadingEl = this.submitBtn.querySelector('.btn-loading');
    if (textEl) textEl.style.display = 'none';
    if (loadingEl) loadingEl.style.display = 'inline-flex';

    // Submit the form
    this.form.submit();
  }

  showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
      <svg width="16" height="16" fill="currentColor" class="me-2">
        <use href="#icon-${type === 'error' ? 'alert-circle' : 'check'}"></use>
      </svg>
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 5000);
  }
}

// Initialize the form when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new StudentRegistrationForm();
});
</script>

<style>
.surface {
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
}

.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label {
  opacity: 0.65;
  transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}

.btn-loading {
  display: none !important;
}

.btn-loading.show {
  display: inline-flex !important;
}

.btn-text.hide {
  display: none !important;
}

/* Section filtering animation */
select option {
  transition: all 0.2s ease;
}

/* Form validation styles */
.is-valid {
  border-color: var(--bs-success);
}

.is-invalid {
  border-color: var(--bs-danger);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .surface {
    padding: 1rem !important;
  }
  
  .dashboard-header h1 {
    font-size: 1.5rem;
  }
}
</style>
