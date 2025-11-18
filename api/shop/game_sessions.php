<?php
// api/shop/game_sessions.php
// API pour l'historique des sessions de jeu (et sessions actives)
// Remplace l'ancienne logique basée sur la table game_sessions obsolète

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer une session spécifique
        $stmt = $pdo->prepare('
            SELECT s.*,
                   g.name as game_name, g.slug as game_slug, g.image_url,
                   p.price, p.payment_status
            FROM active_game_sessions_v2 s
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
            SELECT * FROM session_events 
            WHERE session_id = ? 
            ORDER BY created_at DESC
        ');
        $stmt->execute([$id]);
        $session['events'] = $stmt->fetchAll();
        
        json_response(['session' => $session]);
    } else {
        // Récupérer l'historique des sessions
        $status = $_GET['status'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 20), 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = '
            SELECT s.*,
                   g.name as game_name, g.slug as game_slug, g.image_url, g.thumbnail_url,
                   p.price, p.payment_status,
                   i.invoice_number
            FROM active_game_sessions_v2 s
            INNER JOIN games g ON s.game_id = g.id
            INNER JOIN purchases p ON s.purchase_id = p.id
            LEFT JOIN invoices i ON s.invoice_id = i.id
            WHERE s.user_id = ?
        ';
        $params = [$user['id']];
        
        if ($status) {
            $sql .= ' AND s.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY s.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll();
        
        json_response(['sessions' => $sessions, 'count' => count($sessions)]);
    }
}

// PATCH n'est plus géré ici pour les actions de session (start/pause/etc)
// Utilisez les endpoints spécifiques api/player/start_session.php etc.
// ou api/player/session_heartbeat.php

json_response(['error' => 'Method not allowed. Use player/ endpoints for session control.'], 405);
