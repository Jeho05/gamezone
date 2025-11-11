<?php
/**
 * Démarrer le chronomètre d'une session
 * Endpoint dédié appelé UNE SEULE FOIS au chargement de la page joueur
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

try {
    $now = date('Y-m-d H:i:s');
    
    // Récupérer la session active du joueur qui n'a pas encore démarré
    $stmt = $pdo->prepare('
        SELECT id, total_minutes, started_at, status
        FROM active_game_sessions_v2
        WHERE user_id = ?
        AND status = "active"
        AND started_at IS NULL
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $stmt->execute([$user['id']]);
    $session = $stmt->fetch();
    
    if (!$session) {
        json_response([
            'success' => false,
            'message' => 'Aucune session en attente de démarrage'
        ]);
    }
    
    // Démarrer le chronomètre MAINTENANT
    $stmt = $pdo->prepare('
        UPDATE active_game_sessions_v2
        SET started_at = ?,
            last_heartbeat = ?,
            updated_at = ?
        WHERE id = ?
    ');
    $stmt->execute([$now, $now, $now, $session['id']]);
    
    error_log(sprintf(
        '[start_session] Session %d démarrée pour user %d à %s',
        $session['id'],
        $user['id'],
        $now
    ));
    
    json_response([
        'success' => true,
        'message' => 'Chronomètre démarré',
        'session_id' => $session['id'],
        'started_at' => $now,
        'total_minutes' => $session['total_minutes']
    ]);
    
} catch (Exception $e) {
    error_log('[start_session] Erreur: ' . $e->getMessage());
    json_response([
        'error' => 'Erreur lors du démarrage',
        'details' => $e->getMessage()
    ], 500);
}
