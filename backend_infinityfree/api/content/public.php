<?php
// api/content/public.php
// API publique pour consulter le contenu (news, events, streams, gallery)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();
require_method(['GET']);

$type = $_GET['type'] ?? 'all'; // news, events, streams, gallery, all
$id = $_GET['id'] ?? null;
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

if ($id) {
    // Récupérer un contenu spécifique
    $stmt = $pdo->prepare('
        SELECT c.*, u.username as author_name,
               (SELECT COUNT(*) FROM content_likes WHERE content_id = c.id) as likes_count,
               (SELECT COUNT(*) FROM content_comments WHERE content_id = c.id) as comments_count
        FROM content c
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.id = ? AND c.is_published = 1
    ');
    $stmt->execute([$id]);
    $content = $stmt->fetch();
    
    if (!$content) {
        json_response(['error' => 'Contenu non trouvé'], 404);
    }
    
    // Incrémenter le compteur de vues
    $pdo->prepare('UPDATE content SET views_count = views_count + 1 WHERE id = ?')->execute([$id]);
    
    // Récupérer les commentaires
    $stmt = $pdo->prepare('
        SELECT cc.*, u.username, u.avatar_url
        FROM content_comments cc
        INNER JOIN users u ON cc.user_id = u.id
        WHERE cc.content_id = ? AND cc.is_approved = 1 AND cc.parent_id IS NULL
        ORDER BY cc.created_at DESC
        LIMIT 50
    ');
    $stmt->execute([$id]);
    $comments = $stmt->fetchAll();
    
    // Pour chaque commentaire, récupérer les réponses
    foreach ($comments as &$comment) {
        $stmt = $pdo->prepare('
            SELECT cc.*, u.username, u.avatar_url
            FROM content_comments cc
            INNER JOIN users u ON cc.user_id = u.id
            WHERE cc.parent_id = ? AND cc.is_approved = 1
            ORDER BY cc.created_at ASC
        ');
        $stmt->execute([$comment['id']]);
        $comment['replies'] = $stmt->fetchAll();
    }
    
    // Vérifier si l'utilisateur a aimé ce contenu
    $currentUser = current_user();
    $hasLiked = false;
    if ($currentUser) {
        $stmt = $pdo->prepare('SELECT id FROM content_likes WHERE content_id = ? AND user_id = ?');
        $stmt->execute([$id, $currentUser['id']]);
        $hasLiked = (bool)$stmt->fetch();
    }
    
    json_response([
        'success' => true,
        'content' => $content,
        'comments' => $comments,
        'has_liked' => $hasLiked
    ]);
} else {
    // Liste de contenu
    $sql = '
        SELECT c.*, u.username as author_name,
               (SELECT COUNT(*) FROM content_likes WHERE content_id = c.id) as likes_count,
               (SELECT COUNT(*) FROM content_comments WHERE content_id = c.id) as comments_count
        FROM content c
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.is_published = 1
    ';
    $params = [];
    
    if ($type !== 'all') {
        $sql .= ' AND c.type = ?';
        $params[] = $type;
    }
    
    $sql .= ' ORDER BY c.is_pinned DESC, c.published_at DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
    // Compter le total
    $countSql = 'SELECT COUNT(*) FROM content WHERE is_published = 1';
    if ($type !== 'all') {
        $countSql .= ' AND type = ?';
        $stmt = $pdo->prepare($countSql);
        $stmt->execute([$type]);
    } else {
        $stmt = $pdo->query($countSql);
    }
    $total = (int)$stmt->fetchColumn();
    
    json_response([
        'success' => true,
        'content' => $items,
        'type' => $type,
        'count' => count($items),
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}
