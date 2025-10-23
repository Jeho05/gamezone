<?php
// Lister les utilisateurs pour vérifier les identifiants
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $pdo = get_db();
    
    // Récupérer tous les utilisateurs avec leurs rôles
    $stmt = $pdo->query("
        SELECT id, username, email, role, created_at
        FROM users 
        ORDER BY role, username
        LIMIT 50
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => count($users),
        'stats' => $stats,
        'users' => $users,
        'note' => 'Les mots de passe ne sont pas affichés pour des raisons de sécurité'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
