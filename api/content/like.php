<?php
// api/content/like.php
// Aimer ou ne plus aimer un contenu
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();
require_method(['POST']);

$data = get_json_input();
$contentId = $data['content_id'] ?? null;

if (!$contentId) {
    json_response(['error' => 'content_id requis'], 400);
}

// Vérifier que le contenu existe
$stmt = $pdo->prepare('SELECT id FROM content WHERE id = ?');
$stmt->execute([$contentId]);
if (!$stmt->fetch()) {
    json_response(['error' => 'Contenu non trouvé'], 404);
}

// Vérifier si l'utilisateur a déjà aimé
$stmt = $pdo->prepare('SELECT id FROM content_likes WHERE content_id = ? AND user_id = ?');
$stmt->execute([$contentId, $user['id']]);
$existingLike = $stmt->fetch();

$ts = now();

if ($existingLike) {
    // Retirer le like
    $stmt = $pdo->prepare('DELETE FROM content_likes WHERE content_id = ? AND user_id = ?');
    $stmt->execute([$contentId, $user['id']]);
    
    json_response([
        'success' => true,
        'action' => 'unliked',
        'message' => 'Like retiré'
    ]);
} else {
    // Ajouter le like
    $stmt = $pdo->prepare('
        INSERT INTO content_likes (content_id, user_id, created_at)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$contentId, $user['id'], $ts]);
    
    json_response([
        'success' => true,
        'action' => 'liked',
        'message' => 'Contenu aimé'
    ], 201);
}
