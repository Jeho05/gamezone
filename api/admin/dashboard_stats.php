<?php
// api/admin/dashboard_stats.php
// Statistiques complètes pour le dashboard admin
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();

// Statistiques utilisateurs
$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role != "admin"')->fetchColumn();
$activeUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE last_active >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND role != "admin"')->fetchColumn();
$newUsersToday = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE() AND role != "admin"')->fetchColumn();

// Statistiques points
$totalPointsDistributed = (int)$pdo->query('SELECT COALESCE(SUM(points), 0) FROM users')->fetchColumn();
$pointsToday = (int)$pdo->query('SELECT COALESCE(SUM(change_amount), 0) FROM points_transactions WHERE DATE(created_at) = CURDATE() AND change_amount > 0')->fetchColumn();

// Statistiques achats
$totalPurchases = (int)$pdo->query('SELECT COUNT(*) FROM purchases')->fetchColumn();
$pendingPurchases = (int)$pdo->query('SELECT COUNT(*) FROM purchases WHERE payment_status = "pending"')->fetchColumn();
$completedPurchases = (int)$pdo->query('SELECT COUNT(*) FROM purchases WHERE payment_status = "completed"')->fetchColumn();
$totalRevenue = (float)$pdo->query('SELECT COALESCE(SUM(price), 0) FROM purchases WHERE payment_status = "completed"')->fetchColumn();

// Statistiques sessions de jeu
$activeSessions = (int)$pdo->query('SELECT COUNT(*) FROM game_sessions WHERE status = "active"')->fetchColumn();
$totalSessions = (int)$pdo->query('SELECT COUNT(*) FROM game_sessions')->fetchColumn();
$totalMinutesPlayed = (int)$pdo->query('SELECT COALESCE(SUM(used_minutes), 0) FROM game_sessions')->fetchColumn();

// Statistiques récompenses
$totalRewards = (int)$pdo->query('SELECT COUNT(*) FROM rewards')->fetchColumn();
$rewardsRedeemed = (int)$pdo->query('SELECT COUNT(*) FROM reward_redemptions')->fetchColumn();
$pendingRedemptions = (int)$pdo->query('SELECT COUNT(*) FROM reward_redemptions WHERE status = "pending"')->fetchColumn();

// Statistiques tournois
$totalTournaments = (int)$pdo->query('SELECT COUNT(*) FROM tournaments')->fetchColumn();
$upcomingTournaments = (int)$pdo->query('SELECT COUNT(*) FROM tournaments WHERE status IN ("upcoming", "registration_open")')->fetchColumn();
$ongoingTournaments = (int)$pdo->query('SELECT COUNT(*) FROM tournaments WHERE status = "ongoing"')->fetchColumn();

// Statistiques contenu
$totalContent = (int)$pdo->query('SELECT COUNT(*) FROM content')->fetchColumn();
$publishedContent = (int)$pdo->query('SELECT COUNT(*) FROM content WHERE is_published = 1')->fetchColumn();

// Statistiques conversion points (nouveau système)
$totalConversions = 0;
$pointsConverted = 0;
$minutesGenerated = 0;
try {
    $totalConversions = (int)$pdo->query('SELECT COUNT(*) FROM point_conversions')->fetchColumn();
    $pointsConverted = (int)$pdo->query('SELECT COALESCE(SUM(points_spent), 0) FROM point_conversions')->fetchColumn();
    $minutesGenerated = (int)$pdo->query('SELECT COALESCE(SUM(minutes_gained), 0) FROM point_conversions')->fetchColumn();
} catch (PDOException $e) {
    // Table pas encore créée, ignorer
}

// Revenus ce mois
$revenueThisMonth = (float)$pdo->query('
    SELECT COALESCE(SUM(price), 0) 
    FROM purchases 
    WHERE payment_status = "completed" 
      AND YEAR(created_at) = YEAR(NOW())
      AND MONTH(created_at) = MONTH(NOW())
')->fetchColumn();

// Revenus aujourd'hui
$revenueToday = (float)$pdo->query('
    SELECT COALESCE(SUM(price), 0) 
    FROM purchases 
    WHERE payment_status = "completed" 
      AND DATE(created_at) = CURDATE()
')->fetchColumn();

// Package le plus vendu
$stmt = $pdo->query('
    SELECT package_name, COUNT(*) as count
    FROM purchases
    WHERE payment_status = "completed"
      AND package_name IS NOT NULL
    GROUP BY package_name
    ORDER BY count DESC
    LIMIT 1
');
$topPackage = $stmt->fetch();

// Temps moyen de session
$avgSessionTime = (float)$pdo->query('
    SELECT COALESCE(AVG(used_minutes), 0) 
    FROM game_sessions 
    WHERE status IN ("completed", "terminated")
      AND used_minutes > 0
')->fetchColumn();

// Top 5 jeux les plus joués
$stmt = $pdo->query('
    SELECT g.name, COUNT(gs.id) as sessions, SUM(gs.used_minutes) as total_minutes
    FROM games g
    LEFT JOIN game_sessions gs ON g.id = gs.game_id
    GROUP BY g.id, g.name
    ORDER BY sessions DESC
    LIMIT 5
');
$topGames = $stmt->fetchAll();

// Top 5 utilisateurs par points
$stmt = $pdo->query('
    SELECT username, points, level
    FROM users
    WHERE role != "admin"
    ORDER BY points DESC
    LIMIT 5
');
$topUsers = $stmt->fetchAll();

// Revenus par jour (7 derniers jours)
$stmt = $pdo->query('
    SELECT DATE(created_at) as date, SUM(price) as revenue, COUNT(*) as purchases
    FROM purchases
    WHERE payment_status = "completed" AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
');
$revenueByDay = $stmt->fetchAll();

// Points distribués par jour (7 derniers jours)
$stmt = $pdo->query('
    SELECT DATE(created_at) as date, SUM(change_amount) as points
    FROM points_transactions
    WHERE change_amount > 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
');
$pointsByDay = $stmt->fetchAll();

// Achats en attente récents
$stmt = $pdo->query('
    SELECT p.id, p.created_at, u.username, g.name as game_name, p.price, p.payment_status
    FROM purchases p
    INNER JOIN users u ON p.user_id = u.id
    INNER JOIN games g ON p.game_id = g.id
    WHERE p.payment_status = "pending"
    ORDER BY p.created_at DESC
    LIMIT 10
');
$pendingPurchasesList = $stmt->fetchAll();

json_response([
    'success' => true,
    'overview' => [
        'users' => [
            'total' => $totalUsers,
            'active_7_days' => $activeUsers,
            'new_today' => $newUsersToday,
            'active_percentage' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0
        ],
        'points' => [
            'total_distributed' => $totalPointsDistributed,
            'distributed_today' => $pointsToday
        ],
        'purchases' => [
            'total' => $totalPurchases,
            'pending' => $pendingPurchases,
            'completed' => $completedPurchases,
            'total_revenue' => $totalRevenue
        ],
        'sessions' => [
            'active' => $activeSessions,
            'total' => $totalSessions,
            'total_minutes_played' => $totalMinutesPlayed,
            'total_hours_played' => round($totalMinutesPlayed / 60, 2),
            'average_session_minutes' => round($avgSessionTime, 2)
        ],
        'rewards' => [
            'total' => $totalRewards,
            'redeemed' => $rewardsRedeemed,
            'pending_redemptions' => $pendingRedemptions
        ],
        'tournaments' => [
            'total' => $totalTournaments,
            'upcoming' => $upcomingTournaments,
            'ongoing' => $ongoingTournaments
        ],
        'content' => [
            'total' => $totalContent,
            'published' => $publishedContent
        ],
        'conversions' => [
            'total' => $totalConversions,
            'points_converted' => $pointsConverted,
            'minutes_generated' => $minutesGenerated
        ],
        'revenue' => [
            'today' => $revenueToday,
            'this_month' => $revenueThisMonth,
            'total' => $totalRevenue
        ],
        'popular' => [
            'top_package' => $topPackage ? $topPackage['package_name'] : null,
            'top_package_sales' => $topPackage ? (int)$topPackage['count'] : 0
        ]
    ],
    'top_games' => $topGames,
    'top_users' => $topUsers,
    'charts' => [
        'revenue_by_day' => $revenueByDay,
        'points_by_day' => $pointsByDay
    ],
    'pending_purchases' => $pendingPurchasesList,
    'generated_at' => date('Y-m-d H:i:s')
]);
