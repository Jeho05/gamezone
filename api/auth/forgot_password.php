<?php
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

if (function_exists('check_rate_limit') && !check_rate_limit('forgot_password', 3, 600)) {
    if (class_exists('Logger')) {
        Logger::warning('Rate limit exceeded for password reset request', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    }
    json_response(['error' => 'Trop de tentatives. Réessayez plus tard.'], 429);
}

$input = get_json_input();
$email = trim($input['email'] ?? '');

if (!validate_email($email)) {
    json_response(['error' => 'Email invalide'], 400);
}

$pdo = get_db();

$stmt = $pdo->prepare('SELECT id, email, username, status FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || $user['status'] !== 'active') {
    json_response(['message' => 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.']);
}

$pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([(int)$user['id']]);

$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', time() + 3600);
$createdAt = now();

$stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at, used, created_at) VALUES (?, ?, ?, 0, ?)');
$stmt->execute([(int)$user['id'], $token, $expiresAt, $createdAt]);

$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
$frontendBase = envval('FRONTEND_BASE_URL') ?: ($origin ?: 'http://localhost:4000');
$frontendBase = rtrim($frontendBase, '/');
$resetUrl = $frontendBase . '/auth/reset-password/' . $token;

$subject = 'Réinitialisation de votre mot de passe';
$body = '<p>Bonjour ' . htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') . ',</p>' .
    '<p>Vous avez demandé à réinitialiser votre mot de passe.</p>' .
    '<p>Cliquez sur le lien ci-dessous pour choisir un nouveau mot de passe (valide 1 heure) :</p>' .
    '<p><a href=' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '>' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '</a></p>' .
    '<p>Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer cet email.</p>' .
    '<p>À bientôt sur OnileGame.</p>';

$sent = send_email($user['email'], $subject, $body);

if (!$sent) {
    if (function_exists('log_error')) {
        log_error('Password reset email sending failed', [
            'email' => $user['email'] ?? null,
        ]);
    } elseif (class_exists('Logger')) {
        Logger::error('Password reset email sending failed', [
            'email' => $user['email'] ?? null,
        ]);
    }
}

json_response(['message' => 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.']);
