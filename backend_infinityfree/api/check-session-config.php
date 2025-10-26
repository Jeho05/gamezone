<?php
// Check session configuration
header('Content-Type: application/json');

// Load .env.railway to check what's loaded
$railwayEnv = __DIR__ . '/.env.railway';
$envLoaded = false;
if (file_exists($railwayEnv)) {
    $lines = file($railwayEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
    $envLoaded = true;
}

echo json_encode([
    'env_file_loaded' => $envLoaded,
    'env_vars' => [
        'SESSION_SAMESITE' => getenv('SESSION_SAMESITE') ?: 'NOT_SET',
        'SESSION_SECURE' => getenv('SESSION_SECURE') ?: 'NOT_SET',
    ],
    'php_ini_settings' => [
        'session.cookie_samesite' => ini_get('session.cookie_samesite'),
        'session.cookie_secure' => ini_get('session.cookie_secure'),
        'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    ],
    'expected' => [
        'session.cookie_samesite' => 'None',
        'session.cookie_secure' => '1',
    ]
], JSON_PRETTY_PRINT);
