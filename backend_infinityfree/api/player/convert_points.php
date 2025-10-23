<?php
// api/player/convert_points.php
// Conversion de points en temps de jeu pour les joueurs

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth(); // Doit être connecté
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer l'historique des conversions et la config
// ============================================================================
if ($method === 'GET') {
    try {
        // Charger la configuration
        $stmt = $pdo->query('
            SELECT points_per_minute, min_conversion_points, max_conversion_per_day,
                   conversion_fee_percent, min_minutes_per_conversion, max_minutes_per_conversion,
                   converted_time_expiry_days, is_active
            FROM point_conversion_config
            WHERE id = 1
        ');
        $config = $stmt->fetch();
        
        if (!$config) {
            json_response(['error' => 'Configuration non trouvée'], 500);
        }
        
        // Charger les conversions de l'utilisateur
        $stmt = $pdo->prepare('
            SELECT 
                c.*,
                g.name as game_name,
                g.slug as game_slug,
                g.image_url as game_image
            FROM point_conversions c
            LEFT JOIN games g ON c.game_id = g.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
            LIMIT 50
        ');
        $stmt->execute([$user['id']]);
        $conversions = $stmt->fetchAll();
        
        // Calculer les statistiques
        $stmt = $pdo->prepare('
            SELECT 
                COUNT(*) as total_conversions,
                SUM(points_spent) as total_points_spent,
                SUM(minutes_gained) as total_minutes_gained,
                SUM(minutes_used) as total_minutes_used,
                SUM(CASE WHEN status = "active" AND expires_at > NOW() THEN minutes_gained - minutes_used ELSE 0 END) as minutes_available
            FROM point_conversions
            WHERE user_id = ?
        ');
        $stmt->execute([$user['id']]);
        $stats = $stmt->fetch();
        
        // Vérifier combien de conversions aujourd'hui
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as conversions_today
            FROM point_conversions
            WHERE user_id = ?
              AND DATE(created_at) = CURDATE()
        ');
        $stmt->execute([$user['id']]);
        $today = $stmt->fetch();
        
        json_response([
            'config' => [
                'points_per_minute' => (int)$config['points_per_minute'],
                'min_conversion_points' => (int)$config['min_conversion_points'],
                'max_conversion_per_day' => $config['max_conversion_per_day'] ? (int)$config['max_conversion_per_day'] : null,
                'conversion_fee_percent' => (float)$config['conversion_fee_percent'],
                'min_minutes' => (int)$config['min_minutes_per_conversion'],
                'max_minutes' => $config['max_minutes_per_conversion'] ? (int)$config['max_minutes_per_conversion'] : null,
                'expiry_days' => (int)$config['converted_time_expiry_days'],
                'is_active' => (bool)$config['is_active']
            ],
            'conversions' => $conversions,
            'stats' => [
                'total_conversions' => (int)$stats['total_conversions'],
                'total_points_spent' => (int)$stats['total_points_spent'],
                'total_minutes_gained' => (int)$stats['total_minutes_gained'],
                'total_minutes_used' => (int)$stats['total_minutes_used'],
                'minutes_available' => (int)$stats['minutes_available']
            ],
            'today' => [
                'conversions_count' => (int)$today['conversions_today'],
                'remaining_today' => $config['max_conversion_per_day'] 
                    ? max(0, (int)$config['max_conversion_per_day'] - (int)$today['conversions_today'])
                    : null
            ],
            'user_points' => (int)$user['points']
        ]);
        
    } catch (PDOException $e) {
        log_error('Erreur chargement conversions', $e);
        json_response(['error' => 'Erreur lors du chargement'], 500);
    }
}

// ============================================================================
// POST: Créer une nouvelle conversion
// ============================================================================
if ($method === 'POST') {
    $data = get_json_input();
    
    // Validation
    if (!isset($data['points_to_convert']) || $data['points_to_convert'] <= 0) {
        json_response(['error' => 'Nombre de points invalide'], 400);
    }
    
    $pointsToConvert = (int)$data['points_to_convert'];
    $gameId = isset($data['game_id']) ? (int)$data['game_id'] : null;
    
    // Vérifier que le jeu existe si spécifié
    if ($gameId) {
        $stmt = $pdo->prepare('SELECT id, name FROM games WHERE id = ? AND is_active = 1');
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();
        
        if (!$game) {
            json_response(['error' => 'Jeu introuvable ou inactif'], 404);
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Charger la configuration
        $stmt = $pdo->query('SELECT * FROM point_conversion_config WHERE id = 1');
        $config = $stmt->fetch();
        
        if (!$config || !$config['is_active']) {
            $pdo->rollBack();
            json_response(['error' => 'Le système de conversion est désactivé'], 400);
        }
        
        // Vérifier le solde de l'utilisateur
        $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$user['id']]);
        $userPoints = (int)$stmt->fetchColumn();
        
        if ($userPoints < $pointsToConvert) {
            $pdo->rollBack();
            json_response(['error' => "Points insuffisants. Disponible: {$userPoints}, Requis: {$pointsToConvert}"], 400);
        }
        
        // Vérifier minimum de points
        if ($pointsToConvert < $config['min_conversion_points']) {
            $pdo->rollBack();
            json_response(['error' => "Minimum {$config['min_conversion_points']} points requis"], 400);
        }
        
        // Vérifier limite quotidienne
        if ($config['max_conversion_per_day']) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM point_conversions WHERE user_id = ? AND DATE(created_at) = CURDATE()');
            $stmt->execute([$user['id']]);
            $todayCount = (int)$stmt->fetchColumn();
            
            if ($todayCount >= $config['max_conversion_per_day']) {
                $pdo->rollBack();
                json_response(['error' => "Limite quotidienne atteinte ({$config['max_conversion_per_day']} conversions/jour)"], 400);
            }
        }
        
        // Calculer les minutes
        $minutesGained = floor($pointsToConvert / $config['points_per_minute']);
        
        // Vérifier minimum de minutes
        if ($minutesGained < $config['min_minutes_per_conversion']) {
            $pdo->rollBack();
            json_response(['error' => "Minimum {$config['min_minutes_per_conversion']} minutes requis. Vous obtiendriez {$minutesGained} minutes."], 400);
        }
        
        // Vérifier maximum de minutes
        if ($config['max_minutes_per_conversion'] && $minutesGained > $config['max_minutes_per_conversion']) {
            $pdo->rollBack();
            json_response(['error' => "Maximum {$config['max_minutes_per_conversion']} minutes par conversion"], 400);
        }
        
        // Calculer les frais
        $feeCharged = ($pointsToConvert * $config['conversion_fee_percent'] / 100);
        
        // Calculer l'expiration
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$config['converted_time_expiry_days']} days"));
        
        // Débiter les points
        $stmt = $pdo->prepare('UPDATE users SET points = points - ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$pointsToConvert, $user['id']]);
        
        // Créer la conversion
        $stmt = $pdo->prepare('
            INSERT INTO point_conversions (
                user_id, points_spent, minutes_gained, game_id,
                conversion_rate, fee_charged, status,
                created_at, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ');
        $stmt->execute([
            $user['id'],
            $pointsToConvert,
            $minutesGained,
            $gameId,
            $config['points_per_minute'],
            $feeCharged,
            'active',
            $expiresAt
        ]);
        
        $conversionId = $pdo->lastInsertId();
        
        // Logger la transaction de points
        $stmt = $pdo->prepare('
            INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            $user['id'],
            -$pointsToConvert,
            "Conversion en {$minutesGained} minutes de jeu",
            'conversion'
        ]);
        
        // Mettre à jour les stats utilisateur
        $stmt = $pdo->prepare('
            INSERT INTO user_stats (user_id, total_points_spent, updated_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                total_points_spent = total_points_spent + ?,
                updated_at = NOW()
        ');
        $stmt->execute([$user['id'], $pointsToConvert, $pointsToConvert]);
        
        $pdo->commit();
        
        // Créer un résultat compatible
        $result = [
            'conversion_id' => $conversionId,
            'minutes_gained' => $minutesGained,
            'error' => null
        ];
        
        // Récupérer les détails de la conversion créée
        $stmt = $pdo->prepare('
            SELECT c.*, g.name as game_name
            FROM point_conversions c
            LEFT JOIN games g ON c.game_id = g.id
            WHERE c.id = ?
        ');
        $stmt->execute([$result['conversion_id']]);
        $conversion = $stmt->fetch();
        
        // Récupérer le nouveau solde de points
        $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $newBalance = $stmt->fetchColumn();
        
        json_response([
            'success' => true,
            'message' => sprintf(
                '%d points convertis en %d minutes de jeu!',
                $pointsToConvert,
                $result['minutes_gained']
            ),
            'conversion' => [
                'id' => (int)$result['conversion_id'],
                'points_spent' => (int)$conversion['points_spent'],
                'minutes_gained' => (int)$result['minutes_gained'],
                'game_name' => $conversion['game_name'],
                'expires_at' => $conversion['expires_at'],
                'status' => $conversion['status']
            ],
            'new_balance' => (int)$newBalance,
            'minutes_available' => (int)get_user_converted_minutes($pdo, $user['id'])
        ], 201);
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        log_error('Erreur conversion points', $e);
        json_response(['error' => 'Erreur lors de la conversion', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// DELETE: Annuler une conversion (si pas encore utilisée)
// ============================================================================
if ($method === 'DELETE') {
    $conversionId = $_GET['id'] ?? null;
    
    if (!$conversionId) {
        json_response(['error' => 'ID conversion requis'], 400);
    }
    
    try {
        $pdo->beginTransaction();
        
        // Vérifier que la conversion existe et appartient à l'utilisateur
        $stmt = $pdo->prepare('
            SELECT id, user_id, points_spent, minutes_gained, minutes_used, status
            FROM point_conversions
            WHERE id = ?
              AND user_id = ?
              FOR UPDATE
        ');
        $stmt->execute([$conversionId, $user['id']]);
        $conversion = $stmt->fetch();
        
        if (!$conversion) {
            $pdo->rollBack();
            json_response(['error' => 'Conversion introuvable'], 404);
        }
        
        // Vérifier que la conversion n'a pas été utilisée
        if ($conversion['minutes_used'] > 0) {
            $pdo->rollBack();
            json_response(['error' => 'Impossible d\'annuler: temps déjà utilisé'], 400);
        }
        
        // Vérifier que la conversion est active
        if ($conversion['status'] !== 'active') {
            $pdo->rollBack();
            json_response(['error' => 'Conversion déjà annulée ou expirée'], 400);
        }
        
        // Rembourser les points
        $stmt = $pdo->prepare('
            UPDATE users
            SET points = points + ?,
                updated_at = ?
            WHERE id = ?
        ');
        $stmt->execute([$conversion['points_spent'], now(), $user['id']]);
        
        // Marquer la conversion comme annulée
        $stmt = $pdo->prepare('
            UPDATE point_conversions
            SET status = "cancelled"
            WHERE id = ?
        ');
        $stmt->execute([$conversionId]);
        
        // Logger la transaction
        $stmt = $pdo->prepare('
            INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $user['id'],
            $conversion['points_spent'],
            'Remboursement conversion annulée',
            'refund',
            now()
        ]);
        
        $pdo->commit();
        
        // Récupérer le nouveau solde
        $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $newBalance = $stmt->fetchColumn();
        
        json_response([
            'success' => true,
            'message' => sprintf('%d points remboursés', $conversion['points_spent']),
            'new_balance' => (int)$newBalance
        ]);
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        log_error('Erreur annulation conversion', $e);
        json_response(['error' => 'Erreur lors de l\'annulation'], 500);
    }
}

// ============================================================================
// Fonction helper: Calculer minutes disponibles
// ============================================================================
function get_user_converted_minutes($pdo, $userId) {
    $stmt = $pdo->prepare('SELECT get_user_converted_minutes(?) as minutes');
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

json_response(['error' => 'Méthode non autorisée'], 405);
