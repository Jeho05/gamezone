<?php
// api/admin/games.php
// API Admin pour gérer les jeux disponibles à l'achat

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est admin
$user = require_auth('admin');
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer tous les jeux ou un jeu spécifique
// ============================================================================
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer un jeu spécifique avec ses packages
        $stmt = $pdo->prepare('
            SELECT g.*, 
                   u.username as created_by_username,
                   (SELECT COUNT(*) FROM game_packages WHERE game_id = g.id AND is_active = 1) as active_packages_count,
                   (SELECT COUNT(*) FROM purchases WHERE game_id = g.id) as total_purchases,
                   (SELECT SUM(price) FROM purchases WHERE game_id = g.id AND payment_status = "completed") as total_revenue
            FROM games g
            LEFT JOIN users u ON g.created_by = u.id
            WHERE g.id = ?
        ');
        $stmt->execute([$id]);
        $game = $stmt->fetch();
        
        if (!$game) {
            json_response(['error' => 'Jeu non trouvé'], 404);
        }
        
        // Récupérer les packages du jeu
        $stmt = $pdo->prepare('
            SELECT pkg.*,
                   u.username as created_by_username,
                   (SELECT COUNT(*) FROM purchases WHERE package_id = pkg.id) as purchases_count,
                   (SELECT SUM(price) FROM purchases WHERE package_id = pkg.id AND payment_status = "completed") as revenue
            FROM game_packages pkg
            LEFT JOIN users u ON pkg.created_by = u.id
            WHERE pkg.game_id = ?
            ORDER BY pkg.display_order ASC, pkg.duration_minutes ASC
        ');
        $stmt->execute([$id]);
        $game['packages'] = $stmt->fetchAll();
        
        json_response(['game' => $game]);
    } else {
        // Récupérer tous les jeux
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $is_active = $_GET['is_active'] ?? '';
        
        $sql = '
            SELECT g.*, 
                   u.username as created_by_username,
                   (SELECT COUNT(*) FROM game_packages WHERE game_id = g.id AND is_active = 1) as active_packages_count,
                   (SELECT COUNT(*) FROM purchases WHERE game_id = g.id) as total_purchases,
                   (SELECT SUM(price) FROM purchases WHERE game_id = g.id AND payment_status = "completed") as total_revenue
            FROM games g
            LEFT JOIN users u ON g.created_by = u.id
            WHERE 1=1
        ';
        $params = [];
        
        if ($search) {
            $sql .= ' AND (g.name LIKE ? OR g.description LIKE ?)';
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($category) {
            $sql .= ' AND g.category = ?';
            $params[] = $category;
        }
        
        if ($is_active !== '') {
            $sql .= ' AND g.is_active = ?';
            $params[] = (int)$is_active;
        }
        
        $sql .= ' ORDER BY g.is_featured DESC, g.display_order ASC, g.name ASC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $games = $stmt->fetchAll();
        
        json_response(['games' => $games]);
    }
}

// ============================================================================
// POST: Créer un nouveau jeu
// ============================================================================
if ($method === 'POST') {
    $data = get_json_input();
    
    // Validation
    $required = ['name', 'category', 'points_per_hour', 'base_price'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    // Générer ou utiliser le slug fourni
    if (isset($data['slug']) && trim($data['slug']) !== '') {
        $slug = strtolower(trim($data['slug']));
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));
    }
    
    // Nettoyer le slug
    $slug = preg_replace('/^-+|-+$/', '', $slug);
    
    // Vérifier que le slug est unique
    $stmt = $pdo->prepare('SELECT id FROM games WHERE slug = ?');
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        $slug .= '-' . time();
    }
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO games (
                name, slug, description, short_description, image_url, thumbnail_url,
                category, platform, min_players, max_players, age_rating,
                points_per_hour, base_price, is_reservable, reservation_fee, is_active, is_featured, display_order,
                created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $ts = now();
        $stmt->execute([
            $data['name'],
            $slug,
            $data['description'] ?? null,
            $data['short_description'] ?? null,
            $data['image_url'] ?? null,
            $data['thumbnail_url'] ?? null,
            $data['category'],
            $data['platform'] ?? null,
            $data['min_players'] ?? 1,
            $data['max_players'] ?? 1,
            $data['age_rating'] ?? null,
            $data['points_per_hour'],
            $data['base_price'],
            $data['is_reservable'] ?? 0,
            $data['reservation_fee'] ?? 0.00,
            $data['is_active'] ?? 1,
            $data['is_featured'] ?? 0,
            $data['display_order'] ?? 0,
            $user['id'],
            $ts,
            $ts
        ]);
        
        $gameId = $pdo->lastInsertId();
        
        json_response([
            'success' => true,
            'message' => 'Jeu créé avec succès',
            'game_id' => $gameId
        ], 201);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la création du jeu', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// PUT/PATCH: Mettre à jour un jeu
// ============================================================================
if ($method === 'PUT' || $method === 'PATCH') {
    $data = get_json_input();
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID du jeu requis'], 400);
    }
    
    // Vérifier que le jeu existe
    $stmt = $pdo->prepare('SELECT id FROM games WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Jeu non trouvé'], 404);
    }
    
    // Construire la requête de mise à jour dynamiquement
    $updateFields = [];
    $params = [];
    
    $allowedFields = [
        'name', 'slug', 'description', 'short_description', 'image_url', 'thumbnail_url',
        'category', 'platform', 'min_players', 'max_players', 'age_rating',
        'points_per_hour', 'base_price', 'is_reservable', 'reservation_fee', 'is_active', 'is_featured', 'display_order'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($updateFields)) {
        json_response(['error' => 'Aucune donnée à mettre à jour'], 400);
    }
    
    // Ajouter updated_at
    $updateFields[] = 'updated_at = ?';
    $params[] = now();
    $params[] = $id;
    
    try {
        $sql = 'UPDATE games SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json_response([
            'success' => true,
            'message' => 'Jeu mis à jour avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// DELETE: Supprimer un jeu
// ============================================================================
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID du jeu requis'], 400);
    }
    
    // Vérifier s'il y a des achats associés
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM purchases WHERE game_id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        json_response([
            'error' => 'Impossible de supprimer ce jeu car il a des achats associés',
            'suggestion' => 'Désactivez le jeu plutôt que de le supprimer'
        ], 400);
    }
    
    try {
        $stmt = $pdo->prepare('DELETE FROM games WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response([
            'success' => true,
            'message' => 'Jeu supprimé avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405);
