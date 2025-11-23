# Student Search and View Feature

## Overview
Added comprehensive student search and detailed profile view functionality for Admin and Teachers.

---

## 🎯 Features

### 1. **Admin Students Page** (`/admin/students`)

#### Key Features:
- ✅ **Search Functionality** - Search by:
  - Student Name (First, Middle, Last)
  - LRN (Learner Reference Number)
  - Email Address
  
- ✅ **Advanced Filters:**
  - Grade Level (7-12)
  - Section
  - Enrollment Status (Enrolled, Transferred, Dropped, Graduated)

- ✅ **Quick Statistics:**
  - Total Students
  - Currently Enrolled
  - Unassigned (no section)
  - Transferred/Dropped

- ✅ **Student List View:**
  - Student name with avatar
  - LRN badge
  - Grade level & section
  - Email address
  - Average grade with color coding
  - Enrollment status badge
  - Quick action buttons (View Profile, Edit)

#### Access:
- **Role**: Admin only
- **Navigation**: Admin Panel → User Management → Students
- **URL**: `/admin/students`

---

### 2. **Student Profile View** (`/admin/view-student`)

#### Accessible By:
- ✅ **Admin** - Can view all students
- ✅ **Teacher** - Can view their students
- ✅ **Adviser** - Can view their advised students

#### Profile Sections:

##### **Personal Information Card**
- Full name with avatar
- LRN (Learner Reference Number)
- Student ID
- Grade Level
- Section with room number
- School Year
- Enrollment Status
- Birth Date
- Gender
- Contact Number
- Address

##### **Academic Performance Card**
- Overall GPA/Average (large display)
- Number of subjects
- Attendance rate percentage
- Color-coded performance indicator

##### **Attendance Statistics**
- Visual progress bar showing:
  - Present (Green)
  - Late (Yellow)
  - Absent (Red)
  - Excused (Blue)
- Detailed count for each status
- Total attendance records

##### **Guardian & Emergency Contact**
- Guardian name and contact
- Guardian relationship
- Emergency contact person
- Emergency contact number
- Emergency contact relationship

##### **Enrolled Classes Table**
Shows for each class:
- Subject name and code
- Section name
- Teacher name and email
- Schedule
- Room
- Enrollment status

##### **Grades Summary Table**
Shows for each subject:
- Subject name and code
- Average grade (color-coded)
- Total number of grades
- Remarks (Outstanding, Very Satisfactory, etc.)
- Last graded date

##### **Adviser Information**
- Adviser name
- Adviser email
- Displayed if student has an assigned adviser

#### Actions:
- **Print Profile** - Print-friendly format
- **Edit Profile** - Link to edit student information
- **Back to List** - Return to students list

---

## 📍 Routes Added

```php
// Admin Routes
GET  /admin/students          → AdminController::students()
GET  /admin/view-student      → AdminController::viewStudent()

// Teacher Route (reuses admin method)
GET  /teacher/view-student    → AdminController::viewStudent()
```

---

## 🔍 Search Examples

### By Name:
```
Search: "Juan"
Results: Juan Dela Cruz, Juan Santos, etc.
```

### By LRN:
```
Search: "202500000001"
Results: Student with LRN 202500000001
```

### By Email:
```
Search: "student@example.com"
Results: Student with that email
```

### Combined Filters:
```
Search: "Garcia"
Grade: 10
Section: Section A
Status: Enrolled
Results: All enrolled Grade 10 students in Section A with "Garcia" in their name
```

---

## 🎨 UI Components

### Statistics Cards
- Total Students (Blue)
- Enrolled (Green)
- Unassigned (Yellow)
- Transferred/Dropped (Gray)

### Student List Features
- Avatar with initials
- Color-coded average grades (Green: Pass, Red: Fail)
- Status badges with icons
- Hover effects on rows
- Responsive table design

### Profile Page Features
- Print-friendly layout
- Large avatar with gradient
- Color-coded performance indicators
- Interactive progress bars
- Clean, organized information cards

---

## 📊 Grade Remarks System

Based on DepEd grading scale:

| Grade Range | Remark | Badge Color |
|-------------|--------|-------------|
| 90-100 | Outstanding | Green |
| 85-89 | Very Satisfactory | Info/Blue |
| 80-84 | Satisfactory | Primary/Blue |
| 75-79 | Fairly Satisfactory | Yellow |
| Below 75 | Needs Improvement | Red |

---

## 🔐 Access Control

### Admin:
- ✅ View all students
- ✅ Search all students
- ✅ Edit student profiles
- ✅ Access via `/admin/students`

### Teacher:
- ✅ View their own students (from classes they teach)
- ✅ View student profiles
- ✅ Access via `/teacher/students` (list) and `/teacher/view-student` (profile)
- ❌ Cannot edit student profiles

### Adviser:
- ✅ View students in their advised section
- ✅ View student profiles
- ✅ Access via `/adviser/students`

---

## 💡 Usage Examples

### Admin Searching for a Student:
1. Navigate to **Admin Panel** → **Students**
2. Use search bar to enter name, LRN, or email
3. Click student row or "View Profile" button
4. View complete student profile with grades and attendance

### Teacher Viewing Student Performance:
1. Navigate to **Teacher Panel** → **Students**
2. Find student in the list
3. Click on student name or view icon
4. Review student's grades, attendance, and class enrollment

### Filtering Students by Section:
1. Go to **Students** page
2. Select **Grade Level** (e.g., Grade 10)
3. Select **Section** (e.g., Section A)
4. Click **Search** or filter button
5. View filtered results

---

## 🚀 Benefits

### For Administrators:
✅ Quick student lookup by multiple criteria  
✅ Comprehensive student overview  
✅ Easy performance monitoring  
✅ Export-ready (print function)  
✅ Attendance tracking at a glance  

### For Teachers:
✅ View student academic performance  
✅ Check student enrollment in classes  
✅ Access contact information  
✅ Monitor attendance patterns  

### For the System:
✅ Centralized student information  
✅ Consistent data presentation  
✅ Efficient database queries  
✅ Scalable search functionality  

---

## 📁 Files Created/Modified

### New Files:
1. `resources/views/admin/students.php` - Students list page
2. `resources/views/admin/view-student.php` - Student profile view page
3. `docs/STUDENT_SEARCH_AND_VIEW.md` - This documentation

### Modified Files:
1. `app/Controllers/AdminController.php` - Added `students()` and `viewStudent()` methods
2. `routes/web.php` - Added new routes
3. `resources/views/layouts/dashboard.php` - Added Students nav link
4. `resources/views/layouts/dashboard-optimized.php` - Added Students nav link

---

## 🔧 Technical Details

### Database Queries:
- **Students List**: Joins users, students, sections, grades tables
- **Student Profile**: Multiple optimized queries for different data sections
- **Search**: LIKE queries on multiple fields with OR conditions
- **Grades Summary**: Aggregated AVG and COUNT queries
- **Attendance Stats**: COUNT with CASE WHEN for status breakdown

### Performance:
- Limit of 100 results per search
- Indexed columns (lrn, section_id, grade_level)
- Optimized JOIN queries
- Efficient use of aggregate functions

---

## 📝 Future Enhancements

Potential improvements:
- [ ] Export to CSV/Excel
- [ ] Bulk student operations
- [ ] Student profile photo upload
- [ ] Grade trend charts
- [ ] Attendance calendar view
- [ ] Parent access to student profiles
- [ ] Student academic history timeline
- [ ] Performance comparison charts

---

**Version**: 1.0  
**Date**: November 21, 2025  
**Status**: ✅ Implemented & Tested

