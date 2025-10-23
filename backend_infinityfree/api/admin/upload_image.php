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
    
    // Créer le dossier uploads s'il n'existe pas
    $uploadDir = __DIR__ . '/../../uploads/games/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Générer un nom de fichier unique
    $newFileName = uniqid('game_', true) . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFileName;
    
    // Déplacer le fichier
    if (!move_uploaded_file($fileTmpName, $uploadPath)) {
        json_response(['error' => 'Erreur lors de l\'enregistrement du fichier'], 500);
    }
    
    // Optimiser l'image (optionnel - redimensionner si trop grande)
    try {
        optimizeImage($uploadPath, $fileExtension, 1200); // max width 1200px
    } catch (Exception $e) {
        // Continuer même si l'optimisation échoue
    }
    
    // Retourner l'URL de l'image
    $imageUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/projet%20ismo/uploads/games/' . $newFileName;
    
    json_response([
        'success' => true,
        'message' => 'Image uploadée avec succès',
        'url' => $imageUrl,
        'filename' => $newFileName,
        'size' => filesize($uploadPath),
        'dimensions' => [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        ]
    ]);
}

json_response(['error' => 'Method not allowed'], 405);

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
