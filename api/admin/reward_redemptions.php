<?php
// api/admin/reward_redemptions.php
// Suivi et gestion des échanges de récompenses (admin)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$admin = require_auth('admin');
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $status = $_GET['status'] ?? null; // pending, approved, delivered, completed, cancelled
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $rewardId = isset($_GET['reward_id']) ? (int)$_GET['reward_id'] : null;

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(200, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($status) {
        $where[] = 'rr.status = ?';
        $params[] = $status;
    }
    if ($userId) {
        $where[] = 'rr.user_id = ?';
        $params[] = $userId;
    }
    if ($rewardId) {
        $where[] = 'rr.reward_id = ?';
        $params[] = $rewardId;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Compter le total
    $countSql = 'SELECT COUNT(*) FROM reward_redemptions rr ' . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Requête principale
    $sql = '
        SELECT
            rr.id,
            rr.reward_id,
            rr.user_id,
            rr.cost,
            rr.status,
            rr.notes,
            rr.created_at,
            rr.updated_at,
            u.username AS user_username,
            u.email AS user_email,
            r.name AS reward_name,
            r.description AS reward_description,
            r.reward_type,
            r.category,
            r.game_time_minutes,
            r.game_package_id,
            r.discount_percentage,
            r.discount_game_id,
            g.name AS discount_game_name
        FROM reward_redemptions rr
        INNER JOIN users u ON rr.user_id = u.id
        INNER JOIN rewards r ON rr.reward_id = r.id
        LEFT JOIN games g ON r.discount_game_id = g.id
    ' . $whereSql . ' ORDER BY rr.created_at DESC LIMIT ? OFFSET ?';

    $stmt = $pdo->prepare($sql);
    $bindIndex = 1;
    foreach ($params as $p) {
        $stmt->bindValue($bindIndex++, $p);
    }
    $stmt->bindValue($bindIndex++, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue($bindIndex, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response([
        'success' => true,
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'page_count' => $limit > 0 ? (int)ceil($total / $limit) : 1,
    ]);
}

if ($method === 'PUT' || $method === 'PATCH' || $method === 'POST') {
    // Mise à jour du status / notes pour un échange donné
    $data = get_json_input();
    $id = isset($data['id']) ? (int)$data['id'] : 0;

    if ($id <= 0) {
        json_response(['error' => 'ID de l\'échange requis'], 400);
    }

    $fields = [];
    $params = [];

    if (isset($data['status']) && $data['status'] !== '') {
        $fields[] = 'status = ?';
        $params[] = $data['status'];
    }

    if (array_key_exists('notes', $data)) {
        $fields[] = 'notes = ?';
        $params[] = ($data['notes'] === '' ? null : $data['notes']);
    }

    if (empty($fields)) {
        json_response(['error' => 'Aucune donnée à mettre à jour'], 400);
    }

    $fields[] = 'updated_at = ?';
    $params[] = now();
    $params[] = $id;

    try {
        $sql = 'UPDATE reward_redemptions SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        json_response([
            'success' => true,
            'message' => 'Échange mis à jour avec succès',
        ]);
    } catch (Throwable $e) {
        log_error('Erreur mise à jour reward_redemption', [
            'id' => $id,
            'error' => $e->getMessage(),
        ]);
        json_response([
            'success' => false,
            'error' => 'Erreur lors de la mise à jour de l\'échange',
            'details' => $e->getMessage(),
        ], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
