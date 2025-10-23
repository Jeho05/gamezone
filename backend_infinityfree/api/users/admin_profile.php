<?php
// api/users/admin_profile.php
// Get detailed user profile for admin view
require_once __DIR__ . '/../utils.php';

require_method(['GET']);
require_auth('admin');

$pdo = get_db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    json_response(['error' => 'Paramètre id manquant'], 400);
}

// Get user basic info
$stmt = $pdo->prepare('
    SELECT id, username, email, role, avatar_url, points, level, status, 
           join_date, last_active, created_at, updated_at
    FROM users 
    WHERE id = ?
');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    json_response(['error' => 'Utilisateur introuvable'], 404);
}

// Get points statistics
$stats_stmt = $pdo->prepare('
    SELECT 
        COUNT(*) as total_activities,
        COALESCE(SUM(CASE WHEN change_amount > 0 THEN change_amount ELSE 0 END), 0) as points_earned,
        COALESCE(SUM(CASE WHEN change_amount < 0 THEN ABS(change_amount) ELSE 0 END), 0) as points_spent,
        COUNT(DISTINCT DATE(created_at)) as active_days,
        MAX(created_at) as last_activity
    FROM points_transactions
    WHERE user_id = ?
');
$stats_stmt->execute([$id]);
$stats = $stats_stmt->fetch();

// Also get from user_stats table if available (more accurate for points_spent)
$user_stats_stmt = $pdo->prepare('SELECT total_points_earned, total_points_spent FROM user_stats WHERE user_id = ?');
$user_stats_stmt->execute([$id]);
$user_stats = $user_stats_stmt->fetch();

if ($user_stats) {
    $stats['points_earned'] = max((int)$stats['points_earned'], (int)$user_stats['total_points_earned']);
    $stats['points_spent'] = max((int)$stats['points_spent'], (int)$user_stats['total_points_spent']);
}

// Get recent points history (last 20 entries)
$history_stmt = $pdo->prepare('
    SELECT id, change_amount as points, reason as description, type, created_at
    FROM points_transactions
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
');
$history_stmt->execute([$id]);
$history = $history_stmt->fetchAll();

// Get user's rewards redemptions
$rewards_count = 0;
try {
    $rewards_stmt = $pdo->prepare('
        SELECT COUNT(*) as total_redemptions
        FROM reward_redemptions
        WHERE user_id = ?
    ');
    $rewards_stmt->execute([$id]);
    $rewards_data = $rewards_stmt->fetch();
    $rewards_count = (int)($rewards_data['total_redemptions'] ?? 0);
} catch (PDOException $e) {
    // Table might not exist
    $rewards_count = 0;
}

// Format response
json_response([
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'avatar_url' => $user['avatar_url'],
        'points' => (int)$user['points'],
        'level' => $user['level'],
        'status' => $user['status'],
        'join_date' => $user['join_date'],
        'last_active' => $user['last_active'],
        'created_at' => $user['created_at'],
        'updated_at' => $user['updated_at'],
    ],
    'statistics' => [
        'total_activities' => (int)($stats['total_activities'] ?? 0),
        'points_earned' => (int)($stats['points_earned'] ?? 0),
        'points_spent' => (int)($stats['points_spent'] ?? 0),
        'active_days' => (int)($stats['active_days'] ?? 0),
        'last_activity' => $stats['last_activity'] ?? null,
        'total_redemptions' => $rewards_count,
    ],
    'recent_history' => array_map(function($item) {
        return [
            'id' => (int)$item['id'],
            'points' => (int)$item['points'],
            'description' => $item['description'],
            'type' => $item['type'],
            'created_at' => $item['created_at'],
        ];
    }, $history)
]);
