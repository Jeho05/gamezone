<?php
/**
 * Forcer le démarrage d'une session (admin)
 * Démarrer le chronomètre manuellement pour une session en attente
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Méthode non autorisée'], 405);
}

$data = get_json_input();
$sessionId = $data['session_id'] ?? null;

if (!$sessionId) {
    json_response(['error' => 'session_id requis'], 400);
}

try {
    $now = date('Y-m-d H:i:s');
    
    // Récupérer la session
    $stmt = $pdo->prepare('
        SELECT id, user_id, total_minutes, started_at, status
        FROM active_game_sessions_v2
        WHERE id = ?
    ');
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    
    if (!$session) {
        json_response(['error' => 'Session introuvable'], 404);
    }
    
    // Vérifier que la session n'est pas déjà démarrée
    if ($session['started_at']) {
        json_response([
            'error' => 'Session déjà démarrée',
            'started_at' => $session['started_at']
        ], 400);
    }
    
    // Autoriser démarrage pour 'ready' ou 'active' mais uniquement si started_at est NULL
    if (!in_array($session['status'], ['ready', 'active'], true)) {
        json_response([
            'error' => 'Statut invalide pour démarrer',
            'status' => $session['status']
        ], 400);
    }
    
    // Démarrer le chronomètre MAINTENANT
    if ($session['status'] === 'ready') {
        $stmt = $pdo->prepare('
            UPDATE active_game_sessions_v2
            SET status = "active",
                started_at = NOW(),
                last_heartbeat = NOW(),
                last_countdown_update = NOW(),
                used_minutes = 0,
                expires_at = DATE_ADD(NOW(), INTERVAL total_minutes MINUTE),
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$sessionId]);
    } else {
        $stmt = $pdo->prepare('
            UPDATE active_game_sessions_v2
            SET started_at = NOW(),
                last_heartbeat = NOW(),
                last_countdown_update = NOW(),
                used_minutes = 0,
                expires_at = DATE_ADD(NOW(), INTERVAL total_minutes MINUTE),
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$sessionId]);
    }
    
    error_log(sprintf(
        '[force_start] Admin %d a démarré la session %d (user %d) à %s',
        $user['id'],
        $sessionId,
        $session['user_id'],
        $now
    ));
    
    json_response([
        'success' => true,
        'message' => 'Session démarrée avec succès',
        'session_id' => $sessionId,
        'started_at' => $now,
        'total_minutes' => $session['total_minutes']
    ]);
    
} catch (Exception $e) {
    error_log('[force_start] Erreur: ' . $e->getMessage());
    json_response([
        'error' => 'Erreur lors du démarrage',
        'details' => $e->getMessage()
    ], 500);
}
