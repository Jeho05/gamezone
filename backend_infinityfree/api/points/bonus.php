<?php
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

$user = require_auth(); // any logged-in user
$userId = (int)$user['id'];
$bonusPoints = 25;
$today = date('Y-m-d');

$pdo = get_db();
$pdo->beginTransaction();
try {
    // Check last claim
    $stmt = $pdo->prepare('SELECT last_claim_date FROM daily_bonuses WHERE user_id = ? FOR UPDATE');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row && $row['last_claim_date'] === $today) {
        $pdo->rollBack();
        json_response(['error' => 'Bonus déjà réclamé aujourd\'hui'], 409);
    }

    if ($row) {
        $stmt = $pdo->prepare('UPDATE daily_bonuses SET last_claim_date = ? WHERE user_id = ?');
        $stmt->execute([$today, $userId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO daily_bonuses (user_id, last_claim_date) VALUES (?, ?)');
        $stmt->execute([$userId, $today]);
    }

    // Update user points
    $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$bonusPoints, now(), $userId]);

    // Log transaction
    $stmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, admin_id, created_at) VALUES (?, ?, ?, ?, NULL, ?)');
    $stmt->execute([$userId, $bonusPoints, 'Bonus journalier', 'bonus', now()]);

    $pdo->commit();
    json_response(['message' => 'Bonus réclamé', 'awarded' => $bonusPoints]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['error' => 'Échec du bonus', 'details' => $e->getMessage()], 500);
}
