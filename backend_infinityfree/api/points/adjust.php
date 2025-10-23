<?php
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

$admin = require_auth('admin');
$input = get_json_input();
$userId = (int)($input['user_id'] ?? 0);
$amount = (int)($input['amount'] ?? 0);
$reason = trim($input['reason'] ?? 'Ajustement');
$type = $input['type'] ?? 'adjustment';

if ($userId <= 0 || $amount === 0) {
    json_response(['error' => 'Paramètres invalides (user_id, amount)'], 400);
}

$pdo = get_db();
$pdo->beginTransaction();
try {
    // Ensure user exists
    $stmt = $pdo->prepare('SELECT id, points FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        $pdo->rollBack();
        json_response(['error' => 'Utilisateur introuvable'], 404);
    }

    $newPoints = (int)$user['points'] + $amount;
    if ($newPoints < 0) $newPoints = 0;

    $stmt = $pdo->prepare('UPDATE users SET points = ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$newPoints, now(), $userId]);

    $stmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $amount, $reason, $type, (int)$admin['id'], now()]);

    $pdo->commit();
    json_response(['message' => 'Points ajustés', 'user_id' => $userId, 'points' => $newPoints]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['error' => 'Échec de l\'ajustement', 'details' => $e->getMessage()], 500);
}
