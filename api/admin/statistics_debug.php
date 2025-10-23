<?php
// api/admin/statistics_debug.php
// Version de debug qui affiche les erreurs détaillées

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug Statistics API</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .error{color:red;} .success{color:green;} .info{color:blue;}</style>";

try {
    echo "<p class='info'>Chargement de config.php...</p>";
    require_once __DIR__ . '/../config.php';
    echo "<p class='success'>✓ Config chargé</p>";
    
    echo "<p class='info'>Chargement de auth_check.php...</p>";
    require_once __DIR__ . '/auth_check.php';
    echo "<p class='success'>✓ Auth check chargé</p>";
    
    echo "<p class='info'>Vérification de l'admin...</p>";
    // Skip auth for debug
    // $admin = require_admin();
    echo "<p class='success'>✓ Auth skipped for debug</p>";
    
    echo "<p class='info'>Connexion à la base de données...</p>";
    $db = get_db();
    echo "<p class='success'>✓ Connexion DB OK</p>";

    echo "<h2>Test des requêtes:</h2>";
    
    // Get total users
    echo "<p class='info'>SELECT COUNT(*) as total FROM users</p>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Total users: $totalUsers</p>";
    
    // Get active users (last 30 days)
    echo "<p class='info'>SELECT COUNT(*) FROM users WHERE last_active >= DATE_SUB(NOW(), INTERVAL 30 DAY)</p>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE last_active >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $activeUsers = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Active users: $activeUsers</p>";
    
    // Get new users (last 7 days)
    echo "<p class='info'>SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)</p>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $newUsers = $stmt->fetch()['total'];
    echo "<p class='success'>✓ New users: $newUsers</p>";
    
    // Get total events
    echo "<p class='info'>SELECT COUNT(*) as total FROM events</p>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM events");
    $totalEvents = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Total events: $totalEvents</p>";
    
    // Get events by type
    echo "<p class='info'>SELECT type, COUNT(*) as count FROM events GROUP BY type</p>";
    $stmt = $db->query("SELECT type, COUNT(*) as count FROM events GROUP BY type");
    $eventsByType = [];
    while ($row = $stmt->fetch()) {
        $eventsByType[$row['type']] = (int)$row['count'];
        echo "<p class='success'>  → {$row['type']}: {$row['count']}</p>";
    }
    
    // Check if gallery table exists
    echo "<p class='info'>SHOW TABLES LIKE 'gallery'</p>";
    $stmt = $db->query("SHOW TABLES LIKE 'gallery'");
    $galleryExists = $stmt->fetch();
    
    if ($galleryExists) {
        $stmt = $db->query("SELECT COUNT(*) as total FROM gallery");
        $totalGallery = $stmt->fetch()['total'];
        echo "<p class='success'>✓ Gallery exists: $totalGallery images</p>";
    } else {
        $totalGallery = 0;
        echo "<p class='info'>⚠ Table gallery n'existe pas</p>";
    }
    
    // Get total points distributed
    echo "<p class='info'>SELECT COALESCE(SUM(change_amount), 0) as total FROM points_transactions WHERE change_amount > 0</p>";
    $stmt = $db->query("SELECT COALESCE(SUM(change_amount), 0) as total FROM points_transactions WHERE change_amount > 0");
    $totalPointsDistributed = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Points distributed: $totalPointsDistributed</p>";
    
    // Check if reward_redemptions table exists
    echo "<p class='info'>SHOW TABLES LIKE 'reward_redemptions'</p>";
    $stmt = $db->query("SHOW TABLES LIKE 'reward_redemptions'");
    $rewardsExists = $stmt->fetch();
    
    if ($rewardsExists) {
        $stmt = $db->query("SELECT COUNT(*) as total FROM reward_redemptions");
        $totalRewardsClaimed = $stmt->fetch()['total'];
        echo "<p class='success'>✓ Rewards claimed: $totalRewardsClaimed</p>";
    } else {
        $totalRewardsClaimed = 0;
        echo "<p class='info'>⚠ Table reward_redemptions n'existe pas</p>";
    }
    
    // Count sanctions
    echo "<p class='info'>SELECT COUNT(*) FROM points_transactions WHERE type = 'adjustment' AND change_amount < 0 AND reason LIKE '%SANCTION%'</p>";
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM points_transactions 
        WHERE type = 'adjustment' 
        AND change_amount < 0 
        AND reason LIKE '%SANCTION%'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $activeSanctions = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Active sanctions: $activeSanctions</p>";
    
    // Get recent events
    echo "<p class='info'>SELECT e.* FROM events e ORDER BY e.created_at DESC LIMIT 10</p>";
    $stmt = $db->query("
        SELECT e.*
        FROM events e
        ORDER BY e.created_at DESC
        LIMIT 10
    ");
    $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p class='success'>✓ Recent events: " . count($recentEvents) . "</p>";
    
    // Get top users
    echo "<p class='info'>SELECT id, username, email, points, level, avatar_url FROM users WHERE role = 'player' ORDER BY points DESC LIMIT 5</p>";
    $stmt = $db->query("
        SELECT id, username, email, points, level, avatar_url
        FROM users
        WHERE role = 'player'
        ORDER BY points DESC
        LIMIT 5
    ");
    $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p class='success'>✓ Top users: " . count($topUsers) . "</p>";
    
    echo "<h2 class='success'>✅ TOUS LES TESTS RÉUSSIS!</h2>";
    
    echo "<h3>Résumé des données:</h3>";
    echo "<pre>";
    echo json_encode([
        'success' => true,
        'statistics' => [
            'users' => [
                'total' => (int)$totalUsers,
                'active' => (int)$activeUsers,
                'new' => (int)$newUsers
            ],
            'events' => [
                'total' => (int)$totalEvents,
                'byType' => $eventsByType
            ],
            'gallery' => [
                'total' => (int)$totalGallery
            ],
            'gamification' => [
                'totalPointsDistributed' => (int)$totalPointsDistributed,
                'rewardsClaimed' => (int)$totalRewardsClaimed,
                'activeSanctions' => (int)$activeSanctions
            ]
        ],
        'recentEvents' => $recentEvents,
        'topUsers' => $topUsers
    ], JSON_PRETTY_PRINT);
    echo "</pre>";
    
    echo "<hr>";
    echo "<p class='success'><strong>Si vous voyez ce message, statistics.php DEVRAIT fonctionner.</strong></p>";
    echo "<p>Le problème vient probablement de l'authentification ou d'une erreur lors de l'exécution automatique de ensure_tables_exist().</p>";
    
} catch (PDOException $e) {
    echo "<h2 class='error'>❌ ERREUR BASE DE DONNÉES</h2>";
    echo "<p class='error'><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p class='error'><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "<pre class='error'>" . $e->getTraceAsString() . "</pre>";
    
    echo "<hr>";
    echo "<h3>Solutions possibles:</h3>";
    echo "<ul>";
    echo "<li>Vérifiez que MySQL est démarré dans XAMPP</li>";
    echo "<li>Vérifiez que la base 'gamezone' existe</li>";
    echo "<li>Importez le fichier schema.sql si la base n'existe pas</li>";
    echo "<li>Vérifiez les credentials dans config.php (host, user, password)</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>❌ ERREUR GÉNÉRALE</h2>";
    echo "<p class='error'><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p class='error'><strong>Fichier:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre class='error'>" . $e->getTraceAsString() . "</pre>";
}
