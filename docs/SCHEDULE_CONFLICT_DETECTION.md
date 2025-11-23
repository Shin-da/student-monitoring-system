# Schedule Monitoring and Conflict Detection Feature

## Overview

This feature implements comprehensive schedule monitoring and conflict detection for the student monitoring system's Admin Panel. It prevents teachers from being assigned to overlapping classes and provides real-time conflict detection when creating new class assignments.

## 🎯 Features Implemented

### 1. Database Schema
- **New Table**: `teacher_schedules` - Stores detailed teacher schedule information
- **Enhanced Classes**: Existing `classes` table remains unchanged
- **Conflict Detection**: Real-time checking for schedule overlaps

### 2. Backend Logic
- **Conflict Detection API**: `/api/admin/check-schedule-conflict.php`
- **Teacher Schedule API**: `/api/admin/teacher-schedule.php`
- **Class Management**: Enhanced AdminController with conflict checking
- **Schedule Parsing**: Intelligent parsing of schedule formats (MWF, TTH, etc.)

### 3. Frontend Interface
- **Real-time Validation**: Instant conflict detection as admin types
- **Visual Feedback**: Color-coded conflict warnings and success messages
- **Teacher Schedule Display**: Shows current teacher schedules
- **Responsive Design**: Mobile-friendly interface

## 🗄️ Database Structure

### teacher_schedules Table
```sql
CREATE TABLE teacher_schedules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  class_id INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  UNIQUE KEY unique_schedule (teacher_id, day_of_week, start_time, end_time)
);
```

## 🚀 API Endpoints

### 1. Get Teacher Schedule
**Endpoint**: `GET /api/admin/teacher-schedule.php?teacher_id={id}`

**Response**:
```json
{
  "success": true,
  "teacher_id": 1,
  "schedules": [
    {
      "id": 1,
      "day": "Monday",
      "start": "08:00:00",
      "end": "09:00:00",
      "class_id": 1,
      "section_name": "Grade 7 - Section A",
      "subject_name": "Mathematics"
    }
  ]
}
```

### 2. Check Schedule Conflict
**Endpoint**: `POST /api/admin/check-schedule-conflict.php`

**Request Body**:
```json
{
  "teacher_id": 1,
  "days": ["Monday", "Wednesday", "Friday"],
  "start_time": "08:00:00",
  "end_time": "09:00:00"
}
```

**Response**:
```json
{
  "success": true,
  "has_conflict": false,
  "conflicts": [],
  "conflict_count": 0,
  "requested_schedule": {
    "teacher_id": 1,
    "days": ["Monday", "Wednesday", "Friday"],
    "start_time": "08:00:00",
    "end_time": "09:00:00"
  }
}
```

## 🎨 User Interface

### Admin Panel Navigation
- **New Menu Item**: "Class Management" in admin sidebar
- **Route**: `/admin/classes`
- **Access**: Admin only

### Class Management Interface
1. **Classes Table**: Displays all classes with teacher assignments
2. **Add Class Button**: Opens modal for creating new classes
3. **Schedule Viewer**: Click to view teacher's current schedule
4. **Conflict Detection**: Real-time validation during class creation

### Create Class Modal
1. **Section Selection**: Choose from available sections
2. **Subject Selection**: Choose from available subjects
3. **Teacher Selection**: Choose teacher (triggers schedule loading)
4. **Schedule Input**: Format like "MWF 8:00-9:00" or "TTH 10:00-11:00"
5. **Room Assignment**: Specify classroom
6. **Real-time Validation**: Instant conflict detection

## 🔧 Technical Implementation

### Backend Components

#### AdminController.php
- `classes()` - Display class management page
- `createClass()` - Handle class creation with conflict checking
- `parseSchedule()` - Parse schedule format
- `checkScheduleConflicts()` - Check for conflicts
- `createTeacherSchedules()` - Create schedule entries

#### API Endpoints
- **teacher-schedule.php**: Fetch teacher schedules
- **check-schedule-conflict.php**: Check for conflicts

### Frontend Components

#### JavaScript (admin-class-management.js)
- `ClassManagement` class for handling all interactions
- Real-time conflict detection with debouncing
- Schedule parsing and validation
- Modal management for schedule viewing

#### CSS (admin-class-management.css)
- Enhanced styling for conflict detection
- Responsive design
- Dark mode support
- Accessibility features

## 📋 Usage Examples

### 1. Creating a New Class
1. Navigate to Admin Panel → Class Management
2. Click "Add New Class"
3. Select Section: "Grade 7 - Section A"
4. Select Subject: "Mathematics"
5. Select Teacher: "Shin Da" (triggers schedule loading)
6. Enter Schedule: "MWF 8:00-9:00"
7. Enter Room: "Room 101"
8. System checks for conflicts automatically
9. If no conflicts, click "Create Class"

### 2. Schedule Conflict Detection
- **Conflict Example**: Teacher already has "MWF 8:00-9:00" assigned
- **Warning Display**: Red alert with conflict details
- **Prevention**: Submit button disabled until conflict resolved

### 3. Viewing Teacher Schedule
- Click schedule icon next to any class
- Modal displays teacher's complete weekly schedule
- Color-coded time slots for easy identification

## 🛡️ Security Features

### Access Control
- Admin-only access to all endpoints
- CSRF protection on form submissions
- Session validation for all requests

### Data Validation
- Server-side schedule format validation
- SQL injection prevention with prepared statements
- Input sanitization and validation

## 🎯 Schedule Format Support

### Supported Formats
- **MWF 8:00-9:00**: Monday, Wednesday, Friday
- **TTH 10:00-11:00**: Tuesday, Thursday
- **M 14:00-15:00**: Monday only
- **T 09:00-10:00**: Tuesday only
- **W 11:00-12:00**: Wednesday only
- **F 13:00-14:00**: Friday only
- **S 08:00-09:00**: Saturday only

### Day Code Mapping
- **M**: Monday
- **T**: Tuesday
- **W**: Wednesday
- **TH**: Thursday (special case)
- **F**: Friday
- **S**: Saturday

## 🔄 Workflow Example

### Successful Class Creation
1. Admin selects teacher "Shin Da"
2. System loads teacher's current schedule
3. Admin enters "TTH 10:00-11:00"
4. System checks for conflicts
5. No conflicts found - green success message
6. Admin clicks "Create Class"
7. Class created and schedule entries added
8. Success message displayed

### Conflict Detection
1. Admin selects teacher "Shin Da"
2. System loads teacher's current schedule
3. Admin enters "MWF 8:00-9:00"
4. System detects existing "MWF 8:00-9:00" class
5. Red warning displayed with conflict details
6. Submit button disabled
7. Admin must choose different time

## 🚀 Future Enhancements

### Planned Features
1. **Bulk Class Creation**: Create multiple classes at once
2. **Schedule Templates**: Pre-defined schedule patterns
3. **Room Conflict Detection**: Check room availability
4. **Schedule Optimization**: Suggest optimal time slots
5. **Calendar Integration**: Export to calendar applications
6. **Mobile App**: Native mobile interface
7. **Notifications**: Email alerts for schedule changes

### Advanced Features
1. **Recurring Schedules**: Handle semester-long patterns
2. **Substitute Teachers**: Temporary teacher assignments
3. **Schedule Analytics**: Usage statistics and reports
4. **Integration**: Connect with external scheduling systems

## 🐛 Troubleshooting

### Common Issues

#### 1. Schedule Format Errors
**Problem**: "Invalid schedule format" error
**Solution**: Use correct format like "MWF 8:00-9:00"

#### 2. Conflict Detection Not Working
**Problem**: Conflicts not detected
**Solution**: Check database connection and teacher_schedules table

#### 3. JavaScript Errors
**Problem**: Frontend not responding
**Solution**: Check browser console and ensure all files are loaded

### Debug Mode
Enable debug mode by adding `?debug=1` to any admin URL to see detailed error messages.

## 📊 Performance Considerations

### Database Optimization
- Indexed columns for fast conflict detection
- Optimized queries for large datasets
- Connection pooling for better performance

### Frontend Optimization
- Debounced conflict checking (500ms delay)
- Lazy loading of teacher schedules
- Cached API responses

### Scalability
- Supports unlimited teachers and classes
- Efficient conflict detection algorithm
- Responsive design for all screen sizes

## 🔧 Maintenance

### Regular Tasks
1. **Database Cleanup**: Remove old schedule entries
2. **Performance Monitoring**: Check query execution times
3. **Backup**: Regular database backups
4. **Updates**: Keep dependencies updated

### Monitoring
- Check error logs for API failures
- Monitor database performance
- Track user adoption and usage

## 📝 Conclusion

The Schedule Monitoring and Conflict Detection feature provides a comprehensive solution for managing teacher schedules and preventing conflicts. It combines robust backend logic with an intuitive frontend interface to ensure smooth class management operations.

The system is designed to be:
- **User-friendly**: Intuitive interface with real-time feedback
- **Reliable**: Comprehensive conflict detection and validation
- **Scalable**: Supports growth and additional features
- **Maintainable**: Clean code structure and documentation

This implementation successfully addresses the core requirements while providing a foundation for future enhancements.
