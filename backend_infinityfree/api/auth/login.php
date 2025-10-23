<?php
// Suppress any PHP warnings/notices that could break JSON response
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

require_once __DIR__ . '/../utils.php';
require_method(['POST']);

// Rate limiting: max 5 login attempts per 5 minutes
if (!check_rate_limit('login', 5, 300)) {
    Logger::warning('Rate limit exceeded for login', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    json_response(['error' => 'Trop de tentatives de connexion. Réessayez dans 5 minutes.'], 429);
}

$input = get_json_input();
$email = trim($input['email'] ?? '');
$password = (string)($input['password'] ?? '');

if (!validate_email($email) || $password === '') {
    json_response(['error' => 'Email ou mot de passe invalide'], 400);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['error' => 'Identifiants incorrects'], 401);
}

// Check if account is deactivated
if ($user['status'] === 'inactive') {
    $reason = isset($user['deactivation_reason']) ? $user['deactivation_reason'] : 'Compte désactivé par un administrateur';
    $date = isset($user['deactivation_date']) ? $user['deactivation_date'] : null;
    
    $message = "Votre compte a été désactivé.\n\nMotif: " . $reason;
    if ($date) {
        $message .= "\n\nDate de désactivation: " . date('d/m/Y à H:i', strtotime($date));
    }
    $message .= "\n\nVeuillez contacter un administrateur pour plus d'informations.";
    
    json_response([
        'error' => 'Compte désactivé',
        'message' => $message,
        'reason' => $reason,
        'deactivation_date' => $date,
        'is_deactivated' => true
    ], 403);
}

set_session_user($user);
update_last_active((int)$user['id']);

json_response([
    'message' => 'Connexion réussie',
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
