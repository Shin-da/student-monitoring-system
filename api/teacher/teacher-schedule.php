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
    if (!$user || ($user['role'] ?? '') !== 'teacher') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }

    $pdo = \Core\Database::connection($config['database']);

    // Resolve teacher id
    $stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$teacher) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Teacher profile not found']); exit; }
    $teacherId = (int)$teacher['id'];

    // Fetch teacher schedules with class/section/subject context
    $stmt = $pdo->prepare("\n        SELECT \n            ts.id, ts.day_of_week, ts.start_time, ts.end_time,\n            c.id as class_id, sec.name as section_name, sub.name as subject_name, c.schedule as class_schedule\n        FROM teacher_schedules ts\n        LEFT JOIN classes c ON ts.class_id = c.id\n        LEFT JOIN sections sec ON c.section_id = sec.id\n        LEFT JOIN subjects sub ON c.subject_id = sub.id\n        WHERE ts.teacher_id = ?\n        ORDER BY FIELD(ts.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), ts.start_time\n    ");
    $stmt->execute([$teacherId]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $formatted = array_map(function($row){
        return [
            'id' => (int)$row['id'],
            'day' => $row['day_of_week'],
            'start' => $row['start_time'],
            'end' => $row['end_time'],
            'start_ampm' => date('g:i A', strtotime($row['start_time'])),
            'end_ampm' => date('g:i A', strtotime($row['end_time'])),
            'class_id' => $row['class_id'],
            'section_name' => $row['section_name'],
            'subject_name' => $row['subject_name'],
            'class_schedule' => $row['class_schedule']
        ];
    }, $schedules);

    echo json_encode(['success' => true, 'teacher_id' => $teacherId, 'schedules' => $formatted]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}


