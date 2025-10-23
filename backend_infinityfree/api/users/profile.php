<?php
// api/users/profile.php
// Get or update user profile
require_once __DIR__ . '/../utils.php';

// GET: Récupérer le profil
// PUT: Mettre à jour le profil
require_method(['GET', 'PUT']);

// Vérifier la session (via utils)
$user = require_auth();
$user_id = (int)$user['id'];
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Récupérer le profil utilisateur
    $stmt = $pdo->prepare('
        SELECT id, username, email, role, avatar_url, points, level, status, created_at
        FROM users
        WHERE id = ?
    ');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        json_response(['error' => 'Utilisateur non trouvé'], 404);
    }
    
    // Statistiques supplémentaires
    $stats_stmt = $pdo->prepare('
        SELECT 
            COUNT(*) as total_activities,
            COALESCE(SUM(CASE WHEN change_amount > 0 THEN change_amount ELSE 0 END), 0) as points_earned,
            COUNT(DISTINCT DATE(created_at)) as active_days
        FROM points_transactions
        WHERE user_id = ?
    ');
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch();
    
    json_response([
        'user' => [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'avatar_url' => $user['avatar_url'],
            'points' => (int)$user['points'],
            'level' => $user['level'],
            'status' => $user['status'],
            'member_since' => (new DateTime($user['created_at']))->format(DATE_ATOM),
            'stats' => [
                'total_activities' => (int)($stats['total_activities'] ?? 0),
                'points_earned' => (int)($stats['points_earned'] ?? 0),
                'active_days' => (int)($stats['active_days'] ?? 0)
            ]
        ]
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Mettre à jour le profil
    $input = get_json_input();
    
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $current_password = (string)($input['current_password'] ?? '');
    $new_password = (string)($input['new_password'] ?? '');
    
    // Validation
    if ($username !== '' && (strlen($username) < 3 || strlen($username) > 50)) {
        json_response(['error' => 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères'], 400);
    }
    
    if ($email !== '' && !validate_email($email)) {
        json_response(['error' => 'Email invalide'], 400);
    }
    
    // Récupérer l'utilisateur actuel
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        json_response(['error' => 'Utilisateur non trouvé'], 404);
    }
    
    // Préparer les mises à jour
    $updates = [];
    $params = [];
    
    // Username
    if ($username !== '' && $username !== $user['username']) {
        // Vérifier l'unicité
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $check->execute([$username, $user_id]);
        if ($check->fetch()) {
            json_response(['error' => 'Ce nom d\'utilisateur est déjà utilisé'], 400);
        }
        $updates[] = 'username = ?';
        $params[] = $username;
    }
    
    // Email
    if ($email !== '' && $email !== $user['email']) {
        // Vérifier l'unicité
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check->execute([$email, $user_id]);
        if ($check->fetch()) {
            json_response(['error' => 'Cet email est déjà utilisé'], 400);
        }
        $updates[] = 'email = ?';
        $params[] = $email;
    }
    
    // Changement de mot de passe
    if ($new_password !== '') {
        if ($current_password === '') {
            json_response(['error' => 'Le mot de passe actuel est requis'], 400);
        }
        
        if (!password_verify($current_password, $user['password_hash'])) {
            json_response(['error' => 'Mot de passe actuel incorrect'], 401);
        }
        
        if (strlen($new_password) < 6) {
            json_response(['error' => 'Le nouveau mot de passe doit contenir au moins 6 caractères'], 400);
        }
        
        $updates[] = 'password_hash = ?';
        $params[] = password_hash($new_password, PASSWORD_BCRYPT);
    }
    
    // Si aucune mise à jour
    if (empty($updates)) {
        // Retourner l'utilisateur courant pour compatibilité frontend
        $stmt = $pdo->prepare('SELECT id, username, email, role, avatar_url, points, level, status, created_at FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $current = $stmt->fetch();
        json_response([
            'message' => 'Aucune modification détectée',
            'user' => [
                'id' => (int)$current['id'],
                'username' => $current['username'],
                'email' => $current['email'],
                'role' => $current['role'],
                'avatar_url' => $current['avatar_url'],
                'points' => (int)$current['points'],
                'level' => $current['level'],
                'status' => $current['status'],
                'member_since' => $current['created_at']
            ]
        ]);
    }
    
    // Effectuer la mise à jour
    $params[] = $user_id;
    $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Récupérer le profil mis à jour
    $stmt = $pdo->prepare('SELECT id, username, email, role, avatar_url, points, level, status, created_at FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $updated_user = $stmt->fetch();
    // Mettre à jour la session pour refléter les nouvelles informations
    set_session_user($updated_user);

    json_response([
        'message' => 'Profil mis à jour avec succès',
        'user' => [
            'id' => (int)$updated_user['id'],
            'username' => $updated_user['username'],
            'email' => $updated_user['email'],
            'role' => $updated_user['role'],
            'avatar_url' => $updated_user['avatar_url'],
            'points' => (int)$updated_user['points'],
            'level' => $updated_user['level'],
            'status' => $updated_user['status'],
            'member_since' => (new DateTime($updated_user['created_at']))->format(DATE_ATOM)
        ]
    ]);
}
