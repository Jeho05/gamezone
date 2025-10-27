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
    // 1) Ensure 'refund' value allowed in ENUM
    try {
        $col = $pdo->query("SHOW COLUMNS FROM points_transactions LIKE 'type'")->fetch(PDO::FETCH_ASSOC);
        if ($col && isset($col['Type']) && stripos($col['Type'], "enum") !== false && stripos($col['Type'], "'refund'") === false) {
            $pdo->exec("ALTER TABLE points_transactions MODIFY type ENUM('game','tournament','bonus','reservation','friend','adjustment','reward','refund') NULL");
            echo "points_transactions.type extended with 'refund'\n";
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
