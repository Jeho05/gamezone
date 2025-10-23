<?php
// api/tournaments/register.php
// Inscription à un tournoi

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

require_method(['POST']);
$user = require_auth();
$pdo = get_db();

$input = get_json_input();
$tournamentId = (int)($input['tournament_id'] ?? 0);
$teamName = trim($input['team_name'] ?? '');

if ($tournamentId <= 0) {
    json_response(['error' => 'tournament_id requis'], 400);
}

$pdo->beginTransaction();

try {
    // Vérifier que le tournoi existe et est ouvert aux inscriptions
    $stmt = $pdo->prepare('SELECT * FROM tournaments WHERE id = ? FOR UPDATE');
    $stmt->execute([$tournamentId]);
    $tournament = $stmt->fetch();
    
    if (!$tournament) {
        $pdo->rollBack();
        json_response(['error' => 'Tournoi non trouvé'], 404);
    }
    
    // Vérifier le statut
    if (!in_array($tournament['status'], ['upcoming', 'registration_open'])) {
        $pdo->rollBack();
        json_response(['error' => 'Les inscriptions ne sont pas ouvertes pour ce tournoi'], 400);
    }
    
    // Vérifier la date limite
    if ($tournament['registration_deadline']) {
        $deadline = new DateTime($tournament['registration_deadline']);
        if ($deadline < new DateTime()) {
            $pdo->rollBack();
            json_response(['error' => 'La date limite d\'inscription est dépassée'], 400);
        }
    }
    
    // Vérifier si déjà inscrit
    $stmt = $pdo->prepare('SELECT id FROM tournament_participants WHERE tournament_id = ? AND user_id = ?');
    $stmt->execute([$tournamentId, $user['id']]);
    if ($stmt->fetch()) {
        json_response(['error' => 'Vous êtes déjà inscrit à ce tournoi'], 400);
    }
    
    // Vérifier et débiter les points d'inscription si nécessaire
    $entryFee = (int)$tournament['entry_fee'];
    if ($entryFee > 0) {
        // Vérifier que l'utilisateur a assez de points
        $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ? FOR UPDATE');
        $count = $stmt->fetchColumn();
        
        if ($count >= $tournament['max_participants']) {
            json_response(['error' => 'Le tournoi est complet'], 400);
        }
    }
    
    // Vérifier les frais d'entrée
    if ($tournament['entry_fee'] > 0) {
        $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $userPoints = $stmt->fetchColumn();
        
        if ($userPoints < $tournament['entry_fee']) {
            json_response(['error' => 'Points insuffisants pour l\'inscription'], 400);
        }
        
        // Débiter les points
        $stmt = $pdo->prepare('UPDATE users SET points = points - ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$tournament['entry_fee'], now(), $user['id']]);
        
        // Enregistrer la transaction
        $stmt = $pdo->prepare('
            INSERT INTO points_transactions (user_id, change_amount, reason, type, reference_type, reference_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $user['id'],
            -$tournament['entry_fee'],
            "Inscription tournoi: {$tournament['name']}",
            'tournament_entry',
            'tournament',
            $tournamentId,
            now()
        ]);
    }
    
    // Inscription
    $stmt = $pdo->prepare('
        INSERT INTO tournament_participants (tournament_id, user_id, team_name, status, registered_at)
        VALUES (?, ?, ?, "registered", ?)
    ');
    $stmt->execute([$tournamentId, $user['id'], $teamName, now()]);
    
    $pdo->commit();
    
    json_response([
        'success' => true,
        'message' => 'Inscription réussie au tournoi !',
        'tournament' => $tournament
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    json_response(['error' => 'Erreur lors de l\'inscription', 'details' => $e->getMessage()], 500);
}
