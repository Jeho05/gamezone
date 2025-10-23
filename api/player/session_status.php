<?php
// api/player/session_status.php
// API rapide pour vérifier le statut de la session (polling)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

try {
    // Récupérer uniquement les infos essentielles pour un polling rapide
    $stmt = $pdo->prepare("
        SELECT 
            id,
            status,
            total_minutes,
            used_minutes,
            (total_minutes - used_minutes) as remaining_minutes,
            ROUND((used_minutes / total_minutes) * 100, 1) as progress_percent
        FROM active_game_sessions_v2
        WHERE user_id = ? 
        AND status IN ('ready', 'active', 'paused')
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$user['id']]);
    $session = $stmt->fetch();
    
    json_response([
        'success' => true,
        'has_session' => $session ? true : false,
        'session' => $session,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur',
        'details' => $e->getMessage()
    ], 500);
}
