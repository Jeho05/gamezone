<?php
// api/player/my_active_session.php
// API pour récupérer la session active du joueur connecté

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();

$pdo = get_db();

try {
    // Récupérer la session active du joueur avec toutes les infos (avatar inclus)
    $stmt = $pdo->prepare("
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
        WHERE s.user_id = ? 
        AND s.status IN ('ready', 'active', 'paused')
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$user['id']]);
    $session = $stmt->fetch();
    
    if ($session) {
        // Garantir les valeurs numériques
        $session['remaining_minutes'] = (int)($session['remaining_minutes'] ?? 0);
        $session['progress_percent'] = (float)($session['progress_percent'] ?? 0);
        $session['total_minutes'] = (int)($session['total_minutes'] ?? 0);
        $session['used_minutes'] = (int)($session['used_minutes'] ?? 0);
        $session['level'] = (int)($session['level'] ?? 1);
        $session['points'] = (int)($session['points'] ?? 0);
        
        json_response([
            'success' => true,
            'session' => $session
        ]);
    } else {
        json_response([
            'success' => true,
            'session' => null,
            'message' => 'Aucune session active'
        ]);
    }
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors du chargement de la session',
        'details' => $e->getMessage()
    ], 500);
}
