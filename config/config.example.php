<?php
declare(strict_types=1);

/**
 * Configuration Template
 * 
 * Copy this file to config/config.php and update with your local settings.
 * Your config.php file is gitignored and won't be committed to the repository.
 */

return [
    'app' => [
        'name' => 'St. Ignatius Student Monitoring',
        'env' => 'development', // 'development' or 'production'
        'base_url' => '/', // Change to '/student-monitoring/' if in subfolder
        'timezone' => 'Asia/Manila',
        'display_errors' => true, // Set to false in production
    ],
    'database' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1', // Usually 'localhost' or '127.0.0.1'
        'port' => 3306,
        'database' => 'student_monitoring', // Your database name
        'username' => 'root', // Your MySQL username
        'password' => '', // Your MySQL password (leave empty if no password)
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'session' => [
        'name' => 'ssm_session',
        'cookie_lifetime' => 60 * 60 * 2, // 2 hours
        'cookie_secure' => false, // Set to true if using HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
];

