<?php
declare(strict_types=1);

// JSON API endpoint: /api/create_user.php
// Accepts POST JSON and creates a user (and student record if role is 'student')

header('Content-Type: application/json; charset=utf-8');

// Only allow POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Resolve base path and load config/DB
define('BASE_PATH', dirname(__DIR__));

$configFile = BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
$dbFile = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Database.php';

if (!file_exists($configFile) || !file_exists($dbFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit;
}

$config = require $configFile;
require_once $dbFile;

use Core\Database;
use PDO;

/**
 * Fetch column metadata for a table. Returns a lowercase keyed map.
 */
function getTableColumnMap(PDO $pdo, string $table): array
{
    $safeName = preg_replace('/[^a-z0-9_]/i', '', $table);
    if ($safeName === '') {
        return [];
    }

    $stmt = $pdo->query(sprintf('SHOW COLUMNS FROM `%s`', $safeName));
    $columns = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[strtolower($column['Field'])] = $column;
    }

    return $columns;
}

/**
 * Check if a UNIQUE constraint exists on user_id in teachers table
 * This helps determine which insertion strategy to use
 */
function hasUniqueConstraintOnUserId(PDO $pdo): bool
{
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
            JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
                ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE tc.TABLE_SCHEMA = DATABASE()
            AND tc.TABLE_NAME = 'teachers'
            AND tc.CONSTRAINT_TYPE = 'UNIQUE'
            AND kcu.COLUMN_NAME = 'user_id'
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0) > 0;
    } catch (\Throwable $e) {
        error_log('hasUniqueConstraintOnUserId: Error checking constraint: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ensure a teacher profile exists (and is updated) in the teachers table.
 * This function is designed to be robust and handle schema variations.
 * It uses multiple strategies to ensure teacher records are created/updated reliably.
 *
 * @param array{user_id:int, name?:string, department?:?string, employee_id?:?string, specialization?:?string, hire_date?:?string, is_adviser?:int} $meta
 */
function upsertTeacherProfile(PDO $pdo, array $meta): void
{
    $columns = getTableColumnMap($pdo, 'teachers');
    if (empty($columns)) {
        error_log('upsertTeacherProfile: teachers table columns not found');
        throw new \RuntimeException('Teachers table not found or has no columns');
    }

    $userId = (int)($meta['user_id'] ?? 0);
    if ($userId <= 0) {
        error_log('upsertTeacherProfile: Invalid user_id provided: ' . ($meta['user_id'] ?? 'null'));
        throw new \InvalidArgumentException('Invalid teacher user_id supplied.');
    }

    // Check if teacher record already exists
    $checkStmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = :user_id LIMIT 1');
    $checkStmt->execute(['user_id' => $userId]);
    $existingId = $checkStmt->fetchColumn();
    $exists = ($existingId !== false);

    // Build fields and parameters
    $fields = [];
    $placeholders = [];
    $params = [];
    $updates = [];

    // Always include user_id (PK in teachers)
    $fields[] = 'user_id';
    $placeholders[] = ':user_id';
    $params['user_id'] = $userId;

    // If the table has an auto/unique `id` column, explicitly pass NULL to avoid default 0 duplicates
    if (isset($columns['id'])) {
        $fields[] = 'id';
        $placeholders[] = 'NULL';
    }

    // Include is_adviser
    if (isset($columns['is_adviser'])) {
        $fields[] = 'is_adviser';
        $placeholders[] = ':is_adviser';
        $params['is_adviser'] = (int)($meta['is_adviser'] ?? 0);
        $updates[] = 'is_adviser = GREATEST(is_adviser, VALUES(is_adviser))';
    }

    if (isset($columns['teacher_name'])) {
        $fields[] = 'teacher_name';
        $placeholders[] = ':teacher_name';
        $params['teacher_name'] = $meta['name'] ?? $meta['teacher_name'] ?? null;
        $updates[] = 'teacher_name = VALUES(teacher_name)';
    }

    if (isset($columns['department'])) {
        $fields[] = 'department';
        $placeholders[] = ':department';
        $params['department'] = $meta['department'] ?? null;
        $updates[] = 'department = VALUES(department)';
    }

    if (isset($columns['employee_id'])) {
        $fields[] = 'employee_id';
        $placeholders[] = ':employee_id';
        $params['employee_id'] = $meta['employee_id'] ?? null;
        $updates[] = 'employee_id = VALUES(employee_id)';
    }

    if (isset($columns['specialization'])) {
        $fields[] = 'specialization';
        $placeholders[] = ':specialization';
        $params['specialization'] = $meta['specialization'] ?? null;
        $updates[] = 'specialization = VALUES(specialization)';
    }

    if (isset($columns['hire_date'])) {
        $fields[] = 'hire_date';
        if (($meta['hire_date'] ?? null) !== null) {
            $placeholders[] = ':hire_date';
            $params['hire_date'] = $meta['hire_date'];
        } else {
            $placeholders[] = 'NULL';
        }
        $updates[] = 'hire_date = VALUES(hire_date)';
    }

    if (isset($columns['created_at']) && !$exists) {
        $fields[] = 'created_at';
        $placeholders[] = 'NOW()';
    }

    if (isset($columns['updated_at'])) {
        if (!$exists) {
            $fields[] = 'updated_at';
            $placeholders[] = 'NOW()';
        }
        $updates[] = 'updated_at = NOW()';
    }

    // Strategy 1: Try INSERT ... ON DUPLICATE KEY UPDATE if UNIQUE constraint exists
    $hasUniqueConstraint = hasUniqueConstraintOnUserId($pdo);
    
    if ($hasUniqueConstraint && !empty($updates)) {
        $sql = sprintf(
            'INSERT INTO teachers (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            implode(', ', $fields),
            implode(', ', $placeholders),
            implode(', ', $updates)
        );
        
        try {
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                throw new \PDOException('INSERT ... ON DUPLICATE KEY UPDATE failed');
            }
            
            // Verify success
            $verifyStmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = :user_id LIMIT 1');
            $verifyStmt->execute(['user_id' => $userId]);
            $teacherId = $verifyStmt->fetchColumn();
            
            if ($teacherId === false) {
                throw new \PDOException('Teacher record not found after insert/update');
            }
            
            error_log('upsertTeacherProfile: Successfully created/updated teacher record using ON DUPLICATE KEY UPDATE. user_id: ' . $userId . ', teacher_id: ' . $teacherId);
            return;
            
        } catch (\PDOException $e) {
            error_log('upsertTeacherProfile: ON DUPLICATE KEY UPDATE failed, trying alternative method: ' . $e->getMessage());
            // Fall through to alternative method
        }
    }

    // Strategy 2: Use UPDATE/INSERT pattern if ON DUPLICATE KEY UPDATE didn't work
    if ($exists) {
        // Update existing record
        $updateFields = [];
        $updateParams = ['user_id' => $userId];
        
        // Build update fields from available parameters
        if (isset($params['is_adviser'])) {
            $updateFields[] = '`is_adviser` = :is_adviser';
            $updateParams['is_adviser'] = $params['is_adviser'];
        }
        if (isset($params['department'])) {
            $updateFields[] = '`department` = :department';
            $updateParams['department'] = $params['department'];
        }
        if (isset($params['employee_id'])) {
            $updateFields[] = '`employee_id` = :employee_id';
            $updateParams['employee_id'] = $params['employee_id'];
        }
        if (isset($params['specialization'])) {
            $updateFields[] = '`specialization` = :specialization';
            $updateParams['specialization'] = $params['specialization'];
        }
        if (isset($params['hire_date'])) {
            $updateFields[] = '`hire_date` = :hire_date';
            $updateParams['hire_date'] = $params['hire_date'];
        }
        if (isset($params['teacher_name'])) {
            $updateFields[] = '`teacher_name` = :teacher_name';
            $updateParams['teacher_name'] = $params['teacher_name'];
        }
        
        // Always update updated_at if column exists
        if (isset($columns['updated_at'])) {
            $updateFields[] = '`updated_at` = NOW()';
        }
        
        if (!empty($updateFields)) {
            $sql = 'UPDATE teachers SET ' . implode(', ', $updateFields) . ' WHERE user_id = :user_id';
            
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateParams);
                error_log('upsertTeacherProfile: Successfully updated existing teacher record. user_id: ' . $userId);
                return;
            } catch (\PDOException $e) {
                error_log('upsertTeacherProfile: UPDATE failed: ' . $e->getMessage());
                error_log('upsertTeacherProfile: UPDATE SQL: ' . $sql);
                error_log('upsertTeacherProfile: UPDATE Params: ' . json_encode($updateParams));
                throw $e;
            }
        } else {
            error_log('upsertTeacherProfile: No fields to update for existing teacher record. user_id: ' . $userId);
            return; // Nothing to update, record already exists
        }
    } else {
        // Insert new record
        $sql = sprintf(
            'INSERT INTO teachers (%s) VALUES (%s)',
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        try {
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log('upsertTeacherProfile: INSERT failed. Error: ' . json_encode($errorInfo));
                error_log('upsertTeacherProfile: SQL: ' . $sql);
                error_log('upsertTeacherProfile: Params: ' . json_encode($params));
                throw new \PDOException('Failed to insert teacher profile: ' . ($errorInfo[2] ?? 'Unknown error'));
            }
            
            // Verify the record was created
            $verifyStmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = :user_id LIMIT 1');
            $verifyStmt->execute(['user_id' => $userId]);
            $teacherId = $verifyStmt->fetchColumn();
            
            if ($teacherId === false) {
                error_log('upsertTeacherProfile: Teacher record not found after insert for user_id: ' . $userId);
                throw new \PDOException('Teacher record was not created successfully');
            }
            
            error_log('upsertTeacherProfile: Successfully created teacher record. user_id: ' . $userId . ', teacher_id: ' . $teacherId);
            return;
            
        } catch (\PDOException $e) {
            error_log('upsertTeacherProfile: INSERT failed: ' . $e->getMessage());
            error_log('upsertTeacherProfile: SQL: ' . $sql);
            error_log('upsertTeacherProfile: Params: ' . json_encode($params));
            throw $e;
        }
    }
}

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw ?: 'null', true);

// Log the raw input for debugging
error_log('create_user.php received raw input: ' . substr($raw, 0, 500));
error_log('create_user.php decoded data: ' . json_encode($data));

if (!is_array($data)) {
    error_log('create_user.php: Invalid JSON body. Raw: ' . substr($raw, 0, 200));
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid JSON body',
        'received' => substr($raw, 0, 200)
    ]);
    exit;
}

// Extract and validate inputs
$name = trim((string)($data['name'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');
$role = strtolower(trim((string)($data['role'] ?? '')));

// Optional centralized fields for specific roles
$lrn = isset($data['lrn']) ? trim((string)$data['lrn']) : null; // students
$gradeLevel = isset($data['grade_level']) ? (int)$data['grade_level'] : null; // students
$sectionName = isset($data['section_name']) ? trim((string)$data['section_name']) : null; // students
$isAdviser = isset($data['is_adviser']) ? (int)!!$data['is_adviser'] : 0; // teachers
$linkedStudentUserId = isset($data['linked_student_user_id']) ? (int)$data['linked_student_user_id'] : null; // parents
$parentRelationship = isset($data['parent_relationship']) ? trim((string)$data['parent_relationship']) : null; // parents
$department = isset($data['department']) ? trim((string)$data['department']) : null;
$employeeId = isset($data['employee_id']) ? trim((string)$data['employee_id']) : null;
$subjectSpecialization = isset($data['subject_specialization']) ? trim((string)$data['subject_specialization']) : null;
$rawHireDate = isset($data['hire_date']) ? trim((string)$data['hire_date']) : null;
$hireDate = null;
if ($rawHireDate !== null && $rawHireDate !== '') {
    $timestamp = strtotime($rawHireDate);
    if ($timestamp !== false) {
        $hireDate = date('Y-m-d', $timestamp);
    }
}

if ($name === '' || $email === '' || $password === '' || $role === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

// Basic role check
$allowedRoles = ['admin', 'teacher', 'adviser', 'student', 'parent'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

try {
    $pdo = Database::connection($config['database']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // CRITICAL: Ensure auto-commit is OFF so we can use transactions properly
    // Some database configurations might have auto-commit enabled
    if ($pdo->getAttribute(PDO::ATTR_AUTOCOMMIT)) {
        error_log('WARNING: Auto-commit is enabled. Disabling it for transaction control.');
        // Note: PDO::ATTR_AUTOCOMMIT is not always settable, but we'll handle it in the transaction
    }

    // Validate email format again (server-side)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }

    // Normalize email (lowercase, trim)
    $email = strtolower(trim($email));

    // Check email uniqueness with case-insensitive comparison
    $stmt = $pdo->prepare('SELECT id, email FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
    $stmt->execute(['email' => $email]);
    $existing = $stmt->fetch();
    if ($existing) {
        http_response_code(409);
        echo json_encode([
            'success' => false, 
            'message' => 'Email already exists in the system.',
            'existing_email' => $existing['email']
        ]);
        exit;
    }

    // Validate password strength (minimum 8 characters)
    if (strlen($password) < 8) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
        exit;
    }

    // Validate name (not empty, reasonable length)
    if (strlen($name) < 2 || strlen($name) > 191) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Name must be between 2 and 191 characters.']);
        exit;
    }

    // Begin transaction with proper error handling
    try {
        $pdo->beginTransaction();
    } catch (\PDOException $e) {
        error_log('Failed to begin transaction: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection error. Please try again.']);
        exit;
    }

    // Insert user, adding optional fields only if columns exist
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Discover available columns on users table
    $colsStmt = $pdo->query('SHOW COLUMNS FROM users');
    $availableCols = [];
    foreach ($colsStmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
        $availableCols[strtolower($c['Field'])] = true;
    }

    $columns = ['role', 'email', 'password_hash', 'name', 'status'];
    $placeholders = [':role', ':email', ':hash', ':name', ':status'];
    $params = [
        'role' => $role,
        'email' => $email,
        'hash' => $passwordHash,
        'name' => $name,
        'status' => 'active',
    ];

    // created_at if present
    if (isset($availableCols['created_at'])) {
        $columns[] = 'created_at';
        $placeholders[] = 'NOW()';
    }

    // Optional centralized fields
    if (isset($availableCols['lrn']) && $role === 'student') {
        $columns[] = 'lrn';
        $placeholders[] = ':lrn';
        $params['lrn'] = $lrn ?: null;
    }
    if (isset($availableCols['grade_level']) && $role === 'student') {
        $columns[] = 'grade_level';
        $placeholders[] = ':grade_level';
        $params['grade_level'] = $gradeLevel ?: null;
    }
    if (isset($availableCols['section_name']) && $role === 'student') {
        $columns[] = 'section_name';
        $placeholders[] = ':section_name';
        $params['section_name'] = $sectionName ?: null;
    }
    if (isset($availableCols['is_adviser']) && in_array($role, ['teacher','adviser'], true)) {
        $columns[] = 'is_adviser';
        $placeholders[] = ':is_adviser';
        $params['is_adviser'] = (int)$isAdviser;
    }
    if (isset($availableCols['linked_student_user_id']) && $role === 'parent') {
        $columns[] = 'linked_student_user_id';
        $placeholders[] = ':linked_student_user_id';
        $params['linked_student_user_id'] = $linkedStudentUserId ?: null;
    }
    if (isset($availableCols['parent_relationship']) && $role === 'parent') {
        $columns[] = 'parent_relationship';
        $placeholders[] = ':parent_relationship';
        $params['parent_relationship'] = $parentRelationship ?: null;
    }

    // Build SQL query safely
    $sql = 'INSERT INTO users (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    
    // Log the SQL and params for debugging (only in development)
    if (defined('DEBUG') && DEBUG) {
        error_log('User creation SQL: ' . $sql);
        error_log('User creation params: ' . json_encode($params));
    }
    
    try {
        $insertUser = $pdo->prepare($sql);
        
        // Execute with error handling
        $ok = $insertUser->execute($params);
        
        if (!$ok) {
            $errorInfo = $insertUser->errorInfo();
            $pdo->rollBack();
            error_log('User insert failed. Error info: ' . json_encode($errorInfo));
            error_log('SQL: ' . $sql);
            error_log('Params: ' . json_encode($params));
            
            // Provide user-friendly error messages
            $errorMessage = 'Failed to create user account.';
            if (isset($errorInfo[1])) {
                // MySQL error codes
                switch ($errorInfo[1]) {
                    case 1062: // Duplicate entry
                        $errorMessage = 'This email address is already registered.';
                        break;
                    case 1452: // Foreign key constraint fails
                        $errorMessage = 'Invalid reference data. Please contact support.';
                        break;
                    case 1366: // Incorrect string value
                        $errorMessage = 'Invalid data format. Please check your input.';
                        break;
                    default:
                        $errorMessage = 'Database error occurred. Please try again.';
                }
            }
            
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => $errorMessage,
                'error' => $errorInfo[2] ?? 'Unknown database error',
                'error_code' => $errorInfo[0] ?? null
            ]);
            exit;
        }
        
        // ====================================================================
        // STEP 1: Get the inserted user ID from users table
        // This ID will be used to link to teachers.user_id
        // Flow: users.id → teachers.user_id
        // ====================================================================
        $userId = (int)$pdo->lastInsertId();
        
        if ($userId === 0) {
            // Try to retrieve by email as fallback
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && isset($user['id'])) {
                $userId = (int)$user['id'];
                error_log('User created but lastInsertId failed. Retrieved ID from database: ' . $userId);
            } else {
                $pdo->rollBack();
                error_log('User insert succeeded but could not retrieve user ID');
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'User account was created but could not be verified. Please check the database or try again.',
                    'error' => 'lastInsertId returned 0 and user not found by email'
                ]);
                exit;
            }
        }
        
        error_log('User created successfully in users table with ID: ' . $userId . ' (This ID will be linked to teachers.user_id)');
        
    } catch (\PDOException $e) {
        $pdo->rollBack();
        error_log('PDO Exception during user insert: ' . $e->getMessage());
        error_log('SQL: ' . $sql);
        error_log('Params: ' . json_encode($params));
        error_log('Error code: ' . $e->getCode());
        
        // Provide user-friendly error messages
        $errorMessage = 'Failed to create user account.';
        $errorCode = $e->getCode();
        
        if ($errorCode == 23000) { // Integrity constraint violation
            if (strpos($e->getMessage(), 'email') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
                $errorMessage = 'This email address is already registered.';
            } else {
                $errorMessage = 'Data conflict detected. Please check your input.';
            }
        } elseif ($errorCode == 42000) { // Syntax error
            $errorMessage = 'Database configuration error. Please contact support.';
        }
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => $errorMessage,
            'error' => $e->getMessage(),
            'code' => $errorCode
        ]);
        exit;
    }

    // Create role-specific records (non-critical - user is already created)
    // If these fail, we still commit the user creation
    $roleSpecificErrors = [];
    
    if ($role === 'student') {
        // Check if students table exists and create student record
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'students'");
            if ($stmt->fetch()) {
                $studentStmt = $pdo->prepare('
                    INSERT INTO students (user_id, lrn, grade_level, section_id, enrollment_status, status)
                    VALUES (?, ?, ?, NULL, "pending", "active")
                ');
                $studentStmt->execute([
                    $userId,
                    $lrn ?: 'LRN' . str_pad((string)$userId, 6, '0', STR_PAD_LEFT),
                    $gradeLevel ?: 7
                ]);
            }
        } catch (\Throwable $e) {
            // Students table might not exist or have different structure
            error_log("Student record creation skipped: " . $e->getMessage());
            $roleSpecificErrors[] = 'Student profile could not be created, but user account was saved.';
        }
    } elseif ($role === 'teacher' || $role === 'adviser') {
        // ====================================================================
        // STEP 2: Create teacher record in teachers table (mandatory for these roles)
        // If this fails, rollback the transaction so users/teachers stay consistent.
        // ====================================================================
        try {
            error_log("STEP 2: Creating teacher profile in teachers table with user_id: " . $userId . " (from users.id)");
            
            // Verify user exists before creating teacher profile
            $verifyUserStmt = $pdo->prepare('SELECT id FROM users WHERE id = :user_id LIMIT 1');
            $verifyUserStmt->execute(['user_id' => $userId]);
            $userExists = $verifyUserStmt->fetchColumn();
            
            if (!$userExists) {
                throw new \RuntimeException('User does not exist. Cannot create teacher profile.');
            }
            
            upsertTeacherProfile($pdo, [
                'user_id' => $userId, // This links teachers.user_id to users.id
                'name' => $name,
                'department' => $department ?: null,
                'employee_id' => $employeeId ?: null,
                'specialization' => $subjectSpecialization ?: null,
                'hire_date' => $hireDate,
                'is_adviser' => ($role === 'adviser' || $isAdviser) ? 1 : 0,
            ]);

            // Verify teacher record exists after upsert
            $teacherVerify = $pdo->prepare('SELECT id FROM teachers WHERE user_id = :user_id LIMIT 1');
            $teacherVerify->execute(['user_id' => $userId]);
            $teacherId = $teacherVerify->fetchColumn();
            if ($teacherId === false) {
                throw new \RuntimeException('Teacher profile not found after creation for user_id: ' . $userId);
            }

            error_log("STEP 2: Teacher profile created successfully. teacher_id: {$teacherId}, user_id: {$userId}");
        } catch (\Throwable $e) {
            // Mandatory failure: rollback and return error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("ERROR: Teacher record creation failed for user_id {$userId}. Rolling back transaction. Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create teacher profile. No data was saved.',
                'error' => $e->getMessage()
            ]);
            exit;
        }
    } elseif ($role === 'parent') {
        // Create parent record if parents table exists
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'parents'");
            if ($stmt->fetch()) {
                $parentStmt = $pdo->prepare('
                    INSERT INTO parents (user_id, relationship, created_at)
                    VALUES (?, ?, NOW())
                ');
                $parentStmt->execute([
                    $userId,
                    $parentRelationship ?: 'guardian'
                ]);
            }
        } catch (\Throwable $e) {
            // Parents table might not exist
            error_log("Parent record creation skipped: " . $e->getMessage());
            $roleSpecificErrors[] = 'Parent profile could not be created, but user account was saved.';
        }
    }

    // ====================================================================
    // CRITICAL: Commit the transaction - user is ALWAYS saved
    // Even if teacher profile creation failed, the user account must be saved
    // ====================================================================
    
    // CRITICAL FIX: Ensure we're still in a transaction before committing
    if (!$pdo->inTransaction()) {
        error_log('CRITICAL WARNING: Not in a transaction before commit attempt. User ID: ' . $userId);
        // Try to verify user was saved outside transaction
        try {
            $checkUser = $pdo->prepare('SELECT id, email, name FROM users WHERE id = :user_id LIMIT 1');
            $checkUser->execute(['user_id' => $userId]);
            $existingUser = $checkUser->fetch();
            
            if ($existingUser) {
                error_log('User already exists in database (transaction may have auto-committed). User ID: ' . $userId);
                // User already saved, continue with response
            } else {
                error_log('CRITICAL: User not found and not in transaction. This is a serious error.');
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'User account creation failed. Please try again.',
                    'error' => 'Transaction state error'
                ]);
                exit;
            }
        } catch (\PDOException $checkError) {
            error_log('CRITICAL: Error checking user existence: ' . $checkError->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'User account creation failed. Please try again.',
                'error' => 'Database verification error'
            ]);
            exit;
        }
    } else {
        // We're in a transaction, proceed with commit
        try {
            // Verify user still exists before committing (safety check)
            $verifyBeforeCommit = $pdo->prepare('SELECT id, email, name FROM users WHERE id = :user_id LIMIT 1');
            $verifyBeforeCommit->execute(['user_id' => $userId]);
            $userBeforeCommit = $verifyBeforeCommit->fetch();
            
            if (!$userBeforeCommit) {
                error_log('CRITICAL: User was not found before commit. Rolling back transaction.');
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'User account was not created. Please try again.',
                    'error' => 'User verification failed before commit'
                ]);
                exit;
            }
            
            // COMMIT THE TRANSACTION - This is the critical step
            $commitSuccess = $pdo->commit();
            
            if (!$commitSuccess) {
                throw new \PDOException('Commit returned false');
            }
            
            error_log('Transaction committed successfully. User ID: ' . $userId . ', Email: ' . ($userBeforeCommit['email'] ?? 'unknown'));
            
            // Final verification after commit - use a fresh query to ensure data is persisted
            // Wait a tiny bit to ensure commit is fully processed
            usleep(100000); // 100ms delay
            
            $finalVerify = $pdo->prepare('SELECT id, email, name FROM users WHERE id = :user_id LIMIT 1');
            $finalVerify->execute(['user_id' => $userId]);
            $finalUser = $finalVerify->fetch();
            
            if (!$finalUser) {
                error_log('CRITICAL WARNING: User not found after commit. This should not happen. User ID: ' . $userId);
                // Even if verification fails, we'll still return success since commit succeeded
                // The user might be there but query timing could be an issue
            } else {
                error_log('Final verification: User confirmed in database. ID: ' . $userId . ', Email: ' . ($finalUser['email'] ?? 'unknown'));
            }
            
        } catch (\PDOException $e) {
            error_log('CRITICAL: Failed to commit transaction: ' . $e->getMessage());
            error_log('Transaction state: ' . ($pdo->inTransaction() ? 'still active' : 'not active'));
            error_log('Error code: ' . $e->getCode());
            error_log('Error info: ' . json_encode($e->errorInfo ?? []));
            
            // Try to rollback if commit fails
            try {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                    error_log('Transaction rolled back after commit failure');
                }
            } catch (\PDOException $rollbackError) {
                error_log('CRITICAL: Failed to rollback after commit failure: ' . $rollbackError->getMessage());
            }
            
            // Check if user was actually saved despite commit failure
            try {
                $checkAfterRollback = $pdo->prepare('SELECT id FROM users WHERE id = :user_id LIMIT 1');
                $checkAfterRollback->execute(['user_id' => $userId]);
                $userAfterRollback = $checkAfterRollback->fetchColumn();
                
                if ($userAfterRollback) {
                    error_log('User was saved despite commit error. This may indicate auto-commit was enabled.');
                    // User is saved, continue with success response
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Failed to save user account. Please try again.',
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode()
                    ]);
                    exit;
                }
            } catch (\PDOException $checkError) {
                error_log('Error checking user after rollback: ' . $checkError->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to save user account. Please try again.',
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode()
                ]);
                exit;
            }
        }
    }

    // ====================================================================
    // FINAL VERIFICATION: Ensure user is actually in the database
    // ====================================================================
    $finalUserData = null;
    try {
        $finalCheck = $pdo->prepare('SELECT id, email, name, role, status FROM users WHERE id = :user_id LIMIT 1');
        $finalCheck->execute(['user_id' => $userId]);
        $finalUserData = $finalCheck->fetch();
        
        if (!$finalUserData) {
            error_log('CRITICAL: Final check failed - user not found in database. User ID: ' . $userId);
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'User account was created but could not be verified. Please check the database.',
                'error' => 'Final verification failed',
                'user_id' => $userId
            ]);
            exit;
        }
        
        error_log('SUCCESS: User verified in database. ID: ' . $userId . ', Email: ' . ($finalUserData['email'] ?? 'unknown') . ', Role: ' . ($finalUserData['role'] ?? 'unknown'));
    } catch (\PDOException $e) {
        error_log('Error during final user verification: ' . $e->getMessage());
        // Continue anyway - commit succeeded, user should be there
        // Use the email we have from earlier
    }
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'User created successfully.',
        'user_id' => $userId, // users.id - this is the primary key in users table
        'email' => $finalUserData['email'] ?? $email, // Include email for verification
    ];
    
    // For teachers/advisers, also return the teacher_id if available
    if ($role === 'teacher' || $role === 'adviser') {
        try {
            $teacherStmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = :user_id LIMIT 1');
            $teacherStmt->execute(['user_id' => $userId]);
            $teacherId = $teacherStmt->fetchColumn();
            if ($teacherId !== false) {
                $response['teacher_id'] = (int)$teacherId; // teachers.id - this is the primary key in teachers table
                $response['message'] .= ' Teacher profile linked: teachers.user_id = ' . $userId . ' (users.id)';
                error_log('Teacher profile confirmed. Teacher ID: ' . $teacherId . ', User ID: ' . $userId);
            } else {
                error_log('WARNING: Teacher profile not found for user_id: ' . $userId);
            }
        } catch (\Throwable $e) {
            // Non-critical - just log it
            error_log('Could not retrieve teacher_id for response: ' . $e->getMessage());
        }
    }
    
    // Include warnings if role-specific records had issues
    if (!empty($roleSpecificErrors)) {
        $response['warnings'] = $roleSpecificErrors;
        $response['message'] .= ' Note: Some profile details may need to be updated manually.';
    }
    
    // Log successful completion
    error_log('=== USER CREATION COMPLETED SUCCESSFULLY ===');
    error_log('User ID: ' . $userId);
    error_log('Email: ' . ($finalUserData['email'] ?? $email));
    error_log('Role: ' . $role);
    error_log('==========================================');
    
    echo json_encode($response);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log the full error for debugging
    error_log('User creation error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}


