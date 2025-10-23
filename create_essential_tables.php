<?php
// Créer les tables essentielles manquantes
require_once __DIR__ . '/api/config.php';

echo "=== CRÉATION DES TABLES ESSENTIELLES ===\n\n";

try {
    $pdo = get_db();
    
    // 1. Table payment_transactions
    echo "📦 Création de payment_transactions...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payment_transactions (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ payment_transactions créée\n\n";
    
    // 2. Table session_activities
    echo "📦 Création de session_activities...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS session_activities (
          id INT AUTO_INCREMENT PRIMARY KEY,
          session_id INT NOT NULL,
          activity_type ENUM('start', 'pause', 'resume', 'complete', 'expire', 'cancel', 'time_update') NOT NULL,
          minutes_used INT NOT NULL DEFAULT 0,
          description VARCHAR(500) NULL,
          created_by INT NULL,
          created_at DATETIME NOT NULL,
          CONSTRAINT fk_activities_session FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
          INDEX idx_session (session_id),
          INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ session_activities créée\n\n";
    
    // 3. Table invoices
    echo "📦 Création de invoices...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoices (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ invoices créée\n\n";
    
    // 4. Table invoice_scans
    echo "📦 Création de invoice_scans...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoice_scans (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ invoice_scans créée\n\n";
    
    // 5. Table active_game_sessions_v2
    echo "📦 Création de active_game_sessions_v2...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS active_game_sessions_v2 (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ active_game_sessions_v2 créée\n\n";
    
    // 6. Table session_events
    echo "📦 Création de session_events...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS session_events (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ session_events créée\n\n";
    
    // 7. Table invoice_audit_log
    echo "📦 Création de invoice_audit_log...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoice_audit_log (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ invoice_audit_log créée\n\n";
    
    echo "✅ TOUTES LES TABLES ONT ÉTÉ CRÉÉES AVEC SUCCÈS !\n";
    
} catch (Exception $e) {
    echo "\n✗ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
