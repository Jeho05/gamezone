<?php
// api/shop/payment_callback.php
// Gestion des callbacks de paiement des providers externes

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();

// Récupérer les données du callback
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?: $_POST;

// Log pour debug
error_log("Payment callback received: " . print_r($data, true));

// Récupérer la référence de l'achat
$reference = $data['reference'] ?? $_GET['reference'] ?? null;
$status = $data['status'] ?? $_GET['status'] ?? 'unknown';
$transactionId = $data['transaction_id'] ?? $_GET['transaction_id'] ?? null;

if (!$reference) {
    http_response_code(400);
    echo json_encode(['error' => 'Reference missing']);
    exit;
}

// Extraire l'ID de l'achat depuis la référence (format: PURCHASE-{id}-{timestamp})
if (preg_match('/PURCHASE-(\d+)-/', $reference, $matches)) {
    $purchaseId = (int)$matches[1];
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid reference format']);
    exit;
}

// Récupérer l'achat
$stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ?');
$stmt->execute([$purchaseId]);
$purchase = $stmt->fetch();

if (!$purchase) {
    http_response_code(404);
    echo json_encode(['error' => 'Purchase not found']);
    exit;
}

$pdo->beginTransaction();

try {
    $ts = now();
    
    // Mapper le statut du provider vers notre statut
    $paymentStatus = 'processing';
    $sessionStatus = 'pending';
    
    switch (strtolower($status)) {
        case 'success':
        case 'completed':
        case 'successful':
            $paymentStatus = 'completed';
            $sessionStatus = 'pending';
            break;
        case 'failed':
        case 'error':
        case 'declined':
            $paymentStatus = 'failed';
            $sessionStatus = 'cancelled';
            break;
        case 'pending':
        case 'processing':
            $paymentStatus = 'processing';
            break;
        case 'cancelled':
            $paymentStatus = 'cancelled';
            $sessionStatus = 'cancelled';
            break;
    }
    // Si le paiement est échoué/annulé, annuler la réservation liée si présente
    if (in_array($paymentStatus, ['failed', 'cancelled'], true)) {
        $stmt = $pdo->prepare('UPDATE game_reservations SET status = "cancelled", updated_at = ? WHERE purchase_id = ?');
        $stmt->execute([$ts, $purchaseId]);
    }
    
    // Mettre à jour l'achat
    $stmt = $pdo->prepare('
        UPDATE purchases 
        SET payment_status = ?,
            session_status = ?,
            payment_reference = ?,
            payment_details = ?,
            updated_at = ?
        WHERE id = ?
    ');
    
    $stmt->execute([
        $paymentStatus,
        $sessionStatus,
        $transactionId,
        json_encode($data),
        $ts,
        $purchaseId
    ]);
    
    // Enregistrer la transaction
    $stmt = $pdo->prepare('
        INSERT INTO payment_transactions (
            purchase_id, transaction_type, amount, currency,
            provider_transaction_id, provider_status, provider_response, notes, created_at
        ) VALUES (?, "charge", ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $purchaseId,
        $purchase['price'],
        $purchase['currency'],
        $transactionId,
        $status,
        json_encode($data),
        "Callback reçu du provider",
        $ts
    ]);
    
    // Si le paiement est complété, gérer session/réservation et points
    if ($paymentStatus === 'completed') {
        // Vérifier s'il s'agit d'une réservation liée à cet achat
        $stmt = $pdo->prepare('SELECT id, scheduled_start FROM game_reservations WHERE purchase_id = ?');
        $stmt->execute([$purchaseId]);
        $reservation = $stmt->fetch();

        if ($reservation) {
            // Marquer la réservation comme payée et mettre à jour les montants
            $stmt = $pdo->prepare('UPDATE game_reservations SET status = "paid", total_price = ?, currency = ?, updated_at = ? WHERE purchase_id = ?');
            $stmt->execute([$purchase['price'], $purchase['currency'], $ts, $purchaseId]);
            
            // Mettre à jour le session_status à 'pending' en attendant le scan de la facture
            $stmt = $pdo->prepare('UPDATE purchases SET session_status = "pending", updated_at = ? WHERE id = ?');
            $stmt->execute([$ts, $purchaseId]);
            
            // Ne pas créer de session immédiate; l'activation se fera à l'heure prévue via scan de facture
            // La facture sera créée automatiquement par le trigger after_purchase_completed
        } else {
            // Achat sans réservation: la facture sera créée par le trigger
            // Mettre à jour le session_status à 'pending' en attendant le scan
            $stmt = $pdo->prepare('UPDATE purchases SET session_status = "pending", updated_at = ? WHERE id = ?');
            $stmt->execute([$ts, $purchaseId]);
            
            // Note: L'ancienne table game_sessions n'est plus utilisée
            // La session sera créée dans active_game_sessions_v2 lors du scan de la facture
        }

        // Créditer les points si pas encore fait
        if (!$purchase['points_credited'] && $purchase['points_earned'] > 0) {
            $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
            $stmt->execute([$purchase['points_earned'], $ts, $purchase['user_id']]);
            $stmt = $pdo->prepare('
                INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at)
                VALUES (?, ?, ?, "game", ?)
            ');
            $stmt->execute([
                $purchase['user_id'],
                $purchase['points_earned'],
                "Achat de temps de jeu: {$purchase['game_name']}",
                $ts
            ]);
            $stmt = $pdo->prepare('UPDATE purchases SET points_credited = 1 WHERE id = ?');
            $stmt->execute([$purchaseId]);
        }
    }
    
    $pdo->commit();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Callback processed successfully',
        'purchase_id' => $purchaseId,
        'payment_status' => $paymentStatus
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Payment callback error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Callback processing failed', 'details' => $e->getMessage()]);
}
