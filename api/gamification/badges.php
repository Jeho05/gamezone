<?php
// api/gamification/badges.php
// Get badges (all or user-specific)
require_once __DIR__ . '/../utils.php';
require_method(['GET']);

$pdo = get_db();
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if ($userId) {
    // Get user's earned badges
    $stmt = $pdo->prepare('
        SELECT b.id, b.name, b.description, b.icon, b.category, b.rarity, b.points_reward,
               ub.earned_at
        FROM badges b
        INNER JOIN user_badges ub ON b.id = ub.badge_id
        WHERE ub.user_id = ?
        ORDER BY ub.earned_at DESC
    ');
    $stmt->execute([$userId]);
    $earnedBadges = $stmt->fetchAll();
    
    // Get all available badges with progress
    $stmt = $pdo->prepare('SELECT * FROM badges ORDER BY category, requirement_value ASC');
    $stmt->execute();
    $allBadges = $stmt->fetchAll();
    
    // Get user stats for progress calculation
    $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $userPoints = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare('SELECT * FROM user_stats WHERE user_id = ?');
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        $stats = ['games_played' => 0, 'events_attended' => 0, 'tournaments_participated' => 0, 
                  'tournaments_won' => 0, 'friends_referred' => 0, 'total_points_earned' => 0];
    }
    
    $stmt = $pdo->prepare('SELECT current_streak FROM login_streaks WHERE user_id = ?');
    $stmt->execute([$userId]);
    $streakRow = $stmt->fetch();
    $loginStreak = $streakRow ? (int)$streakRow['current_streak'] : 0;
    
    // Calculate progress for each badge
    $badgesWithProgress = array_map(function($badge) use ($userPoints, $stats, $loginStreak, $earnedBadges) {
        $earned = false;
        foreach ($earnedBadges as $eb) {
            if ((int)$eb['id'] === (int)$badge['id']) {
                $earned = true;
                $badge['earned_at'] = $eb['earned_at'];
                break;
            }
        }
        
        $badge['earned'] = $earned;
        $requirement = (int)$badge['requirement_value'];
        $current = 0;
        
        switch ($badge['requirement_type']) {
            case 'points_total':
                $current = $userPoints;
                break;
            case 'points_earned':
                $current = (int)$stats['total_points_earned'];
                break;
            case 'games_played':
                $current = (int)$stats['games_played'];
                break;
            case 'events_attended':
                $current = (int)$stats['events_attended'];
                break;
            case 'friends_referred':
                $current = (int)$stats['friends_referred'];
                break;
            case 'login_streak':
                $current = $loginStreak;
                break;
        }
        
        $badge['progress'] = min(100, $requirement > 0 ? (int)(($current / $requirement) * 100) : 0);
        $badge['current_value'] = $current;
        
        return [
            'id' => (int)$badge['id'],
            'name' => $badge['name'],
            'description' => $badge['description'],
            'icon' => $badge['icon'],
            'category' => $badge['category'],
            'requirement_type' => $badge['requirement_type'],
            'requirement_value' => (int)$badge['requirement_value'],
            'rarity' => $badge['rarity'],
            'points_reward' => (int)$badge['points_reward'],
            'earned' => $badge['earned'],
            'earned_at' => $badge['earned_at'] ?? null,
            'progress' => $badge['progress'],
            'current_value' => $current
        ];
    }, $allBadges);
    
    json_response([
        'badges' => $badgesWithProgress,
        'total_earned' => count($earnedBadges),
        'total_available' => count($allBadges)
    ]);
} else {
    // Get all badges
    $stmt = $pdo->query('SELECT id, name, description, icon, category, requirement_type, requirement_value, rarity, points_reward FROM badges ORDER BY category, requirement_value ASC');
    $badges = $stmt->fetchAll();
    
    json_response(['badges' => $badges]);
}
