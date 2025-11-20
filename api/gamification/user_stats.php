<?php
// api/gamification/user_stats.php
// Get comprehensive user statistics
require_once __DIR__ . '/../utils.php';
require_method(['GET']);

$auth = require_auth();
$pdo = get_db();

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)$auth['id'];

// Non-admins can only see their own stats
if (($auth['role'] ?? 'player') !== 'admin' && $userId !== (int)$auth['id']) {
    json_response(['error' => 'Forbidden'], 403);
}

// Get user basic info
$stmt = $pdo->prepare('SELECT id, username, points, level FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    json_response(['error' => 'Utilisateur introuvable'], 404);
}

// Get user stats
$stmt = $pdo->prepare('SELECT * FROM user_stats WHERE user_id = ?');
$stmt->execute([$userId]);
$stats = $stmt->fetch();

if (!$stats) {
    $stats = [
        'games_played' => 0,
        'events_attended' => 0,
        'tournaments_participated' => 0,
        'tournaments_won' => 0,
        'friends_referred' => 0,
        'total_points_earned' => 0,
        'total_points_spent' => 0
    ];
}

// Get login streak
$stmt = $pdo->prepare('SELECT current_streak, longest_streak, last_login_date FROM login_streaks WHERE user_id = ?');
$stmt->execute([$userId]);
$streak = $stmt->fetch();

if (!$streak) {
    $streak = ['current_streak' => 0, 'longest_streak' => 0, 'last_login_date' => null];
}

// Get badges count
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM user_badges WHERE user_id = ?');
$stmt->execute([$userId]);
$badgesCount = (int)$stmt->fetchColumn();

// Get total badges available
$totalBadges = (int)$pdo->query('SELECT COUNT(*) FROM badges')->fetchColumn();

// Get rewards redeemed
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM reward_redemptions WHERE user_id = ?');
$stmt->execute([$userId]);
$rewardsRedeemed = (int)$stmt->fetchColumn();

// Get available converted game time (in minutes)
$minutesAvailable = 0;
try {
    $stmt = $pdo->prepare('SELECT get_user_converted_minutes(?) as minutes');
    $stmt->execute([$userId]);
    $minutesAvailable = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    $minutesAvailable = 0;
}

// Get recent achievements (badges in last 30 days)
$stmt = $pdo->prepare('
    SELECT b.name, b.icon, b.rarity, ub.earned_at
    FROM user_badges ub
    INNER JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = ? AND ub.earned_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY ub.earned_at DESC
    LIMIT 5
');
$stmt->execute([$userId]);
$recentBadges = $stmt->fetchAll();

// Get current level info
$userPoints = (int)$user['points'];
$stmt = $pdo->prepare('SELECT level_number, name, points_required, color FROM levels WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1');
$stmt->execute([$userPoints]);
$currentLevel = $stmt->fetch();

$stmt = $pdo->prepare('SELECT level_number, name, points_required, points_bonus FROM levels WHERE points_required > ? ORDER BY points_required ASC LIMIT 1');
$stmt->execute([$userPoints]);
$nextLevel = $stmt->fetch();

json_response([
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'points' => $userPoints,
        'level' => $user['level']
    ],
    'statistics' => [
        'games_played' => (int)$stats['games_played'],
        'events_attended' => (int)$stats['events_attended'],
        'tournaments_participated' => (int)$stats['tournaments_participated'],
        'tournaments_won' => (int)$stats['tournaments_won'],
        'friends_referred' => (int)$stats['friends_referred'],
        'total_points_earned' => (int)$stats['total_points_earned'],
        'total_points_spent' => (int)$stats['total_points_spent'],
        'net_points' => (int)$stats['total_points_earned'] - (int)$stats['total_points_spent'],
        'badges_earned' => $badgesCount,
        'badges_total' => $totalBadges,
        'rewards_redeemed' => $rewardsRedeemed,
        'minutes_available' => $minutesAvailable
    ],
    'streak' => [
        'current' => (int)$streak['current_streak'],
        'longest' => (int)$streak['longest_streak'],
        'last_login' => $streak['last_login_date']
    ],
    'level_progression' => [
        'current' => $currentLevel ? [
            'number' => (int)$currentLevel['level_number'],
            'name' => $currentLevel['name'],
            'points_required' => (int)$currentLevel['points_required'],
            'color' => $currentLevel['color']
        ] : null,
        'next' => $nextLevel ? [
            'number' => (int)$nextLevel['level_number'],
            'name' => $nextLevel['name'],
            'points_required' => (int)$nextLevel['points_required'],
            'points_bonus' => (int)$nextLevel['points_bonus'],
            'points_needed' => (int)$nextLevel['points_required'] - $userPoints
        ] : null
    ],
    'recent_achievements' => array_map(function($badge) {
        return [
            'name' => $badge['name'],
            'icon' => $badge['icon'],
            'rarity' => $badge['rarity'],
            'earned_at' => $badge['earned_at']
        ];
    }, $recentBadges)
]);
