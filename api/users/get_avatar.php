<?php
// api/users/get_avatar.php
// Récupérer un avatar stocké en base64 dans la base de données

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Pas besoin d'authentification pour les avatars publics
$pdo = get_db();

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de l\'avatar requis']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT filename, data, mime_type FROM user_avatars WHERE id = ?');
    $stmt->execute([$id]);
    $avatar = $stmt->fetch();
    
    if (!$avatar) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Avatar non trouvé']);
        exit;
    }
    
    // Envoyer l'avatar
    header('Content-Type: ' . $avatar['mime_type']);
    header('Cache-Control: public, max-age=31536000'); // 1 an de cache
    header('Content-Disposition: inline; filename="' . $avatar['filename'] . '"');
    echo base64_decode($avatar['data']);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur lors de la récupération de l\'avatar', 'details' => $e->getMessage()]);
    exit;
}
