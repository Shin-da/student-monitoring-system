# Static Data Refactor Summary

As of November 2025 the student monitoring application no longer relies on placeholder data. Every dashboard now queries the live database tables and gracefully handles missing datasets.

## Highlights

- **Static helper removed**: `app/Helpers/StaticData.php` has been deleted; no controllers call `StaticData::*`.
- **Admin experience**: Dashboard statistics aggregate directly from `users`, `sections`, `classes`, `subjects`, and `audit_logs`.
- **Teacher workflows**: Class lists, grades, assignments, and attendance pages pull data from `classes`, `student_classes`, `grades`, `assignments`, and `attendance`.
- **Adviser portal**: Advisory sections and student rosters query `sections`, `students`, and `audit_logs`.
- **Parent portal**: Linked pupil information is sourced through `users.linked_student_user_id`, `students`, and `assignments`.

If additional static scaffolding is introduced in the future, document it in the relevant feature specification and ensure it includes a clear removal path.

