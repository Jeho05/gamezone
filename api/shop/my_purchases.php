<?php
// api/shop/my_purchases.php
// API pour que l'utilisateur consulte ses achats et sessions

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// L'utilisateur doit être connecté
$user = require_auth();
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer les achats de l'utilisateur
// ============================================================================
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer un achat spécifique (uniquement si appartient à l'utilisateur)
        $stmt = $pdo->prepare('
            SELECT p.*,
                   g.name as game_name, g.slug as game_slug, g.image_url,
                   pkg.name as package_name,
                   pm.name as payment_method_name, pm.requires_online_payment
            FROM purchases p
            INNER JOIN games g ON p.game_id = g.id
            LEFT JOIN game_packages pkg ON p.package_id = pkg.id
            LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
            WHERE p.id = ? AND p.user_id = ?
        ');
        $stmt->execute([$id, $user['id']]);
        $purchase = $stmt->fetch();
        
        if (!$purchase) {
            json_response(['error' => 'Achat non trouvé'], 404);
        }
        
        // Récupérer la session associée depuis la nouvelle table
        $stmt = $pdo->prepare('
            SELECT * FROM active_game_sessions_v2 WHERE purchase_id = ?
        ');
        $stmt->execute([$id]);
        $purchase['session'] = $stmt->fetch();
        
        json_response(['purchase' => $purchase]);
    } else {
        // Récupérer tous les achats de l'utilisateur
        $status = $_GET['status'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 20), 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = '
            SELECT p.*,
                   g.name as game_name, g.slug as game_slug, g.image_url, g.thumbnail_url,
                   pkg.name as package_name,
                   pm.name as payment_method_name,
                   s.status as game_session_status,
                   s.id as game_session_id,
                   s.total_minutes,
                   s.used_minutes,
                   s.remaining_minutes,
                   s.started_at as session_started_at,
                   s.completed_at as session_completed_at
            FROM purchases p
            INNER JOIN games g ON p.game_id = g.id
            LEFT JOIN game_packages pkg ON p.package_id = pkg.id
            LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
            LEFT JOIN active_game_sessions_v2 s ON p.id = s.purchase_id
            WHERE p.user_id = ?
        ';
        $params = [$user['id']];
        
        if ($status) {
            $sql .= ' AND p.payment_status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $purchases = $stmt->fetchAll();
        
        json_response(['purchases' => $purchases]);
    }
}

json_response(['error' => 'Method not allowed'], 405);
