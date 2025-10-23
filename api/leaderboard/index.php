<?php
require_once __DIR__ . '/../utils.php';
require_method(['GET']);

$pdo = get_db();
$period = $_GET['period'] ?? 'weekly'; // weekly | monthly | all
$limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));

$now = new DateTime();
if ($period === 'monthly') {
    $start = (new DateTime('first day of this month 00:00:00'));
} elseif ($period === 'weekly') {
    // ISO week: Monday start
    $start = new DateTime('monday this week');
    $start->setTime(0, 0, 0);
} else {
    $start = null;
}

if ($start) {
    $stmt = $pdo->prepare('SELECT u.id, u.username, u.avatar_url, SUM(pt.change_amount) AS points
        FROM users u
        JOIN points_transactions pt ON pt.user_id = u.id
        WHERE pt.created_at >= ?
        GROUP BY u.id, u.username, u.avatar_url
        ORDER BY points DESC
        LIMIT ' . $limit);
    $stmt->execute([$start->format('Y-m-d H:i:s')]);
} else {
    // All time: use users.points
    $stmt = $pdo->query('SELECT id, username, avatar_url, points FROM users ORDER BY points DESC LIMIT ' . $limit);
}
$rows = $stmt->fetchAll();

$current = current_user();
$ranked = [];
$rank = 1;
foreach ($rows as $row) {
    $ranked[] = [
        'rank' => $rank++,
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'avatar_url' => $row['avatar_url'],
        'points' => (int)($row['points'] ?? 0),
        'isCurrentUser' => $current ? ((int)$current['id'] === (int)$row['id']) : false,
    ];
}

// Compute current user rank/points if logged in
$currentUserRank = null;
$currentUserPoints = null;
if ($current) {
    $uid = (int)$current['id'];
    if ($start) {
        // Period-based points
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(change_amount), 0) FROM points_transactions WHERE user_id = ? AND created_at >= ?');
        $stmt->execute([$uid, $start->format('Y-m-d H:i:s')]);
        $currentUserPoints = (int)$stmt->fetchColumn();

        // Rank = count of users with higher sum + 1
        $stmt = $pdo->prepare('SELECT COUNT(*) + 1 AS rnk FROM (
            SELECT pt.user_id, SUM(pt.change_amount) AS s
            FROM points_transactions pt
            WHERE pt.created_at >= ?
            GROUP BY pt.user_id
        ) agg WHERE agg.s > ?');
        $stmt->execute([$start->format('Y-m-d H:i:s'), $currentUserPoints]);
        $currentUserRank = (int)$stmt->fetchColumn();
    } else {
        // All-time using users.points
        $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
        $stmt->execute([$uid]);
        $currentUserPoints = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) + 1 FROM users WHERE points > ?');
        $stmt->execute([$currentUserPoints]);
        $currentUserRank = (int)$stmt->fetchColumn();
    }
}

json_response(['period' => $period, 'items' => $ranked, 'current_user_rank' => $currentUserRank, 'current_user_points' => $currentUserPoints]);
