<?php
// Test script to verify statistics API returns real data
// Access: http://localhost/projet%20ismo/api/admin/test_stats.php

require_once __DIR__ . '/../config.php';

$db = get_db();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Statistiques</title>";
echo "<style>body{font-family:sans-serif;padding:20px;background:#f5f5f5;}";
echo "table{border-collapse:collapse;width:100%;background:white;margin:10px 0;}";
echo "th,td{border:1px solid #ddd;padding:12px;text-align:left;}";
echo "th{background:#6366f1;color:white;}";
echo "h2{color:#333;border-bottom:2px solid #6366f1;padding-bottom:5px;}";
echo ".success{color:#10b981;font-weight:bold;}";
echo ".error{color:#ef4444;font-weight:bold;}";
echo "</style></head><body>";

echo "<h1>🔍 Test des Statistiques - Données Réelles</h1>";

// Test 1: Users
echo "<h2>1. Utilisateurs</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Total utilisateurs: <strong>{$totalUsers}</strong></p>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE last_active >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $activeUsers = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Utilisateurs actifs (30j): <strong>{$activeUsers}</strong></p>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $newUsers = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Nouveaux utilisateurs (7j): <strong>{$newUsers}</strong></p>";
    
    // Show sample users
    echo "<h3>Échantillon d'utilisateurs:</h3>";
    $stmt = $db->query("SELECT id, username, email, points, level, status, created_at FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>ID</th><th>Username</th><th>Email</th><th>Points</th><th>Niveau</th><th>Statut</th><th>Créé le</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td><strong>{$user['points']}</strong></td>";
        echo "<td>{$user['level']}</td>";
        echo "<td>{$user['status']}</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur: " . $e->getMessage() . "</p>";
}

// Test 2: Events
echo "<h2>2. Événements</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM events");
    $totalEvents = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Total événements: <strong>{$totalEvents}</strong></p>";
    
    $stmt = $db->query("SELECT type, COUNT(*) as count FROM events GROUP BY type");
    $eventsByType = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Par type:</h3><table><tr><th>Type</th><th>Nombre</th></tr>";
    foreach ($eventsByType as $row) {
        echo "<tr><td>{$row['type']}</td><td><strong>{$row['count']}</strong></td></tr>";
    }
    echo "</table>";
    
    // Show sample events
    echo "<h3>Derniers événements:</h3>";
    $stmt = $db->query("SELECT id, title, type, date, participants, created_at FROM events ORDER BY created_at DESC LIMIT 5");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>ID</th><th>Titre</th><th>Type</th><th>Date</th><th>Participants</th><th>Créé le</th></tr>";
    foreach ($events as $event) {
        echo "<tr>";
        echo "<td>{$event['id']}</td>";
        echo "<td>{$event['title']}</td>";
        echo "<td>{$event['type']}</td>";
        echo "<td>{$event['date']}</td>";
        echo "<td>{$event['participants']}</td>";
        echo "<td>{$event['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur: " . $e->getMessage() . "</p>";
}

// Test 3: Gallery
echo "<h2>3. Galerie</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'gallery'");
    if ($stmt->fetch()) {
        $stmt = $db->query("SELECT COUNT(*) as total FROM gallery");
        $totalGallery = $stmt->fetch()['total'];
        echo "<p class='success'>✓ Total images galerie: <strong>{$totalGallery}</strong></p>";
    } else {
        echo "<p class='error'>⚠ Table 'gallery' n'existe pas encore</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur: " . $e->getMessage() . "</p>";
}

// Test 4: Points
echo "<h2>4. Points & Transactions</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM points_transactions");
    $totalTransactions = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Total transactions: <strong>{$totalTransactions}</strong></p>";
    
    $stmt = $db->query("SELECT COALESCE(SUM(change_amount), 0) as total FROM points_transactions WHERE change_amount > 0");
    $pointsEarned = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Points distribués: <strong>{$pointsEarned}</strong></p>";
    
    $stmt = $db->query("SELECT COALESCE(SUM(ABS(change_amount)), 0) as total FROM points_transactions WHERE change_amount < 0");
    $pointsSpent = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Points dépensés: <strong>{$pointsSpent}</strong></p>";
    
    // Show recent transactions
    echo "<h3>Dernières transactions:</h3>";
    $stmt = $db->query("
        SELECT pt.*, u.username 
        FROM points_transactions pt 
        LEFT JOIN users u ON pt.user_id = u.id 
        ORDER BY pt.created_at DESC 
        LIMIT 10
    ");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>ID</th><th>Utilisateur</th><th>Montant</th><th>Raison</th><th>Type</th><th>Date</th></tr>";
    foreach ($transactions as $t) {
        $color = $t['change_amount'] > 0 ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$t['id']}</td>";
        echo "<td>{$t['username']}</td>";
        echo "<td style='color:{$color};'><strong>" . ($t['change_amount'] > 0 ? '+' : '') . "{$t['change_amount']}</strong></td>";
        echo "<td>{$t['reason']}</td>";
        echo "<td>{$t['type']}</td>";
        echo "<td>{$t['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur: " . $e->getMessage() . "</p>";
}

// Test 5: Rewards
echo "<h2>5. Récompenses</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'reward_redemptions'");
    if ($stmt->fetch()) {
        $stmt = $db->query("SELECT COUNT(*) as total FROM reward_redemptions");
        $totalRewards = $stmt->fetch()['total'];
        echo "<p class='success'>✓ Total récompenses réclamées: <strong>{$totalRewards}</strong></p>";
    } else {
        echo "<p class='error'>⚠ Table 'reward_redemptions' n'existe pas</p>";
    }
    
    $stmt = $db->query("SHOW TABLES LIKE 'rewards'");
    if ($stmt->fetch()) {
        $stmt = $db->query("SELECT COUNT(*) as total FROM rewards WHERE available = 1");
        $availableRewards = $stmt->fetch()['total'];
        echo "<p class='success'>✓ Récompenses disponibles: <strong>{$availableRewards}</strong></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur: " . $e->getMessage() . "</p>";
}

// Test 6: Sanctions
echo "<h2>6. Sanctions</h2>";
try {
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM points_transactions 
        WHERE type = 'adjustment' 
        AND change_amount < 0 
        AND reason LIKE '%SANCTION%'
    ");
    $totalSanctions = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Total sanctions appliquées: <strong>{$totalSanctions}</strong></p>";
    
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM points_transactions 
        WHERE type = 'adjustment' 
        AND change_amount < 0 
        AND reason LIKE '%SANCTION%'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $recentSanctions = $stmt->fetch()['total'];
    echo "<p class='success'>✓ Sanctions récentes (30j): <strong>{$recentSanctions}</strong></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur: " . $e->getMessage() . "</p>";
}

// Test 7: Top Players
echo "<h2>7. Top Joueurs</h2>";
try {
    $stmt = $db->query("
        SELECT id, username, email, points, level 
        FROM users 
        WHERE role = 'player'
        ORDER BY points DESC 
        LIMIT 10
    ");
    $topPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>Rang</th><th>Username</th><th>Email</th><th>Points</th><th>Niveau</th></tr>";
    $rank = 1;
    foreach ($topPlayers as $player) {
        echo "<tr>";
        echo "<td><strong>#{$rank}</strong></td>";
        echo "<td>{$player['username']}</td>";
        echo "<td>{$player['email']}</td>";
        echo "<td><strong>{$player['points']}</strong></td>";
        echo "<td>{$player['level']}</td>";
        echo "</tr>";
        $rank++;
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur: " . $e->getMessage() . "</p>";
}

echo "<hr><p style='text-align:center;color:#666;'>✅ Test terminé - " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
