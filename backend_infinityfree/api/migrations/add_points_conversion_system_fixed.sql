-- Migration: Système de conversion points → temps de jeu (VERSION CORRIGÉE)
-- Date: 2025-10-18

USE `gamezone`;

-- ============================================================================
-- TABLE: point_conversion_config
-- ============================================================================
CREATE TABLE IF NOT EXISTS point_conversion_config (
  id INT PRIMARY KEY DEFAULT 1,
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
  updated_by INT NULL,
  CONSTRAINT fk_conversion_config_admin FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données par défaut
INSERT INTO point_conversion_config (id, points_per_minute, min_conversion_points, max_conversion_per_day, conversion_fee_percent, min_minutes_per_conversion, max_minutes_per_conversion, converted_time_expiry_days, is_active, notes, updated_at) VALUES
(1, 10, 100, 3, 0.00, 10, 300, 30, 1, 'Configuration par défaut: 10 points = 1 minute, max 3 conversions/jour', NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================================================
-- TABLE: point_conversions
-- ============================================================================
CREATE TABLE IF NOT EXISTS point_conversions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  points_spent INT NOT NULL,
  minutes_gained INT NOT NULL,
  game_id INT NULL,
  conversion_rate INT NOT NULL,
  fee_charged DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending', 'active', 'used', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  minutes_used INT NOT NULL DEFAULT 0,
  minutes_remaining INT GENERATED ALWAYS AS (minutes_gained - minutes_used) VIRTUAL,
  purchase_id INT NULL,
  notes TEXT NULL,
  CONSTRAINT fk_conversions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_conversions_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL,
  INDEX idx_user_status (user_id, status),
  INDEX idx_created (created_at),
  INDEX idx_expires (expires_at),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: conversion_usage_log
-- ============================================================================
CREATE TABLE IF NOT EXISTS conversion_usage_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversion_id INT NOT NULL,
  minutes_used INT NOT NULL,
  used_for VARCHAR(200) NULL,
  purchase_id INT NULL,
  session_id INT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_usage_conversion FOREIGN KEY (conversion_id) REFERENCES point_conversions(id) ON DELETE CASCADE,
  INDEX idx_conversion (conversion_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- Fonction: Calculer les minutes disponibles
-- ============================================================================
DELIMITER $$

DROP FUNCTION IF EXISTS get_user_converted_minutes$$

CREATE FUNCTION get_user_converted_minutes(p_user_id INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE total_minutes INT DEFAULT 0;
  
  SELECT COALESCE(SUM(minutes_gained - minutes_used), 0)
  INTO total_minutes
  FROM point_conversions
  WHERE user_id = p_user_id
    AND status = 'active'
    AND expires_at > NOW();
  
  RETURN total_minutes;
END$$

DELIMITER ;

-- ============================================================================
-- Événement: Expirer automatiquement les conversions
-- ============================================================================
DROP EVENT IF EXISTS expire_old_conversions;

CREATE EVENT expire_old_conversions
ON SCHEDULE EVERY 1 HOUR
DO
  UPDATE point_conversions
  SET status = 'expired'
  WHERE status = 'active'
    AND expires_at < NOW();

-- ============================================================================
-- Vue: Résumé des conversions actives par utilisateur
-- ============================================================================
CREATE OR REPLACE VIEW user_converted_minutes_summary AS
SELECT 
  user_id,
  COUNT(*) as total_conversions,
  SUM(points_spent) as total_points_spent,
  SUM(minutes_gained) as total_minutes_gained,
  SUM(minutes_used) as total_minutes_used,
  SUM(minutes_gained - minutes_used) as minutes_available,
  MIN(expires_at) as next_expiry
FROM point_conversions
WHERE status = 'active'
  AND expires_at > NOW()
GROUP BY user_id;

-- Migration terminée
SELECT 'Migration add_points_conversion_system_fixed.sql exécutée avec succès!' as message;
