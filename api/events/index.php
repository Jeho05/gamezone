<?php
require_once __DIR__ . '/../utils.php';
require_method(['GET', 'POST']);

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $type = $_GET['type'] ?? '';
    $where = '';
    $params = [];
    if ($type !== '') {
        $where = 'WHERE type = ?';
        $params[] = $type;
    }
    $stmt = $pdo->prepare("SELECT id, title, date, type, image_url, participants, winner, description, likes, comments FROM events $where ORDER BY date DESC, id DESC");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    json_response(['items' => $items]);
}

// Create event (admin)
require_auth('admin');
$input = get_json_input();
$title = trim($input['title'] ?? '');
$date = trim($input['date'] ?? ''); // YYYY-MM-DD
$type = $input['type'] ?? '';
$image_url = $input['image_url'] ?? null;
$participants = isset($input['participants']) ? (int)$input['participants'] : null;
$winner = $input['winner'] ?? null;
$description = $input['description'] ?? null;

$allowedTypes = ['tournament', 'event', 'stream', 'news'];
if ($title === '' || $date === '' || !in_array($type, $allowedTypes, true)) {
    json_response(['error' => 'Paramètres invalides (title, date, type)'], 400);
}

$stmt = $pdo->prepare('INSERT INTO events (title, date, type, image_url, participants, winner, description, likes, comments, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, ?)');
$stmt->execute([$title, $date, $type, $image_url, $participants, $winner, $description, now()]);
$id = (int)$pdo->lastInsertId();
json_response(['message' => 'Événement créé', 'id' => $id], 201);
