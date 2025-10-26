<?php
// api/config.php
// Database and CORS/session bootstrap

// Load middleware
require_once __DIR__ . '/middleware/error_handler.php';
require_once __DIR__ . '/middleware/logger.php';
require_once __DIR__ . '/middleware/security.php';
require_once __DIR__ . '/middleware/cache.php';

// Load environment from .env files if present (Railway/Local)
// Tries project root then api directory
$__envCandidates = [
  dirname(__DIR__) . '/.env.railway',
  dirname(__DIR__) . '/.env',
  __DIR__ . '/.env.railway',
  __DIR__ . '/.env'
];
foreach ($__envCandidates as $__envPath) {
  if (is_file($__envPath)) {
    $lines = @file($__envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
      foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
          list($k, $v) = explode('=', $line, 2);
          $k = trim($k);
          $v = trim($v);
          putenv("$k=$v");
          $_ENV[$k] = $v;
        }
      }
    }
    break; // first found
  }
}

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
  $sameSite = getenv('SESSION_SAMESITE') ?: 'Lax';
  ini_set('session.cookie_samesite', $sameSite);
  $secure = getenv('SESSION_SECURE') === '1' ? '1' : '0';
  ini_set('session.cookie_secure', $secure);
  
  // Augmenter la durée de vie de la session (24 heures par défaut)
  $sessionLifetime = (int)(getenv('SESSION_LIFETIME') ?: 86400); // 24 heures en secondes
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

// CORS (allow localhost and Vercel)
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:4000';
$allowedOrigins = [
  'https://gamezoneismo.vercel.app',
  'http://localhost:3000',
  'http://localhost:4000',
  'http://127.0.0.1:3000',
  'http://127.0.0.1:4000'
];

if (in_array($origin, $allowedOrigins)
    || strpos($origin, 'http://localhost') === 0
    || strpos($origin, 'http://127.0.0.1') === 0
    || strpos($origin, '.vercel.app') !== false) {
  header("Access-Control-Allow-Origin: $origin");
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');
  header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
} else {
  // Safe fallback
  header("Access-Control-Allow-Origin: https://gamezoneismo.vercel.app");
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');
  header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
}

// DB config - CONSTANTES pour éviter les problèmes de scope
if (!defined('DB_HOST')) {
    // Support Railway (MYSQL* and MYSQL_*) et fallback sur DB_* / XAMPP
    $envHost = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: getenv('DB_HOST');
    $envName = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: getenv('DB_NAME');
    $envUser = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: getenv('DB_USER');
    $envPass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: getenv('DB_PASS');
    $envPort = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: '3306';

    // Production-safe defaults for Railway if host not provided
    $appEnv = getenv('APP_ENV') ?: 'development';
    if ($appEnv === 'production') {
        if ($envHost === false || $envHost === '' || $envHost === '127.0.0.1' || $envHost === 'localhost') {
            $envHost = 'mysql.railway.internal';
        }
        if ($envName === false || $envName === '') {
            $envName = 'railway';
        }
        if ($envPort === false || $envPort === '') {
            $envPort = '3306';
        }
        if ($envUser === false || $envUser === '') {
            $envUser = 'root';
        }
    }

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
                'database' => DB_NAME,
                'user' => DB_USER,
                'pass_length' => strlen(DB_PASS)
            ]
        ]);
        exit;
    }
}
