<?php
// api/shop/purchase_with_package.php
// API améliorée pour acheter un jeu avec package ET méthode de paiement
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

require_method(['POST']);
$data = get_json_input();

// Validation des données requises
$required = ['game_id', 'package_id', 'payment_method_id'];
foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        json_response(['error' => "Le champ '$field' est requis"], 400);
    }
}

$gameId = (int)$data['game_id'];
$packageId = (int)$data['package_id'];
$paymentMethodId = (int)$data['payment_method_id'];

$pdo->beginTransaction();

try {
    // Vérifier que le jeu existe et est actif
    $stmt = $pdo->prepare('SELECT * FROM games WHERE id = ? AND is_active = 1');
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    
    if (!$game) {
        $pdo->rollBack();
        json_response(['error' => 'Jeu non trouvé ou indisponible'], 404);
    }
    
    // Vérifier que le package existe, est actif et lié au jeu
    $stmt = $pdo->prepare('
        SELECT * FROM game_packages 
        WHERE id = ? AND game_id = ? AND is_active = 1
        AND (available_from IS NULL OR available_from <= NOW())
        AND (available_until IS NULL OR available_until >= NOW())
    ');
    $stmt->execute([$packageId, $gameId]);
    $package = $stmt->fetch();
    
    if (!$package) {
        $pdo->rollBack();
        json_response(['error' => 'Package non trouvé, indisponible ou non lié à ce jeu'], 404);
    }
    
    // Vérifier la limite d'achats par utilisateur pour ce package
    if ($package['max_purchases_per_user']) {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as count 
            FROM purchases 
            WHERE user_id = ? AND package_id = ? AND payment_status = "completed"
        ');
        $stmt->execute([$user['id'], $packageId]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= $package['max_purchases_per_user']) {
            $pdo->rollBack();
            json_response([
                'error' => 'Vous avez atteint la limite d\'achats pour ce package',
                'max_purchases' => (int)$package['max_purchases_per_user'],
                'current_purchases' => (int)$result['count']
            ], 400);
        }
    }
    
    // Vérifier que la méthode de paiement existe et est active
    $stmt = $pdo->prepare('SELECT * FROM payment_methods WHERE id = ? AND is_active = 1');
    $stmt->execute([$paymentMethodId]);
    $paymentMethod = $stmt->fetch();
    
    if (!$paymentMethod) {
        $pdo->rollBack();
        json_response(['error' => 'Méthode de paiement non trouvée ou indisponible'], 404);
    }
    
    // Gestion de la réservation (optionnelle): scheduled_start
    $isReservation = false;
    $scheduledStart = null;
    $scheduledEnd = null;
    if (isset($data['scheduled_start']) && trim((string)$data['scheduled_start']) !== '') {
        if ((int)$game['is_reservable'] !== 1) {
            $pdo->rollBack();
            json_response(['error' => 'Ce jeu ne peut pas être réservé'], 400);
        }
        try {
            $scheduledStart = new DateTime((string)$data['scheduled_start']);
        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(['error' => 'Format de date invalide pour scheduled_start (attendu: ISO ou Y-m-d H:i:s)'], 400);
        }
        $isReservation = true;
        $scheduledEnd = (clone $scheduledStart)->modify('+' . (int)$package['duration_minutes'] . ' minutes');

        // Vérifier la disponibilité du créneau (conflits avec autres réservations)
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
        $stmt->execute([$gameId, $scheduledStart->format('Y-m-d H:i:s'), $scheduledEnd->format('Y-m-d H:i:s')]);
        $conflict = $stmt->fetch();
        if (($conflict['cnt'] ?? 0) > 0) {
            $pdo->rollBack();
            json_response([
                'error' => 'Créneau indisponible pour ce jeu',
                'code' => 'time_slot_unavailable',
                'scheduled_start' => $scheduledStart->format('Y-m-d H:i:s'),
                'scheduled_end' => $scheduledEnd->format('Y-m-d H:i:s')
            ], 409);
        }
    }

    // Calculer le montant total avec les frais de paiement
    $baseAmount = (float)$package['price'];
    if ($isReservation) {
        $baseAmount += (float)($game['reservation_fee'] ?? 0.00);
    }
    $feePercentage = (float)$paymentMethod['fee_percentage'];
    $feeFixed = (float)$paymentMethod['fee_fixed'];
    $feeAmount = ($baseAmount * $feePercentage / 100) + $feeFixed;
    $totalAmount = $baseAmount + $feeAmount;
    
    // Calculer les points à gagner avec le multiplicateur de bonus du package
    $basePoints = (int)$package['points_earned'];
    $bonusMultiplier = (float)($package['bonus_multiplier'] ?? 1.0);
    $totalPoints = (int)round($basePoints * $bonusMultiplier);
    
    $ts = now();
    
    // Créer l'achat dans la table purchases
    $stmt = $pdo->prepare('
        INSERT INTO purchases (
            user_id, game_id, package_id,
            game_name, package_name, duration_minutes,
            price, currency,
            points_earned, points_credited,
            payment_method_id, payment_method_name, payment_status,
            session_status,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $paymentStatus = (int)$paymentMethod['auto_confirm'] ? 'processing' : 'pending';
    
    $stmt->execute([
        $user['id'],
        $gameId,
        $packageId,
        $game['name'],
        $package['name'],
        $package['duration_minutes'],
        $totalAmount,
        'XOF',
        $totalPoints,
        0, // points_credited = false initialement
        $paymentMethodId,
        $paymentMethod['name'],
        $paymentStatus,
        'pending',
        $ts,
        $ts
    ]);
    
    $purchaseId = $pdo->lastInsertId();

    // Si réservation, créer l'entrée dans game_reservations
    if ($isReservation) {
        $stmt = $pdo->prepare('
            INSERT INTO game_reservations (
                user_id, game_id, purchase_id,
                scheduled_start, scheduled_end, duration_minutes,
                base_price, reservation_fee, total_price, currency,
                status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending_payment", ?, ?)
        ');
        $stmt->execute([
            $user['id'],
            $gameId,
            $purchaseId,
            $scheduledStart->format('Y-m-d H:i:s'),
            $scheduledEnd->format('Y-m-d H:i:s'),
            (int)$package['duration_minutes'],
            (float)$package['price'] + (float)($game['reservation_fee'] ?? 0.00),
            (float)($game['reservation_fee'] ?? 0.00),
            $totalAmount,
            'XOF',
            $ts,
            $ts
        ]);
    }
    
    // Enregistrer la transaction de paiement initiale
    $stmt = $pdo->prepare('
        INSERT INTO payment_transactions (
            purchase_id, transaction_type, amount, currency,
            provider_status, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $purchaseId,
        'charge',
        $totalAmount,
        'XOF',
        'pending',
        sprintf("Achat créé - %s (%s) - %d minutes - %d points à gagner", 
            $game['name'], 
            $package['name'],
            $package['duration_minutes'],
            $totalPoints
        ),
        $ts
    ]);
    
    // Préparer les données de paiement si nécessaire
    $paymentData = null;
    if ($paymentMethod['requires_online_payment']) {
        $reference = 'PURCHASE-' . $purchaseId . '-' . time();
        $baseUrl = rtrim((getenv('APP_BASE_URL') ?: 'http://localhost/projet%20ismo'), '/');
        $paymentData = [
            'provider' => $paymentMethod['provider'],
            'amount' => $totalAmount,
            'currency' => 'XOF',
            'description' => sprintf("%s - %s (%d min)", 
                $game['name'], 
                $package['name'], 
                $package['duration_minutes']
            ),
            'reference' => $reference,
            'callback_url' => $baseUrl . '/api/shop/payment_callback.php?reference=' . urlencode($reference),
            'return_url' => 'http://localhost:4000/purchases/success',
            'cancel_url' => 'http://localhost:4000/purchases/cancel',
            'metadata' => [
                'purchase_id' => $purchaseId,
                'user_id' => $user['id'],
                'game_name' => $game['name'],
                'package_name' => $package['name']
            ]
        ];
    }
    
    $pdo->commit();
    
    // Récupérer l'achat complet pour la réponse
    $stmt = $pdo->prepare('
        SELECT p.*, 
               g.name as game_name,
               g.image_url as game_image,
               pkg.name as package_name,
               pkg.duration_minutes,
               pm.name as payment_method_name
        FROM purchases p
        INNER JOIN games g ON p.game_id = g.id
        INNER JOIN game_packages pkg ON p.package_id = pkg.id
        INNER JOIN payment_methods pm ON p.payment_method_id = pm.id
        WHERE p.id = ?
    ');
    $stmt->execute([$purchaseId]);
    $purchase = $stmt->fetch();
    
    json_response([
        'success' => true,
        'message' => 'Achat créé avec succès',
        'purchase' => $purchase,
        'reservation' => $isReservation ? [
            'scheduled_start' => $scheduledStart->format('Y-m-d H:i:s'),
            'scheduled_end' => $scheduledEnd->format('Y-m-d H:i:s'),
            'reservation_fee' => (float)($game['reservation_fee'] ?? 0.00)
        ] : null,
        'payment_details' => [
            'base_amount' => $baseAmount,
            'fee_amount' => $feeAmount,
            'total_amount' => $totalAmount,
            'currency' => 'XOF'
        ],
        'points_info' => [
            'base_points' => $basePoints,
            'bonus_multiplier' => $bonusMultiplier,
            'total_points' => $totalPoints
        ],
        'payment_data' => $paymentData,
        'next_step' => $paymentMethod['requires_online_payment'] 
            ? 'complete_payment' 
            : 'wait_for_admin_confirmation'
    ], 201);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response([
        'error' => 'Erreur lors de la création de l\'achat',
        'details' => $e->getMessage()
    ], 500);
}
