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

$sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : null;
$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;

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
    
    // Get students - simplified approach
    if ($sectionId) {
        // Get students from specific section
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.id, u.name, s.lrn, sec.name AS section_name, s.section_id
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN sections sec ON s.section_id = sec.id
            WHERE s.section_id = ? AND EXISTS (
                SELECT 1 FROM classes c 
                WHERE c.section_id = ? AND c.teacher_id = ? AND c.is_active = 1
            )
            ORDER BY u.name
        ");
        $stmt->execute([$sectionId, $sectionId, $teacherId]);
    } else {
        // Get all students from teacher's sections
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.id, u.name, s.lrn, sec.name AS section_name, s.section_id
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN sections sec ON s.section_id = sec.id
            WHERE EXISTS (
                SELECT 1 FROM classes c 
                WHERE c.section_id = sec.id AND c.teacher_id = ? AND c.is_active = 1
            )
            ORDER BY sec.name, u.name
        ");
        $stmt->execute([$teacherId]);
    }
    
    $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $students,
        'count' => count($students),
        'teacher_id' => $teacherId,
        'section_id' => $sectionId,
        'subject_id' => $subjectId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

