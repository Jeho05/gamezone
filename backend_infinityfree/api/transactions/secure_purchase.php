<?php
/**
 * API Sécurisée pour les Achats avec Gestion des Transactions
 * 
 * PRINCIPE: 
 * 1. Créer une transaction en mode "pending"
 * 2. Vérifier toutes les conditions
 * 3. Débiter SEULEMENT si tout est OK
 * 4. Rollback automatique en cas d'erreur
 * 5. Idempotence: Une transaction ne peut être exécutée qu'une fois
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

$user = require_auth();
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    json_response(['error' => 'Méthode non autorisée'], 405);
}

$data = get_json_input();

// Paramètres requis
$rewardId = $data['reward_id'] ?? null;
$idempotencyKey = $data['idempotency_key'] ?? null; // Clé unique pour éviter les doubles achats

if (!$rewardId) {
    json_response(['error' => 'reward_id requis'], 400);
}

// Générer une clé d'idempotence si non fournie
if (!$idempotencyKey) {
    $idempotencyKey = md5($user['id'] . $rewardId . time());
}

try {
    // ============================================================================
    // ÉTAPE 1: Vérifier si cette transaction existe déjà (idempotence)
    // ============================================================================
    
    $stmt = $pdo->prepare('
        SELECT * FROM purchase_transactions 
        WHERE user_id = ? AND idempotency_key = ?
        LIMIT 1
    ');
    $stmt->execute([$user['id'], $idempotencyKey]);
    $existingTx = $stmt->fetch();
    
    if ($existingTx) {
        // Transaction déjà traitée
        if ($existingTx['status'] === 'completed') {
            json_response([
                'success' => true,
                'message' => 'Transaction déjà effectuée',
                'transaction_id' => $existingTx['id'],
                'purchase_id' => $existingTx['purchase_id'],
                'already_processed' => true
            ]);
        } elseif ($existingTx['status'] === 'failed') {
            json_response([
                'error' => 'Transaction précédente échouée',
                'reason' => $existingTx['failure_reason'],
                'can_retry' => true
            ], 400);
        } elseif ($existingTx['status'] === 'processing') {
            json_response([
                'error' => 'Transaction en cours de traitement',
                'message' => 'Veuillez patienter...',
                'transaction_id' => $existingTx['id']
            ], 409);
        }
    }
    
    // ============================================================================
    // ÉTAPE 2: Démarrer une transaction SQL
    // ============================================================================
    
    $pdo->beginTransaction();
    
    try {
        // ============================================================================
        // ÉTAPE 3: Créer un enregistrement de transaction (status: pending)
        // ============================================================================
        
        $stmt = $pdo->prepare('
            INSERT INTO purchase_transactions (
                user_id, reward_id, idempotency_key, 
                status, step, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            $user['id'], 
            $rewardId, 
            $idempotencyKey,
            'pending',
            'initialized'
        ]);
        $transactionId = $pdo->lastInsertId();
        
        // Log
        log_info('Transaction initialisée', [
            'transaction_id' => $transactionId,
            'user_id' => $user['id'],
            'reward_id' => $rewardId
        ]);
        
        // ============================================================================
        // ÉTAPE 4: Récupérer les détails de la récompense
        // ============================================================================
        
        $stmt = $pdo->prepare('
            SELECT r.*, gp.game_id, gp.name as package_name, gp.duration_minutes, 
                   gp.points_earned as points_per_hour, gp.price, 
                   g.name as game_name, g.slug as game_slug
            FROM rewards r
            INNER JOIN game_packages gp ON r.game_package_id = gp.id
            INNER JOIN games g ON gp.game_id = g.id
            WHERE r.id = ? AND r.available = 1
            LIMIT 1
        ');
        $stmt->execute([$rewardId]);
        $reward = $stmt->fetch();
        
        if (!$reward) {
            throw new Exception('Récompense non trouvée ou inactive');
        }
        
        // Mettre à jour le statut
        $pdo->prepare('UPDATE purchase_transactions SET step = ? WHERE id = ?')
            ->execute(['reward_verified', $transactionId]);
        
        // ============================================================================
        // ÉTAPE 5: Vérifier que l'utilisateur a assez de points (SANS DÉBITER)
        // ============================================================================
        
        $requiredPoints = (int)$reward['cost'];
        
        $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$user['id']]);
        $currentUser = $stmt->fetch();
        $currentPoints = (int)$currentUser['points'];
        
        if ($currentPoints < $requiredPoints) {
            throw new Exception("Points insuffisants. Requis: {$requiredPoints}, Disponible: {$currentPoints}");
        }
        
        // Mettre à jour le statut
        $pdo->prepare('UPDATE purchase_transactions SET step = ? WHERE id = ?')
            ->execute(['points_verified', $transactionId]);
        
        // ============================================================================
        // ÉTAPE 6: MAINTENANT on passe en mode "processing"
        // ============================================================================
        
        $pdo->prepare('UPDATE purchase_transactions SET status = ?, step = ? WHERE id = ?')
            ->execute(['processing', 'debiting_points', $transactionId]);
        
        // ============================================================================
        // ÉTAPE 8: DÉBITER LES POINTS (Point de non-retour)
        // ============================================================================
        
        $stmt = $pdo->prepare('
            UPDATE users 
            SET points = points - ?, 
                updated_at = NOW()
            WHERE id = ? AND points >= ?
        ');
        $stmt->execute([$requiredPoints, $user['id'], $requiredPoints]);
        
        // Double-vérification: le UPDATE a-t-il réussi ?
        if ($stmt->rowCount() === 0) {
            throw new Exception('Échec du débit des points (race condition détectée)');
        }
        
        // Log de la transaction de points
        $stmt = $pdo->prepare('
            INSERT INTO points_transactions (
                user_id, type, change_amount, reason, created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            $user['id'],
            'reward',
            -$requiredPoints,
            'Échange récompense: ' . $reward['game_name']
        ]);
        $pointsTxId = $pdo->lastInsertId();
        
        // Mettre à jour le statut
        $pdo->prepare('UPDATE purchase_transactions SET step = ?, points_tx_id = ? WHERE id = ?')
            ->execute(['points_debited', $pointsTxId, $transactionId]);
        
        // ============================================================================
        // ÉTAPE 9: Créer le Purchase
        // ============================================================================
        
        $stmt = $pdo->prepare('
            INSERT INTO purchases (
                user_id, game_id, game_name, package_name, 
                duration_minutes, points_earned,
                price, currency, payment_method_name,
                payment_status, paid_with_points, session_status,
                transaction_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([
            $user['id'],
            $reward['game_id'],
            $reward['game_name'],
            $reward['package_name'],
            $reward['duration_minutes'],
            $reward['points_per_hour'],
            0, // Prix = 0 car payé en points
            'XOF',
            'points',
            'completed', // Déjà payé
            1, // paid_with_points
            'pending', // session pas encore démarrée
            $transactionId
        ]);
        $purchaseId = $pdo->lastInsertId();
        
        // Mettre à jour le statut
        $pdo->prepare('UPDATE purchase_transactions SET step = ?, purchase_id = ? WHERE id = ?')
            ->execute(['purchase_created', $purchaseId, $transactionId]);
        
        // ============================================================================
        // ÉTAPE 11: Marquer la transaction comme COMPLÉTÉE
        // ============================================================================
        
        $pdo->prepare('
            UPDATE purchase_transactions 
            SET status = ?, step = ?, completed_at = NOW()
            WHERE id = ?
        ')->execute(['completed', 'finished', $transactionId]);
        
        // ============================================================================
        // ÉTAPE 12: COMMIT de la transaction SQL
        // ============================================================================
        
        $pdo->commit();
        
        log_info('Transaction complétée avec succès', [
            'transaction_id' => $transactionId,
            'purchase_id' => $purchaseId,
            'user_id' => $user['id'],
            'points_spent' => $requiredPoints
        ]);
        
        // Retourner le succès
        json_response([
            'success' => true,
            'message' => 'Échange effectué avec succès',
            'transaction_id' => $transactionId,
            'purchase_id' => $purchaseId,
            'points_spent' => $requiredPoints,
            'new_balance' => $currentPoints - $requiredPoints,
            'game_name' => $reward['game_name'],
            'duration_minutes' => $reward['duration_minutes']
        ]);
        
    } catch (Exception $e) {
        // ============================================================================
        // ROLLBACK EN CAS D'ERREUR
        // ============================================================================
        
        $pdo->rollBack();
        
        // Enregistrer l'échec
        if (isset($transactionId)) {
            $pdo->prepare('
                UPDATE purchase_transactions 
                SET status = ?, failure_reason = ?, failed_at = NOW()
                WHERE id = ?
            ')->execute(['failed', $e->getMessage(), $transactionId]);
        }
        
        log_error('Transaction échouée - Rollback effectué', [
            'transaction_id' => $transactionId ?? 'unknown',
            'user_id' => $user['id'],
            'error' => $e->getMessage(),
            'step' => isset($transactionId) ? 'check table' : 'init'
        ]);
        
        throw $e; // Re-throw pour le catch externe
    }
    
} catch (Exception $e) {
    json_response([
        'error' => 'Échec de la transaction',
        'message' => $e->getMessage(),
        'safe' => true, // Indique qu'aucun point n'a été perdu
        'can_retry' => true
    ], 400);
}
