<?php
// api/rewards/index_fixed.php
// Version corrigée avec gestion d'erreurs améliorée
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // List rewards
        $user = require_auth();
        
        // Vérifier si la table rewards existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'rewards'");
        if ($stmt->rowCount() == 0) {
            json_response([
                'success' => false,
                'error' => 'La table rewards n\'existe pas',
                'message' => 'Veuillez créer la table rewards dans votre base de données',
                'rewards' => [],
                'count' => 0,
                'user_points' => (int)$user['points']
            ]);
            exit;
        }
        
        // Vérifier les colonnes de la table
        $stmt = $pdo->query("DESCRIBE rewards");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Construire la requête avec seulement les colonnes qui existent
        $selectFields = ['r.id', 'r.name', 'r.cost'];
        
        $optionalFields = [
            'description', 'category', 'icon', 'available', 'is_featured',
            'display_order', 'stock_quantity', 'max_per_user', 'created_at', 'updated_at'
        ];
        
        foreach ($optionalFields as $field) {
            if (in_array($field, $columns)) {
                $selectFields[] = "r.$field";
            }
        }
        
        $sql = 'SELECT ' . implode(', ', $selectFields);
        
        // Vérifier si la table reward_redemptions existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'reward_redemptions'");
        if ($stmt->rowCount() > 0) {
            $sql .= ', (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.id) as total_redemptions';
        } else {
            $sql .= ', 0 as total_redemptions';
        }
        
        $sql .= ' FROM rewards r WHERE 1=1';
        
        $params = [];
        
        // Filtres optionnels
        if (in_array('available', $columns)) {
            $available = isset($_GET['available']) ? (int)$_GET['available'] : 1;
            if ($available) {
                $sql .= ' AND r.available = 1';
            }
        }
        
        if (in_array('category', $columns) && isset($_GET['category'])) {
            $sql .= ' AND r.category = ?';
            $params[] = $_GET['category'];
        }
        
        // Vérifier le stock (seulement si reward_redemptions existe)
        $stmt = $pdo->query("SHOW TABLES LIKE 'reward_redemptions'");
        if (in_array('stock_quantity', $columns) && $stmt->rowCount() > 0) {
            $sql .= ' AND (r.stock_quantity IS NULL OR r.stock_quantity > (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.id AND status != "cancelled"))';
        }
        
        // Ordre
        $orderBy = [];
        if (in_array('is_featured', $columns)) {
            $orderBy[] = 'r.is_featured DESC';
        }
        if (in_array('display_order', $columns)) {
            $orderBy[] = 'r.display_order ASC';
        }
        $orderBy[] = 'r.cost ASC';
        
        $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enrichir les données
        foreach ($items as &$item) {
            // S'assurer que available existe
            if (!isset($item['available'])) {
                $item['available'] = 1;
            }
            
            // Limite par utilisateur (seulement si reward_redemptions existe)
            $stmt = $pdo->query("SHOW TABLES LIKE 'reward_redemptions'");
            if (isset($item['max_per_user']) && $item['max_per_user'] && $stmt->rowCount() > 0) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = ? AND user_id = ?');
                $stmt->execute([$item['id'], $user['id']]);
                $userRedemptions = (int)$stmt->fetchColumn();
                $item['user_redemptions'] = $userRedemptions;
                $item['can_redeem'] = ($userRedemptions < $item['max_per_user']);
            } else {
                $item['user_redemptions'] = 0;
                $item['can_redeem'] = true;
            }
            
            // Stock restant
            if (isset($item['stock_quantity']) && $item['stock_quantity']) {
                $item['stock_remaining'] = $item['stock_quantity'] - $item['total_redemptions'];
            } else {
                $item['stock_remaining'] = null;
            }
        }
        
        json_response([
            'success' => true,
            'rewards' => $items,
            'count' => count($items),
            'user_points' => (int)$user['points']
        ]);
        exit;
    }
    
    if ($method === 'POST') {
        // Create or update reward (admin only)
        $admin = require_auth('admin');
        $input = get_json_input();
        
        $id = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $cost = (int)($input['cost'] ?? 0);
        $available = isset($input['available']) ? (int)$input['available'] : 1;
        
        if ($name === '' || $cost < 0) {
            json_response(['error' => 'Paramètres invalides'], 400);
        }
        
        $now = now();
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE rewards SET name = ?, cost = ?, available = ?, updated_at = ? WHERE id = ?');
            $stmt->execute([$name, $cost, $available, $now, $id]);
            json_response(['success' => true, 'message' => 'Récompense mise à jour', 'id' => $id]);
        exit;
        } else {
            $stmt = $pdo->prepare('INSERT INTO rewards (name, cost, available, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $cost, $available, $now, $now]);
            $newId = (int)$pdo->lastInsertId();
            json_response(['success' => true, 'message' => 'Récompense créée', 'id' => $newId], 201);
            exit;
        }
    }
    
    if ($method === 'DELETE') {
        $admin = require_auth('admin');
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            json_response(['error' => 'ID manquant'], 400);
        }
        
        $stmt = $pdo->prepare('DELETE FROM rewards WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response(['success' => true, 'message' => 'Récompense supprimée']);
        exit;
    }
    
    // Méthode non supportée
    json_response(['error' => 'Méthode HTTP non supportée'], 405);
    exit;
    
} catch (PDOException $e) {
    json_response([
        'success' => false,
        'error' => 'Erreur de base de données',
        'details' => $e->getMessage(),
        'rewards' => [],
        'count' => 0
    ], 500);
    exit;
} catch (Exception $e) {
    json_response([
        'success' => false,
        'error' => 'Erreur serveur',
        'details' => $e->getMessage(),
        'rewards' => [],
        'count' => 0
    ], 500);
    exit;
}
