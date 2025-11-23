<?php
/**
 * Schedule Conflict Detection API
 * Checks for schedule conflicts when assigning teachers to classes
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

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Core/Database.php';
require_once __DIR__ . '/../../app/Core/Session.php';

use Core\Database;
use Core\Session;

try {
    // Check if user is admin
    $user = Session::get('user');
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Admin privileges required.'
        ]);
        exit();
    }

    $teacherId = $_POST['teacher_id'] ?? $_GET['teacher_id'] ?? null;
    $days = $_POST['days'] ?? $_GET['days'] ?? null;
    $startTime = $_POST['start_time'] ?? $_GET['start_time'] ?? null;
    $endTime = $_POST['end_time'] ?? $_GET['end_time'] ?? null;
    $excludeClassId = $_POST['exclude_class_id'] ?? $_GET['exclude_class_id'] ?? null;
    
    if (!$teacherId || !$days || !$startTime || !$endTime) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters: teacher_id, days, start_time, end_time'
        ]);
        exit();
    }

    $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
    $pdo = Database::connection($config['database']);

    // Parse days (can be comma-separated or array)
    if (is_string($days)) {
        $daysArray = array_map('trim', explode(',', $days));
    } else {
        $daysArray = $days;
    }

    // Validate day names
    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $daysArray = array_intersect($daysArray, $validDays);
    
    if (empty($daysArray)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid day names. Must be: Monday, Tuesday, Wednesday, Thursday, Friday, Saturday'
        ]);
        exit();
    }

    // Check for conflicts
    $placeholders = str_repeat('?,', count($daysArray) - 1) . '?';
    $params = array_merge([$teacherId], $daysArray, [$endTime, $startTime]);
    
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
        AND ts.day_of_week IN ($placeholders)
        AND (ts.start_time < ? AND ts.end_time > ?)
        $excludeClause
        ORDER BY ts.day_of_week, ts.start_time
    ");
    
    $stmt->execute($params);
    $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format conflicts with AM/PM format
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

    echo json_encode([
        'success' => true,
        'has_conflict' => $hasConflict,
        'conflicts' => $formattedConflicts,
        'conflict_count' => count($conflicts),
        'requested_schedule' => [
            'teacher_id' => $teacherId,
            'days' => $daysArray,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
