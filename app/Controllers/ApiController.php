<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\View;
use Helpers\Security;
use Helpers\Notification;
use Helpers\ActivityLogger;
use Exception;

class ApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Set API headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Apply security headers
        Security::setSecurityHeaders();
    }
    
    public function dashboard()
    {
        try {
            $this->requireAuth();
            
            $stats = $this->getDashboardStats();
            $recentActivity = ActivityLogger::getRecentLogs(10);
            $notifications = Notification::getFlashed();
            
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_activity' => $recentActivity,
                    'notifications' => $notifications,
                    'timestamp' => time()
                ]
            ]);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    public function students()
    {
        try {
            $this->requireAuth();
            
            $students = $this->getStudentsData();
            
            ActivityLogger::logUserAction('viewed_students_api');
            
            $this->jsonResponse([
                'success' => true,
                'data' => $students,
                'count' => count($students),
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    public function student($id)
    {
        try {
            $this->requireAuth();
            
            if (!$id || !is_numeric($id)) {
                throw new Exception('Invalid student ID');
            }
            
            $student = $this->getStudentData((int)$id);
            
            if (!$student) {
                throw new Exception('Student not found');
            }
            
            ActivityLogger::logStudentAction('viewed_student_api', (int)$id);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $student,
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    public function grades()
    {
        try {
            $this->requireAuth();
            
            $filters = [
                'student_id' => $_GET['student_id'] ?? null,
                'subject' => $_GET['subject'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            $grades = $this->getGradesData($filters);
            
            ActivityLogger::logUserAction('viewed_grades_api', $filters);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $grades,
                'filters' => $filters,
                'count' => count($grades),
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    public function addGrade()
    {
        try {
            $this->requireAuth();
            $this->requireMethod('POST');
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            $errors = Security::validateInput($input, [
                'student_id' => ['required' => true, 'numeric' => true],
                'subject_id' => ['required' => true, 'numeric' => true],
                'grade_type' => ['required' => true],
                'quarter' => ['required' => true, 'numeric' => true],
                'grade_value' => ['required' => true, 'numeric' => true],
                'max_score' => ['numeric' => true],
                'description' => ['max_length' => 255],
                'remarks' => ['max_length' => 1000]
            ]);
            
            if (!empty($errors)) {
                $this->jsonError('Validation failed', $errors, 400);
                return;
            }
            
            // Validate grade_type
            if (!in_array($input['grade_type'], ['ww', 'pt', 'qe'])) {
                throw new Exception('Invalid grade_type. Must be ww, pt, or qe');
            }
            
            // Validate quarter
            if (!in_array((int)$input['quarter'], [1, 2, 3, 4])) {
                throw new Exception('Invalid quarter. Must be 1, 2, 3, or 4');
            }
            
            $sanitized = Security::sanitizeArray($input, [
                'student_id' => 'int',
                'subject_id' => 'int',
                'section_id' => 'int',
                'grade_type' => 'string',
                'quarter' => 'int',
                'grade_value' => 'float',
                'max_score' => 'float',
                'description' => 'string',
                'remarks' => 'string',
                'academic_year' => 'string'
            ]);
            
            $gradeId = $this->saveGrade($sanitized);
            
            ActivityLogger::logGradeAction('added_grade_api', $gradeId, $sanitized);
            Notification::success('Grade added successfully');
            
            $this->jsonResponse([
                'success' => true,
                'data' => ['id' => $gradeId],
                'message' => 'Grade added successfully',
                'timestamp' => time()
            ], 201);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    public function updateGrade($id)
    {
        try {
            $this->requireAuth();
            $this->requireMethod('PUT');
            
            if (!$id || !is_numeric($id)) {
                throw new Exception('Invalid grade ID');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            $errors = Security::validateInput($input, [
                'grade_value' => ['numeric' => true],
                'max_score' => ['numeric' => true],
                'grade_type' => [],
                'quarter' => ['numeric' => true],
                'description' => ['max_length' => 255],
                'remarks' => ['max_length' => 1000]
            ]);
            
            if (!empty($errors)) {
                $this->jsonError('Validation failed', $errors, 400);
                return;
            }
            
            // Validate grade_type if provided
            if (isset($input['grade_type']) && !in_array($input['grade_type'], ['ww', 'pt', 'qe'])) {
                throw new Exception('Invalid grade_type. Must be ww, pt, or qe');
            }
            
            // Validate quarter if provided
            if (isset($input['quarter']) && !in_array((int)$input['quarter'], [1, 2, 3, 4])) {
                throw new Exception('Invalid quarter. Must be 1, 2, 3, or 4');
            }
            
            $sanitized = Security::sanitizeArray($input, [
                'grade_value' => 'float',
                'max_score' => 'float',
                'grade_type' => 'string',
                'quarter' => 'int',
                'description' => 'string',
                'remarks' => 'string'
            ]);
            
            $success = $this->updateGradeData((int)$id, $sanitized);
            
            if (!$success) {
                throw new Exception('Grade not found or update failed');
            }
            
            ActivityLogger::logGradeAction('updated_grade_api', (int)$id, $sanitized);
            Notification::success('Grade updated successfully');
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Grade updated successfully',
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    public function deleteGrade($id)
    {
        try {
            $this->requireAuth();
            $this->requireMethod('DELETE');
            
            if (!$id || !is_numeric($id)) {
                throw new Exception('Invalid grade ID');
            }
            
            $success = $this->deleteGradeData((int)$id);
            
            if (!$success) {
                throw new Exception('Grade not found or delete failed');
            }
            
            ActivityLogger::logGradeAction('deleted_grade_api', (int)$id);
            Notification::success('Grade deleted successfully');
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Grade deleted successfully',
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    public function notifications()
    {
        try {
            $this->requireAuth();
            
            $notifications = Notification::renderJson();
            
            $this->jsonResponse([
                'success' => true,
                'data' => $notifications,
                'count' => count($notifications),
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    public function activityLog()
    {
        try {
            $this->requireAuth();
            
            $limit = min((int)($_GET['limit'] ?? 50), 100);
            $userId = $_GET['user_id'] ?? null;
            $action = $_GET['action'] ?? null;
            
            if ($userId) {
                $logs = ActivityLogger::getLogsByUser((int)$userId, $limit);
            } elseif ($action) {
                $logs = ActivityLogger::getLogsByAction($action, $limit);
            } else {
                $logs = ActivityLogger::getRecentLogs($limit);
            }
            
            ActivityLogger::logUserAction('viewed_activity_log_api', [
                'limit' => $limit,
                'user_id' => $userId,
                'action' => $action
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $logs,
                'count' => count($logs),
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    public function search()
    {
        try {
            $this->requireAuth();
            
            $query = $_GET['q'] ?? '';
            $type = $_GET['type'] ?? 'all';
            
            if (strlen($query) < 2) {
                throw new Exception('Search query must be at least 2 characters');
            }
            
            $results = $this->performSearch($query, $type);
            
            ActivityLogger::logUserAction('search_api', ['query' => $query, 'type' => $type]);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $results,
                'query' => $query,
                'type' => $type,
                'count' => count($results),
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    // Helper methods
    private function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonError('Authentication required', null, 401);
            exit;
        }
    }
    
    private function requireMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            $this->jsonError('Method not allowed', null, 405);
            exit;
        }
    }
    
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    private function jsonError(string $message, $details = null, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => time()
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Data methods (would typically interact with database)
    private function getDashboardStats(): array
    {
        return [
            'total_students' => 150,
            'total_grades' => 1250,
            'average_grade' => 85.5,
            'active_teachers' => 12,
            'recent_submissions' => 25,
            'pending_reviews' => 8
        ];
    }
    
    private function getStudentsData(): array
    {
        return [
            ['id' => 1, 'name' => 'John Doe', 'grade_level' => 10, 'average_grade' => 87.5],
            ['id' => 2, 'name' => 'Jane Smith', 'grade_level' => 11, 'average_grade' => 92.1],
            ['id' => 3, 'name' => 'Bob Johnson', 'grade_level' => 9, 'average_grade' => 78.9]
        ];
    }
    
    private function getStudentData(int $id): ?array
    {
        $students = $this->getStudentsData();
        return array_filter($students, fn($s) => $s['id'] === $id)[0] ?? null;
    }
    
    private function getGradesData(array $filters): array
    {
        return [
            ['id' => 1, 'student_id' => 1, 'subject' => 'Math', 'grade' => 85, 'date' => '2024-01-15'],
            ['id' => 2, 'student_id' => 1, 'subject' => 'Science', 'grade' => 90, 'date' => '2024-01-16'],
            ['id' => 3, 'student_id' => 2, 'subject' => 'English', 'grade' => 95, 'date' => '2024-01-17']
        ];
    }
    
    private function saveGrade(array $data): int
    {
        $gradeModel = new \Models\GradeModel();
        
        // Validate required fields
        $required = ['student_id', 'subject_id', 'grade_type', 'quarter', 'grade_value'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Get section_id from student if not provided
        if (!isset($data['section_id'])) {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            $stmt = $pdo->prepare("SELECT section_id FROM students WHERE id = ?");
            $stmt->execute([$data['student_id']]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$student || !$student['section_id']) {
                throw new Exception("Student section not found");
            }
            $data['section_id'] = $student['section_id'];
        }
        
        // Get teacher_id from subject/class if not provided
        if (!isset($data['teacher_id'])) {
            $user = \Core\Session::get('user');
            if (!$user || !in_array($user['role'], ['teacher', 'adviser'])) {
                throw new Exception("Teacher ID required");
            }
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $teacher = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$teacher) {
                throw new Exception("Teacher profile not found");
            }
            $data['teacher_id'] = $teacher['id'];
        }
        
        return $gradeModel->create($data);
    }
    
    private function updateGradeData(int $id, array $data): bool
    {
        $gradeModel = new \Models\GradeModel();
        return $gradeModel->update($id, $data);
    }
    
    private function deleteGradeData(int $id): bool
    {
        $gradeModel = new \Models\GradeModel();
        return $gradeModel->delete($id);
    }
    
    private function performSearch(string $query, string $type): array
    {
        // Simulate search functionality
        return [
            ['type' => 'student', 'id' => 1, 'name' => 'John Doe', 'relevance' => 0.95],
            ['type' => 'grade', 'id' => 1, 'subject' => 'Mathematics', 'relevance' => 0.87]
        ];
    }
}