<?php
// api/content/news.php
// API pour gérer les actualités

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer les actualités
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    $category = $_GET['category'] ?? '';
    
    if ($id) {
        // Une news spécifique
        $stmt = $pdo->prepare('
            SELECT n.*, u.username as author_name
            FROM news n
            LEFT JOIN users u ON n.author_id = u.id
            WHERE n.id = ? AND n.is_published = 1
        ');
        $stmt->execute([$id]);
        $news = $stmt->fetch();
        
        if (!$news) {
            json_response(['error' => 'Actualité non trouvée'], 404);
        }
        
        json_response(['news' => $news]);
    } else {
        // Liste des news
        $sql = '
            SELECT n.*, u.username as author_name
            FROM news n
            LEFT JOIN users u ON n.author_id = u.id
            WHERE n.is_published = 1
        ';
        
        $params = [];
        
        if ($category) {
            $sql .= ' AND n.category = ?';
            $params[] = $category;
        }
        
        $sql .= ' ORDER BY n.published_at DESC, n.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $news = $stmt->fetchAll();
        
        json_response(['news' => $news, 'count' => count($news)]);
    }
}

// POST - Créer une actualité (ADMIN ONLY)
if ($method === 'POST') {
    $user = require_auth('admin');
    $data = get_json_input();
    
    $required = ['title', 'content'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    try {
        $ts = now();
        $stmt = $pdo->prepare('
            INSERT INTO news (
                title, content, excerpt, category, image_url,
                author_id, is_published, published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $isPublished = $data['is_published'] ?? true;
        $publishedAt = $isPublished ? $ts : null;
        
        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['excerpt'] ?? null,
            $data['category'] ?? 'general',
            $data['image_url'] ?? null,
            $user['id'],
            $isPublished ? 1 : 0,
            $publishedAt,
            $ts,
            $ts
        ]);
        
        $newsId = $pdo->lastInsertId();
        
        json_response([
            'success' => true,
            'message' => 'Actualité créée avec succès',
            'id' => $newsId
        ], 201);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la création', 'details' => $e->getMessage()], 500);
    }
}

// PUT - Mettre à jour (ADMIN ONLY)
if ($method === 'PUT') {
    $user = require_auth('admin');
    $data = get_json_input();
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis'], 400);
    }
    
    try {
        $updates = [];
        $params = [];
        
        $allowedFields = ['title', 'content', 'excerpt', 'category', 'image_url', 'is_published'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            json_response(['error' => 'Aucune donnée à mettre à jour'], 400);
        }
        
        // Si publication, mettre à jour published_at
        if (isset($data['is_published']) && $data['is_published']) {
            $updates[] = 'published_at = ?';
            $params[] = now();
        }
        
        $updates[] = 'updated_at = ?';
        $params[] = now();
        $params[] = $id;
        
        $sql = 'UPDATE news SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json_response(['success' => true, 'message' => 'Actualité mise à jour']);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// DELETE (ADMIN ONLY)
if ($method === 'DELETE') {
    $user = require_auth('admin');
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis'], 400);
    }
    
    try {
        $stmt = $pdo->prepare('DELETE FROM news WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response(['success' => true, 'message' => 'Actualité supprimée']);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405);
