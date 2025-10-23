<?php
require_once __DIR__ . '/../utils.php';
require_method(['POST', 'DELETE']);

require_auth('admin');
$input = get_json_input();
$id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
if ($id <= 0) {
    json_response(['error' => 'Paramètre id manquant'], 400);
}

$pdo = get_db();
$stmt = $pdo->prepare('DELETE FROM rewards WHERE id = ?');
$stmt->execute([$id]);
json_response(['message' => 'Récompense supprimée', 'id' => $id]);
