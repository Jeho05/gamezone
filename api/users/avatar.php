<?php
// api/users/avatar.php
// Upload user avatar
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

// Vérifier la session via utilitaire standard
$auth = require_auth();
$user_id = (int)$auth['id'];

// Vérifier qu'un fichier a été uploadé
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    json_response(['error' => 'Aucun fichier uploadé ou erreur lors de l\'upload'], 400);
}

$file = $_FILES['avatar'];

// Validation du type de fichier
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    json_response(['error' => 'Type de fichier non autorisé. Utilisez JPEG, PNG, GIF ou WebP'], 400);
}

// Validation de la taille (max 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    json_response(['error' => 'Le fichier est trop volumineux (max 5MB)'], 400);
}

// Créer le dossier uploads si nécessaire
$upload_dir = __DIR__ . '/../../uploads/avatars/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Générer un nom de fichier unique
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Déplacer le fichier uploadé
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    json_response(['error' => 'Erreur lors de l\'enregistrement du fichier'], 500);
}

// Mettre à jour la base de données
$pdo = get_db();
$avatar_url = '/uploads/avatars/' . $filename;

// Récupérer l'ancien avatar pour le supprimer
$stmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user && $user['avatar_url'] && strpos($user['avatar_url'], '/uploads/avatars/') === 0) {
    $old_file = __DIR__ . '/../..' . $user['avatar_url'];
    if (file_exists($old_file)) {
        @unlink($old_file);
    }
}

// Mettre à jour l'avatar URL
$stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
$stmt->execute([$avatar_url, $user_id]);

json_response([
    'message' => 'Avatar mis à jour avec succès',
    'avatar_url' => $avatar_url
]);
