<?php
// api/config.php
// Database and CORS/session bootstrap

// Load .env.railway FIRST before anything else
$railwayEnv = __DIR__ . '/.env.railway';
$envVars = []; // Store parsed values
if (file_exists($railwayEnv)) {
    $lines = file($railwayEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $envVars[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Load middleware
require_once __DIR__ . '/middleware/error_handler.php';
require_once __DIR__ . '/middleware/logger.php';
require_once __DIR__ . '/middleware/security.php';
require_once __DIR__ . '/middleware/cache.php';

// Add security headers
add_security_headers();

// Log API request (except for test.php to avoid noise)
if (!strpos($_SERVER['REQUEST_URI'] ?? '', 'test.php')) {
    Logger::logRequest();
}

// Handle OPTIONS immediately before any other processing
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:4000';
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    http_response_code(204);
    exit;
}

// Start session with improved configuration
if (session_status() === PHP_SESSION_NONE) {
  // Ensure cookies are available across the site
  ini_set('session.cookie_path', '/');
  
  // Harden cookies: HttpOnly, SameSite (configurable), and Secure in prod
  ini_set('session.cookie_httponly', '1');
  
  // CRITICAL: Force None for cross-site cookies (Railway production)
  // Check if we're on Railway (production) or localhost (dev)
  $isProduction = isset($_SERVER['RAILWAY_ENVIRONMENT']) || 
                  (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'railway.app') !== false);
  
  if ($isProduction) {
    // Railway production: MUST use None for cross-site
    $sameSite = 'None';
    $secure = '1';
  } else {
    // Local dev: use env vars or defaults
    $sameSite = $envVars['SESSION_SAMESITE'] ?? getenv('SESSION_SAMESITE') ?: 'Lax';
    $secure = ($envVars['SESSION_SECURE'] ?? getenv('SESSION_SECURE')) === '1' ? '1' : '0';
  }
  
  ini_set('session.cookie_samesite', $sameSite);
  ini_set('session.cookie_secure', $secure);
  
  // Augmenter la durée de vie de la session (24 heures par défaut)
  $sessionLifetime = (int)($envVars['SESSION_LIFETIME'] ?? getenv('SESSION_LIFETIME') ?: 86400); // 24 heures en secondes
  ini_set('session.gc_maxlifetime', $sessionLifetime);
  ini_set('session.cookie_lifetime', $sessionLifetime);
  
  // Améliorer la gestion de la garbage collection
  ini_set('session.gc_probability', '1');
  ini_set('session.gc_divisor', '100');
  
  session_start();
  
  // Régénérer l'ID de session périodiquement pour la sécurité (toutes les 30 minutes)
  if (isset($_SESSION['last_regeneration'])) {
    $timeElapsed = time() - $_SESSION['last_regeneration'];
    if ($timeElapsed > 1800) { // 30 minutes
      session_regenerate_id(true);
      $_SESSION['last_regeneration'] = time();
    }
  } else {
    $_SESSION['last_regeneration'] = time();
  }
  
  // Mettre à jour l'activité de l'utilisateur si connecté
  if (isset($_SESSION['user']['id'])) {
    // Mettre à jour last_active toutes les 5 minutes seulement pour réduire la charge DB
    $shouldUpdate = true;
    if (isset($_SESSION['last_activity_update'])) {
      $timeSinceUpdate = time() - $_SESSION['last_activity_update'];
      $shouldUpdate = $timeSinceUpdate > 300; // 5 minutes
    }
    
    if ($shouldUpdate) {
      try {
        $pdo = get_db();
        $stmt = $pdo->prepare('UPDATE users SET last_active = NOW() WHERE id = ?');
        $stmt->execute([(int)$_SESSION['user']['id']]);
        $_SESSION['last_activity_update'] = time();
      } catch (Throwable $e) {
        // Non-fatal, continuer
      }
    }
  }
}

// CORS (allow common localhost ports for dev - NEVER use *)
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:4000';

// Liste des origines autorisées
$allowedOrigins = [
    'http://localhost:4000',
    'http://localhost:4001',
    'http://localhost:3000',
    'http://127.0.0.1:4000',
    'http://127.0.0.1:4001',
    'http://127.0.0.1:3000',
    'https://gamezoneismo.vercel.app'
];

// Vérifier si l'origine est autorisée
if (in_array($origin, $allowedOrigins) || 
    strpos($origin, 'http://localhost') === 0 || 
    strpos($origin, 'http://127.0.0.1') === 0 ||
    strpos($origin, '.vercel.app') !== false) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization, X-CSRF-Token');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
} else {
    // Fallback to localhost:4000 for direct access
    header("Access-Control-Allow-Origin: http://localhost:4000");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization, X-CSRF-Token');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
}

// DB config - Support Railway.app and local environments
if (!defined('DB_HOST')) {
    // Railway.app uses MYSQLHOST, MYSQLDATABASE, etc.
    $envHost = getenv('MYSQLHOST') ?: getenv('DB_HOST');
    $envName = getenv('MYSQLDATABASE') ?: getenv('DB_NAME');
    $envUser = getenv('MYSQLUSER') ?: getenv('DB_USER');
    $envPass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS');
    $envPort = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
    
    define('DB_HOST', ($envHost !== false && $envHost !== '') ? $envHost : '127.0.0.1');
    define('DB_NAME', ($envName !== false && $envName !== '') ? $envName : 'gamezone');
    define('DB_USER', ($envUser !== false && $envUser !== '') ? $envUser : 'root');
    define('DB_PASS', ($envPass !== false && $envPass !== '') ? $envPass : '');
    define('DB_PORT', $envPort);
}

function get_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Database connection failed',
            'details' => $e->getMessage(),
            'debug' => [
                'host' => DB_HOST,
                'port' => DB_PORT,
                'database' => DB_NAME,
                'user' => DB_USER,
                'pass_length' => strlen(DB_PASS)
            ]
        ]);
        exit;
    }
}
