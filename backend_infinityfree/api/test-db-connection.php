<?php
// Test de connexion DB pour Railway
header('Content-Type: application/json');

$host = getenv('MYSQLHOST') ?: 'NOT_SET';
$db = getenv('MYSQLDATABASE') ?: 'NOT_SET';
$user = getenv('MYSQLUSER') ?: 'NOT_SET';
$pass = getenv('MYSQLPASSWORD') ?: 'NOT_SET';
$port = getenv('MYSQLPORT') ?: '3306';

$debug = [
    'host' => $host,
    'database' => $db,
    'user' => $user,
    'password_length' => strlen($pass),
    'port' => $port,
    'password_set' => $pass !== 'NOT_SET',
];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    
    echo json_encode([
        'status' => 'SUCCESS',
        'message' => 'Connexion DB réussie!',
        'debug' => $debug,
        'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
    ], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'FAILED',
        'error' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'debug' => $debug,
    ], JSON_PRETTY_PRINT);
}
