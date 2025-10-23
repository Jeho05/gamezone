<?php
// api/shop/confirm_my_purchase.php
// Permet au joueur de confirmer son propre achat (paiement espèces)
// Cela génère automatiquement la facture avec QR code

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

require_method(['POST']);
$data = get_json_input();

$purchaseId = $data['purchase_id'] ?? null;

if (!$purchaseId) {
    json_response(['error' => 'purchase_id requis'], 400);
}

$pdo->beginTransaction();

try {
    // Vérifier que l'achat appartient à l'utilisateur
    $stmt = $pdo->prepare('
        SELECT * FROM purchases 
        WHERE id = ? AND user_id = ?
    ');
    $stmt->execute([$purchaseId, $user['id']]);
    $purchase = $stmt->fetch();
    
    if (!$purchase) {
        json_response(['error' => 'Achat non trouvé'], 404);
    }
    
    $ts = now();
    
    // Si l'achat est payé en points, il est déjà completed
    $alreadyCompleted = ($purchase['payment_status'] === 'completed');
    
    if (!$alreadyCompleted) {
        // Vérifier que le paiement est en attente
        if ($purchase['payment_status'] !== 'pending') {
            json_response(['error' => 'Cet achat a déjà été traité'], 400);
        }
        
        // Confirmer le paiement
        $stmt = $pdo->prepare('
            UPDATE purchases 
            SET payment_status = "completed",
                confirmed_at = ?,
                updated_at = ?
            WHERE id = ?
        ');
        $stmt->execute([$ts, $ts, $purchaseId]);
    }
    
    // Vérifier si la facture existe déjà
    $stmt = $pdo->prepare('SELECT id FROM invoices WHERE purchase_id = ?');
    $stmt->execute([$purchaseId]);
    $existingInvoice = $stmt->fetch();
    
    // Créer la facture si elle n'existe pas
    if (!$existingInvoice) {
        // Générer numéro de facture unique
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($purchaseId, 6, '0', STR_PAD_LEFT);
        
        // Générer code de validation (16 caractères, formaté en 4 groupes de 4)
        $rawCode = strtoupper(substr(md5($purchaseId . time()), 0, 16));
        $validationCode = substr($rawCode, 0, 4) . '-' . substr($rawCode, 4, 4) . '-' . substr($rawCode, 8, 4) . '-' . substr($rawCode, 12, 4);
        
        // Date d'expiration : 2 mois après la création
        $expiresAt = date('Y-m-d H:i:s', strtotime('+2 months'));
        
        $stmt = $pdo->prepare('
            INSERT INTO invoices (
                purchase_id, user_id, invoice_number, validation_code,
                status, expires_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $purchaseId,
            $user['id'],
            $invoiceNumber,
            $validationCode,
            'pending',
            $expiresAt,
            $ts,
            $ts
        ]);
    }
    
    // Créditer les points uniquement pour les achats non payés en points
    // Pour les achats en points, les points bonus seront crédités après la session
    if (!$purchase['points_credited'] && !$purchase['paid_with_points']) {
        $stmt = $pdo->prepare('
            UPDATE users 
            SET points = points + ?
            WHERE id = ?
        ');
        $stmt->execute([$purchase['points_earned'], $user['id']]);
        
        // Enregistrer la transaction de points
        $stmt = $pdo->prepare('
            INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $user['id'],
            $purchase['points_earned'],
            "Achat: {$purchase['game_name']} - {$purchase['package_name']}",
            'game',
            $ts
        ]);
        
        // Marquer les points comme crédités
        $stmt = $pdo->prepare('
            UPDATE purchases SET points_credited = 1 WHERE id = ?
        ');
        $stmt->execute([$purchaseId]);
    }
    
    $pdo->commit();
    
    // Récupérer l'achat mis à jour avec la facture
    $stmt = $pdo->prepare('
        SELECT p.*, 
               i.id as invoice_id,
               i.invoice_number,
               i.validation_code,
               i.status as invoice_status
        FROM purchases p
        LEFT JOIN invoices i ON p.id = i.purchase_id
        WHERE p.id = ?
    ');
    $stmt->execute([$purchaseId]);
    $updatedPurchase = $stmt->fetch();
    
    json_response([
        'success' => true,
        'message' => 'Session activée ! Votre facture a été générée.',
        'purchase' => $updatedPurchase,
        'has_invoice' => !empty($updatedPurchase['invoice_id']),
        'next_step' => 'view_qr_code'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    json_response([
        'error' => 'Erreur lors de la confirmation',
        'details' => $e->getMessage()
    ], 500);
}
