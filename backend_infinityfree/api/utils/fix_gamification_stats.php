<?php
// api/utils/fix_gamification_stats.php
// Script pour corriger les statistiques de gamification

require_once __DIR__ . '/../config.php';

header('Content-Type: text/plain; charset=UTF-8');

$pdo = get_db();

echo "========================================\n";
echo "🔧 CORRECTION DES STATISTIQUES\n";
echo "========================================\n\n";

try {
    $pdo->beginTransaction();
    
    // 1. Ajouter la colonne rewards_redeemed si elle n'existe pas
    echo "1️⃣ Vérification de la structure de la table...\n";
    
    try {
        $pdo->query("ALTER TABLE user_stats ADD COLUMN rewards_redeemed INT DEFAULT 0");
        echo "   ✅ Colonne rewards_redeemed ajoutée\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ️ Colonne rewards_redeemed existe déjà\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Mettre à jour games_played basé sur les sessions complétées
    echo "\n2️⃣ Mise à jour de games_played...\n";
    
    $stmt = $pdo->query("
        SELECT p.user_id, COUNT(DISTINCT gs.id) as completed_sessions
        FROM game_sessions gs
        INNER JOIN purchases p ON gs.purchase_id = p.id
        WHERE gs.status = 'completed'
        GROUP BY p.user_id
    ");
    
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $stmt = $pdo->prepare("
            INSERT INTO user_stats (user_id, games_played, updated_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                games_played = ?,
                updated_at = NOW()
        ");
        $stmt->execute([
            $user['user_id'],
            $user['completed_sessions'],
            $user['completed_sessions']
        ]);
        
        echo "   User {$user['user_id']}: {$user['completed_sessions']} sessions complétées\n";
    }
    
    // 3. Mettre à jour rewards_redeemed (basé sur paid_with_points)
    echo "\n3️⃣ Mise à jour de rewards_redeemed...\n";
    
    $stmt = $pdo->query("
        SELECT user_id, COUNT(*) as redeemed_count
        FROM purchases
        WHERE paid_with_points = 1
        GROUP BY user_id
    ");
    
    $rewards = $stmt->fetchAll();
    
    if (count($rewards) > 0) {
        foreach ($rewards as $reward) {
            $stmt = $pdo->prepare("
                INSERT INTO user_stats (user_id, rewards_redeemed, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    rewards_redeemed = ?,
                    updated_at = NOW()
            ");
            $stmt->execute([
                $reward['user_id'],
                $reward['redeemed_count'],
                $reward['redeemed_count']
            ]);
            
            echo "   User {$reward['user_id']}: {$reward['redeemed_count']} récompenses\n";
        }
    } else {
        echo "   ℹ️  Aucune récompense échangée pour le moment\n";
    }
    
    $pdo->commit();
    
    echo "\n========================================\n";
    echo "✅ CORRECTION TERMINÉE\n";
    echo "========================================\n";
    
    // Afficher les statistiques mises à jour
    echo "\n📊 STATISTIQUES MISES À JOUR:\n\n";
    
    $stmt = $pdo->query("
        SELECT 
            u.username,
            COALESCE(us.games_played, 0) as games_played,
            COALESCE(us.rewards_redeemed, 0) as rewards_redeemed,
            COALESCE(us.total_points_earned, 0) as points_earned,
            COALESCE(us.total_points_spent, 0) as points_spent
        FROM users u
        LEFT JOIN user_stats us ON u.id = us.user_id
        WHERE u.role = 'player'
        ORDER BY us.games_played DESC
        LIMIT 10
    ");
    
    $stats = $stmt->fetchAll();
    
    printf("%-20s | %10s | %10s | %15s | %15s\n", 
        "Utilisateur", "Parties", "Récomp.", "Points gagnés", "Points dépensés");
    echo str_repeat("-", 85) . "\n";
    
    foreach ($stats as $stat) {
        printf("%-20s | %10d | %10d | %15d | %15d\n",
            substr($stat['username'], 0, 20),
            $stat['games_played'],
            $stat['rewards_redeemed'],
            $stat['points_earned'],
            $stat['points_spent']
        );
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
