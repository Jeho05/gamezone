<?php
// api/gamification/login_streak.php
// Track and reward daily login streaks
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

$user = require_auth();
$userId = (int)$user['id'];
$today = date('Y-m-d');

$pdo = get_db();
$pdo->beginTransaction();

try {
    // Get or create streak record
    $stmt = $pdo->prepare('SELECT current_streak, longest_streak, last_login_date FROM login_streaks WHERE user_id = ? FOR UPDATE');
    $stmt->execute([$userId]);
    $streak = $stmt->fetch();
    
    if (!$streak) {
        // First login ever
        $stmt = $pdo->prepare('INSERT INTO login_streaks (user_id, current_streak, longest_streak, last_login_date) VALUES (?, 1, 1, ?)');
        $stmt->execute([$userId, $today]);
        
        $currentStreak = 1;
        $longestStreak = 1;
        $isNewStreak = true;
    } else {
        $lastLogin = $streak['last_login_date'];
        $currentStreak = (int)$streak['current_streak'];
        $longestStreak = (int)$streak['longest_streak'];
        
        if ($lastLogin === $today) {
            // Already logged in today
            $pdo->rollBack();
            json_response([
                'message' => 'Déjà connecté aujourd\'hui',
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak,
                'points_awarded' => 0
            ]);
        }
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if ($lastLogin === $yesterday) {
            // Continuing streak
            $currentStreak++;
            $isNewStreak = false;
        } else {
            // Streak broken, restart
            $currentStreak = 1;
            $isNewStreak = true;
        }
        
        if ($currentStreak > $longestStreak) {
            $longestStreak = $currentStreak;
        }
        
        $stmt = $pdo->prepare('UPDATE login_streaks SET current_streak = ?, longest_streak = ?, last_login_date = ? WHERE user_id = ?');
        $stmt->execute([$currentStreak, $longestStreak, $today, $userId]);
    }
    
    // Award daily login points
    $stmt = $pdo->prepare('SELECT points_amount FROM points_rules WHERE action_type = ? AND is_active = 1');
    $stmt->execute(['daily_login']);
    $rule = $stmt->fetch();
    $basePoints = $rule ? (int)$rule['points_amount'] : 5;
    
    // Streak bonus: extra points for longer streaks
    $streakBonus = 0;
    if ($currentStreak >= 30) {
        $streakBonus = 50; // Legendary streak
    } elseif ($currentStreak >= 14) {
        $streakBonus = 25; // Two week streak
    } elseif ($currentStreak >= 7) {
        $streakBonus = 10; // Weekly streak
    } elseif ($currentStreak >= 3) {
        $streakBonus = 5; // 3-day streak
    }
    
    $totalPoints = $basePoints + $streakBonus;
    
    // Update user points
    $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$totalPoints, now(), $userId]);
    
    // Log transaction
    $reason = "Connexion quotidienne (série de {$currentStreak})";
    if ($streakBonus > 0) {
        $reason .= " +{$streakBonus} bonus";
    }
    $stmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $totalPoints, $reason, 'bonus', now()]);
    
    // Update stats
    $stmt = $pdo->prepare('INSERT INTO user_stats (user_id, total_points_earned, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE total_points_earned = total_points_earned + ?, updated_at = ?');
    $stmt->execute([$userId, $totalPoints, now(), $totalPoints, now()]);
    
    // Check for streak badges
    require_once __DIR__ . '/check_badges.php';
    $newBadges = check_and_award_badges($pdo, $userId);
    
    $pdo->commit();
    
    json_response([
        'message' => 'Connexion enregistrée',
        'current_streak' => $currentStreak,
        'longest_streak' => $longestStreak,
        'points_awarded' => $totalPoints,
        'streak_bonus' => $streakBonus,
        'is_new_streak' => $isNewStreak,
        'badges_earned' => $newBadges
    ]);
    
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    $msg = $e->getMessage();
    $isMissingTable = (
        strpos($msg, '42S02') !== false ||
        strpos($msg, '1146') !== false ||
        stripos($msg, 'login_streaks') !== false ||
        stripos($msg, 'points_rules') !== false ||
        stripos($msg, 'user_stats') !== false ||
        stripos($msg, 'badges') !== false ||
        stripos($msg, 'user_badges') !== false
    );

    if ($isMissingTable) {
        json_response([
            'message' => 'Connexion enregistrée (gamification partielle)',
            'current_streak' => 0,
            'longest_streak' => 0,
            'points_awarded' => 0,
            'streak_bonus' => 0,
            'is_new_streak' => false,
            'badges_earned' => [],
            'warning' => 'Système de gamification non initialisé complètement sur ce serveur'
        ]);
    }

    json_response([
        'error' => 'Échec de l\'enregistrement de connexion',
        'details' => $msg
    ], 500);
}
