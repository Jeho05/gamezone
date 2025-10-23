<?php
// Créer les comptes demo affichés sur la page de login
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $pdo = get_db();
    
    $results = [];
    
    // Mot de passe: demo123
    $hashedPassword = password_hash('demo123', PASSWORD_DEFAULT);
    
    // 1. Créer ou mettre à jour l'admin demo
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, role, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE password_hash = ?, role = ?, updated_at = NOW()
    ");
    
    $adminEmail = 'admin@gamezone.fr';
    $stmt->execute(['AdminDemo', $adminEmail, $hashedPassword, 'admin', $hashedPassword, 'admin']);
    $results['admin'] = [
        'email' => $adminEmail,
        'password' => 'demo123',
        'action' => $stmt->rowCount() > 0 ? 'created/updated' : 'already exists'
    ];
    
    // 2. Créer ou mettre à jour le player demo
    $playerEmail = 'player@gamezone.fr';
    $stmt->execute(['PlayerDemo', $playerEmail, $hashedPassword, 'player', $hashedPassword, 'player']);
    $results['player'] = [
        'email' => $playerEmail,
        'password' => 'demo123',
        'action' => $stmt->rowCount() > 0 ? 'created/updated' : 'already exists'
    ];
    
    // 3. Vérifier que les comptes existent maintenant
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE email IN (?, ?)");
    $stmt->execute([$adminEmail, $playerEmail]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Comptes demo créés/mis à jour',
        'results' => $results,
        'accounts' => $accounts
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
