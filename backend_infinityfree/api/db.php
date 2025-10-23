<?php
// Nouveau fichier de connexion BDD - Complètement isolé
// Valeurs en dur pour XAMPP

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'gamezone');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_db_connection(): PDO {
    static $pdo = null;
    
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode([
            'error' => 'Database connection failed',
            'details' => $e->getMessage(),
            'config' => [
                'host' => DB_HOST,
                'database' => DB_NAME,
                'user' => DB_USER
            ]
        ]));
    }
}
