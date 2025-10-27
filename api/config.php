<?php
// api/config.php
// Database and CORS/session bootstrap

// Load middleware
require_once __DIR__ . '/middleware/error_handler.php';
require_once __DIR__ . '/middleware/logger.php';
require_once __DIR__ . '/middleware/security.php';
require_once __DIR__ . '/middleware/cache.php';

// Helper to read env variables robustly (getenv, $_ENV, $_SERVER)
function envval(string $key): ?string {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    return null;
}

// Load environment from .env files only in non-production to avoid overriding Railway envs
$__appEnv = envval('APP_ENV') ?: 'development';
if ($__appEnv !== 'production') {
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
    header('Vary: Origin');
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
  header('Vary: Origin');
} else {
  // Safe fallback
  header("Access-Control-Allow-Origin: https://gamezoneismo.vercel.app");
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');
  header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
  header('Vary: Origin');
}

// DB config - CONSTANTES pour éviter les problèmes de scope
if (!defined('DB_HOST')) {
    // Prefer DATABASE_URL-like envs (mysql://user:pass@host:port/dbname)
    $envHost = $envName = $envUser = $envPass = $envPort = null;
    $dbUrl = envval('DATABASE_URL') ?: envval('JAWSDB_URL') ?: envval('CLEARDB_DATABASE_URL');
    if ($dbUrl) {
        $parts = parse_url($dbUrl);
        if ($parts !== false) {
            $envHost = $parts['host'] ?? null;
            $envPort = isset($parts['port']) ? (string)$parts['port'] : null;
            $envUser = $parts['user'] ?? null;
            $envPass = $parts['pass'] ?? null;
            $path = $parts['path'] ?? '';
            if ($path && $path[0] === '/') $path = substr($path, 1);
            $envName = $path ?: null;
        }
    }
    // Otherwise support Railway MYSQL* and MYSQL_* and fallback DB_*
    if (!$envHost) $envHost = envval('MYSQLHOST') ?: envval('MYSQL_HOST') ?: envval('DB_HOST');
    if (!$envName) $envName = envval('MYSQLDATABASE') ?: envval('MYSQL_DATABASE') ?: envval('DB_NAME');
    if (!$envUser) $envUser = envval('MYSQLUSER') ?: envval('MYSQL_USER') ?: envval('DB_USER');
    if (!$envPass) $envPass = envval('MYSQLPASSWORD') ?: envval('MYSQL_PASSWORD') ?: envval('DB_PASS');
    if (!$envPort) $envPort = envval('MYSQLPORT') ?: envval('MYSQL_PORT') ?: envval('DB_PORT') ?: '3306';

    // Helper to choose first non-empty value
    $firstNonEmpty = function($value, $default) {
        if ($value === null) return $default;
        if ($value === false) return $default;
        if (is_string($value) && $value === '') return $default;
        return $value;
    };

    // Detect Railway environment
    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    $isRailway = (bool)(envval('RAILWAY_ENVIRONMENT')
        || strpos($httpHost, 'railway.app') !== false
        || strpos($httpHost, 'up.railway.app') !== false);

    // Production-safe defaults for Railway if host not provided
    $appEnv = envval('APP_ENV') ?: 'development';
    if ($appEnv === 'production' || $isRailway) {
        // If still missing/localhost, try to parse a deployed .env.railway/.env once
        $needsEnvParse = ($envHost === null || $envHost === false || $envHost === '' || $envHost === '127.0.0.1' || $envHost === 'localhost');
        if ($needsEnvParse) {
            $candidates = [
                dirname(__DIR__) . '/.env.railway',
                dirname(__DIR__) . '/.env',
                __DIR__ . '/.env.railway',
                __DIR__ . '/.env'
            ];
            foreach ($candidates as $p) {
                if (is_file($p)) {
                    $lines = @file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if ($lines !== false) {
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if ($line === '' || $line[0] === '#') continue;
                            if (strpos($line, '=') !== false) {
                                list($k, $v) = explode('=', $line, 2);
                                $k = trim($k); $v = trim($v);
                                putenv("$k=$v");
                                $_ENV[$k] = $v;
                                $_SERVER[$k] = $v;
                            }
                        }
                    }
                    break;
                }
            }
            // Re-resolve after parsing
            $envHost = envval('MYSQLHOST') ?: envval('MYSQL_HOST') ?: envval('DB_HOST') ?: $envHost;
            $envName = envval('MYSQLDATABASE') ?: envval('MYSQL_DATABASE') ?: envval('DB_NAME') ?: $envName;
            $envUser = envval('MYSQLUSER') ?: envval('MYSQL_USER') ?: envval('DB_USER') ?: $envUser;
            $envPass = envval('MYSQLPASSWORD') ?: envval('MYSQL_PASSWORD') ?: envval('DB_PASS') ?: $envPass;
            $envPort = envval('MYSQLPORT') ?: envval('MYSQL_PORT') ?: envval('DB_PORT') ?: $envPort;
        }
        if ($envHost === null || $envHost === false || $envHost === '' || $envHost === '127.0.0.1' || $envHost === 'localhost') {
            $envHost = 'mysql.railway.internal';
        }
        if ($envName === null || $envName === false || $envName === '') {
            $envName = 'railway';
        }
        if ($envPort === null || $envPort === false || $envPort === '') {
            $envPort = '3306';
        }
        if ($envUser === null || $envUser === false || $envUser === '') {
            $envUser = 'root';
        }
    }

    define('DB_HOST', $firstNonEmpty($envHost, '127.0.0.1'));
    define('DB_NAME', $firstNonEmpty($envName, 'gamezone'));
    define('DB_USER', $firstNonEmpty($envUser, 'root'));
    define('DB_PASS', $firstNonEmpty($envPass, ''));
    define('DB_PORT', $firstNonEmpty($envPort, '3306'));
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
