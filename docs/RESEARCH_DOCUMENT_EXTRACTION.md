# Research Document Information Extraction

**Document:** Chapter 1-3 REVISED.pdf  
**Title:** A WEB-BASED SMART STUDENT MONITORING SYSTEM WITH AI-POWERED PERFORMANCE ANALYTICS AND EARLY INTERVENTION ALERTS FOR ST. IGNATIUS ACADEMY

**Institution:** Pamantasan ng Lungsod ng Muntinlupa  
**College:** College of Information Technology and Computer Studies  
**Degree:** Bachelor of Science in Information Technology  
**Authors:** BALASICO, HERSHEY NICOLE L. | CHAVEZ, BRYLLE C. | ZARATE, ADRIAN CARLO S.  
**Date:** June 2025

## ✅ Extraction Status

**Status:** Successfully extracted text from PDF using PyPDF2  
**Pages:** 44 pages  
**Extraction Date:** 2025-01-27  
**Extracted Text File:** `research_document_extracted_text.txt`

---

## 🎯 Research Goals and Objectives

### General Objective

The main objective of this study is to **develop a smart student monitoring system integrated with AI-powered performance analytics and early intervention alerts** to enhance academic performance tracking and provide timely support for students at St. Ignatius Academy.

### Specific Objectives

1. **To design the Web-Based Smart Student Monitoring System with AI-Powered Performance Analytics and Early Intervention Alerts** for St. Ignatius Academy with the following features:
   - **a.** Capable of collecting student academic activities and performance data through teacher inputs and records.
   - **b.** Capable of presenting summarized student performance using dashboards and visual reports for easier monitoring by the school administrator.
   - **c.** Capable of analyzing students' academic data and identifying those who are at risk of academic failure.
   - **d.** Capable of delivering early intervention alerts to inform students of possible academic risks.

2. **To develop the web-based Smart Student Monitoring System** using **HTML, CSS, and JavaScript** for the frontend, and **MySQL** for the database.

3. **To test and improve the system** using Alpha and Beta Testing.

4. **To evaluate the system** using **ISO/IEC 25010:2011 Software Evaluation** for IT experts and actual users.

5. **To implement the proposal system** to St. Ignatius Academy.

---

## 📋 Purpose and Description

### Purpose

The purpose of this project is to **help teachers and school administrators monitor student academic performance more efficiently**. The system will be a web-based academic monitoring tool designed for the Senior High School teachers of St. Ignatius Academy. It will allow them to track, analyze, and manage students' grades and performance using the system.

### Key Benefits

- **Reduce difficulties** that teachers face in recording and checking student progress every grading period
- **Serve as an easy-to-use website** that will lessen the workload of teachers
- **Provide a centralized space** for managing student academic data
- **Ensure all student data is up to date, accurate, and accessible** for both teaching and administrative purposes
- **Help in identifying students** who may be at risk or require academic support

---

## 🔧 Technology Stack (From Research Document)

### Frontend Technologies
- **HTML** - Structure and markup
- **CSS** - Styling and layout
- **JavaScript** - Client-side interactivity
- **Bootstrap** - CSS framework for responsive design
- **AJAX** - Asynchronous data requests
- **jQuery** - JavaScript library for DOM manipulation

### Backend Technologies
- **PHP** or **Python** - Server-side programming language
- **MySQL** - Relational database management system

### AI/ML Frameworks
- **Rule-Based AI** - The system will include a **rule-based AI feature** that automatically checks student academic data (quiz scores, activities, major exam results, attendance). If a student's average grade falls below the passing mark, the system will send an alert to both the student and the teacher.
- **Scikit-learn** - Machine learning library for Python (mentioned as option)
- **TensorFlow** - Deep learning framework (mentioned as alternative option)

**Note:** The research document specifies a **rule-based AI approach** rather than complex machine learning models. This is a simpler, more deterministic system that checks predefined rules (e.g., "if average grade < passing mark, then send alert").

### Development Tools
- **Code Editors:**
  - Sublime Text
  - Visual Studio Code (VS Code) - Primary IDE for writing, testing, and fixing source code
- **Local Development Environment:**
  - **XAMPP** - For local hosting and testing (Apache, MySQL, PHP)
- **Design Tools:**
  - **Canva** - For designing icons, banners, and visual elements
  - **Draw.io** - For creating system diagrams (Functional Decomposition Diagram, database designs)

### Development Methodology
- **Agile Methodology** - Following phases:
  1. Requirements gathering and planning
  2. System design
  3. Development
  4. Testing
  5. Deployment
  6. Review
  7. Final launch

### Evaluation Framework
- **ISO/IEC 25010:2011** - Software Quality Evaluation Model
  - Evaluation by IT experts
  - Evaluation by actual users

---

## 📊 System Specifications and Requirements

### Scope

The scope of the study is to develop and implement a **Smart Student Monitoring System** equipped with **AI-powered performance analytics and early intervention alerts** developed for the needs of St. Ignatius Academy.

The system is designed to:
- **Collect and analyze** student academic data and performance
- **Identify learning trends** and predict potential academic risks
- **Generate alerts** when students show signs of academic difficulty through AI-driven algorithms

### Target Institution

**St. Ignatius Academy**
- Location: Navs Building, National Road, Muntinlupa Branch
- Focus: Senior High School level
- Type: Private educational institution

### Functional Requirements

#### School Administrator's Module

The School Administrator's Module handles the main functions of the system:

1. **User Management:**
   - Manage teacher and student information
   - Add student and teacher records

2. **Academic Setup:**
   - Set up the school year
   - Assign subjects per grade level
   - Create class sections
   - Appoint advisers
   - Assign subject teachers to specific sections

3. **Dashboard & Monitoring:**
   - View total number of sections, students, teachers, and subjects
   - Access academic performance reports
   - Monitor overall student performance

#### Teacher's Module

The Teacher Module is designed for teachers to manage and monitor student performance:

**Subject Teachers Can:**
- Add or edit grades
- Input assessment results
- View their assigned sections
- Track grade trends through visual tools

**Class Advisers Can:**
- Monitor overall performance of their advisory class
- View grades and assessment results
- Track students' academic progress
- Provide support when needed

#### Student Academic Data Collection

The main activity being monitored is **student academic performance**, including:
- **Grades in quizzes**
- **Written works**
- **Performance tasks**
- **Final exams**
- **Attendance**

### System Architecture

#### Conceptual Framework

The system follows a **Conceptual Framework** with the following phases:

**1. Input Phase:**
- **Knowledge Requirements:**
  - Web-based monitoring systems
  - Use of AI in educational analytics
  - Tracking academic, behavioral, and social performance metrics
  - Learning analytics dashboards
  - Student performance evaluation methods
  - Educational technologies (EdTech)

- **Software Requirements:**
  - HTML, CSS, JavaScript
  - Bootstrap
  - PHP or Python for backend
  - MySQL for database
  - AJAX, jQuery
  - Code editors (Sublime Text or Visual Studio Code)
  - AI/ML frameworks (Scikit-learn or TensorFlow)

- **Hardware Requirements:**
  - Average hardware specifications
  - Intel Core i5 processor
  - 8GB of RAM
  - Solid-state drive (SSD)
  - Wi-Fi routers or network switches (for LAN deployment)

**2. Process Phase:**
- Follows **Agile methodology**
- Requirements gathering and planning
- System design (including FDD and database designs)
- Development and testing
- Deployment
- Review
- Final launch

**3. Output Phase:**
- Fully functional web-based smart student monitoring system
- Features AI-powered performance analytics
- Early intervention alerts
- Designed to support and improve student monitoring and academic performance at St. Ignatius Academy

**4. Evaluation Loop:**
- Collecting user feedback from the deployed system
- Applying necessary updates and adjustments
- Maintains system functionality, user-friendliness, and adaptability to changing needs

### Deployment Specifications

#### Local Development & Testing
- **Local Hosting:** XAMPP within a Local Area Network (LAN) during testing
- **Network Setup:** Basic networking tools like Wi-Fi routers or network switches for connecting multiple devices within the school

#### Production Deployment
- **Option 1:** Online web hosting service (if system needs to be accessed outside the school)
- **Option 2:** LAN deployment within the school premises

### Testing Approach

1. **Alpha Testing** - Initial testing phase
2. **Beta Testing** - User acceptance testing phase
3. **Evaluation** - Using ISO/IEC 25010:2011 Software Evaluation
   - IT experts evaluation
   - Actual users evaluation

---

## 📚 Related Literature and Studies Context

### Key Focus Areas

1. **Data-Driven Decision Making in Education**
   - Schools turning to data-driven strategies to improve decision-making in the classroom
   - Teachers can quickly spot learners who are struggling academically
   - Enables earlier interventions

2. **Student Performance Monitoring**
   - Tracking student academic activities and performance
   - Collection of student academic data through teacher inputs
   - Monitoring of grades, attendance, and assessment results

### Current Process at St. Ignatius Academy

#### Existing Manual Process

1. **Teacher Process:**
   - Encode submitted work of students
   - Store results in class record
   - Calculate total scores for written work and performance tasks
   - Generate quarterly grades
   - Submit grades to class adviser

2. **Class Adviser Process:**
   - Input students' grades into SF 9 (Progress Report Card) and SF 10 (Learner's Permanent Record)
   - Forward SF 9 and SF 10 to school administrator

3. **School Administrator Process:**
   - Review, evaluate, and store student grades in School Record
   - Manage class information
   - Monitor overall performance of students
   - Identify areas that need improvement

---

## 📝 Cross-Reference Checklist

Use this checklist to verify implementation against research requirements:

### Research Objectives
- [x] General objective defined
- [x] Specific objectives (5) identified
- [x] Features specified (data collection, dashboards, analytics, alerts)

### Technology Stack
- [x] Frontend: HTML, CSS, JavaScript, Bootstrap
- [x] Backend: PHP or Python, MySQL
- [x] AI/ML frameworks: Scikit-learn or TensorFlow
- [x] Development tools identified
- [x] Methodology: Agile

### System Specifications
- [x] School Administrator Module features
- [x] Teacher Module features (Subject Teachers & Advisers)
- [x] Student data collection (grades, attendance, assessments)
- [x] Dashboard and visualization requirements
- [x] AI-powered analytics requirement
- [x] Early intervention alerts requirement

### Functional Requirements
- [x] User management (admin, teachers, students)
- [x] Academic setup (school year, subjects, sections)
- [x] Grade management (input, edit, computation)
- [x] Performance monitoring and reporting
- [x] Risk identification and alerts

### Testing & Evaluation
- [x] Alpha and Beta testing approach
- [x] ISO/IEC 25010:2011 evaluation framework
- [x] IT experts evaluation
- [x] Actual users evaluation

### Deployment
- [x] Local development (XAMPP, LAN)
- [x] Production deployment options
- [x] Hardware specifications

---

## 🔄 Implementation Status vs Research Requirements

### ✅ Implemented Features (Based on Current Codebase)

1. ✅ User Management (Admin, Teacher, Adviser, Student, Parent roles)
2. ✅ Student Registration with LRN
3. ✅ Section Management
4. ✅ Subject Management
5. ✅ Class Creation and Management
6. ✅ Grade Input and Computation (Written Work, Performance Task, Quarterly Exam)
7. ✅ Teacher Schedule Management
8. ✅ Student Schedule Viewing
9. ✅ Attendance Tracking
10. ✅ Dashboard Implementation
11. ✅ Activity Logging

### ⚠️ Partially Implemented / Needs Enhancement

1. ⚠️ **Rule-Based AI Performance Analytics**
   - Status: Alert system structure exists but rule-based AI logic needs implementation
   - Required: Rule-based AI feature that automatically checks:
     - Quiz scores
     - Activities/assignments
     - Major exam results
     - Attendance
     - Average grade calculations
   - Alert Trigger: When student's average grade falls below passing mark, send alert to both student and teacher
   - Note: Research specifies **rule-based AI** (simpler, deterministic rules) rather than complex ML models

2. ⚠️ **Early Intervention Alerts**
   - Status: Alert system structure exists but rule-based trigger logic needed
   - Required: Automated alerts based on rule-based AI analysis of academic risks
   - Alert Recipients: Both student and teacher should receive alerts

3. ⚠️ **Visual Reports and Dashboards**
   - Status: Basic dashboards exist but may need enhancement per research spec
   - Required: Enhanced visualizations for performance monitoring

### ❌ Missing Features (From Research Requirements)

1. ❌ **Rule-Based AI Implementation**
   - Need: Rule-based AI feature that automatically checks student academic data
   - Implementation: Logic to check quiz scores, activities, major exam results, attendance
   - Alert Logic: Trigger alerts when average grade falls below passing mark
   - Note: The research specifies **rule-based AI** (simpler approach) rather than complex ML models like Scikit-learn/TensorFlow

2. ❌ **ISO/IEC 25010:2011 Evaluation Framework**
   - Need: Formal evaluation framework implementation

3. ❌ **Beta Testing Documentation**
   - Need: User acceptance testing documentation

---

## 📌 Notes and Recommendations

1. **Rule-Based AI Implementation Priority:**
   - The research document specifically requires **rule-based AI** (not complex ML models)
   - Current system has the foundation but needs rule-based logic implementation
   - **Key Requirements:**
     - Automatically check student academic data (quiz scores, activities, major exam results, attendance)
     - Calculate average grades
     - Trigger alerts when average grade falls below passing mark
     - Send alerts to both student and teacher
   - Recommendation: Implement rule-based logic first (simpler, deterministic approach) before considering ML models

2. **Early Intervention Alerts:**
   - System structure exists but needs AI-driven risk detection
   - Recommendation: Develop algorithms to analyze student performance patterns and trigger alerts

3. **Evaluation Framework:**
   - ISO/IEC 25010:2011 evaluation is required but not yet implemented
   - Recommendation: Create evaluation metrics and user feedback mechanisms

4. **Visualization Enhancement:**
   - Research emphasizes visual reports and dashboards
   - Recommendation: Enhance dashboard with more comprehensive visualizations

---

**Last Updated:** 2025-01-27  
**Status:** ✅ Complete extraction - All key information extracted from PDF  
**Extracted Text Available:** `research_document_extracted_text.txt`  
**Alternative Source:** `docs/A WEB-BASED SMART STUDENT MONITORING SYS.md` (Markdown conversion of PDF)

---

## 📌 Key Implementation Clarification

**Important:** The research document specifies a **rule-based AI** approach, not complex machine learning models. This means:

- ✅ **Simpler Implementation:** Rule-based logic (if-then conditions) rather than ML training
- ✅ **Deterministic:** Clear, predictable behavior based on predefined rules
- ✅ **Specific Rules:**
  - Check quiz scores, activities, major exam results, attendance
  - Calculate average grade
  - If average grade < passing mark → Send alert to student AND teacher
- ⚠️ **Not Required:** Complex ML frameworks like Scikit-learn or TensorFlow (these are mentioned as optional/alternative technologies, but the core requirement is rule-based)
