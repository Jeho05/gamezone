<?php
// api/users/sanction.php
// Apply sanctions to users
require_once __DIR__ . '/../utils.php';

require_method(['POST']);
$admin = require_auth('admin');
$pdo = get_db();

$input = get_json_input();
$user_id = (int)($input['user_id'] ?? 0);
$reason = trim($input['reason'] ?? '');
$sanction_type = trim($input['sanction_type'] ?? '');

if ($user_id <= 0) {
    json_response(['error' => 'ID utilisateur manquant'], 400);
}

// Prevent admin from sanctioning themselves
if ((int)$admin['id'] === $user_id) {
    json_response(['error' => 'Vous ne pouvez pas vous sanctionner vous-même'], 403);
}

// Check if user exists
$stmt = $pdo->prepare('SELECT id, username, points FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    json_response(['error' => 'Utilisateur introuvable'], 404);
}

// Define sanction types and their point penalties
$sanctions = [
    'warning' => [
        'label' => 'Avertissement',
        'points' => -50,
        'description' => 'Avertissement pour comportement inapproprié'
    ],
    'minor_offense' => [
        'label' => 'Infraction mineure',
        'points' => -100,
        'description' => 'Infraction mineure (langage inapproprié, non-respect des règles mineures)'
    ],
    'major_offense' => [
        'label' => 'Infraction majeure',
        'points' => -250,
        'description' => 'Infraction majeure (triche, comportement abusif)'
    ],
    'cheating' => [
        'label' => 'Triche détectée',
        'points' => -500,
        'description' => 'Utilisation de logiciels de triche ou exploitation de bugs'
    ],
    'harassment' => [
        'label' => 'Harcèlement',
        'points' => -400,
        'description' => 'Harcèlement d\'autres joueurs'
    ],
    'account_sharing' => [
        'label' => 'Partage de compte',
        'points' => -200,
        'description' => 'Partage de compte non autorisé'
    ],
    'spam' => [
        'label' => 'Spam',
        'points' => -75,
        'description' => 'Envoi de messages répétitifs ou non sollicités'
    ],
    'custom' => [
        'label' => 'Sanction personnalisée',
        'points' => (int)($input['custom_points'] ?? -100),
        'description' => $reason ?: 'Sanction administrative personnalisée'
    ]
];

if (!isset($sanctions[$sanction_type])) {
    json_response(['error' => 'Type de sanction invalide'], 400);
}

$sanction = $sanctions[$sanction_type];
$points_change = $sanction['points'];
$description = $reason ?: $sanction['description'];

// Apply the sanction
$new_points = max(0, (int)$user['points'] + $points_change);

// Update user points
$updateStmt = $pdo->prepare('UPDATE users SET points = ?, updated_at = ? WHERE id = ?');
$updateStmt->execute([$new_points, now(), $user_id]);

// Log the sanction in points history
$logStmt = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?)');
$logStmt->execute([
    $user_id,
    $points_change,
    "SANCTION: {$sanction['label']} - {$description}",
    'adjustment',
    $admin['id'],
    now()
]);

json_response([
    'message' => 'Sanction appliquée avec succès',
    'sanction' => [
        'type' => $sanction['label'],
        'points_deducted' => abs($points_change),
        'previous_points' => (int)$user['points'],
        'new_points' => $new_points,
        'description' => $description
    ]
]);
