<?php
/**
 * Batch Performance Analysis Script
 * 
 * This script analyzes all students' performance and generates alerts.
 * Can be run via cron job for daily batch processing.
 * 
 * Usage:
 *   php app/Services/analyze-performance-batch.php [--section=ID] [--quarter=N] [--year=YYYY-YYYY]
 * 
 * Example cron job (runs daily at 2 AM):
 *   0 2 * * * cd /path/to/student-monitoring && php app/Services/analyze-performance-batch.php >> logs/performance-analysis.log 2>&1
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');

// Simple autoloader
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Core' => APP_PATH . '/Core',
        'Controllers' => APP_PATH . '/Controllers',
        'Models' => APP_PATH . '/Models',
        'Helpers' => APP_PATH . '/Helpers',
        'Services' => APP_PATH . '/Services',
    ];

    foreach ($prefixes as $ns => $dir) {
        $nsPrefix = $ns . '\\';
        if (str_starts_with($class, $nsPrefix)) {
            $relative = substr($class, strlen($nsPrefix));
            $path = $dir . '/' . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($path)) {
                require_once $path;
            }
            return;
        }
    }
});

require_once BASE_PATH . '/app/Core/Database.php';

use Services\PerformanceAnalyzer;
use Services\AlertService;

// Parse command line arguments
$options = getopt('', ['section:', 'quarter:', 'year:', 'help']);

if (isset($options['help'])) {
    echo "Usage: php analyze-performance-batch.php [OPTIONS]\n";
    echo "\n";
    echo "Options:\n";
    echo "  --section=ID    Analyze only specific section ID\n";
    echo "  --quarter=N     Analyze specific quarter (1-4)\n";
    echo "  --year=YYYY-YYYY Analyze specific academic year\n";
    echo "  --help          Show this help message\n";
    echo "\n";
    exit(0);
}

$sectionId = isset($options['section']) ? (int)$options['section'] : null;
$quarter = isset($options['quarter']) ? (int)$options['quarter'] : null;
$academicYear = $options['year'] ?? null;

// Validate quarter if provided
if ($quarter !== null && !in_array($quarter, [1, 2, 3, 4], true)) {
    echo "Error: Quarter must be 1, 2, 3, or 4\n";
    exit(1);
}

try {
    $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
    $pdo = \Core\Database::connection($config['database']);
    
    $analyzer = new PerformanceAnalyzer($pdo);
    $alertService = new AlertService($pdo, $analyzer);
    
    echo "[" . date('Y-m-d H:i:s') . "] Starting performance analysis...\n";
    
    if ($sectionId !== null) {
        echo "Analyzing section ID: {$sectionId}\n";
    }
    if ($quarter !== null) {
        echo "Quarter: {$quarter}\n";
    }
    if ($academicYear !== null) {
        echo "Academic Year: {$academicYear}\n";
    }
    
    // Analyze all students
    $analyses = $analyzer->analyzeAllStudents($sectionId, $quarter, $academicYear);
    
    echo "Analyzed " . count($analyses) . " students\n";
    
    // Generate alerts for at-risk students
    $generatedAlerts = $alertService->checkAndGenerateAlerts($sectionId, $quarter, $academicYear);
    
    $atRiskCount = count(array_filter($analyses, fn($a) => $a['is_at_risk'] ?? false));
    $highRiskCount = count(array_filter($analyses, fn($a) => ($a['risk_level'] ?? 'low') === 'high'));
    
    echo "At-risk students: {$atRiskCount}\n";
    echo "High-risk students: {$highRiskCount}\n";
    echo "Alerts generated: " . count($generatedAlerts) . "\n";
    
    echo "[" . date('Y-m-d H:i:s') . "] Analysis complete.\n";
    
    exit(0);
    
} catch (\Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

