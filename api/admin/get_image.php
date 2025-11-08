<?php
// api/admin/get_image.php
// Récupérer une image stockée en base64 dans la base de données

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Pas besoin d'authentification pour les images publiques
$pdo = get_db();

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de l\'image requis']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT filename, data, mime_type FROM game_images WHERE id = ?');
    $stmt->execute([$id]);
    $image = $stmt->fetch();
    
    if (!$image) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Image non trouvée']);
        exit;
    }
    
    // Envoyer l'image
    header('Content-Type: ' . $image['mime_type']);
    header('Cache-Control: public, max-age=31536000'); // 1 an de cache
    echo base64_decode($image['data']);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur lors de la récupération de l\'image', 'details' => $e->getMessage()]);
    exit;
}