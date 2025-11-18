<?php
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

$input = get_json_input();
$token = $input['token'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (!is_string($token) || $token === '') {
    json_response(['error' => 'Token manquant'], 400);
}

if (!is_string($newPassword) || strlen($newPassword) < 6) {
    json_response(['error' => 'Nouveau mot de passe invalide (min 6 caractères)'], 400);
}

$pdo = get_db();
$now = now();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT pr.id, pr.user_id FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > ? LIMIT 1 FOR UPDATE');
    $stmt->execute([$token, $now]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $pdo->rollBack();
        json_response(['error' => 'Lien de réinitialisation invalide ou expiré'], 400);
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);

    $updateUser = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?');
    $updateUser->execute([$hash, $now, (int)$reset['user_id']]);

    $updateReset = $pdo->prepare('UPDATE password_resets SET used = 1, used_at = ? WHERE id = ?');
    $updateReset->execute([$now, (int)$reset['id']]);

    $pdo->commit();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$reset['user_id']]);
    $user = $stmt->fetch();

    if ($user) {
        set_session_user($user);
        update_last_active((int)$user['id']);
        json_response([
            'message' => 'Mot de passe réinitialisé avec succès',
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'avatar_url' => $user['avatar_url'] ?? null,
                'points' => (int)$user['points'],
                'level' => $user['level'] ?? null,
                'status' => $user['status'],
            ],
        ]);
    }

    json_response(['message' => 'Mot de passe réinitialisé avec succès']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Erreur lors de la réinitialisation du mot de passe'], 500);
}
