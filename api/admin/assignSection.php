<?php
declare(strict_types=1);

use Models\StudentModel;

require_once __DIR__ . '/../../dbconnect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');

session_start();
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// CSRF check (optional: integrate with existing Helpers\Csrf if available over API)
$studentId = (int)($_POST['student_id'] ?? 0);
$sectionId = (int)($_POST['section_id'] ?? 0);

if (!$studentId || !$sectionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'student_id and section_id are required']);
    exit;
}

// Ensure target section has available slots
$capacityStmt = $pdo->prepare('
    SELECT s.max_students, COUNT(st.id) as enrolled
    FROM sections s
    LEFT JOIN students st ON st.section_id = s.id
    WHERE s.id = ? AND s.is_active = 1
    GROUP BY s.id, s.max_students
');
$capacityStmt->execute([$sectionId]);
$cap = $capacityStmt->fetch(PDO::FETCH_ASSOC);
if (!$cap) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Section not found or inactive']);
    exit;
}
if ((int)$cap['enrolled'] >= (int)$cap['max_students']) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'Section is full']);
    exit;
}

$model = new StudentModel($pdo);
$model->assignStudentToSection($studentId, $sectionId);

echo json_encode(['success' => true, 'message' => 'Student assigned to section']);


