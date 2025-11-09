<?php
// api/users/avatar.php
// Upload user avatar (BASE64 version for Railway persistence)
require_once __DIR__ . '/../config.php';
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
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];

// Validation du type de fichier
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    json_response(['error' => 'Type de fichier non autorisé. Utilisez JPEG, PNG, GIF ou WebP'], 400);
}

// Validation de la taille (max 2MB pour les avatars)
$maxSize = 2 * 1024 * 1024; // 2MB
if ($fileSize > $maxSize) {
    json_response(['error' => 'Le fichier est trop volumineux (max 2MB)'], 400);
}

// Vérifier que c'est bien une image
$imageInfo = getimagesize($fileTmpName);
if (!$imageInfo) {
    json_response(['error' => 'Le fichier n\'est pas une image valide'], 400);
}

// Convertir l'image en base64
$imageData = file_get_contents($fileTmpName);
if ($imageData === false) {
    json_response(['error' => 'Impossible de lire le fichier image'], 500);
}

// Optimiser l'image (réduire taille si nécessaire)
$imageData = optimizeAvatarImage($fileTmpName, $fileExtension, 400);
$base64Image = base64_encode($imageData);
$newFileName = 'avatar_' . $user_id . '_' . uniqid() . '.' . $fileExtension;
$mimeType = 'image/' . ($fileExtension === 'jpg' ? 'jpeg' : $fileExtension);

// Stocker dans la base de données
$pdo = get_db();

try {
    // Vérifier si la table user_avatars existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'user_avatars'");
    if ($checkTable->rowCount() === 0) {
        // Table n'existe pas, utiliser l'ancien système avec URL directe
        $avatar_url = 'data:' . $mimeType . ';base64,' . $base64Image;
        $stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
        $stmt->execute([$avatar_url, $user_id]);
        
        json_response([
            'message' => 'Avatar mis à jour avec succès',
            'avatar_url' => $avatar_url
        ]);
    }
    
    // Vérifier si l'utilisateur a déjà un avatar
    $stmt = $pdo->prepare('SELECT id FROM user_avatars WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $existingAvatar = $stmt->fetch();
    
    if ($existingAvatar) {
        // Mettre à jour l'avatar existant
        $stmt = $pdo->prepare('
            UPDATE user_avatars 
            SET filename = ?, data = ?, mime_type = ?, updated_at = ?
            WHERE user_id = ?
        ');
        $stmt->execute([
            $newFileName,
            $base64Image,
            $mimeType,
            now(),
            $user_id
        ]);
        $avatarId = $existingAvatar['id'];
    } else {
        // Créer un nouvel avatar
        $stmt = $pdo->prepare('
            INSERT INTO user_avatars (user_id, filename, data, mime_type, created_at) 
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $user_id,
            $newFileName,
            $base64Image,
            $mimeType,
            now()
        ]);
        $avatarId = $pdo->lastInsertId();
    }
    
    // Générer l'URL pour accéder à l'avatar
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $avatarUrl = $scheme . '://' . $host . '/api/users/get_avatar.php?id=' . $avatarId;
    
    // Mettre à jour l'URL de l'avatar dans la table users
    $stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
    $stmt->execute([$avatarUrl, $user_id]);
    
    json_response([
        'message' => 'Avatar mis à jour avec succès',
        'avatar_url' => $avatarUrl
    ]);
    
} catch (PDOException $e) {
    json_response([
        'error' => 'Erreur lors du stockage de l\'avatar',
        'details' => $e->getMessage()
    ], 500);
}

/**
 * Optimiser une image d'avatar (redimensionner et compresser)
 */
function optimizeAvatarImage($path, $extension, $maxSize = 400) {
    $imageInfo = getimagesize($path);
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    
    // Si l'image est déjà assez petite, retourner le contenu original
    if ($width <= $maxSize && $height <= $maxSize) {
        return file_get_contents($path);
    }
    
    // Créer l'image source
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($path);
            break;
        case 'png':
            $source = imagecreatefrompng($path);
            break;
        case 'gif':
            $source = imagecreatefromgif($path);
            break;
        case 'webp':
            $source = imagecreatefromwebp($path);
            break;
        default:
            return file_get_contents($path);
    }
    
    if (!$source) {
        return file_get_contents($path);
    }
    
    // Créer l'image de destination (carré)
    $destination = imagecreatetruecolor($maxSize, $maxSize);
    
    // Préserver la transparence pour PNG et GIF
    if ($extension === 'png' || $extension === 'gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
        imagefill($destination, 0, 0, $transparent);
    }
    
    // Calculer le crop pour centrer l'image
    $aspectRatio = $width / $height;
    if ($aspectRatio > 1) {
        $srcW = $height;
        $srcH = $height;
        $srcX = ($width - $height) / 2;
        $srcY = 0;
    } else {
        $srcW = $width;
        $srcH = $width;
        $srcX = 0;
        $srcY = ($height - $width) / 2;
    }
    
    // Redimensionner et recadrer
    imagecopyresampled($destination, $source, 0, 0, $srcX, $srcY, $maxSize, $maxSize, $srcW, $srcH);
    
    // Capturer l'image optimisée
    ob_start();
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($destination, null, 85);
            break;
        case 'png':
            imagepng($destination, null, 8);
            break;
        case 'gif':
            imagegif($destination);
            break;
        case 'webp':
            imagewebp($destination, null, 85);
            break;
    }
    $imageData = ob_get_clean();
    
    // Libérer la mémoire
    imagedestroy($source);
    imagedestroy($destination);
    
    return $imageData;
}
