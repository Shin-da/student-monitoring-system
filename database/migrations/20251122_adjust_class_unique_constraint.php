<?php
declare(strict_types=1);

use Core\Database;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Core/Database.php';

$config = require __DIR__ . '/../../config/config.php';
$pdo = Database::connection($config['database']);

try {
    $indexName = 'unique_class_assignment';
$indexExistsStmt = $pdo->query("SHOW INDEX FROM classes WHERE Key_name = '{$indexName}'");
$indexExists = (bool)$indexExistsStmt->fetch(\PDO::FETCH_ASSOC);

    if ($indexExists) {
        $pdo->exec('ALTER TABLE classes DROP INDEX ' . $indexName);
    }

    $pdo->exec('ALTER TABLE classes ADD UNIQUE KEY ' . $indexName . ' (section_id, subject_id, semester, school_year, is_active)');

    echo "✅ Updated unique_class_assignment index successfully.\n";
} catch (Throwable $e) {
    echo "❌ Failed to update unique index: " . $e->getMessage() . "\n";
    exit(1);
}

