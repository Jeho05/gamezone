<?php
/**
 * Vérification du système de récompenses avec la structure réelle
 */

$host = 'localhost';
$dbname = 'gamezone';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "=== VÉRIFICATION DU SYSTÈME DE RÉCOMPENSES (Structure Réelle) ===\n\n";
    
    // 1. Statistiques globales
    echo "1. STATISTIQUES GLOBALES:\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rewards WHERE available = 1");
    $activeRewards = $stmt->fetchColumn();
    echo "✓ Récompenses disponibles: {$activeRewards}\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM game_packages WHERE is_active = 1");
    $activePackages = $stmt->fetchColumn();
    echo "✓ Packages de jeu actifs: {$activePackages}\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reward_redemptions");
    $totalRedemptions = $stmt->fetchColumn();
    echo "✓ Total échanges de récompenses: {$totalRedemptions}\n";
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(cost), 0) as total FROM reward_redemptions");
    $totalPointsSpent = $stmt->fetchColumn();
    echo "✓ Points totaux échangés: " . number_format($totalPointsSpent) . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM game_sessions WHERE status = 'active'");
    $activeSessions = $stmt->fetchColumn();
    echo "✓ Sessions actives: {$activeSessions}\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'active' AND role = 'player'");
    $totalPlayers = $stmt->fetchColumn();
    echo "✓ Joueurs actifs: {$totalPlayers}\n\n";
    
    // 2. Récompenses disponibles
    echo "2. RÉCOMPENSES DISPONIBLES:\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.name,
            r.description,
            r.cost,
            r.category,
            r.reward_type,
            r.game_time_minutes,
            r.available,
            r.stock_quantity,
            r.max_per_user,
            r.is_featured
        FROM rewards r
        WHERE r.available = 1
        ORDER BY r.display_order, r.cost
    ");
    
    $rewards = $stmt->fetchAll();
    if (count($rewards) > 0) {
        foreach ($rewards as $reward) {
            echo "  • [{$reward['id']}] {$reward['name']}\n";
            echo "    ├─ Catégorie: {$reward['category']}\n";
            echo "    ├─ Type: {$reward['reward_type']}\n";
            echo "    ├─ Coût: {$reward['cost']} points\n";
            if ($reward['game_time_minutes']) {
                echo "    ├─ Temps de jeu: {$reward['game_time_minutes']} minutes\n";
            }
            if ($reward['stock_quantity']) {
                echo "    ├─ Stock: {$reward['stock_quantity']}\n";
            }
            if ($reward['max_per_user']) {
                echo "    ├─ Max par utilisateur: {$reward['max_per_user']}\n";
            }
            echo "    └─ Vedette: " . ($reward['is_featured'] ? 'Oui' : 'Non') . "\n\n";
        }
    } else {
        echo "  ⚠️  Aucune récompense disponible.\n\n";
    }
    
    // 3. Packages de jeu disponibles
    echo "3. PACKAGES DE JEU:\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            gp.id,
            g.name as game_name,
            gp.name as package_name,
            gp.duration_minutes,
            gp.price,
            gp.points_earned,
            gp.bonus_multiplier,
            gp.is_promotional,
            gp.promotional_label,
            gp.is_active
        FROM game_packages gp
        JOIN games g ON gp.game_id = g.id
        WHERE gp.is_active = 1
        ORDER BY g.name, gp.duration_minutes
    ");
    
    $packages = $stmt->fetchAll();
    if (count($packages) > 0) {
        foreach ($packages as $pkg) {
            echo "  • [{$pkg['id']}] {$pkg['game_name']} - {$pkg['package_name']}\n";
            echo "    ├─ Durée: {$pkg['duration_minutes']} min\n";
            echo "    ├─ Prix: {$pkg['price']} XOF\n";
            echo "    ├─ Points gagnés: {$pkg['points_earned']} pts\n";
            echo "    ├─ Multiplicateur: x{$pkg['bonus_multiplier']}\n";
            if ($pkg['is_promotional']) {
                echo "    ├─ 🎉 PROMO: {$pkg['promotional_label']}\n";
            }
            echo "\n";
        }
    } else {
        echo "  ⚠️  Aucun package disponible.\n\n";
    }
    
    // 4. Utilisateurs et leurs points
    echo "4. TOP 10 UTILISATEURS PAR POINTS:\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.level,
            u.points,
            u.role,
            u.last_active
        FROM users u
        WHERE u.status = 'active' AND u.role = 'player'
        ORDER BY u.points DESC
        LIMIT 10
    ");
    
    $users = $stmt->fetchAll();
    if (count($users) > 0) {
        foreach ($users as $i => $user) {
            $rank = $i + 1;
            $medal = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '  '));
            echo "  {$medal} #{$rank} [{$user['id']}] {$user['username']}\n";
            echo "       └─ Points: {$user['points']} | Niveau: {$user['level']}\n";
        }
    } else {
        echo "  ⚠️  Aucun utilisateur trouvé.\n";
    }
    echo "\n";
    
    // 5. Historique des échanges de récompenses
    echo "5. HISTORIQUE DES ÉCHANGES:\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            rr.id,
            u.username,
            r.name as reward_name,
            rr.cost,
            rr.status,
            rr.created_at
        FROM reward_redemptions rr
        JOIN users u ON rr.user_id = u.id
        JOIN rewards r ON rr.reward_id = r.id
        ORDER BY rr.created_at DESC
        LIMIT 20
    ");
    
    $redemptions = $stmt->fetchAll();
    if (count($redemptions) > 0) {
        foreach ($redemptions as $red) {
            $date = date('d/m/Y H:i', strtotime($red['created_at']));
            $statusEmoji = $red['status'] === 'approved' ? '✅' : 
                          ($red['status'] === 'delivered' ? '🎁' : 
                          ($red['status'] === 'cancelled' ? '❌' : '⏳'));
            echo "  {$statusEmoji} [{$red['id']}] {$red['username']} → {$red['reward_name']}\n";
            echo "       └─ {$red['cost']} pts | {$red['status']} | {$date}\n";
        }
    } else {
        echo "  ℹ️  Aucun échange trouvé.\n";
    }
    echo "\n";
    
    // 6. Sessions de jeu récentes
    echo "6. SESSIONS DE JEU RÉCENTES:\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            gs.id,
            u.username,
            g.name as game_name,
            gs.total_minutes,
            gs.used_minutes,
            gs.status,
            gs.started_at,
            gs.created_at,
            p.payment_status
        FROM game_sessions gs
        JOIN users u ON gs.user_id = u.id
        JOIN games g ON gs.game_id = g.id
        JOIN purchases p ON gs.purchase_id = p.id
        ORDER BY gs.created_at DESC
        LIMIT 15
    ");
    
    $sessions = $stmt->fetchAll();
    if (count($sessions) > 0) {
        foreach ($sessions as $session) {
            $date = date('d/m/Y H:i', strtotime($session['created_at']));
            $progress = $session['total_minutes'] > 0 ? round(($session['used_minutes'] / $session['total_minutes']) * 100) : 0;
            
            $statusEmoji = $session['status'] === 'active' ? '▶️' : 
                          ($session['status'] === 'completed' ? '✅' : 
                          ($session['status'] === 'paused' ? '⏸️' : '⏹️'));
            
            echo "  {$statusEmoji} [{$session['id']}] {$session['username']} → {$session['game_name']}\n";
            echo "       └─ {$session['used_minutes']}/{$session['total_minutes']} min ({$progress}%) | {$session['status']} | {$date}\n";
        }
    } else {
        echo "  ℹ️  Aucune session trouvée.\n";
    }
    echo "\n";
    
    // 7. Transactions de points récentes
    echo "7. TRANSACTIONS DE POINTS RÉCENTES:\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            pt.id,
            u.username,
            pt.transaction_type,
            pt.points,
            pt.description,
            pt.created_at
        FROM points_transactions pt
        JOIN users u ON pt.user_id = u.id
        ORDER BY pt.created_at DESC
        LIMIT 15
    ");
    
    $transactions = $stmt->fetchAll();
    if (count($transactions) > 0) {
        foreach ($transactions as $trans) {
            $date = date('d/m/Y H:i', strtotime($trans['created_at']));
            $emoji = $trans['points'] > 0 ? '➕' : '➖';
            $color = $trans['points'] > 0 ? '+' : '';
            echo "  {$emoji} [{$trans['id']}] {$trans['username']}: {$color}{$trans['points']} pts\n";
            echo "       └─ {$trans['transaction_type']} | {$trans['description']} | {$date}\n";
        }
    } else {
        echo "  ℹ️  Aucune transaction trouvée.\n";
    }
    echo "\n";
    
    // 8. Diagnostic final
    echo "8. DIAGNOSTIC DU SYSTÈME:\n";
    echo str_repeat("-", 60) . "\n";
    
    $issues = [];
    $successes = [];
    
    if ($activeRewards > 0) {
        $successes[] = "✅ {$activeRewards} récompense(s) disponible(s)";
    } else {
        $issues[] = "⚠️  Aucune récompense n'est disponible";
    }
    
    if ($activePackages > 0) {
        $successes[] = "✅ {$activePackages} package(s) de jeu actif(s)";
    } else {
        $issues[] = "⚠️  Aucun package de jeu n'est actif";
    }
    
    if ($totalPlayers > 0) {
        $successes[] = "✅ {$totalPlayers} joueur(s) actif(s)";
    } else {
        $issues[] = "⚠️  Aucun joueur actif";
    }
    
    if ($activeSessions > 0) {
        $successes[] = "✅ {$activeSessions} session(s) de jeu en cours";
    }
    
    if (count($users) > 0 && $users[0]['points'] > 0) {
        $successes[] = "✅ Des joueurs ont accumulé des points";
    }
    
    if (count($successes) > 0) {
        echo "POINTS POSITIFS:\n";
        foreach ($successes as $success) {
            echo "  {$success}\n";
        }
        echo "\n";
    }
    
    if (count($issues) > 0) {
        echo "POINTS À AMÉLIORER:\n";
        foreach ($issues as $issue) {
            echo "  {$issue}\n";
        }
        echo "\n";
    }
    
    // Résumé final
    if ($activeRewards > 0 && $activePackages > 0 && $totalPlayers > 0) {
        echo "🎉 RÉSULTAT: Le système est OPÉRATIONNEL et prêt!\n";
    } else if ($activePackages > 0 && $totalPlayers > 0) {
        echo "⚠️  RÉSULTAT: Le système de jeu fonctionne, mais les récompenses\n";
        echo "   doivent être configurées pour permettre l'échange de points.\n";
    } else {
        echo "⚠️  RÉSULTAT: Configuration initiale requise.\n";
    }
    
    echo "\n";
    echo str_repeat("=", 60) . "\n";
    echo "Test exécuté le " . date('d/m/Y à H:i:s') . "\n";
    echo "Base de données: {$dbname}\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}
