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

$config = require BASE_PATH . '/config/config.php';
Core\Session::start($config['session'] ?? []);

header('Content-Type: application/json');

// Check authentication
$user = Core\Session::get('user');
if (!$user || !in_array($user['role'] ?? '', ['teacher', 'adviser'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
$sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : null;

try {
    $pdo = \Core\Database::connection($config['database']);
    
    // Get teacher ID
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $teacher = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        throw new Exception('Teacher profile not found');
    }
    
    $teacherId = (int)$teacher['id'];
    
    // Get subjects based on student or section
    // Priority: Use section_id if provided, otherwise get from student
    if (!$sectionId && $studentId) {
        // Get student's section first
        $stmt = $pdo->prepare("SELECT section_id FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$student || !$student['section_id']) {
            throw new Exception('Student section not found');
        }
        
        $sectionId = (int)$student['section_id'];
    }
    
    if (!$sectionId) {
        throw new Exception('Section ID is required. Please ensure the student is assigned to a section.');
    }
    
    // Get subjects that teacher teaches for this specific section
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            sub.id,
            sub.name,
            sub.code,
            c.section_id,
            sec.name AS section_name
        FROM subjects sub
        JOIN classes c ON sub.id = c.subject_id
        JOIN sections sec ON c.section_id = sec.id
        WHERE c.section_id = ? AND c.teacher_id = ? AND c.is_active = 1
        ORDER BY sub.name
    ");
    $stmt->execute([$sectionId, $teacherId]);
    $subjects = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $subjects,
        'count' => count($subjects),
        'section_id' => $sectionId,
        'student_id' => $studentId,
        'debug' => [
            'teacher_id' => $teacherId,
            'section_id_used' => $sectionId,
            'subjects_found' => count($subjects)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

