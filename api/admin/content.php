<?php
// api/admin/content.php
// Gestion complète du contenu: News, Events, Streams, Gallery
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer le contenu
// ============================================================================
if ($method === 'GET') {
    $type = $_GET['type'] ?? 'all'; // news, events, streams, gallery, all
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer un élément spécifique
        $stmt = $pdo->prepare('
            SELECT c.*, u.username as author_name
            FROM content c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            json_response(['error' => 'Contenu non trouvé'], 404);
        }
        
        json_response(['content' => $item]);
    } else {
        // Récupérer tous les contenus par type
        $sql = '
            SELECT c.*, u.username as author_name,
                   (SELECT COUNT(*) FROM content_likes WHERE content_id = c.id) as likes_count,
                   (SELECT COUNT(*) FROM content_comments WHERE content_id = c.id) as comments_count
            FROM content c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE 1=1
        ';
        $params = [];
        
        if ($type !== 'all') {
            $sql .= ' AND c.type = ?';
            $params[] = $type;
        }
        
        $sql .= ' ORDER BY c.is_pinned DESC, c.published_at DESC, c.created_at DESC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        
        json_response(['content' => $items, 'type' => $type, 'count' => count($items)]);
    }
}

// ============================================================================
// POST: Créer un nouveau contenu
// ============================================================================
if ($method === 'POST') {
    $data = get_json_input();
    
    // Validation
    $required = ['type', 'title'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    $type = $data['type']; // news, event, stream, gallery
    if (!in_array($type, ['news', 'event', 'stream', 'gallery'])) {
        json_response(['error' => 'Type invalide'], 400);
    }
    
    try {
        $ts = now();
        $publishedAt = $data['published_at'] ?? $ts;
        
        // Convert empty strings to null for datetime fields
        $eventDate = (!empty($data['event_date'])) ? $data['event_date'] : null;
        
        $stmt = $pdo->prepare('
            INSERT INTO content (
                type, title, description, content, image_url, video_url,
                external_link, event_date, event_location, stream_url,
                is_published, is_pinned, published_at,
                created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $type,
            $data['title'],
            $data['description'] ?? null,
            $data['content'] ?? null,
            $data['image_url'] ?? null,
            $data['video_url'] ?? null,
            $data['external_link'] ?? null,
            $eventDate,
            $data['event_location'] ?? null,
            $data['stream_url'] ?? null,
            $data['is_published'] ?? 1,
            $data['is_pinned'] ?? 0,
            $publishedAt,
            $user['id'],
            $ts,
            $ts
        ]);
        
        $contentId = $pdo->lastInsertId();
        
        json_response([
            'success' => true,
            'message' => 'Contenu créé avec succès',
            'content_id' => $contentId
        ], 201);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la création', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// PUT/PATCH: Mettre à jour un contenu
// ============================================================================
if ($method === 'PUT' || $method === 'PATCH') {
    $data = get_json_input();
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID du contenu requis'], 400);
    }
    
    // Vérifier que le contenu existe
    $stmt = $pdo->prepare('SELECT id FROM content WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Contenu non trouvé'], 404);
    }
    
    // Construire la requête de mise à jour
    $updateFields = [];
    $params = [];
    
    $allowedFields = [
        'type', 'title', 'description', 'content', 'image_url', 'video_url',
        'external_link', 'event_date', 'event_location', 'stream_url',
        'is_published', 'is_pinned', 'published_at'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            // Convert empty strings to null for datetime fields
            if ($field === 'event_date' && $data[$field] === '') {
                $params[] = null;
            } else {
                $params[] = $data[$field];
            }
        }
    }
    
    if (empty($updateFields)) {
        json_response(['error' => 'Aucune donnée à mettre à jour'], 400);
    }
    
    $updateFields[] = 'updated_at = ?';
    $params[] = now();
    $params[] = $id;
    
    try {
        $sql = 'UPDATE content SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json_response([
            'success' => true,
            'message' => 'Contenu mis à jour avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// DELETE: Supprimer un contenu
// ============================================================================
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID du contenu requis'], 400);
    }
    
    try {
        // Supprimer d'abord les likes et commentaires
        $pdo->prepare('DELETE FROM content_likes WHERE content_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM content_comments WHERE content_id = ?')->execute([$id]);
        
        // Supprimer le contenu
        $stmt = $pdo->prepare('DELETE FROM content WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response([
            'success' => true,
            'message' => 'Contenu supprimé avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
