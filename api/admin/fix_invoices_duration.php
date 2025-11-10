<?php
/**
 * Script pour corriger les invoices qui n'ont pas de duration_minutes
 * À exécuter UNE FOIS pour corriger les données existantes
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

$pdo = get_db();

try {
    // Trouver toutes les invoices avec duration_minutes = 0 ou NULL
    $stmt = $pdo->query("
        SELECT i.id, i.purchase_id, i.duration_minutes, 
               p.duration_minutes as purchase_duration,
               pkg.duration_minutes as package_duration
        FROM invoices i
        LEFT JOIN purchases p ON i.purchase_id = p.id
        LEFT JOIN game_packages pkg ON p.package_id = pkg.id
        WHERE i.duration_minutes IS NULL OR i.duration_minutes = 0
    ");
    
    $invoices = $stmt->fetchAll();
    $fixed = 0;
    $errors = [];
    
    foreach ($invoices as $invoice) {
        $correctDuration = $invoice['purchase_duration'] ?? $invoice['package_duration'] ?? 0;
        
        if ($correctDuration > 0) {
            $update = $pdo->prepare("UPDATE invoices SET duration_minutes = ? WHERE id = ?");
            $update->execute([$correctDuration, $invoice['id']]);
            $fixed++;
        } else {
            $errors[] = "Invoice {$invoice['id']}: cannot determine duration";
        }
    }
    
    // Trouver les sessions actives avec total_minutes = 0
    $stmt = $pdo->query("
        SELECT s.id, s.invoice_id, s.total_minutes,
               i.duration_minutes as invoice_duration
        FROM active_game_sessions_v2 s
        LEFT JOIN invoices i ON s.invoice_id = i.id
        WHERE s.total_minutes = 0 AND s.status IN ('ready', 'active', 'paused')
    ");
    
    $sessions = $stmt->fetchAll();
    $sessionsFixed = 0;
    
    foreach ($sessions as $session) {
        if ($session['invoice_duration'] > 0) {
            $update = $pdo->prepare("
                UPDATE active_game_sessions_v2 
                SET total_minutes = ?,
                    used_minutes = 0,
                    started_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([$session['invoice_duration'], $session['id']]);
            $sessionsFixed++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'invoices_fixed' => $fixed,
        'sessions_fixed' => $sessionsFixed,
        'errors' => $errors,
        'message' => "Correction terminée: $fixed invoices et $sessionsFixed sessions corrigées"
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
