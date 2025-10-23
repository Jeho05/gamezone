<?php
// api/admin/upload.php
// Secure image upload for admin panel

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_check.php';

$admin = require_admin();

// Define upload directory
$uploadBaseDir = dirname(__DIR__) . '/../uploads';
$imagesDir = $uploadBaseDir . '/images';
$thumbnailsDir = $uploadBaseDir . '/thumbnails';

// Create directories if they don't exist
foreach ([$uploadBaseDir, $imagesDir, $thumbnailsDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Allowed image types
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif'
];

$maxFileSize = 10 * 1024 * 1024; // 10MB

function generate_filename($ext) {
    return date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
}

function create_thumbnail($sourcePath, $destPath, $maxWidth = 400, $maxHeight = 400) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return false;
    
    list($width, $height, $type) = $imageInfo;
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);
    
    // Create source image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$source) return false;
    
    // Create thumbnail
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save thumbnail
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($thumb, $destPath, 85);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($thumb, $destPath, 8);
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($thumb, $destPath, 85);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($thumb, $destPath);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return $success;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            json_response(['error' => 'Aucun fichier uploadé ou erreur lors de l\'upload'], 400);
        }
        
        $file = $_FILES['image'];
        
        // Check file size
        if ($file['size'] > $maxFileSize) {
            json_response(['error' => 'Fichier trop volumineux (max 10MB)'], 400);
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!isset($allowedTypes[$mimeType])) {
            json_response(['error' => 'Type de fichier non autorisé. Formats acceptés: JPEG, PNG, WebP, GIF'], 400);
        }
        
        $ext = $allowedTypes[$mimeType];
        $filename = generate_filename($ext);
        $imagePath = $imagesDir . '/' . $filename;
        $thumbnailPath = $thumbnailsDir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $imagePath)) {
            json_response(['error' => 'Erreur lors de la sauvegarde du fichier'], 500);
        }
        
        // Create thumbnail
        $thumbnailCreated = create_thumbnail($imagePath, $thumbnailPath);
        
        // Generate URLs
        $imageUrl = '/uploads/images/' . $filename;
        $thumbnailUrl = $thumbnailCreated ? '/uploads/thumbnails/' . $filename : $imageUrl;
        
        json_response([
            'success' => true,
            'message' => 'Image uploadée avec succès',
            'image_url' => $imageUrl,
            'thumbnail_url' => $thumbnailUrl,
            'filename' => $filename,
            'size' => $file['size'],
            'mime_type' => $mimeType
        ], 201);
        
    } catch (Exception $e) {
        json_response(['error' => 'Erreur lors de l\'upload', 'details' => $e->getMessage()], 500);
    }
}

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = get_json_input();
    $filename = $input['filename'] ?? null;
    
    if (!$filename) {
        json_response(['error' => 'Nom de fichier requis'], 400);
    }
    
    // Security: ensure filename doesn't contain path traversal
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
        json_response(['error' => 'Nom de fichier invalide'], 400);
    }
    
    try {
        $imagePath = $imagesDir . '/' . $filename;
        $thumbnailPath = $thumbnailsDir . '/' . $filename;
        
        $deleted = false;
        
        if (file_exists($imagePath)) {
            unlink($imagePath);
            $deleted = true;
        }
        
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        
        if (!$deleted) {
            json_response(['error' => 'Fichier non trouvé'], 404);
        }
        
        json_response([
            'success' => true,
            'message' => 'Image supprimée avec succès'
        ]);
        
    } catch (Exception $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
