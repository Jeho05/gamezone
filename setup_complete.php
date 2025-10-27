<?php
/**
 * Configuration complète du backend - Crée toutes les tables manquantes
 * À exécuter une fois après le premier déploiement Railway
 */

// Pas besoin de config.php car on se connecte directement
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Setup GameZone</title>";
echo "<style>body{font-family:monospace;background:#1a1a1a;color:#00ff00;padding:20px;}";
echo ".ok{color:#00ff00;}.error{color:#ff0000;}.info{color:#ffaa00;}";
echo "pre{background:#0a0a0a;padding:10px;border-radius:5px;}</style></head><body>";

echo "<h1>🚀 Setup Complet GameZone Backend</h1>";

try {
    // Charger la configuration centrale (DB, sessions, CORS)
    // Utilise la même logique que toutes les API (get_db())
    require_once __DIR__ . '/config.php';

    // DEBUG: Afficher les fichiers présents
    echo "<p class='info'>📂 Fichiers dans " . __DIR__ . ":</p>";
    $files = scandir(__DIR__);
    echo "<pre>" . implode("\n", array_slice($files, 0, 20)) . "</pre>";
    
    // Afficher les variables détectées et les constantes DB résolues
    echo "<p class='info'>🔎 Variables détectées et valeurs résolues:</p>";
    echo "<pre>";
    echo "APP_ENV: " . htmlspecialchars(envval('APP_ENV') ?: '') . "\n";
    echo "MYSQLHOST: " . var_export(envval('MYSQLHOST'), true) . "\n";
    echo "MYSQLPORT: " . var_export(envval('MYSQLPORT'), true) . "\n";
    echo "MYSQLDATABASE: " . var_export(envval('MYSQLDATABASE'), true) . "\n";
    echo "MYSQLUSER: " . var_export(envval('MYSQLUSER'), true) . "\n";
    echo "Resolved host: " . DB_HOST . "\n";
    echo "Resolved port: " . DB_PORT . "\n";
    echo "Resolved database: " . DB_NAME . "\n";
    echo "Resolved user: " . DB_USER . "\n";
    echo "</pre>";
    
    // Connexion DB via la même fonction que les API
    $pdo = get_db();
    echo "<p class='ok'>✅ Connexion MySQL via config.php réussie</p>";
    
    echo "<p class='ok'>✅ Connexion MySQL réussie</p>";
    
    $created = [];
    
    // 1. Points Rules
    $stmt = $pdo->query("SHOW TABLES LIKE 'points_rules'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table points_rules...</p>";
        $pdo->exec("CREATE TABLE points_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action_type VARCHAR(50) NOT NULL UNIQUE,
            points_amount INT NOT NULL DEFAULT 0,
            description TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Données par défaut
        $stmt = $pdo->prepare("INSERT INTO points_rules (action_type, points_amount, description, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute(['session_complete', 100, 'Points gagnés à la fin d\'une session', 1]);
        $stmt->execute(['daily_login', 10, 'Bonus de connexion quotidien', 1]);
        $stmt->execute(['first_purchase', 50, 'Bonus premier achat', 1]);
        $stmt->execute(['referral', 200, 'Points parrainage', 1]);
        $stmt->execute(['achievement', 150, 'Points succès', 1]);
        
        $created[] = 'points_rules (5 règles)';
        echo "<p class='ok'>✅ Table points_rules créée avec 5 règles par défaut</p>";
    } else {
        echo "<p class='info'>ℹ️ Table points_rules existe déjà</p>";
    }
    
    // 2. Bonus Multipliers
    $stmt = $pdo->query("SHOW TABLES LIKE 'bonus_multipliers'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table bonus_multipliers...</p>";
        $pdo->exec("CREATE TABLE bonus_multipliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00,
            condition_type VARCHAR(50) NOT NULL,
            condition_value VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            start_date DATETIME,
            end_date DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $pdo->prepare("INSERT INTO bonus_multipliers (name, multiplier, condition_type, condition_value, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute(['Weekend Boost', 1.5, 'day_of_week', 'saturday,sunday', 1]);
        $stmt->execute(['Happy Hour', 2.0, 'time_range', '18:00-20:00', 1]);
        $stmt->execute(['VIP Member', 1.25, 'user_level', '5', 1]);
        
        $created[] = 'bonus_multipliers (3 bonus)';
        echo "<p class='ok'>✅ Table bonus_multipliers créée avec 3 bonus par défaut</p>";
    } else {
        echo "<p class='info'>ℹ️ Table bonus_multipliers existe déjà</p>";
    }
    
    // 3. Levels
    $stmt = $pdo->query("SHOW TABLES LIKE 'levels'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table levels...</p>";
        $pdo->exec("CREATE TABLE levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level_number INT NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            min_points INT NOT NULL,
            max_points INT NOT NULL,
            icon VARCHAR(255),
            color VARCHAR(7),
            rewards JSON,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $pdo->prepare("INSERT INTO levels (level_number, name, min_points, max_points, icon, color, rewards, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([1, 'Novice', 0, 100, '🥉', '#CD7F32', json_encode(['discount' => 5])]);
        $stmt->execute([2, 'Amateur', 101, 500, '🥈', '#C0C0C0', json_encode(['discount' => 10])]);
        $stmt->execute([3, 'Pro', 501, 1000, '🥇', '#FFD700', json_encode(['discount' => 15])]);
        $stmt->execute([4, 'Expert', 1001, 5000, '💎', '#00CED1', json_encode(['discount' => 20])]);
        $stmt->execute([5, 'Legend', 5001, 999999, '👑', '#9370DB', json_encode(['discount' => 25])]);
        
        $created[] = 'levels (5 niveaux)';
        echo "<p class='ok'>✅ Table levels créée avec 5 niveaux par défaut</p>";
    } else {
        echo "<p class='info'>ℹ️ Table levels existe déjà</p>";
    }
    
    // 4. Badges
    $stmt = $pdo->query("SHOW TABLES LIKE 'badges'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table badges...</p>";
        $pdo->exec("CREATE TABLE badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(255),
            condition_type VARCHAR(50) NOT NULL,
            condition_value VARCHAR(255),
            points_reward INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $pdo->prepare("INSERT INTO badges (name, description, icon, condition_type, condition_value, points_reward, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute(['Premier Pas', 'Première session complétée', '🎮', 'sessions_completed', '1', 50, 1]);
        $stmt->execute(['Habitué', '10 sessions complétées', '⭐', 'sessions_completed', '10', 200, 1]);
        $stmt->execute(['Vétéran', '50 sessions complétées', '🏆', 'sessions_completed', '50', 500, 1]);
        $stmt->execute(['Dépensier', 'Premier achat effectué', '💰', 'total_spent', '1', 100, 1]);
        
        $created[] = 'badges (4 badges)';
        echo "<p class='ok'>✅ Table badges créée avec 4 badges par défaut</p>";
    } else {
        echo "<p class='info'>ℹ️ Table badges existe déjà</p>";
    }
    
    // 5. User Badges
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_badges'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table user_badges...</p>";
        $pdo->exec("CREATE TABLE user_badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            badge_id INT NOT NULL,
            earned_at DATETIME NOT NULL,
            UNIQUE KEY unique_user_badge (user_id, badge_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $created[] = 'user_badges';
        echo "<p class='ok'>✅ Table user_badges créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table user_badges existe déjà</p>";
    }
    
    // 6. User Stats
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_stats'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table user_stats...</p>";
        $pdo->exec("CREATE TABLE user_stats (
            user_id INT PRIMARY KEY,
            total_sessions INT DEFAULT 0,
            total_playtime_minutes INT DEFAULT 0,
            total_spent DECIMAL(10,2) DEFAULT 0.00,
            current_streak INT DEFAULT 0,
            longest_streak INT DEFAULT 0,
            last_login_date DATE,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Créer stats pour users existants
        $pdo->exec("INSERT INTO user_stats (user_id, created_at, updated_at) 
                    SELECT id, NOW(), NOW() FROM users 
                    WHERE id NOT IN (SELECT user_id FROM user_stats)");
        
        $created[] = 'user_stats';
        echo "<p class='ok'>✅ Table user_stats créée et initialisée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table user_stats existe déjà</p>";
    }
    
    echo "<hr>";
    echo "<h2 class='ok'>🎉 SETUP TERMINÉ AVEC SUCCÈS !</h2>";
    echo "<p><strong>Tables créées:</strong> " . (count($created) > 0 ? count($created) : '0 (toutes existent déjà)') . "</p>";
    if (count($created) > 0) {
        echo "<ul>";
        foreach ($created as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<h3>🧪 Prochaines étapes :</h3>";
    echo "<ol>";
    echo "<li>Tester l'API : <a href='/admin/points_rules.php' style='color:#00ffff;'>/admin/points_rules.php</a></li>";
    echo "<li>Tester création jeu : <a href='https://gamezoneismo.vercel.app/admin/shop' style='color:#00ffff;' target='_blank'>Admin Shop</a></li>";
    echo "<li>Vérifier console : Pas d'erreurs 500</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ ERREUR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
