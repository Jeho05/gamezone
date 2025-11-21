<?php
// api/rewards/index.php
// List and manage rewards catalog
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // List rewards
        $user = require_auth();
        $isAdmin = is_admin($user);

        // Vérifier que les tables critiques existent
        $hasRewards = $pdo->query("SHOW TABLES LIKE 'rewards'");
        $hasRedemptions = $pdo->query("SHOW TABLES LIKE 'reward_redemptions'");

        $hasRewardsTable = $hasRewards && $hasRewards->rowCount() > 0;
        $hasRedemptionsTable = $hasRedemptions && $hasRedemptions->rowCount() > 0;

        if (!$hasRewardsTable || !$hasRedemptionsTable) {
            log_error('Tables de récompenses manquantes pour /rewards/index.php', [
                'has_rewards' => $hasRewardsTable,
                'has_reward_redemptions' => $hasRedemptionsTable,
            ]);

            json_response([
                'success' => false,
                'error' => "Les récompenses ne sont pas encore configurées sur ce serveur",
                'code' => 'REWARDS_SCHEMA_MISSING',
                'rewards' => [],
                'items' => [],
                'count' => 0,
                'user_points' => (int)($user['points'] ?? 0),
            ], 200);
        }

        // Only show available rewards to regular users, unless admin requests all
        $available = isset($_GET['available']) ? (int)$_GET['available'] : 1;
        $category = $_GET['category'] ?? null;

        $sql = 'SELECT r.*,';
        $sql .= ' (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.id) as total_redemptions';
        $sql .= ' FROM rewards r WHERE 1=1';

        $params = [];

        // Si available=0 et utilisateur admin, afficher tout
        // Si available=1, afficher seulement les disponibles
        if ($available === 1) {
            $sql .= ' AND r.available = 1';
        }
        // Si available=0 et non admin, forcer available=1 pour sécurité
        if ($available === 0 && !$isAdmin) {
            $sql .= ' AND r.available = 1';
        }

        if ($category) {
            $sql .= ' AND r.category = ?';
            $params[] = $category;
        }

        // Vérifier le stock disponible
        $sql .= ' AND (r.stock_quantity IS NULL OR r.stock_quantity > (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.id AND status != "cancelled"))';

        $sql .= ' ORDER BY r.is_featured DESC, r.display_order ASC, r.cost ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrichir avec des informations sur la disponibilité pour l'utilisateur
        foreach ($items as &$item) {
            if ($item['max_per_user']) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = ? AND user_id = ?');
                $stmt->execute([$item['id'], $user['id']]);
                $userRedemptions = (int)$stmt->fetchColumn();
                $item['user_redemptions'] = $userRedemptions;
                $item['can_redeem'] = ($userRedemptions < $item['max_per_user']);
            } else {
                $item['user_redemptions'] = 0;
                $item['can_redeem'] = true;
            }

            // Calculer le stock restant
            if ($item['stock_quantity']) {
                $item['stock_remaining'] = $item['stock_quantity'] - $item['total_redemptions'];
            } else {
                $item['stock_remaining'] = null; // illimité
            }
        }

        json_response([
            'success' => true,
            'rewards' => $items,
            'items' => $items, // Alias pour compatibilité frontend admin
            'count' => count($items),
            'user_points' => (int)($user['points'] ?? 0)
        ]);
    } catch (Throwable $e) {
        log_error('Erreur GET /rewards/index.php', [
            'error' => $e->getMessage(),
        ]);

        json_response([
            'success' => false,
            'error' => 'Erreur lors du chargement des récompenses',
            'details' => $e->getMessage(),
            'rewards' => [],
            'items' => [],
            'count' => 0,
        ], 500);
    }
}

if ($method === 'POST') {
    // Create or update reward (admin only)
    $admin = require_auth('admin');
    $input = get_json_input();
    
    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $cost = (int)($input['cost'] ?? 0);
    $category = trim($input['category'] ?? '');
    $available = isset($input['available']) ? (int)$input['available'] : 1;
    $reward_type = trim($input['reward_type'] ?? 'other');
    $game_time_minutes = (int)($input['game_time_minutes'] ?? 0);
    
    if ($name === '' || $cost < 0) {
        json_response(['error' => 'Paramètres invalides'], 400);
    }
    
    $now = now();
    if ($id > 0) {
        // Update existing reward
        $stmt = $pdo->prepare('UPDATE rewards SET name = ?, description = ?, cost = ?, category = ?, available = ?, reward_type = ?, game_time_minutes = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$name, $description ?: null, $cost, $category ?: null, $available, $reward_type, $game_time_minutes, $now, $id]);
        json_response(['success' => true, 'message' => 'Récompense mise à jour', 'id' => $id]);
    } else {
        // Create new reward
        $stmt = $pdo->prepare('INSERT INTO rewards (name, description, cost, category, available, reward_type, game_time_minutes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $description ?: null, $cost, $category ?: null, $available, $reward_type, $game_time_minutes, $now, $now]);
        $newId = (int)$pdo->lastInsertId();
        json_response(['success' => true, 'message' => 'Récompense créée', 'id' => $newId], 201);
    }
}

if ($method === 'DELETE') {
    // Delete reward (admin only)
    $admin = require_auth('admin');
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        json_response(['error' => 'ID manquant'], 400);
    }
    
    $stmt = $pdo->prepare('DELETE FROM rewards WHERE id = ?');
    $stmt->execute([$id]);
    
    json_response(['message' => 'Récompense supprimée']);
}
