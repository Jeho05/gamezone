<?php
/**
 * Leaderboard Endpoint - OPTIMIZED VERSION
 * 
 * Demonstrates usage of new helpers and cache system
 * This file shows how to optimize the leaderboard endpoint
 * 
 * To use: Replace index.php with this file or merge the improvements
 */

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../helpers/database.php';
require_once __DIR__ . '/../helpers/response.php';

require_method(['GET']);

// Validate and sanitize inputs
$period = $_GET['period'] ?? 'weekly'; // weekly | monthly | all
if (!in_array($period, ['weekly', 'monthly', 'all'])) {
    validation_error_response(['period' => 'Doit être: weekly, monthly ou all']);
}

$limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));

// Generate cache key based on parameters
$cacheKey = "leaderboard_{$period}_limit_{$limit}";

// Try to get from cache (5 minutes TTL)
$result = Cache::remember($cacheKey, function() use ($period, $limit) {
    $pdo = get_db();
    $now = new DateTime();
    
    // Calculate date range
    if ($period === 'monthly') {
        $start = (new DateTime('first day of this month 00:00:00'));
    } elseif ($period === 'weekly') {
        $start = new DateTime('monday this week');
        $start->setTime(0, 0, 0);
    } else {
        $start = null;
    }
    
    // Build and execute query
    if ($start) {
        $sql = 'SELECT u.id, u.username, u.avatar_url, SUM(pt.change_amount) AS points
            FROM users u
            JOIN points_transactions pt ON pt.user_id = u.id
            WHERE pt.created_at >= ?
            GROUP BY u.id, u.username, u.avatar_url
            ORDER BY points DESC
            LIMIT ' . $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start->format('Y-m-d H:i:s')]);
    } else {
        // All time: use users.points
        $sql = 'SELECT id, username, avatar_url, points FROM users ORDER BY points DESC LIMIT ' . $limit;
        $stmt = $pdo->query($sql);
    }
    
    $rows = $stmt->fetchAll();
    
    // Format results
    $ranked = [];
    $rank = 1;
    foreach ($rows as $row) {
        $ranked[] = [
            'rank' => $rank++,
            'id' => (int)$row['id'],
            'username' => $row['username'],
            'avatar_url' => $row['avatar_url'],
            'points' => (int)($row['points'] ?? 0),
        ];
    }
    
    return [
        'period' => $period,
        'items' => $ranked,
        'cached_at' => date('c'),
    ];
}, 300); // 5 minutes cache

// Get current user data (not cached, as it's user-specific)
$current = current_user();
$currentUserRank = null;
$currentUserPoints = null;

if ($current) {
    $uid = (int)$current['id'];
    
    // Mark current user in results
    foreach ($result['items'] as &$item) {
        $item['isCurrentUser'] = ((int)$item['id'] === $uid);
    }
    unset($item);
    
    // Calculate current user rank
    if ($period === 'all') {
        $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
        $stmt->execute([$uid]);
        $currentUserPoints = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare('SELECT COUNT(*) + 1 FROM users WHERE points > ?');
        $stmt->execute([$currentUserPoints]);
        $currentUserRank = (int)$stmt->fetchColumn();
    } else {
        // Period-based calculation
        $start = ($period === 'monthly') 
            ? (new DateTime('first day of this month 00:00:00'))
            : (new DateTime('monday this week'))->setTime(0, 0, 0);
        
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(change_amount), 0) FROM points_transactions WHERE user_id = ? AND created_at >= ?');
        $stmt->execute([$uid, $start->format('Y-m-d H:i:s')]);
        $currentUserPoints = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare('SELECT COUNT(*) + 1 AS rnk FROM (
            SELECT pt.user_id, SUM(pt.change_amount) AS s
            FROM points_transactions pt
            WHERE pt.created_at >= ?
            GROUP BY pt.user_id
        ) agg WHERE agg.s > ?');
        $stmt->execute([$start->format('Y-m-d H:i:s'), $currentUserPoints]);
        $currentUserRank = (int)$stmt->fetchColumn();
    }
}

// Add user-specific data
$result['current_user_rank'] = $currentUserRank;
$result['current_user_points'] = $currentUserPoints;

// Log access
Logger::debug('Leaderboard accessed', [
    'period' => $period,
    'limit' => $limit,
    'cached' => !empty($result['cached_at']),
    'user_id' => $current ? $current['id'] : null
]);

// Send success response
success_response($result);
