<?php
use Core\Router;
use Controllers\HomeController;
use Controllers\AuthController;
use Controllers\AdminController;
use Controllers\TeacherController;
use Controllers\AdviserController;
use Controllers\StudentController;
use Controllers\ParentController;
use Controllers\ErrorController;
use Controllers\GradeController;
use Controllers\NotificationController;

/** @var Router $router */

$router->get('/', [HomeController::class, 'index']);

// Auth
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// Admin
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/approve-user', [AdminController::class, 'approveUser']);
$router->post('/admin/reject-user', [AdminController::class, 'rejectUser']);
$router->post('/admin/suspend-user', [AdminController::class, 'suspendUser']);
$router->post('/admin/activate-user', [AdminController::class, 'activateUser']);
$router->post('/admin/delete-user', [AdminController::class, 'deleteUser']);
$router->get('/admin/create-user', [AdminController::class, 'createUser']);
$router->post('/admin/create-user', [AdminController::class, 'createUser']);
$router->get('/admin/create-parent', [AdminController::class, 'createParent']);
$router->post('/admin/create-parent', [AdminController::class, 'createParent']);
$router->get('/admin/create-student', [AdminController::class, 'createStudent']);
$router->post('/admin/create-student', [AdminController::class, 'createStudent']);
$router->get('/admin/assign-advisers', [AdminController::class, 'assignAdvisers']);
$router->post('/admin/assign-adviser', [AdminController::class, 'assignAdviser']);
$router->post('/admin/remove-adviser', [AdminController::class, 'removeAdviser']);
$router->get('/admin/classes', [AdminController::class, 'classes']);
$router->get('/admin/create-class', [AdminController::class, 'createClass']);
$router->post('/admin/create-class', [AdminController::class, 'createClass']);
$router->get('/admin/settings', [AdminController::class, 'settings']);
$router->get('/admin/reports', [AdminController::class, 'reports']);
$router->get('/admin/logs', [AdminController::class, 'logs']);
$router->get('/admin/sections', [AdminController::class, 'sections']);
$router->post('/admin/create-section', [AdminController::class, 'createSection']);
$router->post('/admin/update-section', [AdminController::class, 'updateSection']);
$router->post('/admin/delete-section', [AdminController::class, 'deleteSection']);
$router->post('/admin/assign-student-to-section', [AdminController::class, 'assignStudentToSection']);
$router->get('/admin/api/section-details', [AdminController::class, 'getSectionDetails']);
$router->get('/admin/api/unassigned-students', [AdminController::class, 'getUnassignedStudents']);
$router->get('/admin/students', [AdminController::class, 'students']);
$router->get('/admin/view-student', [AdminController::class, 'viewStudent']);
$router->get('/admin/edit-student', [AdminController::class, 'editStudent']);
$router->post('/admin/update-student', [AdminController::class, 'updateStudent']);
$router->get('/admin/teachers', [AdminController::class, 'teachers']);
$router->get('/admin/view-teacher', [AdminController::class, 'viewTeacher']);
$router->get('/admin/subjects', [AdminController::class, 'subjects']);
$router->post('/admin/create-subject', [AdminController::class, 'createSubject']);

// Role dashboards
$router->get('/teacher', [TeacherController::class, 'dashboard']);
$router->get('/teacher/alerts', [TeacherController::class, 'alerts']);
$router->get('/adviser', [AdviserController::class, 'dashboard']);
$router->get('/student', [StudentController::class, 'dashboard']);
$router->get('/parent', [ParentController::class, 'dashboard']);

// Parent pages
$router->get('/parent/grades', [ParentController::class, 'viewChildGrades']);
$router->get('/parent/attendance', [ParentController::class, 'viewChildAttendance']);
$router->get('/parent/profile', [ParentController::class, 'viewChildProfile']);
$router->get('/parent/schedule', [ParentController::class, 'viewChildSchedule']);

// Student pages
$router->get('/student/grades', [StudentController::class, 'grades']);
$router->get('/student/assignments', [StudentController::class, 'assignments']);
$router->get('/student/profile', [StudentController::class, 'profile']);
$router->get('/student/attendance', [StudentController::class, 'attendance']);
$router->get('/student/alerts', [StudentController::class, 'alerts']);
$router->get('/student/schedule', [StudentController::class, 'schedule']);
$router->get('/student/classes', [StudentController::class, 'myClasses']);
$router->get('/student/view-subject', [StudentController::class, 'viewSubject']);

// Teacher pages
$router->get('/teacher/grades', [TeacherController::class, 'grades']);
$router->get('/teacher/classes', [TeacherController::class, 'classes']);
$router->get('/teacher/view-class', [TeacherController::class, 'viewClass']);
$router->get('/teacher/teaching-loads', [TeacherController::class, 'teachingLoads']);
$router->get('/teacher/sections', [TeacherController::class, 'sections']);
$router->get('/teacher/students', [TeacherController::class, 'students']);
$router->get('/teacher/view-student', [TeacherController::class, 'viewStudent']);
$router->get('/teacher/advised-sections', [TeacherController::class, 'advisedSections']);
$router->get('/teacher/add-students', [TeacherController::class, 'addStudentsToSection']);
$router->get('/teacher/assignments', [TeacherController::class, 'assignments']);
$router->get('/teacher/attendance', [TeacherController::class, 'attendance']);
$router->get('/teacher/communication', [TeacherController::class, 'communication']);
$router->get('/teacher/materials', [TeacherController::class, 'materials']);

// Teacher API endpoints
$router->get('/teacher/api/section-details', [TeacherController::class, 'getSectionDetails']);
$router->post('/teacher/api/log-activity', [TeacherController::class, 'logActivity']);
$router->get('/teacher/api/search-student', [TeacherController::class, 'searchStudentByLRN']);
$router->post('/teacher/api/add-student', [TeacherController::class, 'addStudentToSection']);
// Attendance APIs
$router->get('/teacher/api/attendance/list', [TeacherController::class, 'getAttendanceList']);
$router->post('/teacher/api/attendance/save', [TeacherController::class, 'saveAttendance']);
$router->get('/teacher/api/attendance/history', [TeacherController::class, 'getAttendanceHistory']);

// Adviser pages
$router->get('/adviser/students', [AdviserController::class, 'students']);
$router->get('/adviser/performance', [AdviserController::class, 'performance']);
$router->get('/adviser/communication', [AdviserController::class, 'communication']);

// Demo pages
$router->get('/demo/component-library', [HomeController::class, 'componentLibrary']);
$router->get('/demo/component-system', [HomeController::class, 'componentSystemDemo']);
$router->get('/demo/component-showcase', [HomeController::class, 'componentShowcase']);
$router->get('/demo/pwa-features', [HomeController::class, 'pwaFeatures']);
$router->get('/demo/realtime-features', [HomeController::class, 'realtimeFeatures']);

// Offline route (serves static offline page)
$router->get('/offline', function () {
	$file = BASE_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'offline.html';
	if (is_file($file)) {
		header('Content-Type: text/html; charset=utf-8');
		readfile($file);
		return;
	}
	http_response_code(404);
	echo 'Offline page not found';
});

// Grade Forms (SF9/SF10)
$router->get('/grades/sf9', [GradeController::class, 'generateSF9']);
$router->get('/grades/sf9/view', [GradeController::class, 'viewSF9']);
$router->get('/grades/sf10', [GradeController::class, 'generateSF10']);
$router->get('/grades/sf10/view', [GradeController::class, 'viewSF10']);

// Error Pages
$router->get('/error/404', [ErrorController::class, 'notFound']);
$router->get('/error/403', [ErrorController::class, 'forbidden']);
$router->get('/error/500', [ErrorController::class, 'internalServerError']);
$router->get('/error/401', [ErrorController::class, 'unauthorized']);
$router->get('/error/503', [ErrorController::class, 'serviceUnavailable']);

// Generic error handler
$router->get('/error/{code}', function($code) {
	$errorController = new ErrorController();
	$errorController->error((int)$code);
});

// Notification API endpoints
$router->get('/api/notifications', [NotificationController::class, 'getNotifications']);
$router->get('/api/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
$router->post('/api/notifications/mark-read', [NotificationController::class, 'markAsRead']);
$router->post('/api/notifications/delete', [NotificationController::class, 'delete']);

// Notifications page (for all roles)
$router->get('/notifications', [NotificationController::class, 'index']);

