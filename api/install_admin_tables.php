<?php
// api/install_admin_tables.php
// Minimal installer to create tables required by the admin dashboard

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = get_db();
    echo "Connected to DB\n";

    // Users
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
    echo "users OK\n";

    // Points transactions
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
    echo "points_transactions OK\n";

    // Ensure points_transactions has required schema for secure transactions
    // 1) Ensure 'purchase', 'conversion' and 'refund' values allowed in ENUM
    try {
        $col = $pdo->query("SHOW COLUMNS FROM points_transactions LIKE 'type'")->fetch(PDO::FETCH_ASSOC);
        if ($col && isset($col['Type']) && stripos($col['Type'], "enum") !== false && (stripos($col['Type'], "'refund'") === false || stripos($col['Type'], "'conversion'") === false || stripos($col['Type'], "'purchase'") === false)) {
            $pdo->exec("ALTER TABLE points_transactions MODIFY type ENUM('game','tournament','bonus','reservation','friend','adjustment','reward','purchase','conversion','refund') NULL");
            echo "points_transactions.type extended with 'purchase', 'conversion' and 'refund'\n";
        }
    } catch (Throwable $e) {
        // non-fatal
    }
    // 2) Ensure balance_after column exists
    try {
        $has = $pdo->query("SHOW COLUMNS FROM points_transactions LIKE 'balance_after'")->fetch();
        if (!$has) {
            $pdo->exec("ALTER TABLE points_transactions ADD COLUMN balance_after INT NULL AFTER change_amount");
            echo "points_transactions.balance_after added\n";
        }
    } catch (Throwable $e) {
        // non-fatal
    }
    // 3) Ensure reference_type/reference_id columns + index exist
    try {
        $hasRefType = $pdo->query("SHOW COLUMNS FROM points_transactions LIKE 'reference_type'")->fetch();
        if (!$hasRefType) {
            $pdo->exec("ALTER TABLE points_transactions ADD COLUMN reference_type VARCHAR(50) NULL AFTER type");
            echo "points_transactions.reference_type added\n";
        }
        $hasRefId = $pdo->query("SHOW COLUMNS FROM points_transactions LIKE 'reference_id'")->fetch();
        if (!$hasRefId) {
            $pdo->exec("ALTER TABLE points_transactions ADD COLUMN reference_id INT NULL AFTER reference_type");
            echo "points_transactions.reference_id added\n";
        }
        // Add composite index if missing
        $idx = $pdo->query("SHOW INDEX FROM points_transactions WHERE Key_name = 'idx_reference'")->fetch();
        if (!$idx) {
            $pdo->exec("ALTER TABLE points_transactions ADD INDEX idx_reference (reference_type, reference_id)");
            echo "points_transactions.idx_reference added\n";
        }
    } catch (Throwable $e) {
        // non-fatal
    }

    // Games
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
    echo "games OK\n";

    // Ensure optional columns exist for reservations
    try {
        $col = $pdo->query("SHOW COLUMNS FROM games LIKE 'is_reservable'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE games ADD COLUMN is_reservable TINYINT(1) NOT NULL DEFAULT 0 AFTER base_price");
            echo "games.is_reservable added\n";
        }
    } catch (Throwable $e) { /* ignore */ }
    try {
        $col = $pdo->query("SHOW COLUMNS FROM games LIKE 'reservation_fee'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE games ADD COLUMN reservation_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER is_reservable");
            echo "games.reservation_fee added\n";
        }
    } catch (Throwable $e) { /* ignore */ }

    // Game packages
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
    echo "game_packages OK\n";
    // Extend game_packages to match local dump
    try {
        $col = $pdo->query("SHOW COLUMNS FROM game_packages LIKE 'points_cost'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE game_packages ADD COLUMN points_cost INT NULL COMMENT 'Coût en points si is_points_only = 1' AFTER points_earned");
            echo "game_packages.points_cost added\n";
        }
    } catch (Throwable $e) { /* ignore */ }
    try {
        $col = $pdo->query("SHOW COLUMNS FROM game_packages LIKE 'reward_id'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE game_packages ADD COLUMN reward_id INT NULL COMMENT 'ID de la récompense liée' AFTER points_cost");
            echo "game_packages.reward_id added\n";
        }
    } catch (Throwable $e) { /* ignore */ }
    try {
        $col = $pdo->query("SHOW COLUMNS FROM game_packages LIKE 'is_points_only'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE game_packages ADD COLUMN is_points_only TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Package payable uniquement en points' AFTER is_active");
            echo "game_packages.is_points_only added\n";
        }
    } catch (Throwable $e) { /* ignore */ }

    // Payment methods
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
    echo "payment_methods OK\n";
    try {
        $col = $pdo->query("SHOW COLUMNS FROM payment_methods LIKE 'auto_confirm'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE payment_methods ADD COLUMN auto_confirm TINYINT(1) NOT NULL DEFAULT 0 AFTER requires_online_payment");
            echo "payment_methods.auto_confirm added\n";
        }
    } catch (Throwable $e) { }
    try {
        $col = $pdo->query("SHOW COLUMNS FROM payment_methods LIKE 'description'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE payment_methods ADD COLUMN description TEXT NULL AFTER slug");
            echo "payment_methods.description added\n";
        }
    } catch (Throwable $e) { }

    // Purchases
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
    echo "purchases OK\n";
    // Extend purchases to support points-based and secure transactions
    try { $c = $pdo->query("SHOW COLUMNS FROM purchases LIKE 'paid_with_points'")->fetch(); if (!$c) { $pdo->exec("ALTER TABLE purchases ADD COLUMN paid_with_points TINYINT(1) NOT NULL DEFAULT 0 AFTER points_credited"); echo "purchases.paid_with_points added\n"; } } catch (Throwable $e) {}
    try { $c = $pdo->query("SHOW COLUMNS FROM purchases LIKE 'points_spent'")->fetch(); if (!$c) { $pdo->exec("ALTER TABLE purchases ADD COLUMN points_spent INT NOT NULL DEFAULT 0 AFTER paid_with_points"); echo "purchases.points_spent added\n"; } } catch (Throwable $e) {}
    try { $c = $pdo->query("SHOW COLUMNS FROM purchases LIKE 'transaction_id'")->fetch(); if (!$c) { $pdo->exec("ALTER TABLE purchases ADD COLUMN transaction_id INT NULL COMMENT 'ID de la transaction sécurisée' AFTER id"); echo "purchases.transaction_id added\n"; } } catch (Throwable $e) {}

    // Payment transactions (for provider-level operations)
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_id INT NOT NULL,
        transaction_type ENUM('charge','refund','chargeback','adjustment') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) NOT NULL DEFAULT 'XOF',
        provider_transaction_id VARCHAR(255) NULL,
        provider_status VARCHAR(100) NULL,
        provider_response JSON NULL,
        notes TEXT NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_paytx_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
        INDEX idx_purchase (purchase_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "payment_transactions OK\n";

    // Secure purchase transactions (idempotent controller)
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reward_id INT NULL,
        purchase_id INT NULL COMMENT 'ID de l\'achat créé (si succès)',
        points_tx_id INT NULL COMMENT 'ID de la transaction de points',
        idempotency_key VARCHAR(255) NOT NULL COMMENT 'Clé unique pour éviter doublons',
        status ENUM('pending','processing','completed','failed','refunded') NOT NULL DEFAULT 'pending',
        step VARCHAR(50) NULL COMMENT 'Étape actuelle du processus',
        points_amount INT NULL COMMENT 'Montant en points',
        money_amount DECIMAL(10,2) NULL COMMENT 'Montant en argent',
        currency VARCHAR(10) NULL,
        failure_reason TEXT NULL COMMENT 'Raison de l\'échec si failed',
        refund_reason TEXT NULL COMMENT 'Raison du remboursement',
        refunded_by INT NULL COMMENT 'Admin qui a effectué le remboursement',
        created_at DATETIME NOT NULL,
        completed_at DATETIME NULL,
        failed_at DATETIME NULL,
        refunded_at DATETIME NULL,
        INDEX idx_user (user_id),
        INDEX idx_key (idempotency_key),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "purchase_transactions OK\n";

    // Trigger to protect completed transactions
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS prevent_completed_tx_modification");
        $pdo->exec("CREATE TRIGGER prevent_completed_tx_modification BEFORE UPDATE ON purchase_transactions FOR EACH ROW BEGIN IF OLD.status = 'completed' AND NEW.status != 'refunded' AND NEW.status != 'completed' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Impossible de modifier une transaction complétée'; END IF; END");
        echo "trigger prevent_completed_tx_modification OK\n";
    } catch (Throwable $e) {
        echo "trigger prevent_completed_tx_modification error: " . $e->getMessage() . "\n";
    }

    // Game sessions
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
    echo "game_sessions OK\n";
    // Ensure generated column remaining_minutes exists (parity with local dump)
    try {
        $has = $pdo->query("SHOW COLUMNS FROM game_sessions LIKE 'remaining_minutes'")->fetch();
        if (!$has) {
            $pdo->exec("ALTER TABLE game_sessions ADD COLUMN remaining_minutes INT GENERATED ALWAYS AS (total_minutes - used_minutes) VIRTUAL");
            echo "game_sessions.remaining_minutes added\n";
        }
    } catch (Throwable $e) { /* ignore */ }

    // Rewards
    $pdo->exec("CREATE TABLE IF NOT EXISTS rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        cost INT NOT NULL,
        available TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "rewards OK\n";
    try { $c=$pdo->query("SHOW COLUMNS FROM rewards LIKE 'description'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE rewards ADD COLUMN description TEXT NULL AFTER name"); echo "rewards.description added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM rewards LIKE 'category'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE rewards ADD COLUMN category VARCHAR(100) NULL AFTER description"); echo "rewards.category added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM rewards LIKE 'reward_type'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE rewards ADD COLUMN reward_type ENUM('game_time','discount','item','badge','other','physical','digital','game_package') DEFAULT 'other' AFTER category"); echo "rewards.reward_type added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM rewards LIKE 'game_package_id'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE rewards ADD COLUMN game_package_id INT NULL AFTER reward_type"); echo "rewards.game_package_id added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM rewards LIKE 'game_time_minutes'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE rewards ADD COLUMN game_time_minutes INT NOT NULL DEFAULT 0 AFTER game_package_id"); echo "rewards.game_time_minutes added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM rewards LIKE 'stock_quantity'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE rewards ADD COLUMN stock_quantity INT NULL AFTER available"); echo "rewards.stock_quantity added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM rewards LIKE 'max_per_user'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE rewards ADD COLUMN max_per_user INT NULL AFTER stock_quantity"); echo "rewards.max_per_user added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM rewards LIKE 'is_featured'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE rewards ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER max_per_user"); echo "rewards.is_featured added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM rewards LIKE 'display_order'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE rewards ADD COLUMN display_order INT NOT NULL DEFAULT 0 AFTER is_featured"); echo "rewards.display_order added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM rewards LIKE 'image_url'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE rewards ADD COLUMN image_url VARCHAR(500) NULL AFTER display_order"); echo "rewards.image_url added\n";} } catch (Throwable $e) {}

    // Reward redemptions
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
    echo "reward_redemptions OK\n";
    try { $c=$pdo->query("SHOW COLUMNS FROM reward_redemptions LIKE 'notes'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE reward_redemptions ADD COLUMN notes TEXT NULL AFTER status"); echo "reward_redemptions.notes added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM reward_redemptions LIKE 'updated_at'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE reward_redemptions ADD COLUMN updated_at DATETIME NULL AFTER created_at"); echo "reward_redemptions.updated_at added\n";} } catch (Throwable $e) {}

    // Point conversions
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
    echo "point_conversions OK\n";
    try { $c=$pdo->query("SHOW COLUMNS FROM point_conversions LIKE 'game_id'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE point_conversions ADD COLUMN game_id INT NULL COMMENT 'Jeu choisi (NULL = tous)' AFTER minutes_gained"); echo "point_conversions.game_id added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM point_conversions LIKE 'conversion_rate'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE point_conversions ADD COLUMN conversion_rate INT NOT NULL DEFAULT 0 COMMENT 'X points = 1 minute' AFTER game_id"); echo "point_conversions.conversion_rate added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM point_conversions LIKE 'fee_charged'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE point_conversions ADD COLUMN fee_charged DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER conversion_rate"); echo "point_conversions.fee_charged added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM point_conversions LIKE 'status'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE point_conversions ADD COLUMN status ENUM('pending','active','used','expired','cancelled') NOT NULL DEFAULT 'active' AFTER fee_charged"); echo "point_conversions.status added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM point_conversions LIKE 'expires_at'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE point_conversions ADD COLUMN expires_at DATETIME NOT NULL AFTER created_at"); echo "point_conversions.expires_at added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM point_conversions LIKE 'used_at'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE point_conversions ADD COLUMN used_at DATETIME NULL AFTER expires_at"); echo "point_conversions.used_at added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM point_conversions LIKE 'minutes_used'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE point_conversions ADD COLUMN minutes_used INT NOT NULL DEFAULT 0 AFTER used_at"); echo "point_conversions.minutes_used added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM point_conversions LIKE 'minutes_remaining'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE point_conversions ADD COLUMN minutes_remaining INT GENERATED ALWAYS AS (minutes_gained - minutes_used) VIRTUAL AFTER minutes_used"); echo "point_conversions.minutes_remaining added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM point_conversions LIKE 'purchase_id'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE point_conversions ADD COLUMN purchase_id INT NULL AFTER minutes_remaining"); echo "point_conversions.purchase_id added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM point_conversions LIKE 'notes'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE point_conversions ADD COLUMN notes TEXT NULL AFTER purchase_id"); echo "point_conversions.notes added\n";} } catch (Throwable $e) {}
    try { $idx=$pdo->query("SHOW INDEX FROM point_conversions WHERE Key_name = 'idx_status'")->fetch(); if(!$idx){$pdo->exec("ALTER TABLE point_conversions ADD INDEX idx_status (status)"); echo "point_conversions.idx_status added\n";} } catch (Throwable $e) {}
    try { $idx=$pdo->query("SHOW INDEX FROM point_conversions WHERE Key_name = 'idx_expires'")->fetch(); if(!$idx){$pdo->exec("ALTER TABLE point_conversions ADD INDEX idx_expires (expires_at)"); echo "point_conversions.idx_expires added\n";} } catch (Throwable $e) {}
    // Composite index for performance: (user_id, status, expires_at)
    try { $idx=$pdo->query("SHOW INDEX FROM point_conversions WHERE Key_name = 'idx_user_active'")->fetch(); if(!$idx){$pdo->exec("ALTER TABLE point_conversions ADD INDEX idx_user_active (user_id, status, expires_at)"); echo "point_conversions.idx_user_active added\n";} } catch (Throwable $e) {}

    $pdo->exec("CREATE TABLE IF NOT EXISTS point_conversion_config (
        id INT NOT NULL DEFAULT 1 PRIMARY KEY,
        points_per_minute INT NOT NULL DEFAULT 10,
        min_conversion_points INT NOT NULL DEFAULT 100,
        max_conversion_per_day INT NULL DEFAULT 3,
        conversion_fee_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        min_minutes_per_conversion INT NOT NULL DEFAULT 10,
        max_minutes_per_conversion INT NULL DEFAULT 300,
        converted_time_expiry_days INT NOT NULL DEFAULT 30,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        notes TEXT NULL,
        updated_at DATETIME NOT NULL,
        updated_by INT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "point_conversion_config OK\n";
    try {
        $pdo->exec("INSERT INTO point_conversion_config (id, points_per_minute, min_conversion_points, max_conversion_per_day, conversion_fee_percent, min_minutes_per_conversion, max_minutes_per_conversion, converted_time_expiry_days, is_active, notes, updated_at, updated_by)
                    SELECT 1, 10, 100, 3, 0.00, 10, 300, 30, 1, 'Configuration par défaut: 10 points = 1 minute, max 3 conversions/jour', NOW(), NULL
                    WHERE NOT EXISTS (SELECT 1 FROM point_conversion_config WHERE id = 1)");
        echo "point_conversion_config default row ensured\n";
    } catch (Throwable $e) { }
    // SQL function used by API: get_user_converted_minutes
    try {
        $pdo->exec("DROP FUNCTION IF EXISTS get_user_converted_minutes");
        $pdo->exec("CREATE FUNCTION get_user_converted_minutes(p_user_id INT)\nRETURNS INT\nDETERMINISTIC\nREADS SQL DATA\nBEGIN\n  DECLARE total_minutes INT DEFAULT 0;\n  SELECT COALESCE(SUM(minutes_gained - minutes_used), 0) INTO total_minutes\n  FROM point_conversions\n  WHERE user_id = p_user_id\n    AND status = 'active'\n    AND expires_at > NOW();\n  RETURN total_minutes;\nEND");
        echo "function get_user_converted_minutes OK\n";
    } catch (Throwable $e) {
        echo "function get_user_converted_minutes error: " . $e->getMessage() . "\n";
    }

    // Content (for dashboard counts)
    $pdo->exec("CREATE TABLE IF NOT EXISTS content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        body TEXT NULL,
        is_published TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_published (is_published)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "content OK\n";

    // Upgrade content table to match local schema (add columns if missing)
    try {
        $cols = [
            ['name' => 'type', 'ddl' => "ADD COLUMN type ENUM('news','event','stream','gallery') NOT NULL AFTER id"],
            ['name' => 'content', 'ddl' => "ADD COLUMN content LONGTEXT NULL AFTER description"],
            ['name' => 'image_url', 'ddl' => "ADD COLUMN image_url VARCHAR(500) NULL AFTER content"],
            ['name' => 'video_url', 'ddl' => "ADD COLUMN video_url VARCHAR(500) NULL AFTER image_url"],
            ['name' => 'external_link', 'ddl' => "ADD COLUMN external_link VARCHAR(500) NULL AFTER video_url"],
            ['name' => 'event_date', 'ddl' => "ADD COLUMN event_date DATETIME NULL AFTER external_link"],
            ['name' => 'event_location', 'ddl' => "ADD COLUMN event_location VARCHAR(255) NULL AFTER event_date"],
            ['name' => 'stream_url', 'ddl' => "ADD COLUMN stream_url VARCHAR(500) NULL AFTER event_location"],
            ['name' => 'is_pinned', 'ddl' => "ADD COLUMN is_pinned TINYINT(1) NOT NULL DEFAULT 0 AFTER is_published"],
            ['name' => 'published_at', 'ddl' => "ADD COLUMN published_at DATETIME NULL AFTER is_pinned"],
            ['name' => 'views_count', 'ddl' => "ADD COLUMN views_count INT NOT NULL DEFAULT 0 AFTER published_at"],
            ['name' => 'shares_count', 'ddl' => "ADD COLUMN shares_count INT NOT NULL DEFAULT 0 AFTER views_count"],
            ['name' => 'created_by', 'ddl' => "ADD COLUMN created_by INT NULL AFTER shares_count"],
        ];
        foreach ($cols as $c) {
            $has = $pdo->query("SHOW COLUMNS FROM content LIKE '" . $c['name'] . "'")->fetch();
            if (!$has) {
                $pdo->exec("ALTER TABLE content " . $c['ddl']);
                echo "content." . $c['name'] . " added\n";
            }
        }
    } catch (Throwable $e) { /* ignore */ }

    // Gallery (for statistics)
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
    echo "gallery OK\n";
    // Extend gallery to match local dump
    try { $c=$pdo->query("SHOW COLUMNS FROM gallery LIKE 'thumbnail_url'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE gallery ADD COLUMN thumbnail_url VARCHAR(500) NULL AFTER image_url"); echo "gallery.thumbnail_url added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM gallery LIKE 'event_id'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE gallery ADD COLUMN event_id INT NULL AFTER category"); echo "gallery.event_id added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM gallery LIKE 'status'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE gallery ADD COLUMN status ENUM('active','archived') NOT NULL DEFAULT 'active' AFTER event_id"); echo "gallery.status added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM gallery LIKE 'display_order'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE gallery ADD COLUMN display_order INT NOT NULL DEFAULT 0 AFTER status"); echo "gallery.display_order added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM gallery LIKE 'views'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE gallery ADD COLUMN views INT NOT NULL DEFAULT 0 AFTER display_order"); echo "gallery.views added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM gallery LIKE 'likes'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE gallery ADD COLUMN likes INT NOT NULL DEFAULT 0 AFTER views"); echo "gallery.likes added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM gallery LIKE 'created_by'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE gallery ADD COLUMN created_by INT NULL AFTER likes"); echo "gallery.created_by added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM gallery LIKE 'updated_at'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE gallery ADD COLUMN updated_at DATETIME NULL AFTER created_at"); echo "gallery.updated_at added\n";} } catch (Throwable $e) {}
    // Fix category to ENUM
    try { $col=$pdo->query("SHOW COLUMNS FROM gallery LIKE 'category'")->fetch(PDO::FETCH_ASSOC); if($col && stripos($col['Type'],'varchar')!==false){$pdo->exec("ALTER TABLE gallery MODIFY category ENUM('tournament','event','stream','general','vr','retro') NOT NULL DEFAULT 'general'"); echo "gallery.category converted to ENUM\n";} } catch (Throwable $e) {}

    // Events (string type expected by admin statistics)
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
    echo "events OK\n";
    // Fix events.type to ENUM
    try { $col=$pdo->query("SHOW COLUMNS FROM events LIKE 'type'")->fetch(PDO::FETCH_ASSOC); if($col && stripos($col['Type'],'varchar')!==false){$pdo->exec("ALTER TABLE events MODIFY type ENUM('tournament','event','stream','news') NOT NULL"); echo "events.type converted to ENUM\n";} } catch (Throwable $e) {}
    // Fix events.title to VARCHAR(200)
    try { $col=$pdo->query("SHOW COLUMNS FROM events LIKE 'title'")->fetch(PDO::FETCH_ASSOC); if($col && stripos($col['Type'],'varchar(255)')!==false){$pdo->exec("ALTER TABLE events MODIFY title VARCHAR(200) NOT NULL"); echo "events.title resized to 200\n";} } catch (Throwable $e) {}
    // Fix events.date column type
    try { $col=$pdo->query("SHOW COLUMNS FROM events LIKE 'date'")->fetch(PDO::FETCH_ASSOC); if(!$col){$pdo->exec("ALTER TABLE events ADD COLUMN date DATE NOT NULL AFTER title"); echo "events.date added\n";} } catch (Throwable $e) {}

    // Upgrade events with optional columns from local
    try {
        $evCols = [
            ['winner', "ADD COLUMN winner VARCHAR(100) NULL AFTER participants"],
            ['likes', "ADD COLUMN likes INT NOT NULL DEFAULT 0 AFTER winner"],
            ['comments', "ADD COLUMN comments INT NOT NULL DEFAULT 0 AFTER likes"],
        ];
        foreach ($evCols as $c) {
            $has = $pdo->query("SHOW COLUMNS FROM events LIKE '" . $c[0] . "'")->fetch();
            if (!$has) { $pdo->exec("ALTER TABLE events " . $c[1]); echo "events." . $c[0] . " added\n"; }
        }
    } catch (Throwable $e) { /* ignore */ }

    // Tournaments
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
    echo "tournaments OK\n";
    // Extend tournaments to match local dump
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'type'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN type ENUM('single_elimination','double_elimination','round_robin','swiss','free_for_all') DEFAULT 'single_elimination' AFTER game_id"); echo "tournaments.type added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'max_participants'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN max_participants INT NOT NULL DEFAULT 0 AFTER type"); echo "tournaments.max_participants added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'entry_fee'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN entry_fee INT DEFAULT 0 COMMENT 'Coût en points' AFTER max_participants"); echo "tournaments.entry_fee added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'prize_pool'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN prize_pool INT DEFAULT 0 AFTER entry_fee"); echo "tournaments.prize_pool added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'first_place_prize'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN first_place_prize INT DEFAULT 0 AFTER prize_pool"); echo "tournaments.first_place_prize added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'second_place_prize'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN second_place_prize INT DEFAULT 0 AFTER first_place_prize"); echo "tournaments.second_place_prize added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'third_place_prize'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN third_place_prize INT DEFAULT 0 AFTER second_place_prize"); echo "tournaments.third_place_prize added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'start_date'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN start_date DATETIME NULL AFTER third_place_prize"); echo "tournaments.start_date added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'end_date'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN end_date DATETIME NULL AFTER start_date"); echo "tournaments.end_date added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'registration_deadline'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN registration_deadline DATETIME NULL AFTER end_date"); echo "tournaments.registration_deadline added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'rules'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN rules TEXT NULL AFTER registration_deadline"); echo "tournaments.rules added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'stream_url'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN stream_url VARCHAR(500) NULL AFTER image_url"); echo "tournaments.stream_url added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'is_featured'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER status"); echo "tournaments.is_featured added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'winner_id'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN winner_id INT NULL AFTER is_featured"); echo "tournaments.winner_id added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'created_by'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN created_by INT NULL AFTER winner_id"); echo "tournaments.created_by added\n";} } catch (Throwable $e) {}
    try { $c=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'updated_at'")->fetch(); if(!$c){$pdo->exec("ALTER TABLE tournaments ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at"); echo "tournaments.updated_at added\n";} } catch (Throwable $e) {}
    // Fix status column to be ENUM
    try { $col=$pdo->query("SHOW COLUMNS FROM tournaments LIKE 'status'")->fetch(PDO::FETCH_ASSOC); if($col && stripos($col['Type'],'varchar')!==false){$pdo->exec("ALTER TABLE tournaments MODIFY status ENUM('upcoming','registration_open','registration_closed','ongoing','completed','cancelled') DEFAULT 'upcoming'"); echo "tournaments.status converted to ENUM\n";} } catch (Throwable $e) {}
    $pdo->exec("CREATE TABLE IF NOT EXISTS tournament_matches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tournament_id INT NOT NULL,
        `round` INT NOT NULL,
        match_number INT NOT NULL,
        player1_id INT NULL,
        player2_id INT NULL,
        winner_id INT NULL,
        player1_score INT NOT NULL DEFAULT 0,
        player2_score INT NOT NULL DEFAULT 0,
        status ENUM('pending','ongoing','completed','forfeit') DEFAULT 'pending',
        scheduled_time DATETIME NULL,
        started_at DATETIME NULL,
        completed_at DATETIME NULL,
        notes TEXT NULL,
        CONSTRAINT fk_tm_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
        INDEX idx_tournament (tournament_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "tournament_matches OK\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS tournament_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tournament_id INT NOT NULL,
        user_id INT NOT NULL,
        team_name VARCHAR(255) NULL,
        status ENUM('registered','confirmed','checked_in','disqualified','withdrawn') DEFAULT 'registered',
        placement INT NULL,
        points_earned INT NOT NULL DEFAULT 0,
        prize_won INT NOT NULL DEFAULT 0,
        registered_at DATETIME NOT NULL,
        checked_in_at DATETIME NULL,
        CONSTRAINT fk_tp_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
        CONSTRAINT fk_tp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_tournament (tournament_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "tournament_participants OK\n";

    // Daily bonuses
    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_bonuses (
        user_id INT PRIMARY KEY,
        last_claim_date DATE NOT NULL,
        CONSTRAINT fk_daily_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "daily_bonuses OK\n";

    // Game reservations
    $pdo->exec("CREATE TABLE IF NOT EXISTS game_reservations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        game_id INT NOT NULL,
        purchase_id INT NULL,
        scheduled_start DATETIME NOT NULL,
        scheduled_end DATETIME NOT NULL,
        duration_minutes INT NOT NULL,
        base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        reservation_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        currency VARCHAR(3) NOT NULL DEFAULT 'XOF',
        status ENUM('pending_payment','paid','cancelled','completed','no_show') NOT NULL DEFAULT 'pending_payment',
        notes TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_res_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
        CONSTRAINT fk_res_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL,
        INDEX idx_user (user_id),
        INDEX idx_game (game_id),
        INDEX idx_status (status),
        INDEX idx_start (scheduled_start)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "game_reservations OK\n";

    // Badges (gamification)
    $pdo->exec("CREATE TABLE IF NOT EXISTS badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        icon VARCHAR(255) NULL,
        category ENUM('points','activity','social','achievement','special') NOT NULL DEFAULT 'achievement',
        requirement_type ENUM('points_total','points_earned','days_active','games_played','events_attended','friends_referred','login_streak','special') NOT NULL,
        requirement_value INT NOT NULL,
        rarity ENUM('common','rare','epic','legendary') NOT NULL DEFAULT 'common',
        points_reward INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "badges OK\n";

    // Bonus multipliers
    $pdo->exec("CREATE TABLE IF NOT EXISTS bonus_multipliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00,
        reason VARCHAR(255) NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_bonus_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "bonus_multipliers OK\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        level_number INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        points_required INT NOT NULL,
        points_bonus INT NOT NULL DEFAULT 0,
        color VARCHAR(20) NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "levels OK\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_streaks (
        user_id INT PRIMARY KEY,
        current_streak INT NOT NULL DEFAULT 0,
        longest_streak INT NOT NULL DEFAULT 0,
        last_login_date DATE NOT NULL,
        CONSTRAINT fk_ls_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "login_streaks OK\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS points_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action_type ENUM('game_played','event_attended','tournament_win','tournament_participate','friend_referred','daily_login','profile_complete','first_purchase','review_written','share_social') NOT NULL,
        points_amount INT NOT NULL,
        description VARCHAR(255) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "points_rules OK\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS points_packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        points_amount INT NOT NULL,
        bonus_points INT DEFAULT 0,
        price DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'XOF',
        discount_percentage INT DEFAULT 0,
        is_featured TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        display_order INT DEFAULT 0,
        image_url VARCHAR(500) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "points_packages OK\n";
    // Purchases of points packages (admin stats dependency)
    $pdo->exec("CREATE TABLE IF NOT EXISTS points_package_purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        package_id INT NOT NULL,
        user_id INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) NOT NULL DEFAULT 'XOF',
        payment_status ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
        payment_method_id INT NULL,
        payment_reference VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_ppp_package FOREIGN KEY (package_id) REFERENCES points_packages(id) ON DELETE CASCADE,
        CONSTRAINT fk_ppp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_ppp_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
        INDEX idx_package_status (package_id, payment_status),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "points_package_purchases OK\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        badge_id INT NOT NULL,
        earned_at DATETIME NOT NULL,
        CONSTRAINT fk_ub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_ub_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_badge (badge_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "user_badges OK\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_stats (
        user_id INT PRIMARY KEY,
        games_played INT NOT NULL DEFAULT 0,
        events_attended INT NOT NULL DEFAULT 0,
        tournaments_won INT NOT NULL DEFAULT 0,
        tournaments_participated INT NOT NULL DEFAULT 0,
        friends_referred INT NOT NULL DEFAULT 0,
        total_points_earned INT NOT NULL DEFAULT 0,
        total_points_spent INT NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL,
        rewards_redeemed INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "user_stats OK\n";

    // Content items (advanced CMS)
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(200) NOT NULL,
        excerpt TEXT NULL,
        content TEXT NULL,
        featured_image VARCHAR(500) NULL,
        content_type ENUM('article','news','tutorial','update','announcement') NOT NULL DEFAULT 'article',
        status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
        author_id INT NULL,
        published_at DATETIME NULL,
        views INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_slug (slug),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "content_items OK\n";

    // Content comments
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        parent_id INT NULL,
        is_approved TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        CONSTRAINT fk_cc_content FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
        CONSTRAINT fk_cc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_content (content_id),
        INDEX idx_parent (parent_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "content_comments OK\n";

    // Content likes
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_cl_content FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
        CONSTRAINT fk_cl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_content (content_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "content_likes OK\n";

    // Content reactions
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_id INT NOT NULL,
        user_id INT NOT NULL,
        reaction_type ENUM('like','love','wow','haha','sad','angry') NOT NULL DEFAULT 'like',
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_cr_content FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
        CONSTRAINT fk_cr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_content (content_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "content_reactions OK\n";

    // Content shares
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_shares (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_id INT NOT NULL,
        user_id INT NULL,
        platform ENUM('facebook','twitter','whatsapp','telegram','copy_link') NOT NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_cs_content FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
        INDEX idx_content (content_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "content_shares OK\n";

    // Conversion usage log
    $pdo->exec("CREATE TABLE IF NOT EXISTS conversion_usage_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversion_id INT NOT NULL,
        minutes_used INT NOT NULL,
        used_for VARCHAR(200) NULL,
        purchase_id INT NULL,
        session_id INT NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_conversion (conversion_id),
        INDEX idx_purchase (purchase_id),
        INDEX idx_session (session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "conversion_usage_log OK\n";

    // Deleted users log
    $pdo->exec("CREATE TABLE IF NOT EXISTS deleted_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        email VARCHAR(191) NOT NULL,
        deletion_reason TEXT NOT NULL,
        deleted_by INT NOT NULL,
        deleted_at DATETIME NOT NULL,
        CONSTRAINT fk_du_admin FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "deleted_users OK\n";

    // Invoices and related
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
    echo "invoices OK\n";

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
    echo "invoice_scans OK\n";

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
    echo "active_game_sessions_v2 OK\n";

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
    echo "session_events OK\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS session_activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        activity_type ENUM('start','pause','resume','complete','expire','cancel','time_update') NOT NULL,
        minutes_used INT NOT NULL DEFAULT 0,
        description VARCHAR(500) DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "session_activities OK\n";

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
    echo "invoice_audit_log OK\n";

    // Create view used by admin/active_sessions.php
    try {
        $pdo->exec("CREATE OR REPLACE VIEW session_summary AS
            SELECT 
              s.id,
              s.invoice_id,
              s.purchase_id,
              s.user_id,
              u.username,
              u.avatar_url,
              u.level,
              u.points,
              s.game_id,
              g.name AS game_name,
              g.slug AS game_slug,
              g.image_url AS game_image,
              s.total_minutes,
              s.used_minutes,
              s.remaining_minutes,
              s.status,
              s.ready_at,
              s.started_at,
              s.last_heartbeat,
              s.paused_at,
              s.resumed_at,
              s.completed_at,
              s.expires_at,
              s.auto_countdown,
              s.countdown_interval,
              s.last_countdown_update,
              s.total_pause_time,
              s.pause_count,
              s.monitored_by,
              s.notes,
              s.created_at,
              s.updated_at,
              ROUND(CASE WHEN s.total_minutes > 0 THEN (s.used_minutes / s.total_minutes) * 100 ELSE 0 END, 1) AS progress_percent
            FROM active_game_sessions_v2 s
            INNER JOIN users u ON s.user_id = u.id
            INNER JOIN games g ON s.game_id = g.id");
        echo "session_summary view OK\n";
    } catch (Throwable $e) {
        // non-fatal, just log to output
        echo "session_summary view error: " . $e->getMessage() . "\n";
    }

    echo "\nAll admin tables ensured.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
}
