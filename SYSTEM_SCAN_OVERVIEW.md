# Student Monitoring System - Complete System Scan Overview

**Date:** 2025-01-27  
**System:** Smart Student Monitoring System (SSMS) - Enterprise Edition  
**Location:** `C:\xampp\htdocs\student-monitoring`

---

## 📋 Executive Summary

This is a comprehensive PHP-based Student Monitoring System built with a custom MVC framework. The system supports multiple user roles (Admin, Teacher, Adviser, Student, Parent) with role-based access control, real-time features, PWA capabilities, and enterprise-grade security.

---

## 🏗️ System Architecture

### Core Framework (MVC Pattern)

**Location:** `app/Core/`

1. **Router.php** - Custom routing system
   - Handles GET/POST requests
   - Base-path aware for subfolder deployment
   - Redirects unknown routes to `/error/404`
   - Normalizes paths

2. **Controller.php** - Base controller class
   - Provides View instance to all controllers
   - Abstract base class

3. **Database.php** - PDO database connection manager
   - Singleton pattern
   - Connection pooling
   - Exception handling

4. **Session.php** - Session management
   - Secure cookie configuration
   - Session regeneration support
   - Helper methods (get, set, forget, destroy)

5. **View.php** - Template rendering engine
   - Layout support
   - Data extraction
   - View path resolution

### Bootstrap & Entry Points

- **`public/index.php`** - Front controller (main entry point)
- **`index.php`** (root) - Redirects to `public/index.php`
- **`app/bootstrap.php`** - Application initialization
  - Error reporting configuration
  - Autoloader registration
  - Session start
  - Router initialization
  - Route loading

### Configuration

**`config/config.php`** - Centralized configuration
- App settings (name, env, base_url, timezone)
- Database connection details
- Session configuration

---

## 👥 Controllers & User Roles

### Controllers Location: `app/Controllers/`

#### 1. **AuthController.php**
- **Login/Logout:** Session-based authentication with password verification
- **Registration:** Student self-registration (pending approval workflow)
- **Security:** CSRF protection, session regeneration, account status checks

#### 2. **AdminController.php** (2,203 lines - largest controller)
**Features:**
- **Dashboard:** User statistics, system stats, recent activity
- **User Management:**
  - Create users (all roles)
  - Approve/reject pending registrations
  - Suspend/activate users
  - Delete users
  - Create parent accounts (linked to students)
- **Student Registration:** Full student profile creation with LRN generation
- **Section Management:**
  - Create/update sections
  - Capacity management
  - Assign students to sections
  - Section details API
- **Class Management:**
  - Create classes (section + subject + teacher)
  - Schedule conflict detection
  - Teacher schedule management
  - Unique constraint handling
- **Adviser Assignment:** Assign/remove advisers to sections
- **Reports & Analytics:** User statistics, charts
- **System Logs:** Audit trail access

#### 3. **TeacherController.php** (1,485 lines)
**Features:**
- **Dashboard:** Teacher stats, sections, activities, alerts
- **Grade Management:** Enter/view grades with filtering
- **Class Management:** View assigned classes
- **Section Management:** View sections with student counts
- **Student Management:**
  - View all students
  - Search by LRN
  - Add students to sections
- **Attendance Management:**
  - Take attendance
  - View attendance records
  - API endpoints for attendance
- **Assignments:** Create and manage assignments
- **Student Progress:** Track student performance
- **Communication:** Teacher-student communication
- **Materials:** Teaching resources management

#### 4. **StudentController.php**
**Features:**
- **Dashboard:** Student info, academic stats, classes
- **Grades:** View grades with charts
- **Assignments:** View and submit assignments
- **Profile:** Complete student profile with section info
- **Attendance:** View attendance records
- **Schedule:** Class schedule view
- **Resources:** Learning materials access

#### 5. **AdviserController.php**
**Features:**
- **Dashboard:** Advised sections, student counts, activities
- **Student Management:** View and manage advised students
- **Performance:** Track student performance
- **Communication:** Adviser-student communication

#### 6. **HomeController.php**
- Landing page
- Demo pages (component library, PWA features, real-time features)

#### 7. **ErrorController.php**
- Handles error pages (401, 403, 404, 500, 503)
- Custom error messages support

---

## 🗄️ Database Models

**Location:** `app/Models/`

1. **StudentModel.php**
   - Get student profile by user ID
   - List classmates by section
   - Assign student to section

2. **SectionModel.php**
   - List sections with capacity info
   - Create/update sections
   - Get section capacity

---

## 🛠️ Helper Classes

**Location:** `app/Helpers/`

1. **Url.php** - URL generation
   - Base-path aware
   - Asset URL generation
   - Public path resolution

2. **Csrf.php** - CSRF protection
   - Token generation
   - Token validation
   - Form field/meta tag helpers

3. **ErrorHandler.php** - Error management
   - Redirects to appropriate error pages
   - Exception handling

4. **Validator.php** - Input validation
   - Email, password strength, required fields
   - String sanitization
   - Password error messages

5. **Security.php** (referenced but not read)
   - Secure token generation
   - Constant-time comparison

6. **ActivityLogger.php** (not read)
7. **Cache.php** (not read)
8. **Notification.php** (not read)
9. **AssetManager.php** (not read)
10. **ComponentHelper.php** (not read)
11. **Response.php** (not read)

---

## 🌐 API Endpoints

**Location:** `api/`

### Admin APIs (`api/admin/`)
- `registerStudent.php` - Student registration
- `assignSection.php` - Section assignment
- `fetchSections.php` - Get sections
- `getSectionSlots.php` - Section capacity
- `available-time-slots.php` - Schedule availability
- `check-schedule-conflict.php` - Conflict detection
- `teacher-schedule.php` - Teacher schedule management

### Teacher APIs (`api/teacher/`)
- `create_section.php` - Create section (with dynamic table creation)
- `create_class.php` - Create class
- `list_sections.php` - List sections
- `list_classes.php` - List classes
- `search_student_by_lrn.php` - Student search
- `add_student_to_section.php` - Add student to section
- `teacher-schedule.php` - Schedule management

### Student APIs (`api/student/`)
- `my-classes.php` - Get student classes
- `my-schedule.php` - Get student schedule
- `upload_profile_picture.php` - Profile picture upload

---

## 🎨 Frontend Architecture

### Build System
- **Webpack** for JavaScript bundling
- **Sass** for CSS compilation
- **Babel** for JavaScript transpilation
- **Entry Points:**
  - `app.js` - Core application
  - `dashboard.js` - Dashboard-specific
  - `components.js` - Component library

### Assets Location: `public/assets/`
- 50+ files (24 JS, 20 CSS, 4 images, etc.)
- Organized by feature (sidebar, forms, charts, etc.)

### Views Location: `resources/views/`
- **Layouts:** `layouts/` (app, auth, dashboard, etc.)
- **Role-based views:**
  - `admin/` - 11 views
  - `teacher/` - 13 views
  - `student/` - 5 views
  - `adviser/` - 4 views
  - `parent/` - 1 view
- **Error pages:** `errors/` (401, 403, 404, 500, 503)
- **Demo pages:** `demo/` (component library, PWA, real-time)

---

## 🔐 Security Features

1. **CSRF Protection:** All forms protected
2. **Password Hashing:** `password_hash()` with PASSWORD_DEFAULT
3. **Session Security:** Regeneration, secure cookies
4. **Input Validation:** Multi-layer validation
5. **Role-Based Access Control:** Controllers check user roles
6. **SQL Injection Prevention:** PDO prepared statements
7. **XSS Prevention:** HTML escaping in views

---

## 📊 Database Schema (Inferred)

### Core Tables:
- **users** - Centralized user table (all roles)
  - id, role, email, password_hash, name, status, approved_by, approved_at
- **students** - Student profiles
  - user_id (FK), lrn, first_name, last_name, grade_level, section_id, etc.
- **teachers** - Teacher profiles
  - user_id (FK), is_adviser
- **sections** - Class sections
  - id, name, grade_level, room, max_students, adviser_id, school_year
- **classes** - Class assignments
  - section_id, subject_id, teacher_id, schedule, room, school_year, semester
- **subjects** - Subject catalog
- **student_classes** - Enrollment records
- **grades** - Grade records
- **attendance** - Attendance records
- **audit_logs** - Activity logging
- **user_requests** - Registration requests
- **teacher_schedules** - Teacher schedule slots

### Views:
- **student_profiles** - Consolidated student view

---

## 🔄 Key Workflows

### 1. Student Registration Flow
1. Student registers at `/register`
2. Account created with `status='pending'`
3. Entry created in `user_requests`
4. Admin approves/rejects at `/admin/users`
5. If approved: student record created, status='active'

### 2. Class Creation Flow
1. Admin selects section, subject, teacher
2. Schedule parsed and validated
3. Conflict detection (teacher schedule)
4. Unique constraint check (section + subject + semester + school_year)
5. Class created + teacher_schedules entries created

### 3. Section Assignment Flow
1. Admin views sections with capacity
2. Selects unassigned students
3. Capacity check before assignment
4. Student assigned to section
5. Auto-enrollment in section classes

---

## 📁 Project Structure

```
student-monitoring/
├── api/                    # API endpoints (legacy/standalone)
├── app/
│   ├── Controllers/        # MVC Controllers
│   ├── Core/               # Framework core
│   ├── Helpers/            # Utility classes
│   └── Models/             # Data models
├── config/                 # Configuration files
├── database/               # SQL scripts, migrations
├── docs/                   # Documentation
├── public/                 # Web root
│   ├── assets/             # CSS, JS, images
│   └── index.php           # Front controller
├── resources/
│   └── views/              # PHP templates
├── routes/                 # Route definitions
├── src/                    # Source files (SCSS, JS)
│   ├── js/                 # JavaScript source
│   └── scss/               # Sass source
├── tests/                  # PHPUnit tests
└── vendor/                 # Composer dependencies
```

---

## 🚀 Features Summary

### ✅ Implemented Features
- Multi-role user management (Admin, Teacher, Adviser, Student, Parent)
- Student registration with approval workflow
- Section and class management
- Grade management
- Attendance tracking
- Schedule management with conflict detection
- Adviser assignment
- Audit logging
- PWA support
- Dark/Light mode
- Responsive design
- Real-time features (mentioned in docs)
- Component library
- Error handling system

### 🔧 Technical Stack
- **Backend:** PHP 8.0+ (Custom MVC framework)
- **Database:** MySQL (PDO)
- **Frontend:** Bootstrap 5, JavaScript (ES6+), Sass
- **Build Tools:** Webpack, Babel, Sass compiler
- **Package Manager:** Composer (PHP), npm (Node.js)
- **Testing:** PHPUnit

---

## 📝 Notes & Observations

1. **Dual API System:** Both MVC controllers and standalone API files exist
2. **Dynamic Table Creation:** `create_section.php` creates per-section tables (unusual pattern)
3. **Large Controllers:** AdminController (2,203 lines) and TeacherController (1,485 lines) could benefit from refactoring
4. **Error Handling:** Centralized via ErrorHandler helper
5. **Base-Path Awareness:** System designed for subfolder deployment
6. **Session Management:** Secure session handling with regeneration
7. **Capacity Management:** Sections have max_students with validation
8. **Schedule Parsing:** Custom schedule format parser (e.g., "M 8:00 AM-9:00 AM")

---

## 🔍 Areas for Further Investigation

1. **Helper Classes:** Read remaining helpers (Security, Cache, Notification, etc.)
2. **View Files:** Examine template structure
3. **JavaScript Files:** Review frontend logic
4. **Database Migrations:** Check migration scripts
5. **Documentation:** Review all docs in `docs/` folder
6. **Tests:** Check test coverage

---

## 📚 Documentation Files

Located in `docs/`:
- README.md
- SETUP_GUIDE.md
- USER_MANAGEMENT.md
- ERROR_PAGES_USAGE.md
- FRONTEND_UI.md
- SECURITY_AUDIT.md
- PERFORMANCE.md
- FEATURE_ENHANCEMENTS.md
- And more...

---

**Scan Completed:** Comprehensive overview of system architecture, controllers, models, helpers, API structure, and key workflows.

