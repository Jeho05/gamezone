<?php
// api/rewards/redeem.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

require_method(['POST']);

$user = require_auth();
$input = get_json_input();
$rewardId = (int)($input['reward_id'] ?? 0);

if ($rewardId <= 0) {
    json_response(['error' => 'Paramètre reward_id manquant'], 400);
}

$pdo = get_db();
$pdo->beginTransaction();

try {
    // Load reward avec reward_type et game_time_minutes
    $stmt = $pdo->prepare('SELECT id, name, cost, available, max_per_user, reward_type, game_time_minutes FROM rewards WHERE id = ? FOR UPDATE');
    $stmt->execute([$rewardId]);
    $reward = $stmt->fetch();
    
    if (!$reward || (int)$reward['available'] !== 1) {
        $pdo->rollBack();
        json_response(['error' => 'Récompense indisponible'], 404);
    }

    // Vérifier le nombre de rachats pour cet utilisateur
    if ($reward['max_per_user']) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = ? AND user_id = ?');
        $stmt->execute([$rewardId, $user['id']]);
        $userRedemptions = (int)$stmt->fetchColumn();
        
        if ($userRedemptions >= $reward['max_per_user']) {
            $pdo->rollBack();
            json_response(['error' => 'Limite de rachats atteinte pour cette récompense'], 409);
        }
    }

    // Load user points
    $stmt = $pdo->prepare('SELECT id, points FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([(int)$user['id']]);
    $u = $stmt->fetch();
    
    if (!$u) {
        $pdo->rollBack();
        json_response(['error' => 'Utilisateur introuvable'], 404);
    }

    $cost = (int)$reward['cost'];
    if ((int)$u['points'] < $cost) {
        $pdo->rollBack();
        json_response(['error' => 'Points insuffisants'], 409);
    }

    // Deduct points
    $stmt = $pdo->prepare('UPDATE users SET points = points - ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$cost, now(), (int)$user['id']]);

    // Log redemption
    $stmt = $pdo->prepare('INSERT INTO reward_redemptions (reward_id, user_id, cost, status, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$rewardId, (int)$user['id'], $cost, 'pending', now()]);
    $redemptionId = $pdo->lastInsertId();

    // Log points transaction
    $stmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, admin_id, created_at) VALUES (?, ?, ?, ?, NULL, ?)');
    $stmt->execute([(int)$user['id'], -$cost, 'Échange récompense: ' . $reward['name'], 'reward', now()]);

    // Update user_stats.total_points_spent
    $stmt = $pdo->prepare('INSERT INTO user_stats (user_id, total_points_spent, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE total_points_spent = total_points_spent + ?, updated_at = ?');
    $stmt->execute([(int)$user['id'], $cost, now(), $cost, now()]);

    // Si c'est une récompense de type game_time, créer une conversion de temps
    $gameTimeAdded = 0;
    if ($reward['reward_type'] === 'game_time' && $reward['game_time_minutes'] > 0) {
        $gameTimeAdded = (int)$reward['game_time_minutes'];
        
        // Charger la config de conversion pour la durée d'expiration
        $stmt = $pdo->query('SELECT converted_time_expiry_days FROM point_conversion_config WHERE id = 1');
        $conversionConfig = $stmt->fetch();
        $expiryDays = $conversionConfig ? (int)$conversionConfig['converted_time_expiry_days'] : 30;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));
        
        // Créer une entrée dans point_conversions
        $stmt = $pdo->prepare('
            INSERT INTO point_conversions (
                user_id, points_spent, minutes_gained, game_id,
                conversion_rate, fee_charged, status,
                created_at, expires_at
            ) VALUES (?, ?, ?, NULL, 0, 0, ?, NOW(), ?)
        ');
        $stmt->execute([
            (int)$user['id'],
            $cost,
            $gameTimeAdded,
            'active',
            $expiresAt
        ]);
        
        // Log dans points_transactions pour traçabilité
        // Utilise le type 'reward' (supporté par l'ENUM) pour éviter les erreurs SQL
        $stmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, admin_id, created_at) VALUES (?, ?, ?, ?, NULL, ?)');
        $stmt->execute([
            (int)$user['id'],
            $gameTimeAdded,
            "Temps de jeu ajouté via récompense: {$reward['name']} (+{$gameTimeAdded} min)",
            'reward',
            now()
        ]);
    }

    // Mettre à jour la session avec les nouveaux points
    $_SESSION['user']['points'] = (int)$u['points'] - $cost;

    $pdo->commit();
    
    $message = 'Récompense échangée avec succès';
    if ($gameTimeAdded > 0) {
        $hours = floor($gameTimeAdded / 60);
        $mins = $gameTimeAdded % 60;
        $timeStr = $hours > 0 ? "{$hours}h" : "";
        $timeStr .= $mins > 0 ? "{$mins}min" : "";
        $message = "Récompense échangée ! +{$timeStr} de jeu ajoutés";
    }
    
    json_response([
        'success' => true,
        'message' => $message,
        'reward' => [
            'id' => (int)$reward['id'],
            'name' => $reward['name'],
            'cost' => $cost,
            'type' => $reward['reward_type']
        ],
        'redemption_id' => $redemptionId,
        'new_balance' => (int)$u['points'] - $cost,
        'game_time_added' => $gameTimeAdded
    ]);
    
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response([
        'success' => false,
        'error' => 'Échec de l\'échange',
        'details' => $e->getMessage()
    ], 500);
}
