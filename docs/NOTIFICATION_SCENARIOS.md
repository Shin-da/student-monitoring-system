# Complete Notification Scenarios

This document lists all events in the system that should trigger notifications.

## User Management Notifications

### Admin Actions
1. **User Registration Approval**
   - **Recipient**: Approved user
   - **Type**: success
   - **Category**: approval_request
   - **Message**: Account approved, can now log in

2. **User Registration Rejection**
   - **Recipient**: Rejected user
   - **Type**: error
   - **Category**: approval_request
   - **Message**: Registration rejected with reason

3. **User Suspension**
   - **Recipient**: Suspended user
   - **Type**: error
   - **Category**: user_management
   - **Priority**: urgent
   - **Message**: Account suspended, contact administrator

4. **User Activation**
   - **Recipient**: Activated user
   - **Type**: success
   - **Category**: user_management
   - **Message**: Account reactivated

5. **User Deletion**
   - **Recipient**: All admins (optional)
   - **Type**: info
   - **Category**: user_management
   - **Message**: User deleted (audit log)

6. **New User Registration (Pending)**
   - **Recipient**: All admins
   - **Type**: info
   - **Category**: approval_request
   - **Message**: New user registration pending approval

### Student Management
7. **Student Created**
   - **Recipient**: Created student + parents
   - **Type**: success
   - **Category**: user_management
   - **Message**: Student account created

8. **Student Assigned to Section**
   - **Recipient**: Student
   - **Type**: success
   - **Category**: section_assignment
   - **Message**: Assigned to [Section Name]
   
   - **Recipient**: Parents
   - **Type**: info
   - **Category**: section_assignment
   - **Message**: Child assigned to [Section Name]

9. **Student Removed from Section**
   - **Recipient**: Student + parents
   - **Type**: warning
   - **Category**: section_assignment
   - **Message**: Removed from [Section Name]

## Grade Management Notifications

### Grade Submissions
10. **Grade Posted**
    - **Recipient**: Student
    - **Type**: grade
    - **Category**: grade_submitted
    - **Message**: New grade posted for [Subject]: [Score]

11. **Grade Updated**
    - **Recipient**: Student
    - **Type**: grade
    - **Category**: grade_update
    - **Message**: Grade updated for [Subject]

12. **Low Grade Alert**
    - **Recipient**: Parents
    - **Type**: grade
    - **Category**: low_grade_alert
    - **Priority**: high
    - **Message**: Child's grade in [Subject] is below 75%

13. **Low Grade Alert (Adviser)**
    - **Recipient**: Adviser
    - **Type**: grade
    - **Category**: low_grade_alert
    - **Priority**: high
    - **Message**: [Student] has low grade in [Subject]

14. **Quarterly Grades Posted**
    - **Recipient**: Student + parents
    - **Type**: grade
    - **Category**: grade_submitted
    - **Message**: Quarterly grades for [Quarter] posted

15. **Final Grades Posted**
    - **Recipient**: Student + parents
    - **Type**: grade
    - **Category**: grade_submitted
    - **Priority**: high
    - **Message**: Final grades for [Subject] posted

## Assignment Management Notifications

16. **New Assignment Posted**
    - **Recipient**: Class members (students)
    - **Type**: assignment
    - **Category**: assignment_new
    - **Message**: New [Type]: [Title]. Due: [Date]

17. **Assignment Updated**
    - **Recipient**: Class members
    - **Type**: assignment
    - **Category**: assignment_new
    - **Message**: Assignment "[Title]" has been updated

18. **Assignment Due Soon (2 days)**
    - **Recipient**: Students
    - **Type**: warning
    - **Category**: assignment_due
    - **Priority**: high
    - **Message**: "[Title]" is due in 2 days

19. **Assignment Due Tomorrow**
    - **Recipient**: Students + parents
    - **Type**: warning
    - **Category**: assignment_due
    - **Priority**: urgent
    - **Message**: "[Title]" is due tomorrow

20. **Assignment Overdue**
    - **Recipient**: Students + parents
    - **Type**: error
    - **Category**: assignment_due
    - **Priority**: urgent
    - **Message**: "[Title]" is overdue

21. **Assignment Submitted**
    - **Recipient**: Teacher
    - **Type**: info
    - **Category**: assignment_new
    - **Message**: [Student] submitted "[Title]"

22. **Assignment Graded**
    - **Recipient**: Student
    - **Type**: grade
    - **Category**: grade_submitted
    - **Message**: "[Title]" has been graded: [Score]/[Total]

## Attendance Management Notifications

23. **Attendance Marked (Absent)**
    - **Recipient**: Parents
    - **Type**: attendance
    - **Category**: attendance_marked
    - **Message**: [Student] was absent on [Date] for [Subject]

24. **Attendance Marked (Late)**
    - **Recipient**: Parents
    - **Type**: attendance
    - **Category**: attendance_marked
    - **Message**: [Student] was late on [Date] for [Subject]

25. **Excessive Absences Alert**
    - **Recipient**: Parents
    - **Type**: attendance
    - **Category**: attendance_alert
    - **Priority**: urgent
    - **Message**: [Student] has [X] absences in [Subject]

26. **Excessive Absences Alert (Adviser)**
    - **Recipient**: Adviser
    - **Type**: attendance
    - **Category**: attendance_alert
    - **Priority**: high
    - **Message**: [Student] has [X] absences in [Subject]

27. **Attendance Pattern Alert**
    - **Recipient**: Parents + Adviser
    - **Type**: attendance
    - **Category**: attendance_alert
    - **Priority**: high
    - **Message**: [Student] has been absent [X] times this week

## Schedule Management Notifications

28. **Schedule Conflict Detected**
    - **Recipient**: Admin
    - **Type**: error
    - **Category**: schedule_change
    - **Priority**: high
    - **Message**: Cannot create class: Teacher has conflict

29. **Class Created**
    - **Recipient**: Teacher
    - **Type**: schedule
    - **Category**: class_created
    - **Message**: Assigned to teach [Subject] for [Section]

30. **Class Created (Section)**
    - **Recipient**: Section members
    - **Type**: schedule
    - **Category**: class_created
    - **Message**: New class: [Subject] with [Teacher]. Schedule: [Time]

31. **Schedule Changed**
    - **Recipient**: Class members + teacher
    - **Type**: schedule
    - **Category**: schedule_change
    - **Priority**: high
    - **Message**: Schedule changed for [Subject]: [New Schedule]

32. **Room Changed**
    - **Recipient**: Class members + teacher
    - **Type**: schedule
    - **Category**: schedule_change
    - **Message**: Room changed for [Subject]: [New Room]

33. **Teacher Assigned**
    - **Recipient**: Section members
    - **Type**: schedule
    - **Category**: class_created
    - **Message**: [Teacher] is now teaching [Subject]

## Section Management Notifications

34. **Section Created**
    - **Recipient**: All admins
    - **Type**: info
    - **Category**: section_assignment
    - **Message**: New section created: [Section Name]

35. **Adviser Assigned**
    - **Recipient**: Adviser
    - **Type**: success
    - **Category**: section_assignment
    - **Message**: Assigned as adviser for [Section Name]

36. **Adviser Assigned (Section)**
    - **Recipient**: Section members
    - **Type**: info
    - **Category**: section_assignment
    - **Message**: [Adviser Name] is now your section adviser

37. **Adviser Removed**
    - **Recipient**: Previous adviser
    - **Type**: info
    - **Category**: section_assignment
    - **Message**: Removed as adviser from [Section Name]

38. **Student Added to Section**
    - **Recipient**: Section adviser
    - **Type**: info
    - **Category**: section_assignment
    - **Message**: [Student] added to [Section Name]

39. **Section Capacity Reached**
    - **Recipient**: Admins
    - **Type**: warning
    - **Category**: section_assignment
    - **Priority**: high
    - **Message**: [Section Name] has reached capacity ([X]/[Max])

## System-Wide Notifications

40. **System Maintenance Scheduled**
    - **Recipient**: All users
    - **Type**: system
    - **Category**: system_alert
    - **Priority**: high
    - **Expires**: After maintenance window
    - **Message**: System maintenance on [Date] from [Time] to [Time]

41. **System Maintenance Completed**
    - **Recipient**: All users
    - **Type**: system
    - **Category**: system_alert
    - **Message**: System maintenance completed. All services restored.

42. **New Feature Announcement**
    - **Recipient**: All users or specific roles
    - **Type**: info
    - **Category**: system_alert
    - **Message**: New feature available: [Feature Name]

43. **Security Alert**
    - **Recipient**: All users
    - **Type**: error
    - **Category**: system_alert
    - **Priority**: urgent
    - **Message**: Security alert: [Details]

44. **Data Export Ready**
    - **Recipient**: Requesting user
    - **Type**: success
    - **Category**: system_alert
    - **Message**: Your data export is ready for download

45. **Backup Completed**
    - **Recipient**: Admins
    - **Type**: success
    - **Category**: system_alert
    - **Message**: Database backup completed successfully

## Communication Notifications

46. **Message Received (if messaging feature exists)**
    - **Recipient**: Message recipient
    - **Type**: info
    - **Category**: system_alert
    - **Message**: New message from [Sender]

47. **Announcement Posted**
    - **Recipient**: Target audience (section/class/all)
    - **Type**: info
    - **Category**: system_alert
    - **Message**: New announcement: [Title]

## Report Notifications

48. **Report Generated**
    - **Recipient**: Requesting user
    - **Type**: success
    - **Category**: system_alert
    - **Message**: [Report Type] report is ready

49. **Report Generation Failed**
    - **Recipient**: Requesting user
    - **Type**: error
    - **Category**: system_alert
    - **Message**: Failed to generate [Report Type] report

## Parent-Specific Notifications

50. **Child's Profile Updated**
    - **Recipient**: Parents
    - **Type**: info
    - **Category**: user_management
    - **Message**: [Child's] profile information has been updated

51. **Parent Account Created**
    - **Recipient**: Created parent
    - **Type**: success
    - **Category**: user_management
    - **Message**: Parent account created. Linked to [Student Name]

## Teacher-Specific Notifications

52. **Teaching Load Updated**
    - **Recipient**: Teacher
    - **Type**: info
    - **Category**: schedule_change
    - **Message**: Your teaching load has been updated

53. **Class Removed**
    - **Recipient**: Teacher
    - **Type**: warning
    - **Category**: schedule_change
    - **Message**: Class [Subject] for [Section] has been removed

## Summary

Total: **53 notification scenarios** covering:
- User management: 9 scenarios
- Grade management: 6 scenarios
- Assignment management: 7 scenarios
- Attendance management: 5 scenarios
- Schedule management: 6 scenarios
- Section management: 6 scenarios
- System-wide: 6 scenarios
- Communication: 2 scenarios
- Reports: 2 scenarios
- Parent-specific: 2 scenarios
- Teacher-specific: 2 scenarios

Each notification should be:
- ✅ Properly categorized
- ✅ Routed to correct recipients
- ✅ Include relevant links
- ✅ Have appropriate priority
- ✅ Include metadata for filtering

