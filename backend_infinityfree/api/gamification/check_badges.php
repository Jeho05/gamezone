<?php
// api/gamification/check_badges.php
// Function to check and award badges to a user

function check_and_award_badges($pdo, $userId) {
    $newBadges = [];
    
    // Get user stats
    $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $userPoints = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare('SELECT * FROM user_stats WHERE user_id = ?');
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        // Initialize stats if not exist
        $stmt = $pdo->prepare('INSERT INTO user_stats (user_id, games_played, events_attended, tournaments_participated, tournaments_won, friends_referred, total_points_earned, total_points_spent, updated_at) VALUES (?, 0, 0, 0, 0, 0, 0, 0, ?)');
        $stmt->execute([$userId, date('Y-m-d H:i:s')]);
        $stats = ['games_played' => 0, 'events_attended' => 0, 'tournaments_participated' => 0, 'tournaments_won' => 0, 'friends_referred' => 0, 'total_points_earned' => 0];
    }
    
    // Get login streak
    $stmt = $pdo->prepare('SELECT current_streak FROM login_streaks WHERE user_id = ?');
    $stmt->execute([$userId]);
    $streakRow = $stmt->fetch();
    $loginStreak = $streakRow ? (int)$streakRow['current_streak'] : 0;
    
    // Get all badges user doesn't have yet
    $stmt = $pdo->prepare('
        SELECT b.* FROM badges b
        WHERE b.id NOT IN (SELECT badge_id FROM user_badges WHERE user_id = ?)
    ');
    $stmt->execute([$userId]);
    $availableBadges = $stmt->fetchAll();
    
    foreach ($availableBadges as $badge) {
        $earned = false;
        
        switch ($badge['requirement_type']) {
            case 'points_total':
                $earned = $userPoints >= (int)$badge['requirement_value'];
                break;
            case 'points_earned':
                $earned = (int)$stats['total_points_earned'] >= (int)$badge['requirement_value'];
                break;
            case 'games_played':
                $earned = (int)$stats['games_played'] >= (int)$badge['requirement_value'];
                break;
            case 'events_attended':
                $earned = (int)$stats['events_attended'] >= (int)$badge['requirement_value'];
                break;
            case 'friends_referred':
                $earned = (int)$stats['friends_referred'] >= (int)$badge['requirement_value'];
                break;
            case 'login_streak':
                $earned = $loginStreak >= (int)$badge['requirement_value'];
                break;
            case 'special':
                // Special badges awarded manually or by specific triggers
                break;
        }
        
        if ($earned) {
            // Award badge
            $stmt = $pdo->prepare('INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $badge['id'], date('Y-m-d H:i:s')]);
            
            // Award points if badge has reward
            if ((int)$badge['points_reward'] > 0) {
                $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
                $stmt->execute([$badge['points_reward'], date('Y-m-d H:i:s'), $userId]);
                
                $stmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $badge['points_reward'], "Badge débloqué: {$badge['name']}", 'bonus', date('Y-m-d H:i:s')]);
            }
            
            $newBadges[] = [
                'id' => (int)$badge['id'],
                'name' => $badge['name'],
                'description' => $badge['description'],
                'icon' => $badge['icon'],
                'rarity' => $badge['rarity'],
                'points_reward' => (int)$badge['points_reward']
            ];
        }
    }
    
    return $newBadges;
}

// If called directly as endpoint
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once __DIR__ . '/../utils.php';
    require_method(['POST']);
    
    $user = require_auth();
    $pdo = get_db();
    
    $pdo->beginTransaction();
    try {
        $newBadges = check_and_award_badges($pdo, (int)$user['id']);
        $pdo->commit();
        
        json_response([
            'message' => 'Vérification des badges effectuée',
            'badges_earned' => $newBadges
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        json_response(['error' => 'Échec de la vérification des badges', 'details' => $e->getMessage()], 500);
    }
}
