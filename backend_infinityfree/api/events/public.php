<?php
// api/events/public.php
// Public API to fetch published events for display

require_once __DIR__ . '/../config.php';

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Get single published event with details
        $stmt = $db->prepare("
            SELECT e.*,
                   t.game_name as tournament_game,
                   t.platform as tournament_platform,
                   t.max_participants as tournament_max_participants,
                   t.current_participants as tournament_current_participants,
                   t.prize_pool as tournament_prize,
                   t.status as tournament_status,
                   t.tournament_start,
                   t.tournament_end,
                   t.rules as tournament_rules,
                   s.streamer_name,
                   s.platform as stream_platform,
                   s.stream_url,
                   s.game_name as stream_game,
                   s.scheduled_start as stream_start,
                   s.scheduled_end as stream_end,
                   s.status as stream_status,
                   (SELECT COUNT(*) FROM gallery WHERE event_id = e.id AND status = 'active') as gallery_count
            FROM events e
            LEFT JOIN tournaments t ON t.event_id = e.id
            LEFT JOIN streams s ON s.event_id = e.id
            WHERE e.id = ? AND e.status = 'published'
        ");
        $stmt->execute([$id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            json_response(['error' => 'Événement non trouvé'], 404);
        }
        
        // Get related gallery images
        $stmt = $db->prepare("
            SELECT id, title, image_url, thumbnail_url
            FROM gallery
            WHERE event_id = ? AND status = 'active'
            ORDER BY display_order ASC, created_at DESC
        ");
        $stmt->execute([$id]);
        $event['gallery'] = $stmt->fetchAll();
        
        json_response($event);
    } else {
        // List published events with filters
        $type = $_GET['type'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = "
            SELECT e.*,
                   (SELECT COUNT(*) FROM gallery WHERE event_id = e.id AND status = 'active') as gallery_count
            FROM events e
            WHERE e.status = 'published'
        ";
        $params = [];
        
        if ($type) {
            $sql .= " AND e.type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY e.date DESC, e.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM events e WHERE e.status = 'published'";
        $countParams = [];
        if ($type) {
            $countSql .= " AND e.type = ?";
            $countParams[] = $type;
        }
        
        $stmt = $db->prepare($countSql);
        $stmt->execute($countParams);
        $total = $stmt->fetch()['total'];
        
        json_response([
            'events' => $events,
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
