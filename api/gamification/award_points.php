<?php
// api/gamification/award_points.php
// Award points based on predefined rules
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

$user = require_auth();
$input = get_json_input();
$actionType = $input['action_type'] ?? '';

$validActions = [
    'game_played',
    'session_complete',
    'event_attended',
    'tournament_participate',
    'tournament_win',
    'friend_referred',
    'referral',
    'daily_login',
    'profile_complete',
    'first_purchase',
    'achievement',
    'review_written',
    'share_social',
];

if (!in_array($actionType, $validActions, true)) {
    json_response(['error' => 'Type d\'action invalide'], 400);
}

$pdo = get_db();
$pdo->beginTransaction();

try {
    $userId = (int)$user['id'];
    
    // Get points rule
    $stmt = $pdo->prepare('SELECT points_amount, description FROM points_rules WHERE action_type = ? AND is_active = 1');
    $stmt->execute([$actionType]);
    $rule = $stmt->fetch();
    
    if (!$rule) {
        $pdo->rollBack();
        json_response(['error' => 'Règle de points introuvable ou inactive'], 404);
    }
    
    $basePoints = (int)$rule['points_amount'];
    
    // Check for active multipliers
    $stmt = $pdo->prepare('SELECT multiplier FROM bonus_multipliers WHERE user_id = ? AND expires_at > NOW() ORDER BY multiplier DESC LIMIT 1');
    $stmt->execute([$userId]);
    $multiplierRow = $stmt->fetch();
    $multiplier = $multiplierRow ? (float)$multiplierRow['multiplier'] : 1.0;
    
    $finalPoints = (int)($basePoints * $multiplier);
    
    // Update user points
    $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$finalPoints, now(), $userId]);
    
    // Log transaction
    $reason = $rule['description'];
    if ($multiplier > 1.0) {
        $reason .= " (x{$multiplier} bonus)";
    }
    $stmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $finalPoints, $reason, $actionType === 'tournament_win' ? 'tournament' : 'game', now()]);
    
    // Update user stats
    $stmt = $pdo->prepare('INSERT INTO user_stats (user_id, games_played, events_attended, tournaments_participated, tournaments_won, friends_referred, total_points_earned, updated_at) 
                           VALUES (?, 0, 0, 0, 0, 0, ?, ?) 
                           ON DUPLICATE KEY UPDATE total_points_earned = total_points_earned + ?, updated_at = ?');
    $stmt->execute([$userId, $finalPoints, now(), $finalPoints, now()]);
    
    // Update specific stat counters
    $statUpdates = [
        'game_played' => 'games_played = games_played + 1',
        'event_attended' => 'events_attended = events_attended + 1',
        'tournament_participate' => 'tournaments_participated = tournaments_participated + 1',
        'tournament_win' => 'tournaments_won = tournaments_won + 1, tournaments_participated = tournaments_participated + 1',
        'friend_referred' => 'friends_referred = friends_referred + 1'
    ];
    
    if (isset($statUpdates[$actionType])) {
        $stmt = $pdo->prepare("UPDATE user_stats SET {$statUpdates[$actionType]}, updated_at = ? WHERE user_id = ?");
        $stmt->execute([now(), $userId]);
    }
    
    // Get updated user points
    $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $newPoints = (int)$stmt->fetchColumn();
    
    // Check for level up
    $stmt = $pdo->prepare('SELECT level_number, name, points_bonus FROM levels WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1');
    $stmt->execute([$newPoints]);
    $newLevel = $stmt->fetch();
    
    $leveledUp = false;
    if ($newLevel) {
        $stmt = $pdo->prepare('SELECT level FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $currentLevel = $stmt->fetchColumn();
        
        if ($currentLevel !== $newLevel['name']) {
            $stmt = $pdo->prepare('UPDATE users SET level = ?, updated_at = ? WHERE id = ?');
            $stmt->execute([$newLevel['name'], now(), $userId]);
            
            // Award level bonus
            if ($newLevel['points_bonus'] > 0) {
                $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
                $stmt->execute([$newLevel['points_bonus'], now(), $userId]);
                $newPoints += $newLevel['points_bonus'];
                
                $stmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $newLevel['points_bonus'], "Bonus niveau {$newLevel['level_number']}: {$newLevel['name']}", 'bonus', now()]);
            }
            
            $leveledUp = true;
        }
    }
    
    // Check for new badges
    require_once __DIR__ . '/check_badges.php';
    $newBadges = check_and_award_badges($pdo, $userId);
    
    $pdo->commit();
    
    json_response([
        'message' => 'Points attribués',
        'points_awarded' => $finalPoints,
        'new_total' => $newPoints,
        'multiplier' => $multiplier,
        'leveled_up' => $leveledUp,
        'new_level' => $leveledUp ? $newLevel : null,
        'badges_earned' => $newBadges
    ]);
    
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['error' => 'Échec de l\'attribution des points', 'details' => $e->getMessage()], 500);
}
