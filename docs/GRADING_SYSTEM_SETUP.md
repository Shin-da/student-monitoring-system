# Grading System & SF9/SF10 Forms Setup Guide

## Overview

This system now includes a complete grading system with automatic SF9 (Form 137 - Permanent Record) and SF10 (Form 138 - Report Card) generation that updates automatically when grades are uploaded.

## Features

- ✅ Complete grade entry system (Written Work, Performance Task, Quarterly Exam)
- ✅ Automatic grade calculations with weighted averages
- ✅ Quarter-based grade tracking
- ✅ Academic year management
- ✅ SF9 (Permanent Record) PDF generation
- ✅ SF10 (Report Card) PDF generation
- ✅ Print and download functionality
- ✅ Auto-updates when grades change

## Database Setup

### Step 1: Run the Database Migration

Execute the SQL migration file to enhance the grades table:

```bash
mysql -u root -p student_monitoring < database/enhance_grades_table.sql
```

Or import it through phpMyAdmin:
1. Open phpMyAdmin
2. Select `student_monitoring` database
3. Go to Import tab
4. Select `database/enhance_grades_table.sql`
5. Click Go

This migration will:
- Add all necessary columns (grade_type, quarter, academic_year, etc.)
- Create views for quarterly and final grade calculations
- Set up proper indexes for performance

## PDF Library Setup (Optional but Recommended)

For best PDF quality, install TCPDF:

```bash
composer require tecnickcom/tcpdf
```

Or manually:
1. Download TCPDF from https://github.com/tecnickcom/TCPDF
2. Extract to `vendor/tecnickcom/tcpdf/`

**Note:** The system will work without TCPDF using HTML fallback, but PDF quality will be better with TCPDF installed.

## Usage

### Grade Entry

#### Via API (for teachers)

**Endpoint:** `POST /api/teacher/submit-grade.php`

**Request Body:**
```json
{
  "student_id": 1,
  "subject_id": 1,
  "section_id": 1,
  "grade_type": "ww",
  "quarter": 1,
  "grade_value": 85.5,
  "max_score": 100,
  "description": "Quiz #1",
  "remarks": "Good work",
  "academic_year": "2024-2025"
}
```

**Grade Types:**
- `ww` - Written Work
- `pt` - Performance Task
- `qe` - Quarterly Exam

**Quarters:**
- `1` - 1st Quarter
- `2` - 2nd Quarter
- `3` - 3rd Quarter
- `4` - 4th Quarter

#### Via Teacher Interface

1. Navigate to `/teacher/grades`
2. Click "Add Grade"
3. Fill in the form:
   - Select student
   - Select subject
   - Select grade type (WW, PT, or QE)
   - Select quarter
   - Enter grade value
   - (Optional) Enter description and remarks
4. Submit

### Viewing Grades

#### Student View
- Navigate to `/student/grades`
- View all grades by quarter and subject
- See calculated final grades

#### Teacher View
- Navigate to `/teacher/grades`
- Filter by section, subject, or grade type
- View statistics and pending grades

### Generating SF9 (Permanent Record)

**Download PDF:**
```
/grades/sf9?student_id=1&academic_year=2024-2025
```

**View in Browser (for printing):**
```
/grades/sf9/view?student_id=1&academic_year=2024-2025
```

**Access:**
- Students can view their own SF9
- Teachers/Admins can view any student's SF9
- Parents can view their child's SF9

### Generating SF10 (Report Card)

**Download PDF:**
```
/grades/sf10?student_id=1&quarter=1&academic_year=2024-2025
```

**View in Browser (for printing):**
```
/grades/sf10/view?student_id=1&quarter=1&academic_year=2024-2025
```

**Parameters:**
- `student_id` - Required: Student ID
- `quarter` - Required: Quarter number (1-4)
- `academic_year` - Optional: Defaults to current academic year

## Grade Calculation

The system automatically calculates final grades using the subject's weight configuration:

**Default Weights:**
- Written Work (WW): 30%
- Performance Task (PT): 50%
- Quarterly Exam (QE): 20%

**Custom Weights:**
Subject weights can be configured in the `subjects` table:
- `ww_percent` - Written Work percentage
- `pt_percent` - Performance Task percentage
- `qe_percent` - Quarterly Exam percentage

**Calculation Formula:**
```
Final Grade = (WW Average × WW%) + (PT Average × PT%) + (QE Average × QE%)
```

**Quarterly Average:**
- WW Average = Average of all WW grades for the quarter
- PT Average = Average of all PT grades for the quarter
- QE Average = Average of all QE grades for the quarter

**Final Grade (SF9):**
- Average of all 4 quarters

## Academic Year

The system automatically determines the current academic year:
- If current month is June or later: `YYYY-YYYY+1`
- If current month is before June: `YYYY-1-YYYY`

Example: If today is March 2025, academic year is `2024-2025`

## Auto-Update Feature

SF9 and SF10 forms automatically reflect the latest grades:
- When a teacher uploads a new grade, it's immediately available in the forms
- No manual refresh needed
- Forms are generated on-demand from current database data

## Integration with Frontend

### Add Download Buttons to Student Grades Page

Add to `resources/views/student/grades.php`:

```php
<div class="d-flex gap-2">
  <a href="<?= \Helpers\Url::to('/grades/sf9?student_id=' . $studentId) ?>" 
     class="btn btn-primary" target="_blank">
    Download SF9
  </a>
  <a href="<?= \Helpers\Url::to('/grades/sf10?student_id=' . $studentId . '&quarter=1') ?>" 
     class="btn btn-primary" target="_blank">
    Download SF10 (Q1)
  </a>
</div>
```

### Add Print Buttons

```php
<a href="<?= \Helpers\Url::to('/grades/sf9/view?student_id=' . $studentId) ?>" 
   class="btn btn-outline-primary" target="_blank">
  Print SF9
</a>
```

## Troubleshooting

### Grades not showing
1. Check that grades table has been migrated
2. Verify student has section assigned
3. Check that teacher_id matches logged-in teacher

### PDF not generating
1. Check if TCPDF is installed (optional but recommended)
2. System will fallback to HTML if TCPDF not available
3. Check file permissions for vendor directory

### Calculation errors
1. Verify subject has weight percentages set
2. Check that grade_type values are correct (ww, pt, qe)
3. Ensure quarter values are 1-4

## API Endpoints Summary

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/teacher/submit-grade.php` | POST | Create new grade |
| `/api/teacher/submit-grade.php?id=X` | PUT | Update grade |
| `/api/teacher/submit-grade.php?id=X` | DELETE | Delete grade |
| `/grades/sf9` | GET | Download SF9 PDF |
| `/grades/sf9/view` | GET | View SF9 in browser |
| `/grades/sf10` | GET | Download SF10 PDF |
| `/grades/sf10/view` | GET | View SF10 in browser |

## Next Steps

1. Run the database migration
2. (Optional) Install TCPDF for better PDF quality
3. Test grade entry via teacher interface
4. Generate test SF9/SF10 forms
5. Integrate download buttons into student/teacher dashboards

## Support

For issues or questions, check:
- Database migration logs
- PHP error logs
- Browser console for frontend issues

