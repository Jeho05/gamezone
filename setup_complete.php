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
    require_once __DIR__ . '/api/config.php';

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
    
    // 0. Core tables: users and points transactions
    // users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table users...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL,
            email VARCHAR(191) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('player','admin') NOT NULL DEFAULT 'player',
            avatar_url VARCHAR(500) NULL,
            points INT NOT NULL DEFAULT 0,
            level VARCHAR(100) NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            join_date DATE NULL,
            last_active DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table users créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table users existe déjà</p>";
    }
    // points_transactions
    $stmt = $pdo->query("SHOW TABLES LIKE 'points_transactions'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table points_transactions...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS points_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            change_amount INT NOT NULL,
            reason VARCHAR(255) NULL,
            type ENUM('game','tournament','bonus','reservation','friend','adjustment','reward') NULL,
            admin_id INT NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_pt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table points_transactions créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table points_transactions existe déjà</p>";
    }
    
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
    
    // 7. Shop Core Tables (games, game_packages, payment_methods, purchases, game_sessions)
    echo "<h3 class='info'>🛒 Installation des tables Shop</h3>";
    // games
    $stmt = $pdo->query("SHOW TABLES LIKE 'games'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table games...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS games (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(200) NOT NULL,
          slug VARCHAR(200) NOT NULL UNIQUE,
          description TEXT NULL,
          short_description VARCHAR(500) NULL,
          image_url VARCHAR(500) NULL,
          thumbnail_url VARCHAR(500) NULL,
          category VARCHAR(50) NOT NULL DEFAULT 'other',
          platform VARCHAR(100) NULL,
          min_players INT NOT NULL DEFAULT 1,
          max_players INT NOT NULL DEFAULT 1,
          age_rating VARCHAR(20) NULL,
          points_per_hour INT NOT NULL DEFAULT 10,
          base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          is_featured TINYINT(1) NOT NULL DEFAULT 0,
          display_order INT NOT NULL DEFAULT 0,
          created_by INT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          INDEX idx_active_featured (is_active, is_featured),
          INDEX idx_category (category),
          INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table games créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table games existe déjà</p>";
    }
    // game_packages
    $stmt = $pdo->query("SHOW TABLES LIKE 'game_packages'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table game_packages...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS game_packages (
          id INT AUTO_INCREMENT PRIMARY KEY,
          game_id INT NOT NULL,
          name VARCHAR(150) NOT NULL,
          duration_minutes INT NOT NULL,
          price DECIMAL(10,2) NOT NULL,
          original_price DECIMAL(10,2) NULL,
          points_earned INT NOT NULL,
          bonus_multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00,
          is_promotional TINYINT(1) NOT NULL DEFAULT 0,
          promotional_label VARCHAR(100) NULL,
          max_purchases_per_user INT NULL,
          available_from DATETIME NULL,
          available_until DATETIME NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          display_order INT NOT NULL DEFAULT 0,
          created_by INT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
          INDEX idx_game_active (game_id, is_active),
          INDEX idx_promotional (is_promotional)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table game_packages créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table game_packages existe déjà</p>";
    }
    // payment_methods
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_methods'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table payment_methods...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS payment_methods (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(100) NOT NULL,
          slug VARCHAR(100) NOT NULL UNIQUE,
          provider VARCHAR(100) NULL,
          api_key_public VARCHAR(500) NULL,
          api_key_secret VARCHAR(500) NULL,
          api_endpoint VARCHAR(500) NULL,
          webhook_secret VARCHAR(500) NULL,
          requires_online_payment TINYINT(1) NOT NULL DEFAULT 1,
          auto_confirm_payment TINYINT(1) NOT NULL DEFAULT 0,
          fee_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
          fee_fixed DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          display_order INT NOT NULL DEFAULT 0,
          instructions TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          INDEX idx_active (is_active),
          INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table payment_methods créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table payment_methods existe déjà</p>";
    }
    // purchases
    $stmt = $pdo->query("SHOW TABLES LIKE 'purchases'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table purchases...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS purchases (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NOT NULL,
          game_id INT NOT NULL,
          package_id INT NULL,
          game_name VARCHAR(200) NOT NULL,
          package_name VARCHAR(150) NULL,
          duration_minutes INT NOT NULL,
          price DECIMAL(10,2) NOT NULL,
          currency VARCHAR(3) NOT NULL DEFAULT 'XOF',
          points_earned INT NOT NULL DEFAULT 0,
          points_credited TINYINT(1) NOT NULL DEFAULT 0,
          payment_method_id INT NULL,
          payment_method_name VARCHAR(100) NULL,
          payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
          payment_reference VARCHAR(255) NULL,
          payment_details JSON NULL,
          confirmed_by INT NULL,
          confirmed_at DATETIME NULL,
          session_status VARCHAR(50) NOT NULL DEFAULT 'pending',
          notes TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
          FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
          FOREIGN KEY (package_id) REFERENCES game_packages(id) ON DELETE SET NULL,
          FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
          INDEX idx_user_status (user_id, payment_status),
          INDEX idx_payment_status (payment_status),
          INDEX idx_payment_reference (payment_reference),
          INDEX idx_session_status (session_status),
          INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table purchases créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table purchases existe déjà</p>";
    }
    // game_sessions
    $stmt = $pdo->query("SHOW TABLES LIKE 'game_sessions'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table game_sessions...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS game_sessions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          purchase_id INT NOT NULL,
          user_id INT NOT NULL,
          game_id INT NOT NULL,
          total_minutes INT NOT NULL,
          used_minutes INT NOT NULL DEFAULT 0,
          status VARCHAR(50) NOT NULL DEFAULT 'pending',
          started_at DATETIME NULL,
          paused_at DATETIME NULL,
          resumed_at DATETIME NULL,
          completed_at DATETIME NULL,
          expires_at DATETIME NULL,
          last_activity_at DATETIME NULL,
          notes TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
          FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
          INDEX idx_user_status (user_id, status),
          INDEX idx_purchase (purchase_id),
          INDEX idx_status (status),
          INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table game_sessions créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table game_sessions existe déjà</p>";
    }

    // 8. Payments & Invoices system
    echo "<h3 class='info'>💳 Installation des tables Paiements & Facturation</h3>";
    // payment_transactions
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_transactions'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table payment_transactions...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS payment_transactions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          purchase_id INT NOT NULL,
          transaction_type ENUM('charge', 'refund', 'chargeback', 'adjustment') NOT NULL,
          amount DECIMAL(10,2) NOT NULL,
          currency VARCHAR(3) NOT NULL DEFAULT 'XOF',
          provider_transaction_id VARCHAR(255) NULL,
          provider_status VARCHAR(100) NULL,
          provider_response JSON NULL,
          notes TEXT NULL,
          created_at DATETIME NOT NULL,
          CONSTRAINT fk_transactions_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
          INDEX idx_purchase (purchase_id),
          INDEX idx_provider_transaction (provider_transaction_id),
          INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table payment_transactions créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table payment_transactions existe déjà</p>";
    }
    // invoices
    $stmt = $pdo->query("SHOW TABLES LIKE 'invoices'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table invoices...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
          id INT AUTO_INCREMENT PRIMARY KEY,
          purchase_id INT NOT NULL UNIQUE,
          user_id INT NOT NULL,
          invoice_number VARCHAR(50) NOT NULL UNIQUE,
          validation_code VARCHAR(32) NOT NULL UNIQUE,
          qr_code_data TEXT NOT NULL,
          qr_code_hash VARCHAR(64) NOT NULL,
          amount DECIMAL(10,2) NOT NULL,
          currency VARCHAR(3) NOT NULL DEFAULT 'XOF',
          duration_minutes INT NOT NULL,
          game_name VARCHAR(200) NOT NULL,
          package_name VARCHAR(150) NULL,
          status ENUM('pending', 'active', 'used', 'expired', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
          issued_at DATETIME NOT NULL,
          expires_at DATETIME NOT NULL,
          activated_at DATETIME NULL,
          used_at DATETIME NULL,
          activated_by INT NULL,
          activation_ip VARCHAR(45) NULL,
          activation_device TEXT NULL,
          scan_attempts INT NOT NULL DEFAULT 0,
          last_scan_attempt DATETIME NULL,
          is_suspicious TINYINT(1) NOT NULL DEFAULT 0,
          fraud_notes TEXT NULL,
          notes TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          CONSTRAINT fk_invoices_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
          CONSTRAINT fk_invoices_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
          INDEX idx_user_status (user_id, status),
          INDEX idx_validation_code (validation_code),
          INDEX idx_invoice_number (invoice_number),
          INDEX idx_status (status),
          INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table invoices créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table invoices existe déjà</p>";
    }
    // invoice_scans
    $stmt = $pdo->query("SHOW TABLES LIKE 'invoice_scans'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table invoice_scans...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_scans (
          id INT AUTO_INCREMENT PRIMARY KEY,
          invoice_id INT NULL,
          validation_code VARCHAR(32) NOT NULL,
          scan_result ENUM('success', 'invalid_code', 'already_used', 'expired', 'cancelled', 'fraud_detected', 'error') NOT NULL,
          scan_message TEXT NULL,
          scanned_by INT NULL,
          scanned_at DATETIME NOT NULL,
          ip_address VARCHAR(45) NULL,
          user_agent TEXT NULL,
          device_info JSON NULL,
          request_headers JSON NULL,
          geolocation JSON NULL,
          CONSTRAINT fk_scans_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
          INDEX idx_invoice (invoice_id),
          INDEX idx_code (validation_code),
          INDEX idx_scanned_at (scanned_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table invoice_scans créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table invoice_scans existe déjà</p>";
    }
    // active_game_sessions_v2
    $stmt = $pdo->query("SHOW TABLES LIKE 'active_game_sessions_v2'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table active_game_sessions_v2...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS active_game_sessions_v2 (
          id INT AUTO_INCREMENT PRIMARY KEY,
          invoice_id INT NOT NULL UNIQUE,
          purchase_id INT NOT NULL,
          user_id INT NOT NULL,
          game_id INT NOT NULL,
          total_minutes INT NOT NULL,
          used_minutes INT NOT NULL DEFAULT 0,
          remaining_minutes INT GENERATED ALWAYS AS (total_minutes - used_minutes) VIRTUAL,
          status ENUM('ready', 'active', 'paused', 'completed', 'expired', 'terminated') NOT NULL DEFAULT 'ready',
          ready_at DATETIME NOT NULL,
          started_at DATETIME NULL,
          last_heartbeat DATETIME NULL,
          paused_at DATETIME NULL,
          resumed_at DATETIME NULL,
          completed_at DATETIME NULL,
          expires_at DATETIME NOT NULL,
          auto_countdown TINYINT(1) NOT NULL DEFAULT 1,
          countdown_interval INT NOT NULL DEFAULT 60,
          last_countdown_update DATETIME NULL,
          total_pause_time INT NOT NULL DEFAULT 0,
          pause_count INT NOT NULL DEFAULT 0,
          monitored_by INT NULL,
          notes TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          CONSTRAINT fk_sessions_v2_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
          CONSTRAINT fk_sessions_v2_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
          CONSTRAINT fk_sessions_v2_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
          CONSTRAINT fk_sessions_v2_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
          INDEX idx_user_status (user_id, status),
          INDEX idx_status (status),
          INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table active_game_sessions_v2 créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table active_game_sessions_v2 existe déjà</p>";
    }
    // session_events
    $stmt = $pdo->query("SHOW TABLES LIKE 'session_events'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table session_events...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS session_events (
          id INT AUTO_INCREMENT PRIMARY KEY,
          session_id INT NOT NULL,
          event_type ENUM('ready', 'start', 'pause', 'resume', 'complete', 'expire', 'terminate', 'countdown_update', 'heartbeat', 'warning_low_time', 'admin_action') NOT NULL,
          event_message TEXT NULL,
          minutes_delta INT NULL,
          minutes_before INT NULL,
          minutes_after INT NULL,
          triggered_by INT NULL,
          triggered_by_system TINYINT(1) NOT NULL DEFAULT 0,
          event_data JSON NULL,
          created_at DATETIME NOT NULL,
          CONSTRAINT fk_events_session FOREIGN KEY (session_id) REFERENCES active_game_sessions_v2(id) ON DELETE CASCADE,
          INDEX idx_session (session_id),
          INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table session_events créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table session_events existe déjà</p>";
    }
    // invoice_audit_log
    $stmt = $pdo->query("SHOW TABLES LIKE 'invoice_audit_log'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table invoice_audit_log...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_audit_log (
          id INT AUTO_INCREMENT PRIMARY KEY,
          invoice_id INT NULL,
          action ENUM('created', 'activated', 'used', 'expired', 'cancelled', 'refunded', 'scan_attempt', 'fraud_detected', 'modified', 'deleted') NOT NULL,
          performed_by INT NULL,
          performed_by_type ENUM('user', 'admin', 'system') NOT NULL,
          action_details TEXT NULL,
          old_values JSON NULL,
          new_values JSON NULL,
          ip_address VARCHAR(45) NULL,
          user_agent TEXT NULL,
          created_at DATETIME NOT NULL,
          CONSTRAINT fk_audit_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
          INDEX idx_invoice (invoice_id),
          INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table invoice_audit_log créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table invoice_audit_log existe déjà</p>";
    }

    // 9. Content & Tournaments and missing tables used by admin
    echo "<h3 class='info'>🗂️ Installation des tables Contenu & Tournois</h3>";
    // content (used by dashboard)
    $stmt = $pdo->query("SHOW TABLES LIKE 'content'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table content...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS content (
          id INT AUTO_INCREMENT PRIMARY KEY,
          title VARCHAR(255) NOT NULL,
          body TEXT NULL,
          is_published TINYINT(1) NOT NULL DEFAULT 0,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          INDEX idx_published (is_published)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table content créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table content existe déjà</p>";
    }
    // gallery
    $stmt = $pdo->query("SHOW TABLES LIKE 'gallery'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table gallery...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS gallery (
          id INT AUTO_INCREMENT PRIMARY KEY,
          title VARCHAR(255),
          description TEXT,
          image_url VARCHAR(500) NOT NULL,
          category VARCHAR(50) DEFAULT 'general',
          tags TEXT,
          uploaded_by INT,
          is_featured TINYINT(1) DEFAULT 0,
          created_at DATETIME NOT NULL,
          INDEX idx_category (category),
          INDEX idx_featured (is_featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table gallery créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table gallery existe déjà</p>";
    }
    // events (with 'type' column expected by stats endpoints)
    $stmt = $pdo->query("SHOW TABLES LIKE 'events'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table events...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
          id INT AUTO_INCREMENT PRIMARY KEY,
          title VARCHAR(255) NOT NULL,
          type VARCHAR(50) DEFAULT 'general',
          description TEXT,
          image_url VARCHAR(500),
          date DATE NULL,
          participants INT NULL,
          created_at DATETIME NOT NULL,
          INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table events créée</p>";
    } else {
        // ensure column 'type' exists
        try { $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS type VARCHAR(50) DEFAULT 'general'"); } catch (Throwable $e) {}
        echo "<p class='info'>ℹ️ Table events existe déjà (type assuré)</p>";
    }
    // tournaments
    $stmt = $pdo->query("SHOW TABLES LIKE 'tournaments'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table tournaments...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS tournaments (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(255) NOT NULL,
          description TEXT,
          game_id INT NULL,
          status VARCHAR(20) DEFAULT 'upcoming',
          image_url VARCHAR(500) NULL,
          created_at DATETIME NOT NULL,
          INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table tournaments créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table tournaments existe déjà</p>";
    }

    // 10. Rewards & Conversions: align schema with dashboard expectations
    echo "<h3 class='info'>🏆 Installation des tables Récompenses & Conversions</h3>";
    // rewards
    $stmt = $pdo->query("SHOW TABLES LIKE 'rewards'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table rewards...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS rewards (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(150) NOT NULL,
          cost INT NOT NULL,
          available TINYINT(1) NOT NULL DEFAULT 1,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table rewards créée</p>";
    }
    // reward_redemptions with status
    $stmt = $pdo->query("SHOW TABLES LIKE 'reward_redemptions'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table reward_redemptions...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS reward_redemptions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          reward_id INT NOT NULL,
          user_id INT NOT NULL,
          cost INT NOT NULL,
          status VARCHAR(20) NOT NULL DEFAULT 'pending',
          created_at DATETIME NOT NULL,
          CONSTRAINT fk_rr_reward FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE,
          CONSTRAINT fk_rr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table reward_redemptions créée</p>";
    } else {
        // ensure status column exists
        try { $pdo->exec("ALTER TABLE reward_redemptions ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'pending'"); } catch (Throwable $e) {}
        echo "<p class='info'>ℹ️ Table reward_redemptions existe déjà (status assuré)</p>";
    }
    // point_conversions
    $stmt = $pdo->query("SHOW TABLES LIKE 'point_conversions'");
    if (!$stmt->fetch()) {
        echo "<p class='info'>🔧 Création table point_conversions...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS point_conversions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NOT NULL,
          points_spent INT NOT NULL,
          minutes_gained INT NOT NULL,
          created_at DATETIME NOT NULL,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
          INDEX idx_user (user_id),
          INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>✅ Table point_conversions créée</p>";
    } else {
        echo "<p class='info'>ℹ️ Table point_conversions existe déjà</p>";
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
