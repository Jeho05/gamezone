<?php
// api/reservations/check_availability.php
// Check if a reservation slot is available for a given game and time window

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!in_array($method, ['GET', 'POST'], true)) {
    json_response(['error' => 'Method not allowed'], 405);
}

$params = $method === 'POST' ? get_json_input() : $_GET;
$gameId = isset($params['game_id']) ? (int)$params['game_id'] : 0;
$startStr = trim($params['scheduled_start'] ?? ''); // expected 'YYYY-MM-DD HH:MM'
$duration = isset($params['duration_minutes']) ? (int)$params['duration_minutes'] : 0;
$excludeId = isset($params['exclude_id']) ? (int)$params['exclude_id'] : 0; // optional for edits

if ($gameId <= 0 || $startStr === '' || $duration <= 0) {
    json_response(['error' => 'Paramètres invalides', 'hint' => 'game_id, scheduled_start, duration_minutes requis'], 400);
}

try {
    // Validate game exists and is reservable
    $stmt = $pdo->prepare('SELECT id, is_reservable, reservation_fee FROM games WHERE id = ?');
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    if (!$game) {
        json_response(['error' => 'Jeu non trouvé'], 404);
    }
    if ((int)$game['is_reservable'] !== 1) {
        json_response(['available' => false, 'reason' => 'Ce jeu n\'est pas réservable']);
    }

    // Parse times
    $start = new DateTime($startStr);
    $end = clone $start;
    $end->modify("+{$duration} minutes");

    // Enforce window 08:00 - 23:00 on the start day
    $dayStart = (clone $start)->setTime(8, 0, 0);
    $dayEnd = (clone $start)->setTime(23, 0, 0);
    if ($start < $dayStart || $end > $dayEnd) {
        json_response(['available' => false, 'reason' => 'Réservations disponibles uniquement entre 08:00 et 23:00']);
    }

    // Overlap check: same game, overlapping time, excluding cancelled; optionally exclude an existing id
    $sql = 'SELECT COUNT(*) as cnt FROM game_reservations 
            WHERE game_id = ?
              AND status IN ("pending_payment","paid","completed","no_show")
              AND scheduled_start < ?
              AND scheduled_end   > ?';
    $params = [$gameId, $end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')];
    if ($excludeId > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $overlap = (int)($stmt->fetch()['cnt'] ?? 0) > 0;

    if ($overlap) {
        json_response(['available' => false, 'reason' => 'Créneau déjà réservé pour ce jeu']);
    }

    json_response([
        'available' => true,
        'scheduled_start' => $start->format('Y-m-d H:i:s'),
        'scheduled_end' => $end->format('Y-m-d H:i:s'),
        'reservation_fee' => (float)$game['reservation_fee']
    ]);
} catch (Throwable $e) {
    json_response(['error' => 'Erreur serveur', 'details' => $e->getMessage()], 500);
}
