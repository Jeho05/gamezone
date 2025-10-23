<?php
// api/shop/my_reservations.php
// Liste les réservations de l'utilisateur connecté

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

require_method(['GET']);

$status = $_GET['status'] ?? ''; // pending_payment, paid, cancelled, completed, no_show
$limit = min((int)($_GET['limit'] ?? 20), 50);
$offset = (int)($_GET['offset'] ?? 0);

try {
    $sql = '
        SELECT r.*,
               g.name as game_name, g.slug as game_slug, g.image_url as game_image,
               p.payment_status, p.price as purchase_price, p.currency as purchase_currency
        FROM game_reservations r
        INNER JOIN games g ON r.game_id = g.id
        LEFT JOIN purchases p ON r.purchase_id = p.id
        WHERE r.user_id = ?
    ';
    $params = [$user['id']];

    if ($status) {
        $sql .= ' AND r.status = ?';
        $params[] = $status;
    }

    $sql .= ' ORDER BY r.scheduled_start DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Enrichissement: temps restant avant le début et fin
    $nowTs = time();
    foreach ($rows as &$row) {
        $startTs = strtotime($row['scheduled_start']);
        $endTs = strtotime($row['scheduled_end']);
        $row['minutes_until_start'] = (int) floor(($startTs - $nowTs) / 60);
        $row['minutes_until_end'] = (int) floor(($endTs - $nowTs) / 60);
        $row['is_window_open'] = $nowTs >= $startTs && $nowTs <= $endTs;
    }

    // Statistiques de l'utilisateur
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid,
               SUM(CASE WHEN status = "pending_payment" THEN 1 ELSE 0 END) as pending_payment,
               SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
               SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
        FROM game_reservations
        WHERE user_id = ?
    ');
    $stmt->execute([$user['id']]);
    $stats = $stmt->fetch();

    json_response([
        'reservations' => $rows,
        'stats' => $stats,
        'count' => count($rows),
        'limit' => $limit,
        'offset' => $offset
    ]);
} catch (Exception $e) {
    json_response(['error' => 'Erreur lors du chargement des réservations', 'details' => $e->getMessage()], 500);
}
