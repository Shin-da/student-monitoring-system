<?php
/**
 * Setup Notifications Table
 * Run this script to create the notifications table if it doesn't exist
 */

// Define base paths (required for bootstrap)
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'app');

require_once __DIR__ . '/../app/bootstrap.php';

$config = require BASE_PATH . '/config/config.php';

try {
    $pdo = \Core\Database::connection($config['database']);
    
    echo "Checking if notifications table exists...\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Notifications table already exists.\n";
        exit(0);
    }
    
    echo "Creating notifications table...\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/create_notifications_table.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^(--|SET|START|COMMIT)/i', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (!empty(trim($statement))) {
            try {
                $pdo->exec($statement);
            } catch (\PDOException $e) {
                // Ignore errors for CREATE INDEX IF NOT EXISTS
                if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                    echo "⚠️  Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✅ Notifications table created successfully!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

