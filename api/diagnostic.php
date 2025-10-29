<?php
// api/diagnostic.php
// Diagnostic complet: session/cookies, CORS, DB, auth, endpoints clés

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');

$out = [
  'timestamp' => date('c'),
  'request' => [
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'host' => $_SERVER['HTTP_HOST'] ?? null,
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? null,
    'referer' => $_SERVER['HTTP_REFERER'] ?? null,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
  ],
  'session' => [],
  'cors' => [],
  'database' => [],
  'auth' => [],
  'endpoints' => [],
];

// 1) Session / Cookies
$out['session']['status'] = session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive';
$out['session']['id'] = session_id();
$out['session']['ini'] = [
  'cookie_path' => ini_get('session.cookie_path'),
  'cookie_httponly' => ini_get('session.cookie_httponly'),
  'cookie_samesite' => ini_get('session.cookie_samesite'),
  'cookie_secure' => ini_get('session.cookie_secure'),
  'save_path' => ini_get('session.save_path'),
  'gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
];
$out['session']['server_cookie_header'] = $_SERVER['HTTP_COOKIE'] ?? null;
// Test d'écriture session
$_SESSION['__diag_counter'] = ($_SESSION['__diag_counter'] ?? 0) + 1;
$out['session']['write_read_ok'] = $_SESSION['__diag_counter'] > 0;

// 2) CORS - Refléter ce que renvoie config.php (ajouté par add_security_headers et CORS logic)
$out['cors']['allowed_origin_sent'] = $out['request']['origin'] ?? null;
$out['cors']['vary_origin_header'] = 'set-by-config.php';

// 3) Database
try {
  $pdo = get_db();
  $pdo->query('SELECT 1');
  $out['database']['status'] = 'connected';
  $out['database']['config'] = [
    'host' => DB_HOST,
    'port' => DB_PORT,
    'name' => DB_NAME,
    'user' => DB_USER,
  ];
  // Tables critiques présentes ? (sans planter si manquantes)
  $critical = ['users','purchases','game_sessions','points_transactions','rewards'];
  $present = [];
  foreach ($critical as $t) {
    try {
      $pdo->query("SELECT 1 FROM `{$t}` LIMIT 1");
      $present[$t] = true;
    } catch (Throwable $e) {
      $present[$t] = false;
    }
  }
  $out['database']['critical_tables'] = $present;
} catch (Throwable $e) {
  $out['database']['status'] = 'error';
  $out['database']['error'] = $e->getMessage();
}

// 4) Auth / Current user
$out['auth']['session_user'] = $_SESSION['user'] ?? null;
try {
  $me = null;
  if (isset($_SESSION['user']['id'])) {
    $stmt = $pdo->prepare('SELECT id, username, email, role, last_active FROM users WHERE id = ?');
    $stmt->execute([(int)$_SESSION['user']['id']]);
    $me = $stmt->fetch();
  }
  $out['auth']['me_db'] = $me;
} catch (Throwable $e) {
  $out['auth']['me_error'] = $e->getMessage();
}

// 5) Endpoints clés (vérifications internes)
$endpoints = [
  'auth_me' => function() { require __DIR__ . '/auth/me.php'; },
];
// On ne les exécute pas directement pour éviter de casser la sortie JSON ;
// on simule juste l'accessibilité logique via require_auth.
$out['endpoints']['admin_access_possible'] = is_admin() ? true : false;

// 6) Résumé
$out['summary'] = [
  'is_logged_in' => isset($_SESSION['user']),
  'is_admin' => is_admin(),
  'db_ok' => ($out['database']['status'] ?? null) === 'connected',
  'cookies_received' => !empty($out['session']['server_cookie_header']),
  'session_cookie_config_ok' => (
    (string)ini_get('session.cookie_samesite') === 'None' && (string)ini_get('session.cookie_secure') === '1'
  ),
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>


