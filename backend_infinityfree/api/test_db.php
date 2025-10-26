<?php
// Test de connexion et vérification des tables
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $pdo = get_db();
    
    // Vérifier les tables importantes
    $tables_to_check = [
        'games',
        'game_packages', 
        'payment_methods',
        'purchases',
        'game_sessions',
        'users'
    ];
    
    $results = [];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $results[$table] = [
                'exists' => true,
                'count' => $result['count']
            ];
        } catch (Exception $e) {
            $results[$table] = [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Vérifier si l'utilisateur est connecté (session déjà démarrée dans config.php)
    $user = $_SESSION['user'] ?? null;
    
    echo json_encode([
        'success' => true,
        'database' => 'Connected',
        'tables' => $results,
        'user_session' => $user ? [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ] : null
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
