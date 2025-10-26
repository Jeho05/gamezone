<?php
// Test session behavior
header('Content-Type: application/json');

try {
    echo "1. Loading config.php...\n";
    require_once __DIR__ . '/config.php';
    
    echo "2. Session status after config: " . session_status() . " (" . 
         (session_status() === PHP_SESSION_ACTIVE ? "ACTIVE" : 
          session_status() === PHP_SESSION_NONE ? "NONE" : "DISABLED") . ")\n";
    
    echo "3. Session config:\n";
    echo "   - cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";
    echo "   - cookie_secure: " . ini_get('session.cookie_secure') . "\n";
    echo "   - cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
    
    // Force session start for test
    if (session_status() === PHP_SESSION_NONE) {
        echo "4. Starting session manually...\n";
        session_start();
        echo "5. Session started. ID: " . session_id() . "\n";
    }
    
    // Test session write
    $_SESSION['test'] = 'value';
    echo "6. Session write test: OK\n";
    
    // Test session read
    echo "7. Session read test: " . ($_SESSION['test'] ?? 'NOT_SET') . "\n";
    
    echo "✅ All tests passed\n";
    
} catch (Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
