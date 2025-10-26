<?php
/**
 * Script d'initialisation complète de toutes les tables manquantes
 * Crée toutes les tables qui n'existent pas encore
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$pdo = get_db();
$created = [];
$errors = [];

try {
    // 1. Vérifier et créer table points_rules
    $stmt = $pdo->query("SHOW TABLES LIKE 'points_rules'");
    if (!$stmt->fetch()) {
        $sql = "CREATE TABLE points_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action_type VARCHAR(50) NOT NULL UNIQUE,
            points_amount INT NOT NULL DEFAULT 0,
            description TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_action_type (action_type),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        $created[] = 'points_rules';
        
        // Insérer règles par défaut
        $stmt = $pdo->prepare("INSERT INTO points_rules (action_type, points_amount, description, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $rules = [
            ['session_complete', 100, 'Points gagnés à la fin d\'une session de jeu', 1],
            ['daily_login', 10, 'Bonus de connexion quotidien', 1],
            ['first_purchase', 50, 'Bonus pour le premier achat', 1],
            ['referral', 200, 'Points pour avoir parrainé un ami', 1],
            ['achievement', 150, 'Points pour avoir débloqué un succès', 1]
        ];
        
        foreach ($rules as $rule) {
            $stmt->execute($rule);
        }
        $created[] = 'points_rules (5 règles par défaut)';
    }
    
    // 2. Vérifier et créer table bonus_multipliers
    $stmt = $pdo->query("SHOW TABLES LIKE 'bonus_multipliers'");
    if (!$stmt->fetch()) {
        $sql = "CREATE TABLE bonus_multipliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00,
            condition_type VARCHAR(50) NOT NULL,
            condition_value VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            start_date DATETIME,
            end_date DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_is_active (is_active),
            INDEX idx_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        $created[] = 'bonus_multipliers';
        
        // Insérer multiplicateurs par défaut
        $stmt = $pdo->prepare("INSERT INTO bonus_multipliers (name, multiplier, condition_type, condition_value, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $bonuses = [
            ['Weekend Boost', 1.5, 'day_of_week', 'saturday,sunday', 1],
            ['Happy Hour', 2.0, 'time_range', '18:00-20:00', 1],
            ['VIP Member', 1.25, 'user_level', '5', 1]
        ];
        
        foreach ($bonuses as $bonus) {
            $stmt->execute($bonus);
        }
        $created[] = 'bonus_multipliers (3 bonus par défaut)';
    }
    
    // 3. Vérifier table levels
    $stmt = $pdo->query("SHOW TABLES LIKE 'levels'");
    if (!$stmt->fetch()) {
        $sql = "CREATE TABLE levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level_number INT NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            min_points INT NOT NULL,
            max_points INT NOT NULL,
            icon VARCHAR(255),
            color VARCHAR(7),
            rewards JSON,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_level_number (level_number),
            INDEX idx_points_range (min_points, max_points)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        $created[] = 'levels';
        
        // Insérer niveaux par défaut
        $stmt = $pdo->prepare("INSERT INTO levels (level_number, name, min_points, max_points, icon, color, rewards, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $levels = [
            [1, 'Novice', 0, 100, '🥉', '#CD7F32', json_encode(['discount' => 5])],
            [2, 'Amateur', 101, 500, '🥈', '#C0C0C0', json_encode(['discount' => 10])],
            [3, 'Pro', 501, 1000, '🥇', '#FFD700', json_encode(['discount' => 15])],
            [4, 'Expert', 1001, 5000, '💎', '#00CED1', json_encode(['discount' => 20])],
            [5, 'Legend', 5001, 999999, '👑', '#9370DB', json_encode(['discount' => 25])]
        ];
        
        foreach ($levels as $level) {
            $stmt->execute($level);
        }
        $created[] = 'levels (5 niveaux par défaut)';
    }
    
    // 4. Vérifier table badges
    $stmt = $pdo->query("SHOW TABLES LIKE 'badges'");
    if (!$stmt->fetch()) {
        $sql = "CREATE TABLE badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(255),
            condition_type VARCHAR(50) NOT NULL,
            condition_value VARCHAR(255),
            points_reward INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        $created[] = 'badges';
        
        // Insérer badges par défaut
        $stmt = $pdo->prepare("INSERT INTO badges (name, description, icon, condition_type, condition_value, points_reward, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $badges = [
            ['Premier Pas', 'Première session complétée', '🎮', 'sessions_completed', '1', 50, 1],
            ['Habitué', '10 sessions complétées', '⭐', 'sessions_completed', '10', 200, 1],
            ['Vétéran', '50 sessions complétées', '🏆', 'sessions_completed', '50', 500, 1],
            ['Dépensier', 'Premier achat effectué', '💰', 'total_spent', '1', 100, 1]
        ];
        
        foreach ($badges as $badge) {
            $stmt->execute($badge);
        }
        $created[] = 'badges (4 badges par défaut)';
    }
    
    // 5. Vérifier table user_badges
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_badges'");
    if (!$stmt->fetch()) {
        $sql = "CREATE TABLE user_badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            badge_id INT NOT NULL,
            earned_at DATETIME NOT NULL,
            UNIQUE KEY unique_user_badge (user_id, badge_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_earned_at (earned_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        $created[] = 'user_badges';
    }
    
    // 6. Vérifier table user_stats (pour gamification)
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_stats'");
    if (!$stmt->fetch()) {
        $sql = "CREATE TABLE user_stats (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        $created[] = 'user_stats';
        
        // Créer stats pour tous les users existants
        $pdo->exec("INSERT INTO user_stats (user_id, created_at, updated_at) 
                    SELECT id, NOW(), NOW() FROM users 
                    WHERE id NOT IN (SELECT user_id FROM user_stats)");
        $created[] = 'user_stats (initialisé pour users existants)';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Initialisation complète réussie',
        'tables_created' => $created,
        'errors' => $errors
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'tables_created' => $created,
        'errors' => $errors
    ], JSON_PRETTY_PRINT);
}
