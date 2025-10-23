<?php
// api/tournaments/public.php
// API publique pour consulter les tournois
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();
require_method(['GET']);

$id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? 'upcoming'; // upcoming, registration_open, ongoing, completed

if ($id) {
    // Récupérer un tournoi spécifique
    $stmt = $pdo->prepare('
        SELECT t.*, 
               g.name as game_name,
               g.image_url as game_image,
               u.username as organizer,
               (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id AND status IN ("registered", "confirmed")) as participants_count
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
    
    // Récupérer les participants
    $stmt = $pdo->prepare('
        SELECT tp.*, u.username, u.avatar_url, u.level
        FROM tournament_participants tp
        INNER JOIN users u ON tp.user_id = u.id
        WHERE tp.tournament_id = ? AND tp.status IN ("registered", "confirmed", "checked_in")
        ORDER BY tp.registered_at ASC
    ');
    $stmt->execute([$id]);
    $participants = $stmt->fetchAll();
    
    // Vérifier si l'utilisateur actuel est inscrit
    $currentUser = current_user();
    $isRegistered = false;
    if ($currentUser) {
        $stmt = $pdo->prepare('SELECT id FROM tournament_participants WHERE tournament_id = ? AND user_id = ?');
        $stmt->execute([$id, $currentUser['id']]);
        $isRegistered = (bool)$stmt->fetch();
    }
    
    // Calculer les places restantes
    $spotsRemaining = (int)$tournament['max_participants'] - (int)$tournament['participants_count'];
    
    json_response([
        'success' => true,
        'tournament' => $tournament,
        'participants' => $participants,
        'is_registered' => $isRegistered,
        'spots_remaining' => max(0, $spotsRemaining),
        'is_full' => $spotsRemaining <= 0
    ]);
    
} else {
    // Liste des tournois
    $sql = '
        SELECT t.*, 
               g.name as game_name,
               g.image_url as game_image,
               (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id AND status IN ("registered", "confirmed")) as participants_count
        FROM tournaments t
        LEFT JOIN games g ON t.game_id = g.id
        WHERE 1=1
    ';
    $params = [];
    
    if ($status && $status !== 'all') {
        $sql .= ' AND t.status = ?';
        $params[] = $status;
    }
    
    $sql .= ' ORDER BY t.is_featured DESC, t.start_date ASC LIMIT 50';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tournaments = $stmt->fetchAll();
    
    // Enrichir avec info utilisateur
    $currentUser = current_user();
    if ($currentUser) {
        foreach ($tournaments as &$tournament) {
            $stmt = $pdo->prepare('SELECT id FROM tournament_participants WHERE tournament_id = ? AND user_id = ?');
            $stmt->execute([$tournament['id'], $currentUser['id']]);
            $tournament['is_registered'] = (bool)$stmt->fetch();
        }
    }
    
    json_response([
        'success' => true,
        'tournaments' => $tournaments,
        'count' => count($tournaments),
        'status' => $status
    ]);
}
