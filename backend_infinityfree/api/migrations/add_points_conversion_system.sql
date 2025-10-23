-- Migration: Système de conversion points → temps de jeu
-- Date: 2025-10-18
-- Description: Permet aux joueurs de convertir leurs points en heures de jeu

USE `gamezone`;

-- ============================================================================
-- TABLE: point_conversion_config
-- Configuration du système de conversion (1 seule ligne)
-- ============================================================================
CREATE TABLE IF NOT EXISTS point_conversion_config (
  id INT PRIMARY KEY DEFAULT 1,
  points_per_minute INT NOT NULL DEFAULT 10 COMMENT 'Ex: 10 points = 1 minute',
  min_conversion_points INT NOT NULL DEFAULT 100 COMMENT 'Minimum 100 points pour convertir',
  max_conversion_per_day INT NULL DEFAULT 3 COMMENT 'Max 3 conversions par jour (NULL = illimité)',
  conversion_fee_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Frais en % (ex: 5.00 = 5%)',
  min_minutes_per_conversion INT NOT NULL DEFAULT 10 COMMENT 'Minimum 10 minutes par conversion',
  max_minutes_per_conversion INT NULL DEFAULT 300 COMMENT 'Maximum minutes par conversion (NULL = illimité)',
  converted_time_expiry_days INT NOT NULL DEFAULT 30 COMMENT 'Le temps converti expire dans X jours',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  notes TEXT NULL COMMENT 'Notes pour l\'admin sur les règles',
  updated_at DATETIME NOT NULL,
  updated_by INT NULL COMMENT 'Admin qui a modifié la config',
  CONSTRAINT fk_conversion_config_admin FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT chk_points_per_minute CHECK (points_per_minute > 0),
  CONSTRAINT chk_min_points CHECK (min_conversion_points >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Configuration système conversion points';

-- Données par défaut
INSERT INTO point_conversion_config (id, points_per_minute, min_conversion_points, max_conversion_per_day, conversion_fee_percent, min_minutes_per_conversion, max_minutes_per_conversion, converted_time_expiry_days, is_active, notes, updated_at) VALUES
(1, 10, 100, 3, 0.00, 10, 300, 30, 1, 'Configuration par défaut: 10 points = 1 minute, max 3 conversions/jour', NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================================================
-- TABLE: point_conversions
-- Historique des conversions effectuées par les joueurs
-- ============================================================================
CREATE TABLE IF NOT EXISTS point_conversions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  points_spent INT NOT NULL COMMENT 'Points dépensés',
  minutes_gained INT NOT NULL COMMENT 'Minutes de jeu gagnées',
  game_id INT NULL COMMENT 'Jeu choisi (NULL = tous les jeux)',
  conversion_rate INT NOT NULL COMMENT 'Rate au moment: X points = 1 minute',
  fee_charged DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Frais appliqués',
  status ENUM('pending', 'active', 'used', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL COMMENT 'Date d\'expiration du temps converti',
  used_at DATETIME NULL COMMENT 'Date d\'utilisation',
  minutes_used INT NOT NULL DEFAULT 0 COMMENT 'Minutes déjà utilisées',
  minutes_remaining INT GENERATED ALWAYS AS (minutes_gained - minutes_used) VIRTUAL,
  purchase_id INT NULL COMMENT 'Achat créé avec ce temps converti',
  notes TEXT NULL,
  
  CONSTRAINT fk_conversions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_conversions_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL,
  CONSTRAINT fk_conversions_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL,
  INDEX idx_user_status (user_id, status),
  INDEX idx_created (created_at),
  INDEX idx_expires (expires_at),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historique conversions points → temps';

-- ============================================================================
-- TABLE: conversion_usage_log
-- Log détaillé de l'utilisation du temps converti
-- ============================================================================
CREATE TABLE IF NOT EXISTS conversion_usage_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversion_id INT NOT NULL,
  minutes_used INT NOT NULL,
  used_for VARCHAR(200) NULL COMMENT 'Description: achat FIFA, session GTA, etc.',
  purchase_id INT NULL,
  session_id INT NULL,
  created_at DATETIME NOT NULL,
  
  CONSTRAINT fk_usage_conversion FOREIGN KEY (conversion_id) REFERENCES point_conversions(id) ON DELETE CASCADE,
  CONSTRAINT fk_usage_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL,
  CONSTRAINT fk_usage_session FOREIGN KEY (session_id) REFERENCES active_game_sessions_v2(id) ON DELETE SET NULL,
  INDEX idx_conversion (conversion_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Log utilisation temps converti';

-- ============================================================================
-- Fonction: Calculer les minutes disponibles d'un utilisateur via conversions
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
-- Procédure: Convertir des points en minutes
-- ============================================================================
DELIMITER $$

DROP PROCEDURE IF EXISTS convert_points_to_minutes$$

CREATE PROCEDURE convert_points_to_minutes(
  IN p_user_id INT,
  IN p_points_to_spend INT,
  IN p_game_id INT,
  OUT p_conversion_id INT,
  OUT p_minutes_gained INT,
  OUT p_error VARCHAR(500)
)
BEGIN
  DECLARE v_user_points INT;
  DECLARE v_config_active TINYINT;
  DECLARE v_points_per_minute INT;
  DECLARE v_min_points INT;
  DECLARE v_max_per_day INT;
  DECLARE v_conversions_today INT;
  DECLARE v_fee_percent DECIMAL(5,2);
  DECLARE v_min_minutes INT;
  DECLARE v_max_minutes INT;
  DECLARE v_expiry_days INT;
  DECLARE v_fee_charged DECIMAL(10,2);
  DECLARE v_minutes INT;
  DECLARE v_expires_at DATETIME;
  
  SET p_conversion_id = NULL;
  SET p_minutes_gained = 0;
  SET p_error = NULL;
  
  -- Vérifier que l'utilisateur existe
  SELECT points INTO v_user_points
  FROM users
  WHERE id = p_user_id;
  
  IF v_user_points IS NULL THEN
    SET p_error = 'Utilisateur introuvable';
    ROLLBACK;
    LEAVE;
  END IF;
  
  -- Charger la configuration
  SELECT is_active, points_per_minute, min_conversion_points, max_conversion_per_day,
         conversion_fee_percent, min_minutes_per_conversion, max_minutes_per_conversion,
         converted_time_expiry_days
  INTO v_config_active, v_points_per_minute, v_min_points, v_max_per_day,
       v_fee_percent, v_min_minutes, v_max_minutes, v_expiry_days
  FROM point_conversion_config
  WHERE id = 1;
  
  -- Vérifier que le système est actif
  IF v_config_active != 1 THEN
    SET p_error = 'Le système de conversion est actuellement désactivé';
    ROLLBACK;
    LEAVE;
  END IF;
  
  -- Vérifier minimum de points
  IF p_points_to_spend < v_min_points THEN
    SET p_error = CONCAT('Minimum ', v_min_points, ' points requis');
    ROLLBACK;
    LEAVE;
  END IF;
  
  -- Vérifier que l'utilisateur a assez de points
  IF v_user_points < p_points_to_spend THEN
    SET p_error = CONCAT('Points insuffisants. Disponible: ', v_user_points, ', Requis: ', p_points_to_spend);
    ROLLBACK;
    LEAVE;
  END IF;
  
  -- Vérifier limite quotidienne
  IF v_max_per_day IS NOT NULL THEN
    SELECT COUNT(*)
    INTO v_conversions_today
    FROM point_conversions
    WHERE user_id = p_user_id
      AND DATE(created_at) = CURDATE();
    
    IF v_conversions_today >= v_max_per_day THEN
      SET p_error = CONCAT('Limite quotidienne atteinte (', v_max_per_day, ' conversions/jour)');
      ROLLBACK;
      LEAVE;
    END IF;
  END IF;
  
  -- Calculer les minutes (avant frais)
  SET v_minutes = FLOOR(p_points_to_spend / v_points_per_minute);
  
  -- Vérifier minimum de minutes
  IF v_minutes < v_min_minutes THEN
    SET p_error = CONCAT('Minimum ', v_min_minutes, ' minutes requis. Vous obtiendriez ', v_minutes, ' minutes.');
    ROLLBACK;
    LEAVE;
  END IF;
  
  -- Vérifier maximum de minutes
  IF v_max_minutes IS NOT NULL AND v_minutes > v_max_minutes THEN
    SET p_error = CONCAT('Maximum ', v_max_minutes, ' minutes par conversion');
    ROLLBACK;
    LEAVE;
  END IF;
  
  -- Calculer les frais
  SET v_fee_charged = (p_points_to_spend * v_fee_percent / 100);
  
  -- Calculer expiration
  SET v_expires_at = DATE_ADD(NOW(), INTERVAL v_expiry_days DAY);
  
  -- Débiter les points
  UPDATE users
  SET points = points - p_points_to_spend,
      updated_at = NOW()
  WHERE id = p_user_id;
  
  -- Créer la conversion
  INSERT INTO point_conversions (
    user_id, points_spent, minutes_gained, game_id,
    conversion_rate, fee_charged, status,
    created_at, expires_at
  ) VALUES (
    p_user_id, p_points_to_spend, v_minutes, p_game_id,
    v_points_per_minute, v_fee_charged, 'active',
    NOW(), v_expires_at
  );
  
  SET p_conversion_id = LAST_INSERT_ID();
  SET p_minutes_gained = v_minutes;
  
  -- Logger la transaction de points
  INSERT INTO points_transactions (
    user_id, change_amount, reason, type, created_at
  ) VALUES (
    p_user_id, -p_points_to_spend, 
    CONCAT('Conversion en ', v_minutes, ' minutes de jeu'),
    'conversion', NOW()
  );
  
  -- Mettre à jour les stats utilisateur
  INSERT INTO user_stats (user_id, total_points_spent, updated_at)
  VALUES (p_user_id, p_points_to_spend, NOW())
  ON DUPLICATE KEY UPDATE
    total_points_spent = total_points_spent + p_points_to_spend,
    updated_at = NOW();
  
  COMMIT;
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

-- ============================================================================
-- Indexes additionnels pour performance
-- ============================================================================
ALTER TABLE point_conversions ADD INDEX idx_user_active (user_id, status, expires_at);

-- ============================================================================
-- Permissions
-- ============================================================================
-- Les joueurs peuvent lire leurs propres conversions
-- Les admins peuvent tout voir et modifier la config

-- Migration terminée avec succès
SELECT 'Migration add_points_conversion_system.sql exécutée avec succès!' as message;
