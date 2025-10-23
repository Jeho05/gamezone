<?php
require_once __DIR__ . '/../utils.php';
require_method(['GET']);

$auth = require_auth();
$pdo = get_db();

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)$auth['id'];
$limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

// Non-admins can only see their own history
if (($auth['role'] ?? 'player') !== 'admin' && $userId !== (int)$auth['id']) {
    json_response(['error' => 'Forbidden'], 403);
}

$stmt = $pdo->prepare('SELECT pt.id, pt.change_amount AS points, pt.reason, pt.type, pt.created_at
  FROM points_transactions pt
  WHERE pt.user_id = ?
  ORDER BY pt.created_at DESC, pt.id DESC
  LIMIT ' . $limit . ' OFFSET ' . $offset);
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

json_response(['items' => $items, 'limit' => $limit, 'offset' => $offset]);
