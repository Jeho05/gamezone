<?php
require_once __DIR__ . '/../utils.php';
require_method(['GET', 'POST']);
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Admin only for listing users
    try {
        $user = require_auth();
        if (!is_admin($user)) {
            http_response_code(403);
            json_response(['error' => 'Accès refusé - Admin uniquement'], 403);
        }
    } catch (Exception $e) {
        http_response_code(401);
        json_response(['error' => 'Non authentifié', 'details' => $e->getMessage()], 401);
    }
    
    // List users with optional filters
    $q = trim($_GET['q'] ?? '');
    $status = $_GET['status'] ?? '';
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(username LIKE ? OR email LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like; $params[] = $like;
    }
    if ($status !== '') {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Déterminer dynamiquement les colonnes disponibles
    try {
        $columnsStmt = $pdo->query('SHOW COLUMNS FROM users');
        $existingColumns = array_map(static fn($row) => $row['Field'], $columnsStmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        $existingColumns = [];
    }

    $selectParts = [
        'id',
        'username',
        'email',
        'role'
    ];

    $selectParts[] = in_array('avatar_url', $existingColumns, true)
        ? 'avatar_url'
        : 'NULL AS avatar_url';
    $selectParts[] = in_array('points', $existingColumns, true)
        ? 'points'
        : '0 AS points';
    $selectParts[] = in_array('level', $existingColumns, true)
        ? 'level'
        : "NULL AS level";
    $selectParts[] = in_array('status', $existingColumns, true)
        ? 'status'
        : "'active' AS status";
    $selectParts[] = in_array('join_date', $existingColumns, true)
        ? 'join_date'
        : 'NULL AS join_date';
    $selectParts[] = in_array('last_active', $existingColumns, true)
        ? 'last_active'
        : 'NULL AS last_active';

    $selectSql = 'SQL_CALC_FOUND_ROWS ' . implode(', ', $selectParts);

    try {
        $stmt = $pdo->prepare("SELECT $selectSql FROM users $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();

        $items = array_map(static function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'username' => $row['username'] ?? '',
                'email' => $row['email'] ?? '',
                'role' => $row['role'] ?? 'player',
                'avatar_url' => $row['avatar_url'] ?? null,
                'points' => isset($row['points']) ? (int)$row['points'] : 0,
                'level' => $row['level'] ?? 'Gamer',
                'status' => $row['status'] ?? 'active',
                'join_date' => $row['join_date'] ?? null,
                'last_active' => $row['last_active'] ?? null,
            ];
        }, $rows);

        json_response(['items' => $items, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
    } catch (PDOException $e) {
        http_response_code(500);
        json_response(['error' => 'Erreur base de données', 'details' => $e->getMessage()], 500);
    }
}

// POST create user (admin only)
$admin = require_auth('admin');
$input = get_json_input();
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = (string)($input['password'] ?? '');
$role = ($input['role'] ?? 'player') === 'admin' ? 'admin' : 'player';
$statusVal = ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

if ($username === '' || !validate_email($email) || strlen($password) < 6) {
    json_response(['error' => 'Données invalides'], 400);
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(['error' => 'Email déjà utilisé'], 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$now = now();
$joinDate = date('Y-m-d');
$stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, points, status, join_date, created_at, updated_at) VALUES (?,?,?,?,0,?,?,?,?)');
$stmt->execute([$username, $email, $hash, $role, $statusVal, $joinDate, $now, $now]);
$id = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare('SELECT id, username, email, role, avatar_url, points, level, status, join_date, last_active FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
json_response(['message' => 'Utilisateur créé', 'user' => $user], 201);
