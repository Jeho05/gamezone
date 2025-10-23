<?php
// api/content/stats.php
// Statistiques complètes du contenu
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();

try {
    // Statistiques par type
    $stmt = $pdo->query('
        SELECT 
            type,
            COUNT(*) as count,
            SUM(views_count) as total_views,
            SUM(shares_count) as total_shares
        FROM content
        WHERE is_published = 1
        GROUP BY type
    ');
    $statsByType = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques totales des likes
    $stmt = $pdo->query('
        SELECT COUNT(*) as total_likes
        FROM content_likes
    ');
    $likesData = $stmt->fetch();
    
    // Statistiques totales des commentaires
    $stmt = $pdo->query('
        SELECT COUNT(*) as total_comments
        FROM content_comments
        WHERE is_approved = 1
    ');
    $commentsData = $stmt->fetch();
    
    // Top 5 contenus par vues
    $stmt = $pdo->query('
        SELECT id, title, type, views_count
        FROM content
        WHERE is_published = 1
        ORDER BY views_count DESC
        LIMIT 5
    ');
    $topViews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top 5 contenus par likes
    $stmt = $pdo->query('
        SELECT c.id, c.title, c.type, COUNT(cl.id) as likes_count
        FROM content c
        LEFT JOIN content_likes cl ON c.id = cl.content_id
        WHERE c.is_published = 1
        GROUP BY c.id
        ORDER BY likes_count DESC
        LIMIT 5
    ');
    $topLikes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques de partage par plateforme
    $stmt = $pdo->query('
        SELECT platform, COUNT(*) as count
        FROM content_shares
        GROUP BY platform
        ORDER BY count DESC
    ');
    $sharesByPlatform = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les stats par type pour un accès facile
    $formattedStats = [
        'gallery' => ['count' => 0, 'views' => 0, 'shares' => 0],
        'event' => ['count' => 0, 'views' => 0, 'shares' => 0],
        'news' => ['count' => 0, 'views' => 0, 'shares' => 0],
        'stream' => ['count' => 0, 'views' => 0, 'shares' => 0]
    ];
    
    foreach ($statsByType as $stat) {
        $formattedStats[$stat['type']] = [
            'count' => (int)$stat['count'],
            'views' => (int)($stat['total_views'] ?? 0),
            'shares' => (int)($stat['total_shares'] ?? 0)
        ];
    }
    
    json_response([
        'success' => true,
        'stats' => [
            'by_type' => $formattedStats,
            'total_likes' => (int)$likesData['total_likes'],
            'total_comments' => (int)$commentsData['total_comments'],
            'top_views' => $topViews,
            'top_likes' => $topLikes,
            'shares_by_platform' => $sharesByPlatform
        ]
    ]);
    
} catch (PDOException $e) {
    json_response(['error' => 'Erreur lors du chargement des statistiques', 'details' => $e->getMessage()], 500);
}
