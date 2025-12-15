<?php
/**
 * Check All Teachers Schedule API
 * Returns all occupied time slots across ALL teachers for a given day
 * Used to prevent selecting schedules already taken by any teacher
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

try {
    $config = require __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../app/Core/Database.php';
    require_once __DIR__ . '/../../app/Core/Session.php';

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

    $day = $_GET['day'] ?? $_POST['day'] ?? null;
    
    if (!$day) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Day is required'
        ]);
        exit();
    }

    $pdo = \Core\Database::connection($config['database']);

    // Get ALL occupied schedules for the given day across ALL teachers
    $stmt = $pdo->prepare('
        SELECT DISTINCT
            ts.teacher_id,
            ts.start_time,
            ts.end_time,
            u.name as teacher_name,
            sec.name as section_name,
            sub.name as subject_name
        FROM teacher_schedules ts
        LEFT JOIN teachers t ON ts.teacher_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN classes c ON ts.class_id = c.id
        LEFT JOIN sections sec ON c.section_id = sec.id
        LEFT JOIN subjects sub ON c.subject_id = sub.id
        WHERE ts.day_of_week = ?
        ORDER BY ts.start_time
    ');
    
    $stmt->execute([$day]);
    $occupiedSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $formattedSchedules = [];
    foreach ($occupiedSchedules as $schedule) {
        $formattedSchedules[] = [
            'teacher_id' => (int)$schedule['teacher_id'],
            'teacher_name' => $schedule['teacher_name'] ?? 'Unknown Teacher',
            'start_time' => $schedule['start_time'],
            'end_time' => $schedule['end_time'],
            'start_ampm' => date('g:i A', strtotime($schedule['start_time'])),
            'end_ampm' => date('g:i A', strtotime($schedule['end_time'])),
            'section_name' => $schedule['section_name'],
            'subject_name' => $schedule['subject_name']
        ];
    }

    echo json_encode([
        'success' => true,
        'day' => $day,
        'occupied_schedules' => $formattedSchedules,
        'count' => count($formattedSchedules)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

