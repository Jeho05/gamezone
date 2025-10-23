<?php
// api/sessions/update_session.php
// API pour mettre à jour une session de jeu et calculer les points en temps réel

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

require_method(['POST', 'PATCH']);
$data = get_json_input();

$sessionId = $data['session_id'] ?? null;
$action = $data['action'] ?? null; // 'start', 'pause', 'resume', 'complete', 'update_time'

if (!$sessionId) {
    json_response(['error' => 'Session ID requis'], 400);
}

$pdo->beginTransaction();

try {
    // Récupérer la session
    $stmt = $pdo->prepare('
        SELECT s.*, p.user_id, p.purchase_id, g.points_per_hour, g.name as game_name
        FROM game_sessions s
        INNER JOIN purchases p ON s.purchase_id = p.id
        INNER JOIN games g ON s.game_id = g.id
        WHERE s.id = ? AND p.user_id = ?
    ');
    $stmt->execute([$sessionId, $user['id']]);
    $session = $stmt->fetch();
    
    if (!$session) {
        json_response(['error' => 'Session non trouvée ou vous n\'avez pas accès'], 404);
    }
    
    $ts = now();
    $updates = [];
    $params = [];
    
    // Gérer les différentes actions
    switch ($action) {
        case 'start':
            if ($session['status'] !== 'pending') {
                json_response(['error' => 'Cette session a déjà été démarrée'], 400);
            }
            $updates[] = "status = 'active'";
            $updates[] = "started_at = ?";
            $params[] = $ts;
            $updates[] = "last_activity_at = ?";
            $params[] = $ts;
            break;
            
        case 'pause':
            if ($session['status'] !== 'active') {
                json_response(['error' => 'Cette session n\'est pas active'], 400);
            }
            $updates[] = "status = 'paused'";
            $updates[] = "paused_at = ?";
            $params[] = $ts;
            break;
            
        case 'resume':
            if ($session['status'] !== 'paused') {
                json_response(['error' => 'Cette session n\'est pas en pause'], 400);
            }
            $updates[] = "status = 'active'";
            $updates[] = "resumed_at = ?";
            $params[] = $ts;
            $updates[] = "last_activity_at = ?";
            $params[] = $ts;
            break;
            
        case 'update_time':
            // Mettre à jour le temps utilisé
            $usedMinutes = $data['used_minutes'] ?? null;
            if ($usedMinutes === null) {
                json_response(['error' => 'used_minutes requis'], 400);
            }
            
            // Vérifier que le temps utilisé ne dépasse pas le temps total
            if ($usedMinutes > $session['total_minutes']) {
                $usedMinutes = $session['total_minutes'];
            }
            
            $updates[] = "used_minutes = ?";
            $params[] = $usedMinutes;
            $updates[] = "last_activity_at = ?";
            $params[] = $ts;
            
            // Si tout le temps est utilisé, marquer comme complété
            if ($usedMinutes >= $session['total_minutes']) {
                $updates[] = "status = 'completed'";
                $updates[] = "completed_at = ?";
                $params[] = $ts;
            }
            break;
            
        case 'complete':
            if ($session['status'] === 'completed') {
                json_response(['error' => 'Cette session est déjà complétée'], 400);
            }
            
            // Utiliser le temps spécifié ou le temps total
            $finalUsedMinutes = $data['used_minutes'] ?? $session['total_minutes'];
            if ($finalUsedMinutes > $session['total_minutes']) {
                $finalUsedMinutes = $session['total_minutes'];
            }
            
            $updates[] = "status = 'completed'";
            $updates[] = "completed_at = ?";
            $params[] = $ts;
            $updates[] = "used_minutes = ?";
            $params[] = $finalUsedMinutes;
            break;
            
        default:
            json_response(['error' => 'Action invalide'], 400);
    }
    
    // Appliquer les mises à jour
    if (!empty($updates)) {
        $updates[] = "updated_at = ?";
        $params[] = $ts;
        $params[] = $sessionId;
        
        $sql = "UPDATE game_sessions SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    // Récupérer la session mise à jour
    $stmt = $pdo->prepare('
        SELECT s.*, p.user_id, p.purchase_id, p.id as purchase_id_ref, g.points_per_hour, g.name as game_name
        FROM game_sessions s
        INNER JOIN purchases p ON s.purchase_id = p.id
        INNER JOIN games g ON s.game_id = g.id
        WHERE s.id = ?
    ');
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    
    // CALCUL AUTOMATIQUE DES POINTS basé sur le temps réel joué
    if ($action === 'complete' || $action === 'update_time') {
        $usedMinutes = $session['used_minutes'];
        $pointsPerHour = $session['points_per_hour'];
        
        // Calculer les points de base: (temps en heures) * points par heure
        $basePoints = ($usedMinutes / 60) * $pointsPerHour;
        
        // Vérifier les bonus multipliers actifs pour l'utilisateur
        $stmt = $pdo->prepare('
            SELECT multiplier, reason 
            FROM bonus_multipliers 
            WHERE user_id = ? AND expires_at > NOW() 
            ORDER BY multiplier DESC 
            LIMIT 1
        ');
        $stmt->execute([$user['id']]);
        $multiplierRow = $stmt->fetch();
        $multiplier = $multiplierRow ? (float)$multiplierRow['multiplier'] : 1.0;
        $multiplierReason = $multiplierRow ? $multiplierRow['reason'] : null;
        
        // Appliquer le multiplicateur et arrondir
        $calculatedPoints = (int)round($basePoints * $multiplier);
        
        // Vérifier si des points ont déjà été crédités pour cette session
        $stmt = $pdo->prepare('
            SELECT SUM(change_amount) as total_credited
            FROM points_transactions
            WHERE reference_type = "game_session" AND reference_id = ?
        ');
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch();
        $alreadyCredited = (int)($result['total_credited'] ?? 0);
        
        // Créditer uniquement la différence
        $pointsToCredit = $calculatedPoints - $alreadyCredited;
        
        if ($pointsToCredit > 0) {
            // Ajouter les points à l'utilisateur
            $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
            $stmt->execute([$pointsToCredit, $ts, $user['id']]);
            
            // Enregistrer la transaction de points
            $reason = sprintf("Temps de jeu: %s (%d min × %d pts/h", 
                $session['game_name'], 
                $usedMinutes, 
                $pointsPerHour
            );
            
            if ($multiplier > 1.0) {
                $reason .= sprintf(" × %.1fx bonus = %d pts)", $multiplier, $pointsToCredit);
                if ($multiplierReason) {
                    $reason .= " - " . $multiplierReason;
                }
            } else {
                $reason .= sprintf(" = %d pts)", $pointsToCredit);
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO points_transactions (user_id, change_amount, reason, type, reference_type, reference_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $user['id'],
                $pointsToCredit,
                $reason,
                'game_session',
                'game_session',
                $sessionId,
                $ts
            ]);
            
            // Mettre à jour les statistiques
            $stmt = $pdo->prepare('
                INSERT INTO user_stats (user_id, total_points_earned, games_played, updated_at) 
                VALUES (?, ?, 1, ?) 
                ON DUPLICATE KEY UPDATE 
                    total_points_earned = total_points_earned + ?,
                    games_played = games_played + IF(? = "complete", 1, 0),
                    updated_at = ?
            ');
            $stmt->execute([$user['id'], $pointsToCredit, $ts, $pointsToCredit, $action, $ts]);
            
            $session['points_credited'] = $calculatedPoints;
            $session['points_this_update'] = $pointsToCredit;
        } else {
            $session['points_credited'] = $alreadyCredited;
            $session['points_this_update'] = 0;
        }
        
        $session['calculated_points'] = $calculatedPoints;
        $session['base_points'] = (int)round($basePoints);
        $session['bonus_multiplier'] = $multiplier;
        $session['bonus_multiplier_reason'] = $multiplierReason;
    }
    
    $pdo->commit();
    
    json_response([
        'success' => true,
        'message' => 'Session mise à jour avec succès',
        'session' => $session,
        'action' => $action
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    json_response(['error' => 'Erreur lors de la mise à jour de la session', 'details' => $e->getMessage()], 500);
}
