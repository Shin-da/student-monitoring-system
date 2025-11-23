<?php
declare(strict_types=1);

use Models\SectionModel;

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

$sectionId = (int)($_GET['section_id'] ?? 0);
if (!$sectionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'section_id is required']);
    exit;
}

$model = new SectionModel($pdo);
$capacity = $model->getSectionCapacity($sectionId);

if (!$capacity) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Section not found']);
    exit;
}

echo json_encode(['success' => true, 'capacity' => $capacity]);


