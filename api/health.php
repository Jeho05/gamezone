<?php
/**
 * Health Check Endpoint
 * Returns system health status and metrics
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/database.php';

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

// Check database connection
$dbHealthy = check_db_health();
$health['checks']['database'] = [
    'status' => $dbHealthy ? 'up' : 'down',
    'message' => $dbHealthy ? 'Database connection successful' : 'Database connection failed'
];

if (!$dbHealthy) {
    $health['status'] = 'unhealthy';
}

// Check cache directory
$cacheWritable = is_writable(sys_get_temp_dir());
$health['checks']['cache'] = [
    'status' => $cacheWritable ? 'up' : 'down',
    'message' => $cacheWritable ? 'Cache directory writable' : 'Cache directory not writable'
];

// Check uploads directory
$uploadsDir = __DIR__ . '/../uploads';
$uploadsWritable = is_dir($uploadsDir) && is_writable($uploadsDir);
$health['checks']['uploads'] = [
    'status' => $uploadsWritable ? 'up' : 'down',
    'message' => $uploadsWritable ? 'Uploads directory writable' : 'Uploads directory not writable'
];

// Check PHP version
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
$health['checks']['php'] = [
    'status' => $phpOk ? 'up' : 'down',
    'version' => $phpVersion,
    'message' => $phpOk ? 'PHP version compatible' : 'PHP version too old (requires 7.4+)'
];

// System info
$health['system'] = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
];

// Overall status
$allHealthy = $health['checks']['database']['status'] === 'up' && 
              $health['checks']['cache']['status'] === 'up';

$health['status'] = $allHealthy ? 'healthy' : 'degraded';

$statusCode = $allHealthy ? 200 : 503;
http_response_code($statusCode);
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
