<?php
// api/admin/conversion_config.php
// Gestion de la configuration du système de conversion points

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin'); // Admin uniquement
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer la configuration + statistiques
// ============================================================================
if ($method === 'GET') {
    try {
        // Configuration
        $stmt = $pdo->query('SELECT * FROM point_conversion_config WHERE id = 1');
        $config = $stmt->fetch();
        
        if (!$config) {
            json_response(['error' => 'Configuration non trouvée'], 500);
        }
        
        // Statistiques globales
        $stmt = $pdo->query('
            SELECT 
                COUNT(*) as total_conversions,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(points_spent) as total_points_converted,
                SUM(minutes_gained) as total_minutes_generated,
                SUM(minutes_used) as total_minutes_used,
                SUM(CASE WHEN status = "active" AND expires_at > NOW() THEN minutes_gained - minutes_used ELSE 0 END) as total_minutes_available,
                SUM(CASE WHEN status = "expired" THEN minutes_gained - minutes_used ELSE 0 END) as total_minutes_wasted
            FROM point_conversions
        ');
        $stats = $stmt->fetch();
        
        // Conversions ce mois
        $stmt = $pdo->query('
            SELECT 
                COUNT(*) as conversions_this_month,
                SUM(points_spent) as points_this_month
            FROM point_conversions
            WHERE YEAR(created_at) = YEAR(NOW())
              AND MONTH(created_at) = MONTH(NOW())
        ');
        $thisMonth = $stmt->fetch();
        
        // Conversions aujourd'hui
        $stmt = $pdo->query('
            SELECT 
                COUNT(*) as conversions_today,
                SUM(points_spent) as points_today
            FROM point_conversions
            WHERE DATE(created_at) = CURDATE()
        ');
        $today = $stmt->fetch();
        
        // Top convertisseurs
        $stmt = $pdo->query('
            SELECT 
                u.id,
                u.username,
                u.email,
                u.avatar_url,
                COUNT(c.id) as conversion_count,
                SUM(c.points_spent) as total_points_spent,
                SUM(c.minutes_gained) as total_minutes_gained
            FROM point_conversions c
            JOIN users u ON c.user_id = u.id
            GROUP BY u.id
            ORDER BY total_points_spent DESC
            LIMIT 10
        ');
        $topConverters = $stmt->fetchAll();
        
        json_response([
            'config' => $config,
            'stats' => [
                'total_conversions' => (int)$stats['total_conversions'],
                'unique_users' => (int)$stats['unique_users'],
                'total_points_converted' => (int)$stats['total_points_converted'],
                'total_minutes_generated' => (int)$stats['total_minutes_generated'],
                'total_minutes_used' => (int)$stats['total_minutes_used'],
                'total_minutes_available' => (int)$stats['total_minutes_available'],
                'total_minutes_wasted' => (int)$stats['total_minutes_wasted'],
                'utilization_rate' => $stats['total_minutes_generated'] > 0 
                    ? round(($stats['total_minutes_used'] / $stats['total_minutes_generated']) * 100, 2)
                    : 0
            ],
            'this_month' => [
                'conversions' => (int)$thisMonth['conversions_this_month'],
                'points' => (int)$thisMonth['points_this_month']
            ],
            'today' => [
                'conversions' => (int)$today['conversions_today'],
                'points' => (int)$today['points_today']
            ],
            'top_converters' => $topConverters
        ]);
        
    } catch (PDOException $e) {
        log_error('Erreur chargement config conversion', $e);
        json_response(['error' => 'Erreur lors du chargement'], 500);
    }
}

// ============================================================================
// PUT: Mettre à jour la configuration
// ============================================================================
if ($method === 'PUT' || $method === 'PATCH') {
    $data = get_json_input();
    
    try {
        $fields = [];
        $values = [];
        
        // Champs modifiables
        if (isset($data['points_per_minute'])) {
            $fields[] = 'points_per_minute = ?';
            $values[] = (int)$data['points_per_minute'];
        }
        
        if (isset($data['min_conversion_points'])) {
            $fields[] = 'min_conversion_points = ?';
            $values[] = (int)$data['min_conversion_points'];
        }
        
        if (isset($data['max_conversion_per_day'])) {
            $fields[] = 'max_conversion_per_day = ?';
            $values[] = $data['max_conversion_per_day'] ? (int)$data['max_conversion_per_day'] : null;
        }
        
        if (isset($data['conversion_fee_percent'])) {
            $fields[] = 'conversion_fee_percent = ?';
            $values[] = (float)$data['conversion_fee_percent'];
        }
        
        if (isset($data['min_minutes_per_conversion'])) {
            $fields[] = 'min_minutes_per_conversion = ?';
            $values[] = (int)$data['min_minutes_per_conversion'];
        }
        
        if (isset($data['max_minutes_per_conversion'])) {
            $fields[] = 'max_minutes_per_conversion = ?';
            $values[] = $data['max_minutes_per_conversion'] ? (int)$data['max_minutes_per_conversion'] : null;
        }
        
        if (isset($data['converted_time_expiry_days'])) {
            $fields[] = 'converted_time_expiry_days = ?';
            $values[] = (int)$data['converted_time_expiry_days'];
        }
        
        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $values[] = $data['is_active'] ? 1 : 0;
        }
        
        if (isset($data['notes'])) {
            $fields[] = 'notes = ?';
            $values[] = $data['notes'];
        }
        
        if (empty($fields)) {
            json_response(['error' => 'Aucun champ à mettre à jour'], 400);
        }
        
        // Ajouter updated_at et updated_by
        $fields[] = 'updated_at = ?';
        $values[] = now();
        $fields[] = 'updated_by = ?';
        $values[] = $user['id'];
        
        // Construire et exécuter la requête
        $sql = 'UPDATE point_conversion_config SET ' . implode(', ', $fields) . ' WHERE id = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        // Récupérer la config mise à jour
        $stmt = $pdo->query('SELECT * FROM point_conversion_config WHERE id = 1');
        $config = $stmt->fetch();
        
        json_response([
            'success' => true,
            'message' => 'Configuration mise à jour',
            'config' => $config
        ]);
        
    } catch (PDOException $e) {
        log_error('Erreur mise à jour config conversion', $e);
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// POST: Réinitialiser la configuration par défaut
// ============================================================================
if ($method === 'POST' && isset($_GET['reset'])) {
    try {
        $stmt = $pdo->prepare('
            UPDATE point_conversion_config
            SET points_per_minute = 10,
                min_conversion_points = 100,
                max_conversion_per_day = 3,
                conversion_fee_percent = 0.00,
                min_minutes_per_conversion = 10,
                max_minutes_per_conversion = 300,
                converted_time_expiry_days = 30,
                is_active = 1,
                notes = "Configuration par défaut: 10 points = 1 minute",
                updated_at = ?,
                updated_by = ?
            WHERE id = 1
        ');
        $stmt->execute([now(), $user['id']]);
        
        // Récupérer la config réinitialisée
        $stmt = $pdo->query('SELECT * FROM point_conversion_config WHERE id = 1');
        $config = $stmt->fetch();
        
        json_response([
            'success' => true,
            'message' => 'Configuration réinitialisée aux valeurs par défaut',
            'config' => $config
        ]);
        
    } catch (PDOException $e) {
        log_error('Erreur réinitialisation config', $e);
        json_response(['error' => 'Erreur lors de la réinitialisation'], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
