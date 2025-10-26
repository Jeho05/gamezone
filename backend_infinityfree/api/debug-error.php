<?php
// Debug endpoint - shows PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: text/plain');

echo "=== PHP ERROR DEBUG ===\n\n";

try {
    echo "1. Loading config.php...\n";
    require_once __DIR__ . '/config.php';
    echo "✅ config.php loaded\n\n";
    
    echo "2. Session status: " . (session_status() === PHP_SESSION_ACTIVE ? "ACTIVE" : "NONE") . "\n\n";
    
    echo "3. Session config:\n";
    echo "   - cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";
    echo "   - cookie_secure: " . ini_get('session.cookie_secure') . "\n";
    echo "   - cookie_httponly: " . ini_get('session.cookie_httponly') . "\n\n";
    
    echo "4. Environment detection:\n";
    echo "   - RAILWAY_ENVIRONMENT: " . (getenv('RAILWAY_ENVIRONMENT') ?: 'NOT_SET') . "\n";
    echo "   - HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT_SET') . "\n";
    $isRailway = isset($_SERVER['RAILWAY_ENVIRONMENT']) || 
                 (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'railway.app') !== false);
    echo "   - Detected as Railway: " . ($isRailway ? 'YES' : 'NO') . "\n\n";
    
    echo "5. Testing DB connection...\n";
    $pdo = get_db();
    echo "✅ DB connection successful\n\n";
    
    echo "6. Session data:\n";
    echo "   - User logged in: " . (isset($_SESSION['user']) ? 'YES' : 'NO') . "\n";
    if (isset($_SESSION['user'])) {
        echo "   - User: " . json_encode($_SESSION['user'], JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n=== NO ERRORS DETECTED ===\n";
    
} catch (Throwable $e) {
    echo "\n❌ ERROR CAUGHT:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
