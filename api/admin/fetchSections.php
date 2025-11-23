<?php
declare(strict_types=1);

use Models\SectionModel;

require_once __DIR__ . '/../../dbconnect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');

// Session-based access control
session_start();
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$schoolYear = isset($_GET['school_year']) ? (string)$_GET['school_year'] : null;

$model = new SectionModel($pdo);
$sections = $model->listSectionsWithCapacity((int)$user['id'], $schoolYear);

echo json_encode(['success' => true, 'sections' => $sections]);


