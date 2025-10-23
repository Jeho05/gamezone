<?php
// api/admin/bonus_multipliers.php
// Get all active bonus multipliers (admin only)
require_once __DIR__ . '/../utils.php';

require_auth('admin');
$pdo = get_db();

// Get all active multipliers
$stmt = $pdo->query('
    SELECT bm.*, u.username 
    FROM bonus_multipliers bm
    LEFT JOIN users u ON bm.user_id = u.id
    WHERE bm.expires_at > NOW()
    ORDER BY bm.expires_at ASC
');

$multipliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response(['multipliers' => $multipliers]);
