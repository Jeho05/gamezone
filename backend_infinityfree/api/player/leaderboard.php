<?php
/**
 * Player Leaderboard Endpoint
 * Affiche le classement détaillé avec de vraies informations
 * GET /api/player/leaderboard
 * Params: 
 *  - period: weekly|monthly|all (default: weekly)
 *  - limit: 1-100 (default: 50)
 */

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../helpers/response.php';

require_method(['GET']);

$pdo = get_db();
$period = $_GET['period'] ?? 'weekly'; // weekly | monthly | all
$limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));

// Validation
if (!in_array($period, ['weekly', 'monthly', 'all'])) {
    json_response(['error' => 'Period must be: weekly, monthly or all'], 400);
}

// Calculate date range
$now = new DateTime();
$start = null;
$periodLabel = '';

switch ($period) {
    case 'monthly':
        $start = (new DateTime('first day of this month 00:00:00'));
        $periodLabel = 'Mois de ' . $start->format('F Y');
        break;
    case 'weekly':
        $start = new DateTime('monday this week');
        $start->setTime(0, 0, 0);
        $end = clone $start;
        $end->add(new DateInterval('P6D'));
        $periodLabel = 'Semaine du ' . $start->format('d/m') . ' au ' . $end->format('d/m/Y');
        break;
    case 'all':
        $periodLabel = 'Classement général';
        break;
}

// Get leaderboard data
if ($start) {
    // Period-based: sum from points_transactions (only positive changes)
    $sql = 'SELECT 
        u.id, 
        u.username, 
        u.avatar_url, 
        u.level,
        u.points as total_points,
        COALESCE(SUM(CASE WHEN pt.change_amount > 0 THEN pt.change_amount ELSE 0 END), 0) AS period_points,
        COUNT(DISTINCT DATE(pt.created_at)) as active_days
    FROM users u
    LEFT JOIN points_transactions pt ON pt.user_id = u.id AND pt.created_at >= ?
    WHERE u.role != "admin"
    GROUP BY u.id, u.username, u.avatar_url, u.level, u.points
    HAVING period_points > 0
    ORDER BY period_points DESC
    LIMIT ' . $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start->format('Y-m-d H:i:s')]);
} else {
    // All time: use total points
    $sql = 'SELECT 
        u.id, 
        u.username, 
        u.avatar_url, 
        u.level,
        u.points as total_points,
        u.points as period_points,
        (SELECT COUNT(DISTINCT DATE(pt.created_at)) 
         FROM points_transactions pt 
         WHERE pt.user_id = u.id) as active_days
    FROM users u
    WHERE u.points > 0 AND u.role != "admin"
    ORDER BY u.points DESC, u.created_at ASC
    LIMIT ' . $limit;
    
    $stmt = $pdo->query($sql);
}

$rows = $stmt->fetchAll();

// Get current user info
$current = current_user();
$currentUserId = $current ? (int)$current['id'] : null;

// Get additional stats for each user
$ranked = [];
$rank = 1;
$previousPoints = null;
$actualRank = 1;

foreach ($rows as $row) {
    $userId = (int)$row['id'];
    $points = (int)$row['period_points'];
    
    // Handle ties - same points = same rank
    if ($previousPoints !== null && $points < $previousPoints) {
        $actualRank = $rank;
    }
    $previousPoints = $points;
    
    // Get badges count for this user
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_badges WHERE user_id = ?');
    $stmt->execute([$userId]);
    $badgesCount = (int)$stmt->fetchColumn();
    
    // Get recent activity (last 7 days)
    $stmt = $pdo->prepare('
        SELECT COUNT(*) 
        FROM points_transactions 
        WHERE user_id = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ');
    $stmt->execute([$userId]);
    $recentActivity = (int)$stmt->fetchColumn();
    
    // Get level info
    $stmt = $pdo->prepare('
        SELECT name, color, points_required 
        FROM levels 
        WHERE points_required <= ? 
        ORDER BY points_required DESC 
        LIMIT 1
    ');
    $stmt->execute([(int)$row['total_points']]);
    $levelInfo = $stmt->fetch();
    
    // Calculate rank change (compare to 7 days ago if weekly/monthly)
    $rankChange = 0;
    if ($period !== 'all' && $start) {
        $previousPeriodStart = clone $start;
        $previousPeriodStart->sub(new DateInterval('P7D'));
        
        $stmt = $pdo->prepare('
            SELECT COUNT(*) + 1 
            FROM (
                SELECT user_id, SUM(change_amount) as pts 
                FROM points_transactions 
                WHERE created_at >= ? AND created_at < ?
                GROUP BY user_id
            ) as prev
            WHERE pts > (
                SELECT COALESCE(SUM(change_amount), 0)
                FROM points_transactions
                WHERE user_id = ? AND created_at >= ? AND created_at < ?
            )
        ');
        $stmt->execute([
            $previousPeriodStart->format('Y-m-d H:i:s'),
            $start->format('Y-m-d H:i:s'),
            $userId,
            $previousPeriodStart->format('Y-m-d H:i:s'),
            $start->format('Y-m-d H:i:s')
        ]);
        $previousRank = (int)$stmt->fetchColumn();
        $rankChange = $previousRank - $actualRank;
    }
    
    $ranked[] = [
        'rank' => $actualRank,
        'user' => [
            'id' => $userId,
            'username' => $row['username'],
            'avatar_url' => $row['avatar_url'],
            'level' => (int)$row['level'],
            'level_info' => $levelInfo ? [
                'name' => $levelInfo['name'],
                'color' => $levelInfo['color'],
                'points_required' => (int)$levelInfo['points_required']
            ] : null
        ],
        'points' => $points,
        'total_points' => (int)$row['total_points'],
        'badges_earned' => $badgesCount,
        'active_days' => (int)$row['active_days'],
        'recent_activity' => $recentActivity,
        'rank_change' => $rankChange,
        'is_current_user' => ($currentUserId === $userId)
    ];
    
    $rank++;
}

// Get current user rank if not in top list
$currentUserData = null;
if ($currentUserId) {
    $found = false;
    foreach ($ranked as $item) {
        if ($item['user']['id'] === $currentUserId) {
            $found = true;
            $currentUserData = $item;
            break;
        }
    }
    
    if (!$found) {
        // Calculate current user stats
        if ($start) {
            $stmt = $pdo->prepare('
                SELECT COALESCE(SUM(change_amount), 0) 
                FROM points_transactions 
                WHERE user_id = ? AND created_at >= ?
            ');
            $stmt->execute([$currentUserId, $start->format('Y-m-d H:i:s')]);
            $userPoints = (int)$stmt->fetchColumn();
            
            $stmt = $pdo->prepare('
                SELECT COUNT(*) + 1 
                FROM (
                    SELECT user_id, SUM(change_amount) as pts 
                    FROM points_transactions 
                    WHERE created_at >= ?
                    GROUP BY user_id
                ) as lb
                WHERE pts > ?
            ');
            $stmt->execute([$start->format('Y-m-d H:i:s'), $userPoints]);
            $userRank = (int)$stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
            $stmt->execute([$currentUserId]);
            $userPoints = (int)$stmt->fetchColumn();
            
            $stmt = $pdo->prepare('SELECT COUNT(*) + 1 FROM users WHERE points > ?');
            $stmt->execute([$userPoints]);
            $userRank = (int)$stmt->fetchColumn();
        }
        
        $currentUserData = [
            'rank' => $userRank,
            'user' => [
                'id' => $currentUserId,
                'username' => $current['username'],
                'avatar_url' => $current['avatar_url'] ?? null,
                'level' => (int)$current['level']
            ],
            'points' => $userPoints,
            'total_points' => (int)$current['points'],
            'is_current_user' => true
        ];
    }
}

// Get some stats about the leaderboard
$totalPlayers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE points > 0')->fetchColumn();
$totalPointsInPeriod = 0;
if ($start) {
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(change_amount), 0) FROM points_transactions WHERE created_at >= ? AND change_amount > 0');
    $stmt->execute([$start->format('Y-m-d H:i:s')]);
    $totalPointsInPeriod = (int)$stmt->fetchColumn();
} else {
    $totalPointsInPeriod = (int)$pdo->query('SELECT COALESCE(SUM(points), 0) FROM users')->fetchColumn();
}

// Response
json_response([
    'success' => true,
    'leaderboard' => [
        'period' => $period,
        'period_label' => $periodLabel,
        'start_date' => $start ? $start->format('Y-m-d H:i:s') : null,
        'end_date' => $period === 'all' ? null : $now->format('Y-m-d H:i:s'),
        'total_players' => $totalPlayers,
        'total_points_distributed' => $totalPointsInPeriod,
        'showing_top' => count($ranked),
        'rankings' => $ranked
    ],
    'current_user' => $currentUserData,
    'generated_at' => date('Y-m-d H:i:s')
]);
