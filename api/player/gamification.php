<?php
/**
 * Player Gamification Dashboard Endpoint
 * Retourne toutes les informations de gamification pour un joueur
 * GET /api/player/gamification
 * Params:
 *  - user_id: ID de l'utilisateur (optionnel, par défaut = utilisateur connecté)
 */

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../helpers/response.php';

require_method(['GET']);

// Check authentication
$auth = current_user();
if (!$auth) {
    json_response([
        'success' => false,
        'error' => 'Authentification requise',
        'message' => 'Vous devez être connecté pour accéder à cette page',
        'redirect' => '/auth/login'
    ], 401);
}

$pdo = get_db();

// Determine which user to show
$requestedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)$auth['id'];

// Non-admins can only see their own stats
if (($auth['role'] ?? 'player') !== 'admin' && $requestedUserId !== (int)$auth['id']) {
    json_response(['error' => 'Accès interdit'], 403);
}

// === USER BASIC INFO ===
$stmt = $pdo->prepare('
    SELECT 
        id, 
        username, 
        email,
        avatar_url, 
        points, 
        level,
        created_at,
        last_active
    FROM users 
    WHERE id = ?
');
$stmt->execute([$requestedUserId]);
$user = $stmt->fetch();

if (!$user) {
    json_response(['error' => 'Utilisateur introuvable'], 404);
}

$userId = (int)$user['id'];
$userPoints = (int)$user['points'];

// === LEVEL PROGRESSION ===
// Récupérer le niveau actuel
$stmt = $pdo->prepare('
    SELECT level_number, name, points_required, color, points_bonus
    FROM levels 
    WHERE points_required <= ? 
    ORDER BY points_required DESC 
    LIMIT 1
');
$stmt->execute([$userPoints]);
$currentLevel = $stmt->fetch();

// Si aucun niveau trouvé, créer un niveau par défaut
if (!$currentLevel) {
    $currentLevel = [
        'level_number' => 1,
        'name' => 'Débutant',
        'points_required' => 0,
        'color' => '#808080',
        'points_bonus' => 0
    ];
}

// Récupérer le prochain niveau
$stmt = $pdo->prepare('
    SELECT level_number, name, points_required, color, points_bonus
    FROM levels 
    WHERE points_required > ? 
    ORDER BY points_required ASC 
    LIMIT 1
');
$stmt->execute([$userPoints]);
$nextLevel = $stmt->fetch();

// Calculer la progression
$currentLevelPoints = (int)$currentLevel['points_required'];
$nextLevelPoints = $nextLevel ? (int)$nextLevel['points_required'] : $currentLevelPoints;
$pointsInCurrentLevel = $userPoints - $currentLevelPoints;
$pointsNeededForNextLevel = $nextLevelPoints - $currentLevelPoints;

$progressPercentage = 100;
if ($nextLevel && $pointsNeededForNextLevel > 0) {
    $progressPercentage = round(($pointsInCurrentLevel / $pointsNeededForNextLevel) * 100, 2);
    // S'assurer que le pourcentage est entre 0 et 100
    $progressPercentage = max(0, min(100, $progressPercentage));
}

$levelProgression = [
    'current' => [
        'number' => (int)$currentLevel['level_number'],
        'name' => $currentLevel['name'],
        'points_required' => $currentLevelPoints,
        'color' => $currentLevel['color'],
        'points_bonus' => (int)$currentLevel['points_bonus']
    ],
    'next' => $nextLevel ? [
        'number' => (int)$nextLevel['level_number'],
        'name' => $nextLevel['name'],
        'points_required' => $nextLevelPoints,
        'color' => $nextLevel['color'],
        'points_bonus' => (int)$nextLevel['points_bonus'],
        'points_needed' => max(0, $nextLevelPoints - $userPoints)
    ] : null,
    'progress_percentage' => $progressPercentage,
    'points_in_current_level' => $pointsInCurrentLevel,
    'points_needed_for_next' => $nextLevel ? max(0, $pointsNeededForNextLevel - $pointsInCurrentLevel) : 0,
    'is_max_level' => !$nextLevel
];

// === USER STATS ===
$stmt = $pdo->prepare('SELECT * FROM user_stats WHERE user_id = ?');
$stmt->execute([$userId]);
$stats = $stmt->fetch();

if (!$stats) {
    // Create default stats if not exists
    $stmt = $pdo->prepare('INSERT INTO user_stats (user_id) VALUES (?)');
    $stmt->execute([$userId]);
    $stats = [
        'games_played' => 0,
        'events_attended' => 0,
        'tournaments_participated' => 0,
        'tournaments_won' => 0,
        'friends_referred' => 0,
        'total_points_earned' => 0,
        'total_points_spent' => 0,
        'achievements_unlocked' => 0
    ];
}

// === LOGIN STREAK ===
$stmt = $pdo->prepare('
    SELECT current_streak, longest_streak, last_login_date 
    FROM login_streaks 
    WHERE user_id = ?
');
$stmt->execute([$userId]);
$streak = $stmt->fetch();

if (!$streak) {
    $streak = [
        'current_streak' => 0,
        'longest_streak' => 0,
        'last_login_date' => null
    ];
}

// === BADGES ===
$stmt = $pdo->prepare('
    SELECT 
        b.id,
        b.name,
        b.description,
        b.icon,
        b.rarity,
        b.points_reward,
        ub.earned_at,
        ub.progress
    FROM user_badges ub
    INNER JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = ?
    ORDER BY ub.earned_at DESC
');
$stmt->execute([$userId]);
$earnedBadges = $stmt->fetchAll();

// Get total badges
$totalBadgesAvailable = (int)$pdo->query('SELECT COUNT(*) FROM badges')->fetchColumn();

// Format badges
$badges = [
    'earned' => array_map(function($badge) {
        return [
            'id' => (int)$badge['id'],
            'name' => $badge['name'],
            'description' => $badge['description'],
            'icon' => $badge['icon'],
            'rarity' => $badge['rarity'],
            'points_reward' => (int)$badge['points_reward'],
            'earned_at' => $badge['earned_at'],
            'progress' => (int)$badge['progress']
        ];
    }, $earnedBadges),
    'total_earned' => count($earnedBadges),
    'total_available' => $totalBadgesAvailable,
    'completion_percentage' => $totalBadgesAvailable > 0 ? 
        round((count($earnedBadges) / $totalBadgesAvailable) * 100, 2) : 0
];

// Get recent badges (last 5)
$recentBadges = array_slice($earnedBadges, 0, 5);

// === POINTS HISTORY ===
$stmt = $pdo->prepare('
    SELECT 
        change_amount,
        reason,
        type,
        created_at
    FROM points_transactions
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
');
$stmt->execute([$userId]);
$pointsHistory = array_map(function($tx) {
    return [
        'amount' => (int)$tx['change_amount'],
        'reason' => $tx['reason'],
        'type' => $tx['type'],
        'date' => $tx['created_at']
    ];
}, $stmt->fetchAll());

// === BONUS MULTIPLIERS ===
$stmt = $pdo->prepare('
    SELECT 
        bm.id,
        bm.multiplier,
        bm.reason,
        bm.starts_at,
        bm.ends_at
    FROM bonus_multipliers bm
    WHERE bm.user_id = ? AND bm.ends_at > NOW()
    ORDER BY bm.ends_at ASC
');
$stmt->execute([$userId]);
$activeMultipliers = array_map(function($mult) {
    return [
        'id' => (int)$mult['id'],
        'multiplier' => (float)$mult['multiplier'],
        'reason' => $mult['reason'],
        'starts_at' => $mult['starts_at'],
        'ends_at' => $mult['ends_at'],
        'time_remaining' => (new DateTime($mult['ends_at']))->diff(new DateTime())->format('%h heures %i minutes')
    ];
}, $stmt->fetchAll());

// === REWARDS REDEEMED ===
$stmt = $pdo->prepare('
    SELECT 
        r.name,
        r.cost,
        rr.redeemed_at,
        rr.status
    FROM reward_redemptions rr
    INNER JOIN rewards r ON rr.reward_id = r.id
    WHERE rr.user_id = ?
    ORDER BY rr.redeemed_at DESC
    LIMIT 10
');
$stmt->execute([$userId]);
$rewardsRedeemed = array_map(function($reward) {
    return [
        'name' => $reward['name'],
        'cost' => (int)$reward['cost'],
        'redeemed_at' => $reward['redeemed_at'],
        'status' => $reward['status']
    ];
}, $stmt->fetchAll());

// === ACTIVITY STATS ===
// Points earned in last 7 days
$stmt = $pdo->prepare('
    SELECT COALESCE(SUM(change_amount), 0) 
    FROM points_transactions 
    WHERE user_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND change_amount > 0
');
$stmt->execute([$userId]);
$pointsLast7Days = (int)$stmt->fetchColumn();

// Points earned in last 30 days
$stmt = $pdo->prepare('
    SELECT COALESCE(SUM(change_amount), 0) 
    FROM points_transactions 
    WHERE user_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND change_amount > 0
');
$stmt->execute([$userId]);
$pointsLast30Days = (int)$stmt->fetchColumn();

// Daily activity (last 7 days breakdown)
$stmt = $pdo->prepare('
    SELECT 
        DATE(created_at) as date,
        SUM(CASE WHEN change_amount > 0 THEN change_amount ELSE 0 END) as earned,
        SUM(CASE WHEN change_amount < 0 THEN ABS(change_amount) ELSE 0 END) as spent,
        COUNT(*) as transactions
    FROM points_transactions
    WHERE user_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
');
$stmt->execute([$userId]);
$dailyActivity = array_map(function($day) {
    return [
        'date' => $day['date'],
        'earned' => (int)$day['earned'],
        'spent' => (int)$day['spent'],
        'net' => (int)$day['earned'] - (int)$day['spent'],
        'transactions' => (int)$day['transactions']
    ];
}, $stmt->fetchAll());

// === LEADERBOARD POSITION ===
$stmt = $pdo->prepare('SELECT COUNT(*) + 1 FROM users WHERE points > ?');
$stmt->execute([$userPoints]);
$globalRank = (int)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE points > 0');
$totalActivePlayers = (int)$stmt->fetchColumn();

// === ACHIEVEMENTS & MILESTONES ===
$milestones = [
    'points' => [
        ['threshold' => 100, 'label' => 'Novice'],
        ['threshold' => 500, 'label' => 'Amateur'],
        ['threshold' => 1000, 'label' => 'Expérimenté'],
        ['threshold' => 5000, 'label' => 'Expert'],
        ['threshold' => 10000, 'label' => 'Maître'],
        ['threshold' => 50000, 'label' => 'Légende']
    ],
    'days_active' => [
        ['threshold' => 7, 'label' => '1 semaine'],
        ['threshold' => 30, 'label' => '1 mois'],
        ['threshold' => 90, 'label' => '3 mois'],
        ['threshold' => 180, 'label' => '6 mois'],
        ['threshold' => 365, 'label' => '1 an']
    ]
];

// Calculate days active
$createdDate = new DateTime($user['created_at']);
$daysActive = (int)$createdDate->diff(new DateTime())->format('%a');

$nextMilestones = [
    'points' => null,
    'days_active' => null
];

foreach ($milestones['points'] as $milestone) {
    if ($userPoints < $milestone['threshold']) {
        $nextMilestones['points'] = [
            'threshold' => $milestone['threshold'],
            'label' => $milestone['label'],
            'remaining' => $milestone['threshold'] - $userPoints
        ];
        break;
    }
}

foreach ($milestones['days_active'] as $milestone) {
    if ($daysActive < $milestone['threshold']) {
        $nextMilestones['days_active'] = [
            'threshold' => $milestone['threshold'],
            'label' => $milestone['label'],
            'remaining' => $milestone['threshold'] - $daysActive
        ];
        break;
    }
}

// === FINAL RESPONSE ===
json_response([
    'success' => true,
    'user' => [
        'id' => $userId,
        'username' => $user['username'],
        'email' => $user['email'],
        'avatar_url' => $user['avatar_url'],
        'points' => $userPoints,
        'level' => (int)$user['level'],
        'member_since' => $user['created_at'],
        'days_active' => $daysActive,
        'last_active' => $user['last_active']
    ],
    'level_progression' => $levelProgression,
    'statistics' => [
        'games_played' => (int)$stats['games_played'],
        'total_points_earned' => (int)$stats['total_points_earned'],
        'total_points_spent' => (int)$stats['total_points_spent'],
        'net_points' => (int)$stats['total_points_earned'] - (int)$stats['total_points_spent'],
        'badges_earned' => count($earnedBadges),
        'badges_total' => $totalBadgesAvailable,
        'rewards_redeemed' => count($rewardsRedeemed)
    ],
    'activity' => [
        'points_last_7_days' => $pointsLast7Days,
        'points_last_30_days' => $pointsLast30Days,
        'daily_breakdown' => $dailyActivity
    ],
    'streak' => [
        'current' => (int)$streak['current_streak'],
        'longest' => (int)$streak['longest_streak'],
        'last_login_date' => $streak['last_login_date']
    ],
    'badges' => $badges,
    'recent_badges' => array_map(function($badge) {
        return [
            'name' => $badge['name'],
            'icon' => $badge['icon'],
            'rarity' => $badge['rarity'],
            'earned_at' => $badge['earned_at']
        ];
    }, $recentBadges),
    'points_history' => $pointsHistory,
    'active_multipliers' => $activeMultipliers,
    'rewards_redeemed' => [
        'items' => $rewardsRedeemed,
        'total_count' => count($rewardsRedeemed)
    ],
    'leaderboard' => [
        'global_rank' => $globalRank,
        'total_players' => $totalActivePlayers,
        'percentile' => $totalActivePlayers > 0 ? 
            round((($totalActivePlayers - $globalRank + 1) / $totalActivePlayers) * 100, 2) : 0
    ],
    'next_milestones' => $nextMilestones,
    'generated_at' => date('Y-m-d H:i:s')
]);
