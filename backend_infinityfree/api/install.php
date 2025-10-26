<?php
// api/install.php
// One-time installer to create the database schema and seed data
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Use constants defined in config.php (which loads .env.railway)
$DB_HOST_CONN = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$DB_NAME_CONN = defined('DB_NAME') ? DB_NAME : 'railway';
$DB_USER_CONN = defined('DB_USER') ? DB_USER : 'root';
$DB_PASS_CONN = defined('DB_PASS') ? DB_PASS : '';
$DB_PORT_CONN = defined('DB_PORT') ? DB_PORT : '3306';

try {
    // 1) Connect without DB to create it if needed
    $dsnNoDb = "mysql:host={$DB_HOST_CONN};port={$DB_PORT_CONN};charset=utf8mb4";
    $pdoNoDb = new PDO($dsnNoDb, $DB_USER_CONN, $DB_PASS_CONN, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdoNoDb->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME_CONN}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdoNoDb->exec("USE `{$DB_NAME_CONN}`");

    // 2) Use the connection from config.php (already connected to DB)
    $pdo = $pdoNoDb; // Use the same connection

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
        // Skip comment lines
        if (strpos($trim, '--') === 0 || $trim === '') {
            continue;
        }
        $filtered[] = $line;
    }
    $sql = implode("\n", $filtered);

    // Better SQL parsing: split on semicolons but handle multi-line statements
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    
    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];
        
        // Handle string literals
        if (($char === '"' || $char === "'") && ($i === 0 || $sql[$i-1] !== '\\')) {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar) {
                $inString = false;
            }
        }
        
        // Split on semicolon only if not in string
        if ($char === ';' && !$inString) {
            $stmt = trim($current);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $current = '';
        } else {
            $current .= $char;
        }
    }
    
    // Add last statement if exists
    $stmt = trim($current);
    if ($stmt !== '') {
        $statements[] = $stmt;
    }
    $executedCount = 0;
    $errors = [];
    
    foreach ($statements as $index => $stmt) {
        if ($stmt === '') continue;
        try {
            $pdo->exec($stmt);
            $executedCount++;
        } catch (PDOException $e) {
            // Log error but continue to see all errors
            $errors[] = [
                'statement_index' => $index,
                'error' => $e->getMessage(),
                'statement_preview' => substr($stmt, 0, 150)
            ];
        }
    }

    if (!empty($errors)) {
        echo json_encode([
            'status' => 'partial',
            'message' => "Installation partielle: {$executedCount} statements exécutés",
            'database' => $DB_NAME_CONN,
            'executed' => $executedCount,
            'errors' => $errors
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'Installation terminée',
            'database' => $DB_NAME_CONN,
            'executed' => $executedCount
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Échec installation', 'details' => $e->getMessage()]);
}
