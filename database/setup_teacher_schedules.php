<?php
/**
 * Setup Teacher Schedules Table
 * This script creates the teacher_schedules table for schedule conflict detection
 */

$config = require __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['database']};charset=utf8mb4",
        $config['database']['username'],
        $config['database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "Creating teacher_schedules table...\n";

    // Create the teacher_schedules table
    $sql = "
    CREATE TABLE IF NOT EXISTS `teacher_schedules` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `teacher_id` INT UNSIGNED NOT NULL,
      `day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
      `start_time` TIME NOT NULL,
      `end_time` TIME NOT NULL,
      `class_id` INT UNSIGNED DEFAULT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `unique_schedule` (`teacher_id`, `day_of_week`, `start_time`, `end_time`),
      KEY `idx_teacher_day` (`teacher_id`, `day_of_week`),
      KEY `idx_time_range` (`start_time`, `end_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";

    $pdo->exec($sql);
    echo "✅ teacher_schedules table created successfully!\n";

    // Insert sample schedule data
    echo "Inserting sample schedule data...\n";
    
    // Get the first teacher ID for sample data
    $stmt = $pdo->query("SELECT id FROM teachers LIMIT 1");
    $teacher = $stmt->fetch();
    
    if ($teacher) {
        $teacherId = $teacher['id'];
        
        // Get a class for this teacher
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE teacher_id = ? LIMIT 1");
        $stmt->execute([$teacherId]);
        $class = $stmt->fetch();
        
        if ($class) {
            $classId = $class['id'];
            
            // Insert sample schedules
            $schedules = [
                ['Monday', '08:00:00', '09:00:00'],
                ['Wednesday', '08:00:00', '09:00:00'],
                ['Friday', '08:00:00', '09:00:00'],
                ['Tuesday', '10:00:00', '11:00:00'],
                ['Thursday', '10:00:00', '11:00:00']
            ];
            
            $insertStmt = $pdo->prepare("
                INSERT IGNORE INTO teacher_schedules (teacher_id, day_of_week, start_time, end_time, class_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($schedules as $schedule) {
                $insertStmt->execute([$teacherId, $schedule[0], $schedule[1], $schedule[2], $classId]);
            }
            
            echo "✅ Sample schedule data inserted successfully!\n";
        }
    }

    // Verify the table
    echo "\nVerifying table structure...\n";
    $stmt = $pdo->query("DESCRIBE teacher_schedules");
    $columns = $stmt->fetchAll();
    
    echo "Table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }

    // Show sample data
    echo "\nSample data:\n";
    $stmt = $pdo->query("
        SELECT 
            ts.id,
            u.name as teacher_name,
            ts.day_of_week,
            ts.start_time,
            ts.end_time,
            c.id as class_id,
            sec.name as section_name,
            sub.name as subject_name
        FROM teacher_schedules ts
        JOIN teachers t ON ts.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        LEFT JOIN classes c ON ts.class_id = c.id
        LEFT JOIN sections sec ON c.section_id = sec.id
        LEFT JOIN subjects sub ON c.subject_id = sub.id
        ORDER BY ts.teacher_id, ts.day_of_week, ts.start_time
    ");
    
    $schedules = $stmt->fetchAll();
    foreach ($schedules as $schedule) {
        echo "- {$schedule['teacher_name']}: {$schedule['day_of_week']} {$schedule['start_time']}-{$schedule['end_time']} ({$schedule['subject_name']})\n";
    }

    echo "\n✅ Setup completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
