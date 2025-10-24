<?php
// Connexion BDD - Lit les variables depuis .env avec parse_ini_file()

// Charger le fichier .env
$envFile = __DIR__ . '/.env';
$envVars = [];

if (file_exists($envFile)) {
    // Utiliser parse_ini_file qui fonctionne sur InfinityFree
    $envVars = parse_ini_file($envFile);
    
    // Si parse_ini_file échoue, parser manuellement
    if ($envVars === false) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parser KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $envVars[trim($key)] = trim($value);
            }
        }
    }
}

// Définir les constantes avec les valeurs du .env ou fallback
define('DB_HOST', isset($envVars['DB_HOST']) ? $envVars['DB_HOST'] : '127.0.0.1');
define('DB_NAME', isset($envVars['DB_NAME']) ? $envVars['DB_NAME'] : 'gamezone');
define('DB_USER', isset($envVars['DB_USER']) ? $envVars['DB_USER'] : 'root');
define('DB_PASS', isset($envVars['DB_PASS']) ? $envVars['DB_PASS'] : '');

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
            'debug' => [
                'host' => DB_HOST,
                'database' => DB_NAME,
                'user' => DB_USER,
                'pass_length' => strlen(DB_PASS)
            ]
        ]));
    }
}

// Alias pour compatibilité
function get_db() {
    return get_db_connection();
}
