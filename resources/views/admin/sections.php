<?php
$title = $title ?? 'Section Management';
$user = $user ?? null;
$activeNav = $activeNav ?? 'sections';
$sections = $sections ?? [];
$unassignedCount = $unassignedCount ?? 0;
$teachers = $teachers ?? [];
$csrf_token = $csrf_token ?? '';
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
?>

<!-- Success/Error Messages -->
<?php if ($success): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <svg width="16" height="16" fill="currentColor" class="me-2">
      <use href="#icon-check-circle"></use>
    </svg>
    <?php if ($success === 'section_created'): ?>
      Section created successfully!
    <?php elseif ($success === 'section_updated'): ?>
      Section updated successfully!
    <?php elseif ($success === 'student_assigned'): ?>
      Student assigned to section successfully!
    <?php elseif ($success === 'section_deleted'): ?>
      Section deleted successfully!
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <svg width="16" height="16" fill="currentColor" class="me-2">
      <use href="#icon-alert-circle"></use>
    </svg>
    <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="dashboard-header mb-4">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <h1 class="h3 fw-bold mb-1">Section Management</h1>
      <p class="text-muted mb-0">Manage sections, monitor capacity, and assign students</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshSectionData()">
        <svg width="16" height="16" fill="currentColor">
          <use href="#icon-refresh"></use>
        </svg>
        <span class="d-none d-md-inline ms-1">Refresh</span>
      </button>
      <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createSectionModal">
        <svg width="16" height="16" fill="currentColor">
          <use href="#icon-plus"></use>
        </svg>
        <span class="d-none d-md-inline ms-1">Add Section</span>
      </button>
    </div>
  </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0">
            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
              <svg width="24" height="24" fill="currentColor" class="text-primary">
                <use href="#icon-graduation-cap"></use>
              </svg>
            </div>
          </div>
          <div class="flex-grow-1 ms-3">
            <h6 class="text-muted mb-0">Total Sections</h6>
            <h3 class="mb-0 fw-bold" id="totalSections"><?= count($sections) ?></h3>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0">
            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
              <svg width="24" height="24" fill="currentColor" class="text-warning">
                <use href="#icon-users"></use>
              </svg>
            </div>
          </div>
          <div class="flex-grow-1 ms-3">
            <h6 class="text-muted mb-0">Unassigned Students</h6>
            <h3 class="mb-0 fw-bold" id="unassignedStudents"><?= $unassignedCount ?></h3>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0">
            <div class="bg-success bg-opacity-10 rounded-circle p-3">
              <svg width="24" height="24" fill="currentColor" class="text-success">
                <use href="#icon-check-circle"></use>
              </svg>
            </div>
          </div>
          <div class="flex-grow-1 ms-3">
            <h6 class="text-muted mb-0">Available Sections</h6>
            <h3 class="mb-0 fw-bold" id="availableSections">
              <?= count(array_filter($sections, fn($s) => $s['status'] === 'available')) ?>
            </h3>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0">
            <div class="bg-danger bg-opacity-10 rounded-circle p-3">
              <svg width="24" height="24" fill="currentColor" class="text-danger">
                <use href="#icon-x-circle"></use>
              </svg>
            </div>
          </div>
          <div class="flex-grow-1 ms-3">
            <h6 class="text-muted mb-0">Full Sections</h6>
            <h3 class="mb-0 fw-bold" id="fullSections">
              <?= count(array_filter($sections, fn($s) => $s['status'] === 'full')) ?>
            </h3>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Search and Filter -->
<div class="surface p-3 mb-4">
  <div class="row g-3">
    <div class="col-md-4">
      <div class="input-group">
        <span class="input-group-text">
          <svg width="16" height="16" fill="currentColor">
            <use href="#icon-search"></use>
          </svg>
        </span>
        <input type="text" class="form-control" id="searchInput" placeholder="Search sections by name, room, or grade level...">
      </div>
    </div>
    <div class="col-md-2">
      <select class="form-select" id="gradeFilter">
        <option value="">All Grades</option>
        <?php for ($i = 1; $i <= 12; $i++): ?>
          <option value="<?= $i ?>">Grade <?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" id="statusFilter">
        <option value="">All Status</option>
        <option value="available">Available</option>
        <option value="nearly_full">Nearly Full</option>
        <option value="full">Full</option>
      </select>
    </div>
    <div class="col-md-4 text-end">
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
        <svg width="16" height="16" fill="currentColor">
          <use href="#icon-x"></use>
        </svg>
        Clear Filters
      </button>
    </div>
  </div>
</div>

<!-- Sections Table -->
<div class="surface p-4">
  <?php if (empty($sections)): ?>
    <div class="text-center py-5">
      <svg width="64" height="64" fill="currentColor" class="text-muted mb-3">
        <use href="#icon-graduation-cap"></use>
      </svg>
      <h5 class="text-muted">No Sections Found</h5>
      <p class="text-muted">Start by creating your first section.</p>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSectionModal">
        <svg width="16" height="16" fill="currentColor" class="me-2">
          <use href="#icon-plus"></use>
        </svg>
        Add Section
      </button>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0" id="sectionsTable">
        <thead class="table-light">
          <tr>
            <th>Section</th>
            <th>Grade Level</th>
            <th>Room</th>
            <th>Capacity</th>
            <th>Enrolled</th>
            <th>Available</th>
            <th>Status</th>
            <th>Adviser</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections as $section): ?>
            <?php
              $enrolled = (int)$section['enrolled_students'];
              $max = (int)$section['max_students'];
              $available = $max - $enrolled;
              $percentage = $max > 0 ? ($enrolled / $max) * 100 : 0;
              
              // Status badge color
              $statusClass = 'bg-success';
              $statusText = 'Available';
              if ($section['status'] === 'full') {
                $statusClass = 'bg-danger';
                $statusText = 'Full';
              } elseif ($section['status'] === 'nearly_full') {
                $statusClass = 'bg-warning';
                $statusText = 'Nearly Full';
              }
            ?>
            <tr data-section-id="<?= $section['id'] ?>" 
                data-grade="<?= $section['grade_level'] ?>" 
                data-status="<?= $section['status'] ?>">
              <td>
                <div class="d-flex align-items-center">
                  <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                    <svg width="16" height="16" fill="currentColor" class="text-primary">
                      <use href="#icon-graduation-cap"></use>
                    </svg>
                  </div>
                  <div>
                    <h6 class="mb-0 fw-semibold"><?= htmlspecialchars($section['name']) ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($section['school_year']) ?></small>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge bg-info">Grade <?= $section['grade_level'] ?></span>
              </td>
              <td>
                <?= htmlspecialchars($section['room'] ?? 'N/A') ?>
              </td>
              <td>
                <span class="fw-semibold"><?= $max ?></span>
              </td>
              <td>
                <span class="fw-semibold"><?= $enrolled ?></span>
              </td>
              <td>
                <span class="fw-semibold <?= $available <= 0 ? 'text-danger' : 'text-success' ?>">
                  <?= $available ?>
                </span>
              </td>
              <td>
                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                <div class="progress mt-1" style="height: 4px; width: 60px;">
                  <div class="progress-bar <?= $statusClass === 'bg-danger' ? 'bg-danger' : ($statusClass === 'bg-warning' ? 'bg-warning' : 'bg-success') ?>" 
                       role="progressbar" 
                       style="width: <?= min(100, $percentage) ?>%"></div>
                </div>
              </td>
              <td>
                <?php if ($section['adviser_name']): ?>
                  <div>
                    <small class="d-block"><?= htmlspecialchars($section['adviser_name']) ?></small>
                    <small class="text-muted"><?= htmlspecialchars($section['adviser_email']) ?></small>
                  </div>
                <?php else: ?>
                  <span class="text-muted">No Adviser</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="btn-group" role="group">
                  <button type="button" class="btn btn-sm btn-outline-primary action-view-details" 
                          data-section-id="<?= $section['id'] ?>"
                          title="View Details">
                    <svg width="14" height="14" fill="currentColor">
                      <use href="#icon-eye"></use>
                    </svg>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-info action-edit-capacity" 
                          data-section-id="<?= $section['id'] ?>"
                          data-max-students="<?= $section['max_students'] ?>"
                          data-enrolled="<?= $enrolled ?>"
                          title="Edit Capacity">
                    <svg width="14" height="14" fill="currentColor">
                      <use href="#icon-edit"></use>
                    </svg>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-success action-assign-student" 
                          data-section-id="<?= $section['id'] ?>"
                          data-section-name="<?= htmlspecialchars($section['name'], ENT_QUOTES, 'UTF-8') ?>"
                          title="Assign Student"
                          <?= $section['status'] === 'full' ? 'disabled' : '' ?>>
                    <svg width="14" height="14" fill="currentColor">
                      <use href="#icon-user-plus"></use>
                    </svg>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-danger action-delete-section" 
                          data-section-id="<?= $section['id'] ?>"
                          data-section-name="<?= htmlspecialchars($section['name'], ENT_QUOTES, 'UTF-8') ?>"
                          data-enrolled="<?= $enrolled ?>"
                          title="Delete Section">
                    <svg width="14" height="14" fill="currentColor">
                      <use href="#icon-delete"></use>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Create Section Modal -->
<div class="modal fade" id="createSectionModal" tabindex="-1" aria-labelledby="createSectionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createSectionModalLabel">Create New Section</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="createSectionForm" method="POST" action="<?= \Helpers\Url::to('/admin/create-section') ?>">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
          
          <div class="mb-3">
            <label for="section_name" class="form-label">Section Name *</label>
            <input type="text" class="form-control" id="section_name" name="name" 
                   placeholder="e.g., Section A, Section B" required>
            <div class="form-text">Unique name for this section</div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="section_grade_level" class="form-label">Grade Level *</label>
              <select class="form-select" id="section_grade_level" name="grade_level" required>
                <option value="">Select Grade</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                  <option value="<?= $i ?>">Grade <?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="section_room" class="form-label">Room</label>
              <input type="text" class="form-control" id="section_room" name="room" 
                     placeholder="e.g., Room 101">
            </div>
          </div>

          <div class="mb-3">
            <label for="section_max_students" class="form-label">Maximum Students *</label>
            <input type="number" class="form-control" id="section_max_students" name="max_students" 
                   value="50" min="1" max="100" required>
            <div class="form-text">Maximum number of students this section can accommodate</div>
          </div>

          <div class="mb-3">
            <label for="section_school_year" class="form-label">School Year</label>
            <input type="text" class="form-control" id="section_school_year" name="school_year" 
                   value="2025-2026" required>
          </div>

          <div class="mb-3">
            <label for="section_adviser_id" class="form-label">Adviser (Optional)</label>
            <select class="form-select" id="section_adviser_id" name="adviser_id">
              <option value="">No Adviser (Assign Later)</option>
              <?php if (!empty($teachers)): ?>
                <?php foreach ($teachers as $teacher): ?>
                  <option value="<?= $teacher['id'] ?>">
                    <?= htmlspecialchars($teacher['name']) ?> 
                    (<?= htmlspecialchars($teacher['email']) ?>)
                    <?php if ($teacher['role'] === 'adviser' || ($teacher['is_adviser'] ?? 0)): ?>
                      - Adviser
                    <?php else: ?>
                      - Teacher
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
            <div class="form-text">Select a teacher to assign as class adviser. Teachers can be assigned later.</div>
          </div>

          <div class="mb-3">
            <label for="section_description" class="form-label">Description</label>
            <textarea class="form-control" id="section_description" name="description" rows="3" 
                      placeholder="Optional description"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Section</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Section Capacity Modal -->
<div class="modal fade" id="editCapacityModal" tabindex="-1" aria-labelledby="editCapacityModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editCapacityModalLabel">Edit Section Capacity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editCapacityForm" method="POST" action="<?= \Helpers\Url::to('/admin/update-section') ?>">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
          <input type="hidden" name="section_id" id="edit_section_id">
          
          <div class="alert alert-info">
            <strong>Current Status:</strong>
            <div id="capacityInfo" class="mt-2"></div>
          </div>

          <div class="mb-3">
            <label for="edit_max_students" class="form-label">New Maximum Students *</label>
            <input type="number" class="form-control" id="edit_max_students" name="max_students" 
                   min="1" max="100" required>
            <div class="form-text">Cannot be less than the current number of enrolled students</div>
          </div>

          <div class="mb-3">
            <label for="edit_room" class="form-label">Room</label>
            <input type="text" class="form-control" id="edit_room" name="room" 
                   placeholder="e.g., Room 101">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Capacity</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Assign Student Modal -->
<div class="modal fade" id="assignStudentModal" tabindex="-1" aria-labelledby="assignStudentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignStudentModalLabel">Assign Student to Section</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="assignStudentForm" method="POST" action="<?= \Helpers\Url::to('/admin/assign-student-to-section') ?>">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
          <input type="hidden" name="section_id" id="assign_section_id">
          
          <div class="alert alert-info" id="sectionInfo">
            <strong>Section:</strong> <span id="sectionNameDisplay"></span>
          </div>

          <div class="mb-3">
            <label for="studentSearch" class="form-label">Search Unassigned Students</label>
            <div class="input-group">
              <span class="input-group-text">
                <svg width="16" height="16" fill="currentColor">
                  <use href="#icon-search"></use>
                </svg>
              </span>
              <input type="text" class="form-control" id="studentSearch" 
                     placeholder="Search by name, LRN, or email...">
            </div>
          </div>

          <div class="table-responsive" style="max-height: 400px;">
            <table class="table table-hover" id="unassignedStudentsTable">
              <thead class="table-light sticky-top">
                <tr>
                  <th>Name</th>
                  <th>LRN</th>
                  <th>Grade Level</th>
                  <th>Email</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="studentsTableBody">
                <tr>
                  <td colspan="5" class="text-center text-muted">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Loading students...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Section Details Modal -->
<div class="modal fade" id="sectionDetailsModal" tabindex="-1" aria-labelledby="sectionDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="sectionDetailsModalLabel">Section Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="sectionDetailsContent">
        <div class="text-center">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2">Loading section details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php
// Get the correct path for the JavaScript file
$jsPath = \Helpers\Url::to('/assets/admin-sections.js');
?>
<!-- Load script synchronously - most reliable method -->
<script src="<?= $jsPath ?>"></script>
<script>
// Ensure functions are available and initialize
(function() {
    'use strict';
    
    // Verify critical functions are loaded
    function verifyFunctions() {
        const required = ['viewSectionDetails', 'editSectionCapacity', 'assignStudentToSection'];
        const missing = required.filter(fn => typeof window[fn] !== 'function');
        
        if (missing.length > 0) {
            console.error('Missing functions:', missing);
            return false;
        }
        return true;
    }
    
    // Wait for functions to be available (with timeout)
    function waitForFunctions(callback, maxAttempts = 50) {
        let attempts = 0;
        
        function check() {
            attempts++;
            if (verifyFunctions()) {
                console.log('All required functions loaded successfully');
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(check, 100);
            } else {
                console.error('Functions not available after', maxAttempts, 'attempts');
                console.error('viewSectionDetails:', typeof viewSectionDetails);
                console.error('editSectionCapacity:', typeof editSectionCapacity);
                console.error('assignStudentToSection:', typeof assignStudentToSection);
                
                // Try to reload script as last resort
                const script = document.createElement('script');
                script.src = <?= json_encode($jsPath) ?> + '?v=' + Date.now();
                script.onload = function() {
                    setTimeout(function() {
                        if (verifyFunctions()) {
                            callback();
                        } else {
                            console.error('Functions still not available after reload');
                        }
                    }, 100);
                };
                document.head.appendChild(script);
            }
        }
        
        // Start checking immediately
        check();
    }
    
    // Initialize page functionality
    function initSectionsPage() {
        // Auto-refresh functionality
        if (typeof refreshSectionData === 'function') {
            setInterval(function() {
                refreshSectionData();
            }, 30000);
        }
        
        // Filter functionality
        const searchInput = document.getElementById('searchInput');
        const gradeFilter = document.getElementById('gradeFilter');
        const statusFilter = document.getElementById('statusFilter');
        
        if (searchInput && typeof filterSections === 'function') {
            searchInput.addEventListener('keyup', filterSections);
        }
        if (gradeFilter && typeof filterSections === 'function') {
            gradeFilter.addEventListener('change', filterSections);
        }
        if (statusFilter && typeof filterSections === 'function') {
            statusFilter.addEventListener('change', filterSections);
        }
        
        // Student search input
        function initSearchInput() {
            const studentSearchInput = document.getElementById('studentSearch');
            if (studentSearchInput) {
                studentSearchInput.addEventListener('keyup', function() {
                    if (typeof searchUnassignedStudents === 'function') {
                        searchUnassignedStudents();
                    } else if (typeof loadUnassignedStudents === 'function') {
                        loadUnassignedStudents(this.value);
                    }
                });
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSearchInput);
        } else {
            initSearchInput();
        }
        
        console.log('Sections management initialized successfully');
    }
    
    // Event delegation for action buttons
    function handleActionClick(e) {
        // View Details button
        if (e.target.closest('.action-view-details')) {
            e.preventDefault();
            const btn = e.target.closest('.action-view-details');
            const sectionId = parseInt(btn.getAttribute('data-section-id'));
            
            if (typeof viewSectionDetails === 'function') {
                viewSectionDetails(sectionId);
            } else {
                console.error('viewSectionDetails not available');
                alert('Error: View function not loaded. Please refresh the page.');
            }
            return;
        }
        
        // Edit Capacity button
        if (e.target.closest('.action-edit-capacity')) {
            e.preventDefault();
            const btn = e.target.closest('.action-edit-capacity');
            const sectionId = parseInt(btn.getAttribute('data-section-id'));
            const currentMax = parseInt(btn.getAttribute('data-max-students'));
            const enrolled = parseInt(btn.getAttribute('data-enrolled'));
            
            if (typeof editSectionCapacity === 'function') {
                editSectionCapacity(sectionId, currentMax, enrolled);
            } else {
                console.error('editSectionCapacity not available');
                alert('Error: Edit function not loaded. Please refresh the page.');
            }
            return;
        }
        
        // Assign Student button
        if (e.target.closest('.action-assign-student')) {
            e.preventDefault();
            const btn = e.target.closest('.action-assign-student');
            if (btn.disabled) return;
            const sectionId = parseInt(btn.getAttribute('data-section-id'));
            const sectionName = btn.getAttribute('data-section-name');
            
            if (typeof assignStudentToSection === 'function') {
                assignStudentToSection(sectionId, sectionName);
            } else {
                console.error('assignStudentToSection not available');
                alert('Error: Assign function not loaded. Please refresh the page.');
            }
            return;
        }
        
        // Delete Section button
        if (e.target.closest('.action-delete-section')) {
            e.preventDefault();
            const btn = e.target.closest('.action-delete-section');
            const sectionId = parseInt(btn.getAttribute('data-section-id'));
            const sectionName = btn.getAttribute('data-section-name');
            const enrolled = parseInt(btn.getAttribute('data-enrolled')) || 0;
            
            // Confirmation prompt with details
            let confirmMessage = `Are you sure you want to delete section "${sectionName}"?\n\n`;
            if (enrolled > 0) {
                confirmMessage += `⚠️ WARNING: This section has ${enrolled} enrolled student(s). They will be unassigned from this section.\n\n`;
            }
            confirmMessage += 'This action cannot be undone.';
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Double confirmation for sections with students
            if (enrolled > 0) {
                if (!confirm(`Final confirmation: Delete section "${sectionName}" and unassign ${enrolled} student(s)?`)) {
                    return;
                }
            }
            
            // Get CSRF token
            const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
            if (!csrfTokenInput) {
                alert('Error: Security token not found. Please refresh the page.');
                return;
            }
            
            // Disable button during deletion
            btn.disabled = true;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('csrf_token', csrfTokenInput.value);
            formData.append('section_id', sectionId);
            
            // Get base URL
            const baseUrl = window.location.origin + (window.location.pathname.includes('/student-monitoring') ? '/student-monitoring' : '');
            const deleteUrl = `${baseUrl}/admin/delete-section`;
            
            // Send delete request with proper headers
            fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const json = JSON.parse(text);
                            throw new Error(json.error || 'Failed to delete section');
                        } catch (e) {
                            if (e instanceof Error && e.message) {
                                throw e;
                            }
                            throw new Error('Server error: ' + response.status + ' ' + response.statusText);
                        }
                    });
                }
                
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // If it's not JSON, try to parse as text
                    return response.text().then(text => {
                        // If it's a redirect or HTML, assume success and reload
                        return { success: true, message: 'Section deleted successfully!' };
                    });
                }
            })
            .then(data => {
                if (data && data.success) {
                    // Show success notification
                    if (typeof showNotification === 'function') {
                        showNotification(data.message || 'Section deleted successfully!', 'success');
                    } else {
                        alert(data.message || 'Section deleted successfully!');
                    }
                    
                    // ALWAYS reload the page after successful deletion to ensure database consistency
                    // This ensures the UI reflects the actual database state
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error notification
                    const errorMsg = (data && data.error) ? data.error : 'Failed to delete section';
                    if (typeof showNotification === 'function') {
                        showNotification(errorMsg, 'error');
                    } else {
                        alert('Error: ' + errorMsg);
                    }
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error deleting section:', error);
                const errorMsg = error.message || 'An error occurred while deleting the section. Please try again.';
                if (typeof showNotification === 'function') {
                    showNotification(errorMsg, 'error');
                } else {
                    alert('Error: ' + errorMsg);
                }
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
            
            return;
        }
    }
    
    // Attach event listener immediately
    document.addEventListener('click', handleActionClick);
    
    // Wait for functions and initialize
    waitForFunctions(function() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSectionsPage);
        } else {
            initSectionsPage();
        }
    });
})();
</script>

