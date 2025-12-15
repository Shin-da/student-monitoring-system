<?php
/**
 * Fixed Schedule Conflict Check API
 * Provides real-time conflict validation for schedule assignments
 */

// Always return JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handling to ensure JSON response
function returnJson($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data);
    exit();
}

try {
    // Include required files
    $config = require __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../app/Core/Database.php';
    require_once __DIR__ . '/../../app/Core/Session.php';

    // Ensure session is started and check if user is admin
    \Core\Session::start($config['session'] ?? []);
    $user = \Core\Session::get('user');
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        returnJson([
            'status' => 'error',
            'message' => 'Access denied. Admin privileges required.'
        ], 403);
    }

    // Parse JSON body if provided
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);

    // Get parameters (JSON takes priority, fallback to POST/GET)
    $teacherId = null;
    $day = null;
    $startTime = null;
    $endTime = null;

    if (is_array($jsonData)) {
        $teacherId = $jsonData['teacherId'] ?? $jsonData['teacher_id'] ?? null;
        $day = $jsonData['day'] ?? null;
        $startTime = $jsonData['startTime'] ?? $jsonData['start_time'] ?? null;
        $endTime = $jsonData['endTime'] ?? $jsonData['end_time'] ?? null;
    }

    if ($teacherId === null) {
        $teacherId = $_POST['teacher_id'] ?? $_GET['teacher_id'] ?? null;
    }
    if ($day === null) {
        $day = $_POST['day'] ?? $_GET['day'] ?? null;
    }
    if ($startTime === null) {
        $startTime = $_POST['start_time'] ?? $_GET['start_time'] ?? null;
    }
    if ($endTime === null) {
        $endTime = $_POST['end_time'] ?? $_GET['end_time'] ?? null;
    }
    
    // Validate required parameters (treat numeric 0 as valid, only null/empty as missing)
    $missing = [];
    if ($teacherId === null || $teacherId === '') {
        $missing[] = 'teacher_id';
    }
    if ($day === null || $day === '') {
        $missing[] = 'day';
    }
    if ($startTime === null || $startTime === '') {
        $missing[] = 'start_time';
    }
    if ($endTime === null || $endTime === '') {
        $missing[] = 'end_time';
    }

    if (!empty($missing)) {
        returnJson([
            'status' => 'error',
            'message' => 'Missing required parameters: ' . implode(', ', $missing),
            'received' => [
                'teacherId' => $teacherId,
                'day' => $day,
                'startTime' => $startTime,
                'endTime' => $endTime,
            ],
        ], 400);
    }

    // Get database connection
    $pdo = \Core\Database::connection($config['database']);

    // Convert AM/PM to 24-hour format if needed
    $startTime24 = convertTo24Hour($startTime);
    $endTime24 = convertTo24Hour($endTime);

    // Validate time format
    if (!$startTime24 || !$endTime24) {
        returnJson([
            'status' => 'error',
            'message' => 'Invalid time format. Please use format like "8:00 AM" or "2:30 PM"'
        ], 400);
    }

    // Check for conflicts (exact duplicates and overlapping times)
    $stmt = $pdo->prepare("
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
        AND ts.day_of_week = ?
        AND (
            -- Exact duplicate: same day, same start and end time
            (ts.start_time = ? AND ts.end_time = ?)
            OR
            -- Overlapping: new start < existing end AND new end > existing start
            (ts.start_time < ? AND ts.end_time > ?)
        )
        ORDER BY ts.start_time
    ");
    
    // Parameters: teacherId, day, startTime24 (for exact), endTime24 (for exact), endTime24 (for overlap), startTime24 (for overlap)
    $stmt->execute([$teacherId, $day, $startTime24, $endTime24, $endTime24, $startTime24]);
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
        returnJson([
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
        returnJson([
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
    returnJson([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
}

function convertTo24Hour($timeString) {
    // Handle various time formats
    $timeString = trim((string)$timeString);

    // If already in 24-hour format (HH:MM:SS or HH:MM), normalize to HH:MM:SS
    if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $timeString)) {
        // If it's HH:MM, append seconds for consistency
        if (strlen($timeString) === 5) {
            return $timeString . ':00';
        }
        return $timeString;
    }

    // Try to parse AM/PM or other common formats
    $timestamp = strtotime($timeString);
    if ($timestamp === false) {
        return null;
    }

    return date('H:i:s', $timestamp);
}
