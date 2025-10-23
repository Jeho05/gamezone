<?php
/**
 * API Leaderboard - Top joueurs
 * Retourne les meilleurs joueurs par points
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

require_method(['GET']);

$pdo = get_db();

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $limit = min($limit, 100); // Max 100
    
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.avatar_url,
            u.level,
            u.points,
            u.join_date,
            COUNT(DISTINCT gs.id) as total_sessions,
            COALESCE(SUM(gs.total_minutes), 0) as total_playtime,
            COALESCE(SUM(CASE WHEN gs.status = 'completed' THEN 1 ELSE 0 END), 0) as completed_sessions,
            ROW_NUMBER() OVER (ORDER BY u.points DESC, u.id ASC) as rank
        FROM users u
        LEFT JOIN game_sessions gs ON u.id = gs.user_id
        WHERE u.role = 'player' AND u.status = 'active'
        GROUP BY u.id, u.username, u.avatar_url, u.level, u.points, u.join_date
        ORDER BY u.points DESC, u.id ASC
        LIMIT ?
    ");
    
    $stmt->execute([$limit]);
    $leaderboard = $stmt->fetchAll();
    
    // Normaliser les données
    foreach ($leaderboard as &$player) {
        $player['rank'] = (int)$player['rank'];
        $player['points'] = (int)$player['points'];
        $player['level'] = (int)$player['level'];
        $player['total_sessions'] = (int)$player['total_sessions'];
        $player['total_playtime'] = (int)$player['total_playtime'];
        $player['completed_sessions'] = (int)$player['completed_sessions'];
    }
    
    json_response([
        'success' => true,
        'leaderboard' => $leaderboard,
        'timestamp' => now()
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors du chargement du leaderboard',
        'details' => $e->getMessage()
    ], 500);
}
