<?php
/**
 * DEBUG: Afficher l'état complet d'une session pour diagnostic
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gamezoneismo.vercel.app');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();

$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    json_response(['error' => 'session_id requis'], 400);
}

try {
    // Récupérer TOUT de la session
    $stmt = $pdo->prepare('
        SELECT *,
               NOW() as current_server_time,
               TIMESTAMPDIFF(SECOND, started_at, NOW()) as seconds_since_start,
               TIMESTAMPDIFF(MINUTE, started_at, NOW()) as minutes_since_start,
               TIMESTAMPDIFF(SECOND, last_countdown_update, NOW()) as seconds_since_last_update,
               TIMESTAMPDIFF(MINUTE, expires_at, NOW()) as minutes_past_expiry,
               (total_minutes - used_minutes) as calculated_remaining,
               CASE 
                   WHEN started_at IS NULL THEN "not_started"
                   WHEN expires_at < NOW() THEN "expired_by_date"
                   WHEN used_minutes >= total_minutes THEN "expired_by_usage"
                   ELSE "active_ok"
               END as expiry_reason
        FROM active_game_sessions_v2
        WHERE id = ?
    ');
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    
    if (!$session) {
        json_response(['error' => 'Session introuvable'], 404);
    }
    
    // Vérifier s'il y a des triggers
    $stmt = $pdo->query("
        SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING, ACTION_STATEMENT
        FROM information_schema.TRIGGERS
        WHERE TRIGGER_SCHEMA = DATABASE()
        AND EVENT_OBJECT_TABLE = 'active_game_sessions_v2'
    ");
    $triggers = $stmt->fetchAll();
    
    // Récupérer les derniers events de la session
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM session_events
            WHERE session_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ');
        $stmt->execute([$sessionId]);
        $events = $stmt->fetchAll();
    } catch (Exception $e) {
        $events = ['error' => 'Table session_events inaccessible'];
    }
    
    json_response([
        'success' => true,
        'session' => $session,
        'triggers' => $triggers,
        'recent_events' => $events,
        'analysis' => [
            'should_be_expired' => $session['expiry_reason'] !== 'active_ok' && $session['expiry_reason'] !== 'not_started',
            'time_consistency' => [
                'started_at_valid' => $session['started_at'] !== null,
                'expires_at_in_future' => $session['expires_at'] > $session['current_server_time'],
                'last_update_recent' => $session['seconds_since_last_update'] < 300
            ]
        ]
    ], 200);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur debug',
        'details' => $e->getMessage()
    ], 500);
}
