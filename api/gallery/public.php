<?php
// api/gallery/public.php
// Public API to fetch gallery images for display

require_once __DIR__ . '/../config.php';

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Get single gallery item
        $stmt = $db->prepare("
            SELECT g.*,
                   e.title as event_title,
                   e.type as event_type,
                   e.date as event_date
            FROM gallery g
            LEFT JOIN events e ON g.event_id = e.id
            WHERE g.id = ? AND g.status = 'active'
        ");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            json_response(['error' => 'Image non trouvée'], 404);
        }
        
        // Increment view count
        $updateStmt = $db->prepare("UPDATE gallery SET views = views + 1 WHERE id = ?");
        $updateStmt->execute([$id]);
        
        json_response($item);
    } else {
        // List gallery items with filters
        $category = $_GET['category'] ?? null;
        $eventId = $_GET['event_id'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 50), 200);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = "
            SELECT g.*,
                   e.title as event_title,
                   e.type as event_type
            FROM gallery g
            LEFT JOIN events e ON g.event_id = e.id
            WHERE g.status = 'active'
        ";
        $params = [];
        
        if ($category) {
            $sql .= " AND g.category = ?";
            $params[] = $category;
        }
        
        if ($eventId) {
            $sql .= " AND g.event_id = ?";
            $params[] = $eventId;
        }
        
        $sql .= " ORDER BY g.display_order ASC, g.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM gallery g WHERE g.status = 'active'";
        $countParams = [];
        if ($category) {
            $countSql .= " AND g.category = ?";
            $countParams[] = $category;
        }
        if ($eventId) {
            $countSql .= " AND g.event_id = ?";
            $countParams[] = $eventId;
        }
        
        $stmt = $db->prepare($countSql);
        $stmt->execute($countParams);
        $total = $stmt->fetch()['total'];
        
        json_response([
            'items' => $items,
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
}

// POST - Like a gallery item
if ($method === 'POST') {
    $input = get_json_input();
    $id = $input['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis'], 400);
    }
    
    try {
        $stmt = $db->prepare("UPDATE gallery SET likes = likes + 1 WHERE id = ? AND status = 'active'");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            json_response(['error' => 'Image non trouvée'], 404);
        }
        
        // Get updated likes count
        $stmt = $db->prepare("SELECT likes FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        json_response([
            'success' => true,
            'likes' => (int)$result['likes']
        ]);
        
    } catch (Exception $e) {
        json_response(['error' => 'Erreur lors du like', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
