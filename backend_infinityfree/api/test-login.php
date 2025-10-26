<?php
// Test login directly
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Force POST data for testing
$_POST['email'] = 'admin@gmail.com';
$_POST['password'] = 'demo123';

try {
    $pdo = get_db();
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Get user
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found',
            'email_tested' => $email
        ]);
        exit;
    }
    
    // Test password
    $passwordMatch = password_verify($password, $user['password_hash']);
    
    echo json_encode([
        'status' => $passwordMatch ? 'success' : 'failed',
        'user_found' => true,
        'password_match' => $passwordMatch,
        'user_details' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'hash_info' => [
            'hash_preview' => substr($user['password_hash'], 0, 30) . '...',
            'hash_length' => strlen($user['password_hash']),
            'password_tested' => $password
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Test failed',
        'details' => $e->getMessage()
    ]);
}
