<?php
// api/install.php
// One-time installer to create the database schema and seed data
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'gamezone';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

try {
    // 1) Connect without DB to create it if needed
    $dsnNoDb = "mysql:host={$DB_HOST};charset=utf8mb4";
    $pdoNoDb = new PDO($dsnNoDb, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdoNoDb->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 2) Connect to the DB
    $pdo = get_db();

    // 3) Load schema.sql and execute statements (skip CREATE DATABASE/USE if present)
    $schemaPath = __DIR__ . '/schema.sql';
    if (!file_exists($schemaPath)) {
        echo json_encode(['error' => 'schema.sql introuvable']);
        exit;
    }
    $sql = file_get_contents($schemaPath);

    // Remove lines that start with CREATE DATABASE or USE to avoid duplicate actions
    $lines = preg_split('/\r\n|\r|\n/', $sql);
    $filtered = [];
    foreach ($lines as $line) {
        $trim = ltrim($line);
        if (stripos($trim, 'CREATE DATABASE') === 0 || stripos($trim, 'USE ') === 0) {
            continue;
        }
        $filtered[] = $line;
    }
    $sql = implode("\n", $filtered);

    // Split by semicolon but keep it simple (no complicated procedures in file)
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt === '' || strpos($stmt, '--') === 0) continue;
        $pdo->exec($stmt);
    }

    echo json_encode(['message' => 'Installation terminée', 'database' => $DB_NAME]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Échec installation', 'details' => $e->getMessage()]);
}
