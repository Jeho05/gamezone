<?php
// api/admin/users.php
// Admin user management endpoint

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_check.php';

$admin = require_admin();
$db = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// GET - List users or get one user
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Get single user with full details
        $stmt = $db->prepare("
            SELECT u.*,
                   (SELECT COUNT(*) FROM points_transactions WHERE user_id = u.id) as total_transactions,
                   (SELECT COALESCE(SUM(change_amount), 0) FROM points_transactions WHERE user_id = u.id AND change_amount > 0) as points_earned,
                   (SELECT COALESCE(SUM(ABS(change_amount)), 0) FROM points_transactions WHERE user_id = u.id AND change_amount < 0) as points_spent
            FROM users u
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            json_response(['error' => 'Utilisateur non trouvé'], 404);
        }
        
        // Get recent transactions
        $stmt = $db->prepare("
            SELECT * FROM points_transactions
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$id]);
        $user['recent_transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count sanctions from points_transactions
        $stmt = $db->prepare("
            SELECT * FROM points_transactions
            WHERE user_id = ? 
            AND type = 'adjustment' 
            AND change_amount < 0 
            AND reason LIKE '%SANCTION%'
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$id]);
        $user['sanctions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $user['active_sanctions'] = count($user['sanctions']);
        
        json_response($user);
    } else {
        // List all users with filters
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? null;
        $role = $_GET['role'] ?? null;
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = "
            SELECT u.*,
                   (SELECT COUNT(*) FROM points_transactions pt 
                    WHERE pt.user_id = u.id 
                    AND pt.type = 'adjustment' 
                    AND pt.change_amount < 0 
                    AND pt.reason LIKE '%SANCTION%'
                    AND pt.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as active_sanctions
            FROM users u
            WHERE 1=1
        ";
        $params = [];
        
        if ($search) {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($status) {
            $sql .= " AND u.status = ?";
            $params[] = $status;
        }
        
        if ($role) {
            $sql .= " AND u.role = ?";
            $params[] = $role;
        }
        
        $sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
        $countParams = [];
        
        if ($search) {
            $countSql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $searchParam = "%{$search}%";
            $countParams[] = $searchParam;
            $countParams[] = $searchParam;
        }
        
        if ($status) {
            $countSql .= " AND u.status = ?";
            $countParams[] = $status;
        }
        
        if ($role) {
            $countSql .= " AND u.role = ?";
            $countParams[] = $role;
        }
        
        $stmt = $db->prepare($countSql);
        $stmt->execute($countParams);
        $total = $stmt->fetch()['total'];
        
        json_response([
            'users' => $users,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
}

// PUT - Update user
if ($method === 'PUT' || $method === 'PATCH') {
    $input = get_json_input();
    $id = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis pour la mise à jour'], 400);
    }
    
    try {
        // Build update query dynamically
        $updates = [];
        $params = [];
        
        $allowed = ['username', 'email', 'role', 'status', 'points', 'level'];
        
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updates)) {
            json_response(['error' => 'Aucun champ à mettre à jour'], 400);
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        json_response([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès'
        ]);
        
    } catch (Exception $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// DELETE - Delete user
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis pour la suppression'], 400);
    }
    
    // Don't allow deleting yourself
    if ((int)$id === (int)$admin['id']) {
        json_response(['error' => 'Vous ne pouvez pas supprimer votre propre compte'], 400);
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            json_response(['error' => 'Utilisateur non trouvé'], 404);
        }
        
        json_response([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
        
    } catch (Exception $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}
