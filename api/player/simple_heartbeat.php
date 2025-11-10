<?php
/**
 * Heartbeat SIMPLIFIÉ
 * Met à jour UNIQUEMENT last_heartbeat
 * Ne touche PAS à used_minutes (géré par cron job séparé)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

try {
    $now = date('Y-m-d H:i:s');
    
    // Récupérer la session active
    $stmt = $pdo->prepare('
        SELECT id, total_minutes, used_minutes, started_at, status
        FROM active_game_sessions_v2
        WHERE user_id = ?
        AND status = "active"
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $stmt->execute([$user['id']]);
    $session = $stmt->fetch();
    
    if (!$session) {
        json_response([
            'success' => false,
            'message' => 'Aucune session active'
        ]);
    }
    
    // Mettre à jour UNIQUEMENT le heartbeat
    $stmt = $pdo->prepare('
        UPDATE active_game_sessions_v2
        SET last_heartbeat = ?,
            updated_at = ?
        WHERE id = ?
    ');
    $stmt->execute([$now, $now, $session['id']]);
    
    // Retourner l'état actuel (sans modification)
    json_response([
        'success' => true,
        'remaining_minutes' => max(0, (int)$session['total_minutes'] - (int)$session['used_minutes']),
        'used_minutes' => (int)$session['used_minutes'],
        'status' => $session['status'],
        'heartbeat_updated' => true
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur heartbeat',
        'details' => $e->getMessage()
    ], 500);
}
