# Admin Interface File Reference Guide

This document provides a comprehensive overview of all files in the admin interface/panel, explaining the function of each file so you know which files to modify for specific features.

---

## 📁 Directory Structure

```
admin/
├── app/Controllers/AdminController.php    [Main Controller]
├── resources/views/admin/                  [View Templates]
├── api/admin/                              [API Endpoints]
└── public/assets/                          [JavaScript & CSS]
```

---

## 🎮 Main Controller

### `app/Controllers/AdminController.php`
**Purpose:** Central controller handling all admin operations and routing.

**Key Methods:**
- `dashboard()` - Renders admin dashboard with statistics
- `users()` - Lists all users (teachers, students, parents, admins)
- `createUser()` - Creates new user accounts (teacher/student/parent/admin)
- `approveUser()` - Approves pending user registrations
- `rejectUser()` - Rejects pending user registrations
- `suspendUser()` / `activateUser()` - User status management
- `deleteUser()` - Deletes user accounts
- `createParent()` - Creates parent accounts linked to students
- `classes()` - Lists all classes with schedules
- `createClass()` - Creates new class assignments (teacher + subject + section + schedule)
- `students()` - Lists all students
- `createStudent()` - Registers new student with full details
- `viewStudent()` / `editStudent()` / `updateStudent()` - Student management
- `teachers()` - Lists all teachers
- `viewTeacher()` - Shows teacher details and schedule
- `sections()` - Lists all sections (class groups)
- `createSection()` / `updateSection()` - Section management
- `assignStudentToSection()` - Assigns students to sections
- `assignAdvisers()` / `assignAdviser()` / `removeAdviser()` - Section adviser management
- `subjects()` / `createSubject()` - Subject management
- `reports()` - System reports and analytics
- `logs()` - Activity and audit logs
- `settings()` - System settings

**When to Modify:**
- Adding new admin features
- Changing business logic for user/class/student management
- Adding new database operations
- Modifying approval workflows

---

## 📄 View Templates (`resources/views/admin/`)

### `dashboard.php`
**Purpose:** Main admin dashboard displaying system statistics, user counts, and recent activity.

**Displays:**
- User statistics by role (students, teachers, advisers, parents, admins)
- System stats (sections, classes, subjects, unassigned students)
- Pending user approvals count
- Recent activity feed

**When to Modify:**
- Adding new dashboard widgets
- Changing statistics display
- Adding new metrics or charts

---

### `users.php`
**Purpose:** User management interface listing all users with filtering, search, and bulk actions.

**Features:**
- Lists all users (teachers, students, parents, admins)
- Filter by role and status
- Search functionality
- Approve/reject/suspend/activate/delete actions
- User detail modals

**When to Modify:**
- Changing user list display
- Adding new user filters
- Modifying user action buttons
- Adding bulk operations

---

### `create-user.php`
**Purpose:** Form for creating new user accounts (teacher, student, parent, admin, adviser).

**Features:**
- Role selection (teacher/adviser/student/parent/admin)
- Form validation
- Dynamic fields based on role
- Teacher-specific fields (department, employee_id, specialization, hire_date)
- Student-specific fields (LRN, grade_level, section_name)
- Parent-specific fields (linked_student_user_id, parent_relationship)

**When to Modify:**
- Adding new user fields
- Changing form validation
- Adding role-specific fields
- Modifying user creation workflow

---

### `create-parent.php`
**Purpose:** Specialized form for creating parent accounts linked to specific students.

**Features:**
- Student selection dropdown
- Parent relationship selection
- Option to sync parent info to student's guardian fields
- Creates parent user and links to student

**When to Modify:**
- Changing parent-student linking logic
- Adding parent relationship types
- Modifying guardian info sync behavior

---

### `classes.php`
**Purpose:** Class management interface for creating and managing class assignments.

**Features:**
- Lists all classes with details (section, subject, teacher, schedule, room)
- "Add New Class" modal with:
  - Section selection
  - Subject selection
  - Teacher selection (dynamically refreshed from database)
  - Room assignment
  - Centralized time management (day, start time, end time)
  - Schedule conflict detection
  - Teacher's current schedule display
- Schedule conflict warnings
- Duplicate class detection

**When to Modify:**
- Adding new class fields
- Modifying schedule conflict detection UI
- Changing teacher dropdown behavior
- Adding class editing/deletion features

---

### `students.php`
**Purpose:** Student management interface listing all students.

**Features:**
- Student list with details (name, LRN, grade, section, status)
- Search and filter functionality
- View/edit student actions
- Enrollment status indicators

**When to Modify:**
- Adding student list columns
- Changing student filters
- Adding bulk student operations

---

### `create-student.php`
**Purpose:** Form for registering new students with complete details.

**Features:**
- Student personal information
- LRN (Learner Reference Number)
- Grade level and section assignment
- Guardian information
- Enrollment status
- Optional parent account creation

**When to Modify:**
- Adding new student fields
- Changing enrollment workflow
- Modifying parent account creation integration

---

### `view-student.php`
**Purpose:** Detailed view of a single student's information.

**Features:**
- Student profile details
- Academic information
- Section assignment
- Guardian information
- Enrollment history

**When to Modify:**
- Adding new student detail sections
- Changing profile display layout

---

### `edit-student.php`
**Purpose:** Form for editing existing student information.

**Features:**
- Pre-filled form with current student data
- Update student details
- Change section assignment
- Update guardian information

**When to Modify:**
- Adding editable fields
- Changing validation rules

---

### `teachers.php`
**Purpose:** Teacher management interface listing all teachers.

**Features:**
- Teacher list with details (name, department, specialization, status)
- Statistics cards (total, active, advisers)
- Search and filter functionality
- View teacher details action

**When to Modify:**
- Adding teacher list columns
- Changing teacher filters
- Adding bulk teacher operations

---

### `view-teacher.php`
**Purpose:** Detailed view of a single teacher's information and schedule.

**Features:**
- Teacher profile details
- Department and specialization
- Class assignments
- Schedule display
- Section adviser assignments

**When to Modify:**
- Adding teacher detail sections
- Changing schedule display format

---

### `sections.php`
**Purpose:** Section (class group) management interface.

**Features:**
- Lists all sections with details (name, grade level, room, capacity, adviser)
- Section statistics
- Create/update section modals
- Assign students to sections
- Assign section advisers
- Capacity monitoring

**When to Modify:**
- Adding section fields
- Changing capacity management
- Modifying student assignment workflow

---

### `subjects.php`
**Purpose:** Subject management interface.

**Features:**
- Lists all subjects
- Subject details (name, code, grade level, description)
- Create new subjects
- Subject statistics

**When to Modify:**
- Adding subject fields
- Changing subject categorization

---

### `assign-advisers.php`
**Purpose:** Interface for assigning teachers as section advisers.

**Features:**
- Lists all sections
- Current adviser assignments
- Assign/remove adviser actions
- Bulk assignment options

**When to Modify:**
- Changing adviser assignment workflow
- Adding bulk operations

---

### `reports.php`
**Purpose:** System reports and analytics dashboard.

**Features:**
- Various system reports
- Analytics and statistics
- Export functionality

**When to Modify:**
- Adding new report types
- Changing report display
- Adding export formats

---

### `logs.php`
**Purpose:** Activity and audit log viewer.

**Features:**
- System activity logs
- User action tracking
- Audit trail
- Log filtering and search

**When to Modify:**
- Adding log filters
- Changing log display format
- Adding log export

---

### `settings.php`
**Purpose:** System settings and configuration.

**Features:**
- System configuration options
- Settings management

**When to Modify:**
- Adding new settings
- Changing settings UI

---

## 🔌 API Endpoints (`api/admin/`)

### `list-teachers.php`
**Purpose:** Returns fresh list of all active teachers/advisers for dropdowns.

**Method:** GET  
**Returns:** JSON with teacher list (id, name, email, department, is_adviser)

**When to Modify:**
- Changing teacher list criteria
- Adding teacher fields to response
- Modifying filtering logic

---

### `teacher-schedule.php`
**Purpose:** Fetches teacher's current schedule for conflict detection.

**Method:** GET  
**Parameters:** `teacher_id`  
**Returns:** JSON with teacher's schedules (day, start_time, end_time, subject, section)

**When to Modify:**
- Changing schedule format
- Adding schedule details
- Modifying conflict detection logic

---

### `check-schedule-fixed.php`
**Purpose:** Real-time schedule conflict validation when creating classes.

**Method:** POST  
**Parameters:** `teacherId`, `day`, `startTime`, `endTime` (JSON)  
**Returns:** JSON with conflict status and details

**When to Modify:**
- Changing conflict detection algorithm
- Adding conflict details
- Modifying validation rules

---

### `check-schedule-conflict.php`
**Purpose:** Alternative schedule conflict check endpoint.

**Method:** POST  
**Parameters:** `teacher_id`, `days`, `start_time`, `end_time`  
**Returns:** JSON with conflict information

**When to Modify:**
- Alternative conflict checking logic
- Different conflict detection method

---

### `check-schedule.php`
**Purpose:** Basic schedule checking endpoint.

**Method:** GET/POST  
**Returns:** Schedule availability information

**When to Modify:**
- Basic schedule validation
- Simple conflict checks

---

### `available-time-slots.php`
**Purpose:** Returns available time slots for a teacher on a specific day.

**Method:** GET  
**Parameters:** `teacher_id`, `day`  
**Returns:** JSON with available time slots

**When to Modify:**
- Changing time slot calculation
- Adding slot availability rules

---

### `registerStudent.php`
**Purpose:** API endpoint for registering new students.

**Method:** POST  
**Parameters:** Student data (JSON)  
**Returns:** JSON with registration result

**When to Modify:**
- Changing student registration logic
- Adding validation rules
- Modifying response format

---

### `assignSection.php`
**Purpose:** Assigns student to a section.

**Method:** POST  
**Parameters:** `student_id`, `section_id`  
**Returns:** JSON with assignment result

**When to Modify:**
- Changing assignment logic
- Adding validation
- Modifying section capacity checks

---

### `fetchSections.php`
**Purpose:** Returns list of sections for dropdowns.

**Method:** GET  
**Returns:** JSON with section list

**When to Modify:**
- Changing section list criteria
- Adding section fields

---

### `getSectionSlots.php`
**Purpose:** Returns available slots/capacity for a section.

**Method:** GET  
**Parameters:** `section_id`  
**Returns:** JSON with slot information

**When to Modify:**
- Changing capacity calculation
- Adding slot rules

---

### `test-schedule.php`
**Purpose:** Test endpoint for schedule API functionality.

**Method:** GET  
**Returns:** Test response

**When to Modify:**
- API testing
- Debugging schedule endpoints

---

## 💻 JavaScript Files (`public/assets/`)

### `admin-time-management.js`
**Purpose:** Handles time selection, schedule conflict detection, and teacher schedule display in class creation.

**Key Features:**
- Time slot generation (7 AM - 6 PM, 30-minute intervals)
- Teacher schedule loading and display
- Schedule conflict checking
- Time input validation
- Dynamic time dropdown population
- Occupied time slot disabling

**When to Modify:**
- Changing time slot ranges
- Modifying conflict detection UI
- Adding new time input methods
- Changing schedule display format

---

### `admin-class-management.js`
**Purpose:** Class management JavaScript functionality.

**Features:**
- Class list operations
- Class creation/editing
- Class deletion

**When to Modify:**
- Adding class management features
- Changing class list behavior

---

### `admin-dashboard.js`
**Purpose:** Admin dashboard JavaScript functionality.

**Features:**
- Dashboard statistics updates
- Real-time data refresh
- Chart rendering

**When to Modify:**
- Adding dashboard widgets
- Changing chart types
- Modifying data refresh logic

---

### `admin-sections.js`
**Purpose:** Section management JavaScript functionality.

**Features:**
- Section list operations
- Section creation/editing
- Student assignment to sections

**When to Modify:**
- Adding section management features
- Changing assignment workflow

---

### `admin-settings.js`
**Purpose:** Settings page JavaScript functionality.

**Features:**
- Settings form handling
- Settings updates

**When to Modify:**
- Adding settings features
- Changing settings UI

---

### `admin-sidebar.js`
**Purpose:** Admin sidebar navigation functionality.

**Features:**
- Sidebar menu handling
- Navigation highlighting
- Menu state management

**When to Modify:**
- Adding navigation items
- Changing sidebar behavior

---

### `admin-reports.js`
**Purpose:** Reports page JavaScript functionality.

**Features:**
- Report generation
- Data visualization
- Export functionality

**When to Modify:**
- Adding report types
- Changing visualization
- Adding export formats

---

### `admin-logs.js`
**Purpose:** Logs page JavaScript functionality.

**Features:**
- Log filtering
- Log search
- Log display

**When to Modify:**
- Adding log filters
- Changing log display

---

### `js/adminCreateUser.js` / `public/assets/js/adminCreateUser.js`
**Purpose:** JavaScript for user creation form.

**Features:**
- Form validation
- Dynamic field display based on role
- AJAX user creation
- Form submission handling

**When to Modify:**
- Adding form validation rules
- Changing dynamic field behavior
- Modifying AJAX submission

---

## 🎨 CSS Files

### `admin-time-management.css`
**Purpose:** Styles for time management interface in class creation.

**When to Modify:**
- Changing time picker appearance
- Modifying schedule display styles
- Adding new UI components

---

## 📝 Quick Reference: Which File to Modify?

| **Feature to Modify** | **File(s) to Edit** |
|----------------------|---------------------|
| **Teacher Account Creation** | `app/Controllers/AdminController.php` (createUser method)<br>`resources/views/admin/create-user.php`<br>`api/create_user.php` |
| **Teacher Dropdown in Create Class** | `resources/views/admin/classes.php` (refreshTeacherDropdown function)<br>`api/admin/list-teachers.php` |
| **Schedule Conflict Detection** | `api/admin/check-schedule-fixed.php`<br>`public/assets/admin-time-management.js` (checkAvailability method) |
| **Teacher Schedule Display** | `api/admin/teacher-schedule.php`<br>`public/assets/admin-time-management.js` (loadTeacherSchedule method)<br>`resources/views/admin/classes.php` |
| **Class Creation** | `app/Controllers/AdminController.php` (createClass method)<br>`resources/views/admin/classes.php` |
| **Teacher ID Management** | `app/Controllers/AdminController.php` (createUser, approveUser methods)<br>`api/create_user.php` (upsertTeacherProfile function)<br>`database/repair_teachers_table.sql` |
| **User Management** | `app/Controllers/AdminController.php` (users, approveUser, suspendUser, etc.)<br>`resources/views/admin/users.php` |
| **Student Management** | `app/Controllers/AdminController.php` (students, createStudent, editStudent)<br>`resources/views/admin/students.php`<br>`resources/views/admin/create-student.php` |
| **Section Management** | `app/Controllers/AdminController.php` (sections, createSection)<br>`resources/views/admin/sections.php` |
| **Dashboard** | `app/Controllers/AdminController.php` (dashboard method)<br>`resources/views/admin/dashboard.php`<br>`public/assets/admin-dashboard.js` |

---

## 🔧 Common Modification Patterns

### Adding a New Field to Teacher Creation
1. **Backend:** `app/Controllers/AdminController.php` → `createUser()` method
2. **View:** `resources/views/admin/create-user.php` → Add form field
3. **API:** `api/create_user.php` → Add field to upsertTeacherProfile function
4. **Database:** Update `teachers` table schema

### Adding a New Admin Feature
1. **Controller:** `app/Controllers/AdminController.php` → Add new method
2. **View:** `resources/views/admin/[feature-name].php` → Create view template
3. **Route:** `routes/web.php` → Add route
4. **JavaScript:** `public/assets/admin-[feature].js` → Add frontend logic (if needed)

### Modifying Schedule Conflict Detection
1. **API:** `api/admin/check-schedule-fixed.php` → Modify conflict logic
2. **JavaScript:** `public/assets/admin-time-management.js` → Update UI handling
3. **View:** `resources/views/admin/classes.php` → Update conflict display

---

## 📌 Important Notes

- **Always check `AdminController.php` first** - it's the central hub for all admin operations
- **API endpoints** handle AJAX requests and return JSON
- **View templates** handle the UI and form rendering
- **JavaScript files** handle client-side interactivity
- **Database operations** are primarily in the controller, but some are in API endpoints

---

**Last Updated:** 2025-12-01  
**Maintained By:** Development Team

