/**
 * Admin Time Management JavaScript
 * Centralized time selection with dropdown and manual input support
 */

class TimeManagement {
    constructor() {
        this.currentTeacherId = null;
        this.teacherSchedules = []; // Selected teacher's schedules
        this.allTeachersSchedules = {}; // All teachers' schedules by day (for conflict prevention)
        this.availableSlots = {};
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
            teacherSelect.addEventListener('change', () => this.loadTeacherSchedule());
        }

        // Day selection change
        const daySelect = document.getElementById('day_of_week');
        if (daySelect) {
            daySelect.addEventListener('change', () => {
                this.loadAllTeachersSchedulesForDay(daySelect.value);
                this.updateTimeOptions();
            });
        }

        // Start time selection/input change
        const startTimeSelect = document.getElementById('start_time');
        const startTimeInput = document.getElementById('start_time_input');
        if (startTimeSelect) {
            startTimeSelect.addEventListener('change', () => this.onStartTimeChange());
        }
        if (startTimeInput) {
            startTimeInput.addEventListener('input', () => this.onStartTimeChange());
            startTimeInput.addEventListener('blur', () => this.validateTimeFormat('start_time_input'));
        }

        // End time selection/input change
        const endTimeSelect = document.getElementById('end_time');
        const endTimeInput = document.getElementById('end_time_input');
        if (endTimeSelect) {
            endTimeSelect.addEventListener('change', () => this.onEndTimeChange());
        }
        if (endTimeInput) {
            endTimeInput.addEventListener('input', () => this.onEndTimeChange());
            endTimeInput.addEventListener('blur', () => this.validateTimeFormat('end_time_input'));
        }

        // Check availability button
        const checkBtn = document.getElementById('checkAvailabilityBtn');
        if (checkBtn) {
            checkBtn.addEventListener('click', () => this.checkAvailability());
        }

        // Form submission
        const form = document.getElementById('createClassForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
    }

    initializeTimeSlots() {
        // Generate time slots from 7:00 AM to 6:00 PM in 30-minute intervals
        this.timeSlots = this.generateTimeSlots();
    }

    generateTimeSlots() {
        const slots = [];
        const startHour = 7; // 7:00 AM
        const endHour = 18; // 6:00 PM
        
        for (let hour = startHour; hour < endHour; hour++) {
            for (let minute = 0; minute < 60; minute += 30) {
                const time24 = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}:00`;
                const time12 = this.formatTo12Hour(time24);
                
                slots.push({
                    value: time24,
                    display: time12,
                    hour: hour,
                    minute: minute
                });
            }
        }
        
        return slots;
    }

    formatTo12Hour(time24) {
        const [hours, minutes] = time24.split(':');
        const hour = parseInt(hours);
        const period = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour === 0 ? 12 : (hour > 12 ? hour - 12 : hour);
        return `${displayHour}:${minutes} ${period}`;
    }

    async loadTeacherSchedule() {
        const teacherId = document.getElementById('teacher_id').value;
        const container = document.getElementById('teacherScheduleContainer');
        const display = document.getElementById('teacherScheduleDisplay');
        
        // Treat empty string or "0" as "no valid teacher selected"
        if (!teacherId || teacherId === '0') {
            container.style.display = 'none';
            this.clearTimeSelections();
            return;
        }
        
        this.currentTeacherId = teacherId;
        container.style.display = 'block';
        display.innerHTML = this.getLoadingHTML();
        
        try {
            const response = await fetch(`../api/admin/teacher-schedule.php?teacher_id=${teacherId}`);
            const data = await response.json();
            
            if (data.success) {
                // Deduplicate schedules before storing (remove exact duplicates)
                const uniqueSchedules = [];
                const seen = new Set();
                
                data.schedules.forEach(schedule => {
                    const key = `${schedule.day}-${schedule.start}-${schedule.end}-${schedule.class_id || 'null'}`;
                    if (!seen.has(key)) {
                        seen.add(key);
                        uniqueSchedules.push(schedule);
                    }
                });
                
                this.teacherSchedules = uniqueSchedules;
                this.displayTeacherSchedule(uniqueSchedules);
                this.updateTimeOptions();
            } else {
                display.innerHTML = this.getErrorHTML('Error loading teacher schedule');
            }
        } catch (error) {
            console.error('Error:', error);
            display.innerHTML = this.getErrorHTML('Error loading teacher schedule');
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
        // Track seen schedules to prevent duplicates (same day, start, end, class_id)
        const seen = new Set();
        
        schedules.forEach(schedule => {
            // Create unique key: day + start + end + class_id
            const key = `${schedule.day}-${schedule.start}-${schedule.end}-${schedule.class_id || 'null'}`;
            
            // Skip if we've already seen this exact schedule
            if (seen.has(key)) {
                return;
            }
            seen.add(key);
            
            if (!scheduleByDay[schedule.day]) {
                scheduleByDay[schedule.day] = [];
            }
            scheduleByDay[schedule.day].push(schedule);
        });
        return scheduleByDay;
    }

    renderDaySchedule(day, daySchedules) {
        let html = `<div class="col-md-4 mb-3">
            <div class="day-schedule-card">
                <div class="day-schedule-header">
                    <h6 class="mb-0">${day}</h6>
                </div>
                <div class="day-schedule-body">`;
        
        if (daySchedules.length === 0) {
            html += '<small class="text-muted">No classes</small>';
        } else {
            daySchedules.forEach(schedule => {
                html += `<div class="schedule-time-slot occupied">
                    <div class="fw-semibold">${schedule.start_ampm}-${schedule.end_ampm}</div>
                    <small class="text-muted">${schedule.subject_name || 'Unknown Subject'}</small>
                </div>`;
            });
        }
        
        html += '</div></div></div>';
        return html;
    }

    async updateTimeOptions() {
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
        
        // Load all teachers' schedules for this day (if not already loaded)
        await this.loadAllTeachersSchedulesForDay(selectedDay);
        
        // Get occupied times for the selected day (from all teachers)
        const occupiedTimes = this.getOccupiedTimesForDay(selectedDay);
        
        // Populate start time options
        this.timeSlots.forEach(slot => {
            const isOccupied = this.isTimeOccupied(slot.value, occupiedTimes);
            const option = document.createElement('option');
            option.value = slot.value;
            option.textContent = slot.display;
            option.disabled = isOccupied;
            if (isOccupied) {
                // Find which teacher(s) have this slot occupied
                const occupiers = occupiedTimes.filter(occ => {
                    const slotMinutes = this.timeToMinutes(slot.value);
                    const occStart = this.timeToMinutes(occ.start);
                    const occEnd = this.timeToMinutes(occ.end);
                    return slotMinutes >= occStart && slotMinutes < occEnd;
                });
                
                if (occupiers.length > 0) {
                    const teacherNames = occupiers
                        .map(occ => occ.teacher_name || `Teacher ID ${occ.teacher_id}`)
                        .filter((name, idx, arr) => arr.indexOf(name) === idx) // unique
                        .join(', ');
                    option.textContent += ` (Occupied by ${teacherNames})`;
                } else {
                option.textContent += ' (Occupied)';
                }
            }
            startTimeSelect.appendChild(option);
        });
    }

    async loadAllTeachersSchedulesForDay(day) {
        if (!day) {
            this.allTeachersSchedules[day] = [];
            return;
        }

        // If we already loaded this day's schedules, use cached data
        if (this.allTeachersSchedules[day]) {
            return;
        }

        try {
            const base = (window.__BASE_PATH__ || '').replace(/\/$/, '');
            const response = await fetch(`${base}/api/admin/check-all-teachers-schedule.php?day=${encodeURIComponent(day)}`);
            const data = await response.json();
            
            if (data.success) {
                // Store all occupied schedules for this day
                this.allTeachersSchedules[day] = data.occupied_schedules || [];
            } else {
                this.allTeachersSchedules[day] = [];
            }
        } catch (error) {
            console.error('Error loading all teachers schedules:', error);
            this.allTeachersSchedules[day] = [];
        }
    }

    getOccupiedTimesForDay(day) {
        // Combine selected teacher's schedules with all teachers' schedules for conflict prevention
        const selectedTeacherSchedules = this.teacherSchedules
            .filter(schedule => schedule.day === day)
            .map(schedule => ({
                start: schedule.start,
                end: schedule.end,
                teacher_id: this.currentTeacherId
            }));

        // Get all teachers' schedules for this day (from cache or empty array)
        const allTeachersSchedules = (this.allTeachersSchedules[day] || []).map(schedule => ({
            start: schedule.start_time,
            end: schedule.end_time,
            teacher_id: schedule.teacher_id,
            teacher_name: schedule.teacher_name
        }));

        // Combine and deduplicate
        const allOccupied = [...selectedTeacherSchedules, ...allTeachersSchedules];
        const uniqueOccupied = [];
        const seen = new Set();

        allOccupied.forEach(occupied => {
            const key = `${occupied.start}-${occupied.end}`;
            if (!seen.has(key)) {
                seen.add(key);
                uniqueOccupied.push(occupied);
            }
        });

        return uniqueOccupied;
    }

    isTimeOccupied(time, occupiedTimes) {
        // Check if the time slot overlaps with any occupied time range
        // A time slot is occupied if it falls within or overlaps an occupied range
        return occupiedTimes.some(occupied => {
            const timeValue = this.timeToMinutes(time);
            const occupiedStart = this.timeToMinutes(occupied.start);
            const occupiedEnd = this.timeToMinutes(occupied.end);
            
            // Check if time falls within the occupied range
            // Also check for partial overlaps (time slot starts before occupied ends, or ends after occupied starts)
            return timeValue >= occupiedStart && timeValue < occupiedEnd;
        });
    }

    timeToMinutes(timeString) {
        // Convert "HH:MM:SS" or "HH:MM" to minutes since midnight
        const parts = timeString.split(':');
        const hours = parseInt(parts[0], 10);
        const minutes = parseInt(parts[1] || 0, 10);
        return hours * 60 + minutes;
    }

    onStartTimeChange() {
        const startTimeSelect = document.getElementById('start_time');
        const startTimeInput = document.getElementById('start_time_input');
        const endTimeSelect = document.getElementById('end_time');
        
        const startTime = startTimeSelect.value || startTimeInput.value;
        
        // Clear end time options
        endTimeSelect.innerHTML = '<option value="">Select End Time</option>';
        
        if (!startTime) {
            return;
        }
        
        // Update end time input with suggested end time
        const suggestedEndTime = this.getSuggestedEndTime(startTime);
        const endTimeInput = document.getElementById('end_time_input');
        if (endTimeInput && suggestedEndTime) {
            endTimeInput.value = suggestedEndTime;
        }
        
        // Populate end time options
        this.populateEndTimeOptions(startTime);
        
        // Update schedule display
        this.updateScheduleDisplay();
    }

    onEndTimeChange() {
        this.updateScheduleDisplay();
    }

    getSuggestedEndTime(startTime) {
        const startHour = parseInt(startTime.split(':')[0]);
        const startMinute = parseInt(startTime.split(':')[1]);
        
        // Add 1 hour to start time
        let endHour = startHour + 1;
        let endMinute = startMinute;
        
        if (endHour >= 24) {
            endHour = 23;
            endMinute = 59;
        }
        
        return `${endHour.toString().padStart(2, '0')}:${endMinute.toString().padStart(2, '0')}:00`;
    }

    populateEndTimeOptions(startTime) {
        const endTimeSelect = document.getElementById('end_time');
        const daySelect = document.getElementById('day_of_week');
        const selectedDay = daySelect.value;
        
        const startHour = parseInt(startTime.split(':')[0]);
        const startMinute = parseInt(startTime.split(':')[1]);
        
        // Get occupied times for the selected day to check end time conflicts
        const occupiedTimes = this.getOccupiedTimesForDay(selectedDay);
        
        // Only show times after the start time
        this.timeSlots.forEach(slot => {
            const slotHour = parseInt(slot.value.split(':')[0]);
            const slotMinute = parseInt(slot.value.split(':')[1]);
            
            if (slotHour > startHour || (slotHour === startHour && slotMinute > startMinute)) {
                // Check if this end time would create a conflict
                // A conflict occurs if the time range (startTime to slot.value) overlaps with any occupied time
                const wouldConflict = this.wouldTimeRangeConflict(startTime, slot.value, occupiedTimes);
                
                const option = document.createElement('option');
                option.value = slot.value;
                option.textContent = slot.display;
                option.disabled = wouldConflict;
                
                if (wouldConflict) {
                    option.textContent += ' (Occupied)';
                }
                
                endTimeSelect.appendChild(option);
            }
        });
    }

    wouldTimeRangeConflict(startTime, endTime, occupiedTimes) {
        // Check if the time range from startTime to endTime conflicts with any occupied time
        const startMinutes = this.timeToMinutes(startTime);
        const endMinutes = this.timeToMinutes(endTime);
        
        return occupiedTimes.some(occupied => {
            const occupiedStart = this.timeToMinutes(occupied.start);
            const occupiedEnd = this.timeToMinutes(occupied.end);
            
            // Overlap occurs if: new start < existing end AND new end > existing start
            return startMinutes < occupiedEnd && endMinutes > occupiedStart;
        });
    }

    updateScheduleDisplay() {
        const daySelect = document.getElementById('day_of_week');
        const startTimeSelect = document.getElementById('start_time');
        const startTimeInput = document.getElementById('start_time_input');
        const endTimeSelect = document.getElementById('end_time');
        const endTimeInput = document.getElementById('end_time_input');
        const scheduleInput = document.getElementById('schedule');
        
        const day = daySelect.value;
        const startTime = startTimeSelect.value || startTimeInput.value;
        const endTime = endTimeSelect.value || endTimeInput.value;
        
        if (day && startTime && endTime) {
            // Convert to readable format
            const startAmpm = this.formatTo12Hour(startTime);
            const endAmpm = this.formatTo12Hour(endTime);
            
            // Create schedule string
            const dayCode = this.getDayCode(day);
            const scheduleString = `${dayCode} ${startAmpm}-${endAmpm}`;
            
            scheduleInput.value = scheduleString;
        } else {
            scheduleInput.value = '';
        }
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

    validateTimeFormat(inputId) {
        const input = document.getElementById(inputId);
        const timeValue = input.value.trim();
        
        if (!timeValue) {
            input.classList.remove('is-valid', 'is-invalid');
            return;
        }
        
        // Try to parse the time
        const timestamp = strtotime(timeValue);
        if (timestamp !== false) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }
    }

    async testAPI() {
        try {
            console.log('Testing API endpoint...');
            const response = await fetch('../api/admin/test-schedule.php');
            const data = await response.json();
            console.log('API test response:', data);
            this.showAlert('API test successful: ' + data.message, 'success');
        } catch (error) {
            console.error('API test failed:', error);
            this.showAlert('API test failed: ' + error.message, 'danger');
        }
    }

    async checkAvailability() {
        const daySelect = document.getElementById('day_of_week');
        const startTimeSelect = document.getElementById('start_time');
        const startTimeInput = document.getElementById('start_time_input');
        const endTimeSelect = document.getElementById('end_time');
        const endTimeInput = document.getElementById('end_time_input');
        const checkBtn = document.getElementById('checkAvailabilityBtn');
        const teacherId = this.currentTeacherId || document.getElementById('teacher_id').value;

        const day = daySelect.value;
        const startTimeRaw = startTimeSelect.value || startTimeInput.value;
        const endTimeRaw = endTimeSelect.value || endTimeInput.value;

        if (!teacherId) {
            this.showAlert('Please select a teacher first.', 'warning');
            return;
        }

        if (!day || !startTimeRaw || !endTimeRaw) {
            this.showAlert('Please select day, start time, and end time.', 'warning');
            return;
        }

        const startTime = this.normalizeTimeInput(startTimeRaw);
        const endTime = this.normalizeTimeInput(endTimeRaw);
        if (!this.isValidTime(startTime) || !this.isValidTime(endTime)) {
            console.warn('Invalid time detected', { startTime, endTime, startTimeRaw, endTimeRaw });
            this.showAlert('Invalid time format. Please use HH:MM.', 'danger');
            return;
        }

        // First, check if this time slot is already occupied by ANY teacher (client-side check)
        await this.loadAllTeachersSchedulesForDay(day);
        const occupiedTimes = this.getOccupiedTimesForDay(day);
        const startWithSeconds = this.ensureSeconds(startTime);
        const endWithSeconds = this.ensureSeconds(endTime);
        
        // Check for conflicts with all teachers
        const conflicts = occupiedTimes.filter(occ => {
            const occStart = this.timeToMinutes(occ.start);
            const occEnd = this.timeToMinutes(occ.end);
            const newStart = this.timeToMinutes(startWithSeconds);
            const newEnd = this.timeToMinutes(endWithSeconds);
            
            // Overlap: new start < existing end AND new end > existing start
            return newStart < occEnd && newEnd > occStart;
        });

        if (conflicts.length > 0) {
            const conflictTeachers = conflicts
                .map(occ => occ.teacher_name || `Teacher ID ${occ.teacher_id}`)
                .filter((name, idx, arr) => arr.indexOf(name) === idx) // unique
                .join(', ');
            
            this.showAlert(`⚠️ Schedule conflict: This time slot is already occupied by ${conflictTeachers}. Please choose a different time.`, 'danger');
            this.disableSubmitButton();
            return;
        }

        const payload = {
            teacherId: isNaN(Number(teacherId)) ? teacherId : Number(teacherId),
            day,
            startTime,
            endTime
        };

        console.log('checkAvailability payload:', payload);

        const originalText = checkBtn.innerHTML;
        checkBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Checking...';
        checkBtn.disabled = true;

        try {
            const response = await fetch('../api/admin/check-schedule-fixed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            let data = null;
            try {
                data = await response.json();
            } catch (parseError) {
                console.error('Failed to parse availability response', parseError);
                throw new Error('Invalid response from server.');
            }

            console.log('Availability response:', response.status, data);

            if (!response.ok) {
                throw new Error(data?.message || `HTTP error! status: ${response.status}`);
            }

            if (data.status === 'available') {
                this.showAlert('✅ Time slot is available!', 'success');

                // Optimistically add this new slot into the in-memory teacher schedule
                // so the "Teacher's Current Schedule" panel and time options reflect it
                const startWithSeconds = this.ensureSeconds(startTime);
                const endWithSeconds = this.ensureSeconds(endTime);

                // Remove any existing temporary entry with the same day/start/end to avoid duplicates
                this.teacherSchedules = this.teacherSchedules.filter(s => {
                    return !(s.day === day && s.start === startWithSeconds && s.end === endWithSeconds && s._temp === true);
                });

                // Get subject name for display (fallback to generic label)
                let subjectName = 'New Class';
                const subjectSelect = document.getElementById('subject_id');
                if (subjectSelect && subjectSelect.options && subjectSelect.selectedIndex > 0) {
                    const opt = subjectSelect.options[subjectSelect.selectedIndex];
                    subjectName = opt.getAttribute('data-subject-name') || opt.textContent.trim() || subjectName;
                }

                const tempSchedule = {
                    id: 'temp-' + Date.now(),
                    day: day,
                    start: startWithSeconds,
                    end: endWithSeconds,
                    start_ampm: this.formatTo12Hour(startWithSeconds),
                    end_ampm: this.formatTo12Hour(endWithSeconds),
                    subject_name: subjectName,
                    class_schedule: null,
                    _temp: true // mark as temporary (not yet saved in DB)
                };

                this.teacherSchedules.push(tempSchedule);
                this.displayTeacherSchedule(this.teacherSchedules);
                // Clear cache for this day so it reloads with the new schedule
                delete this.allTeachersSchedules[day];
                await this.updateTimeOptions();

                this.enableSubmitButton();
            } else if (data.status === 'conflict') {
                const conflictDetails = data.conflicts && data.conflicts.length > 0
                    ? ` Conflict with: ${data.conflicts.map(c => c.subject_name || 'existing class').join(', ')}.`
                    : '';
                this.showAlert(`⚠️ Schedule conflict detected.${conflictDetails} Please choose another time.`, 'danger');
                this.disableSubmitButton();
            } else {
                const message = data.message || 'Unexpected response from server.';
                this.showAlert(message, 'warning');
                this.disableSubmitButton();
            }
        } catch (error) {
            console.error('Error checking availability:', error);
            this.showAlert('Error checking availability: ' + error.message, 'danger');
            this.disableSubmitButton();
        } finally {
            checkBtn.innerHTML = originalText;
            checkBtn.disabled = false;
        }
    }

    normalizeTimeInput(timeValue) {
        if (!timeValue) return '';
        const parts = timeValue.split(':').map(part => part.trim());
        if (parts.length < 2) {
            return timeValue;
        }
        const hours = parts[0].padStart(2, '0');
        const minutes = parts[1].padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    ensureSeconds(timeValue) {
        if (!timeValue) return '';
        return timeValue.length === 5 ? `${timeValue}:00` : timeValue;
    }

    isValidTime(timeValue) {
        return /^\d{2}:\d{2}$/.test(timeValue);
    }

    showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;
        
        alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }

    enableSubmitButton() {
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }

    disableSubmitButton() {
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
        }
    }

    clearTimeSelections() {
        document.getElementById('day_of_week').value = '';
        document.getElementById('start_time').innerHTML = '<option value="">Select Start Time</option>';
        document.getElementById('end_time').innerHTML = '<option value="">Select End Time</option>';
        document.getElementById('start_time_input').value = '';
        document.getElementById('end_time_input').value = '';
        document.getElementById('schedule').value = '';
        this.disableSubmitButton();
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
}

// Helper function for time parsing (simplified version)
function strtotime(timeString) {
    const timestamp = Date.parse(timeString);
    return isNaN(timestamp) ? false : timestamp;
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.timeManagement = new TimeManagement();
});

// Custom input toggle functionality
function toggleCustomInput(type) {
    const select = document.getElementById(type);
    const input = document.getElementById(type + '_input');
    const toggle = event.target;
    
    if (input.style.display === 'none' || input.style.display === '') {
        // Show custom input
        select.style.display = 'none';
        input.style.display = 'block';
        input.focus();
        toggle.classList.add('active');
        toggle.textContent = '✓';
    } else {
        // Show dropdown
        select.style.display = 'block';
        input.style.display = 'none';
        input.value = '';
        toggle.classList.remove('active');
        toggle.textContent = '✎';
    }
}

// Global functions for backward compatibility
function loadTeacherSchedule() {
    if (window.timeManagement) {
        window.timeManagement.loadTeacherSchedule();
    }
}

function updateTimeOptions() {
    if (window.timeManagement) {
        window.timeManagement.updateTimeOptions();
    }
}

function onStartTimeChange() {
    if (window.timeManagement) {
        window.timeManagement.onStartTimeChange();
    }
}

function onEndTimeChange() {
    if (window.timeManagement) {
        window.timeManagement.onEndTimeChange();
    }
}

function checkAvailability() {
    if (window.timeManagement) {
        window.timeManagement.checkAvailability();
    }
}

function testAPI() {
    if (window.timeManagement) {
        window.timeManagement.testAPI();
    }
}
