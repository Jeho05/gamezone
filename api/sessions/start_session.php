<?php
// api/sessions/start_session.php
// API pour démarrer une session de jeu après confirmation du paiement

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

require_method(['POST']);
$data = get_json_input();

$purchaseId = $data['purchase_id'] ?? null;

if (!$purchaseId) {
    json_response(['error' => 'Purchase ID requis'], 400);
}

$pdo->beginTransaction();

try {
    // Récupérer l'achat
    $stmt = $pdo->prepare('
        SELECT p.*, g.points_per_hour, g.name as game_name
        FROM purchases p
        INNER JOIN games g ON p.game_id = g.id
        WHERE p.id = ? AND p.user_id = ?
    ');
    $stmt->execute([$purchaseId, $user['id']]);
    $purchase = $stmt->fetch();
    
    if (!$purchase) {
        json_response(['error' => 'Achat non trouvé ou vous n\'avez pas accès'], 404);
    }
    
    // Vérifier que le paiement est confirmé
    if ($purchase['payment_status'] !== 'completed') {
        json_response(['error' => 'Le paiement n\'a pas encore été confirmé'], 400);
    }
    
    // Vérifier qu'il n'y a pas déjà une session active
    $stmt = $pdo->prepare('
        SELECT id FROM game_sessions 
        WHERE purchase_id = ? AND status IN ("active", "paused")
    ');
    $stmt->execute([$purchaseId]);
    if ($stmt->fetch()) {
        json_response(['error' => 'Une session est déjà active pour cet achat'], 400);
    }
    
    // Vérifier s'il existe déjà une session (même complétée)
    $stmt = $pdo->prepare('SELECT id FROM game_sessions WHERE purchase_id = ?');
    $stmt->execute([$purchaseId]);
    $existingSession = $stmt->fetch();
    
    if ($existingSession) {
        // Réactiver la session existante
        $ts = now();
        $stmt = $pdo->prepare('
            UPDATE game_sessions 
            SET status = "active", started_at = ?, last_activity_at = ?, updated_at = ?
            WHERE id = ?
        ');
        $stmt->execute([$ts, $ts, $ts, $existingSession['id']]);
        $sessionId = $existingSession['id'];
    } else {
        // Créer une nouvelle session
        $ts = now();
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$purchase['duration_minutes']} minutes"));
        
        $stmt = $pdo->prepare('
            INSERT INTO game_sessions (
                purchase_id, user_id, game_id,
                total_minutes, used_minutes, status,
                started_at, expires_at, last_activity_at,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $purchaseId,
            $user['id'],
            $purchase['game_id'],
            $purchase['duration_minutes'],
            0, // used_minutes = 0 au départ
            'active',
            $ts,
            $expiresAt,
            $ts,
            $ts,
            $ts
        ]);
        
        $sessionId = $pdo->lastInsertId();
    }
    
    // Mettre à jour le statut de la session dans l'achat
    $stmt = $pdo->prepare('UPDATE purchases SET session_status = "active", updated_at = ? WHERE id = ?');
    $stmt->execute([now(), $purchaseId]);
    
    // Récupérer la session créée
    $stmt = $pdo->prepare('
        SELECT s.*, 
               g.name as game_name, 
               g.points_per_hour,
               p.package_name
        FROM game_sessions s
        INNER JOIN purchases p ON s.purchase_id = p.id
        INNER JOIN games g ON s.game_id = g.id
        WHERE s.id = ?
    ');
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    
    $pdo->commit();
    
    json_response([
        'success' => true,
        'message' => 'Session démarrée avec succès',
        'session' => $session,
        'points_calculation' => [
            'points_per_hour' => (int)$session['points_per_hour'],
            'total_minutes' => (int)$session['total_minutes'],
            'max_points' => (int)round(($session['total_minutes'] / 60) * $session['points_per_hour']),
            'formula' => '(minutes / 60) × points_per_hour'
        ]
    ], 201);
    
} catch (Exception $e) {
    $pdo->rollBack();
    json_response(['error' => 'Erreur lors du démarrage de la session', 'details' => $e->getMessage()], 500);
}
