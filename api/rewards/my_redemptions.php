<?php
// api/rewards/my_redemptions.php
// Liste des échanges de récompenses pour l'utilisateur connecté
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

require_method(['GET']);

$user = require_auth();
$pdo = get_db();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
$offset = ($page - 1) * $limit;

try {
    $tablesCheck = $pdo->query("SHOW TABLES LIKE 'reward_redemptions'");
    $hasRedemptionsTable = $tablesCheck && $tablesCheck->rowCount() > 0;
    $tablesCheck = $pdo->query("SHOW TABLES LIKE 'rewards'");
    $hasRewardsTable = $tablesCheck && $tablesCheck->rowCount() > 0;

    if (!$hasRedemptionsTable || !$hasRewardsTable) {
        log_error('Tables de récompenses manquantes pour my_redemptions', [
            'user_id' => $user['id'] ?? null,
            'has_reward_redemptions' => $hasRedemptionsTable,
            'has_rewards' => $hasRewardsTable,
        ]);

        json_response([
            'success' => false,
            'error' => "Les récompenses ne sont pas encore configurées sur ce serveur",
            'code' => 'REWARDS_SCHEMA_MISSING',
            'items' => [],
            'total' => 0,
            'page' => $page,
            'limit' => $limit,
            'page_count' => 1,
        ], 200);
    }

    // Compter le total
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM reward_redemptions WHERE user_id = ?');
    $countStmt->execute([(int)$user['id']]);
    $total = (int)$countStmt->fetchColumn();

    // Récupérer la liste détaillée
    $stmt = $pdo->prepare('
        SELECT rr.id, rr.reward_id, rr.user_id, rr.cost, rr.status, rr.notes, rr.created_at, rr.updated_at,
               r.name AS reward_name,
               r.description AS reward_description,
               r.reward_type,
               r.category,
               r.game_time_minutes,
               r.game_package_id,
               r.discount_percentage,
               r.discount_game_id,
               g.name AS discount_game_name
        FROM reward_redemptions rr
        INNER JOIN rewards r ON rr.reward_id = r.id
        LEFT JOIN games g ON r.discount_game_id = g.id
        WHERE rr.user_id = ?
        ORDER BY rr.created_at DESC
        LIMIT ? OFFSET ?
    ');

    $stmt->bindValue(1, (int)$user['id'], PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response([
        'success' => true,
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'page_count' => $limit > 0 ? (int)ceil($total / $limit) : 1,
    ]);
} catch (Throwable $e) {
    log_error('Erreur lors de la récupération des récompenses utilisateur', [
        'user_id' => $user['id'] ?? null,
        'error' => $e->getMessage(),
    ]);

    json_response([
        'success' => false,
        'error' => 'Erreur lors du chargement de vos récompenses',
    ], 500);
}
