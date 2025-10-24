<?php
/**
 * TEST DIRECT DE CONNEXION MYSQL
 * Uploadez ce fichier et testez : http://ismo.gamer.gd/api/verify_db.php
 * Si ça fonctionne, vous saurez que parse_ini_file() marche !
 */

header('Content-Type: application/json');

// Charger le .env avec parse_ini_file
$envFile = __DIR__ . '/.env';
$envVars = parse_ini_file($envFile);

$result = [
    'step1_env_loaded' => !empty($envVars),
    'step2_credentials' => [
        'DB_HOST' => $envVars['DB_HOST'] ?? 'NOT FOUND',
        'DB_NAME' => $envVars['DB_NAME'] ?? 'NOT FOUND',
        'DB_USER' => $envVars['DB_USER'] ?? 'NOT FOUND',
        'DB_PASS_length' => isset($envVars['DB_PASS']) ? strlen($envVars['DB_PASS']) : 0
    ]
];

// Tester la connexion MySQL
try {
    $dsn = "mysql:host={$envVars['DB_HOST']};dbname={$envVars['DB_NAME']};charset=utf8mb4";
    $pdo = new PDO($dsn, $envVars['DB_USER'], $envVars['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $result['step3_connection'] = 'SUCCESS';
    $result['mysql_version'] = $pdo->query('SELECT VERSION()')->fetchColumn();
    
    // Tester une vraie requête
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $count = $stmt->fetch();
    $result['step4_query'] = 'SUCCESS';
    $result['users_count'] = $count['count'];
    
    $result['final_status'] = 'EVERYTHING WORKS!';
    
} catch (PDOException $e) {
    $result['step3_connection'] = 'FAILED';
    $result['error'] = $e->getMessage();
    $result['final_status'] = 'CONNECTION FAILED';
}

echo json_encode($result, JSON_PRETTY_PRINT);
