<?php
/**
 * Heartbeat de session joueur
 * Met à jour last_heartbeat et calcule used_minutes
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

try {
    $now = date('Y-m-d H:i:s');
    
    // Récupérer la session active du joueur
    $stmt = $pdo->prepare('
        SELECT id, started_at, total_minutes, used_minutes, status
        FROM active_game_sessions_v2
        WHERE user_id = ?
        AND status IN ("active", "paused")
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $stmt->execute([$user['id']]);
    $session = $stmt->fetch();
    
    if (!$session) {
        json_response([
            'success' => true,
            'message' => 'Aucune session active'
        ]);
    }
    
    // Calculer les minutes utilisées
    $elapsedMinutes = 0;
    if ($session['started_at'] && $session['status'] === 'active') {
        $start = new DateTime($session['started_at']);
        $current = new DateTime($now);
        $diff = $current->diff($start);
        $elapsedMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        
        // Log pour debug
        error_log(sprintf(
            '[heartbeat] Session %d: started_at=%s, now=%s, elapsed=%d min',
            $session['id'],
            $session['started_at'],
            $now,
            $elapsedMinutes
        ));
    }
    
    // Ne pas dépasser le total
    $usedMinutes = min($elapsedMinutes, $session['total_minutes']);
    $remainingMinutes = $session['total_minutes'] - $usedMinutes;
    
    // Log calcul final
    error_log(sprintf(
        '[heartbeat] Session %d: total=%d, elapsed=%d, used=%d, remaining=%d',
        $session['id'],
        $session['total_minutes'],
        $elapsedMinutes,
        $usedMinutes,
        $remainingMinutes
    ));
    
    // Mettre à jour la session
    $stmt = $pdo->prepare('
        UPDATE active_game_sessions_v2 SET
            last_heartbeat = ?,
            used_minutes = ?,
            last_countdown_update = ?,
            updated_at = ?
        WHERE id = ?
    ');
    $stmt->execute([$now, $usedMinutes, $now, $now, $session['id']]);
    
    // Si le temps est écoulé, marquer comme complétée
    if ($remainingMinutes <= 0 && $session['status'] === 'active') {
        $pdo->beginTransaction();
        
        try {
            // Compléter la session
            $stmt = $pdo->prepare('
                UPDATE active_game_sessions_v2 SET
                    status = "completed",
                    completed_at = ?,
                    used_minutes = total_minutes,
                    updated_at = ?
                WHERE id = ?
            ');
            $stmt->execute([$now, $now, $session['id']]);
            
            // Marquer facture comme utilisée
            $stmt = $pdo->prepare('
                UPDATE invoices SET
                    status = "used",
                    used_at = ?,
                    updated_at = ?
                WHERE id = (SELECT invoice_id FROM active_game_sessions_v2 WHERE id = ?)
            ');
            $stmt->execute([$now, $now, $session['id']]);
            
            // Mettre à jour purchase
            $stmt = $pdo->prepare('
                UPDATE purchases SET
                    session_status = "completed",
                    updated_at = ?
                WHERE id = (SELECT purchase_id FROM active_game_sessions_v2 WHERE id = ?)
            ');
            $stmt->execute([$now, $session['id']]);
            
            // Logger
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO session_events (session_id, event_type, event_message, created_at)
                    VALUES (?, "complete", "Session complétée automatiquement (temps écoulé - heartbeat)", ?)
                ');
                $stmt->execute([$session['id'], $now]);
            } catch (PDOException $e) {
                // Optionnel
            }
            
            $pdo->commit();
            
            json_response([
                'success' => true,
                'session_completed' => true,
                'remaining_minutes' => 0,
                'used_minutes' => $session['total_minutes'],
                'message' => 'Votre session est terminée'
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    json_response([
        'success' => true,
        'remaining_minutes' => $remainingMinutes,
        'used_minutes' => $usedMinutes,
        'status' => $session['status'],
        'last_heartbeat' => $now
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors de la mise à jour',
        'details' => $e->getMessage()
    ], 500);
}
