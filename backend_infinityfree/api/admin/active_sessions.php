<?php
// api/admin/active_sessions.php
// API pour récupérer toutes les sessions actives (ADMIN ONLY)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que c'est un admin
$user = require_auth();
if (!is_admin($user)) {
    json_response(['error' => 'Accès refusé - Admin uniquement'], 403);
}

$pdo = get_db();

try {
    // Récupérer toutes les sessions avec infos complètes (avatars, niveaux, etc.)
    $stmt = $pdo->query("
        SELECT 
            s.*,
            s.remaining_minutes,
            s.progress_percent,
            s.avatar_url,
            s.username,
            s.level,
            s.points,
            s.game_name,
            s.game_slug,
            s.game_image
        FROM session_summary s
        WHERE s.status IN ('ready', 'active', 'paused')
        ORDER BY 
            CASE s.status
                WHEN 'active' THEN 1
                WHEN 'paused' THEN 2
                WHEN 'ready' THEN 3
            END,
            s.remaining_minutes ASC
    ");
    
    $sessions = $stmt->fetchAll();
    // Normaliser les types de données
    foreach ($sessions as &$s) {
        $s['remaining_minutes'] = (int)($s['remaining_minutes'] ?? 0);
        $s['progress_percent'] = (float)($s['progress_percent'] ?? 0);
        $s['total_minutes'] = (int)($s['total_minutes'] ?? 0);
        $s['used_minutes'] = (int)($s['used_minutes'] ?? 0);
        $s['level'] = (int)($s['level'] ?? 1);
        $s['points'] = (int)($s['points'] ?? 0);
    }
    
    // Statistiques du jour
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_today,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
            SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) as paused,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_today
        FROM active_game_sessions_v2
        WHERE DATE(created_at) = CURDATE()
    ");
    
    $stats = $stmt->fetch();
    
    json_response([
        'success' => true,
        'sessions' => $sessions,
        'stats' => [
            'total' => (int)$stats['total_today'],
            'active' => (int)$stats['active'],
            'ready' => (int)$stats['ready'],
            'paused' => (int)$stats['paused'],
            'completed_today' => (int)$stats['completed_today']
        ],
        'timestamp' => now()
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors du chargement des sessions',
        'details' => $e->getMessage()
    ], 500);
}
