<?php
/**
 * Admin API: List Teachers
 * Returns a fresh list of all active teacher/adviser accounts for dropdowns.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
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
            'message' => 'Access denied. Admin privileges required.',
        ]);
        exit;
    }

    $pdo = \Core\Database::connection($config['database']);

    // Always read the latest teachers directly from the database
    $stmt = $pdo->prepare('
        SELECT DISTINCT
            t.id AS teacher_id,
            t.id,
            t.user_id,
            u.name,
            u.email,
            COALESCE(NULLIF(t.department, \'\'), \'General Education\') AS department,
            t.is_adviser
        FROM teachers t
        INNER JOIN users u ON t.user_id = u.id
        WHERE u.status = "active"
          AND u.role IN ("teacher", "adviser")
        ORDER BY u.name
    ');
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'count' => count($teachers),
        'teachers' => $teachers,
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
}


