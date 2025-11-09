<?php
/**
 * VERSION SIMPLIFIÉE - Scan facture SANS procédure stockée
 * Alternative robuste si scan_invoice.php échoue
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Admin uniquement
$user = require_auth();
if (!is_admin($user)) {
    json_response(['error' => 'Accès refusé'], 403);
}

$pdo = get_db();

// GET: Vérifier un code
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $code = trim($_GET['code'] ?? '');
    if (!$code) {
        json_response(['error' => 'Code requis'], 400);
    }
    
    // Nettoyer le code
    $code = strtoupper(preg_replace('/[-\s]/', '', $code));
    
    // Reformater si 16 caractères
    $codeFormatted = $code;
    if (strlen($code) === 16) {
        $codeFormatted = substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . 
                         substr($code, 8, 4) . '-' . substr($code, 12, 4);
    }
    
    $stmt = $pdo->prepare('
        SELECT i.*, u.username, u.email,
               TIMESTAMPDIFF(MINUTE, NOW(), i.expires_at) as minutes_until_expiry
        FROM invoices i
        INNER JOIN users u ON i.user_id = u.id
        WHERE i.validation_code = ? OR i.validation_code = ?
    ');
    $stmt->execute([$code, $codeFormatted]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        json_response(['valid' => false, 'error' => 'Code invalide'], 404);
    }
    
    $canActivate = $invoice['status'] === 'pending' && 
                   strtotime($invoice['expires_at']) > time();
    
    json_response([
        'valid' => true,
        'can_activate' => $canActivate,
        'invoice' => [
            'invoice_number' => $invoice['invoice_number'],
            'status' => $invoice['status'],
            'user' => ['username' => $invoice['username'], 'email' => $invoice['email']],
            'game_name' => $invoice['game_name'],
            'package_name' => $invoice['package_name'],
            'duration_minutes' => $invoice['duration_minutes'],
            'amount' => (float)$invoice['amount'],
            'expires_at' => $invoice['expires_at'],
            'minutes_until_expiry' => $invoice['minutes_until_expiry']
        ]
    ]);
}

// POST: Activer une facture (VERSION SIMPLIFIÉE)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Méthode non autorisée'], 405);
}

$data = get_json_input();
$codeRaw = trim($data['validation_code'] ?? '');

if (!$codeRaw) {
    json_response(['error' => 'Code de validation requis'], 400);
}

// Nettoyer
$code = strtoupper(preg_replace('/[-\s]/', '', $codeRaw));

// Valider format
if (!preg_match('/^[A-Z0-9]{8,16}$/', $code)) {
    json_response(['error' => 'Format de code invalide'], 400);
}

// Reformater
$codeFormatted = $code;
if (strlen($code) === 16) {
    $codeFormatted = substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . 
                     substr($code, 8, 4) . '-' . substr($code, 12, 4);
}

try {
    $pdo->beginTransaction();
    
    // Récupérer la facture
    $stmt = $pdo->prepare('
        SELECT i.*, p.id as purchase_id
        FROM invoices i
        INNER JOIN purchases p ON i.purchase_id = p.id
        WHERE (i.validation_code = ? OR i.validation_code = ?)
    ');
    $stmt->execute([$code, $codeFormatted]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'invalid_code', 'message' => 'Code invalide'], 400);
    }
    
    // Vérifier le statut
    if ($invoice['status'] !== 'pending') {
        $pdo->rollBack();
        $message = $invoice['status'] === 'active' ? 'Facture déjà activée' : 'Facture ' . $invoice['status'];
        json_response(['success' => false, 'error' => 'already_active', 'message' => $message], 400);
    }
    
    // Vérifier expiration
    if (strtotime($invoice['expires_at']) < time()) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'expired', 'message' => 'Facture expirée'], 400);
    }
    
    // Logger le scan
    $stmt = $pdo->prepare('
        INSERT INTO invoice_scans (invoice_id, scanned_by_user_id, ip_address, user_agent)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([
        $invoice['id'],
        $user['id'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    // Mettre à jour la facture
    $stmt = $pdo->prepare('UPDATE invoices SET status = "active", activated_at = NOW() WHERE id = ?');
    $stmt->execute([$invoice['id']]);
    
    // Créer une session active
    $stmt = $pdo->prepare('
        INSERT INTO active_game_sessions_v2 
        (invoice_id, user_id, game_name, total_minutes, status, started_at, created_at)
        VALUES (?, ?, ?, ?, "active", NOW(), NOW())
    ');
    $stmt->execute([
        $invoice['id'],
        $invoice['user_id'],
        $invoice['game_name'],
        $invoice['duration_minutes']
    ]);
    $sessionId = $pdo->lastInsertId();
    
    // Mettre à jour purchases
    $stmt = $pdo->prepare('
        UPDATE purchases 
        SET session_status = "active", session_activated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$invoice['purchase_id']]);
    
    $pdo->commit();
    
    // Récupérer les détails complets
    $stmt = $pdo->prepare('
        SELECT i.*, u.username, u.email,
               s.id as session_id, s.total_minutes, s.status as session_status
        FROM invoices i
        INNER JOIN users u ON i.user_id = u.id
        LEFT JOIN active_game_sessions_v2 s ON i.id = s.invoice_id
        WHERE i.id = ?
    ');
    $stmt->execute([$invoice['id']]);
    $invoiceDetails = $stmt->fetch();
    
    json_response([
        'success' => true,
        'message' => 'Facture activée avec succès',
        'invoice' => $invoiceDetails,
        'next_action' => 'session_started'
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    log_error('Scan invoice error', [
        'code' => $codeRaw,
        'admin_id' => $user['id'],
        'error' => $e->getMessage()
    ]);
    
    json_response([
        'error' => 'Erreur lors de l\'activation',
        'details' => $e->getMessage()
    ], 500);
}
