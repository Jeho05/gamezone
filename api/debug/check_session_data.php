<?php
/**
 * Script de debug pour vérifier les données de session
 * Usage: GET /api/debug/check_session_data.php?session_id=123
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    echo json_encode(['error' => 'session_id required']);
    exit;
}

$pdo = get_db();

try {
    // Récupérer la session brute
    $stmt = $pdo->prepare('SELECT * FROM active_game_sessions_v2 WHERE id = ?');
    $stmt->execute([$sessionId]);
    $sessionRaw = $stmt->fetch();
    
    if (!$sessionRaw) {
        echo json_encode(['error' => 'Session not found']);
        exit;
    }
    
    // Récupérer depuis la vue
    $stmt = $pdo->prepare('SELECT * FROM session_summary WHERE id = ?');
    $stmt->execute([$sessionId]);
    $sessionView = $stmt->fetch();
    
    // Récupérer l'invoice associée
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([$sessionRaw['invoice_id']]);
    $invoice = $stmt->fetch();
    
    // Récupérer le purchase associé
    $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ?');
    $stmt->execute([$sessionRaw['purchase_id']]);
    $purchase = $stmt->fetch();
    
    // Calculer remaining_minutes manuellement
    $calculatedRemaining = (int)$sessionRaw['total_minutes'] - (int)$sessionRaw['used_minutes'];
    
    echo json_encode([
        'session_id' => $sessionId,
        'session_raw' => [
            'total_minutes' => (int)$sessionRaw['total_minutes'],
            'used_minutes' => (int)$sessionRaw['used_minutes'],
            'remaining_minutes' => (int)$sessionRaw['remaining_minutes'],
            'status' => $sessionRaw['status'],
            'started_at' => $sessionRaw['started_at'],
            'created_at' => $sessionRaw['created_at']
        ],
        'session_from_view' => $sessionView ? [
            'total_minutes' => (int)$sessionView['total_minutes'],
            'used_minutes' => (int)$sessionView['used_minutes'],
            'remaining_minutes' => (int)$sessionView['remaining_minutes'],
            'status' => $sessionView['status']
        ] : null,
        'invoice' => $invoice ? [
            'duration_minutes' => (int)$invoice['duration_minutes'],
            'status' => $invoice['status'],
            'game_name' => $invoice['game_name']
        ] : null,
        'purchase' => $purchase ? [
            'game_id' => (int)$purchase['game_id'],
            'duration_minutes' => (int)($purchase['duration_minutes'] ?? 0),
            'payment_status' => $purchase['payment_status'],
            'session_status' => $purchase['session_status']
        ] : null,
        'calculated_remaining' => $calculatedRemaining,
        'diagnosis' => [
            'total_is_zero' => (int)$sessionRaw['total_minutes'] === 0,
            'used_equals_total' => (int)$sessionRaw['used_minutes'] === (int)$sessionRaw['total_minutes'],
            'remaining_is_zero' => (int)$sessionRaw['remaining_minutes'] === 0,
            'status_is_completed' => $sessionRaw['status'] === 'completed'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
