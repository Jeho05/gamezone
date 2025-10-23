<?php
require_once __DIR__ . '/../utils.php';
require_method(['GET', 'POST']);
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Admin only for listing users
    require_auth('admin');
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

    $stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id, username, email, role, avatar_url, points, level, status, join_date, last_active FROM users $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
    json_response(['items' => $items, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
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
