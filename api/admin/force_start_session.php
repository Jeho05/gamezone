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
    
    // Vérifier que la session est active
    if ($session['status'] !== 'active') {
        json_response([
            'error' => 'Seules les sessions actives peuvent être démarrées',
            'status' => $session['status']
        ], 400);
    }
    
    // Démarrer le chronomètre MAINTENANT
    $stmt = $pdo->prepare('
        UPDATE active_game_sessions_v2
        SET started_at = ?,
            last_heartbeat = ?,
            updated_at = ?
        WHERE id = ?
    ');
    $stmt->execute([$now, $now, $now, $sessionId]);
    
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
