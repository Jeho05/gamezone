<?php
/**
 * Cron job: Mettre à jour used_minutes des sessions actives
 * À exécuter toutes les 1-2 minutes
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

$pdo = get_db();

try {
    $now = date('Y-m-d H:i:s');
    $updatedCount = 0;
    $completedCount = 0;
    
    // Récupérer toutes les sessions actives
    $stmt = $pdo->prepare('
        SELECT id, total_minutes, used_minutes, started_at
        FROM active_game_sessions_v2
        WHERE status = "active"
        AND started_at IS NOT NULL
    ');
    $stmt->execute();
    $sessions = $stmt->fetchAll();
    
    foreach ($sessions as $session) {
        // Calculer minutes écoulées depuis le début
        $start = new DateTime($session['started_at']);
        $current = new DateTime($now);
        $diff = $current->diff($start);
        $elapsedMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        
        // Ne pas dépasser le total
        $newUsedMinutes = min($elapsedMinutes, (int)$session['total_minutes']);
        $remainingMinutes = (int)$session['total_minutes'] - $newUsedMinutes;
        
        // Log
        error_log(sprintf(
            '[update_sessions_time] Session %d: elapsed=%d, used=%d->%d, remaining=%d',
            $session['id'],
            $elapsedMinutes,
            $session['used_minutes'],
            $newUsedMinutes,
            $remainingMinutes
        ));
        
        // Mettre à jour used_minutes
        $stmt = $pdo->prepare('
            UPDATE active_game_sessions_v2
            SET used_minutes = ?,
                last_countdown_update = ?,
                updated_at = ?
            WHERE id = ?
        ');
        $stmt->execute([$newUsedMinutes, $now, $now, $session['id']]);
        $updatedCount++;
        
        // Si temps écoulé, compléter la session
        if ($remainingMinutes <= 0) {
            $pdo->beginTransaction();
            
            try {
                // Marquer session comme complétée
                $stmt = $pdo->prepare('
                    UPDATE active_game_sessions_v2
                    SET status = "completed",
                        completed_at = ?,
                        used_minutes = total_minutes,
                        updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$now, $now, $session['id']]);
                
                // Marquer invoice comme utilisée
                $stmt = $pdo->prepare('
                    UPDATE invoices
                    SET status = "used",
                        used_at = ?,
                        updated_at = ?
                    WHERE id = (SELECT invoice_id FROM active_game_sessions_v2 WHERE id = ?)
                ');
                $stmt->execute([$now, $now, $session['id']]);
                
                // Mettre à jour purchase
                $stmt = $pdo->prepare('
                    UPDATE purchases
                    SET session_status = "completed",
                        updated_at = ?
                    WHERE id = (SELECT purchase_id FROM active_game_sessions_v2 WHERE id = ?)
                ');
                $stmt->execute([$now, $session['id']]);
                
                $pdo->commit();
                $completedCount++;
                
                error_log("[update_sessions_time] Session {$session['id']} complétée automatiquement");
                
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("[update_sessions_time] Erreur complétion session {$session['id']}: " . $e->getMessage());
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'updated' => $updatedCount,
        'completed' => $completedCount,
        'checked_at' => $now
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
