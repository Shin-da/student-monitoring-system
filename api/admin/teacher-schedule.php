<?php
/**
 * Teacher Schedule API Endpoint
 * Fetches teacher schedules for conflict detection
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Core/Database.php';
require_once __DIR__ . '/../../app/Core/Session.php';

try {
    // Ensure session is started and check if user is admin
    \Core\Session::start($config['session'] ?? []);
    $user = \Core\Session::get('user');
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Admin privileges required.'
        ]);
        exit();
    }

    $teacherId = $_GET['teacher_id'] ?? null;
    
    // Validate teacher ID: treat null/empty as missing, but allow numeric 0 (legacy data) to return empty schedule
    if ($teacherId === null || $teacherId === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Teacher ID is required'
        ]);
        exit();
    }

    // Normalize to integer to avoid accidental string/whitespace issues
    $teacherId = (int)$teacherId;

    $pdo = \Core\Database::connection($config['database']);

    // Get teacher's current schedules (use DISTINCT to prevent duplicates from JOINs)
    $stmt = $pdo->prepare('
        SELECT DISTINCT
            ts.id,
            ts.day_of_week,
            ts.start_time,
            ts.end_time,
            ts.class_id,
            sec.name as section_name,
            sub.name as subject_name,
            c.schedule as class_schedule
        FROM teacher_schedules ts
        LEFT JOIN classes c ON ts.class_id = c.id
        LEFT JOIN sections sec ON c.section_id = sec.id
        LEFT JOIN subjects sub ON c.subject_id = sub.id
        WHERE ts.teacher_id = ?
        ORDER BY ts.day_of_week, ts.start_time
    ');
    
    $stmt->execute([$teacherId]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response with AM/PM format
    $formattedSchedules = [];
    foreach ($schedules as $schedule) {
        $formattedSchedules[] = [
            'id' => $schedule['id'],
            'day' => $schedule['day_of_week'],
            'start' => $schedule['start_time'],
            'end' => $schedule['end_time'],
            'start_ampm' => date('g:i A', strtotime($schedule['start_time'])),
            'end_ampm' => date('g:i A', strtotime($schedule['end_time'])),
            'class_id' => $schedule['class_id'],
            'section_name' => $schedule['section_name'],
            'subject_name' => $schedule['subject_name'],
            'class_schedule' => $schedule['class_schedule']
        ];
    }

    echo json_encode([
        'success' => true,
        'teacher_id' => $teacherId,
        'schedules' => $formattedSchedules
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
