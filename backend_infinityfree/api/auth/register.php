<?php
// Suppress any PHP warnings/notices that could break JSON response
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

require_once __DIR__ . '/../utils.php';
require_method(['POST']);

// Rate limiting: max 3 registrations per 10 minutes per IP
if (!check_rate_limit('register', 3, 600)) {
    Logger::warning('Rate limit exceeded for registration', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    json_response(['error' => 'Trop de tentatives d\'inscription. Réessayez dans 10 minutes.'], 429);
}

try {
    $pdo = get_db();
} catch (Exception $e) {
    json_response(['error' => 'Erreur de connexion base de données'], 500);
}

// Accept JSON or multipart/form-data
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
$isMultipart = stripos($contentType, 'multipart/form-data') !== false;

if ($isMultipart) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
} else {
    $input = get_json_input();
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = (string)($input['password'] ?? '');
}

if ($username === '' || !validate_email($email) || strlen($password) < 6) {
    json_response(['error' => 'Données invalides (pseudo, email ou mot de passe)'], 400);
}

// Check uniqueness
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(['error' => 'Email déjà utilisé'], 409);
}

$avatarUrl = null;
// Optional avatar upload
if ($isMultipart && isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profileImage'];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        json_response(['error' => 'Type de fichier non supporté'], 415);
    }
    $ext = $allowed[$mime];
    $uploadDir = dirname(__DIR__) . '/../uploads/avatars';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }
    $basename = bin2hex(random_bytes(8)) . '.' . $ext;
    $target = $uploadDir . '/' . $basename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        json_response(['error' => 'Échec du téléversement'], 500);
    }
    // Public URL
    $avatarUrl = '/uploads/avatars/' . $basename;
}

try {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $now = now();
    $joinDate = date('Y-m-d');

    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, avatar_url, points, level, status, join_date, last_active, created_at, updated_at) VALUES (?, ?, ?, "player", ?, 0, NULL, "active", ?, ?, ?, ?)');
    $stmt->execute([$username, $email, $hash, $avatarUrl, $joinDate, $now, $now, $now]);
    $userId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        json_response(['error' => 'Erreur lors de la création du compte'], 500);
    }

    set_session_user($user);

    json_response([
        'message' => 'Inscription réussie',
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
    ], 201);
} catch (Exception $e) {
    error_log('Register error: ' . $e->getMessage());
    json_response(['error' => 'Erreur lors de l\'inscription: ' . $e->getMessage()], 500);
}
