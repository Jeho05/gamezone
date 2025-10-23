<?php
// api/rewards/create.php
// Create/update rewards (admin only)
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

$pdo = get_db();
$admin = require_auth('admin');

$input = get_json_input();

$id = (int)($input['id'] ?? 0);
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$cost = (int)($input['cost'] ?? 0);
$category = trim($input['category'] ?? '');
$available = isset($input['available']) ? (int)$input['available'] : 1;
$reward_type = trim($input['reward_type'] ?? 'other');
$game_time_minutes = (int)($input['game_time_minutes'] ?? 0);

if ($name === '' || $cost < 0) {
    json_response(['error' => 'Paramètres invalides'], 400);
}

$now = now();

if ($id > 0) {
    // Update existing reward
    $stmt = $pdo->prepare('
        UPDATE rewards 
        SET name = ?, description = ?, cost = ?, category = ?, 
            available = ?, reward_type = ?, game_time_minutes = ?, updated_at = ? 
        WHERE id = ?
    ');
    $stmt->execute([
        $name, 
        $description ?: null, 
        $cost, 
        $category ?: null, 
        $available, 
        $reward_type, 
        $game_time_minutes, 
        $now, 
        $id
    ]);
    
    json_response([
        'success' => true, 
        'message' => 'Récompense mise à jour', 
        'id' => $id
    ]);
} else {
    // Create new reward
    $stmt = $pdo->prepare('
        INSERT INTO rewards (
            name, description, cost, category, available, 
            reward_type, game_time_minutes, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $name, 
        $description ?: null, 
        $cost, 
        $category ?: null, 
        $available, 
        $reward_type, 
        $game_time_minutes, 
        $now, 
        $now
    ]);
    
    $newId = (int)$pdo->lastInsertId();
    
    json_response([
        'success' => true, 
        'message' => 'Récompense créée', 
        'id' => $newId
    ], 201);
}
