/**
 * Admin Class Management JavaScript
 * Handles schedule conflict detection and UI interactions
 */

class ClassManagement {
    constructor() {
        this.currentTeacherId = null;
        this.currentSchedule = null;
        this.conflictCheckTimeout = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeFormValidation();
    }

    bindEvents() {
        // Teacher selection change
        const teacherSelect = document.getElementById('teacher_id');
        if (teacherSelect) {
            teacherSelect.addEventListener('change', () => this.loadTeacherSchedule());
        }

        // Schedule input change with debouncing
        const scheduleInput = document.getElementById('schedule');
        if (scheduleInput) {
            scheduleInput.addEventListener('input', () => this.debounceConflictCheck());
        }

        // Form submission
        const form = document.getElementById('createClassForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
    }

    initializeFormValidation() {
        // Real-time validation for schedule input
        const scheduleInput = document.getElementById('schedule');
        if (scheduleInput) {
            scheduleInput.addEventListener('blur', () => this.validateScheduleFormat());
        }
    }

    debounceConflictCheck() {
        clearTimeout(this.conflictCheckTimeout);
        this.conflictCheckTimeout = setTimeout(() => {
            this.checkScheduleConflict();
        }, 500); // 500ms delay
    }

    async loadTeacherSchedule() {
        const teacherId = document.getElementById('teacher_id').value;
        const container = document.getElementById('teacherScheduleContainer');
        const display = document.getElementById('teacherScheduleDisplay');
        
        if (!teacherId) {
            container.style.display = 'none';
            return;
        }
        
        this.currentTeacherId = teacherId;
        container.style.display = 'block';
        display.innerHTML = this.getLoadingHTML();
        
        try {
            const response = await fetch(`/api/admin/teacher-schedule.php?teacher_id=${teacherId}`);
            const data = await response.json();
            
            if (data.success) {
                this.displayTeacherSchedule(data.schedules);
            } else {
                display.innerHTML = this.getErrorHTML('Error loading schedule');
            }
        } catch (error) {
            console.error('Error:', error);
            display.innerHTML = this.getErrorHTML('Error loading schedule');
        }
    }

    displayTeacherSchedule(schedules) {
        const display = document.getElementById('teacherScheduleDisplay');
        
        if (schedules.length === 0) {
            display.innerHTML = '<div class="text-center text-muted">No scheduled classes</div>';
            return;
        }
        
        // Group by day
        const scheduleByDay = this.groupSchedulesByDay(schedules);
        
        let html = '<div class="row">';
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        days.forEach(day => {
            const daySchedules = scheduleByDay[day] || [];
            html += this.renderDaySchedule(day, daySchedules);
        });
        
        html += '</div>';
        display.innerHTML = html;
    }

    groupSchedulesByDay(schedules) {
        const scheduleByDay = {};
        schedules.forEach(schedule => {
            if (!scheduleByDay[schedule.day]) {
                scheduleByDay[schedule.day] = [];
            }
            scheduleByDay[schedule.day].push(schedule);
        });
        return scheduleByDay;
    }

    renderDaySchedule(day, daySchedules) {
        let html = `<div class="col-md-4 mb-2">
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
        return html;
    }

    async checkScheduleConflict() {
        const teacherId = document.getElementById('teacher_id').value;
        const schedule = document.getElementById('schedule').value;
        const conflictWarning = document.getElementById('conflictWarning');
        const scheduleSuccess = document.getElementById('scheduleSuccess');
        const submitBtn = document.getElementById('submitBtn');
        
        if (!teacherId || !schedule) {
            this.hideConflictMessages();
            submitBtn.disabled = true;
            return;
        }
        
        // Parse schedule to get days and times
        const scheduleData = this.parseScheduleInput(schedule);
        if (!scheduleData) {
            this.showConflictWarning('Invalid schedule format. Use format like "MWF 8:00-9:00" or "TTH 10:00-11:00"');
            submitBtn.disabled = true;
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('teacher_id', teacherId);
            formData.append('days', scheduleData.days.join(','));
            formData.append('start_time', scheduleData.startTime);
            formData.append('end_time', scheduleData.endTime);
            
            const response = await fetch('/api/admin/check-schedule-conflict.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (data.has_conflict) {
                    this.showConflictWarning(this.formatConflictDetails(data.conflicts));
                    submitBtn.disabled = true;
                } else {
                    this.showScheduleSuccess();
                    submitBtn.disabled = false;
                }
            } else {
                this.showConflictWarning('Error checking schedule: ' + data.message);
                submitBtn.disabled = true;
            }
        } catch (error) {
            console.error('Error:', error);
            this.showConflictWarning('Error checking schedule');
            submitBtn.disabled = true;
        }
    }

    parseScheduleInput(schedule) {
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

    validateScheduleFormat() {
        const scheduleInput = document.getElementById('schedule');
        const schedule = scheduleInput.value.trim();
        
        if (!schedule) return;
        
        const isValid = this.parseScheduleInput(schedule) !== null;
        
        if (isValid) {
            scheduleInput.classList.remove('is-invalid');
            scheduleInput.classList.add('is-valid');
        } else {
            scheduleInput.classList.remove('is-valid');
            scheduleInput.classList.add('is-invalid');
        }
    }

    showConflictWarning(message) {
        const conflictWarning = document.getElementById('conflictWarning');
        const scheduleSuccess = document.getElementById('scheduleSuccess');
        const conflictDetails = document.getElementById('conflictDetails');
        
        conflictDetails.innerHTML = message;
        conflictWarning.style.display = 'block';
        scheduleSuccess.style.display = 'none';
    }

    showScheduleSuccess() {
        const conflictWarning = document.getElementById('conflictWarning');
        const scheduleSuccess = document.getElementById('scheduleSuccess');
        
        conflictWarning.style.display = 'none';
        scheduleSuccess.style.display = 'block';
    }

    hideConflictMessages() {
        const conflictWarning = document.getElementById('conflictWarning');
        const scheduleSuccess = document.getElementById('scheduleSuccess');
        
        conflictWarning.style.display = 'none';
        scheduleSuccess.style.display = 'none';
    }

    formatConflictDetails(conflicts) {
        let details = 'The teacher already has classes during this time:<br>';
        conflicts.forEach(conflict => {
            details += `• ${conflict.day} ${conflict.start}-${conflict.end}: ${conflict.subject_name}<br>`;
        });
        return details;
    }

    async viewSchedule(teacherId, teacherName) {
        document.getElementById('teacherScheduleModalLabel').textContent = `${teacherName}'s Schedule`;
        document.getElementById('scheduleModalContent').innerHTML = this.getLoadingHTML();
        
        const modal = new bootstrap.Modal(document.getElementById('teacherScheduleModal'));
        modal.show();
        
        try {
            const response = await fetch(`/api/admin/teacher-schedule.php?teacher_id=${teacherId}`);
            const data = await response.json();
            
            if (data.success) {
                this.displayScheduleModal(data.schedules);
            } else {
                document.getElementById('scheduleModalContent').innerHTML = this.getErrorHTML('Error loading schedule');
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('scheduleModalContent').innerHTML = this.getErrorHTML('Error loading schedule');
        }
    }

    displayScheduleModal(schedules) {
        const content = document.getElementById('scheduleModalContent');
        
        if (schedules.length === 0) {
            content.innerHTML = '<div class="text-center text-muted">No scheduled classes</div>';
            return;
        }
        
        // Group by day
        const scheduleByDay = this.groupSchedulesByDay(schedules);
        
        let html = '<div class="row">';
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        days.forEach(day => {
            const daySchedules = scheduleByDay[day] || [];
            html += this.renderModalDaySchedule(day, daySchedules);
        });
        
        html += '</div>';
        content.innerHTML = html;
    }

    renderModalDaySchedule(day, daySchedules) {
        let html = `<div class="col-md-6 mb-3">
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
        return html;
    }

    handleFormSubmit(e) {
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn.disabled) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Creating Class...';
        submitBtn.disabled = true;
    }

    getLoadingHTML() {
        return '<div class="text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading schedule...</div>';
    }

    getErrorHTML(message) {
        return `<div class="text-center text-danger">${message}</div>`;
    }

    // Utility methods for global access
    refreshScheduleData() {
        location.reload();
    }

    editClass(classId) {
        alert('Edit class functionality will be implemented in the next phase.');
    }

    deleteClass(classId) {
        if (confirm('Are you sure you want to delete this class?')) {
            alert('Delete class functionality will be implemented in the next phase.');
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.classManagement = new ClassManagement();
});

// Global functions for backward compatibility
function loadTeacherSchedule() {
    if (window.classManagement) {
        window.classManagement.loadTeacherSchedule();
    }
}

function checkScheduleConflict() {
    if (window.classManagement) {
        window.classManagement.checkScheduleConflict();
    }
}

function viewSchedule(teacherId, teacherName) {
    if (window.classManagement) {
        window.classManagement.viewSchedule(teacherId, teacherName);
    }
}

function refreshScheduleData() {
    if (window.classManagement) {
        window.classManagement.refreshScheduleData();
    }
}

function editClass(classId) {
    if (window.classManagement) {
        window.classManagement.editClass(classId);
    }
}

function deleteClass(classId) {
    if (window.classManagement) {
        window.classManagement.deleteClass(classId);
    }
}
