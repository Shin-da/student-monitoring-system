<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');

// Simple autoloader
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Core' => APP_PATH . '/Core',
        'Controllers' => APP_PATH . '/Controllers',
        'Models' => APP_PATH . '/Models',
        'Helpers' => APP_PATH . '/Helpers',
    ];

    foreach ($prefixes as $ns => $dir) {
        $nsPrefix = $ns . '\\';
        if (str_starts_with($class, $nsPrefix)) {
            $relative = substr($class, strlen($nsPrefix));
            $path = $dir . '/' . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($path)) {
                require_once $path;
            }
            return;
        }
    }
});

require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Core/Session.php';
require_once BASE_PATH . '/app/Helpers/Csrf.php';

$config = require BASE_PATH . '/config/config.php';
Core\Session::start($config['session'] ?? []);

use Core\Session;
use Models\GradeModel;
use Helpers\Security;
use Helpers\Csrf;

header('Content-Type: application/json');

// Check authentication
$user = Session::get('user');
if (!$user || !in_array($user['role'] ?? '', ['teacher', 'adviser'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    // Create new grade
    try {
        // Check CSRF token for form submissions
        if (isset($_POST['csrf_token'])) {
            if (!Csrf::check($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            $input = $_POST;
        } else {
            // JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
        }
        
        // Validate required fields
        $required = ['student_id', 'subject_id', 'grade_type', 'quarter', 'grade_value'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || $input[$field] === '') {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Validate grade_type
        if (!in_array($input['grade_type'], ['ww', 'pt', 'qe'])) {
            throw new Exception('Invalid grade_type. Must be ww, pt, or qe');
        }
        
        // Validate quarter
        if (!in_array((int)$input['quarter'], [1, 2, 3, 4])) {
            throw new Exception('Invalid quarter. Must be 1, 2, 3, or 4');
        }
        
        // Get teacher ID
        $config = require BASE_PATH . '/config/config.php';
        $pdo = \Core\Database::connection($config['database']);
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $teacher = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$teacher) {
            throw new Exception('Teacher profile not found');
        }
        
        // Get student section if not provided
        if (!isset($input['section_id'])) {
            $stmt = $pdo->prepare("SELECT section_id FROM students WHERE id = ?");
            $stmt->execute([$input['student_id']]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$student || !$student['section_id']) {
                throw new Exception('Student section not found. Please ensure the student is assigned to a section.');
            }
            $input['section_id'] = $student['section_id'];
        }
        
        // Verify teacher has access to this section/subject combination
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM classes 
            WHERE section_id = ? AND subject_id = ? AND teacher_id = ? AND is_active = 1
        ");
        $stmt->execute([$input['section_id'], $input['subject_id'], (int)$teacher['id']]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new Exception('You do not have permission to grade this student for this subject.');
        }
        
        // Prepare grade data
        $gradeData = [
            'student_id' => (int)$input['student_id'],
            'section_id' => (int)$input['section_id'],
            'subject_id' => (int)$input['subject_id'],
            'teacher_id' => (int)$teacher['id'],
            'grade_type' => $input['grade_type'],
            'quarter' => (int)$input['quarter'],
            'grade_value' => (float)$input['grade_value'],
            'max_score' => isset($input['max_score']) ? (float)$input['max_score'] : 100.00,
            'description' => $input['description'] ?? null,
            'remarks' => $input['remarks'] ?? null,
            'academic_year' => $input['academic_year'] ?? null,
        ];
        
        $gradeModel = new GradeModel();
        $gradeId = $gradeModel->create($gradeData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Grade submitted successfully',
            'data' => ['id' => $gradeId]
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($method === 'PUT') {
    // Update grade
    $id = $_GET['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid grade ID']);
        exit;
    }
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        $gradeModel = new GradeModel();
        $success = $gradeModel->update((int)$id, $input);
        
        if (!$success) {
            throw new Exception('Grade not found or update failed');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Grade updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($method === 'DELETE') {
    // Delete grade
    $id = $_GET['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid grade ID']);
        exit;
    }
    
    try {
        $gradeModel = new GradeModel();
        $success = $gradeModel->delete((int)$id);
        
        if (!$success) {
            throw new Exception('Grade not found or delete failed');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Grade deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

