<?php
$title = $title ?? 'Class Management';
$user = $user ?? null;
$activeNav = $activeNav ?? 'classes';
$classes = $classes ?? [];
$sections = $sections ?? [];
$subjects = $subjects ?? [];
$teachers = $teachers ?? [];
$error = $error ?? null;
$success = $success ?? null;
?>

<!-- Include the time management CSS -->
<link rel="stylesheet" href="<?= \Helpers\Url::to('/assets/admin-time-management.css') ?>">

<div class="container-fluid">
  <!-- Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 mb-1">Class Management</h1>
          <p class="text-muted mb-0">Manage classes, schedules, and teacher assignments with conflict detection</p>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
            <svg width="16" height="16" fill="currentColor" class="me-2">
              <use href="#icon-plus"></use>
            </svg>
            Add New Class
          </button>
          <a href="<?= \Helpers\Url::to('/admin/users') ?>" class="btn btn-outline-secondary">
            <svg width="16" height="16" fill="currentColor" class="me-2">
              <use href="#icon-arrow-left"></use>
            </svg>
            Back to Users
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Success/Error Messages -->
  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <svg width="16" height="16" fill="currentColor" class="me-2">
        <use href="#icon-check-circle"></use>
      </svg>
      <?php if ($_GET['success'] === 'class_created'): ?>
        Class created successfully! Schedule has been validated and saved.
      <?php endif; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <div class="d-flex align-items-start">
        <svg width="20" height="20" fill="currentColor" class="me-3 flex-shrink-0 mt-1">
          <use href="#icon-exclamation-triangle"></use>
        </svg>
        <div class="flex-grow-1">
          <strong>Error!</strong>
          <div class="mt-2" style="white-space: pre-line; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <?= htmlspecialchars($_GET['error']) ?>
          </div>
        </div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Classes Table -->
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">All Classes</h5>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshScheduleData()">
                <svg width="14" height="14" fill="currentColor" class="me-1">
                  <use href="#icon-refresh"></use>
                </svg>
                Refresh
              </button>
            </div>
          </div>
        </div>
        <div class="card-body p-0">
          <?php if (empty($classes)): ?>
            <div class="text-center py-5">
              <svg width="48" height="48" fill="currentColor" class="text-muted mb-3">
                <use href="#icon-graduation-cap"></use>
              </svg>
              <h5 class="text-muted">No Classes Found</h5>
              <p class="text-muted">Start by creating your first class assignment.</p>
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
                <svg width="16" height="16" fill="currentColor" class="me-2">
                  <use href="#icon-plus"></use>
                </svg>
                Add New Class
              </button>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Class Details</th>
                    <th>Section</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Schedule</th>
                    <th>Room</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($classes as $class): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                            <svg width="16" height="16" fill="currentColor" class="text-primary">
                              <use href="#icon-graduation-cap"></use>
                            </svg>
                          </div>
                          <div>
                            <h6 class="mb-0">Class #<?= $class['id'] ?></h6>
                            <small class="text-muted">Created: <?= date('M j, Y', strtotime($class['created_at'])) ?></small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div>
                          <span class="fw-semibold"><?= htmlspecialchars($class['section_name']) ?></span>
                          <br>
                          <small class="text-muted">Grade <?= $class['grade_level'] ?></small>
                        </div>
                      </td>
                      <td>
                        <div>
                          <span class="fw-semibold"><?= htmlspecialchars($class['subject_name']) ?></span>
                          <br>
                          <small class="text-muted"><?= htmlspecialchars($class['subject_code']) ?></small>
                        </div>
                      </td>
                      <td>
                        <div>
                          <span class="fw-semibold"><?= htmlspecialchars($class['teacher_name']) ?></span>
                          <br>
                          <small class="text-muted"><?= htmlspecialchars($class['teacher_email']) ?></small>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-info"><?= htmlspecialchars($class['schedule']) ?></span>
                      </td>
                      <td>
                        <span class="text-muted"><?= htmlspecialchars($class['class_room']) ?></span>
                      </td>
                      <td>
                        <?php if ($class['is_active']): ?>
                          <span class="badge bg-success">Active</span>
                        <?php else: ?>
                          <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <div class="btn-group" role="group">
                          <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewSchedule(<?= $class['teacher_id'] ?>, '<?= htmlspecialchars($class['teacher_name']) ?>')">
                            <svg width="14" height="14" fill="currentColor">
                              <use href="#icon-calendar"></use>
                            </svg>
                          </button>
                          <button type="button" class="btn btn-sm btn-outline-warning" onclick="editClass(<?= $class['id'] ?>)">
                            <svg width="14" height="14" fill="currentColor">
                              <use href="#icon-edit"></use>
                            </svg>
                          </button>
                          <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteClass(<?= $class['id'] ?>)">
                            <svg width="14" height="14" fill="currentColor">
                              <use href="#icon-trash"></use>
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
      </div>
    </div>
  </div>
</div>

<!-- Create Class Modal -->
<div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createClassModalLabel">Add New Class</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="createClassForm" method="POST" action="<?= \Helpers\Url::to('/admin/create-class') ?>">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
          
          <!-- Section Selection -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="section_id" class="form-label">Section *</label>
              <select class="form-select" id="section_id" name="section_id" required onchange="checkForDuplicateClass()">
                <option value="">Select Section</option>
                <?php foreach ($sections as $section): ?>
                  <option value="<?= $section['id'] ?>" data-section-name="<?= htmlspecialchars($section['name']) ?>" data-grade-level="<?= $section['grade_level'] ?>">
                    <?= htmlspecialchars($section['name']) ?> (Grade <?= $section['grade_level'] ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="subject_id" class="form-label">Subject *</label>
              <select class="form-select" id="subject_id" name="subject_id" required onchange="checkForDuplicateClass()">
                <option value="">Select Subject</option>
                <?php foreach ($subjects as $subject): ?>
                  <option value="<?= $subject['id'] ?>" data-subject-name="<?= htmlspecialchars($subject['name']) ?>" data-subject-code="<?= htmlspecialchars($subject['code']) ?>">
                    <?= htmlspecialchars($subject['name']) ?> (<?= htmlspecialchars($subject['code']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Teacher Selection -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="teacher_id" class="form-label">Teacher *</label>
              <select class="form-select" id="teacher_id" name="teacher_id" required onchange="loadTeacherSchedule()">
                <option value="">Select Teacher</option>
                <?php foreach ($teachers as $teacher): ?>
                  <option value="<?= $teacher['id'] ?>">
                    <?= htmlspecialchars($teacher['name']) ?> (<?= htmlspecialchars($teacher['department']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="room" class="form-label">Room *</label>
              <input type="text" class="form-control" id="room" name="room" placeholder="e.g., Room 101" required>
            </div>
          </div>

          <!-- Centralized Time Management -->
          <div class="time-control-container">
            <div class="time-control-header">
              <svg width="20" height="20" fill="currentColor" class="icon">
                <use href="#icon-clock"></use>
              </svg>
              <h5>Centralized Time Management</h5>
            </div>
            
            <div class="time-selection-grid">
              <div class="time-selection-group">
                <label for="day_of_week">Day of Week *</label>
                <select class="form-select" id="day_of_week" name="day_of_week" required onchange="updateTimeOptions()">
                  <option value="">Select Day</option>
                  <option value="Monday">Monday</option>
                  <option value="Tuesday">Tuesday</option>
                  <option value="Wednesday">Wednesday</option>
                  <option value="Thursday">Thursday</option>
                  <option value="Friday">Friday</option>
                  <option value="Saturday">Saturday</option>
                </select>
              </div>
              
              <div class="time-selection-group">
                <label for="start_time">Start Time *</label>
                <div class="time-input-container">
                  <select class="form-select" id="start_time" name="start_time" onchange="onStartTimeChange()">
                    <option value="">Select Start Time</option>
                  </select>
                  <input type="text" class="form-control" id="start_time_input" placeholder="e.g., 8:00 AM" onchange="onStartTimeChange()">
                  <button type="button" class="custom-input-toggle" onclick="toggleCustomInput('start_time')">✎</button>
                </div>
              </div>
              
              <div class="time-selection-group">
                <label for="end_time">End Time *</label>
                <div class="time-input-container">
                  <select class="form-select" id="end_time" name="end_time" onchange="onEndTimeChange()">
                    <option value="">Select End Time</option>
                  </select>
                  <input type="text" class="form-control" id="end_time_input" placeholder="e.g., 9:00 AM" onchange="onEndTimeChange()">
                  <button type="button" class="custom-input-toggle" onclick="toggleCustomInput('end_time')">✎</button>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <label for="semester" class="form-label">Semester</label>
                <select class="form-select" id="semester" name="semester" onchange="checkForDuplicateClass()">
                  <option value="1st">1st Semester</option>
                  <option value="2nd">2nd Semester</option>
                </select>
              </div>
              <div class="col-md-6 d-flex align-items-end gap-2">
                <button type="button" class="btn btn-outline-info btn-sm" id="testApiBtn" onclick="testAPI()">
                  Test API
                </button>
                <button type="button" class="btn check-availability-btn" id="checkAvailabilityBtn" onclick="checkAvailability()">
                  <svg width="16" height="16" fill="currentColor" class="me-2">
                    <use href="#icon-search"></use>
                  </svg>
                  Check Availability
                </button>
              </div>
            </div>
            
            <!-- Alert Container -->
            <div id="alertContainer" class="alert-container"></div>
          </div>

          <!-- Duplicate Class Warning -->
          <div id="duplicateWarning" class="alert alert-warning" style="display: none;">
            <div class="d-flex align-items-start">
              <svg width="20" height="20" fill="currentColor" class="me-2 flex-shrink-0">
                <use href="#icon-exclamation-triangle"></use>
              </svg>
              <div>
                <strong>⚠️ Warning: Possible Duplicate Class</strong>
                <div id="duplicateDetails" class="mt-2" style="white-space: pre-line;"></div>
              </div>
            </div>
          </div>

          <!-- Hidden schedule field for form submission -->
          <input type="hidden" id="schedule" name="schedule" value="">

          <!-- Enhanced Teacher Schedule Display -->
          <div id="teacherScheduleContainer" class="mb-3" style="display: none;">
            <div class="teacher-schedule-enhanced">
              <div class="teacher-schedule-header">
                <svg width="16" height="16" fill="currentColor" class="me-2">
                  <use href="#icon-calendar"></use>
                </svg>
                Teacher's Current Schedule
              </div>
              <div class="teacher-schedule-content">
                <div id="teacherScheduleDisplay" class="loading-enhanced">
                  <div class="loading-spinner-enhanced"></div>
                  Loading teacher schedule...
                </div>
              </div>
            </div>
          </div>

          <!-- Enhanced Conflict Warning -->
          <div id="conflictWarning" class="conflict-warning-enhanced" style="display: none;">
            <div class="alert-icon">⚠️</div>
            <strong>Schedule Conflict Detected!</strong>
            <div id="conflictDetails" class="mt-2"></div>
          </div>

          <!-- Enhanced Success Message -->
          <div id="scheduleSuccess" class="schedule-success-enhanced" style="display: none;">
            <div class="success-icon">✅</div>
            <strong>Schedule Available!</strong> No conflicts detected for this time slot.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
            <svg width="16" height="16" fill="currentColor" class="me-2">
              <use href="#icon-plus"></use>
            </svg>
            Create Class
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Teacher Schedule Modal -->
<div class="modal fade" id="teacherScheduleModal" tabindex="-1" aria-labelledby="teacherScheduleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="teacherScheduleModalLabel">Teacher Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="scheduleModalContent">
          <div class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading schedule...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Include the time management JavaScript -->
<script src="<?= \Helpers\Url::to('/assets/admin-time-management.js') ?>"></script>

<?php
// Prepare classes data for JavaScript
$classesData = array_map(function($class) {
    return [
        'id' => $class['id'],
        'section_id' => $class['section_id'],
        'subject_id' => $class['subject_id'],
        'semester' => $class['semester'],
        'school_year' => $class['school_year'],
        'section_name' => $class['section_name'],
        'subject_name' => $class['subject_name'],
        'subject_code' => $class['subject_code'],
        'teacher_name' => $class['teacher_name'],
        'schedule' => $class['schedule'],
        'room' => $class['room']
    ];
}, $classes);
?>

<script>
// Global variables
let currentTeacherId = null;
let currentSchedule = null;

// Existing classes data for duplicate checking
const existingClasses = <?= json_encode($classesData) ?>;

// Check for duplicate class
function checkForDuplicateClass() {
    const sectionId = document.getElementById('section_id').value;
    const subjectId = document.getElementById('subject_id').value;
    const semester = document.getElementById('semester').value;
    const schoolYear = '2025-2026'; // Current school year
    const duplicateWarning = document.getElementById('duplicateWarning');
    const duplicateDetails = document.getElementById('duplicateDetails');
    
    // Hide warning if required fields are not selected
    if (!sectionId || !subjectId) {
        duplicateWarning.style.display = 'none';
        return;
    }
    
    // Check for existing class with same section, subject, semester, and school year
    const duplicate = existingClasses.find(c => 
        c.section_id == sectionId && 
        c.subject_id == subjectId && 
        c.semester === semester && 
        c.school_year === schoolYear
    );
    
    if (duplicate) {
        // Get selected option text for better display
        const sectionSelect = document.getElementById('section_id');
        const subjectSelect = document.getElementById('subject_id');
        const selectedSection = sectionSelect.options[sectionSelect.selectedIndex];
        const selectedSubject = subjectSelect.options[subjectSelect.selectedIndex];
        
        duplicateDetails.innerHTML = `
A class with this combination already exists:

📚 Subject: ${duplicate.subject_name} (${duplicate.subject_code})
🏫 Section: ${duplicate.section_name}
📅 Semester: ${duplicate.semester} Semester, ${duplicate.school_year}
👨‍🏫 Teacher: ${duplicate.teacher_name}
🕐 Schedule: ${duplicate.schedule}
🚪 Room: ${duplicate.room}

💡 <strong>Please choose:</strong>
• A different subject for this section
• A different section for this subject
• A different semester (${semester === '1st' ? '2nd' : '1st'} Semester)
• Or edit the existing class instead
        `.trim();
        duplicateWarning.style.display = 'block';
        
        // Optionally disable submit button
        // document.getElementById('submitBtn').disabled = true;
    } else {
        duplicateWarning.style.display = 'none';
        // document.getElementById('submitBtn').disabled = false;
    }
}

// Load teacher schedule when teacher is selected
function loadTeacherSchedule() {
    const teacherId = document.getElementById('teacher_id').value;
    const container = document.getElementById('teacherScheduleContainer');
    const display = document.getElementById('teacherScheduleDisplay');
    
    if (!teacherId) {
        container.style.display = 'none';
        return;
    }
    
    currentTeacherId = teacherId;
    container.style.display = 'block';
    display.innerHTML = '<div class="text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading schedule...</div>';
    
    // Fetch teacher schedule
    fetch(`<?= \Helpers\Url::to('/api/admin/teacher-schedule.php') ?>?teacher_id=${teacherId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTeacherSchedule(data.schedules);
            } else {
                display.innerHTML = '<div class="text-center text-danger">Error loading schedule</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            display.innerHTML = '<div class="text-center text-danger">Error loading schedule</div>';
        });
}

// Display teacher schedule
function displayTeacherSchedule(schedules) {
    const display = document.getElementById('teacherScheduleDisplay');
    
    if (schedules.length === 0) {
        display.innerHTML = '<div class="text-center text-muted">No scheduled classes</div>';
        return;
    }
    
    // Group by day
    const scheduleByDay = {};
    schedules.forEach(schedule => {
        if (!scheduleByDay[schedule.day]) {
            scheduleByDay[schedule.day] = [];
        }
        scheduleByDay[schedule.day].push(schedule);
    });
    
    let html = '<div class="row">';
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    days.forEach(day => {
        const daySchedules = scheduleByDay[day] || [];
        html += `<div class="col-md-4 mb-2">
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="mb-0">${day}</h6>
                </div>
                <div class="card-body py-2">`;
        
        if (daySchedules.length === 0) {
            html += '<small class="text-muted">No classes</small>';
        } else {
            daySchedules.forEach(schedule => {
                html += `<div class="mb-1">
                    <small class="text-primary">${schedule.start}-${schedule.end}</small><br>
                    <small class="text-muted">${schedule.subject_name || 'Unknown Subject'}</small>
                </div>`;
            });
        }
        
        html += '</div></div></div>';
    });
    
    html += '</div>';
    display.innerHTML = html;
}

// Check for schedule conflicts
function checkScheduleConflict() {
    const teacherId = document.getElementById('teacher_id').value;
    const schedule = document.getElementById('schedule').value;
    const conflictWarning = document.getElementById('conflictWarning');
    const scheduleSuccess = document.getElementById('scheduleSuccess');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!teacherId || !schedule) {
        conflictWarning.style.display = 'none';
        scheduleSuccess.style.display = 'none';
        submitBtn.disabled = true;
        return;
    }
    
    // Parse schedule to get days and times
    const scheduleData = parseScheduleInput(schedule);
    if (!scheduleData) {
        conflictWarning.style.display = 'block';
        scheduleSuccess.style.display = 'none';
        submitBtn.disabled = true;
        document.getElementById('conflictDetails').innerHTML = 'Invalid schedule format. Use format like "MWF 8:00-9:00" or "TTH 10:00-11:00"';
        return;
    }
    
    // Check for conflicts
    const formData = new FormData();
    formData.append('teacher_id', teacherId);
    formData.append('days', scheduleData.days.join(','));
    formData.append('start_time', scheduleData.startTime);
    formData.append('end_time', scheduleData.endTime);
    
    fetch('<?= \Helpers\Url::to('/api/admin/check-schedule-conflict.php') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.has_conflict) {
                conflictWarning.style.display = 'block';
                scheduleSuccess.style.display = 'none';
                submitBtn.disabled = true;
                
                let conflictDetails = 'The teacher already has classes during this time:<br>';
                data.conflicts.forEach(conflict => {
                    conflictDetails += `• ${conflict.day} ${conflict.start}-${conflict.end}: ${conflict.subject_name}<br>`;
                });
                document.getElementById('conflictDetails').innerHTML = conflictDetails;
            } else {
                conflictWarning.style.display = 'none';
                scheduleSuccess.style.display = 'block';
                submitBtn.disabled = false;
            }
        } else {
            conflictWarning.style.display = 'block';
            scheduleSuccess.style.display = 'none';
            submitBtn.disabled = true;
            document.getElementById('conflictDetails').innerHTML = 'Error checking schedule: ' + data.message;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        conflictWarning.style.display = 'block';
        scheduleSuccess.style.display = 'none';
        submitBtn.disabled = true;
        document.getElementById('conflictDetails').innerHTML = 'Error checking schedule';
    });
}

// Parse schedule input
function parseScheduleInput(schedule) {
    const match = schedule.match(/^([MTWFS]+)\s+(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/);
    if (!match) return null;
    
    const dayCodes = match[1];
    const startTime = match[2] + ':00';
    const endTime = match[3] + ':00';
    
    const dayMap = {
        'M': 'Monday',
        'T': 'Tuesday',
        'W': 'Wednesday',
        'F': 'Friday',
        'S': 'Saturday'
    };
    
    const days = [];
    for (let i = 0; i < dayCodes.length; i++) {
        const code = dayCodes[i];
        if (code === 'T' && i + 1 < dayCodes.length && dayCodes[i + 1] === 'H') {
            days.push('Thursday');
            i++; // Skip next H
        } else {
            days.push(dayMap[code]);
        }
    }
    
    return {
        days: days.filter(day => day),
        startTime: startTime,
        endTime: endTime
    };
}

// View teacher schedule
function viewSchedule(teacherId, teacherName) {
    document.getElementById('teacherScheduleModalLabel').textContent = `${teacherName}'s Schedule`;
    document.getElementById('scheduleModalContent').innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading schedule...</p></div>';
    
    const modal = new bootstrap.Modal(document.getElementById('teacherScheduleModal'));
    modal.show();
    
    fetch(`<?= \Helpers\Url::to('/api/admin/teacher-schedule.php') ?>?teacher_id=${teacherId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayScheduleModal(data.schedules);
            } else {
                document.getElementById('scheduleModalContent').innerHTML = '<div class="text-center text-danger">Error loading schedule</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('scheduleModalContent').innerHTML = '<div class="text-center text-danger">Error loading schedule</div>';
        });
}

// Display schedule in modal
function displayScheduleModal(schedules) {
    const content = document.getElementById('scheduleModalContent');
    
    if (schedules.length === 0) {
        content.innerHTML = '<div class="text-center text-muted">No scheduled classes</div>';
        return;
    }
    
    // Group by day
    const scheduleByDay = {};
    schedules.forEach(schedule => {
        if (!scheduleByDay[schedule.day]) {
            scheduleByDay[schedule.day] = [];
        }
        scheduleByDay[schedule.day].push(schedule);
    });
    
    let html = '<div class="row">';
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    days.forEach(day => {
        const daySchedules = scheduleByDay[day] || [];
        html += `<div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">${day}</h6>
                </div>
                <div class="card-body">`;
        
        if (daySchedules.length === 0) {
            html += '<small class="text-muted">No classes</small>';
        } else {
            daySchedules.forEach(schedule => {
                html += `<div class="mb-2 p-2 border rounded">
                    <div class="d-flex justify-content-between">
                        <span class="text-primary fw-semibold">${schedule.start}-${schedule.end}</span>
                        <span class="badge bg-info">${schedule.subject_name || 'Unknown'}</span>
                    </div>
                    <small class="text-muted">${schedule.section_name || 'Unknown Section'}</small>
                </div>`;
            });
        }
        
        html += '</div></div></div>';
    });
    
    html += '</div>';
    content.innerHTML = html;
}

// Refresh schedule data
function refreshScheduleData() {
    location.reload();
}

// Edit class (placeholder)
function editClass(classId) {
    alert('Edit class functionality will be implemented in the next phase.');
}

// Delete class (placeholder)
function deleteClass(classId) {
    if (confirm('Are you sure you want to delete this class?')) {
        alert('Delete class functionality will be implemented in the next phase.');
    }
}
</script>
