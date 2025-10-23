<?php
// api/content/edit_comment.php
// Éditer son propre commentaire
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();
require_method(['PUT']);

$data = get_json_input();
$commentId = $data['id'] ?? null;
$newComment = trim($data['comment'] ?? '');

if (!$commentId || $newComment === '') {
    json_response(['error' => 'ID et nouveau commentaire requis'], 400);
}

// Vérifier que le commentaire existe et appartient à l'utilisateur
$stmt = $pdo->prepare('SELECT id, user_id, content_id FROM content_comments WHERE id = ?');
$stmt->execute([$commentId]);
$comment = $stmt->fetch();

if (!$comment) {
    json_response(['error' => 'Commentaire non trouvé'], 404);
}

// Vérifier que l'utilisateur est le propriétaire
if ($comment['user_id'] != $user['id']) {
    json_response(['error' => 'Non autorisé à modifier ce commentaire'], 403);
}

$ts = now();

try {
    $stmt = $pdo->prepare('
        UPDATE content_comments 
        SET comment = ?, updated_at = ?
        WHERE id = ?
    ');
    $stmt->execute([$newComment, $ts, $commentId]);
    
    json_response([
        'success' => true,
        'message' => 'Commentaire modifié',
        'content_id' => $comment['content_id']
    ]);
} catch (PDOException $e) {
    json_response(['error' => 'Erreur lors de la modification', 'details' => $e->getMessage()], 500);
}
