<?php
/**
 * Available Time Slots API Endpoint
 * Returns available time slots for a teacher based on their current schedule
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

    $teacherId = $_GET['teacher_id'] ?? null;
    
    if (!$teacherId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Teacher ID is required'
        ]);
        exit();
    }

    $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
    $pdo = Database::connection($config['database']);

    // Get teacher's current schedules
    $stmt = $pdo->prepare('
        SELECT 
            ts.day_of_week,
            ts.start_time,
            ts.end_time
        FROM teacher_schedules ts
        WHERE ts.teacher_id = ?
        ORDER BY ts.day_of_week, ts.start_time
    ');
    
    $stmt->execute([$teacherId]);
    $occupiedSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate available time slots
    $availableSlots = generateAvailableTimeSlots($occupiedSchedules);

    echo json_encode([
        'success' => true,
        'teacher_id' => $teacherId,
        'available_slots' => $availableSlots,
        'occupied_schedules' => formatOccupiedSchedules($occupiedSchedules)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

function generateAvailableTimeSlots($occupiedSchedules) {
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $timeSlots = generateTimeSlots(); // 7:00 AM to 5:00 PM
    $availableSlots = [];
    
    // Group occupied schedules by day
    $occupiedByDay = [];
    foreach ($occupiedSchedules as $schedule) {
        $day = $schedule['day_of_week'];
        if (!isset($occupiedByDay[$day])) {
            $occupiedByDay[$day] = [];
        }
        $occupiedByDay[$day][] = [
            'start' => $schedule['start_time'],
            'end' => $schedule['end_time']
        ];
    }
    
    // Generate available slots for each day
    foreach ($days as $day) {
        $daySlots = [];
        $occupiedForDay = $occupiedByDay[$day] ?? [];
        
        foreach ($timeSlots as $slot) {
            if (!isTimeSlotOccupied($slot, $occupiedForDay)) {
                $daySlots[] = [
                    'day' => $day,
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'start_ampm' => $slot['start_ampm'],
                    'end_ampm' => $slot['end_ampm'],
                    'display' => $slot['display']
                ];
            }
        }
        
        if (!empty($daySlots)) {
            $availableSlots[$day] = $daySlots;
        }
    }
    
    return $availableSlots;
}

function generateTimeSlots() {
    $slots = [];
    $startHour = 7; // 7:00 AM
    $endHour = 17; // 5:00 PM
    
    for ($hour = $startHour; $hour < $endHour; $hour++) {
        $startTime = sprintf('%02d:00:00', $hour);
        $endTime = sprintf('%02d:00:00', $hour + 1);
        
        $slots[] = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_ampm' => date('g:i A', strtotime($startTime)),
            'end_ampm' => date('g:i A', strtotime($endTime)),
            'display' => date('g:i A', strtotime($startTime)) . ' - ' . date('g:i A', strtotime($endTime))
        ];
    }
    
    return $slots;
}

function isTimeSlotOccupied($slot, $occupiedSchedules) {
    foreach ($occupiedSchedules as $occupied) {
        // Check if there's any overlap
        if ($slot['start_time'] < $occupied['end'] && $slot['end_time'] > $occupied['start']) {
            return true;
        }
    }
    return false;
}

function formatOccupiedSchedules($schedules) {
    $formatted = [];
    foreach ($schedules as $schedule) {
        $formatted[] = [
            'day' => $schedule['day_of_week'],
            'start' => $schedule['start_time'],
            'end' => $schedule['end_time'],
            'start_ampm' => date('g:i A', strtotime($schedule['start_time'])),
            'end_ampm' => date('g:i A', strtotime($schedule['end_time'])),
            'display' => date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time']))
        ];
    }
    return $formatted;
}
