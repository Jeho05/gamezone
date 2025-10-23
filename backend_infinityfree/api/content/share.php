<?php
// api/content/share.php
// Enregistrer un partage de contenu
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();
require_method(['POST']);

// Optionnel: authentification (peut être anonyme)
$user = current_user();

$data = get_json_input();
$contentId = $data['content_id'] ?? null;
$platform = $data['platform'] ?? null;

if (!$contentId || !$platform) {
    json_response(['error' => 'content_id et platform requis'], 400);
}

$validPlatforms = ['facebook', 'twitter', 'whatsapp', 'telegram', 'copy_link'];
if (!in_array($platform, $validPlatforms)) {
    json_response(['error' => 'Platform invalide'], 400);
}

// Vérifier que le contenu existe
$stmt = $pdo->prepare('SELECT id FROM content WHERE id = ? AND is_published = 1');
$stmt->execute([$contentId]);
if (!$stmt->fetch()) {
    json_response(['error' => 'Contenu non trouvé'], 404);
}

$ts = now();

try {
    // Enregistrer le partage
    $stmt = $pdo->prepare('
        INSERT INTO content_shares (content_id, user_id, platform, created_at)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$contentId, $user ? $user['id'] : null, $platform, $ts]);
    
    // Incrémenter le compteur de partages
    $pdo->prepare('UPDATE content SET shares_count = shares_count + 1 WHERE id = ?')
        ->execute([$contentId]);
    
    json_response([
        'success' => true,
        'message' => 'Partage enregistré'
    ], 201);
} catch (PDOException $e) {
    json_response(['error' => 'Erreur lors de l\'enregistrement', 'details' => $e->getMessage()], 500);
}
