<?php
// Test de configuration - Affiche les valeurs utilisées pour la connexion
header('Content-Type: application/json');

// Charger config
require_once __DIR__ . '/config.php';

// Afficher les variables (sans exposer les mots de passe réels)
$configInfo = [
    'DB_HOST' => defined('DB_HOST') ? DB_HOST : 'NON DEFINI',
    'DB_NAME' => defined('DB_NAME') ? DB_NAME : 'NON DEFINI',
    'DB_USER' => defined('DB_USER') ? DB_USER : 'NON DEFINI',
    'DB_PASS_LENGTH' => defined('DB_PASS') ? strlen(DB_PASS) : 'NON DEFINI',
    'DB_PASS_EMPTY' => defined('DB_PASS') ? (DB_PASS === '') : true,
    'using_constants' => true,
    'getenv_results' => [
        'DB_HOST' => getenv('DB_HOST'),
        'DB_NAME' => getenv('DB_NAME'),
        'DB_USER' => getenv('DB_USER'),
        'DB_PASS' => getenv('DB_PASS')
    ]
];

// Tester la connexion
try {
    $pdo = get_db();
    $configInfo['connection'] = 'SUCCESS';
    $configInfo['database_test'] = 'Connected to database';
    
    // Tester une requête simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $configInfo['users_count'] = $result['count'];
    
} catch (Exception $e) {
    $configInfo['connection'] = 'FAILED';
    $configInfo['error'] = $e->getMessage();
}

echo json_encode($configInfo, JSON_PRETTY_PRINT);
