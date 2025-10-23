-- ============================================================================
-- Migration: Système de facturation sécurisé avec codes QR
-- Date: 2025-01-17
-- Description: Système complet de facturation pour achats de temps de jeu
-- ============================================================================

USE `gamezone`;

-- Table des factures avec codes de validation
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
  CONSTRAINT fk_invoices_activator FOREIGN KEY (activated_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user_status (user_id, status),
  INDEX idx_validation_code (validation_code),
  INDEX idx_invoice_number (invoice_number),
  INDEX idx_status (status),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Historique des scans
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
  CONSTRAINT fk_scans_admin FOREIGN KEY (scanned_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_invoice (invoice_id),
  INDEX idx_code (validation_code),
  INDEX idx_scanned_at (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sessions de jeu avec décompte automatique
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Événements de sessions
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
  CONSTRAINT fk_events_actor FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_session (session_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit log des factures
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
  CONSTRAINT fk_audit_actor FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_invoice (invoice_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Règles de détection de fraude
CREATE TABLE IF NOT EXISTS fraud_detection_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rule_name VARCHAR(100) NOT NULL UNIQUE,
  rule_type ENUM('scan_frequency', 'ip_blacklist', 'device_fingerprint', 'time_pattern', 'geo_anomaly') NOT NULL,
  rule_config JSON NOT NULL,
  action ENUM('flag', 'block', 'notify_admin', 'require_verification') NOT NULL DEFAULT 'flag',
  severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données par défaut: règles de fraude
INSERT INTO fraud_detection_rules (rule_name, rule_type, rule_config, action, severity, is_active, created_at, updated_at) VALUES
('Tentatives multiples scan rapide', 'scan_frequency', JSON_OBJECT('max_attempts', 5, 'time_window_minutes', 5), 'block', 'high', 1, NOW(), NOW()),
('Scan depuis plusieurs IPs', 'ip_blacklist', JSON_OBJECT('max_different_ips', 3, 'time_window_hours', 1), 'flag', 'medium', 1, NOW(), NOW()),
('Délai anormal activation', 'time_pattern', JSON_OBJECT('min_delay_seconds', 2), 'flag', 'medium', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE rule_config = VALUES(rule_config), updated_at = NOW();
