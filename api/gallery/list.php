<?php
/**
 * API Gallery - Liste des items
 * Retourne la liste des images/vidéos de la galerie
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

require_method(['GET']);

$pdo = get_db();

try {
    $category = $_GET['category'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $where = "g.status = 'active'";
    $params = [];
    
    if ($category) {
        $where .= " AND g.category = ?";
        $params[] = $category;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            g.*,
            e.title as event_title,
            u.username as created_by_username
        FROM gallery g
        LEFT JOIN events e ON g.event_id = e.id
        LEFT JOIN users u ON g.created_by = u.id
        WHERE $where
        ORDER BY g.display_order ASC, g.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    
    $items = $stmt->fetchAll();
    
    // Compter le total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM gallery g WHERE $where");
    $stmt->execute(array_slice($params, 0, -2));
    $total = $stmt->fetchColumn();
    
    json_response([
        'success' => true,
        'items' => $items,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset
        ],
        'timestamp' => now()
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors du chargement de la galerie',
        'details' => $e->getMessage()
    ], 500);
}
