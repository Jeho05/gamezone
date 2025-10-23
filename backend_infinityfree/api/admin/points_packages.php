<?php
// api/admin/points_packages.php
// Gestion des packages de points à vendre
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer les packages de points
// ============================================================================
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer un package spécifique
        $stmt = $pdo->prepare('
            SELECT pp.*,
                   (SELECT COUNT(*) FROM points_package_purchases WHERE package_id = pp.id) as purchases_count,
                   (SELECT SUM(price) FROM points_package_purchases WHERE package_id = pp.id AND payment_status = "completed") as revenue
            FROM points_packages pp
            WHERE pp.id = ?
        ');
        $stmt->execute([$id]);
        $package = $stmt->fetch();
        
        if (!$package) {
            json_response(['error' => 'Package non trouvé'], 404);
        }
        
        json_response(['package' => $package]);
    } else {
        // Récupérer tous les packages
        $sql = '
            SELECT pp.*,
                   (SELECT COUNT(*) FROM points_package_purchases WHERE package_id = pp.id) as purchases_count,
                   (SELECT SUM(price) FROM points_package_purchases WHERE package_id = pp.id AND payment_status = "completed") as revenue
            FROM points_packages pp
            ORDER BY pp.display_order ASC, pp.points_amount ASC
        ';
        
        $stmt = $pdo->query($sql);
        $packages = $stmt->fetchAll();
        
        json_response(['packages' => $packages]);
    }
}

// ============================================================================
// POST: Créer un nouveau package de points
// ============================================================================
if ($method === 'POST') {
    $data = get_json_input();
    
    // Validation
    $required = ['name', 'points_amount', 'price'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    try {
        $ts = now();
        
        $stmt = $pdo->prepare('
            INSERT INTO points_packages (
                name, description, points_amount, bonus_points, price, currency,
                discount_percentage, is_featured, is_active, display_order, image_url,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['points_amount'],
            $data['bonus_points'] ?? 0,
            $data['price'],
            $data['currency'] ?? 'XOF',
            $data['discount_percentage'] ?? 0,
            $data['is_featured'] ?? 0,
            $data['is_active'] ?? 1,
            $data['display_order'] ?? 0,
            $data['image_url'] ?? null,
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
    $stmt = $pdo->prepare('SELECT id FROM points_packages WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Package non trouvé'], 404);
    }
    
    // Construire la requête de mise à jour
    $updateFields = [];
    $params = [];
    
    $allowedFields = [
        'name', 'description', 'points_amount', 'bonus_points', 'price', 'currency',
        'discount_percentage', 'is_featured', 'is_active', 'display_order', 'image_url'
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
        $sql = 'UPDATE points_packages SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
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
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM points_package_purchases WHERE package_id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        json_response([
            'error' => 'Impossible de supprimer ce package car il a des achats associés',
            'suggestion' => 'Désactivez le package plutôt que de le supprimer'
        ], 400);
    }
    
    try {
        $stmt = $pdo->prepare('DELETE FROM points_packages WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response([
            'success' => true,
            'message' => 'Package supprimé avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
