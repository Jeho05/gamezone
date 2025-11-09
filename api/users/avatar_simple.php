<?php
/**
 * VERSION SIMPLIFIÉE - Upload avatar SANS optimisation d'image
 * Alternative robuste si avatar.php échoue
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Authentification requise
$auth = require_auth();
$user_id = (int)$auth['id'];

// GET: Récupérer l'avatar actuel
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    json_response([
        'avatar_url' => $user['avatar_url'] ?? null
    ]);
}

// POST: Upload uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Méthode non autorisée'], 405);
}

// Vérifier le fichier
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    json_response(['error' => 'Aucun fichier uploadé'], 400);
}

$file = $_FILES['avatar'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileName = $file['name'];

// Validation extension
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    json_response(['error' => 'Format non autorisé (JPEG, PNG, GIF, WebP)'], 400);
}

// Validation taille (2MB max)
if ($fileSize > 2 * 1024 * 1024) {
    json_response(['error' => 'Fichier trop volumineux (max 2MB)'], 400);
}

// Vérifier que c'est une image
if (!@getimagesize($fileTmpName)) {
    json_response(['error' => 'Fichier invalide'], 400);
}

try {
    $pdo = get_db();
    
    // Lire le fichier SANS optimisation (plus rapide, plus fiable)
    $imageData = file_get_contents($fileTmpName);
    if ($imageData === false) {
        json_response(['error' => 'Lecture fichier échouée'], 500);
    }
    
    // Encoder en BASE64
    $base64Image = base64_encode($imageData);
    $newFileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
    $mimeType = 'image/' . ($fileExtension === 'jpg' ? 'jpeg' : $fileExtension);
    
    // Vérifier si table user_avatars existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'user_avatars'");
    
    if ($checkTable->rowCount() === 0) {
        // Fallback: stocker directement dans users (data:image)
        $dataUrl = "data:$mimeType;base64,$base64Image";
        $stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
        $stmt->execute([$dataUrl, $user_id]);
        
        json_response([
            'success' => true,
            'message' => 'Avatar enregistré (mode fallback)',
            'avatar_url' => $dataUrl,
            'method' => 'inline_base64'
        ]);
    }
    
    // Mode normal: table user_avatars existe
    $pdo->beginTransaction();
    
    // Supprimer ancien avatar
    $stmt = $pdo->prepare('DELETE FROM user_avatars WHERE user_id = ?');
    $stmt->execute([$user_id]);
    
    // Insérer nouveau
    $stmt = $pdo->prepare('
        INSERT INTO user_avatars (user_id, filename, data, mime_type, file_size)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$user_id, $newFileName, $base64Image, $mimeType, $fileSize]);
    $avatarId = $pdo->lastInsertId();
    
    // Mettre à jour users
    $avatarUrl = $_ENV['API_BASE_URL'] . '/users/get_avatar.php?id=' . $avatarId;
    $stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
    $stmt->execute([$avatarUrl, $user_id]);
    
    $pdo->commit();
    
    json_response([
        'success' => true,
        'message' => 'Avatar enregistré avec succès',
        'avatar_url' => $avatarUrl,
        'method' => 'base64_table'
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    log_error('Avatar upload error', [
        'user_id' => $user_id,
        'error' => $e->getMessage()
    ]);
    
    json_response([
        'error' => 'Erreur lors de l\'enregistrement',
        'details' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur inattendue',
        'details' => $e->getMessage()
    ], 500);
}
