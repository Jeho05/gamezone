<?php
/**
 * UTILITAIRE: Réinitialiser toutes les sessions 'ready' ou 'active' non démarrées
 * Mettre expires_at à +7 jours pour permettre un démarrage propre
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gamezoneismo.vercel.app');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();

try {
    // Trouver toutes les sessions ready ou active sans started_at
    $stmt = $pdo->query('
        SELECT id, status, expires_at, total_minutes
        FROM active_game_sessions_v2
        WHERE status IN ("ready", "active")
        AND started_at IS NULL
    ');
    $sessions = $stmt->fetchAll();
    
    if (empty($sessions)) {
        json_response([
            'success' => true,
            'message' => 'Aucune session à réinitialiser',
            'count' => 0
        ]);
    }
    
    // Mettre à jour expires_at à +7 jours et used_minutes à 0
    $stmt = $pdo->prepare('
        UPDATE active_game_sessions_v2
        SET expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY),
            used_minutes = 0,
            status = "ready",
            updated_at = NOW()
        WHERE id = ?
    ');
    
    $updated = 0;
    foreach ($sessions as $session) {
        $stmt->execute([$session['id']]);
        $updated++;
    }
    
    json_response([
        'success' => true,
        'message' => 'Sessions réinitialisées avec succès',
        'count' => $updated,
        'sessions' => $sessions
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors de la réinitialisation',
        'details' => $e->getMessage()
    ], 500);
}
