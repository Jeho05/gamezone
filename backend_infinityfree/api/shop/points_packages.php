<?php
// api/shop/points_packages.php
// API pour acheter des packages de points
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Liste des packages de points disponibles
// ============================================================================
if ($method === 'GET') {
    // Récupérer tous les packages actifs
    $stmt = $pdo->query('
        SELECT * FROM points_packages
        WHERE is_active = 1
        ORDER BY display_order ASC, price ASC
    ');
    $packages = $stmt->fetchAll();
    
    // Calculer le total de points pour chaque package
    foreach ($packages as &$package) {
        $package['total_points'] = (int)$package['points_amount'] + (int)$package['bonus_points'];
        $package['price'] = (float)$package['price'];
    }
    
    json_response([
        'success' => true,
        'packages' => $packages
    ]);
}

// ============================================================================
// POST: Acheter un package de points
// ============================================================================
if ($method === 'POST') {
    $user = require_auth();
    $data = get_json_input();
    
    $packageId = $data['package_id'] ?? null;
    $paymentMethod = $data['payment_method'] ?? null;
    
    if (!$packageId || !$paymentMethod) {
        json_response(['error' => 'package_id et payment_method requis'], 400);
    }
    
    $pdo->beginTransaction();
    
    try {
        // Récupérer le package
        $stmt = $pdo->prepare('SELECT * FROM points_packages WHERE id = ? AND is_active = 1');
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();
        
        if (!$package) {
            json_response(['error' => 'Package non trouvé ou indisponible'], 404);
        }
        
        $totalPoints = (int)$package['points_amount'] + (int)$package['bonus_points'];
        $ts = now();
        
        // Créer l'achat
        $stmt = $pdo->prepare('
            INSERT INTO points_package_purchases (
                user_id, package_id, points_amount, bonus_points, total_points,
                price, currency, payment_method, payment_status,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $user['id'],
            $packageId,
            $package['points_amount'],
            $package['bonus_points'],
            $totalPoints,
            $package['price'],
            $package['currency'],
            $paymentMethod,
            'pending', // En attente de confirmation
            $ts,
            $ts
        ]);
        
        $purchaseId = $pdo->lastInsertId();
        
        // Si paiement automatique (par exemple, si gratuit pour test)
        if ($paymentMethod === 'test' || $paymentMethod === 'admin_credit') {
            // Créditer immédiatement les points
            $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
            $stmt->execute([$totalPoints, $ts, $user['id']]);
            
            // Marquer l'achat comme complété
            $stmt = $pdo->prepare('
                UPDATE points_package_purchases 
                SET payment_status = "completed", points_credited = 1, credited_at = ?, updated_at = ?
                WHERE id = ?
            ');
            $stmt->execute([$ts, $ts, $purchaseId]);
            
            // Enregistrer la transaction de points
            $stmt = $pdo->prepare('
                INSERT INTO points_transactions (
                    user_id, type, change_amount, reason, created_at
                ) VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $user['id'],
                'game',
                $totalPoints,
                "Achat package: {$package['name']}",
                $ts
            ]);
        }
        
        $pdo->commit();
        
        json_response([
            'success' => true,
            'message' => 'Achat créé avec succès',
            'purchase_id' => $purchaseId,
            'package' => $package,
            'total_points' => $totalPoints,
            'payment_required' => ($paymentMethod !== 'test' && $paymentMethod !== 'admin_credit')
        ], 201);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['error' => 'Erreur lors de la création de l\'achat', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
