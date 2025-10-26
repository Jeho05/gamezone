<?php
// Check if admin account exists in database
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $pdo = get_db();
    
    // Check if users table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    
    if (empty($tables)) {
        echo json_encode([
            'error' => 'Table users does not exist',
            'suggestion' => 'Run install.php first'
        ]);
        exit;
    }
    
    // Get all users
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users");
    $users = $stmt->fetchAll();
    
    // Check specifically for admin account
    $adminStmt = $pdo->prepare("SELECT id, username, email, role, password_hash, created_at FROM users WHERE email = ?");
    $adminStmt->execute(['admin@gmail.com']);
    $admin = $adminStmt->fetch();
    
    // Test password hash
    $testPassword = 'demo123';
    $passwordMatch = false;
    if ($admin) {
        $passwordMatch = password_verify($testPassword, $admin['password_hash']);
    }
    
    echo json_encode([
        'total_users' => count($users),
        'all_users' => $users,
        'admin_exists' => $admin !== false,
        'admin_details' => $admin ? [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'role' => $admin['role'],
            'password_hash_length' => strlen($admin['password_hash']),
            'password_hash_preview' => substr($admin['password_hash'], 0, 20) . '...',
            'created_at' => $admin['created_at']
        ] : null,
        'password_test' => [
            'test_password' => $testPassword,
            'hash_matches' => $passwordMatch
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
}
