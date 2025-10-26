<?php
// Test direct sans auth pour voir l'erreur
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Direct API Statistics</h1>";

try {
    require_once __DIR__ . '/../config.php';
    echo "<p style='color: green;'>✓ Config chargé</p>";
    
    $db = get_db();
    echo "<p style='color: green;'>✓ Connexion DB OK</p>";
    
    // Test simple
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ Query users OK: {$result['total']} utilisateurs</p>";
    
    // Test qui pourrait échouer
    echo "<h2>Test des tables:</h2>";
    
    $tables = ['users', 'events', 'points_transactions', 'gallery', 'reward_redemptions'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✓ Table '$table' existe</p>";
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p style='color: blue;'>  → $count lignes</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Table '$table' n'existe pas</p>";
        }
    }
    
    echo "<h2>Test des requêtes de statistics.php:</h2>";
    
    // Test exact de statistics.php
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    echo "<p>Total users: $totalUsers</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE last_active >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $activeUsers = $stmt->fetch()['total'];
    echo "<p>Active users: $activeUsers</p>";
    
    echo "<p style='color: green; font-weight: bold;'>✓ TOUT EST OK!</p>";
    echo "<p>Si ce script fonctionne mais pas statistics.php, le problème vient de l'authentification.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ ERREUR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
