<?php
/**
 * Version SIMPLIFIÉE de récupération de session
 * Sans calculs complexes, juste les données brutes
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

try {
    // Récupérer DIRECTEMENT depuis la table (pas la vue)
    $stmt = $pdo->prepare('
        SELECT 
            s.id,
            s.invoice_id,
            s.purchase_id,
            s.user_id,
            s.game_id,
            s.total_minutes,
            s.used_minutes,
            (s.total_minutes - s.used_minutes) as remaining_minutes,
            s.status,
            s.started_at,
            s.created_at,
            g.name as game_name,
            g.slug as game_slug,
            g.image_url as game_image
        FROM active_game_sessions_v2 s
        INNER JOIN games g ON s.game_id = g.id
        WHERE s.user_id = ?
        AND s.status IN ("active", "paused")
        ORDER BY s.created_at DESC
        LIMIT 1
    ');
    
    $stmt->execute([$user['id']]);
    $session = $stmt->fetch();
    
    if (!$session) {
        json_response([
            'success' => true,
            'session' => null
        ]);
    }
    
    // Convertir en entiers
    $session['id'] = (int)$session['id'];
    $session['total_minutes'] = (int)$session['total_minutes'];
    $session['used_minutes'] = (int)$session['used_minutes'];
    $session['remaining_minutes'] = (int)$session['remaining_minutes'];
    
    // Log pour debug
    error_log(sprintf(
        '[simple_session] User %d - Session %d: total=%d, used=%d, remaining=%d, status=%s, started_at=%s',
        $user['id'],
        $session['id'],
        $session['total_minutes'],
        $session['used_minutes'],
        $session['remaining_minutes'],
        $session['status'],
        $session['started_at'] ?? 'NULL'
    ));
    
    // Vérifier les anomalies
    if ($session['total_minutes'] === 0) {
        error_log("[simple_session] ALERTE: Session {$session['id']} a total_minutes = 0");
    }
    
    if ($session['remaining_minutes'] === 0 && $session['used_minutes'] === 0) {
        error_log("[simple_session] ALERTE: Session {$session['id']} a remaining=0 et used=0 (anormal)");
    }
    
    json_response([
        'success' => true,
        'session' => $session,
        'debug' => [
            'total_is_zero' => $session['total_minutes'] === 0,
            'remaining_is_zero' => $session['remaining_minutes'] === 0,
            'started_at_is_null' => empty($session['started_at'])
        ]
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors du chargement',
        'details' => $e->getMessage()
    ], 500);
}
