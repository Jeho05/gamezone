<?php
// Test avec le nouveau fichier db.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();
    
    // Test simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Nouvelle configuration fonctionne!',
        'connection' => 'SUCCESS',
        'users_count' => $result['count'],
        'config' => [
            'host' => DB_HOST,
            'database' => DB_NAME,
            'user' => DB_USER
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
