<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
$cookieName = session_name();
$cookiePresent = isset($_COOKIE[$cookieName]);
$cookiesRaw = $_SERVER['HTTP_COOKIE'] ?? '';

$user = $_SESSION['user'] ?? null;

// Compute the CORS origin value this request would get per config.php logic
$allowedOrigins = [
  'https://gamezoneismo.vercel.app',
  'http://localhost:3000',
  'http://localhost:4000',
  'http://127.0.0.1:3000',
  'http://127.0.0.1:4000'
];
$computedAllowedOrigin = 'https://gamezoneismo.vercel.app';
if ($origin) {
  if (in_array($origin, $allowedOrigins)
      || strpos($origin, 'http://localhost') === 0
      || strpos($origin, 'http://127.0.0.1') === 0
      || strpos($origin, '.vercel.app') !== false) {
    $computedAllowedOrigin = $origin;
  }
}

$data = [
  'timestamp' => date('c'),
  'request' => [
    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'host' => $_SERVER['HTTP_HOST'] ?? '',
    'origin' => $origin,
    'referer' => $_SERVER['HTTP_REFERER'] ?? '',
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
  ],
  'session' => [
    'status' => session_status(),
    'id' => session_id(),
    'cookie_name' => $cookieName,
    'cookie_present' => $cookiePresent,
    'cookie_raw_length' => strlen($cookiesRaw),
    'cookie_value_sample' => $cookiePresent ? (substr($_COOKIE[$cookieName], 0, 8) . '...') : null,
    'cookie_samesite' => ini_get('session.cookie_samesite'),
    'cookie_secure' => ini_get('session.cookie_secure'),
    'cookie_path' => ini_get('session.cookie_path'),
    'cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'save_handler' => ini_get('session.save_handler'),
    'save_path' => ini_get('session.save_path'),
    'session_file_exists' => (function(){
      $handler = ini_get('session.save_handler');
      $path = ini_get('session.save_path');
      if ($handler === 'files' && $path && session_id()) {
        $file = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . 'sess_' . session_id();
        return file_exists($file);
      }
      return null;
    })(),
    'user_present' => (bool)$user,
    'user' => $user ? [
      'id' => $user['id'] ?? null,
      'email' => $user['email'] ?? null,
      'role' => $user['role'] ?? null
    ] : null,
    'last_regeneration' => $_SESSION['last_regeneration'] ?? null,
    'last_activity_update' => $_SESSION['last_activity_update'] ?? null,
  ],
  'cors' => [
    'computed_allow_origin' => $computedAllowedOrigin,
    'allow_credentials' => true
  ],
  'db' => [
    'ok' => null,
    'error' => null
  ],
  'deployment' => [
    // In container, config.php is one level above /admin when endpoints are flattened at web root
    'endpoints_flattened_to_root' => file_exists(__DIR__ . '/../config.php'),
    // Whether an /api prefix exists alongside (unlikely in flattened deploy)
    'api_prefix_exists' => is_dir(__DIR__ . '/../api') || file_exists(__DIR__ . '/../api/health.php')
  ],
  'hints' => []
];

try {
  $pdo = get_db();
  $pdo->query('SELECT 1');
  $data['db']['ok'] = true;
} catch (Throwable $e) {
  $data['db']['ok'] = false;
  $data['db']['error'] = $e->getMessage();
}

// High-level hints
if (!$cookiePresent) {
  $data['hints'][] = 'No session cookie received. Ensure frontend fetch uses credentials: "include" and that the site is served over HTTPS.';
}
if (!$user) {
  $data['hints'][] = 'No authenticated user in session. If you expected to be logged in, check that login response sets a cookie with SameSite=None; Secure and that the browser did not block it.';
}
if ($data['deployment']['endpoints_flattened_to_root'] && !$data['deployment']['api_prefix_exists']) {
  $data['hints'][] = 'Endpoints are served at / (e.g. /admin/...) in production. Requests to /api/... should be rewritten to /...';
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
