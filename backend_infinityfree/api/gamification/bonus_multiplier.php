<?php
// api/gamification/bonus_multiplier.php
// Award special bonus multipliers (admin only)
require_once __DIR__ . '/../utils.php';
require_method(['POST', 'GET', 'DELETE']);

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get active multipliers for a user
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
    if (!$userId) {
        $auth = require_auth();
        $userId = (int)$auth['id'];
    }
    
    $stmt = $pdo->prepare('SELECT id, multiplier, reason, expires_at, created_at FROM bonus_multipliers WHERE user_id = ? AND expires_at > NOW() ORDER BY multiplier DESC');
    $stmt->execute([$userId]);
    $multipliers = $stmt->fetchAll();
    
    json_response(['multipliers' => $multipliers]);
}

if ($method === 'POST') {
    // Create multiplier (admin only)
    $admin = require_auth('admin');
    $input = get_json_input();
    
    $userId = (int)($input['user_id'] ?? 0);
    $multiplier = (float)($input['multiplier'] ?? 1.0);
    $reason = trim($input['reason'] ?? '');
    $durationHours = (int)($input['duration_hours'] ?? 24);
    
    if ($userId <= 0 || $multiplier < 1.0 || $multiplier > 10.0) {
        json_response(['error' => 'Paramètres invalides'], 400);
    }
    
    if ($reason === '') {
        $reason = "Multiplicateur de bonus x{$multiplier}";
    }
    
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationHours} hours"));
    
    $stmt = $pdo->prepare('INSERT INTO bonus_multipliers (user_id, multiplier, reason, expires_at, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $multiplier, $reason, $expiresAt, now()]);
    $id = (int)$pdo->lastInsertId();
    
    json_response([
        'message' => 'Multiplicateur créé',
        'id' => $id,
        'multiplier' => $multiplier,
        'expires_at' => $expiresAt
    ], 201);
}

if ($method === 'DELETE') {
    // Delete multiplier (admin only)
    $admin = require_auth('admin');
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        json_response(['error' => 'ID manquant'], 400);
    }
    
    $stmt = $pdo->prepare('DELETE FROM bonus_multipliers WHERE id = ?');
    $stmt->execute([$id]);
    
    json_response(['message' => 'Multiplicateur supprimé']);
}
