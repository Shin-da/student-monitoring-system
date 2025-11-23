# Grade Computation System Update

## Overview
The grade computation system has been updated to match the new school requirements:

- **50%** - Performance Task (PT)
- **20%** - Final Examination (QE) 
- **20%** - Written Works (WW)
- **10%** - Attendance

## Changes Made

### 1. Database Updates
- **File**: `database/update_grade_computation.sql`
- Added `attendance_percent` column to `subjects` table
- Updated default percentages for all existing subjects:
  - WW: 30% → 20%
  - PT: 50% (unchanged)
  - QE: 20% (unchanged)
  - Attendance: 10% (new)

### 2. GradeModel Updates
- **File**: `app/Models/GradeModel.php`
- Updated `calculateQuarterlyGrade()` method to:
  - Include `attendance_percent` in subject weight retrieval
  - Calculate attendance average for the quarter
  - Include attendance in final grade calculation
- Added new `calculateAttendanceAverage()` method:
  - Calculates attendance percentage based on present/late/excused vs total days
  - Handles quarter date ranges correctly (Q1: Jun-Aug, Q2: Sep-Nov, Q3: Dec-Feb, Q4: Mar-May)
  - Returns 0.0 if no attendance records found

### 3. View Updates
- **File**: `resources/views/student/profile.php`
  - Added attendance percentage display in subject information
- **File**: `resources/views/student/grades.php`
  - Added "Attendance" column to grades table
  - Displays attendance average percentage

### 4. Controller Updates
- **File**: `app/Controllers/StudentController.php`
  - Updated to fetch `attendance_percent` from subjects table
  - Includes `attendance_average` in quarterly grades data

### 5. Documentation Updates
- **File**: `docs/ERD_NOTES.md`
  - Updated subject table schema to reflect new defaults and attendance_percent column

## How It Works

### Attendance Calculation
Attendance is calculated per quarter based on:
- **Present days**: Status = 'present', 'late', or 'excused'
- **Total days**: All attendance records for the quarter
- **Formula**: `(Present Days / Total Days) × 100`

### Final Grade Calculation
```
Final Grade = (WW Average × 20%) + 
              (PT Average × 50%) + 
              (QE Average × 20%) + 
              (Attendance Average × 10%)
```

### Quarter Date Ranges
- **Quarter 1**: June 1 - August 31
- **Quarter 2**: September 1 - November 30
- **Quarter 3**: December 1 - February 28/29 (spans two years)
- **Quarter 4**: March 1 - May 31

## Migration Instructions

1. **Run the SQL migration**:
   ```sql
   source database/update_grade_computation.sql;
   ```
   Or execute the SQL file in your database management tool.

2. **Verify the update**:
   - Check that `attendance_percent` column exists in `subjects` table
   - Verify all subjects have updated percentages (WW=20, PT=50, QE=20, Attendance=10)

3. **Test the calculation**:
   - View a student's grades to see attendance included
   - Verify final grades are calculated correctly with attendance

## Notes

- Attendance calculation requires attendance records to be entered in the system
- If no attendance records exist for a quarter, attendance average will be 0.0
- The system automatically handles quarter date ranges based on the academic year
- All existing grade calculations will now include attendance in the final grade

## Backward Compatibility

- Existing grade records are preserved
- The system will use default percentages (20/50/20/10) if subject-specific percentages are not set
- Views that display grades will show attendance as "-" if no attendance data is available

