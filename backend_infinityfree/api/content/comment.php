<?php
// api/content/comment.php
// Commenter un contenu
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();
require_method(['POST']);

$data = get_json_input();
$contentId = $data['content_id'] ?? null;
$comment = trim($data['comment'] ?? '');
$parentId = $data['parent_id'] ?? null;

if (!$contentId || $comment === '') {
    json_response(['error' => 'content_id et comment requis'], 400);
}

// Vérifier que le contenu existe
$stmt = $pdo->prepare('SELECT id FROM content WHERE id = ?');
$stmt->execute([$contentId]);
if (!$stmt->fetch()) {
    json_response(['error' => 'Contenu non trouvé'], 404);
}

// Vérifier le parent si spécifié
if ($parentId) {
    $stmt = $pdo->prepare('SELECT id FROM content_comments WHERE id = ? AND content_id = ?');
    $stmt->execute([$parentId, $contentId]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Commentaire parent non trouvé'], 404);
    }
}

$ts = now();

try {
    $stmt = $pdo->prepare('
        INSERT INTO content_comments (content_id, user_id, comment, parent_id, is_approved, created_at, updated_at)
        VALUES (?, ?, ?, ?, 1, ?, ?)
    ');
    $stmt->execute([$contentId, $user['id'], $comment, $parentId, $ts, $ts]);
    
    $commentId = $pdo->lastInsertId();
    
    json_response([
        'success' => true,
        'message' => 'Commentaire ajouté',
        'comment_id' => $commentId
    ], 201);
} catch (PDOException $e) {
    json_response(['error' => 'Erreur lors de l\'ajout du commentaire', 'details' => $e->getMessage()], 500);
}
