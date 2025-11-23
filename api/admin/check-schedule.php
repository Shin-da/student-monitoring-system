<?php
/**
 * Live Schedule Conflict Check API
 * Provides real-time conflict validation for schedule assignments
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

// Using fully qualified class names to avoid namespace import within try block

try {
    // Ensure session is started and check if user is admin
    \Core\Session::start($config['session'] ?? []);
    $user = \Core\Session::get('user');
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Access denied. Admin privileges required.'
        ]);
        exit();
    }

    $teacherId = $_POST['teacher_id'] ?? $_GET['teacher_id'] ?? null;
    $day = $_POST['day'] ?? $_GET['day'] ?? null;
    $startTime = $_POST['start_time'] ?? $_GET['start_time'] ?? null;
    $endTime = $_POST['end_time'] ?? $_GET['end_time'] ?? null;
    $excludeClassId = $_POST['exclude_class_id'] ?? $_GET['exclude_class_id'] ?? null;
    
    if (!$teacherId || !$day || !$startTime || !$endTime) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required parameters: teacher_id, day, start_time, end_time'
        ]);
        exit();
    }

    $pdo = \Core\Database::connection($config['database']);

    // Convert AM/PM to 24-hour format if needed
    $startTime24 = convertTo24Hour($startTime);
    $endTime24 = convertTo24Hour($endTime);

    // Validate time format
    if (!$startTime24 || !$endTime24) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid time format. Please use format like "8:00 AM" or "2:30 PM"'
        ]);
        exit();
    }

    // Check for conflicts
    $params = [$teacherId, $day, $endTime24, $startTime24];
    $excludeClause = '';
    
    if ($excludeClassId) {
        $excludeClause = ' AND (ts.class_id IS NULL OR ts.class_id != ?)';
        $params[] = $excludeClassId;
    }

    $stmt = $pdo->prepare("
        SELECT 
            ts.id,
            ts.day_of_week,
            ts.start_time,
            ts.end_time,
            ts.class_id,
            c.id as class_id,
            sec.name as section_name,
            sub.name as subject_name,
            c.schedule as class_schedule
        FROM teacher_schedules ts
        LEFT JOIN classes c ON ts.class_id = c.id
        LEFT JOIN sections sec ON c.section_id = sec.id
        LEFT JOIN subjects sub ON c.subject_id = sub.id
        WHERE ts.teacher_id = ? 
        AND ts.day_of_week = ?
        AND (ts.start_time < ? AND ts.end_time > ?)
        $excludeClause
        ORDER BY ts.start_time
    ");
    
    $stmt->execute($params);
    $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format conflicts with AM/PM
    $formattedConflicts = [];
    foreach ($conflicts as $conflict) {
        $formattedConflicts[] = [
            'id' => $conflict['id'],
            'day' => $conflict['day_of_week'],
            'start' => $conflict['start_time'],
            'end' => $conflict['end_time'],
            'start_ampm' => date('g:i A', strtotime($conflict['start_time'])),
            'end_ampm' => date('g:i A', strtotime($conflict['end_time'])),
            'class_id' => $conflict['class_id'],
            'section_name' => $conflict['section_name'],
            'subject_name' => $conflict['subject_name'],
            'class_schedule' => $conflict['class_schedule']
        ];
    }

    $hasConflict = !empty($conflicts);

    if ($hasConflict) {
        echo json_encode([
            'status' => 'conflict',
            'message' => 'Schedule conflict detected',
            'conflicts' => $formattedConflicts,
            'conflict_count' => count($conflicts),
            'requested_schedule' => [
                'teacher_id' => $teacherId,
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'start_time_24' => $startTime24,
                'end_time_24' => $endTime24
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'available',
            'message' => 'Time slot is available',
            'conflicts' => [],
            'conflict_count' => 0,
            'requested_schedule' => [
                'teacher_id' => $teacherId,
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'start_time_24' => $startTime24,
                'end_time_24' => $endTime24
            ]
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

function convertTo24Hour($timeString) {
    // Handle various time formats
    $timeString = trim($timeString);
    
    // If already in 24-hour format (HH:MM:SS or HH:MM)
    if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $timeString)) {
        return $timeString;
    }
    
    // Try to parse AM/PM format
    $timestamp = strtotime($timeString);
    if ($timestamp === false) {
        return null;
    }
    
    return date('H:i:s', $timestamp);
}
