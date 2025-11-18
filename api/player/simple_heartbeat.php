<?php
/**
 * Heartbeat SIMPLIFIÉ avec "Lazy Countdown"
 * Met à jour last_heartbeat ET used_minutes si nécessaire
 * Sert de fallback si le Cron Job n'est pas configuré
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

try {
    $now = date('Y-m-d H:i:s');
    
    // Récupérer la session active
    $stmt = $pdo->prepare('
        SELECT id, total_minutes, used_minutes, started_at, status, last_countdown_update, invoice_id, purchase_id
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
    
    // --- LAZY COUNTDOWN LOGIC ---
    // Si last_countdown_update est vieux de plus de 1 minute (ou NULL), on met à jour
    $lastUpdate = $session['last_countdown_update'] ? strtotime($session['last_countdown_update']) : strtotime($session['started_at']);
    $currentTime = time();
    $minutesToAdd = 0;
    
    // On ne met à jour que si au moins 1 minute s'est écoulée
    if (($currentTime - $lastUpdate) >= 60) {
        $minutesToAdd = floor(($currentTime - $lastUpdate) / 60);
    }
    
    $newUsedMinutes = (int)$session['used_minutes'] + $minutesToAdd;
    $totalMinutes = (int)$session['total_minutes'];
    $isCompleted = $newUsedMinutes >= $totalMinutes;
    
    if ($minutesToAdd > 0) {
        if ($isCompleted) {
            // TERMINER LA SESSION
            $newUsedMinutes = $totalMinutes; // Cap à total
            
            $stmt = $pdo->prepare('
                UPDATE active_game_sessions_v2
                SET status = "completed",
                    used_minutes = ?,
                    completed_at = ?,
                    last_countdown_update = ?,
                    last_heartbeat = ?,
                    updated_at = ?
                WHERE id = ?
            ');
            $stmt->execute([$newUsedMinutes, $now, $now, $now, $now, $session['id']]);
            
            // Mettre à jour Invoice
            $stmt = $pdo->prepare('UPDATE invoices SET status = "used", used_at = ?, updated_at = ? WHERE id = ?');
            $stmt->execute([$now, $now, $session['invoice_id']]);
            
            // Mettre à jour Purchase
            $stmt = $pdo->prepare('UPDATE purchases SET session_status = "completed", updated_at = ? WHERE id = ?');
            $stmt->execute([$now, $session['purchase_id']]);
            
            // Log event
            $stmt = $pdo->prepare('
                INSERT INTO session_events (session_id, event_type, event_message, minutes_after, triggered_by_system, created_at)
                VALUES (?, "complete", "Session terminée - temps écoulé (Heartbeat)", 0, 1, ?)
            ');
            $stmt->execute([$session['id'], $now]);
            
            $session['status'] = 'completed';
            
        } else {
            // METTRE À JOUR LE TEMPS
            $stmt = $pdo->prepare('
                UPDATE active_game_sessions_v2
                SET used_minutes = ?,
                    last_countdown_update = ?,
                    last_heartbeat = ?,
                    updated_at = ?
                WHERE id = ?
            ');
            $stmt->execute([$newUsedMinutes, $now, $now, $now, $session['id']]);
            
            // Log event (optionnel, pour éviter de spammer on ne log que les gros sauts ou pas du tout pour heartbeat)
            // Ici on ne log pas pour garder le heartbeat léger
        }
    } else {
        // Juste mettre à jour le heartbeat
        $stmt = $pdo->prepare('
            UPDATE active_game_sessions_v2
            SET last_heartbeat = ?,
                updated_at = ?
            WHERE id = ?
        ');
        $stmt->execute([$now, $now, $session['id']]);
    }
    
    // Retourner l'état actuel
    json_response([
        'success' => true,
        'remaining_minutes' => max(0, $totalMinutes - $newUsedMinutes),
        'used_minutes' => $newUsedMinutes,
        'status' => $session['status'],
        'heartbeat_updated' => true,
        'minutes_added' => $minutesToAdd
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur heartbeat',
        'details' => $e->getMessage()
    ], 500);
}
