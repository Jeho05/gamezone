<?php
// api/admin/tournaments.php
// Gestion complète des tournois par l'admin
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer les tournois
// ============================================================================
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer un tournoi spécifique avec participants
        $stmt = $pdo->prepare('
            SELECT t.*, 
                   u.username as created_by_name,
                   g.name as game_name,
                   (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id) as participants_count,
                   (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id AND status = "confirmed") as confirmed_count
            FROM tournaments t
            LEFT JOIN users u ON t.created_by = u.id
            LEFT JOIN games g ON t.game_id = g.id
            WHERE t.id = ?
        ');
        $stmt->execute([$id]);
        $tournament = $stmt->fetch();
        
        if (!$tournament) {
            json_response(['error' => 'Tournoi non trouvé'], 404);
        }
        
        // Récupérer les participants
        $stmt = $pdo->prepare('
            SELECT tp.*, u.username, u.avatar_url, u.level, u.points
            FROM tournament_participants tp
            INNER JOIN users u ON tp.user_id = u.id
            WHERE tp.tournament_id = ?
            ORDER BY tp.registered_at ASC
        ');
        $stmt->execute([$id]);
        $participants = $stmt->fetchAll();
        
        $tournament['participants'] = $participants;
        
        json_response(['tournament' => $tournament]);
    } else {
        // Récupérer tous les tournois
        $status = $_GET['status'] ?? null; // upcoming, ongoing, completed, cancelled
        
        $sql = '
            SELECT t.*, 
                   u.username as created_by_name,
                   g.name as game_name,
                   (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id) as participants_count,
                   (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id AND status = "confirmed") as confirmed_count
            FROM tournaments t
            LEFT JOIN users u ON t.created_by = u.id
            LEFT JOIN games g ON t.game_id = g.id
            WHERE 1=1
        ';
        $params = [];
        
        if ($status) {
            $sql .= ' AND t.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY t.start_date DESC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tournaments = $stmt->fetchAll();
        
        json_response(['tournaments' => $tournaments, 'count' => count($tournaments)]);
    }
}

// ============================================================================
// POST: Créer un nouveau tournoi
// ============================================================================
if ($method === 'POST') {
    $data = get_json_input();
    
    // Validation
    $required = ['name', 'game_id', 'start_date', 'max_participants', 'entry_fee'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    // Vérifier que le jeu existe
    $stmt = $pdo->prepare('SELECT id, name FROM games WHERE id = ?');
    $stmt->execute([$data['game_id']]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Jeu non trouvé'], 404);
    }
    
    try {
        $ts = now();
        
        $stmt = $pdo->prepare('
            INSERT INTO tournaments (
                name, description, game_id, type,
                max_participants, entry_fee, prize_pool,
                first_place_prize, second_place_prize, third_place_prize,
                start_date, end_date, registration_deadline,
                rules, image_url, stream_url,
                status, is_featured,
                created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['game_id'],
            $data['type'] ?? 'single_elimination',
            $data['max_participants'],
            $data['entry_fee'],
            $data['prize_pool'] ?? 0,
            $data['first_place_prize'] ?? 0,
            $data['second_place_prize'] ?? 0,
            $data['third_place_prize'] ?? 0,
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['registration_deadline'] ?? $data['start_date'],
            $data['rules'] ?? null,
            $data['image_url'] ?? null,
            $data['stream_url'] ?? null,
            $data['status'] ?? 'upcoming',
            $data['is_featured'] ?? 0,
            $user['id'],
            $ts,
            $ts
        ]);
        
        $tournamentId = $pdo->lastInsertId();
        
        json_response([
            'success' => true,
            'message' => 'Tournoi créé avec succès',
            'tournament_id' => $tournamentId
        ], 201);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la création', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// PUT/PATCH: Mettre à jour un tournoi
// ============================================================================
if ($method === 'PUT' || $method === 'PATCH') {
    $data = get_json_input();
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID du tournoi requis'], 400);
    }
    
    // Vérifier que le tournoi existe
    $stmt = $pdo->prepare('SELECT id FROM tournaments WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Tournoi non trouvé'], 404);
    }
    
    // Construire la requête de mise à jour
    $updateFields = [];
    $params = [];
    
    $allowedFields = [
        'name', 'description', 'game_id', 'type', 'max_participants',
        'entry_fee', 'prize_pool', 'first_place_prize', 'second_place_prize', 'third_place_prize',
        'start_date', 'end_date', 'registration_deadline', 'rules',
        'image_url', 'stream_url', 'status', 'is_featured', 'winner_id'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($updateFields)) {
        json_response(['error' => 'Aucune donnée à mettre à jour'], 400);
    }
    
    $updateFields[] = 'updated_at = ?';
    $params[] = now();
    $params[] = $id;
    
    try {
        $sql = 'UPDATE tournaments SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json_response([
            'success' => true,
            'message' => 'Tournoi mis à jour avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// DELETE: Supprimer un tournoi
// ============================================================================
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID du tournoi requis'], 400);
    }
    
    // Vérifier s'il y a des participants
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM tournament_participants WHERE tournament_id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        json_response([
            'error' => 'Impossible de supprimer ce tournoi car il a des participants',
            'suggestion' => 'Annulez le tournoi plutôt que de le supprimer'
        ], 400);
    }
    
    try {
        $stmt = $pdo->prepare('DELETE FROM tournaments WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response([
            'success' => true,
            'message' => 'Tournoi supprimé avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
