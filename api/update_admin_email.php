<?php
// Update admin email from @gamezone.fr to @gmail.com
require_once __DIR__ . '/config.php';

try {
    $pdo = get_db();
    
    // Update email if exists
    $stmt = $pdo->prepare("UPDATE users SET email = 'admin@gmail.com' WHERE email = 'admin@gamezone.fr'");
    $stmt->execute();
    
    echo json_encode([
        'message' => 'Email admin mis à jour',
        'rows_affected' => $stmt->rowCount()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
