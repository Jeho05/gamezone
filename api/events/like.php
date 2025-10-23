<?php
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

$user = require_auth();
$input = get_json_input();
$eventId = (int)($input['event_id'] ?? 0);
if ($eventId <= 0) {
    json_response(['error' => 'Paramètre event_id manquant'], 400);
}

$pdo = get_db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('UPDATE events SET likes = likes + 1 WHERE id = ?');
    $stmt->execute([$eventId]);

    $stmt = $pdo->prepare('SELECT id, likes FROM events WHERE id = ?');
    $stmt->execute([$eventId]);
    $row = $stmt->fetch();
    if (!$row) {
        $pdo->rollBack();
        json_response(['error' => 'Événement introuvable'], 404);
    }

    $pdo->commit();
    json_response(['message' => 'Like enregistré', 'event' => ['id' => (int)$row['id'], 'likes' => (int)$row['likes']]]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['error' => 'Échec du like', 'details' => $e->getMessage()], 500);
}
