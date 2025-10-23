<?php
// api/admin/events.php
// Admin CRUD for events (news, tournaments, streams, events)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_check.php';

$admin = require_admin();
$db = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// GET - List all events or get one
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Get single event with all details
        $stmt = $db->prepare("
            SELECT e.*, 
                   u.username as creator_username,
                   t.game_name as tournament_game,
                   t.max_participants as tournament_max_participants,
                   t.prize_pool as tournament_prize,
                   t.status as tournament_status,
                   s.streamer_name,
                   s.platform as stream_platform,
                   s.stream_url,
                   s.status as stream_status
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN tournaments t ON t.event_id = e.id
            LEFT JOIN streams s ON s.event_id = e.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            json_response(['error' => 'Événement non trouvé'], 404);
        }
        
        json_response($event);
    } else {
        // List all events with filters
        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null;
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = "
            SELECT e.*, 
                   u.username as creator_username,
                   (SELECT COUNT(*) FROM gallery WHERE event_id = e.id) as gallery_count
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE 1=1
        ";
        $params = [];
        
        if ($type) {
            $sql .= " AND e.type = ?";
            $params[] = $type;
        }
        
        if ($status) {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM events e WHERE 1=1";
        $countParams = [];
        if ($type) {
            $countSql .= " AND e.type = ?";
            $countParams[] = $type;
        }
        if ($status) {
            $countSql .= " AND e.status = ?";
            $countParams[] = $status;
        }
        
        $stmt = $db->prepare($countSql);
        $stmt->execute($countParams);
        $total = $stmt->fetch()['total'];
        
        json_response([
            'events' => $events,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
}

// POST - Create new event
if ($method === 'POST') {
    $input = get_json_input();
    
    $required = ['title', 'date', 'type'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    $db->beginTransaction();
    
    try {
        // Insert event
        $stmt = $db->prepare("
            INSERT INTO events (
                title, date, type, image_url, participants, winner, 
                description, content, status, created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $input['title'],
            $input['date'],
            $input['type'],
            $input['image_url'] ?? null,
            $input['participants'] ?? null,
            $input['winner'] ?? null,
            $input['description'] ?? null,
            $input['content'] ?? null,
            $input['status'] ?? 'draft',
            $admin['id']
        ]);
        
        $eventId = $db->lastInsertId();
        
        // If tournament, create tournament details
        if ($input['type'] === 'tournament' && isset($input['tournament'])) {
            $t = $input['tournament'];
            $stmt = $db->prepare("
                INSERT INTO tournaments (
                    event_id, game_name, platform, max_participants, 
                    prize_pool, registration_start, registration_end,
                    tournament_start, tournament_end, rules, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $eventId,
                $t['game_name'],
                $t['platform'] ?? null,
                $t['max_participants'] ?? 32,
                $t['prize_pool'] ?? null,
                $t['registration_start'] ?? null,
                $t['registration_end'] ?? null,
                $t['tournament_start'] ?? $input['date'],
                $t['tournament_end'] ?? null,
                $t['rules'] ?? null,
                $t['status'] ?? 'upcoming'
            ]);
        }
        
        // If stream, create stream details
        if ($input['type'] === 'stream' && isset($input['stream'])) {
            $s = $input['stream'];
            $stmt = $db->prepare("
                INSERT INTO streams (
                    event_id, streamer_name, platform, stream_url, 
                    game_name, scheduled_start, scheduled_end, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $eventId,
                $s['streamer_name'],
                $s['platform'] ?? 'Twitch',
                $s['stream_url'] ?? null,
                $s['game_name'] ?? null,
                $s['scheduled_start'] ?? $input['date'],
                $s['scheduled_end'] ?? null,
                $s['status'] ?? 'scheduled'
            ]);
        }
        
        $db->commit();
        
        json_response([
            'success' => true,
            'message' => 'Événement créé avec succès',
            'id' => $eventId
        ], 201);
        
    } catch (Exception $e) {
        $db->rollBack();
        json_response(['error' => 'Erreur lors de la création', 'details' => $e->getMessage()], 500);
    }
}

// PUT/PATCH - Update event
if ($method === 'PUT' || $method === 'PATCH') {
    $input = get_json_input();
    $id = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis pour la mise à jour'], 400);
    }
    
    $db->beginTransaction();
    
    try {
        // Build update query dynamically
        $updates = [];
        $params = [];
        
        $allowed = ['title', 'date', 'type', 'image_url', 'participants', 'winner', 'description', 'content', 'status'];
        
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updates)) {
            json_response(['error' => 'Aucun champ à mettre à jour'], 400);
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE events SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Update tournament if applicable
        if (isset($input['tournament'])) {
            $t = $input['tournament'];
            
            // Check if tournament exists
            $check = $db->prepare("SELECT id FROM tournaments WHERE event_id = ?");
            $check->execute([$id]);
            $existing = $check->fetch();
            
            if ($existing) {
                // Update existing tournament
                $tUpdates = [];
                $tParams = [];
                $tAllowed = ['game_name', 'platform', 'max_participants', 'prize_pool', 
                            'registration_start', 'registration_end', 'tournament_start', 
                            'tournament_end', 'rules', 'status'];
                
                foreach ($tAllowed as $field) {
                    if (isset($t[$field])) {
                        $tUpdates[] = "$field = ?";
                        $tParams[] = $t[$field];
                    }
                }
                
                if (!empty($tUpdates)) {
                    $tUpdates[] = "updated_at = NOW()";
                    $tParams[] = $id;
                    $sql = "UPDATE tournaments SET " . implode(', ', $tUpdates) . " WHERE event_id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($tParams);
                }
            }
        }
        
        // Update stream if applicable
        if (isset($input['stream'])) {
            $s = $input['stream'];
            
            $check = $db->prepare("SELECT id FROM streams WHERE event_id = ?");
            $check->execute([$id]);
            $existing = $check->fetch();
            
            if ($existing) {
                $sUpdates = [];
                $sParams = [];
                $sAllowed = ['streamer_name', 'platform', 'stream_url', 'game_name', 
                            'scheduled_start', 'scheduled_end', 'status'];
                
                foreach ($sAllowed as $field) {
                    if (isset($s[$field])) {
                        $sUpdates[] = "$field = ?";
                        $sParams[] = $s[$field];
                    }
                }
                
                if (!empty($sUpdates)) {
                    $sUpdates[] = "updated_at = NOW()";
                    $sParams[] = $id;
                    $sql = "UPDATE streams SET " . implode(', ', $sUpdates) . " WHERE event_id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($sParams);
                }
            }
        }
        
        $db->commit();
        
        json_response([
            'success' => true,
            'message' => 'Événement mis à jour avec succès'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// DELETE - Delete event
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis pour la suppression'], 400);
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            json_response(['error' => 'Événement non trouvé'], 404);
        }
        
        json_response([
            'success' => true,
            'message' => 'Événement supprimé avec succès'
        ]);
        
    } catch (Exception $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}
