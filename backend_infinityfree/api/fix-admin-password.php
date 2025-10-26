<?php
// Fix admin password by regenerating hash
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $pdo = get_db();
    
    // Generate new hash for 'demo123'
    $newPassword = 'demo123';
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update admin account
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $result = $stmt->execute([$newHash, 'admin@gmail.com']);
    
    // Verify the update worked
    $checkStmt = $pdo->prepare("SELECT password_hash FROM users WHERE email = ?");
    $checkStmt->execute(['admin@gmail.com']);
    $admin = $checkStmt->fetch();
    
    $verifyWorks = password_verify($newPassword, $admin['password_hash']);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Admin password updated',
        'new_password' => $newPassword,
        'new_hash_preview' => substr($newHash, 0, 30) . '...',
        'update_successful' => $result,
        'verification_test' => $verifyWorks ? 'PASS ✅' : 'FAIL ❌'
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update password',
        'details' => $e->getMessage()
    ]);
}
