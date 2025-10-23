<?php
// api/admin/statistics.php
// Admin statistics endpoint - provides overall stats for dashboard

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_check.php';

$admin = require_admin();
$db = get_db();

try {
    // Get total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    
    // Get active users (last 30 days)
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE last_active >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $activeUsers = $stmt->fetch()['total'];
    
    // Get new users (last 7 days)
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $newUsers = $stmt->fetch()['total'];
    
    // Get total events
    $stmt = $db->query("SELECT COUNT(*) as total FROM events");
    $totalEvents = $stmt->fetch()['total'];
    
    // Get events by type
    $stmt = $db->query("SELECT type, COUNT(*) as count FROM events GROUP BY type");
    $eventsByType = [];
    while ($row = $stmt->fetch()) {
        $eventsByType[$row['type']] = (int)$row['count'];
    }
    
    // Check if gallery table exists
    $stmt = $db->query("SHOW TABLES LIKE 'gallery'");
    $galleryExists = $stmt->fetch();
    
    if ($galleryExists) {
        $stmt = $db->query("SELECT COUNT(*) as total FROM gallery");
        $totalGallery = $stmt->fetch()['total'];
    } else {
        $totalGallery = 0;
    }
    
    // Get total points distributed
    $stmt = $db->query("SELECT COALESCE(SUM(change_amount), 0) as total FROM points_transactions WHERE change_amount > 0");
    $totalPointsDistributed = $stmt->fetch()['total'];
    
    // Check if reward_redemptions table exists
    $stmt = $db->query("SHOW TABLES LIKE 'reward_redemptions'");
    $rewardsExists = $stmt->fetch();
    
    if ($rewardsExists) {
        $stmt = $db->query("SELECT COUNT(*) as total FROM reward_redemptions");
        $totalRewardsClaimed = $stmt->fetch()['total'];
    } else {
        $totalRewardsClaimed = 0;
    }
    
    // Count sanctions from points_transactions (negative adjustments with "SANCTION" in reason)
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM points_transactions 
        WHERE type = 'adjustment' 
        AND change_amount < 0 
        AND reason LIKE '%SANCTION%'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $activeSanctions = $stmt->fetch()['total'];
    
    // Get recent activity (last 10 events)
    $stmt = $db->query("
        SELECT e.*
        FROM events e
        ORDER BY e.created_at DESC
        LIMIT 10
    ");
    $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top users by points (top 5)
    $stmt = $db->query("
        SELECT id, username, email, points, level, avatar_url
        FROM users
        WHERE role = 'player'
        ORDER BY points DESC
        LIMIT 5
    ");
    $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user growth (last 30 days by day)
    $stmt = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $userGrowth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get points transactions summary (last 7 days)
    $stmt = $db->query("
        SELECT DATE(created_at) as date, 
               SUM(CASE WHEN change_amount > 0 THEN change_amount ELSE 0 END) as earned,
               SUM(CASE WHEN change_amount < 0 THEN ABS(change_amount) ELSE 0 END) as spent
        FROM points_transactions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $pointsActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    json_response([
        'success' => true,
        'statistics' => [
            'users' => [
                'total' => (int)$totalUsers,
                'active' => (int)$activeUsers,
                'new' => (int)$newUsers
            ],
            'events' => [
                'total' => (int)$totalEvents,
                'byType' => $eventsByType
            ],
            'gallery' => [
                'total' => (int)$totalGallery
            ],
            'gamification' => [
                'totalPointsDistributed' => (int)$totalPointsDistributed,
                'rewardsClaimed' => (int)$totalRewardsClaimed,
                'activeSanctions' => (int)$activeSanctions
            ]
        ],
        'recentEvents' => $recentEvents,
        'topUsers' => $topUsers,
        'charts' => [
            'userGrowth' => $userGrowth,
            'pointsActivity' => $pointsActivity
        ]
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors de la récupération des statistiques',
        'details' => $e->getMessage()
    ], 500);
}
