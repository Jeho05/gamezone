<?php
// Debug: Vérifier si .env.railway existe et est lisible
header('Content-Type: application/json');

$envFile = __DIR__ . '/.env.railway';
$exists = file_exists($envFile);
$readable = is_readable($envFile);
$content = $exists ? file_get_contents($envFile) : null;

// Charger les variables comme config.php le fait
if ($exists && $readable) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

echo json_encode([
    'env_file_exists' => $exists,
    'env_file_readable' => $readable,
    'env_file_path' => $envFile,
    'env_file_size' => $exists ? filesize($envFile) : 0,
    'env_file_lines' => $exists ? count(file($envFile)) : 0,
    'loaded_vars' => [
        'DB_HOST' => getenv('DB_HOST') ?: 'NOT_SET',
        'DB_NAME' => getenv('DB_NAME') ?: 'NOT_SET',
        'DB_USER' => getenv('DB_USER') ?: 'NOT_SET',
        'DB_PASS_LENGTH' => strlen(getenv('DB_PASS') ?: ''),
    ],
    'files_in_dir' => array_slice(scandir(__DIR__), 0, 20),
], JSON_PRETTY_PRINT);
