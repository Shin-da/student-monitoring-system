<div class="dashboard-header mb-4">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <h1 class="h3 fw-bold mb-1">Create Parent Account</h1>
      <p class="text-muted mb-0">Create a parent account linked to a specific student</p>
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

<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="surface p-4">
      <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form id="createParentForm" method="post" action="<?= \Helpers\Url::to('/admin/create-parent') ?>">
        <input type="hidden" name="csrf_token" value="<?= \Helpers\Csrf::generateToken() ?>">
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" required 
                     pattern="[A-Za-z\s]{2,50}" minlength="2" maxlength="50">
              <label for="name">Parent Full Name</label>
              <div class="invalid-feedback">Please enter a valid name (2-50 characters, letters only)</div>
              <div class="valid-feedback">Name looks good!</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <input type="email" class="form-control" id="email" name="email" placeholder="parent@example.com" required>
              <label for="email">Email Address</label>
              <div class="invalid-feedback">Please enter a valid email address</div>
              <div class="valid-feedback">Email looks good!</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <input type="tel" class="form-control" id="contact" name="contact" placeholder="+63 9XX XXX XXXX" 
                     pattern="[0-9+\s\-()]+" maxlength="20">
              <label for="contact">Contact Number (Optional)</label>
              <div class="form-text">Will be synced to student's guardian contact if provided</div>
              <div class="invalid-feedback">Please enter a valid contact number</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating position-relative">
              <input type="password" class="form-control" id="password" name="password" placeholder="Password" required 
                     minlength="8" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$">
              <label for="password">Password</label>
              <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2" 
                      id="togglePassword" style="z-index: 10; border: none; background: none; color: var(--color-muted);">
                <svg width="16" height="16" fill="currentColor">
                  <use href="#icon-eye"></use>
                </svg>
              </button>
              <div class="form-text">
                <div class="password-requirements">
                  <small class="text-muted">Password must contain:</small>
                  <ul class="list-unstyled small mt-1">
                    <li class="requirement" data-requirement="length">
                      <svg width="12" height="12" fill="currentColor" class="me-1">
                        <use href="#icon-check"></use>
                      </svg>
                      At least 8 characters
                    </li>
                    <li class="requirement" data-requirement="lowercase">
                      <svg width="12" height="12" fill="currentColor" class="me-1">
                        <use href="#icon-check"></use>
                      </svg>
                      One lowercase letter
                    </li>
                    <li class="requirement" data-requirement="uppercase">
                      <svg width="12" height="12" fill="currentColor" class="me-1">
                        <use href="#icon-check"></use>
                      </svg>
                      One uppercase letter
                    </li>
                    <li class="requirement" data-requirement="number">
                      <svg width="12" height="12" fill="currentColor" class="me-1">
                        <use href="#icon-check"></use>
                      </svg>
                      One number
                    </li>
                  </ul>
                </div>
              </div>
              <div class="invalid-feedback">Password must meet all requirements above</div>
              <div class="valid-feedback">Password is strong!</div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-floating">
              <select class="form-select" id="relationship" name="relationship" required>
                <option value="">Choose relationship...</option>
                <option value="father">Father</option>
                <option value="mother">Mother</option>
                <option value="guardian">Guardian</option>
                <option value="grandparent">Grandparent</option>
                <option value="sibling">Sibling</option>
                <option value="other">Other</option>
              </select>
              <label for="relationship">Relationship to Student</label>
              <div class="invalid-feedback">Please select a relationship</div>
              <div class="valid-feedback">Relationship selected!</div>
            </div>
          </div>
          
          <div class="col-12">
            <label class="form-label">Link to Student <span class="text-danger">*</span></label>
            <div class="position-relative">
              <div class="input-group">
                <span class="input-group-text">
                  <svg width="16" height="16" fill="currentColor">
                    <use href="#icon-search"></use>
                  </svg>
                </span>
                <input type="text" 
                       class="form-control" 
                       id="student_search" 
                       placeholder="Type to search by name, LRN, grade, or section..." 
                       autocomplete="off"
                       required>
                <input type="hidden" id="student_id" name="student_id" required>
              </div>
              <div id="student_search_results" class="dropdown-menu w-100 position-absolute" style="max-height: 400px; overflow-y: auto; display: none; z-index: 1000; top: 100%;">
                <div class="px-3 py-2 text-muted small border-bottom" id="search_results_header">
                  <span id="results_count">Start typing to search students...</span>
                </div>
                <div id="search_results_list" class="list-group list-group-flush">
                  <!-- Results will be populated here -->
                </div>
                <div id="search_loading" class="px-3 py-2 text-center" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <span class="ms-2 small text-muted">Searching...</span>
                </div>
                <div id="search_no_results" class="px-3 py-2 text-muted text-center" style="display: none;">
                  No students found. Try a different search term.
                </div>
              </div>
            </div>
            <div class="form-text">
              <div class="student-info d-none mt-3 p-3 bg-light rounded border" id="selectedStudentInfo">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <svg width="20" height="20" fill="currentColor" class="text-primary">
                      <use href="#icon-user"></use>
                    </svg>
                    <span class="fw-semibold fs-6" id="selectedStudentName">-</span>
                    <span class="badge bg-success">Selected</span>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-secondary" id="clearStudentSelection">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                    Clear
                  </button>
                </div>
                <div class="row g-2 small">
                  <div class="col-md-4">
                    <strong>LRN:</strong> <span id="selectedStudentLRN">-</span>
                  </div>
                  <div class="col-md-4">
                    <strong>Grade:</strong> <span id="selectedStudentGrade">-</span>
                  </div>
                  <div class="col-md-4">
                    <strong>Section:</strong> <span id="selectedStudentSection">-</span>
                  </div>
                </div>
                <div class="mt-2 p-2 bg-white rounded border" id="guardianInfo" style="display: none;">
                  <small class="text-muted d-block mb-1"><strong>Current Guardian Info:</strong></small>
                  <small class="d-block" id="currentGuardianName">-</small>
                  <small class="d-block text-muted" id="currentGuardianContact">-</small>
                </div>
              </div>
            </div>
            <div class="invalid-feedback">Please select a student</div>
            <div class="valid-feedback">Student selected!</div>
          </div>
          
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="sync_to_student" name="sync_to_student" value="1" checked>
              <label class="form-check-label" for="sync_to_student">
                <strong>Sync parent information to student's guardian fields</strong>
              </label>
              <div class="form-text">
                <small class="text-muted">
                  If checked, this parent's name, contact, and relationship will be saved to the student's guardian information. 
                  <span id="syncWarning" class="text-warning" style="display: none;">
                    <strong>Note:</strong> Student already has guardian information. Sync will only work if guardian fields are empty (first parent only).
                  </span>
                  <span id="syncInfo" class="text-info" style="display: none;">
                    <strong>Info:</strong> Guardian fields are empty. This parent's info will be synced to guardian fields.
                  </span>
                </small>
              </div>
            </div>
          </div>
        </div>
        
        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary" id="submitBtn">
            <span class="btn-text">
              <svg width="16" height="16" fill="currentColor">
                <use href="#icon-plus"></use>
              </svg>
              <span class="ms-1">Create Parent Account</span>
            </span>
            <span class="btn-loading d-none">
              <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              Creating Account...
            </span>
          </button>
          <a href="<?= \Helpers\Url::to('/admin/users') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="row mt-5">
  <div class="col-12">
    <div class="surface p-4">
      <h6 class="fw-bold mb-3">Parent Account Information</h6>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="border rounded-3 p-3">
            <h6 class="fw-semibold mb-2">Access Rights</h6>
            <ul class="small text-muted mb-0">
              <li>View their child's grades and academic progress</li>
              <li>Monitor attendance records</li>
              <li>Receive notifications about their child</li>
              <li>View school announcements and events</li>
            </ul>
          </div>
        </div>
        <div class="col-md-6">
          <div class="border rounded-3 p-3">
            <h6 class="fw-semibold mb-2">Security Features</h6>
            <ul class="small text-muted mb-0">
              <li>Account created and managed by administrators</li>
              <li>Linked to specific student records only</li>
              <li>Cannot access other students' information</li>
              <li>Secure login with email and password</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Pass students data to JavaScript
const studentsData = <?= json_encode($students ?? []) ?>;
</script>
<script src="<?= \Helpers\Url::asset('create-parent-form.js') ?>"></script>
