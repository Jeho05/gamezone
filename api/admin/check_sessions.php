<?php
/**
 * Cron Job: Vérifier et compléter automatiquement les sessions expirées
 * À exécuter toutes les minutes
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();

try {
    $now = date('Y-m-d H:i:s');
    
    // Trouver toutes les sessions actives ou en pause où le temps est écoulé
    $stmt = $pdo->prepare('
        SELECT id, invoice_id, user_id, total_minutes, used_minutes, 
               TIMESTAMPDIFF(MINUTE, started_at, ?) as elapsed_minutes
        FROM active_game_sessions_v2
        WHERE status IN ("active", "paused")
        AND started_at IS NOT NULL
        AND (
            used_minutes >= total_minutes
            OR TIMESTAMPDIFF(MINUTE, started_at, ?) >= total_minutes
            OR ? > expires_at
        )
    ');
    $stmt->execute([$now, $now, $now]);
    $expiredSessions = $stmt->fetchAll();
    
    $completedCount = 0;
    
    foreach ($expiredSessions as $session) {
        $pdo->beginTransaction();
        
        try {
            // Marquer la session comme complétée
            $stmt = $pdo->prepare('
                UPDATE active_game_sessions_v2 SET
                    status = "completed",
                    completed_at = ?,
                    used_minutes = total_minutes,
                    updated_at = ?
                WHERE id = ?
            ');
            $stmt->execute([$now, $now, $session['id']]);
            
            // Marquer la facture comme utilisée
            $stmt = $pdo->prepare('
                UPDATE invoices SET 
                    status = "used", 
                    used_at = ?,
                    updated_at = ?
                WHERE id = ?
            ');
            $stmt->execute([$now, $now, $session['invoice_id']]);
            
            // Mettre à jour l'achat
            $stmt = $pdo->prepare('
                UPDATE purchases SET
                    session_status = "completed",
                    updated_at = ?
                WHERE id = (SELECT purchase_id FROM active_game_sessions_v2 WHERE id = ?)
            ');
            $stmt->execute([$now, $session['id']]);
            
            // Logger l'événement
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO session_events (session_id, event_type, event_message, created_at)
                    VALUES (?, "complete", "Session complétée automatiquement (temps écoulé)", ?)
                ');
                $stmt->execute([$session['id'], $now]);
            } catch (PDOException $e) {
                // Table optionnelle
            }
            
            $pdo->commit();
            $completedCount++;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur complétion session {$session['id']}: " . $e->getMessage());
        }
    }
    
    // Mettre à jour les sessions actives (décrémenter le temps)
    if (defined('UPDATE_ACTIVE_SESSIONS') && UPDATE_ACTIVE_SESSIONS) {
        $stmt = $pdo->prepare('
            UPDATE active_game_sessions_v2 SET
                used_minutes = LEAST(
                    total_minutes,
                    TIMESTAMPDIFF(MINUTE, started_at, ?)
                ),
                last_countdown_update = ?,
                updated_at = ?
            WHERE status = "active" 
            AND started_at IS NOT NULL
            AND auto_countdown = 1
            AND TIMESTAMPDIFF(SECOND, last_countdown_update, ?) >= countdown_interval
        ');
        $stmt->execute([$now, $now, $now, $now]);
    }
    
    echo json_encode([
        'success' => true,
        'completed_sessions' => $completedCount,
        'checked_at' => $now
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de la vérification des sessions',
        'details' => $e->getMessage()
    ]);
}
