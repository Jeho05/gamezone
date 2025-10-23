<?php
require_once __DIR__ . '/../utils.php';
require_method(['GET']);

$user = current_user();
if (!$user) {
    json_response(['user' => null], 200);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, username, email, role, avatar_url, points, level, status, last_active, created_at FROM users WHERE id = ?');
$stmt->execute([(int)$user['id']]);
$u = $stmt->fetch();
if (!$u) {
    json_response(['user' => null], 200);
}
if ($u && isset($u['created_at'])) {
    $u['member_since'] = (new DateTime($u['created_at']))->format(DATE_ATOM);
}
json_response(['user' => $u]);
