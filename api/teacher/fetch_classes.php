<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
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

	// Resolve teacher id from users table
	$stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? LIMIT 1');
	$stmt->execute([$user['id']]);
	$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$teacher) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Teacher profile not found']); exit; }
	$teacherId = (int)$teacher['id'];

	// Fetch real classes assigned to this teacher
	$stmt = $pdo->prepare("\n\t\tSELECT \n\t\t\tc.id as class_id, c.schedule, c.room as class_room, c.created_at,\n\t\t\tsec.id as section_id, sec.name as section_name, sec.grade_level,\n\t\t\tsub.id as subject_id, sub.name as subject_name,\n\t\t\tCOUNT(DISTINCT sc.student_id) as student_count\n\t\tFROM classes c\n\t\tJOIN sections sec ON c.section_id = sec.id\n\t\tJOIN subjects sub ON c.subject_id = sub.id\n\t\tLEFT JOIN student_classes sc ON sc.class_id = c.id AND sc.status = 'enrolled'\n\t\tWHERE c.teacher_id = ? AND c.is_active = 1\n\t\tGROUP BY c.id, c.schedule, c.room, c.created_at, sec.id, sec.name, sec.grade_level, sub.id, sub.name\n\t\tORDER BY sec.grade_level, sec.name, sub.name\n\t");
	$stmt->execute([$teacherId]);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

	$classes = array_map(function($r) {
		return [
			'id' => (int)$r['class_id'],
			'class_name' => $r['section_name'],
			'subject' => $r['subject_name'],
			'grade_level' => (string)$r['grade_level'],
			'section' => $r['section_name'],
			'room' => $r['class_room'] ?: 'TBD',
			'description' => '',
			'date_created' => $r['created_at'],
			'display_name' => $r['section_name'] . ' (' . $r['subject_name'] . ')',
			'student_count' => (int)$r['student_count'],
			'attendance_rate' => 0
		];
	}, $rows);

	echo json_encode(['success' => true, 'data' => $classes, 'count' => count($classes)]);
} catch (\Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Failed to fetch classes', 'error' => $e->getMessage()]);
}
