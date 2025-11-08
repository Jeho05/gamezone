<?php
// api/admin/upload_image.php
// API pour uploader des images (jeux, avatars, etc.)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est admin
$user = require_auth('admin');

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// POST: Upload d'une image
// ============================================================================
if ($method === 'POST') {
    
    // Vérifier qu'un fichier a été envoyé
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        json_response(['error' => 'Aucun fichier uploadé ou erreur lors de l\'upload'], 400);
    }
    
    $file = $_FILES['image'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    // Vérifier le type de fichier
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        json_response(['error' => 'Type de fichier non autorisé. Utilisez: ' . implode(', ', $allowedExtensions)], 400);
    }
    
    // Vérifier la taille (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($fileSize > $maxSize) {
        json_response(['error' => 'Fichier trop volumineux. Taille max: 5MB'], 400);
    }
    
    // Vérifier que c'est bien une image
    $imageInfo = getimagesize($fileTmpName);
    if (!$imageInfo) {
        json_response(['error' => 'Le fichier n\'est pas une image valide'], 400);
    }
    
    // Convertir l'image en base64 pour un stockage permanent
    $imageData = file_get_contents($fileTmpName);
    if ($imageData === false) {
        return json_response(['error' => 'Impossible de lire le fichier image'], 500);
    }
    
    $base64Image = base64_encode($imageData);
    $newFileName = uniqid('game_', true) . '.' . $fileExtension;
    
    // Stocker l'image dans la base de données
    global $pdo;
    if (!isset($pdo)) {
        $pdo = get_db();
    }
    
    try {
        $stmt = $pdo->prepare('INSERT INTO game_images (filename, data, mime_type, created_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $newFileName,
            $base64Image,
            'image/' . $fileExtension,
            now()
        ]);
        
        $imageId = $pdo->lastInsertId();
        
        // Générer l'URL pour accéder à l'image
        $imageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                   '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . 
                   '/api/admin/get_image.php?id=' . $imageId;
    } catch (Exception $e) {
        return json_response(['error' => 'Erreur lors du stockage de l\'image', 'details' => $e->getMessage()], 500);
    }

    json_response([
        'success' => true,
        'message' => 'Image uploadée avec succès',
        'url' => $imageUrl,
        'filename' => $newFileName,
        'dimensions' => [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        ]
    ]);
}

json_response(['error' => 'Method not allowed'], 405);

/**
 * Upload une image vers Cloudinary
 */
function uploadToCloudinary($filePath, $originalName) {
    $cloudinaryUrl = getenv('CLOUDINARY_URL');
    if (!$cloudinaryUrl) {
        return ['success' => false, 'message' => 'Cloudinary URL non configurée'];
    }
    
    // Parser l'URL Cloudinary
    $parsed = parse_url($cloudinaryUrl);
    if (!$parsed || !isset($parsed['host']) || !isset($parsed['user']) || !isset($parsed['pass'])) {
        return ['success' => false, 'message' => 'Format Cloudinary URL invalide'];
    }
    
    $cloudName = $parsed['host'];
    $apiKey = $parsed['user'];
    $apiSecret = $parsed['pass'];
    
    // Générer un nom de fichier unique
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $publicId = 'games/' . uniqid('game_', true);
    
    // Construire l'URL d'upload
    $timestamp = time();
    $signature = sha1("timestamp={$timestamp}&public_id={$publicId}");
    
    // Pour un vrai upload Cloudinary, il faudrait utiliser leur SDK
    // Pour le moment, simuler un succès
    return [
        'success' => true,
        'url' => "https://res.cloudinary.com/{$cloudName}/image/upload/v{$timestamp}/{$publicId}.{$extension}",
        'public_id' => $publicId
    ];
}

/**
 * Optimiser une image (redimensionner si trop grande)
 */
function optimizeImage($path, $extension, $maxWidth = 1200) {
    $imageInfo = getimagesize($path);
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    
    // Si l'image est déjà assez petite, ne rien faire
    if ($width <= $maxWidth) {
        return;
    }
    
    // Calculer les nouvelles dimensions
    $newWidth = $maxWidth;
    $newHeight = (int)(($height / $width) * $newWidth);
    
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
            return;
    }
    
    // Créer l'image de destination
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Préserver la transparence pour PNG et GIF
    if ($extension === 'png' || $extension === 'gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
    }
    
    // Redimensionner
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Sauvegarder
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($destination, $path, 85);
            break;
        case 'png':
            imagepng($destination, $path, 8);
            break;
        case 'gif':
            imagegif($destination, $path);
            break;
        case 'webp':
            imagewebp($destination, $path, 85);
            break;
    }
    
    // Libérer la mémoire
    imagedestroy($source);
    imagedestroy($destination);
}

// Répondre avec les informations de l'image
json_response([
    'success' => true,
    'message' => 'Image uploadée avec succès',
    'url' => $imageUrl,
    'filename' => $newFileName,
    'dimensions' => [
        'width' => $imageInfo[0],
        'height' => $imageInfo[1]
    ]
]);

json_response(['error' => 'Method not allowed'], 405);
