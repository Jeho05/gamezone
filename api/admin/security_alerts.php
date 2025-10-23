<?php
/**
 * API Admin: Alertes de Sécurité
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

$user = require_auth();
if (!is_admin($user)) {
    json_response(['error' => 'Accès refusé'], 403);
}

try {
    $pdo = get_db();
    
    // Vérifier si la table existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_transactions'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        json_response([
            'success' => true,
            'alerts' => [
                'stuck_transactions' => 0,
                'suspicious_activity' => 0,
                'failed_payments' => 0,
                'last_cleanup' => null
            ],
            'notice' => 'Système de transactions sécurisées non encore installé'
        ]);
    }
    
    // Transactions bloquées (en processing depuis plus de 5 minutes)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM purchase_transactions
        WHERE status = 'processing'
          AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stuckTransactions = $stmt->fetch();
    
    // Activité suspecte (factures marquées comme suspectes)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM invoices
        WHERE is_suspicious = 1
          AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $suspicious = $stmt->fetch();
    
    // Paiements échoués dans les dernières 24h
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM purchases
        WHERE payment_status = 'failed'
          AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $failedPayments = $stmt->fetch();
    
    // Dernier nettoyage (simulé pour l'instant)
    // En production, on pourrait logger les exécutions de l'event cleanup
    $lastCleanup = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    
    json_response([
        'success' => true,
        'alerts' => [
            'stuck_transactions' => (int)$stuckTransactions['count'],
            'suspicious_activity' => (int)$suspicious['count'],
            'failed_payments' => (int)$failedPayments['count'],
            'last_cleanup' => $lastCleanup
        ]
    ]);
    
} catch (Exception $e) {
    log_error('Erreur alertes sécurité', ['error' => $e->getMessage()]);
    json_response([
        'error' => 'Erreur lors du chargement des alertes',
        'message' => $e->getMessage()
    ], 500);
}
