<?php
// api/shop/redeem_with_points.php
// Échanger des points contre un package de jeu

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer tous les packages payables en points
// ============================================================================
if ($method === 'GET') {
    $gameId = $_GET['game_id'] ?? null;
    
    // Requête pour récupérer les packages payables en points
    $sql = '
        SELECT 
            pkg.id,
            pkg.game_id,
            g.name as game_name,
            g.slug as game_slug,
            g.image_url as game_image,
            g.category as game_category,
            pkg.name as package_name,
            pkg.duration_minutes,
            pkg.points_cost,
            pkg.points_earned,
            pkg.bonus_multiplier,
            pkg.is_promotional,
            pkg.promotional_label,
            pkg.max_purchases_per_user,
            pkg.display_order,
            r.id as reward_id,
            r.name as reward_name,
            r.description as reward_description,
            r.image_url as reward_image,
            r.is_featured,
            (
                SELECT COUNT(*) 
                FROM purchases 
                WHERE package_id = pkg.id 
                  AND user_id = ? 
                  AND paid_with_points = 1
                  AND payment_status = "completed"
            ) as user_purchases_count
        FROM game_packages pkg
        INNER JOIN games g ON pkg.game_id = g.id
        LEFT JOIN rewards r ON pkg.reward_id = r.id
        WHERE pkg.is_points_only = 1 
          AND pkg.is_active = 1
          AND g.is_active = 1
          AND (pkg.available_from IS NULL OR pkg.available_from <= NOW())
          AND (pkg.available_until IS NULL OR pkg.available_until >= NOW())
    ';
    
    $params = [$user['id']];
    
    if ($gameId) {
        $sql .= ' AND pkg.game_id = ?';
        $params[] = $gameId;
    }
    
    $sql .= ' ORDER BY g.name, pkg.display_order ASC, pkg.points_cost ASC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $packages = $stmt->fetchAll();
    
    // Récupérer les points actuels de l'utilisateur
    $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $userPoints = $stmt->fetchColumn();
    
    json_response([
        'packages' => $packages,
        'user_points' => $userPoints,
        'count' => count($packages)
    ]);
}

// ============================================================================
// POST: Échanger des points contre un package
// ============================================================================
if ($method === 'POST') {
    $data = get_json_input();
    
    // Validation
    if (!isset($data['package_id']) || !is_numeric($data['package_id'])) {
        json_response(['error' => 'ID du package requis'], 400);
    }
        $packageId = (int)$data['package_id'];
    $scheduledStart = $data['scheduled_start'] ?? null;
    
    try {
        $pdo->beginTransaction();
        
        // Récupérer le package et les infos du jeu
        $stmt = $pdo->prepare('
            SELECT 
                pkg.*,
                g.name as game_name,
                g.slug as game_slug,
                g.is_reservable,
                g.reservation_fee,
                r.id as reward_id
            FROM game_packages pkg
            INNER JOIN games g ON pkg.game_id = g.id
            LEFT JOIN rewards r ON pkg.reward_id = r.id
            WHERE pkg.id = ? AND pkg.is_points_only = 1 AND pkg.is_active = 1
        ');
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();
        
        if (!$package) {
            $pdo->rollBack();
            json_response(['error' => 'Package non trouvé ou non disponible'], 404);
        }
        
        // Vérifier si le jeu est actif
        $stmt = $pdo->prepare('SELECT is_active FROM games WHERE id = ?');
        $stmt->execute([$package['game_id']]);
        $gameActive = $stmt->fetchColumn();
        
        if (!$gameActive) {
            $pdo->rollBack();
            json_response(['error' => 'Ce jeu n\'est pas disponible actuellement'], 400);
        }
        
        // Gestion de la réservation (optionnelle)
        $isReservation = false;
        $scheduledStartDT = null;
        $scheduledEndDT = null;
        
        if (!empty($scheduledStart)) {
            // Vérifier que le jeu est réservable
            if ((int)$package['is_reservable'] !== 1) {
                $pdo->rollBack();
                json_response(['error' => 'Ce jeu ne peut pas être réservé'], 400);
            }
            
            try {
                $scheduledStartDT = new DateTime($scheduledStart);
            } catch (Exception $e) {
                $pdo->rollBack();
                json_response(['error' => 'Format de date invalide pour scheduled_start'], 400);
            }
            
            $isReservation = true;
            $scheduledEndDT = (clone $scheduledStartDT)->modify('+' . (int)$package['duration_minutes'] . ' minutes');
            
            // Vérifier la disponibilité du créneau
            $stmt = $pdo->prepare('
                SELECT COUNT(*) as cnt
                FROM game_reservations
                WHERE game_id = ?
                  AND NOT (scheduled_end <= ? OR scheduled_start >= ?)
                  AND (
                    status = "paid"
                    OR (status = "pending_payment" AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE))
                  )
            ');
            $stmt->execute([
                $package['game_id'], 
                $scheduledStartDT->format('Y-m-d H:i:s'), 
                $scheduledEndDT->format('Y-m-d H:i:s')
            ]);
            $conflict = $stmt->fetch();
            
            if (($conflict['cnt'] ?? 0) > 0) {
                $pdo->rollBack();
                json_response([
                    'error' => 'Créneau indisponible pour ce jeu',
                    'code' => 'time_slot_unavailable',
                    'scheduled_start' => $scheduledStartDT->format('Y-m-d H:i:s'),
                    'scheduled_end' => $scheduledEndDT->format('Y-m-d H:i:s')
                ], 409);
            }
        }
        
        // Vérifier les limitations d'achat
        if ($package['max_purchases_per_user']) {
            $stmt = $pdo->prepare('
                SELECT COUNT(*) 
                FROM purchases 
                WHERE package_id = ? 
                  AND user_id = ? 
                  AND paid_with_points = 1
                  AND payment_status = "completed"
            ');
            $stmt->execute([$packageId, $user['id']]);
            $userPurchases = $stmt->fetchColumn();
            
            if ($userPurchases >= $package['max_purchases_per_user']) {
                $pdo->rollBack();
                json_response([
                    'error' => 'Limite d\'achats atteinte pour ce package',
                    'max_purchases' => $package['max_purchases_per_user'],
                    'current_purchases' => $userPurchases
                ], 400);
            }
        }
        
        // Récupérer les points actuels de l'utilisateur avec verrouillage
        $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$user['id']]);
        $currentPoints = (int)$stmt->fetchColumn();
        
        // Vérifier si l'utilisateur a assez de points
        if ($currentPoints < $package['points_cost']) {
            $pdo->rollBack();
            json_response([
                'error' => 'Points insuffisants',
                'required_points' => $package['points_cost'],
                'current_points' => $currentPoints,
                'missing_points' => $package['points_cost'] - $currentPoints
            ], 400);
        }
        
        // Déduire les points
        $newPoints = $currentPoints - $package['points_cost'];
        $stmt = $pdo->prepare('UPDATE users SET points = ? WHERE id = ?');
        $stmt->execute([$newPoints, $user['id']]);
        
        $ts = now();
        
        // Créer l'achat
        $stmt = $pdo->prepare('
            INSERT INTO purchases (
                user_id, game_id, package_id,
                game_name, package_name, duration_minutes,
                price, currency, points_earned, points_credited,
                paid_with_points, points_spent,
                payment_method_id, payment_method_name,
                payment_status, session_status,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $user['id'],
            $package['game_id'],
            $packageId,
            $package['game_name'],
            $package['name'],
            $package['duration_minutes'],
            0.00, // Prix en argent = 0
            'PTS',
            $package['points_earned'],
            0, // Points pas encore crédités (crédités après le jeu)
            1, // paid_with_points = true
            $package['points_cost'],
            null, // Pas de méthode de paiement
            'Points Fidélité',
            'completed', // Paiement immédiatement complété
            'pending', // Session en attente
            $ts,
            $ts
        ]);
        
        $purchaseId = $pdo->lastInsertId();
        
        // Si réservation, créer l'entrée dans game_reservations
        if ($isReservation) {
            $reservationFee = (float)($package['reservation_fee'] ?? 0.00);
            $stmt = $pdo->prepare('
                INSERT INTO game_reservations (
                    user_id, game_id, purchase_id,
                    scheduled_start, scheduled_end, duration_minutes,
                    base_price, reservation_fee, total_price, currency,
                    status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "paid", ?, ?)
            ');
            $stmt->execute([
                $user['id'],
                $package['game_id'],
                $purchaseId,
                $scheduledStartDT->format('Y-m-d H:i:s'),
                $scheduledEndDT->format('Y-m-d H:i:s'),
                (int)$package['duration_minutes'],
                0.00, // Base price = 0 car payé en points
                $reservationFee,
                0.00, // Total = 0 car payé en points
                'PTS',
                $ts,
                $ts
            ]);
        }
        
        // Enregistrer la transaction de points
        $stmt = $pdo->prepare('
            INSERT INTO points_transactions (
                user_id, type, change_amount, reason, created_at
            ) VALUES (?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $user['id'],
            'reward',
            -$package['points_cost'],
            'Échange de points pour ' . $package['name'] . ' - ' . $package['game_name'] . ' (Purchase #' . $purchaseId . ')',
            $ts
        ]);
        
        // Si récompense associée, créer un échange de récompense
        if ($package['reward_id']) {
            $stmt = $pdo->prepare('
                INSERT INTO reward_redemptions (
                    reward_id, user_id, cost, created_at
                ) VALUES (?, ?, ?, ?)
            ');

            $stmt->execute([
                $package['reward_id'],
                $user['id'],
                $package['points_cost'],
                $ts
            ]);
        }
        
        $pdo->commit();
        
        $message = 'Échange effectué avec succès ! Votre session de jeu est prête.';
        if ($isReservation) {
            $message = 'Réservation créée avec succès ! Vous pourrez jouer à partir du ' . 
                       $scheduledStartDT->format('d/m/Y à H:i');
        }
        
        json_response([
            'success' => true,
            'message' => $message,
            'purchase_id' => $purchaseId,
            'package_name' => $package['name'],
            'game_name' => $package['game_name'],
            'duration_minutes' => $package['duration_minutes'],
            'points_spent' => $package['points_cost'],
            'points_earned' => $package['points_earned'],
            'remaining_points' => $newPoints,
            'is_reservation' => $isReservation,
            'scheduled_start' => $isReservation ? $scheduledStartDT->format('Y-m-d H:i:s') : null,
            'redirect_to' => $isReservation ? '/player/my-reservations' : '/player/my-purchases'
        ], 201);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        log_error('Erreur lors de l\'échange de points', [
            'user_id' => $user['id'],
            'package_id' => $packageId,
            'error' => $e->getMessage()
        ]);
        json_response(['error' => 'Erreur lors de l\'échange', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
