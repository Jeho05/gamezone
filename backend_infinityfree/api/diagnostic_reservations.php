<?php
// api/diagnostic_reservations.php
// Diagnostic du système de réservations

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');

$pdo = get_db();
$diagnostic = [];

try {
    // 1. Vérifier si les colonnes is_reservable et reservation_fee existent sur games
    $stmt = $pdo->query("SHOW COLUMNS FROM games LIKE 'is_reservable'");
    $diagnostic['games_has_is_reservable'] = $stmt && $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM games LIKE 'reservation_fee'");
    $diagnostic['games_has_reservation_fee'] = $stmt && $stmt->rowCount() > 0;
    
    // 2. Vérifier si la table game_reservations existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'game_reservations'");
    $diagnostic['table_game_reservations_exists'] = $stmt && $stmt->rowCount() > 0;
    
    // 3. Si la table existe, compter les réservations
    if ($diagnostic['table_game_reservations_exists']) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM game_reservations");
        $result = $stmt->fetch();
        $diagnostic['reservations_count'] = (int)$result['count'];
        
        // 4. Compter par statut
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM game_reservations 
            GROUP BY status
        ");
        $diagnostic['reservations_by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    // 5. Vérifier les jeux réservables
    if ($diagnostic['games_has_is_reservable']) {
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM games 
            WHERE is_reservable = 1 AND is_active = 1
        ");
        $result = $stmt->fetch();
        $diagnostic['reservable_games_count'] = (int)$result['count'];
        
        // Liste des jeux réservables
        $stmt = $pdo->query("
            SELECT id, name, slug, reservation_fee 
            FROM games 
            WHERE is_reservable = 1 AND is_active = 1
        ");
        $diagnostic['reservable_games'] = $stmt->fetchAll();
    }
    
    // 6. Vérifier les endpoints de l'API
    $apiEndpoints = [
        'create_purchase' => file_exists(__DIR__ . '/shop/create_purchase.php'),
        'check_availability' => file_exists(__DIR__ . '/shop/check_availability.php'),
        'my_reservations' => file_exists(__DIR__ . '/shop/my_reservations.php'),
        'payment_callback' => file_exists(__DIR__ . '/shop/payment_callback.php'),
    ];
    $diagnostic['api_endpoints'] = $apiEndpoints;
    
    // 7. Vérifier la migration
    $migrationFile = __DIR__ . '/migrations/add_reservations_system.sql';
    $diagnostic['migration_file_exists'] = file_exists($migrationFile);
    
    // 8. Résumé
    $diagnostic['migration_applied'] = 
        $diagnostic['games_has_is_reservable'] && 
        $diagnostic['games_has_reservation_fee'] && 
        $diagnostic['table_game_reservations_exists'];
    
    $diagnostic['system_ready'] = 
        $diagnostic['migration_applied'] && 
        count(array_filter($apiEndpoints)) === count($apiEndpoints);
    
    echo json_encode($diagnostic, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Erreur lors du diagnostic',
        'details' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
