<?php
// api/admin/game_packages.php
// API Admin pour gérer les packages de temps de jeu

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est admin
$user = require_auth('admin');
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer tous les packages ou un package spécifique
// ============================================================================
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    $game_id = $_GET['game_id'] ?? null;
    
    if ($id) {
        // Récupérer un package spécifique
        $stmt = $pdo->prepare('
            SELECT pkg.*, 
                   g.name as game_name,
                   g.slug as game_slug,
                   u.username as created_by_username,
                   (SELECT COUNT(*) FROM purchases WHERE package_id = pkg.id) as purchases_count,
                   (SELECT SUM(price) FROM purchases WHERE package_id = pkg.id AND payment_status = "completed") as revenue
            FROM game_packages pkg
            INNER JOIN games g ON pkg.game_id = g.id
            LEFT JOIN users u ON pkg.created_by = u.id
            WHERE pkg.id = ?
        ');
        $stmt->execute([$id]);
        $package = $stmt->fetch();
        
        if (!$package) {
            json_response(['error' => 'Package non trouvé'], 404);
        }
        
        json_response(['package' => $package]);
    } else {
        // Récupérer tous les packages (avec filtre optionnel par jeu)
        $sql = '
            SELECT pkg.*, 
                   g.name as game_name,
                   g.slug as game_slug,
                   u.username as created_by_username,
                   (SELECT COUNT(*) FROM purchases WHERE package_id = pkg.id) as purchases_count,
                   (SELECT SUM(price) FROM purchases WHERE package_id = pkg.id AND payment_status = "completed") as revenue
            FROM game_packages pkg
            INNER JOIN games g ON pkg.game_id = g.id
            LEFT JOIN users u ON pkg.created_by = u.id
            WHERE 1=1
        ';
        $params = [];
        
        if ($game_id) {
            $sql .= ' AND pkg.game_id = ?';
            $params[] = $game_id;
        }
        
        $sql .= ' ORDER BY g.name, pkg.display_order ASC, pkg.duration_minutes ASC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $packages = $stmt->fetchAll();
        
        json_response(['packages' => $packages]);
    }
}

// ============================================================================
// POST: Créer un nouveau package
// ============================================================================
if ($method === 'POST') {
    $data = get_json_input();
    
    // Validation
    $required = ['game_id', 'name', 'duration_minutes', 'price', 'points_earned'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    // Vérifier que le jeu existe
    $stmt = $pdo->prepare('SELECT id FROM games WHERE id = ?');
    $stmt->execute([$data['game_id']]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Jeu non trouvé'], 404);
    }
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO game_packages (
                game_id, name, duration_minutes, price, original_price,
                points_earned, bonus_multiplier, is_promotional, promotional_label,
                max_purchases_per_user, available_from, available_until,
                is_active, display_order, created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $ts = now();
        $stmt->execute([
            $data['game_id'],
            $data['name'],
            $data['duration_minutes'],
            $data['price'],
            $data['original_price'] ?? null,
            $data['points_earned'],
            $data['bonus_multiplier'] ?? 1.00,
            $data['is_promotional'] ?? 0,
            $data['promotional_label'] ?? null,
            $data['max_purchases_per_user'] ?? null,
            $data['available_from'] ?? null,
            $data['available_until'] ?? null,
            $data['is_active'] ?? 1,
            $data['display_order'] ?? 0,
            $user['id'],
            $ts,
            $ts
        ]);
        
        $packageId = $pdo->lastInsertId();
        
        json_response([
            'success' => true,
            'message' => 'Package créé avec succès',
            'package_id' => $packageId
        ], 201);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la création du package', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// PUT/PATCH: Mettre à jour un package
// ============================================================================
if ($method === 'PUT' || $method === 'PATCH') {
    $data = get_json_input();
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID du package requis'], 400);
    }
    
    // Vérifier que le package existe
    $stmt = $pdo->prepare('SELECT id FROM game_packages WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Package non trouvé'], 404);
    }
    
    // Si game_id est fourni, vérifier que le jeu existe
    if (isset($data['game_id'])) {
        $stmt = $pdo->prepare('SELECT id FROM games WHERE id = ?');
        $stmt->execute([$data['game_id']]);
        if (!$stmt->fetch()) {
            json_response(['error' => 'Jeu non trouvé'], 404);
        }
    }
    
    // Construire la requête de mise à jour dynamiquement
    $updateFields = [];
    $params = [];
    
    $allowedFields = [
        'game_id', 'name', 'duration_minutes', 'price', 'original_price',
        'points_earned', 'bonus_multiplier', 'is_promotional', 'promotional_label',
        'max_purchases_per_user', 'available_from', 'available_until',
        'is_active', 'display_order'
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
        $sql = 'UPDATE game_packages SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json_response([
            'success' => true,
            'message' => 'Package mis à jour avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// DELETE: Supprimer un package
// ============================================================================
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID du package requis'], 400);
    }
    
    // Vérifier s'il y a des achats associés
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM purchases WHERE package_id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        json_response([
            'error' => 'Impossible de supprimer ce package car il a des achats associés',
            'suggestion' => 'Désactivez le package plutôt que de le supprimer'
        ], 400);
    }
    
    try {
        $stmt = $pdo->prepare('DELETE FROM game_packages WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response([
            'success' => true,
            'message' => 'Package supprimé avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405);
