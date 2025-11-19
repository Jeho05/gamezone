<?php
// api/shop/check_availability.php
// Vérifie si un créneau est disponible pour un jeu donné (optionnellement via un package)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();

require_method(['GET']);

$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
$packageId = isset($_GET['package_id']) ? (int)$_GET['package_id'] : null;
$durationMinutes = isset($_GET['duration_minutes']) ? (int)$_GET['duration_minutes'] : null;
$scheduledStartRaw = $_GET['scheduled_start'] ?? '';

if (!$gameId || !$scheduledStartRaw || (!$packageId && !$durationMinutes)) {
    json_response([
        'error' => 'Paramètres requis: game_id, scheduled_start, (package_id ou duration_minutes)'
    ], 400);
}

try {
    // Vérifier le jeu et la réservation
    $stmt = $pdo->prepare('SELECT id, name, is_reservable, reservation_fee FROM games WHERE id = ? AND is_active = 1');
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    if (!$game) {
        json_response(['error' => 'Jeu non trouvé ou indisponible'], 404);
    }
    if ((int)$game['is_reservable'] !== 1) {
        json_response(['error' => 'Ce jeu ne peut pas être réservé'], 400);
    }

    // Déterminer la durée
    if ($packageId) {
        $stmt = $pdo->prepare('SELECT duration_minutes FROM game_packages WHERE id = ? AND game_id = ? AND is_active = 1');
        $stmt->execute([$packageId, $gameId]);
        $pkg = $stmt->fetch();
        if (!$pkg) {
            json_response(['error' => 'Package non trouvé ou indisponible pour ce jeu'], 404);
        }
        $durationMinutes = (int)$pkg['duration_minutes'];
    }

    // Parser la date
    try {
        $scheduledStart = new DateTime($scheduledStartRaw);
    } catch (Exception $e) {
        json_response(['error' => 'Format de date invalide pour scheduled_start (attendu: ISO ou Y-m-d H:i:s)'], 400);
    }
    $scheduledEnd = (clone $scheduledStart)->modify('+' . $durationMinutes . ' minutes');

    // Vérifier les conflits sur les réservations
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as cnt
        FROM game_reservations
        WHERE game_id = ?
          AND status IN ("pending_payment", "paid")
          AND NOT (scheduled_end <= ? OR scheduled_start >= ?)
    ');
    $stmt->execute([$gameId, $scheduledStart->format('Y-m-d H:i:s'), $scheduledEnd->format('Y-m-d H:i:s')]);
    $conflict = (int)($stmt->fetch()['cnt'] ?? 0);

    $available = $conflict === 0;

    json_response([
        'available' => $available,
        'game' => [
            'id' => (int)$game['id'],
            'name' => $game['name'],
            'is_reservable' => (bool)$game['is_reservable'],
            'reservation_fee' => (float)$game['reservation_fee']
        ],
        'slot' => [
            'scheduled_start' => $scheduledStart->format('Y-m-d H:i:s'),
            'scheduled_end' => $scheduledEnd->format('Y-m-d H:i:s'),
            'duration_minutes' => $durationMinutes
        ]
    ]);
} catch (Exception $e) {
    json_response(['error' => 'Erreur lors de la vérification', 'details' => $e->getMessage()], 500);
}
