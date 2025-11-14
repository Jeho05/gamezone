<?php
// api/public/upload_image.php
// API pour uploader des images publiquement (pour l'inscription)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Headers CORS (dynamic)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'https://gamezoneismo.vercel.app',
    'http://localhost',
    'http://localhost:5173',
    'http://127.0.0.1',
    'http://127.0.0.1:5173',
];
if ($origin && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://gamezoneismo.vercel.app');
}
header('Vary: Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// POST: Upload d'une image (pas besoin d'authentification pour l'inscription)
// ============================================================================
if ($method === 'POST') {
    
    // Rate limiting pour éviter les abus
    if (!check_rate_limit('upload_image', 5, 300)) {
        json_response(['error' => 'Trop de tentatives d\'upload. Réessayez dans 5 minutes.'], 429);
    }
    
    // Vérifier qu'un fichier a été envoyé
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        json_response(['error' => 'Aucun fichier uploadé ou erreur lors de l\'upload'], 400);
    }
    
    $file = $_FILES['image'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    
    // Vérifier le type de fichier
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        json_response(['error' => 'Type de fichier non autorisé. Utilisez: ' . implode(', ', $allowedExtensions)], 400);
    }
    
    // Vérifier la taille (max 2MB pour les uploads publics)
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($fileSize > $maxSize) {
        json_response(['error' => 'Fichier trop volumineux. Taille max: 2MB'], 400);
    }
    
    // Vérifier que c'est bien une image
    $imageInfo = getimagesize($fileTmpName);
    if (!$imageInfo) {
        json_response(['error' => 'Le fichier n\'est pas une image valide'], 400);
    }
    
    // Lire le fichier et l'encoder en base64
    $imageData = file_get_contents($fileTmpName);
    if ($imageData === false) {
        json_response(['error' => 'Impossible de lire le fichier image'], 500);
    }
    
    $base64Image = base64_encode($imageData);
    $mimeType = 'image/' . ($fileExtension === 'jpg' ? 'jpeg' : $fileExtension);
    
    // Créer une data URL
    $dataUrl = "data:$mimeType;base64," . $base64Image;
    
    // Pas de stockage en base pour les uploads publics, juste retourner la data URL
    json_response([
        'success' => true,
        'message' => 'Image uploadée avec succès',
        'url' => $dataUrl,
        'filename' => $fileName,
        'dimensions' => [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        ]
    ]);
}

json_response(['error' => 'Method not allowed'], 405);
