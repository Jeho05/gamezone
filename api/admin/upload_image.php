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
    
    // Résoudre le dossier uploads (supporte les environnements readonly partiels)
    $baseUploadsDir = __DIR__ . '/../../uploads';
    if (!is_dir($baseUploadsDir)) {
        if (!@mkdir($baseUploadsDir, 0775, true) && !is_dir($baseUploadsDir)) {
            log_error('Upload image failed: unable to create base uploads directory', [
                'baseUploadsDir' => $baseUploadsDir,
                'last_error' => error_get_last()
            ]);
            return json_response(['error' => 'Le dossier uploads est inaccessible.'], 500);
        }
    }

    $uploadDir = rtrim($baseUploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'games' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            log_error('Upload image failed: unable to create games directory', [
                'uploadDir' => $uploadDir,
                'last_error' => error_get_last()
            ]);
            return json_response(['error' => 'Impossible de créer le dossier pour les images.'], 500);
        }
    }

    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0775);
        if (!is_writable($uploadDir)) {
            log_error('Upload image failed: directory not writable', [
                'uploadDir' => $uploadDir,
                'permissions' => substr(sprintf('%o', fileperms($uploadDir)), -4)
            ]);
            return json_response(['error' => 'Le dossier d\'upload n\'est pas accessible en écriture.'], 500);
        }
    }
    
    // Générer un nom de fichier unique
    $newFileName = uniqid('game_', true) . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFileName;
    
    // Déplacer le fichier
    if (!is_uploaded_file($fileTmpName)) {
        log_error('Upload image failed: temporary file not found', [
            'tmp_name' => $fileTmpName,
            'file' => $fileName,
            'error_code' => $fileError
        ]);
        return json_response(['error' => 'Le fichier temporaire est invalide.'], 500);
    }

    $moved = @move_uploaded_file($fileTmpName, $uploadPath);
    if (!$moved) {
        // Fallback to rename (certain environnements désactivent move_uploaded_file)
        $moved = @rename($fileTmpName, $uploadPath);
    }

    if (!$moved) {
        $lastError = error_get_last();
        log_error('Upload image failed: unable to move uploaded file', [
            'tmp_name' => $fileTmpName,
            'destination' => $uploadPath,
            'last_error' => $lastError
        ]);
        $response = ['error' => 'Erreur lors de l\'enregistrement du fichier'];
        $appEnv = getenv('APP_ENV') ?: 'production';
        if ($appEnv !== 'production') {
            $response['debug'] = [
                'destination' => $uploadPath,
                'tmp_name' => $fileTmpName,
                'last_error' => $lastError
            ];
        }
        return json_response($response, 500);
    }
    
    // Optimiser l'image (optionnel - redimensionner si trop grande)
    try {
        optimizeImage($uploadPath, $fileExtension, 1200); // max width 1200px
    } catch (Exception $e) {
        // Continuer même si l'optimisation échoue
    }
    
    // Déterminer l'URL de base dynamiquement pour supporter différentes configurations (proxy, HTTPS, etc.)
    $scheme = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0];
    } elseif (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        $scheme = 'https';
    }

    // Déterminer l'hôte (support des proxies type Railway/Vercel)
    $hostHeader = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = $hostHeader;
    if (strpos($host, ':') === false) {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $host .= ':' . $_SERVER['HTTP_X_FORWARDED_PORT'];
        } elseif (!empty($_SERVER['SERVER_PORT']) && !in_array($_SERVER['SERVER_PORT'], ['80', '443'], true)) {
            $host .= ':' . $_SERVER['SERVER_PORT'];
        }
    }

    // Identifier le chemin public avant /api/
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = '';
    if ($scriptName !== '') {
        $apiPos = stripos($scriptName, '/api/');
        if ($apiPos !== false) {
            $basePath = substr($scriptName, 0, $apiPos);
        } else {
            $basePath = dirname(dirname(dirname($scriptName)));
        }
        if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
    }

    $relativePath = rtrim($basePath, '/') . '/uploads/games/' . $newFileName;
    if ($relativePath === '' || $relativePath[0] !== '/') {
        $relativePath = '/' . ltrim($relativePath, '/');
    }

    // Normaliser les doubles slashs (sauf après le schéma)
    $relativePath = preg_replace('#/+#', '/', $relativePath);

    $imageUrl = $scheme . '://' . $host . $relativePath;

    json_response([
        'success' => true,
        'message' => 'Image uploadée avec succès',
        'url' => $imageUrl,
        'relativePath' => $relativePath,
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
