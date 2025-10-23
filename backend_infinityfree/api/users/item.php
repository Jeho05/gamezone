<?php
require_once __DIR__ . '/../utils.php';
require_method(['GET', 'PUT', 'DELETE']);
$pdo = get_db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    json_response(['error' => 'Paramètre id manquant'], 400);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $auth = current_user();
    if (!$auth) json_response(['error' => 'Unauthorized'], 401);
    if (($auth['role'] ?? 'player') !== 'admin' && (int)$auth['id'] !== $id) {
        json_response(['error' => 'Forbidden'], 403);
    }
    $stmt = $pdo->prepare('SELECT id, username, email, role, avatar_url, points, level, status, join_date, last_active FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) json_response(['error' => 'Utilisateur introuvable'], 404);
    json_response(['user' => $user]);
}

if ($method === 'PUT') {
    $admin = require_auth('admin');
    $input = get_json_input();
    
    // Prevent admin from modifying their own account (security)
    if ((int)$admin['id'] === $id) {
        json_response(['error' => 'Vous ne pouvez pas modifier votre propre compte'], 403);
    }
    
    $fields = [];
    $params = [];

    if (isset($input['username'])) { $fields[] = 'username = ?'; $params[] = trim($input['username']); }
    if (isset($input['email'])) {
        $email = trim($input['email']);
        if (!validate_email($email)) json_response(['error' => 'Email invalide'], 400);
        // Ensure uniqueness
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $check->execute([$email, $id]);
        if ($check->fetch()) json_response(['error' => 'Email déjà utilisé'], 409);
        $fields[] = 'email = ?'; $params[] = $email;
    }
    if (isset($input['role'])) { $fields[] = 'role = ?'; $params[] = ($input['role'] === 'admin' ? 'admin' : 'player'); }
    
    // Handle status change - if deactivating, reset points to 0
    if (isset($input['status'])) { 
        $newStatus = ($input['status'] === 'inactive' ? 'inactive' : 'active');
        $fields[] = 'status = ?'; 
        $params[] = $newStatus;
        
        // Check if deactivation columns exist
        $checkColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'deactivation_reason'");
        $hasDeactivationColumns = $checkColumns->rowCount() > 0;
        
        // If deactivating user, reset points to 0 as a sanction
        if ($newStatus === 'inactive') {
            // Get deactivation reason from input
            $deactivationReason = trim($input['deactivation_reason'] ?? '');
            
            // Require deactivation reason only if columns exist
            if ($hasDeactivationColumns) {
                if (empty($deactivationReason)) {
                    json_response(['error' => 'Le motif de désactivation est obligatoire'], 400);
                }
            }
            
            $fields[] = 'points = ?';
            $params[] = 0;
            
            // Store deactivation reason and date only if columns exist
            if ($hasDeactivationColumns) {
                $fields[] = 'deactivation_reason = ?';
                $params[] = $deactivationReason;
                
                $fields[] = 'deactivation_date = ?';
                $params[] = now();
                
                $fields[] = 'deactivated_by = ?';
                $params[] = $admin['id'];
            }
            
            // Log the points reset in history
            $resetStmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?)');
            $currentPoints = $pdo->prepare('SELECT points FROM users WHERE id = ?');
            $currentPoints->execute([$id]);
            $pts = $currentPoints->fetchColumn();
            if ($pts > 0) {
                $reason = !empty($deactivationReason)
                    ? 'Compte désactivé - ' . $deactivationReason 
                    : 'Compte désactivé - Sanction administrative';
                $resetStmt->execute([$id, -$pts, $reason, 'adjustment', $admin['id'], now()]);
            }
        } else {
            // If reactivating, clear deactivation fields only if columns exist
            if ($hasDeactivationColumns) {
                $fields[] = 'deactivation_reason = ?';
                $params[] = null;
                
                $fields[] = 'deactivation_date = ?';
                $params[] = null;
                
                $fields[] = 'deactivated_by = ?';
                $params[] = null;
            }
        }
    }
    
    if (isset($input['level'])) { $fields[] = 'level = ?'; $params[] = $input['level'] === null ? null : (string)$input['level']; }
    if (isset($input['avatar_url'])) { $fields[] = 'avatar_url = ?'; $params[] = $input['avatar_url'] === null ? null : (string)$input['avatar_url']; }

    if (empty($fields)) {
        json_response(['error' => 'Aucun champ à mettre à jour'], 400);
    }
    $fields[] = 'updated_at = ?';
    $params[] = now();
    $params[] = $id;
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $stmt = $pdo->prepare('SELECT id, username, email, role, avatar_url, points, level, status, join_date, last_active FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    json_response(['message' => 'Utilisateur mis à jour', 'user' => $user]);
}

// DELETE
$admin = require_auth('admin');

// Try to get input for deletion reason
try {
    $input = get_json_input();
} catch (Exception $e) {
    $input = [];
}

// Prevent admin from deleting their own account (security)
if ((int)$admin['id'] === $id) {
    json_response(['error' => 'Vous ne pouvez pas supprimer votre propre compte'], 403);
}

// Require deletion reason (only warn if not provided, for backward compatibility)
$deletionReason = trim($input['deletion_reason'] ?? '');
if (empty($deletionReason)) {
    // For now, just use a default reason to maintain compatibility
    $deletionReason = 'Suppression administrative sans motif spécifié';
}

// First, mark as deleted in a deleted_users log table (for audit trail)
try {
    // Get user info before deletion
    $userStmt = $pdo->prepare('SELECT username, email FROM users WHERE id = ?');
    $userStmt->execute([$id]);
    $userData = $userStmt->fetch();
    
    if ($userData) {
        // Create deleted_users table if it doesn't exist
        $pdo->exec('CREATE TABLE IF NOT EXISTS deleted_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            username VARCHAR(100) NOT NULL,
            email VARCHAR(191) NOT NULL,
            deletion_reason TEXT NOT NULL,
            deleted_by INT NOT NULL,
            deleted_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        
        // Log the deletion
        $logStmt = $pdo->prepare('INSERT INTO deleted_users (user_id, username, email, deletion_reason, deleted_by, deleted_at) VALUES (?, ?, ?, ?, ?, ?)');
        $logStmt->execute([$id, $userData['username'], $userData['email'], $deletionReason, $admin['id'], now()]);
    }
} catch (Exception $e) {
    // Continue with deletion even if logging fails
}

// Delete the user
$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);
json_response(['message' => 'Utilisateur supprimé']);
