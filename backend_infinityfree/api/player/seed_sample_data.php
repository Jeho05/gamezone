<?php
/**
 * Script pour générer des données de test pour les endpoints player
 * Lance ce script pour créer des utilisateurs de test avec des points et badges
 * 
 * Usage: php seed_sample_data.php
 */

require_once __DIR__ . '/../utils.php';

$pdo = get_db();

echo "🎮 Génération de données de test pour les endpoints player\n";
echo "===========================================================\n\n";

try {
    $pdo->beginTransaction();
    
    // 1. Vérifier et créer des niveaux si nécessaire
    echo "📊 Vérification des niveaux...\n";
    $levelCount = (int)$pdo->query('SELECT COUNT(*) FROM levels')->fetchColumn();
    
    if ($levelCount < 10) {
        echo "   Création de niveaux par défaut...\n";
        
        $levels = [
            [1, 'Novice', 0, '#808080', 0],
            [2, 'Débutant', 100, '#CD7F32', 5],
            [3, 'Amateur', 250, '#C0C0C0', 10],
            [4, 'Intermédiaire', 500, '#87CEEB', 15],
            [5, 'Avancé', 1000, '#4169E1', 25],
            [6, 'Expert', 2500, '#9370DB', 40],
            [7, 'Maître', 5000, '#FFD700', 60],
            [8, 'Grand Maître', 10000, '#FF8C00', 100],
            [9, 'Légendaire', 25000, '#DC143C', 150],
            [10, 'Mythique', 50000, '#8B008B', 250]
        ];
        
        $stmt = $pdo->prepare('INSERT INTO levels (level_number, name, points_required, color, points_bonus) VALUES (?, ?, ?, ?, ?)');
        foreach ($levels as $level) {
            $stmt->execute($level);
        }
        
        echo "   ✅ " . count($levels) . " niveaux créés\n";
    } else {
        echo "   ✅ Niveaux déjà présents ($levelCount)\n";
    }
    
    // 2. Vérifier et créer des badges si nécessaire
    echo "\n🏆 Vérification des badges...\n";
    $badgeCount = (int)$pdo->query('SELECT COUNT(*) FROM badges')->fetchColumn();
    
    if ($badgeCount < 5) {
        echo "   Création de badges par défaut...\n";
        
        $badges = [
            ['Premier Pas', 'Jouer votre premier jeu', '🎮', 'common', 10],
            ['Habitué', 'Jouer 10 jeux', '🎯', 'common', 50],
            ['Vétéran', 'Jouer 50 jeux', '⭐', 'rare', 100],
            ['Champion', 'Gagner un tournoi', '🏆', 'epic', 250],
            ['Marathonien', 'Série de connexion de 7 jours', '🔥', 'rare', 150],
            ['Perfectionniste', 'Atteindre le niveau 10', '💎', 'legendary', 500],
            ['Social', 'Parrainer 3 amis', '👥', 'rare', 200],
            ['Généreux', 'Participer à 5 événements', '🎪', 'common', 75],
            ['Élite', 'Être dans le top 10', '👑', 'epic', 300],
            ['Maître des Points', 'Accumuler 10000 points', '💰', 'legendary', 1000]
        ];
        
        $stmt = $pdo->prepare('INSERT INTO badges (name, description, icon, rarity, points_reward) VALUES (?, ?, ?, ?, ?)');
        foreach ($badges as $badge) {
            $stmt->execute($badge);
        }
        
        echo "   ✅ " . count($badges) . " badges créés\n";
    } else {
        echo "   ✅ Badges déjà présents ($badgeCount)\n";
    }
    
    // 3. Créer des utilisateurs de test
    echo "\n👥 Création d'utilisateurs de test...\n";
    
    $testUsers = [
        ['testplayer1', 'test1@example.com', 'Test Player 1', 5000],
        ['testplayer2', 'test2@example.com', 'Test Player 2', 3500],
        ['testplayer3', 'test3@example.com', 'Test Player 3', 8000],
        ['testplayer4', 'test4@example.com', 'Test Player 4', 1500],
        ['testplayer5', 'test5@example.com', 'Test Player 5', 12000],
        ['testplayer6', 'test6@example.com', 'Test Player 6', 2500],
        ['testplayer7', 'test7@example.com', 'Test Player 7', 6500],
        ['testplayer8', 'test8@example.com', 'Test Player 8', 4200],
        ['testplayer9', 'test9@example.com', 'Test Player 9', 9500],
        ['testplayer10', 'test10@example.com', 'Test Player 10', 7800]
    ];
    
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $createdUsers = 0;
    
    foreach ($testUsers as $user) {
        // Check if user exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$user[0]]);
        
        if (!$stmt->fetch()) {
            // Calculate level based on points
            $stmtLevel = $pdo->prepare('SELECT level_number FROM levels WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1');
            $stmtLevel->execute([$user[3]]);
            $level = (int)$stmtLevel->fetchColumn() ?: 1;
            
            // Create user
            $stmtInsert = $pdo->prepare('INSERT INTO users (username, email, password_hash, points, level, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmtInsert->execute([$user[0], $user[1], $password, $user[3], $level, 'player']);
            
            $userId = $pdo->lastInsertId();
            
            // Create user_stats
            $stmtStats = $pdo->prepare('INSERT INTO user_stats (user_id, games_played, total_points_earned) VALUES (?, ?, ?)');
            $stmtStats->execute([$userId, rand(5, 50), $user[3]]);
            
            // Create login_streaks
            $stmtStreak = $pdo->prepare('INSERT INTO login_streaks (user_id, current_streak, longest_streak, last_login_date) VALUES (?, ?, ?, CURDATE())');
            $stmtStreak->execute([$userId, rand(1, 15), rand(5, 30)]);
            
            // Add some points transactions
            $transactionCount = rand(10, 30);
            for ($i = 0; $i < $transactionCount; $i++) {
                $amount = rand(10, 100);
                $date = date('Y-m-d H:i:s', strtotime("-" . rand(1, 60) . " days"));
                
                $stmtTrans = $pdo->prepare('INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at) VALUES (?, ?, ?, ?, ?)');
                $stmtTrans->execute([
                    $userId,
                    $amount,
                    'Activité de test',
                    'game',
                    $date
                ]);
            }
            
            // Assign some random badges
            $allBadges = $pdo->query('SELECT id FROM badges')->fetchAll(PDO::FETCH_COLUMN);
            $badgesToGive = array_rand(array_flip($allBadges), min(rand(2, 5), count($allBadges)));
            
            if (!is_array($badgesToGive)) {
                $badgesToGive = [$badgesToGive];
            }
            
            foreach ($badgesToGive as $badgeId) {
                $earnedDate = date('Y-m-d H:i:s', strtotime("-" . rand(1, 30) . " days"));
                $stmtBadge = $pdo->prepare('INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, ?)');
                $stmtBadge->execute([$userId, $badgeId, $earnedDate]);
            }
            
            $createdUsers++;
        }
    }
    
    echo "   ✅ $createdUsers nouveaux utilisateurs créés\n";
    
    $pdo->commit();
    
    echo "\n✅ DONNÉES DE TEST CRÉÉES AVEC SUCCÈS!\n\n";
    echo "📋 Résumé:\n";
    echo "   - Niveaux: " . $pdo->query('SELECT COUNT(*) FROM levels')->fetchColumn() . "\n";
    echo "   - Badges: " . $pdo->query('SELECT COUNT(*) FROM badges')->fetchColumn() . "\n";
    echo "   - Utilisateurs: " . $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . "\n";
    echo "   - Transactions: " . $pdo->query('SELECT COUNT(*) FROM points_transactions')->fetchColumn() . "\n";
    echo "\n";
    echo "🔑 Identifiants de test:\n";
    echo "   Username: testplayer1 à testplayer10\n";
    echo "   Password: password123\n";
    echo "\n";
    echo "🚀 Testez maintenant:\n";
    echo "   http://localhost:4000/api/player/leaderboard.php\n";
    echo "   http://localhost:4000/test_player_endpoints.html\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . "\n";
    echo "   Ligne: " . $e->getLine() . "\n";
    exit(1);
}
