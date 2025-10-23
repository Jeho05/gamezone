<?php
// api/tournaments/index.php
// API CRUD pour les tournois

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Liste des tournois
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    $status = $_GET['status'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    
    if ($id) {
        // Tournoi spécifique avec participants
        $stmt = $pdo->prepare('
            SELECT t.*, g.name as game_name, g.image_url as game_image,
                   u.username as creator_name,
                   (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id) as participants_count
            FROM tournaments t
            LEFT JOIN games g ON t.game_id = g.id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.id = ?
        ');
        $stmt->execute([$id]);
        $tournament = $stmt->fetch();
        
        if (!$tournament) {
            json_response(['error' => 'Tournoi non trouvé'], 404);
        }
        
        // Get participants
        $stmt = $pdo->prepare('
            SELECT tp.*, u.username, u.avatar_url
            FROM tournament_participants tp
            INNER JOIN users u ON tp.user_id = u.id
            WHERE tp.tournament_id = ?
            ORDER BY tp.seed ASC, tp.registered_at ASC
        ');
        $stmt->execute([$id]);
        $tournament['participants'] = $stmt->fetchAll();
        
        json_response(['tournament' => $tournament]);
    } else {
        // Liste
        $sql = '
            SELECT t.*, g.name as game_name, g.image_url as game_image,
                   (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id) as participants_count
            FROM tournaments t
            LEFT JOIN games g ON t.game_id = g.id
            WHERE 1=1
        ';
        
        $params = [];
        
        if ($status) {
            $sql .= ' AND t.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY t.start_date DESC LIMIT ?';
        $params[] = $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tournaments = $stmt->fetchAll();
        
        json_response(['tournaments' => $tournaments]);
    }
}

// POST - Créer un tournoi (ADMIN)
if ($method === 'POST') {
    $user = require_auth('admin');
    $data = get_json_input();
    
    $required = ['name', 'start_date'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    try {
        $ts = now();
        $stmt = $pdo->prepare('
            INSERT INTO tournaments (
                name, description, game_id, tournament_type, prize_pool, prize_currency,
                max_participants, entry_fee, start_date, end_date, registration_deadline,
                status, image_url, rules, created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['game_id'] ?? null,
            $data['tournament_type'] ?? 'single_elimination',
            $data['prize_pool'] ?? 0,
            $data['prize_currency'] ?? 'XOF',
            $data['max_participants'] ?? null,
            $data['entry_fee'] ?? 0,
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['registration_deadline'] ?? null,
            $data['status'] ?? 'upcoming',
            $data['image_url'] ?? null,
            $data['rules'] ?? null,
            $user['id'],
            $ts,
            $ts
        ]);
        
        $tournamentId = $pdo->lastInsertId();
        
        json_response([
            'success' => true,
            'message' => 'Tournoi créé avec succès',
            'id' => $tournamentId
        ], 201);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la création', 'details' => $e->getMessage()], 500);
    }
}

// PUT - Mettre à jour (ADMIN)
if ($method === 'PUT') {
    $user = require_auth('admin');
    $data = get_json_input();
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis'], 400);
    }
    
    try {
        $updates = [];
        $params = [];
        
        $allowedFields = [
            'name', 'description', 'game_id', 'tournament_type', 'prize_pool', 'prize_currency',
            'max_participants', 'entry_fee', 'start_date', 'end_date', 'registration_deadline',
            'status', 'image_url', 'rules'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            json_response(['error' => 'Aucune donnée à mettre à jour'], 400);
        }
        
        $updates[] = 'updated_at = ?';
        $params[] = now();
        $params[] = $id;
        
        $sql = 'UPDATE tournaments SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json_response(['success' => true, 'message' => 'Tournoi mis à jour']);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// DELETE (ADMIN)
if ($method === 'DELETE') {
    $user = require_auth('admin');
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis'], 400);
    }
    
    try {
        $stmt = $pdo->prepare('DELETE FROM tournaments WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response(['success' => true, 'message' => 'Tournoi supprimé']);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405);
