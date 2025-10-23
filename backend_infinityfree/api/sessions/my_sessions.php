<?php
// api/sessions/my_sessions.php
// API pour récupérer les sessions de jeu de l'utilisateur

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

require_method(['GET']);

$status = $_GET['status'] ?? ''; // active, paused, completed, expired
$gameId = $_GET['game_id'] ?? null;
$limit = min((int)($_GET['limit'] ?? 20), 50);
$offset = (int)($_GET['offset'] ?? 0);

try {
    // Construire la requête
    $sql = '
        SELECT s.*,
               g.name as game_name,
               g.image_url as game_image,
               g.points_per_hour,
               p.package_name,
               p.price,
               (s.total_minutes - s.used_minutes) as remaining_minutes,
               ROUND((s.used_minutes / 60) * g.points_per_hour) as calculated_points,
               (SELECT COALESCE(SUM(change_amount), 0) 
                FROM points_transactions 
                WHERE reference_type = "game_session" AND reference_id = s.id) as points_credited
        FROM game_sessions s
        INNER JOIN purchases p ON s.purchase_id = p.id
        INNER JOIN games g ON s.game_id = g.id
        WHERE s.user_id = ?
    ';
    
    $params = [$user['id']];
    
    if ($status) {
        $sql .= ' AND s.status = ?';
        $params[] = $status;
    }
    
    if ($gameId) {
        $sql .= ' AND s.game_id = ?';
        $params[] = $gameId;
    }
    
    $sql .= ' ORDER BY s.last_activity_at DESC, s.created_at DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sessions = $stmt->fetchAll();
    
    // Calculer les totaux
    $stmt = $pdo->prepare('
        SELECT 
            COUNT(*) as total_sessions,
            SUM(CASE WHEN s.status = "active" THEN 1 ELSE 0 END) as active_sessions,
            SUM(CASE WHEN s.status = "completed" THEN 1 ELSE 0 END) as completed_sessions,
            SUM(s.used_minutes) as total_minutes_played,
            SUM(ROUND((s.used_minutes / 60) * g.points_per_hour)) as total_points_calculated
        FROM game_sessions s
        INNER JOIN games g ON s.game_id = g.id
        WHERE s.user_id = ?
    ');
    $stmt->execute([$user['id']]);
    $stats = $stmt->fetch();
    
    json_response([
        'sessions' => $sessions,
        'stats' => $stats,
        'count' => count($sessions),
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    json_response(['error' => 'Erreur lors du chargement des sessions', 'details' => $e->getMessage()], 500);
}
