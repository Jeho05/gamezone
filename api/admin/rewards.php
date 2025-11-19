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

    // Normaliser le type de récompense
    $rewardType = $data['reward_type'] ?? 'physical';

    // Normaliser les valeurs numériques optionnelles pour éviter les "" non valides
    $maxPerUser = (isset($data['max_per_user']) && $data['max_per_user'] !== '')
        ? (int)$data['max_per_user']
        : null;
    $stockQuantity = (isset($data['stock_quantity']) && $data['stock_quantity'] !== '')
        ? (int)$data['stock_quantity']
        : null;
    $displayOrder = (isset($data['display_order']) && $data['display_order'] !== '')
        ? (int)$data['display_order']
        : 0;
    $availableFlag = isset($data['available']) ? (int)$data['available'] : 1;
    $isFeaturedFlag = isset($data['is_featured']) ? (int)$data['is_featured'] : 0;

    // Minutes de jeu pour les récompenses de type game_time
    $gameTimeMinutes = (isset($data['game_time_minutes']) && $data['game_time_minutes'] !== '')
        ? (int)$data['game_time_minutes']
        : 0;

    // Validation de base
    $required = ['name', 'cost'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }

    // Validation spécifique pour game_time
    if ($rewardType === 'game_time' && $gameTimeMinutes <= 0) {
        json_response(['error' => 'Nombre de minutes de jeu invalide pour une récompense de type game_time'], 400);
    }
    
    // Si c'est une récompense de type game_package, valider les champs supplémentaires
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
                $maxPerUser,
                $data['available_from'] ?? null,
                $data['available_until'] ?? null,
                $availableFlag,
                $displayOrder,
                $user['id'],
                $ts,
                $ts
            ]);
            
            $packageId = $pdo->lastInsertId();
        }
        
        // Créer la récompense (inclut désormais game_time_minutes)
        $stmt = $pdo->prepare('
            INSERT INTO rewards (
                name, description, cost, category, reward_type,
                game_package_id, game_time_minutes, image_url, stock_quantity, max_per_user,
                available, is_featured, display_order,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['cost'],
            $data['category'] ?? ($rewardType === 'game_package' ? 'gaming' : 'general'),
            $rewardType,
            $packageId,
            $gameTimeMinutes,
            $data['image_url'] ?? null,
            $stockQuantity,
            $maxPerUser,
            $availableFlag,
            $isFeaturedFlag,
            $displayOrder,
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
    
    // Vérifier que la récompense existe et récupérer le lien éventuel vers un package de jeu
    $stmt = $pdo->prepare('SELECT id, reward_type, game_package_id FROM rewards WHERE id = ?');
    $stmt->execute([$id]);
    $rewardRow = $stmt->fetch();
    if (!$rewardRow) {
        json_response(['error' => 'Récompense non trouvée'], 404);
    }

    // Normaliser les valeurs numériques optionnelles pour éviter les "" non valides
    if (array_key_exists('max_per_user', $data)) {
        $data['max_per_user'] = ($data['max_per_user'] === '' || $data['max_per_user'] === null)
            ? null
            : (int)$data['max_per_user'];
    }
    if (array_key_exists('stock_quantity', $data)) {
        $data['stock_quantity'] = ($data['stock_quantity'] === '' || $data['stock_quantity'] === null)
            ? null
            : (int)$data['stock_quantity'];
    }
    if (array_key_exists('display_order', $data)) {
        $data['display_order'] = ($data['display_order'] === '' || $data['display_order'] === null)
            ? 0
            : (int)$data['display_order'];
    }
    if (array_key_exists('available', $data)) {
        $data['available'] = (int)$data['available'];
    }
    if (array_key_exists('is_featured', $data)) {
        $data['is_featured'] = (int)$data['is_featured'];
    }
    if (array_key_exists('game_time_minutes', $data)) {
        $data['game_time_minutes'] = (int)$data['game_time_minutes'];
    }
    
    // Construire la requête de mise à jour
    $updateFields = [];
    $params = [];
    
    $allowedFields = [
        'name', 'description', 'cost', 'category', 'reward_type',
        'image_url', 'stock_quantity', 'max_per_user',
        'available', 'is_featured', 'display_order', 'game_time_minutes'
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

        // Si la récompense est liée à un package de jeu, mettre aussi à jour le package
        if ($rewardRow['reward_type'] === 'game_package' && !empty($rewardRow['game_package_id'])) {
            $pkgId = (int)$rewardRow['game_package_id'];

            $pkgFields = [];
            $pkgParams = [];

            if (isset($data['game_id'])) {
                $pkgFields[] = 'game_id = ?';
                $pkgParams[] = (int)$data['game_id'];
            }
            if (isset($data['duration_minutes'])) {
                $pkgFields[] = 'duration_minutes = ?';
                $pkgParams[] = (int)$data['duration_minutes'];
            }
            if (isset($data['points_earned'])) {
                $pkgFields[] = 'points_earned = ?';
                $pkgParams[] = (int)$data['points_earned'];
            }
            if (isset($data['bonus_multiplier'])) {
                $pkgFields[] = 'bonus_multiplier = ?';
                $pkgParams[] = (float)$data['bonus_multiplier'];
            }
            if (array_key_exists('max_per_user', $data)) {
                $pkgFields[] = 'max_purchases_per_user = ?';
                $pkgParams[] = $data['max_per_user']; // déjà normalisé
            }
            if (isset($data['is_promotional'])) {
                $pkgFields[] = 'is_promotional = ?';
                $pkgParams[] = (int)$data['is_promotional'];
            }
            if (array_key_exists('promotional_label', $data)) {
                $pkgFields[] = 'promotional_label = ?';
                $pkgParams[] = ($data['promotional_label'] === '' ? null : $data['promotional_label']);
            }
            if (isset($data['available'])) {
                $pkgFields[] = 'is_active = ?';
                $pkgParams[] = (int)$data['available'];
            }
            if (isset($data['display_order'])) {
                $pkgFields[] = 'display_order = ?';
                $pkgParams[] = (int)$data['display_order'];
            }

            if (!empty($pkgFields)) {
                $pkgFields[] = 'updated_at = ?';
                $pkgParams[] = now();
                $pkgParams[] = $pkgId;

                $sqlPkg = 'UPDATE game_packages SET ' . implode(', ', $pkgFields) . ' WHERE id = ?';
                $stmtPkg = $pdo->prepare($sqlPkg);
                $stmtPkg->execute($pkgParams);
            }
        }

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
    // Accepter l'ID via JSON (body) ou via query string
    $body = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $body = get_json_input();
    }

    $id = $body['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        json_response(['error' => 'ID de la récompense requis'], 400);
    }

    // Charger la récompense pour connaître son type et le package lié
    $stmt = $pdo->prepare('SELECT id, reward_type, game_package_id FROM rewards WHERE id = ?');
    $stmt->execute([$id]);
    $reward = $stmt->fetch();

    if (!$reward) {
        json_response(['error' => 'Récompense non trouvée'], 404);
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
        // Si un package de jeu est lié, le désactiver pour éviter un package orphelin actif
        if ($reward['reward_type'] === 'game_package' && !empty($reward['game_package_id'])) {
            $stmtPkg = $pdo->prepare('UPDATE game_packages SET is_active = 0, updated_at = ? WHERE id = ?');
            $stmtPkg->execute([now(), (int)$reward['game_package_id']]);
        }

        // Supprimer la récompense elle-même
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
