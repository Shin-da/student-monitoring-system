<?php
declare(strict_types=1);

use Core\Database;

// Autoload and configuration
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/config.php';
$dbConfig = $config['database'];

/** @var PDO $pdo */
$pdo = Database::connection([
    'host' => $dbConfig['host'],
    'port' => (int)$dbConfig['port'],
    'database' => $dbConfig['database'],
    'username' => $dbConfig['username'],
    'password' => $dbConfig['password'],
    'charset' => $dbConfig['charset'] ?? 'utf8mb4',
]);


