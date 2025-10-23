<?php
// api/shop/game_sessions.php
// API pour gérer les sessions de jeu actives

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// L'utilisateur doit être connecté
$user = require_auth();
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer les sessions de l'utilisateur
// ============================================================================
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer une session spécifique
        $stmt = $pdo->prepare('
            SELECT s.*,
                   g.name as game_name, g.slug as game_slug, g.image_url,
                   p.price, p.payment_status
            FROM game_sessions s
            INNER JOIN games g ON s.game_id = g.id
            INNER JOIN purchases p ON s.purchase_id = p.id
            WHERE s.id = ? AND s.user_id = ?
        ');
        $stmt->execute([$id, $user['id']]);
        $session = $stmt->fetch();
        
        if (!$session) {
            json_response(['error' => 'Session non trouvée'], 404);
        }
        
        // Récupérer l'historique d'activité
        $stmt = $pdo->prepare('
            SELECT * FROM session_activities 
            WHERE session_id = ? 
            ORDER BY created_at DESC
        ');
        $stmt->execute([$id]);
        $session['activities'] = $stmt->fetchAll();
        
        json_response(['session' => $session]);
    } else {
        // Récupérer toutes les sessions de l'utilisateur
        $status = $_GET['status'] ?? '';
        
        $sql = '
            SELECT s.*,
                   g.name as game_name, g.slug as game_slug, g.image_url, g.thumbnail_url,
                   p.price, p.payment_status
            FROM game_sessions s
            INNER JOIN games g ON s.game_id = g.id
            INNER JOIN purchases p ON s.purchase_id = p.id
            WHERE s.user_id = ?
        ';
        $params = [$user['id']];
        
        if ($status) {
            $sql .= ' AND s.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY s.created_at DESC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll();
        
        json_response(['sessions' => $sessions]);
    }
}

// ============================================================================
// PATCH: Gérer une session (start, pause, resume, complete)
// ============================================================================
if ($method === 'PATCH') {
    $data = get_json_input();
    $id = $data['id'] ?? null;
    $action = $data['action'] ?? '';
    
    if (!$id) {
        json_response(['error' => 'ID de la session requis'], 400);
    }
    
    // Récupérer la session
    $stmt = $pdo->prepare('
        SELECT s.*, p.payment_status 
        FROM game_sessions s
        INNER JOIN purchases p ON s.purchase_id = p.id
        WHERE s.id = ? AND s.user_id = ?
    ');
    $stmt->execute([$id, $user['id']]);
    $session = $stmt->fetch();
    
    if (!$session) {
        json_response(['error' => 'Session non trouvée'], 404);
    }
    
    // Vérifier que le paiement est complété
    if ($session['payment_status'] !== 'completed') {
        json_response(['error' => 'Le paiement doit être complété avant de démarrer la session'], 400);
    }
    
    $pdo->beginTransaction();
    
    try {
        $ts = now();
        
        switch ($action) {
            case 'start':
                // Démarrer la session
                if ($session['status'] !== 'pending') {
                    json_response(['error' => 'Cette session ne peut pas être démarrée'], 400);
                }
                
                $stmt = $pdo->prepare('
                    UPDATE game_sessions 
                    SET status = "active", 
                        started_at = ?,
                        last_activity_at = ?,
                        updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $ts, $ts, $id]);
                
                // Log l'activité
                $stmt = $pdo->prepare('
                    INSERT INTO session_activities (session_id, activity_type, description, created_at)
                    VALUES (?, "start", "Session démarrée", ?)
                ');
                $stmt->execute([$id, $ts]);
                
                $message = 'Session démarrée avec succès';
                break;
                
            case 'pause':
                // Mettre en pause
                if ($session['status'] !== 'active') {
                    json_response(['error' => 'Seule une session active peut être mise en pause'], 400);
                }
                
                // Calculer le temps utilisé depuis le dernier démarrage/reprise
                $lastStart = $session['resumed_at'] ?? $session['started_at'];
                $minutesUsed = 0;
                if ($lastStart) {
                    $start = new DateTime($lastStart);
                    $now = new DateTime();
                    $minutesUsed = (int)floor(($now->getTimestamp() - $start->getTimestamp()) / 60);
                }
                
                $newUsedMinutes = $session['used_minutes'] + $minutesUsed;
                
                $stmt = $pdo->prepare('
                    UPDATE game_sessions 
                    SET status = "paused",
                        used_minutes = ?,
                        paused_at = ?,
                        last_activity_at = ?,
                        updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$newUsedMinutes, $ts, $ts, $ts, $id]);
                
                // Log l'activité
                $stmt = $pdo->prepare('
                    INSERT INTO session_activities (session_id, activity_type, minutes_used, description, created_at)
                    VALUES (?, "pause", ?, ?, ?)
                ');
                $stmt->execute([$id, $minutesUsed, "Session mise en pause (+{$minutesUsed} min)", $ts]);
                
                $message = 'Session mise en pause';
                break;
                
            case 'resume':
                // Reprendre
                if ($session['status'] !== 'paused') {
                    json_response(['error' => 'Seule une session en pause peut être reprise'], 400);
                }
                
                $stmt = $pdo->prepare('
                    UPDATE game_sessions 
                    SET status = "active",
                        resumed_at = ?,
                        last_activity_at = ?,
                        updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $ts, $ts, $id]);
                
                // Log l'activité
                $stmt = $pdo->prepare('
                    INSERT INTO session_activities (session_id, activity_type, description, created_at)
                    VALUES (?, "resume", "Session reprise", ?)
                ');
                $stmt->execute([$id, $ts]);
                
                $message = 'Session reprise';
                break;
                
            case 'complete':
                // Terminer
                if (!in_array($session['status'], ['active', 'paused'])) {
                    json_response(['error' => 'Cette session ne peut pas être terminée'], 400);
                }
                
                // Calculer le temps final si la session est active
                $finalUsedMinutes = $session['used_minutes'];
                if ($session['status'] === 'active') {
                    $lastStart = $session['resumed_at'] ?? $session['started_at'];
                    if ($lastStart) {
                        $start = new DateTime($lastStart);
                        $now = new DateTime();
                        $minutesUsed = (int)floor(($now->getTimestamp() - $start->getTimestamp()) / 60);
                        $finalUsedMinutes += $minutesUsed;
                    }
                }
                
                $stmt = $pdo->prepare('
                    UPDATE game_sessions 
                    SET status = "completed",
                        used_minutes = ?,
                        completed_at = ?,
                        last_activity_at = ?,
                        updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$finalUsedMinutes, $ts, $ts, $ts, $id]);
                
                // Mettre à jour le statut de l'achat
                $stmt = $pdo->prepare('
                    UPDATE purchases SET session_status = "completed", updated_at = ? WHERE id = ?
                ');
                $stmt->execute([$ts, $session['purchase_id']]);
                
                // Log l'activité
                $stmt = $pdo->prepare('
                    INSERT INTO session_activities (session_id, activity_type, minutes_used, description, created_at)
                    VALUES (?, "complete", ?, ?, ?)
                ');
                $stmt->execute([$id, $finalUsedMinutes, "Session terminée (Total: {$finalUsedMinutes}/{$session['total_minutes']} min)", $ts]);
                
                $message = 'Session terminée avec succès';
                break;
                
            default:
                json_response(['error' => 'Action non reconnue'], 400);
        }
        
        $pdo->commit();
        
        // Récupérer la session mise à jour
        $stmt = $pdo->prepare('
            SELECT s.*,
                   g.name as game_name, g.slug as game_slug, g.image_url
            FROM game_sessions s
            INNER JOIN games g ON s.game_id = g.id
            WHERE s.id = ?
        ');
        $stmt->execute([$id]);
        $updatedSession = $stmt->fetch();
        
        json_response([
            'success' => true,
            'message' => $message,
            'session' => $updatedSession
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['error' => 'Erreur lors du traitement', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405);
