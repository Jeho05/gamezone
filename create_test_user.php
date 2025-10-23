<?php
// Créer un utilisateur de test
require_once __DIR__ . '/api/config.php';

try {
    $pdo = get_db();
    
    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = 1");
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "✓ Utilisateur ID 1 existe déjà\n";
    } else {
        // Créer l'utilisateur
        $password = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (id, username, email, password_hash, role, points, status, created_at, updated_at)
            VALUES (1, 'testuser', 'test@test.com', ?, 'player', 0, 'active', NOW(), NOW())
        ");
        $stmt->execute([$password]);
        echo "✓ Utilisateur de test créé\n";
        echo "  Email: test@test.com\n";
        echo "  Password: test123\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
}
