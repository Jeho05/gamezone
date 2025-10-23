<?php
// api/admin/gallery.php
// Admin CRUD for gallery management

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_check.php';

$admin = require_admin();
$db = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// GET - List all gallery items or get one
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Get single gallery item
        $stmt = $db->prepare("
            SELECT g.*, 
                   u.username as creator_username,
                   e.title as event_title,
                   e.type as event_type
            FROM gallery g
            LEFT JOIN users u ON g.created_by = u.id
            LEFT JOIN events e ON g.event_id = e.id
            WHERE g.id = ?
        ");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            json_response(['error' => 'Image non trouvée'], 404);
        }
        
        json_response($item);
    } else {
        // List all gallery items with filters
        $category = $_GET['category'] ?? null;
        $status = $_GET['status'] ?? null;
        $eventId = $_GET['event_id'] ?? null;
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = "
            SELECT g.*, 
                   u.username as creator_username,
                   e.title as event_title
            FROM gallery g
            LEFT JOIN users u ON g.created_by = u.id
            LEFT JOIN events e ON g.event_id = e.id
            WHERE 1=1
        ";
        $params = [];
        
        if ($category) {
            $sql .= " AND g.category = ?";
            $params[] = $category;
        }
        
        if ($status) {
            $sql .= " AND g.status = ?";
            $params[] = $status;
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
        $countSql = "SELECT COUNT(*) as total FROM gallery g WHERE 1=1";
        $countParams = [];
        if ($category) {
            $countSql .= " AND g.category = ?";
            $countParams[] = $category;
        }
        if ($status) {
            $countSql .= " AND g.status = ?";
            $countParams[] = $status;
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
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
}

// POST - Create new gallery item
if ($method === 'POST') {
    $input = get_json_input();
    
    $required = ['title', 'image_url', 'category'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO gallery (
                title, description, image_url, thumbnail_url, category, 
                event_id, status, display_order, created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $input['title'],
            $input['description'] ?? null,
            $input['image_url'],
            $input['thumbnail_url'] ?? $input['image_url'],
            $input['category'],
            $input['event_id'] ?? null,
            $input['status'] ?? 'active',
            $input['display_order'] ?? 0,
            $admin['id']
        ]);
        
        $itemId = $db->lastInsertId();
        
        json_response([
            'success' => true,
            'message' => 'Image ajoutée à la galerie',
            'id' => $itemId
        ], 201);
        
    } catch (Exception $e) {
        json_response(['error' => 'Erreur lors de l\'ajout', 'details' => $e->getMessage()], 500);
    }
}

// PUT/PATCH - Update gallery item
if ($method === 'PUT' || $method === 'PATCH') {
    $input = get_json_input();
    $id = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis pour la mise à jour'], 400);
    }
    
    try {
        $updates = [];
        $params = [];
        
        $allowed = ['title', 'description', 'image_url', 'thumbnail_url', 'category', 
                   'event_id', 'status', 'display_order'];
        
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updates)) {
            json_response(['error' => 'Aucun champ à mettre à jour'], 400);
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE gallery SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() === 0) {
            json_response(['error' => 'Image non trouvée'], 404);
        }
        
        json_response([
            'success' => true,
            'message' => 'Image mise à jour avec succès'
        ]);
        
    } catch (Exception $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// DELETE - Delete gallery item
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis pour la suppression'], 400);
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            json_response(['error' => 'Image non trouvée'], 404);
        }
        
        json_response([
            'success' => true,
            'message' => 'Image supprimée avec succès'
        ]);
        
    } catch (Exception $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}
