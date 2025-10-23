<?php
// api/admin/rewards.php
// Gestion complète des récompenses par l'admin
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer les récompenses
// ============================================================================
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer une récompense spécifique
        $stmt = $pdo->prepare('
            SELECT r.*,
                   g.name as game_name,
                   g.slug as game_slug,
                   pkg.name as package_name,
                   pkg.duration_minutes,
                   pkg.points_cost as package_points_cost,
                   pkg.points_earned as package_points_earned,
                   (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.id) as redemptions_count,
                   (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.id AND status = "pending") as pending_count,
                   (SELECT COUNT(*) FROM purchases WHERE package_id = pkg.id AND paid_with_points = 1) as package_purchases_count
            FROM rewards r
            LEFT JOIN game_packages pkg ON r.game_package_id = pkg.id
            LEFT JOIN games g ON pkg.game_id = g.id
            WHERE r.id = ?
        ');
        $stmt->execute([$id]);
        $reward = $stmt->fetch();
        
        if (!$reward) {
            json_response(['error' => 'Récompense non trouvée'], 404);
        }
        
        json_response(['reward' => $reward]);
    } else {
        // Récupérer toutes les récompenses
        $stmt = $pdo->query('
            SELECT r.*,
                   g.name as game_name,
                   g.slug as game_slug,
                   pkg.name as package_name,
                   pkg.duration_minutes,
                   pkg.points_cost as package_points_cost,
                   pkg.points_earned as package_points_earned,
                   (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.id) as redemptions_count,
                   (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.id AND status = "pending") as pending_count,
                   (SELECT COUNT(*) FROM purchases WHERE package_id = pkg.id AND paid_with_points = 1) as package_purchases_count
            FROM rewards r
            LEFT JOIN game_packages pkg ON r.game_package_id = pkg.id
            LEFT JOIN games g ON pkg.game_id = g.id
            ORDER BY r.cost ASC
        ');
        $rewards = $stmt->fetchAll();
        
        json_response(['rewards' => $rewards, 'count' => count($rewards)]);
    }
}

// ============================================================================
// POST: Créer une nouvelle récompense (avec ou sans package de jeu)
// ============================================================================
if ($method === 'POST') {
    $data = get_json_input();
    
    // Validation
    $required = ['name', 'cost'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    // Si c'est une récompense de type game_package, valider les champs supplémentaires
    $rewardType = $data['reward_type'] ?? 'physical';
    if ($rewardType === 'game_package') {
        $packageRequired = ['game_id', 'duration_minutes', 'points_earned'];
        foreach ($packageRequired as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                json_response(['error' => "Le champ '$field' est requis pour un package de jeu"], 400);
            }
        }
        
        // Vérifier que le jeu existe
        $stmt = $pdo->prepare('SELECT id, name FROM games WHERE id = ?');
        $stmt->execute([$data['game_id']]);
        $game = $stmt->fetch();
        if (!$game) {
            json_response(['error' => 'Jeu non trouvé'], 404);
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        $ts = now();
        $packageId = null;
        
        // Si c'est un package de jeu, créer d'abord le package
        if ($rewardType === 'game_package') {
            $stmt = $pdo->prepare('
                INSERT INTO game_packages (
                    game_id, name, duration_minutes, price, 
                    points_earned, bonus_multiplier, points_cost,
                    is_points_only, is_promotional, promotional_label,
                    max_purchases_per_user, available_from, available_until,
                    is_active, display_order, created_by, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $packageName = $data['package_name'] ?? ($data['name'] . ' - ' . $data['duration_minutes'] . ' min');
            
            $stmt->execute([
                $data['game_id'],
                $packageName,
                $data['duration_minutes'],
                0.00, // Prix en argent = 0 (payable uniquement en points)
                $data['points_earned'],
                $data['bonus_multiplier'] ?? 1.00,
                $data['cost'], // Le coût de la récompense devient le points_cost du package
                1, // is_points_only = true
                $data['is_promotional'] ?? 0,
                $data['promotional_label'] ?? null,
                $data['max_per_user'] ?? null,
                $data['available_from'] ?? null,
                $data['available_until'] ?? null,
                $data['available'] ?? 1,
                $data['display_order'] ?? 0,
                $user['id'],
                $ts,
                $ts
            ]);
            
            $packageId = $pdo->lastInsertId();
        }
        
        // Créer la récompense
        $stmt = $pdo->prepare('
            INSERT INTO rewards (
                name, description, cost, category, reward_type,
                game_package_id, image_url, stock_quantity, max_per_user,
                available, is_featured, display_order,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['cost'],
            $data['category'] ?? ($rewardType === 'game_package' ? 'gaming' : 'general'),
            $rewardType,
            $packageId,
            $data['image_url'] ?? null,
            $data['stock_quantity'] ?? null,
            $data['max_per_user'] ?? null,
            $data['available'] ?? 1,
            $data['is_featured'] ?? 0,
            $data['display_order'] ?? 0,
            $ts,
            $ts
        ]);
        
        $rewardId = $pdo->lastInsertId();
        
        // Si un package a été créé, mettre à jour le reward_id du package
        if ($packageId) {
            $stmt = $pdo->prepare('UPDATE game_packages SET reward_id = ? WHERE id = ?');
            $stmt->execute([$rewardId, $packageId]);
        }
        
        $pdo->commit();
        
        json_response([
            'success' => true,
            'message' => $rewardType === 'game_package' 
                ? 'Récompense et package de jeu créés avec succès' 
                : 'Récompense créée avec succès',
            'reward_id' => $rewardId,
            'package_id' => $packageId
        ], 201);
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['error' => 'Erreur lors de la création', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// PUT/PATCH: Mettre à jour une récompense
// ============================================================================
if ($method === 'PUT' || $method === 'PATCH') {
    $data = get_json_input();
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID de la récompense requis'], 400);
    }
    
    // Vérifier que la récompense existe
    $stmt = $pdo->prepare('SELECT id FROM rewards WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Récompense non trouvée'], 404);
    }
    
    // Construire la requête de mise à jour
    $updateFields = [];
    $params = [];
    
    $allowedFields = [
        'name', 'description', 'cost', 'category', 'reward_type',
        'image_url', 'stock_quantity', 'max_per_user',
        'available', 'is_featured', 'display_order'
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
    
    $updateFields[] = 'updated_at = ?';
    $params[] = now();
    $params[] = $id;
    
    try {
        $sql = 'UPDATE rewards SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json_response([
            'success' => true,
            'message' => 'Récompense mise à jour avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// DELETE: Supprimer une récompense
// ============================================================================
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID de la récompense requis'], 400);
    }
    
    // Vérifier s'il y a des échanges
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM reward_redemptions WHERE reward_id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        json_response([
            'error' => 'Impossible de supprimer cette récompense car elle a des échanges associés',
            'suggestion' => 'Rendez-la indisponible plutôt que de la supprimer'
        ], 400);
    }
    
    try {
        $stmt = $pdo->prepare('DELETE FROM rewards WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response([
            'success' => true,
            'message' => 'Récompense supprimée avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
