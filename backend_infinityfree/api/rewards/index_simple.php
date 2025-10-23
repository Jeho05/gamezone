<?php
// api/rewards/index_simple.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

try {
    $user = require_auth();
    $pdo = get_db();
    
    // Récupérer les récompenses disponibles
    $sql = 'SELECT r.*,';
    $sql .= ' (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.id) as total_redemptions';
    $sql .= ' FROM rewards r WHERE r.available = 1';
    $sql .= ' AND (r.stock_quantity IS NULL OR r.stock_quantity > (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.id AND status != "cancelled"))';
    $sql .= ' ORDER BY r.is_featured DESC, r.display_order ASC, r.cost ASC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enrichir avec des informations sur la disponibilité pour l'utilisateur
    foreach ($items as &$item) {
        if ($item['max_per_user']) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = ? AND user_id = ?');
            $stmt->execute([$item['id'], $user['id']]);
            $userRedemptions = (int)$stmt->fetchColumn();
            $item['user_redemptions'] = $userRedemptions;
            $item['can_redeem'] = ($userRedemptions < $item['max_per_user']);
        } else {
            $item['user_redemptions'] = 0;
            $item['can_redeem'] = true;
        }
        
        // Calculer le stock restant
        if ($item['stock_quantity']) {
            $item['stock_remaining'] = $item['stock_quantity'] - $item['total_redemptions'];
        } else {
            $item['stock_remaining'] = null; // illimité
        }
    }
    
    json_response([
        'success' => true,
        'rewards' => $items,
        'count' => count($items),
        'user_points' => (int)$user['points']
    ]);
    
} catch (Exception $e) {
    json_response([
        'success' => false,
        'error' => $e->getMessage(),
        'rewards' => [],
        'count' => 0
    ], 500);
}
