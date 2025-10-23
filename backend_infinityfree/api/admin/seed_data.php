<?php
// Script to populate database with sample data for testing
// Access: http://localhost/projet%20ismo/api/admin/seed_data.php

require_once __DIR__ . '/../config.php';

$db = get_db();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Remplissage Base de Données</title>";
echo "<style>body{font-family:sans-serif;padding:20px;background:#f5f5f5;max-width:800px;margin:0 auto;}";
echo ".success{color:#10b981;padding:10px;background:#d1fae5;border-radius:5px;margin:5px 0;}";
echo ".error{color:#ef4444;padding:10px;background:#fee2e2;border-radius:5px;margin:5px 0;}";
echo ".info{color:#3b82f6;padding:10px;background:#dbeafe;border-radius:5px;margin:5px 0;}";
echo "h1{color:#333;border-bottom:3px solid #6366f1;padding-bottom:10px;}";
echo "h2{color:#6366f1;margin-top:30px;}</style></head><body>";

echo "<h1>🌱 Remplissage de la Base de Données</h1>";
echo "<p class='info'>Ce script va ajouter des données de test pour que le tableau de bord affiche de vraies informations.</p>";

try {
    $db->beginTransaction();
    
    // 1. Créer des utilisateurs de test
    echo "<h2>1. Création d'utilisateurs de test</h2>";
    $users = [
        ['username' => 'ProGamer', 'email' => 'progamer@test.com', 'points' => 2500],
        ['username' => 'NoobMaster', 'email' => 'noob@test.com', 'points' => 1800],
        ['username' => 'SpeedRunner', 'email' => 'speed@test.com', 'points' => 3200],
        ['username' => 'CasualPlayer', 'email' => 'casual@test.com', 'points' => 450],
        ['username' => 'EliteGamer', 'email' => 'elite@test.com', 'points' => 5600],
        ['username' => 'NewbieJoe', 'email' => 'newbie@test.com', 'points' => 120],
        ['username' => 'VeteranKing', 'email' => 'veteran@test.com', 'points' => 4300],
        ['username' => 'StreamerPro', 'email' => 'streamer@test.com', 'points' => 2900]
    ];
    
    $password = password_hash('test123', PASSWORD_BCRYPT);
    $now = date('Y-m-d H:i:s');
    $joinDate = date('Y-m-d', strtotime('-' . rand(1, 180) . ' days'));
    
    foreach ($users as $userData) {
        // Check if user already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        
        if (!$stmt->fetch()) {
            $lastActive = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'));
            $level = $userData['points'] < 500 ? 'Novice' : ($userData['points'] < 1500 ? 'Joueur' : ($userData['points'] < 3000 ? 'Expert' : 'Maître'));
            
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password_hash, role, points, level, status, join_date, last_active, created_at, updated_at)
                VALUES (?, ?, ?, 'player', ?, ?, 'active', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userData['username'],
                $userData['email'],
                $password,
                $userData['points'],
                $level,
                $joinDate,
                $lastActive,
                $now,
                $now
            ]);
            echo "<div class='success'>✓ Utilisateur créé: {$userData['username']} ({$userData['points']} pts)</div>";
        } else {
            echo "<div class='info'>ℹ Utilisateur existe déjà: {$userData['username']}</div>";
        }
    }
    
    // 2. Créer des transactions de points
    echo "<h2>2. Création de transactions de points</h2>";
    $stmt = $db->query("SELECT id FROM users WHERE role = 'player' LIMIT 10");
    $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $transactionCount = 0;
    foreach ($userIds as $userId) {
        // Ajouter 5-15 transactions par utilisateur
        $numTransactions = rand(5, 15);
        for ($i = 0; $i < $numTransactions; $i++) {
            $types = ['game', 'tournament', 'bonus', 'reward'];
            $type = $types[array_rand($types)];
            $amount = rand(10, 200);
            $reason = $type === 'game' ? 'Partie jouée' : ($type === 'tournament' ? 'Participation tournoi' : ($type === 'bonus' ? 'Bonus quotidien' : 'Récompense réclamée'));
            
            if ($type === 'reward') {
                $amount = -$amount; // Dépense
                $reason = 'Achat récompense';
            }
            
            $createdAt = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days'));
            
            $stmt = $db->prepare("
                INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $amount, $reason, $type, $createdAt]);
            $transactionCount++;
        }
    }
    echo "<div class='success'>✓ {$transactionCount} transactions créées</div>";
    
    // 3. Ajouter quelques sanctions
    echo "<h2>3. Création de sanctions (exemples)</h2>";
    if (count($userIds) > 0) {
        $sanctionedUser = $userIds[array_rand($userIds)];
        $stmt = $db->prepare("
            INSERT INTO points_transactions (user_id, change_amount, reason, type, created_at)
            VALUES (?, -100, 'SANCTION: Avertissement - Comportement inapproprié', 'adjustment', ?)
        ");
        $stmt->execute([$sanctionedUser, date('Y-m-d H:i:s', strtotime('-5 days'))]);
        echo "<div class='success'>✓ 1 sanction ajoutée (exemple)</div>";
    }
    
    // 4. Vérifier/Créer des événements
    echo "<h2>4. Vérification des événements</h2>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM events");
    $eventCount = $stmt->fetch()['count'];
    
    if ($eventCount < 5) {
        echo "<div class='info'>ℹ Ajout d'événements supplémentaires...</div>";
        
        $events = [
            ['title' => 'Tournoi FIFA 2025', 'type' => 'tournament', 'participants' => 32, 'date' => date('Y-m-d', strtotime('+7 days'))],
            ['title' => 'Soirée Gaming Rétro', 'type' => 'event', 'participants' => 25, 'date' => date('Y-m-d', strtotime('+14 days'))],
            ['title' => 'Stream Live Fortnite', 'type' => 'stream', 'participants' => 150, 'date' => date('Y-m-d', strtotime('+3 days'))],
            ['title' => 'Nouveau Espace VR Ouvert', 'type' => 'news', 'participants' => null, 'date' => date('Y-m-d', strtotime('-2 days'))],
            ['title' => 'Championship League of Legends', 'type' => 'tournament', 'participants' => 16, 'date' => date('Y-m-d', strtotime('+21 days'))]
        ];
        
        foreach ($events as $event) {
            $stmt = $db->prepare("
                INSERT INTO events (title, date, type, participants, description, status, likes, comments, created_at)
                VALUES (?, ?, ?, ?, 'Événement passionnant à ne pas manquer!', 'published', ?, ?, ?)
            ");
            $stmt->execute([
                $event['title'],
                $event['date'],
                $event['type'],
                $event['participants'],
                rand(10, 150),
                rand(5, 45),
                $now
            ]);
            echo "<div class='success'>✓ Événement créé: {$event['title']}</div>";
        }
    } else {
        echo "<div class='success'>✓ {$eventCount} événements existent déjà</div>";
    }
    
    // 5. Créer table gallery si elle n'existe pas
    echo "<h2>5. Vérification de la galerie</h2>";
    $stmt = $db->query("SHOW TABLES LIKE 'gallery'");
    if (!$stmt->fetch()) {
        echo "<div class='info'>ℹ Création de la table gallery...</div>";
        $db->exec("
            CREATE TABLE IF NOT EXISTS gallery (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                description TEXT NULL,
                image_url VARCHAR(500) NOT NULL,
                thumbnail_url VARCHAR(500) NULL,
                category ENUM('tournament','event','stream','general','vr','retro') NOT NULL DEFAULT 'general',
                event_id INT NULL,
                status ENUM('active','archived') NOT NULL DEFAULT 'active',
                display_order INT NOT NULL DEFAULT 0,
                views INT NOT NULL DEFAULT 0,
                likes INT NOT NULL DEFAULT 0,
                created_by INT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<div class='success'>✓ Table gallery créée</div>";
        
        // Ajouter des images
        $images = [
            ['title' => 'Zone Gaming Pro', 'category' => 'general'],
            ['title' => 'Tournoi FIFA', 'category' => 'tournament'],
            ['title' => 'Espace VR', 'category' => 'vr'],
            ['title' => 'Console Rétro', 'category' => 'retro']
        ];
        
        foreach ($images as $img) {
            $stmt = $db->prepare("
                INSERT INTO gallery (title, description, image_url, category, status, created_at)
                VALUES (?, 'Image de démonstration', 'https://via.placeholder.com/800x600', ?, 'active', ?)
            ");
            $stmt->execute([$img['title'], $img['category'], $now]);
        }
        echo "<div class='success'>✓ 4 images ajoutées à la galerie</div>";
    } else {
        $stmt = $db->query("SELECT COUNT(*) as count FROM gallery");
        $galleryCount = $stmt->fetch()['count'];
        echo "<div class='success'>✓ {$galleryCount} images dans la galerie</div>";
    }
    
    $db->commit();
    
    echo "<h2>✅ Résumé Final</h2>";
    echo "<div class='success'><strong>Base de données remplie avec succès!</strong></div>";
    
    // Afficher les statistiques
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'player'");
    $playerCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM points_transactions");
    $transCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM events");
    $evCount = $stmt->fetch()['count'];
    
    echo "<ul>";
    echo "<li><strong>{$playerCount}</strong> joueurs</li>";
    echo "<li><strong>{$transCount}</strong> transactions de points</li>";
    echo "<li><strong>{$evCount}</strong> événements</li>";
    echo "</ul>";
    
    echo "<p class='info'>🎮 Vous pouvez maintenant accéder au <a href='../../admin/index.html'>tableau de bord admin</a> pour voir les vraies données!</p>";
    echo "<p class='info'>🔍 Ou vérifier les <a href='test_stats.php'>statistiques détaillées</a></p>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
