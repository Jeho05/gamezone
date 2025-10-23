<?php
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

$auth = require_auth();
$pdo = get_db();
$input = get_json_input();

$userId = isset($input['user_id']) ? (int)$input['user_id'] : (int)$auth['id'];
$current = $input['current_password'] ?? null;
$new = $input['new_password'] ?? null;

if (!$new || strlen($new) < 6) {
    json_response(['error' => 'Nouveau mot de passe invalide (min 6 caractères)'], 400);
}

// Admin can change other users' passwords without current password
$isAdmin = ($auth['role'] ?? 'player') === 'admin';
if (!$isAdmin && $userId !== (int)$auth['id']) {
    json_response(['error' => 'Forbidden'], 403);
}

// Verify current password for non-admin self-change
if (!$isAdmin || $userId === (int)$auth['id']) {
    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) json_response(['error' => 'Utilisateur introuvable'], 404);
    if (!$isAdmin) {
        if (!$current || !password_verify($current, $row['password_hash'])) {
            json_response(['error' => 'Mot de passe actuel incorrect'], 400);
        }
    }
}

$hash = password_hash($new, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?');
$stmt->execute([$hash, now(), $userId]);

json_response(['message' => 'Mot de passe mis à jour', 'user_id' => $userId]);
