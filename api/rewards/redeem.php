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
    $gamePackage = null;
    $badgesEarned = [];

    // Load reward avec reward_type, temps de jeu et éventuel package de jeu
    $stmt = $pdo->prepare('SELECT id, name, cost, available, max_per_user, reward_type, game_time_minutes, game_package_id FROM rewards WHERE id = ? FOR UPDATE');
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

    // Vérifier le stock global de la récompense si défini
    if (!empty($reward['stock_quantity'])) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = ? AND status != "cancelled"');
        $stmt->execute([$rewardId]);
        $usedStock = (int)$stmt->fetchColumn();

        if ($usedStock >= (int)$reward['stock_quantity']) {
            $pdo->rollBack();
            json_response(['error' => 'Cette récompense est en rupture de stock'], 409);
        }
    }

    // Si la récompense est liée à un package de jeu, charger le package
    if ($reward['reward_type'] === 'game_package') {
        $stmt = $pdo->prepare('
            SELECT 
                pkg.id,
                pkg.game_id,
                pkg.name,
                pkg.duration_minutes,
                pkg.points_earned,
                pkg.points_cost,
                pkg.max_purchases_per_user,
                pkg.is_active,
                pkg.available_from,
                pkg.available_until,
                g.name AS game_name,
                g.is_active AS game_active
            FROM game_packages pkg
            INNER JOIN games g ON pkg.game_id = g.id
            WHERE pkg.id = ?
        ');
        $stmt->execute([$reward['game_package_id'] ?? null]);
        $gamePackage = $stmt->fetch();

        if (!$gamePackage) {
            $pdo->rollBack();
            json_response(['error' => 'Package de jeu associé introuvable'], 404);
        }

        if ((int)$gamePackage['is_active'] !== 1 || (int)$gamePackage['game_active'] !== 1) {
            $pdo->rollBack();
            json_response(['error' => 'Ce package de jeu n\'est plus disponible'], 400);
        }

        // Vérifier la fenêtre de disponibilité si définie
        $nowTs = date('Y-m-d H:i:s');
        if (!empty($gamePackage['available_from']) && $gamePackage['available_from'] > $nowTs) {
            $pdo->rollBack();
            json_response(['error' => 'Ce package n\'est pas encore disponible'], 400);
        }
        if (!empty($gamePackage['available_until']) && $gamePackage['available_until'] < $nowTs) {
            $pdo->rollBack();
            json_response(['error' => 'Ce package n\'est plus disponible'], 400);
        }

        // Limite d'achats par utilisateur pour ce package
        if (!empty($gamePackage['max_purchases_per_user'])) {
            $stmt = $pdo->prepare('
                SELECT COUNT(*) 
                FROM purchases 
                WHERE package_id = ? 
                  AND user_id = ? 
                  AND paid_with_points = 1
                  AND payment_status = "completed"
            ');
            $stmt->execute([(int)$gamePackage['id'], (int)$user['id']]);
            $userPurchases = (int)$stmt->fetchColumn();

            if ($userPurchases >= (int)$gamePackage['max_purchases_per_user']) {
                $pdo->rollBack();
                json_response([
                    'error' => 'Limite d\'achats atteinte pour ce package',
                    'max_purchases' => (int)$gamePackage['max_purchases_per_user'],
                    'current_purchases' => $userPurchases
                ], 400);
            }
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

    // Pour les packages de jeu, aligner le coût sur le package s'il est défini
    if ($reward['reward_type'] === 'game_package' && $gamePackage && isset($gamePackage['points_cost']) && (int)$gamePackage['points_cost'] > 0) {
        $cost = (int)$gamePackage['points_cost'];
    }

    if ((int)$u['points'] < $cost) {
        $pdo->rollBack();
        json_response(['error' => 'Points insuffisants'], 409);
    }

    // Deduct points
    $stmt = $pdo->prepare('UPDATE users SET points = points - ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$cost, now(), (int)$user['id']]);

    // Log redemption
    $stmt = $pdo->prepare('INSERT INTO reward_redemptions (reward_id, user_id, cost, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$rewardId, (int)$user['id'], $cost, now()]);
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

    // Si c'est une récompense de type badge, attribuer automatiquement un badge lié
    if ($reward['reward_type'] === 'badge') {
        $stmt = $pdo->prepare('SELECT * FROM badges WHERE name = ? LIMIT 1');
        $stmt->execute([$reward['name']]);
        $badge = $stmt->fetch();

        if ($badge) {
            // Vérifier si l'utilisateur a déjà ce badge
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = ?');
            $stmt->execute([(int)$user['id'], (int)$badge['id']]);
            $hasBadge = (int)$stmt->fetchColumn() > 0;

            if (!$hasBadge) {
                $ts = now();

                // Attribuer le badge
                $stmt = $pdo->prepare('INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, ?)');
                $stmt->execute([(int)$user['id'], (int)$badge['id'], $ts]);

                // Attribuer les points bonus liés au badge s'il y en a
                $badgePoints = isset($badge['points_reward']) ? (int)$badge['points_reward'] : 0;
                if ($badgePoints > 0) {
                    // Créditer les points sur l'utilisateur
                    $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
                    $stmt->execute([$badgePoints, $ts, (int)$user['id']]);

                    // Log de la transaction de points (type bonus)
                    $stmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, admin_id, created_at) VALUES (?, ?, ?, ?, NULL, ?)');
                    $stmt->execute([
                        (int)$user['id'],
                        $badgePoints,
                        "Badge débloqué via récompense: {$badge['name']}",
                        'bonus',
                        $ts
                    ]);

                    // Mettre à jour user_stats.total_points_earned
                    $stmt = $pdo->prepare('INSERT INTO user_stats (user_id, total_points_earned, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE total_points_earned = total_points_earned + ?, updated_at = ?');
                    $stmt->execute([
                        (int)$user['id'],
                        $badgePoints,
                        $ts,
                        $badgePoints,
                        $ts
                    ]);
                }

                $badgesEarned[] = [
                    'id' => (int)$badge['id'],
                    'name' => $badge['name'],
                    'description' => $badge['description'],
                    'icon' => $badge['icon'],
                    'rarity' => $badge['rarity'],
                    'points_reward' => isset($badge['points_reward']) ? (int)$badge['points_reward'] : 0,
                ];
            }
        }
    }

    // Si c'est une récompense de type game_package, créer un achat payé en points
    $purchaseData = null;
    if ($reward['reward_type'] === 'game_package' && $gamePackage) {
        $ts = now();

        $stmt = $pdo->prepare('
            INSERT INTO purchases (
                user_id, game_id, package_id,
                game_name, package_name, duration_minutes,
                price, currency, points_earned, points_credited,
                paid_with_points, points_spent,
                payment_method_id, payment_method_name,
                payment_status, session_status,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            (int)$user['id'],
            (int)$gamePackage['game_id'],
            (int)$gamePackage['id'],
            $gamePackage['game_name'],
            $gamePackage['name'],
            (int)$gamePackage['duration_minutes'],
            0.00,
            'PTS',
            (int)$gamePackage['points_earned'],
            0,
            1,
            $cost,
            null,
            'Points Fidélité',
            'completed',
            'pending',
            $ts,
            $ts
        ]);

        $purchaseId = (int)$pdo->lastInsertId();

        // Laisser le statut de reward_redemptions sur sa valeur par défaut (ex: 'pending')
        // L'admin peut ensuite le faire évoluer via l'interface dédiée.

        $purchaseData = [
            'id' => $purchaseId,
            'game_name' => $gamePackage['game_name'],
            'package_name' => $gamePackage['name'],
            'duration_minutes' => (int)$gamePackage['duration_minutes'],
        ];
    }

    // Recharger le solde après tous les ajustements (badges, etc.)
    $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
    $stmt->execute([(int)$user['id']]);
    $newBalance = (int)$stmt->fetchColumn();

    // Mettre à jour la session avec les nouveaux points
    $_SESSION['user']['points'] = $newBalance;

    $pdo->commit();
    
    $message = 'Récompense échangée avec succès';
    if ($gameTimeAdded > 0) {
        $hours = floor($gameTimeAdded / 60);
        $mins = $gameTimeAdded % 60;
        $timeStr = $hours > 0 ? "{$hours}h" : "";
        $timeStr .= $mins > 0 ? " {$mins}min" : "";
        $message = "Récompense échangée ! +{$timeStr} de jeu ajoutés";
    }

    if ($reward['reward_type'] === 'badge' && !empty($badgesEarned)) {
        $message = 'Badge débloqué !';
    } elseif ($reward['reward_type'] === 'discount') {
        $message = 'Récompense échangée ! Votre réduction sera appliquée par l\'équipe sur un prochain achat.';
    } elseif ($reward['reward_type'] === 'physical') {
        $message = 'Récompense échangée ! Merci de vous présenter en salle pour récupérer votre cadeau physique.';
    } elseif ($reward['reward_type'] === 'digital') {
        $message = 'Récompense échangée ! Votre avantage digital sera communiqué ou appliqué par l\'équipe.';
    } elseif ($reward['reward_type'] === 'item' || $reward['reward_type'] === 'other') {
        $message = 'Récompense échangée ! L\'équipe appliquera votre avantage lors de votre prochaine visite.';
    }

    if ($reward['reward_type'] === 'game_package' && $purchaseData) {
        $message = 'Récompense échangée ! Votre session de jeu est prête.';
    }

    $response = [
        'success' => true,
        'message' => $message,
        'reward' => [
            'id' => (int)$reward['id'],
            'name' => $reward['name'],
            'cost' => $cost,
            'type' => $reward['reward_type']
        ],
        'redemption_id' => $redemptionId,
        'new_balance' => $newBalance,
        'game_time_added' => $gameTimeAdded,
        'badges_earned' => $badgesEarned
    ];

    if ($purchaseData) {
        $response['purchase'] = $purchaseData;
        $response['purchase_id'] = $purchaseData['id'];
    }

    json_response($response);
    
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
