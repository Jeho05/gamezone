<?php
// api/content/delete_comment.php
// Supprimer son propre commentaire
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();
require_method(['DELETE']);

$commentId = $_GET['id'] ?? null;

if (!$commentId) {
    json_response(['error' => 'ID du commentaire requis'], 400);
}

// Vérifier que le commentaire existe et appartient à l'utilisateur
$stmt = $pdo->prepare('SELECT id, user_id, content_id FROM content_comments WHERE id = ?');
$stmt->execute([$commentId]);
$comment = $stmt->fetch();

if (!$comment) {
    json_response(['error' => 'Commentaire non trouvé'], 404);
}

// Vérifier que l'utilisateur est le propriétaire ou un admin
$isOwner = $comment['user_id'] == $user['id'];
$isAdmin = in_array($user['role'], ['admin', 'super_admin']);

if (!$isOwner && !$isAdmin) {
    json_response(['error' => 'Non autorisé à supprimer ce commentaire'], 403);
}

try {
    // Supprimer le commentaire et ses réponses (CASCADE)
    $stmt = $pdo->prepare('DELETE FROM content_comments WHERE id = ?');
    $stmt->execute([$commentId]);
    
    json_response([
        'success' => true,
        'message' => 'Commentaire supprimé',
        'content_id' => $comment['content_id']
    ]);
} catch (PDOException $e) {
    json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
}
