<?php
declare(strict_types=1);

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

// Basic validation
$firstName = trim((string)($_POST['first_name'] ?? ''));
$lastName = trim((string)($_POST['last_name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$gradeLevel = (int)($_POST['grade_level'] ?? 0);
$sectionId = (int)($_POST['section_id'] ?? 0);
$schoolYear = (string)($_POST['school_year'] ?? '2025-2026');
$lrn = trim((string)($_POST['lrn'] ?? ''));

if ($firstName === '' || $lastName === '' || $email === '' || strlen($password) < 8 || $gradeLevel < 1 || !$sectionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$pdo->beginTransaction();
try {
    // Check email uniqueness
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Email already exists');
    }

    // Check section capacity
    $capStmt = $pdo->prepare('
        SELECT s.max_students, COUNT(st.id) as enrolled
        FROM sections s
        LEFT JOIN students st ON st.section_id = s.id
        WHERE s.id = ? AND s.is_active = 1
        GROUP BY s.id, s.max_students
    ');
    $capStmt->execute([$sectionId]);
    $cap = $capStmt->fetch(PDO::FETCH_ASSOC);
    if (!$cap) {
        throw new RuntimeException('Section not found or inactive');
    }
    if ((int)$cap['enrolled'] >= (int)$cap['max_students']) {
        throw new RuntimeException('Section is full');
    }

    $fullName = trim($firstName . ' ' . ($_POST['middle_name'] ?? '') . ' ' . $lastName);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Create user
    $stmt = $pdo->prepare('
        INSERT INTO users (role, email, password_hash, name, status, approved_by, approved_at)
        VALUES ("student", ?, ?, ?, "active", ?, NOW())
    ');
    $stmt->execute([$email, $hash, $fullName, (int)$user['id']]);
    $userId = (int)$pdo->lastInsertId();

    // Generate LRN if not set
    if ($lrn === '') {
        $lrn = 'LRN' . str_pad((string)$userId, 6, '0', STR_PAD_LEFT);
    }

    // Create student
    $stmt = $pdo->prepare('
        INSERT INTO students (user_id, lrn, first_name, last_name, middle_name, grade_level, section_id, school_year, enrollment_status, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, "enrolled", "active")
    ');
    $stmt->execute([
        $userId,
        $lrn,
        $firstName,
        $lastName,
        $_POST['middle_name'] ?? null,
        $gradeLevel,
        $sectionId,
        $schoolYear,
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'user_id' => $userId]);
} catch (\Throwable $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


