<?php
// Create admin account with admin@gmail.com
require_once __DIR__ . '/config.php';

try {
    $pdo = get_db();
    
    // Delete old admin if exists
    $pdo->exec("DELETE FROM users WHERE email IN ('admin@gamezone.fr', 'admin@gmail.com')");
    
    // Create new admin
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, role, points, level, status, join_date, last_active, created_at, updated_at)
        VALUES ('Admin', 'admin@gmail.com', ?, 'admin', 0, 'Admin', 'active', CURDATE(), NOW(), NOW(), NOW())
    ");
    
    // Password: demo123
    $password_hash = password_hash('demo123', PASSWORD_BCRYPT);
    $stmt->execute([$password_hash]);
    
    echo json_encode([
        'message' => 'Compte admin créé',
        'email' => 'admin@gmail.com',
        'password' => 'demo123'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
