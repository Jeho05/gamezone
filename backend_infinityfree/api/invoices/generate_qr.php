<?php
// api/invoices/generate_qr.php
// Génère le QR code pour une facture

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

require_method(['GET']);

$invoiceId = $_GET['invoice_id'] ?? null;

if (!$invoiceId) {
    json_response(['error' => 'invoice_id requis'], 400);
}

// Récupérer la facture
$stmt = $pdo->prepare('
    SELECT * FROM invoices 
    WHERE id = ? AND user_id = ?
');
$stmt->execute([$invoiceId, $user['id']]);
$invoice = $stmt->fetch();

if (!$invoice) {
    json_response(['error' => 'Facture non trouvée'], 404);
}

// Vérifier que la facture est valide
if (!in_array($invoice['status'], ['pending', 'active'])) {
    json_response(['error' => 'Cette facture ne peut plus être utilisée'], 400);
}

// Données pour le QR code
$qrData = [
    'type' => 'gamezone_invoice',
    'version' => '1.0',
    'invoice_id' => $invoice['id'],
    'invoice_number' => $invoice['invoice_number'],
    'validation_code' => $invoice['validation_code'],
    'user_id' => $invoice['user_id'],
    'amount' => (float)$invoice['amount'],
    'currency' => $invoice['currency'],
    'duration_minutes' => $invoice['duration_minutes'],
    'game_name' => $invoice['game_name'],
    'issued_at' => $invoice['issued_at'],
    'expires_at' => $invoice['expires_at'],
    'hash' => $invoice['qr_code_hash']
];

// URL pour scan rapide (peut être utilisée par admin ou système)
$scanUrl = 'https://gamezone.local/admin/scan-invoice?code=' . urlencode($invoice['validation_code']);

json_response([
    'success' => true,
    'invoice' => [
        'id' => $invoice['id'],
        'invoice_number' => $invoice['invoice_number'],
        'validation_code' => $invoice['validation_code'],
        'status' => $invoice['status'],
        'amount' => (float)$invoice['amount'],
        'duration_minutes' => $invoice['duration_minutes'],
        'expires_at' => $invoice['expires_at']
    ],
    'qr_data' => json_encode($qrData),
    'qr_text' => $scanUrl,
    'qr_size' => 300,
    // Note: Pour générer l'image QR, utiliser une bibliothèque côté client ou
    // une API comme: https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=...
    'qr_image_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($scanUrl)
]);
