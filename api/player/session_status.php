<?php
// api/player/session_status.php
// API rapide pour vérifier le statut de la session (polling)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

try {
    // Récupérer uniquement les infos essentielles pour un polling rapide,
    // en recalculant le temps utilisé pour les sessions actives à partir de started_at
    $stmt = $pdo->prepare("
        SELECT 
            id,
            status,
            total_minutes,
            
            -- Temps utilisé côté serveur (ne diminue jamais)
            CASE 
                WHEN status = 'active' AND started_at IS NOT NULL THEN
                    LEAST(total_minutes, TIMESTAMPDIFF(MINUTE, started_at, NOW()))
                ELSE used_minutes
            END AS used_minutes,
            
            -- Minutes restantes calculées de façon robuste
            CASE 
                WHEN status = 'active' AND started_at IS NOT NULL THEN
                    GREATEST(0, total_minutes - LEAST(total_minutes, TIMESTAMPDIFF(MINUTE, started_at, NOW())))
                ELSE GREATEST(0, total_minutes - used_minutes)
            END AS remaining_minutes,
            
            -- Pourcentage de progression basé sur le temps réellement utilisé
            CASE 
                WHEN total_minutes > 0 THEN
                    ROUND(
                        (
                            CASE 
                                WHEN status = 'active' AND started_at IS NOT NULL THEN
                                    LEAST(total_minutes, TIMESTAMPDIFF(MINUTE, started_at, NOW()))
                                ELSE used_minutes
                            END
                            / total_minutes
                        ) * 100,
                        1
                    )
                ELSE 0
            END AS progress_percent
        FROM active_game_sessions_v2
        WHERE user_id = ? 
        AND status IN ('ready', 'active', 'paused')
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$user['id']]);
    $session = $stmt->fetch();
    
    json_response([
        'success' => true,
        'has_session' => $session ? true : false,
        'session' => $session,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur',
        'details' => $e->getMessage()
    ], 500);
}
