<?php
// api/admin/points_rules.php
// Manage points rules (admin only)
require_once __DIR__ . '/../utils.php';

$admin = require_auth('admin');
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get all points rules
    $stmt = $pdo->query('SELECT * FROM points_rules ORDER BY action_type ASC');
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    json_response(['rules' => $rules]);
}

if ($method === 'PUT') {
    // Update a points rule
    $input = get_json_input();
    $id = (int)($input['id'] ?? 0);
    
    if ($id <= 0) {
        json_response(['error' => 'ID manquant'], 400);
    }
    
    $updates = [];
    $params = [];
    
    if (isset($input['points_amount'])) {
        $updates[] = 'points_amount = ?';
        $params[] = (int)$input['points_amount'];
    }
    
    if (isset($input['is_active'])) {
        $updates[] = 'is_active = ?';
        $params[] = (int)$input['is_active'];
    }
    
    if (isset($input['description'])) {
        $updates[] = 'description = ?';
        $params[] = trim($input['description']);
    }
    
    if (empty($updates)) {
        json_response(['error' => 'Aucune modification'], 400);
    }
    
    $updates[] = 'updated_at = ?';
    $params[] = now();
    $params[] = $id;
    
    $sql = 'UPDATE points_rules SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    json_response(['message' => 'Règle mise à jour']);
}

if ($method === 'POST') {
    // Create a new points rule (advanced feature)
    $input = get_json_input();
    
    $actionType = trim($input['action_type'] ?? '');
    $pointsAmount = (int)($input['points_amount'] ?? 0);
    $description = trim($input['description'] ?? '');
    $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;
    
    if ($actionType === '' || $pointsAmount < 0) {
        json_response(['error' => 'Paramètres invalides'], 400);
    }
    
    $stmt = $pdo->prepare('INSERT INTO points_rules (action_type, points_amount, description, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$actionType, $pointsAmount, $description, $isActive, now(), now()]);
    
    $id = (int)$pdo->lastInsertId();
    json_response(['message' => 'Règle créée', 'id' => $id], 201);
}
