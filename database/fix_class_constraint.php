<?php
/**
 * Fix Unique Class Assignment Constraint
 * 
 * This script fixes the unique_class_assignment constraint in the database
 * to allow multiple classes with different subjects for the same section.
 * 
 * Usage: Run this script from command line or via browser (for development only)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$config = require __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['name']};charset=utf8mb4",
        $config['database']['username'],
        $config['database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "=== Fixing Unique Class Assignment Constraint ===\n\n";

    // Step 1: Check if the old constraint exists
    echo "Step 1: Checking for existing constraint...\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM information_schema.STATISTICS 
        WHERE table_schema = DATABASE() 
        AND table_name = 'classes' 
        AND index_name = 'unique_class_assignment'
    ");
    $oldConstraintExists = $stmt->fetch()['count'] > 0;

    if ($oldConstraintExists) {
        echo "  ✓ Found old constraint 'unique_class_assignment'\n";
        
        // Drop the old constraint
        echo "\nStep 2: Dropping old constraint...\n";
        $pdo->exec("ALTER TABLE `classes` DROP INDEX `unique_class_assignment`");
        echo "  ✓ Old constraint dropped successfully\n";
    } else {
        echo "  ℹ Old constraint 'unique_class_assignment' does not exist\n";
    }

    // Step 2: Check if the new constraint already exists
    echo "\nStep 3: Checking for new constraint...\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM information_schema.STATISTICS 
        WHERE table_schema = DATABASE() 
        AND table_name = 'classes' 
        AND index_name = 'unique_class_section_subject'
    ");
    $newConstraintExists = $stmt->fetch()['count'] > 0;

    if (!$newConstraintExists) {
        // Create the new constraint
        echo "\nStep 4: Creating new constraint with subject_id...\n";
        $pdo->exec("
            ALTER TABLE `classes` 
            ADD UNIQUE KEY `unique_class_section_subject` 
            (`section_id`, `subject_id`, `semester`, `school_year`)
        ");
        echo "  ✓ New constraint 'unique_class_section_subject' created successfully\n";
        echo "  ✓ This allows multiple classes with different subjects for the same section\n";
    } else {
        echo "  ℹ New constraint 'unique_class_section_subject' already exists\n";
    }

    // Step 3: Verify constraints
    echo "\nStep 5: Verifying constraints...\n";
    $stmt = $pdo->query("
        SELECT 
            INDEX_NAME,
            GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS COLUMNS
        FROM information_schema.STATISTICS
        WHERE table_schema = DATABASE()
        AND table_name = 'classes'
        AND INDEX_NAME LIKE 'unique%'
        GROUP BY INDEX_NAME
    ");
    
    $constraints = $stmt->fetchAll();
    if (empty($constraints)) {
        echo "  ⚠ No unique constraints found on classes table\n";
    } else {
        foreach ($constraints as $constraint) {
            echo "  ✓ Constraint: {$constraint['INDEX_NAME']} on ({$constraint['COLUMNS']})\n";
        }
    }

    echo "\n=== Fix Completed Successfully ===\n";
    echo "\nYou can now create multiple classes with different subjects for the same section.\n";
    echo "The constraint ensures that each combination of section, subject, semester, and school year is unique.\n";

} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. Database credentials in config/config.php\n";
    echo "2. Database connection\n";
    echo "3. Table structure\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
