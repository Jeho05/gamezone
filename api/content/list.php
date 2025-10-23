<?php
/**
 * API Content - Liste du contenu
 * Retourne la liste des articles/contenu public
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

require_method(['GET']);

$pdo = get_db();

try {
    $type = $_GET['type'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $where = "c.status = 'published'";
    $params = [];
    
    if ($type) {
        $where .= " AND c.content_type = ?";
        $params[] = $type;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u.username as author_name,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id = c.id) as total_reactions,
            (SELECT COUNT(*) FROM content_shares WHERE content_id = c.id) as total_shares
        FROM content_items c
        LEFT JOIN users u ON c.author_id = u.id
        WHERE $where
        ORDER BY c.published_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    
    $content = $stmt->fetchAll();
    
    // Compter le total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM content_items c WHERE $where");
    $stmt->execute(array_slice($params, 0, -2));
    $total = $stmt->fetchColumn();
    
    json_response([
        'success' => true,
        'content' => $content,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset
        ],
        'timestamp' => now()
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors du chargement du contenu',
        'details' => $e->getMessage()
    ], 500);
}
