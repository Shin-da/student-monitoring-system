/**
 * Enhanced Admin Class Management JavaScript
 * Handles dropdown-based schedule selection with AM/PM format
 */

class EnhancedClassManagement {
    constructor() {
        this.currentTeacherId = null;
        this.availableTimeSlots = {};
        this.occupiedSchedules = {};
        this.conflictCheckTimeout = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeTimeSlots();
    }

    bindEvents() {
        // Teacher selection change
        const teacherSelect = document.getElementById('teacher_id');
        if (teacherSelect) {
            teacherSelect.addEventListener('change', () => this.loadTeacherAvailability());
        }

        // Day selection change
        const daySelect = document.getElementById('day_of_week');
        if (daySelect) {
            daySelect.addEventListener('change', () => this.updateTimeSlots());
        }

        // Start time selection change
        const startTimeSelect = document.getElementById('start_time');
        if (startTimeSelect) {
            startTimeSelect.addEventListener('change', () => this.updateEndTimeOptions());
        }

        // End time selection change
        const endTimeSelect = document.getElementById('end_time');
        if (endTimeSelect) {
            endTimeSelect.addEventListener('change', () => this.updateScheduleDisplay());
        }

        // Form submission
        const form = document.getElementById('createClassForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
    }

    initializeTimeSlots() {
        // Generate standard time slots (7:00 AM to 5:00 PM)
        this.generateStandardTimeSlots();
    }

    generateStandardTimeSlots() {
        const slots = [];
        for (let hour = 7; hour < 17; hour++) {
            const startTime = this.formatTime(hour, 0);
            const endTime = this.formatTime(hour + 1, 0);
            slots.push({
                start_time: this.formatTime24(hour, 0),
                end_time: this.formatTime24(hour + 1, 0),
                start_ampm: startTime,
                end_ampm: endTime,
                display: `${startTime} - ${endTime}`
            });
        }
        this.standardTimeSlots = slots;
    }

    formatTime(hour, minute) {
        const period = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour === 0 ? 12 : (hour > 12 ? hour - 12 : hour);
        return `${displayHour}:${minute.toString().padStart(2, '0')} ${period}`;
    }

    formatTime24(hour, minute) {
        return `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}:00`;
    }

    async loadTeacherAvailability() {
        const teacherId = document.getElementById('teacher_id').value;
        const container = document.getElementById('teacherScheduleContainer');
        const display = document.getElementById('teacherScheduleDisplay');
        
        if (!teacherId) {
            container.style.display = 'none';
            this.clearScheduleSelections();
            return;
        }
        
        this.currentTeacherId = teacherId;
        container.style.display = 'block';
        display.innerHTML = this.getLoadingHTML();
        
        try {
            // Load available time slots
            const response = await fetch(`/api/admin/available-time-slots.php?teacher_id=${teacherId}`);
            const data = await response.json();
            
            if (data.success) {
                this.availableTimeSlots = data.available_slots;
                this.occupiedSchedules = data.occupied_schedules;
                this.displayTeacherSchedule(data.occupied_schedules);
                this.updateTimeSlots();
            } else {
                display.innerHTML = this.getErrorHTML('Error loading teacher availability');
            }
        } catch (error) {
            console.error('Error:', error);
            display.innerHTML = this.getErrorHTML('Error loading teacher availability');
        }
    }

    displayTeacherSchedule(occupiedSchedules) {
        const display = document.getElementById('teacherScheduleDisplay');
        
        if (occupiedSchedules.length === 0) {
            display.innerHTML = '<div class="text-center text-muted">No scheduled classes</div>';
            return;
        }
        
        // Group by day
        const scheduleByDay = this.groupSchedulesByDay(occupiedSchedules);
        
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
        let html = `<div class="col-md-4 mb-3">
            <div class="day-schedule-card-enhanced">
                <div class="day-schedule-header-enhanced">
                    <h6 class="mb-0">${day}</h6>
                </div>
                <div class="day-schedule-body">`;
        
        if (daySchedules.length === 0) {
            html += '<small class="text-muted">No classes</small>';
        } else {
            daySchedules.forEach(schedule => {
                html += `<div class="schedule-time-slot-enhanced occupied">
                    <div class="fw-semibold">${schedule.start_ampm}-${schedule.end_ampm}</div>
                    <small class="text-muted">${schedule.subject_name || 'Unknown Subject'}</small>
                </div>`;
            });
        }
        
        html += '</div></div></div>';
        return html;
    }

    updateTimeSlots() {
        const daySelect = document.getElementById('day_of_week');
        const startTimeSelect = document.getElementById('start_time');
        const endTimeSelect = document.getElementById('end_time');
        
        const selectedDay = daySelect.value;
        
        // Clear existing options
        startTimeSelect.innerHTML = '<option value="">Select Start Time</option>';
        endTimeSelect.innerHTML = '<option value="">Select End Time</option>';
        
        if (!selectedDay) {
            return;
        }
        
        // Get available slots for the selected day
        const daySlots = this.availableTimeSlots[selectedDay] || [];
        
        if (daySlots.length === 0) {
            startTimeSelect.innerHTML = '<option value="">No available slots</option>';
            startTimeSelect.disabled = true;
            endTimeSelect.disabled = true;
            return;
        }
        
        // Populate start time options
        daySlots.forEach(slot => {
            const option = document.createElement('option');
            option.value = slot.start_time;
            option.textContent = slot.start_ampm;
            option.dataset.endTime = slot.end_time;
            option.dataset.endAmpm = slot.end_ampm;
            startTimeSelect.appendChild(option);
        });
        
        startTimeSelect.disabled = false;
        endTimeSelect.disabled = false;
    }

    updateEndTimeOptions() {
        const startTimeSelect = document.getElementById('start_time');
        const endTimeSelect = document.getElementById('end_time');
        const daySelect = document.getElementById('day_of_week');
        
        const selectedStartTime = startTimeSelect.value;
        const selectedDay = daySelect.value;
        
        // Clear existing options
        endTimeSelect.innerHTML = '<option value="">Select End Time</option>';
        
        if (!selectedStartTime || !selectedDay) {
            return;
        }
        
        // Get available slots for the selected day
        const daySlots = this.availableTimeSlots[selectedDay] || [];
        
        // Find the selected start time slot
        const selectedSlot = daySlots.find(slot => slot.start_time === selectedStartTime);
        if (selectedSlot) {
            // Add the corresponding end time
            const option = document.createElement('option');
            option.value = selectedSlot.end_time;
            option.textContent = selectedSlot.end_ampm;
            endTimeSelect.appendChild(option);
        }
    }

    updateScheduleDisplay() {
        const daySelect = document.getElementById('day_of_week');
        const startTimeSelect = document.getElementById('start_time');
        const endTimeSelect = document.getElementById('end_time');
        const scheduleInput = document.getElementById('schedule');
        
        const day = daySelect.value;
        const startTime = startTimeSelect.value;
        const endTime = endTimeSelect.value;
        
        if (day && startTime && endTime) {
            // Convert to readable format
            const startAmpm = this.formatTimeFrom24(startTime);
            const endAmpm = this.formatTimeFrom24(endTime);
            
            // Create schedule string
            const dayCode = this.getDayCode(day);
            const scheduleString = `${dayCode} ${startAmpm}-${endAmpm}`;
            
            scheduleInput.value = scheduleString;
            
            // Check for conflicts
            this.checkScheduleConflict();
        } else {
            scheduleInput.value = '';
            this.hideConflictMessages();
        }
    }

    formatTimeFrom24(time24) {
        const [hours, minutes] = time24.split(':');
        const hour = parseInt(hours);
        const period = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour === 0 ? 12 : (hour > 12 ? hour - 12 : hour);
        return `${displayHour}:${minutes} ${period}`;
    }

    getDayCode(day) {
        const dayCodes = {
            'Monday': 'M',
            'Tuesday': 'T',
            'Wednesday': 'W',
            'Thursday': 'TH',
            'Friday': 'F',
            'Saturday': 'S'
        };
        return dayCodes[day] || day;
    }

    async checkScheduleConflict() {
        const daySelect = document.getElementById('day_of_week');
        const startTimeSelect = document.getElementById('start_time');
        const endTimeSelect = document.getElementById('end_time');
        const submitBtn = document.getElementById('submitBtn');
        
        const day = daySelect.value;
        const startTime = startTimeSelect.value;
        const endTime = endTimeSelect.value;
        
        if (!day || !startTime || !endTime) {
            this.hideConflictMessages();
            submitBtn.disabled = true;
            return;
        }
        
        // Check against occupied schedules
        const isConflict = this.isTimeSlotOccupied(day, startTime, endTime);
        
        if (isConflict) {
            this.showConflictWarning('This teacher already has a class at that time. Please choose another schedule.');
            submitBtn.disabled = true;
        } else {
            this.showScheduleSuccess();
            submitBtn.disabled = false;
        }
    }

    isTimeSlotOccupied(day, startTime, endTime) {
        const daySchedules = this.occupiedSchedules.filter(schedule => schedule.day === day);
        
        for (const schedule of daySchedules) {
            // Check if there's any overlap
            if (startTime < schedule.end && endTime > schedule.start) {
                return true;
            }
        }
        return false;
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

    clearScheduleSelections() {
        document.getElementById('day_of_week').value = '';
        document.getElementById('start_time').innerHTML = '<option value="">Select Start Time</option>';
        document.getElementById('end_time').innerHTML = '<option value="">Select End Time</option>';
        document.getElementById('schedule').value = '';
        this.hideConflictMessages();
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
        return '<div class="loading-enhanced"><div class="loading-spinner-enhanced"></div>Loading availability...</div>';
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
    window.enhancedClassManagement = new EnhancedClassManagement();
});

// Global functions for backward compatibility
function loadTeacherSchedule() {
    if (window.enhancedClassManagement) {
        window.enhancedClassManagement.loadTeacherAvailability();
    }
}

function updateTimeSlots() {
    if (window.enhancedClassManagement) {
        window.enhancedClassManagement.updateTimeSlots();
    }
}

function updateEndTimeOptions() {
    if (window.enhancedClassManagement) {
        window.enhancedClassManagement.updateEndTimeOptions();
    }
}

function updateScheduleDisplay() {
    if (window.enhancedClassManagement) {
        window.enhancedClassManagement.updateScheduleDisplay();
    }
}

function checkScheduleConflict() {
    if (window.enhancedClassManagement) {
        window.enhancedClassManagement.checkScheduleConflict();
    }
}

function viewSchedule(teacherId, teacherName) {
    if (window.enhancedClassManagement) {
        window.enhancedClassManagement.viewSchedule(teacherId, teacherName);
    }
}

function refreshScheduleData() {
    if (window.enhancedClassManagement) {
        window.enhancedClassManagement.refreshScheduleData();
    }
}

function editClass(classId) {
    if (window.enhancedClassManagement) {
        window.enhancedClassManagement.editClass(classId);
    }
}

function deleteClass(classId) {
    if (window.enhancedClassManagement) {
        window.enhancedClassManagement.deleteClass(classId);
    }
}
