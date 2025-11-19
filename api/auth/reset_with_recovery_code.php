<?php
// api/auth/reset_with_recovery_code.php
// Réinitialisation de mot de passe via code de récupération (sans email externe)

require_once __DIR__ . '/../utils.php';
require_method(['POST']);

$input = get_json_input();
$email = trim($input['email'] ?? '');
$recoveryCode = (string)($input['recovery_code'] ?? '');
$newPassword = (string)($input['new_password'] ?? '');

if (!validate_email($email) || $recoveryCode === '' || strlen($newPassword) < 6) {
    json_response(['error' => 'Données invalides (email, code de récupération ou mot de passe)'], 400);
}

$pdo = get_db();

$stmt = $pdo->prepare('SELECT id, email, username, status, recovery_code_hash FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || ($user['status'] ?? '') !== 'active') {
    json_response(['error' => 'Utilisateur introuvable ou inactif'], 400);
}

if (empty($user['recovery_code_hash']) || !password_verify($recoveryCode, $user['recovery_code_hash'])) {
    json_response(['error' => 'Code de récupération invalide'], 400);
}

$now = now();
$newHash = password_hash($newPassword, PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    // Mettre à jour le mot de passe
    $update = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?');
    $update->execute([$newHash, $now, (int)$user['id']]);

    // Générer un nouveau code de récupération pour les prochaines fois
    $newRecoveryCode = bin2hex(random_bytes(8));
    $newRecoveryHash = password_hash($newRecoveryCode, PASSWORD_BCRYPT);

    $updateCode = $pdo->prepare('UPDATE users SET recovery_code_hash = ?, updated_at = ? WHERE id = ?');
    $updateCode->execute([$newRecoveryHash, $now, (int)$user['id']]);

    $pdo->commit();

    json_response([
        'message' => 'Mot de passe réinitialisé avec succès.',
        // On renvoie le nouveau code de récupération à noter soigneusement
        'recovery_code' => $newRecoveryCode,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Erreur lors de la réinitialisation du mot de passe'], 500);
}
