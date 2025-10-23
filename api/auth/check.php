<?php
// api/auth/check.php
// Check if user is authenticated and return user info

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// 401 automatique si non authentifié
$sessionUser = require_auth();

// Rafraîchir les infos depuis la base (points, level, last_active)
$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, username, email, role, avatar_url, points, level, last_active FROM users WHERE id = ?');
$stmt->execute([(int)$sessionUser['id']]);
$dbUser = $stmt->fetch();

if (!$dbUser) {
    json_response(['error' => 'Utilisateur introuvable'], 404);
}

json_response([
    'authenticated' => true,
    'user' => [
        'id' => (int)$dbUser['id'],
        'username' => $dbUser['username'],
        'email' => $dbUser['email'],
        'role' => $dbUser['role'],
        'avatar_url' => $dbUser['avatar_url'],
        'points' => (int)$dbUser['points'],
        'level' => $dbUser['level'],
        'last_active' => $dbUser['last_active']
    ]
]);
