<?php
// api/gamification/points_transactions.php
// API pour récupérer l'historique des transactions de points

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

require_method(['GET']);

$limit = min((int)($_GET['limit'] ?? 20), 100);
$offset = (int)($_GET['offset'] ?? 0);
$type = $_GET['type'] ?? ''; // filter by type

try {
    $sql = '
        SELECT 
            id,
            change_amount,
            reason,
            type,
            reference_type,
            reference_id,
            created_at
        FROM points_transactions
        WHERE user_id = ?
    ';
    
    $params = [$user['id']];
    
    if ($type) {
        $sql .= ' AND type = ?';
        $params[] = $type;
    }
    
    $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Get totals
    $stmt = $pdo->prepare('
        SELECT 
            COUNT(*) as total_transactions,
            COALESCE(SUM(CASE WHEN change_amount > 0 THEN change_amount ELSE 0 END), 0) as total_earned,
            COALESCE(SUM(CASE WHEN change_amount < 0 THEN ABS(change_amount) ELSE 0 END), 0) as total_spent
        FROM points_transactions
        WHERE user_id = ?
    ');
    $stmt->execute([$user['id']]);
    $totals = $stmt->fetch();
    
    json_response([
        'transactions' => $transactions,
        'totals' => $totals,
        'count' => count($transactions),
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    json_response(['error' => 'Erreur lors du chargement', 'details' => $e->getMessage()], 500);
}
