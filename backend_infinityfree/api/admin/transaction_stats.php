<?php
/**
 * API Admin: Statistiques des Transactions
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
        // Table pas encore créée, retourner des valeurs par défaut
        json_response([
            'success' => true,
            'stats' => [
                'today' => ['count' => 0, 'points' => 0, 'success_rate' => 0],
                'week' => ['count' => 0, 'points' => 0, 'success_rate' => 0],
                'month' => ['count' => 0, 'points' => 0, 'success_rate' => 0],
                'failed' => ['count' => 0, 'last_failures' => []],
                'pending' => ['count' => 0],
                'refunded' => ['count' => 0, 'total_points' => 0]
            ],
            'notice' => 'Système de transactions sécurisées non encore installé'
        ]);
    }
    
    // Stats Aujourd'hui
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'completed' THEN points_amount ELSE 0 END) as points
        FROM purchase_transactions
        WHERE DATE(created_at) = CURDATE()
    ");
    $today = $stmt->fetch();
    $todaySuccessRate = $today['total'] > 0 ? round(($today['completed'] / $today['total']) * 100) : 0;
    
    // Stats Cette Semaine
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'completed' THEN points_amount ELSE 0 END) as points
        FROM purchase_transactions
        WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
    ");
    $week = $stmt->fetch();
    $weekSuccessRate = $week['total'] > 0 ? round(($week['completed'] / $week['total']) * 100) : 0;
    
    // Stats Ce Mois
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'completed' THEN points_amount ELSE 0 END) as points
        FROM purchase_transactions
        WHERE YEAR(created_at) = YEAR(CURDATE())
          AND MONTH(created_at) = MONTH(CURDATE())
    ");
    $month = $stmt->fetch();
    $monthSuccessRate = $month['total'] > 0 ? round(($month['completed'] / $month['total']) * 100) : 0;
    
    // Transactions échouées récentes
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM purchase_transactions
        WHERE status = 'failed'
          AND failed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $failed = $stmt->fetch();
    
    // Dernières erreurs
    $stmt = $pdo->query("
        SELECT 
            failure_reason as reason,
            failed_at,
            CASE
                WHEN failed_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN CONCAT(TIMESTAMPDIFF(MINUTE, failed_at, NOW()), ' min')
                WHEN failed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, failed_at, NOW()), 'h')
                ELSE CONCAT(TIMESTAMPDIFF(DAY, failed_at, NOW()), 'j')
            END as `when`
        FROM purchase_transactions
        WHERE status = 'failed'
        ORDER BY failed_at DESC
        LIMIT 5
    ");
    $lastFailures = $stmt->fetchAll();
    
    // Transactions en attente
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM purchase_transactions
        WHERE status = 'pending'
    ");
    $pending = $stmt->fetch();
    
    // Remboursements
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as count,
            SUM(points_amount) as total_points
        FROM purchase_transactions
        WHERE status = 'refunded'
    ");
    $refunded = $stmt->fetch();
    
    json_response([
        'success' => true,
        'stats' => [
            'today' => [
                'count' => (int)$today['total'],
                'points' => (int)$today['points'],
                'success_rate' => $todaySuccessRate
            ],
            'week' => [
                'count' => (int)$week['total'],
                'points' => (int)$week['points'],
                'success_rate' => $weekSuccessRate
            ],
            'month' => [
                'count' => (int)$month['total'],
                'points' => (int)$month['points'],
                'success_rate' => $monthSuccessRate
            ],
            'failed' => [
                'count' => (int)$failed['count'],
                'last_failures' => $lastFailures
            ],
            'pending' => [
                'count' => (int)$pending['count']
            ],
            'refunded' => [
                'count' => (int)$refunded['count'],
                'total_points' => (int)($refunded['total_points'] ?? 0)
            ]
        ]
    ]);
    
} catch (Exception $e) {
    log_error('Erreur stats transactions', ['error' => $e->getMessage()]);
    json_response([
        'error' => 'Erreur lors du chargement des statistiques',
        'message' => $e->getMessage()
    ], 500);
}
