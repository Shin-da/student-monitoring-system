<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../app/Core/Database.php';
require_once __DIR__ . '/../../app/Core/Session.php';
$config = require __DIR__ . '/../../config/config.php';

try {
    \Core\Session::start($config['session'] ?? []);
    $user = \Core\Session::get('user');
    if (!$user || ($user['role'] ?? '') !== 'student') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }

    $pdo = \Core\Database::connection($config['database']);

    // Resolve student id and section
    $stmt = $pdo->prepare('SELECT id, section_id FROM students WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Student profile not found']); exit; }

    // If no section assigned, return empty classes
    if (empty($student['section_id'])) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    // Find classes the student is enrolled in via student_classes
    $stmt = $pdo->prepare("\n        SELECT \n            c.id as class_id, c.schedule, c.room as class_room,\n            sec.id as section_id, sec.name as section_name, sec.grade_level,\n            sub.id as subject_id, sub.name as subject_name, sub.code as subject_code,\n            u.name as teacher_name\n        FROM student_classes sc\n        JOIN classes c ON sc.class_id = c.id\n        JOIN sections sec ON c.section_id = sec.id\n        JOIN subjects sub ON c.subject_id = sub.id\n        JOIN teachers t ON c.teacher_id = t.id\n        JOIN users u ON t.user_id = u.id\n        WHERE sc.student_id = ? AND sc.status = 'enrolled'\n        ORDER BY sec.grade_level, sec.name, sub.name\n    ");
    $stmt->execute([(int)$student['id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $classes = array_map(function($r){
        return [
            'class_id' => (int)$r['class_id'],
            'section_id' => (int)$r['section_id'],
            'section_name' => $r['section_name'],
            'grade_level' => (int)$r['grade_level'],
            'subject_id' => (int)$r['subject_id'],
            'subject_name' => $r['subject_name'],
            'subject_code' => $r['subject_code'],
            'teacher_name' => $r['teacher_name'],
            'schedule' => $r['schedule'],
            'room' => $r['class_room'],
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $classes]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}


