<?php
// api/admin/manage_session.php
// API pour gérer les sessions de jeu (ADMIN ONLY)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
if (!is_admin($user)) {
    json_response(['error' => 'Accès refusé - Admin uniquement'], 403);
}

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// GET: Récupérer les sessions actives
if ($method === 'GET') {
    $sessionId = $_GET['id'] ?? null;
    
    if ($sessionId) {
        // Session spécifique avec tous les détails
        $stmt = $pdo->prepare('
            SELECT s.*,
                   i.invoice_number, i.validation_code, i.status as invoice_status,
                   u.username, u.email,
                   g.name as game_name,
                   ROUND((s.used_minutes / s.total_minutes) * 100, 1) as progress_percent,
                   LEAST(s.total_minutes,
                        CASE WHEN s.started_at IS NULL THEN 0 ELSE TIMESTAMPDIFF(MINUTE, s.started_at, NOW()) END
                   ) AS server_used_minutes,
                   GREATEST(0,
                        s.total_minutes - LEAST(s.total_minutes,
                            CASE WHEN s.started_at IS NULL THEN 0 ELSE TIMESTAMPDIFF(MINUTE, s.started_at, NOW()) END
                        )
                   ) AS server_remaining_minutes
            FROM active_game_sessions_v2 s
            INNER JOIN invoices i ON s.invoice_id = i.id
            INNER JOIN users u ON s.user_id = u.id
            INNER JOIN games g ON s.game_id = g.id
            WHERE s.id = ?
        ');
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if (!$session) {
            json_response(['error' => 'Session non trouvée'], 404);
        }
        
        // Récupérer les événements récents
        $stmt = $pdo->prepare('
            SELECT * FROM session_events 
            WHERE session_id = ? 
            ORDER BY created_at DESC
            LIMIT 50
        ');
        $stmt->execute([$sessionId]);
        $session['events'] = $stmt->fetchAll();
        
        json_response(['session' => $session]);
    } else {
        // Toutes les sessions avec filtres
        $status = $_GET['status'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        
        $sql = '
            SELECT s.*,
                   i.invoice_number, i.validation_code,
                   u.username, u.email,
                   g.name as game_name,
                   ROUND((s.used_minutes / s.total_minutes) * 100, 1) as progress_percent,
                   LEAST(s.total_minutes,
                        CASE WHEN s.started_at IS NULL THEN 0 ELSE TIMESTAMPDIFF(MINUTE, s.started_at, NOW()) END
                   ) AS server_used_minutes,
                   GREATEST(0,
                        s.total_minutes - LEAST(s.total_minutes,
                            CASE WHEN s.started_at IS NULL THEN 0 ELSE TIMESTAMPDIFF(MINUTE, s.started_at, NOW()) END
                        )
                   ) AS server_remaining_minutes
            FROM active_game_sessions_v2 s
            INNER JOIN invoices i ON s.invoice_id = i.id
            INNER JOIN users u ON s.user_id = u.id
            INNER JOIN games g ON s.game_id = g.id
            WHERE 1=1
        ';
        $params = [];
        
        if ($status) {
            $sql .= ' AND s.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY s.created_at DESC LIMIT ?';
        $params[] = $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll();
        
        // Statistiques
        $stmt = $pdo->query('
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = "ready" THEN 1 ELSE 0 END) as ready,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = "paused" THEN 1 ELSE 0 END) as paused,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
            FROM active_game_sessions_v2
        ');
        $stats = $stmt->fetch();
        
        json_response([
            'sessions' => $sessions,
            'stats' => $stats
        ]);
    }
}

// POST: Démarrer une session
if ($method === 'POST') {
    $data = get_json_input();
    $sessionId = $data['session_id'] ?? null;
    $action = $data['action'] ?? '';
    
    if (!$sessionId) {
        json_response(['error' => 'session_id requis'], 400);
    }
    
    $pdo->beginTransaction();
    
    try {
        $ts = now();
        // Lock the session row to prevent concurrent state changes (except for 'start' handled by stored procedure)
        if (in_array($action, ['pause', 'resume', 'terminate', 'complete'], true)) {
            $lockStmt = $pdo->prepare('SELECT id FROM active_game_sessions_v2 WHERE id = ? FOR UPDATE');
            $lockStmt->execute([$sessionId]);
        }
        
        if ($action === 'start') {
            // Tenter d'utiliser la procédure stockée
            $procOk = false;
            try {
                $stmt = $pdo->prepare('CALL start_session(?, ?, @result)');
                $stmt->execute([$sessionId, $user['id']]);
                $stmt = $pdo->query('SELECT @result as result');
                $result = $stmt->fetch();
                $procOk = ($result['result'] ?? '') === 'success';
            } catch (Throwable $e) {
                $procOk = false;
            }

            if (!$procOk) {
                // Fallback direct: passer la session à active
                $stmt = $pdo->prepare('
                    UPDATE active_game_sessions_v2 SET
                        status = "active",
                        started_at = NOW(),
                        last_heartbeat = NOW(),
                        last_countdown_update = NOW(),
                        used_minutes = 0,
                        expires_at = DATE_ADD(NOW(), INTERVAL total_minutes MINUTE),
                        updated_at = NOW()
                    WHERE id = ? AND status = "ready"
                ');
                $stmt->execute([$sessionId]);
                if ($stmt->rowCount() === 0) {
                    $pdo->rollBack();
                    json_response(['error' => 'Impossible de démarrer la session (statut non prêt)'], 400);
                }
                // Event
                $evt = $pdo->prepare('
                    INSERT INTO session_events (session_id, event_type, event_message, triggered_by, created_at)
                    VALUES (?, "start", "Session démarrée (fallback)", ?, ?)
                ');
                $evt->execute([$sessionId, $user['id'], $ts]);
            }

            // Note: Le trigger sync_session_to_purchase mettra à jour automatiquement
            // purchases.session_status quand la procédure start_session modifie le statut

            $message = 'Session démarrée avec succès';
            
        } elseif ($action === 'pause') {
            $stmt = $pdo->prepare('
                UPDATE active_game_sessions_v2 SET
                    status = "paused",
                    paused_at = ?,
                    pause_count = pause_count + 1,
                    updated_at = ?
                WHERE id = ? AND status = "active"
            ');
            $stmt->execute([$ts, $ts, $sessionId]);
            
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                json_response(['error' => 'Session non trouvée ou statut invalide'], 400);
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO session_events (
                    session_id, event_type, event_message, triggered_by, created_at
                ) VALUES (?, "pause", "Session mise en pause par admin", ?, ?)
            ');
            $stmt->execute([$sessionId, $user['id'], $ts]);
            
            $message = 'Session mise en pause';
            
        } elseif ($action === 'resume') {
            $stmt = $pdo->prepare('
                UPDATE active_game_sessions_v2 SET
                    status = "active",
                    resumed_at = ?,
                    last_heartbeat = ?,
                    last_countdown_update = ?,
                    updated_at = ?
                WHERE id = ? AND status = "paused"
            ');
            $stmt->execute([$ts, $ts, $ts, $ts, $sessionId]);
            
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                json_response(['error' => 'Session non trouvée ou statut invalide'], 400);
            }
            // Note: Le trigger sync_session_to_purchase synchronisera automatiquement
            
            $stmt = $pdo->prepare('
                INSERT INTO session_events (
                    session_id, event_type, event_message, triggered_by, created_at
                ) VALUES (?, "resume", "Session reprise par admin", ?, ?)
            ');
            $stmt->execute([$sessionId, $user['id'], $ts]);
            
            $message = 'Session reprise';
            
        } elseif ($action === 'terminate') {
            $stmt = $pdo->prepare('
                UPDATE active_game_sessions_v2 SET
                    status = "terminated",
                    completed_at = ?,
                    updated_at = ?
                WHERE id = ? AND status IN ("ready", "active", "paused")
            ');
            $stmt->execute([$ts, $ts, $sessionId]);
            
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                json_response(['error' => 'Session non trouvée ou déjà terminée'], 400);
            }
            
            // Marquer la facture comme utilisée
            $stmt = $pdo->prepare('
                UPDATE invoices SET status = "used", used_at = ?, updated_at = ?
                WHERE id = (SELECT invoice_id FROM active_game_sessions_v2 WHERE id = ?)
            ');
            $stmt->execute([$ts, $ts, $sessionId]);
            
            // Note: Le trigger sync_session_to_purchase synchronisera automatiquement purchases.session_status
            
            $stmt = $pdo->prepare('
                INSERT INTO session_events (
                    session_id, event_type, event_message, triggered_by, created_at
                ) VALUES (?, "terminate", "Session terminée par admin", ?, ?)
            ');
            $stmt->execute([$sessionId, $user['id'], $ts]);
            
            $message = 'Session terminée';
            
        } elseif ($action === 'complete') {
            // Complétion automatique quand le temps est écoulé
            $stmt = $pdo->prepare('
                UPDATE active_game_sessions_v2 SET
                    status = "completed",
                    completed_at = ?,
                    used_minutes = total_minutes,
                    updated_at = ?
                WHERE id = ? AND status IN ("active", "paused")
            ');
            $stmt->execute([$ts, $ts, $sessionId]);
            
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                json_response(['error' => 'Session non trouvée ou déjà terminée'], 400);
            }
            
            // Marquer la facture comme utilisée
            $stmt = $pdo->prepare('
                UPDATE invoices SET status = "used", used_at = ?, updated_at = ?
                WHERE id = (SELECT invoice_id FROM active_game_sessions_v2 WHERE id = ?)
            ');
            $stmt->execute([$ts, $ts, $sessionId]);
            
            // Note: Le trigger sync_session_to_purchase synchronisera automatiquement purchases.session_status
            
            $stmt = $pdo->prepare('
                INSERT INTO session_events (
                    session_id, event_type, event_message, triggered_by, created_at
                ) VALUES (?, "complete", "Session terminée automatiquement (temps écoulé)", ?, ?)
            ');
            $stmt->execute([$sessionId, $user['id'], $ts]);
            
            $message = 'Session complétée automatiquement';
            
        } else {
            $pdo->rollBack();
            json_response(['error' => 'Action non reconnue'], 400);
        }
        
        $pdo->commit();
        
        // Récupérer la session mise à jour depuis la vue
        $stmt = $pdo->prepare('SELECT * FROM session_summary WHERE id = ?');
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if (!$session) {
            // Fallback: récupérer depuis la table directement
            $stmt = $pdo->prepare('SELECT * FROM active_game_sessions_v2 WHERE id = ?');
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();
        }
        
        json_response([
            'success' => true,
            'message' => $message,
            'session' => $session
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['error' => 'Erreur lors du traitement', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405);
