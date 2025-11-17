<?php
// api/admin/reset_user_password.php
// Endpoint admin de secours pour réinitialiser manuellement le mot de passe d'un utilisateur

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_check.php';

$admin = require_admin();
require_method(['POST']);

$input = get_json_input();
$userId = isset($input['user_id']) ? (int)$input['user_id'] : null;
$email = trim($input['email'] ?? '');
$newPassword = (string)($input['new_password'] ?? '');

if ($userId <= 0 && $email === '') {
    json_response(['error' => 'user_id ou email requis'], 400);
}

if (strlen($newPassword) < 6) {
    json_response(['error' => 'Nouveau mot de passe trop court (min 6 caractères)'], 400);
}

$db = get_db();

// Récupérer l'utilisateur cible
if ($userId > 0) {
    $stmt = $db->prepare('SELECT id, email, username, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
} else {
    $stmt = $db->prepare('SELECT id, email, username, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    json_response(['error' => 'Utilisateur non trouvé'], 404);
}

if (($user['status'] ?? '') !== 'active') {
    json_response(['error' => 'Utilisateur inactif, impossible de réinitialiser le mot de passe'], 400);
}

$now = now();
$newHash = password_hash($newPassword, PASSWORD_BCRYPT);

try {
    $db->beginTransaction();

    $update = $db->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?');
    $update->execute([$newHash, $now, (int)$user['id']]);

    // Optionnel : régénérer un code de récupération
    $newRecoveryCode = bin2hex(random_bytes(8));
    $newRecoveryHash = password_hash($newRecoveryCode, PASSWORD_BCRYPT);
    $updateCode = $db->prepare('UPDATE users SET recovery_code_hash = ?, updated_at = ? WHERE id = ?');
    $updateCode->execute([$newRecoveryHash, $now, (int)$user['id']]);

    $db->commit();

    json_response([
        'success' => true,
        'message' => 'Mot de passe réinitialisé avec succès pour l\'utilisateur.',
        // L\'admin pourra communiquer ce code par un canal sécurisé (en personne, téléphone, etc.)
        'recovery_code' => $newRecoveryCode,
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    json_response(['error' => 'Erreur lors de la réinitialisation du mot de passe', 'details' => $e->getMessage()], 500);
}
