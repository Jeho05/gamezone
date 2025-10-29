-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 28 oct. 2025 à 01:20
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gamezone`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `activate_invoice` (IN `p_validation_code` VARCHAR(32), IN `p_admin_id` INT, IN `p_ip_address` VARCHAR(45), IN `p_user_agent` TEXT, OUT `p_result` VARCHAR(50), OUT `p_invoice_id` INT, OUT `p_session_id` INT)   BEGIN
  DECLARE v_invoice_status VARCHAR(20);
  DECLARE v_expires_at DATETIME;
  DECLARE v_is_suspicious TINYINT(1);
  DECLARE v_purchase_id INT;
  DECLARE v_user_id INT;
  DECLARE v_game_id INT;
  DECLARE v_duration INT;
  
  SELECT id, status, expires_at, is_suspicious, purchase_id, user_id
  INTO p_invoice_id, v_invoice_status, v_expires_at, v_is_suspicious, v_purchase_id, v_user_id
  FROM invoices
  WHERE validation_code = p_validation_code
  LIMIT 1;
  
  IF p_invoice_id IS NULL THEN
    SET p_result = 'invalid_code';
  ELSEIF v_invoice_status != 'pending' THEN
    SET p_result = CONCAT('already_', v_invoice_status);
  ELSEIF v_expires_at < NOW() THEN
    UPDATE invoices SET status = 'expired', updated_at = NOW() WHERE id = p_invoice_id;
    SET p_result = 'expired';
  ELSEIF v_is_suspicious = 1 THEN
    SET p_result = 'fraud_detected';
  ELSE
    
    UPDATE invoices SET
      status = 'active',
      activated_at = NOW(),
      activated_by = p_admin_id,
      activation_ip = p_ip_address,
      activation_device = p_user_agent,
      updated_at = NOW()
    WHERE id = p_invoice_id;
    
    
    SELECT game_id, duration_minutes INTO v_game_id, v_duration
    FROM purchases WHERE id = v_purchase_id;
    
    
    INSERT INTO active_game_sessions_v2 (
      invoice_id, purchase_id, user_id, game_id,
      total_minutes, used_minutes, status,
      ready_at, expires_at, created_at, updated_at
    ) VALUES (
      p_invoice_id, v_purchase_id, v_user_id, v_game_id,
      v_duration, 0, 'ready',
      NOW(), DATE_ADD(NOW(), INTERVAL v_duration * 2 MINUTE),
      NOW(), NOW()
    );
    
    SET p_session_id = LAST_INSERT_ID();
    
    
    UPDATE purchases 
    SET session_status = 'ready', updated_at = NOW()
    WHERE id = v_purchase_id;
    
    
    INSERT INTO session_events (
      session_id, event_type, event_message,
      triggered_by, created_at
    ) VALUES (
      p_session_id, 'ready', 'Session pr├¬te - facture activ├®e',
      p_admin_id, NOW()
    );
    
    
    INSERT INTO invoice_audit_log (
      invoice_id, action, performed_by, performed_by_type,
      action_details, ip_address, user_agent, created_at
    ) VALUES (
      p_invoice_id, 'activated', p_admin_id, 'admin',
      'Facture activ├®e par scan', p_ip_address, p_user_agent, NOW()
    );
    
    SET p_result = 'success';
  END IF;
  
  
  INSERT INTO invoice_scans (
    invoice_id, validation_code, scan_result, scan_message,
    scanned_by, scanned_at, ip_address, user_agent
  ) VALUES (
    p_invoice_id, p_validation_code, p_result,
    CASE p_result
      WHEN 'success' THEN 'Activation r├®ussie'
      WHEN 'invalid_code' THEN 'Code invalide'
      WHEN 'expired' THEN 'Facture expir├®e'
      WHEN 'fraud_detected' THEN 'Fraude d├®tect├®e'
      ELSE CONCAT('Facture d├®j├á ', v_invoice_status)
    END,
    p_admin_id, NOW(), p_ip_address, p_user_agent
  );
  
  
  IF p_invoice_id IS NOT NULL THEN
    UPDATE invoices SET
      scan_attempts = scan_attempts + 1,
      last_scan_attempt = NOW()
    WHERE id = p_invoice_id;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `cleanup_stuck_transactions` ()   BEGIN
UPDATE purchase_transactions
SET status = 'failed',
failure_reason = 'Transaction timeout - Processus bloqué',
failed_at = NOW()
WHERE status = 'processing'
AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE);
UPDATE purchase_transactions
SET status = 'failed',
failure_reason = 'Transaction abandonné - Timeout',
failed_at = NOW()
WHERE status = 'pending'
AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `countdown_active_sessions` ()   BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE v_session_id INT;
  DECLARE v_purchase_id INT;
  DECLARE v_invoice_id INT;
  DECLARE v_last_update DATETIME;
  DECLARE v_used_minutes INT;
  DECLARE v_total_minutes INT;
  DECLARE v_minutes_to_add INT;
  
  DECLARE session_cursor CURSOR FOR
    SELECT id, purchase_id, invoice_id, last_countdown_update, used_minutes, total_minutes
    FROM active_game_sessions_v2
    WHERE status = 'active' AND auto_countdown = 1;
  
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  
  OPEN session_cursor;
  
  read_loop: LOOP
    FETCH session_cursor INTO v_session_id, v_purchase_id, v_invoice_id, v_last_update, v_used_minutes, v_total_minutes;
    
    IF done THEN
      LEAVE read_loop;
    END IF;
    
    
    IF v_last_update IS NOT NULL THEN
      SET v_minutes_to_add = TIMESTAMPDIFF(MINUTE, v_last_update, NOW());
    ELSE
      SET v_minutes_to_add = TIMESTAMPDIFF(MINUTE, 
        (SELECT started_at FROM active_game_sessions_v2 WHERE id = v_session_id),
        NOW()
      );
    END IF;
    
    IF v_minutes_to_add > 0 THEN
      SET v_used_minutes = v_used_minutes + v_minutes_to_add;
      
      
      IF v_used_minutes >= v_total_minutes THEN
        UPDATE active_game_sessions_v2 SET
          status = 'completed',
          used_minutes = v_total_minutes,
          completed_at = NOW(),
          last_countdown_update = NOW(),
          updated_at = NOW()
        WHERE id = v_session_id;
        
        
        UPDATE invoices SET
          status = 'used',
          used_at = NOW(),
          updated_at = NOW()
        WHERE id = v_invoice_id;
        
        
        
        INSERT INTO session_events (
          session_id, event_type, event_message,
          minutes_after, triggered_by_system, created_at
        ) VALUES (
          v_session_id, 'complete', 'Session termin├®e - temps ├®coul├®',
          0, 1, NOW()
        );
        
      ELSE
        
        UPDATE active_game_sessions_v2 SET
          used_minutes = v_used_minutes,
          last_countdown_update = NOW(),
          last_heartbeat = NOW(),
          updated_at = NOW()
        WHERE id = v_session_id;
        
        INSERT INTO session_events (
          session_id, event_type, event_message,
          minutes_delta, minutes_after, triggered_by_system, created_at
        ) VALUES (
          v_session_id, 'countdown_update', CONCAT('D├®compte: +', v_minutes_to_add, ' min'),
          v_minutes_to_add,
          v_total_minutes - v_used_minutes,
          1, NOW()
        );
        
        
        IF (v_total_minutes - v_used_minutes) <= GREATEST(FLOOR(v_total_minutes * 0.1), 5) THEN
          INSERT INTO session_events (
            session_id, event_type, event_message,
            minutes_after, triggered_by_system, created_at
          ) VALUES (
            v_session_id, 'warning_low_time',
            CONCAT('Attention: ', v_total_minutes - v_used_minutes, ' min restantes'),
            v_total_minutes - v_used_minutes, 1, NOW()
          );
        END IF;
      END IF;
    END IF;
  END LOOP;
  
  CLOSE session_cursor;
  
  
  UPDATE active_game_sessions_v2 SET status = 'expired', updated_at = NOW()
  WHERE status IN ('ready', 'active', 'paused') AND expires_at < NOW();
  
  
  
  
  UPDATE invoices SET status = 'expired', updated_at = NOW()
  WHERE status = 'pending' AND expires_at < NOW();
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `refund_transaction` (IN `p_transaction_id` INT, IN `p_refund_reason` TEXT, IN `p_admin_id` INT, OUT `p_result` VARCHAR(50))   BEGIN
DECLARE v_user_id INT;
DECLARE v_points_amount INT;
DECLARE v_money_amount DECIMAL(10,2);
DECLARE v_purchase_id INT;
DECLARE v_points_tx_id INT;
DECLARE v_status VARCHAR(20);
DECLARE v_current_balance INT;
SELECT user_id, status, points_amount, money_amount, purchase_id, points_tx_id
INTO v_user_id, v_status, v_points_amount, v_money_amount, v_purchase_id, v_points_tx_id
FROM purchase_transactions
WHERE id = p_transaction_id
LIMIT 1;
IF v_user_id IS NULL THEN
SET p_result = 'transaction_not_found';
ELSEIF v_status = 'refunded' THEN
SET p_result = 'already_refunded';
ELSEIF v_status != 'completed' THEN
SET p_result = 'cannot_refund_uncompleted';
ELSE
IF v_points_amount > 0 THEN
UPDATE users
SET points = points + v_points_amount, updated_at = NOW()
WHERE id = v_user_id;
SELECT points INTO v_current_balance FROM users WHERE id = v_user_id;
INSERT INTO points_transactions (
user_id, type, change_amount, balance_after,
reason, reference_type, reference_id, created_at
) VALUES (
v_user_id, 'refund', v_points_amount, v_current_balance,
CONCAT('Remboursement: ', p_refund_reason),
'transaction', p_transaction_id, NOW()
);
END IF;
IF v_purchase_id IS NOT NULL THEN
UPDATE purchases
SET session_status = 'cancelled', updated_at = NOW()
WHERE id = v_purchase_id;
END IF;
UPDATE purchase_transactions
SET status = 'refunded',
refund_reason = p_refund_reason,
refunded_by = p_admin_id,
refunded_at = NOW()
WHERE id = p_transaction_id;
SET p_result = 'success';
END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `start_game_session` (IN `p_session_id` INT, IN `p_admin_id` INT, OUT `p_result` VARCHAR(50))   BEGIN
        DECLARE v_status VARCHAR(50);
        
        SELECT status INTO v_status FROM active_game_sessions_v2 WHERE id = p_session_id;
        
        IF v_status IS NULL THEN
            SET p_result = 'session_not_found';
        ELSEIF v_status NOT IN ('ready', 'paused') THEN
            SET p_result = 'invalid_status';
        ELSE
            UPDATE active_game_sessions_v2
            SET status = 'active',
                started_at = NOW(),
                last_heartbeat = NOW(),
                updated_at = NOW()
            WHERE id = p_session_id;
            
            INSERT INTO session_events (session_id, event_type, triggered_by, created_at)
            VALUES (p_session_id, 'start', p_admin_id, NOW());
            
            SET p_result = 'success';
        END IF;
    END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `start_session` (IN `p_session_id` INT, IN `p_admin_id` INT, OUT `p_result` VARCHAR(50))   BEGIN
  DECLARE v_status VARCHAR(20);
  DECLARE v_purchase_id INT;
  
  SELECT status, purchase_id 
  INTO v_status, v_purchase_id 
  FROM active_game_sessions_v2 
  WHERE id = p_session_id;
  
  IF v_status IS NULL THEN
    SET p_result = 'session_not_found';
  ELSEIF v_status != 'ready' THEN
    SET p_result = 'invalid_status';
  ELSE
    
    UPDATE active_game_sessions_v2 SET
      status = 'active',
      started_at = NOW(),
      last_heartbeat = NOW(),
      last_countdown_update = NOW(),
      updated_at = NOW()
    WHERE id = p_session_id;
    
    
    UPDATE purchases 
    SET session_status = 'active', updated_at = NOW()
    WHERE id = v_purchase_id;
    
    
    INSERT INTO session_events (
      session_id, event_type, event_message,
      triggered_by, created_at
    ) VALUES (
      p_session_id, 'start', 'Session d├®marr├®e',
      p_admin_id, NOW()
    );
    
    SET p_result = 'success';
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sync_purchase_session_status` ()   BEGIN
  
  UPDATE purchases p
  INNER JOIN active_game_sessions_v2 s ON p.id = s.purchase_id
  SET p.session_status = s.status,
      p.updated_at = NOW()
  WHERE p.session_status != s.status;
  
  
  UPDATE purchases p
  LEFT JOIN active_game_sessions_v2 s ON p.id = s.purchase_id
  SET p.session_status = 'pending',
      p.updated_at = NOW()
  WHERE p.payment_status = 'completed' 
    AND s.id IS NULL
    AND p.session_status NOT IN ('pending', 'cancelled');
  
  
  UPDATE purchases 
  SET session_status = 'cancelled',
      updated_at = NOW()
  WHERE payment_status IN ('failed', 'cancelled', 'refunded')
    AND session_status != 'cancelled';

  SELECT 
    'Synchronisation termin├®e' as message,
    (SELECT COUNT(*) FROM purchases p INNER JOIN active_game_sessions_v2 s ON p.id = s.purchase_id WHERE p.session_status = s.status) as synced,
    (SELECT COUNT(*) FROM purchases p INNER JOIN active_game_sessions_v2 s ON p.id = s.purchase_id WHERE p.session_status != s.status) as remaining_mismatches;
END$$

--
-- Fonctions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `get_user_converted_minutes` (`p_user_id` INT) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
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

-- --------------------------------------------------------

--
-- Structure de la table `active_game_sessions_v2`
--

CREATE TABLE `active_game_sessions_v2` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `total_minutes` int(11) NOT NULL,
  `used_minutes` int(11) NOT NULL DEFAULT 0,
  `remaining_minutes` int(11) GENERATED ALWAYS AS (`total_minutes` - `used_minutes`) VIRTUAL,
  `status` enum('ready','active','paused','completed','expired','terminated') NOT NULL DEFAULT 'ready',
  `ready_at` datetime NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `last_heartbeat` datetime DEFAULT NULL,
  `paused_at` datetime DEFAULT NULL,
  `resumed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `auto_countdown` tinyint(1) NOT NULL DEFAULT 1,
  `countdown_interval` int(11) NOT NULL DEFAULT 60,
  `last_countdown_update` datetime DEFAULT NULL,
  `total_pause_time` int(11) NOT NULL DEFAULT 0,
  `pause_count` int(11) NOT NULL DEFAULT 0,
  `monitored_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `active_game_sessions_v2`
--

INSERT INTO `active_game_sessions_v2` (`id`, `invoice_id`, `purchase_id`, `user_id`, `game_id`, `total_minutes`, `used_minutes`, `status`, `ready_at`, `started_at`, `last_heartbeat`, `paused_at`, `resumed_at`, `completed_at`, `expires_at`, `auto_countdown`, `countdown_interval`, `last_countdown_update`, `total_pause_time`, `pause_count`, `monitored_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 4, 8, 1, 10, 0, 'completed', '2025-10-17 15:14:03', '2025-10-17 15:17:12', '2025-10-17 15:17:12', NULL, NULL, '2025-10-17 15:26:04', '2025-10-17 15:24:03', 1, 60, '2025-10-17 15:17:12', 0, 0, 9, NULL, '2025-10-17 15:14:03', '2025-10-17 15:17:12'),
(2, 3, 5, 8, 1, 10, 0, 'completed', '2025-10-17 15:18:07', '2025-10-17 15:18:11', '2025-10-17 15:18:11', NULL, NULL, '2025-10-17 15:26:04', '2025-10-17 15:28:07', 1, 60, '2025-10-17 15:18:11', 0, 0, 9, NULL, '2025-10-17 15:18:07', '2025-10-17 15:18:11'),
(4, 4, 6, 1, 3, 5, 1, 'terminated', '2025-10-17 15:26:54', '2025-10-17 15:26:54', '2025-10-17 15:26:54', NULL, NULL, '2025-10-17 16:01:18', '2025-10-17 15:31:54', 1, 60, '2025-10-17 15:52:11', 0, 0, 1, NULL, '2025-10-17 15:26:54', '2025-10-17 16:01:18'),
(5, 5, 7, 8, 3, 60, 60, 'completed', '2025-10-17 16:21:39', '2025-10-17 16:21:44', '2025-10-17 16:21:44', NULL, NULL, '2025-10-17 19:07:46', '2025-10-17 17:21:39', 1, 60, '2025-10-17 16:21:44', 0, 0, 9, NULL, '2025-10-17 16:21:39', '2025-10-17 19:07:46'),
(6, 7, 9, 8, 4, 60, 60, 'completed', '2025-10-17 17:49:01', '2025-10-17 17:49:05', '2025-10-17 17:49:05', NULL, NULL, '2025-10-17 19:07:46', '2025-10-17 18:49:01', 1, 60, '2025-10-17 17:49:05', 0, 0, 9, NULL, '2025-10-17 17:49:01', '2025-10-17 19:07:46'),
(7, 8, 10, 8, 5, 1, 1, 'completed', '2025-10-17 18:11:28', '2025-10-17 18:11:33', '2025-10-17 18:11:33', NULL, NULL, '2025-10-17 19:07:46', '2025-10-17 18:12:28', 1, 60, '2025-10-17 18:11:33', 0, 0, 9, NULL, '2025-10-17 18:11:28', '2025-10-17 19:07:46'),
(8, 9, 11, 8, 5, 1, 1, 'completed', '2025-10-17 18:39:15', '2025-10-17 18:39:21', '2025-10-17 18:39:21', NULL, NULL, '2025-10-17 19:07:46', '2025-10-17 18:40:15', 1, 60, '2025-10-17 18:39:21', 0, 0, 9, NULL, '2025-10-17 18:39:15', '2025-10-17 19:07:46'),
(9, 11, 13, 8, 1, 60, 60, 'completed', '2025-10-17 22:13:50', '2025-10-17 22:13:55', '2025-10-17 22:13:55', NULL, NULL, '2025-10-18 14:00:49', '2025-10-17 23:13:50', 1, 60, '2025-10-18 14:00:49', 0, 0, 9, NULL, '2025-10-17 22:13:50', '2025-10-18 14:00:49'),
(10, 12, 14, 8, 1, 1, 1, 'completed', '2025-10-18 12:32:31', '2025-10-18 12:32:40', '2025-10-18 12:32:40', NULL, NULL, '2025-10-18 14:00:49', '2025-10-18 12:33:31', 1, 60, '2025-10-18 14:00:49', 0, 0, 9, NULL, '2025-10-18 12:32:31', '2025-10-18 14:00:49'),
(11, 10, 12, 8, 5, 1, 1, 'completed', '2025-10-18 13:56:31', '2025-10-18 13:56:31', '2025-10-18 13:56:31', NULL, NULL, '2025-10-18 14:00:49', '2025-10-18 13:58:31', 1, 60, '2025-10-18 14:00:49', 0, 0, NULL, NULL, '2025-10-18 13:56:31', '2025-10-18 14:00:49'),
(12, 6, 8, 8, 3, 60, 10, 'terminated', '2025-10-18 13:57:39', '2025-10-18 13:57:39', '2025-10-18 14:08:29', NULL, NULL, '2025-10-18 15:02:28', '2025-10-18 15:57:39', 1, 60, '2025-10-18 14:08:29', 0, 0, NULL, NULL, '2025-10-18 13:57:39', '2025-10-18 15:02:28'),
(13, 1, 3, 1, 1, 10, 5, 'terminated', '2025-10-18 14:02:26', '2025-10-18 14:02:36', '2025-10-18 14:08:29', NULL, NULL, '2025-10-18 14:52:30', '2025-10-18 14:22:26', 1, 60, '2025-10-18 14:08:29', 0, 0, NULL, NULL, '2025-10-18 14:02:26', '2025-10-18 14:52:30'),
(14, 18, 15, 8, 5, 1, 0, 'terminated', '2025-10-18 14:26:40', '2025-10-18 14:26:40', '2025-10-18 14:26:40', NULL, NULL, '2025-10-18 14:52:30', '2025-10-18 14:28:40', 1, 60, '2025-10-18 14:26:40', 0, 0, NULL, NULL, '2025-10-18 14:26:40', '2025-10-18 14:52:30'),
(15, 19, 17, 8, 5, 1, 0, 'terminated', '2025-10-18 14:35:39', '2025-10-18 14:35:39', '2025-10-18 14:35:39', NULL, NULL, '2025-10-18 14:52:29', '2025-10-18 14:37:39', 1, 60, '2025-10-18 14:35:39', 0, 0, NULL, NULL, '2025-10-18 14:35:39', '2025-10-18 14:52:29'),
(16, 21, 18, 8, 5, 1, 0, 'terminated', '2025-10-18 15:06:21', '2025-10-18 15:06:21', '2025-10-18 15:06:21', NULL, NULL, '2025-10-18 15:07:38', '2025-10-18 15:08:21', 1, 60, '2025-10-18 15:06:21', 0, 0, NULL, NULL, '2025-10-18 15:06:21', '2025-10-18 15:07:38'),
(17, 22, 19, 8, 4, 60, 0, 'terminated', '2025-10-18 15:49:02', '2025-10-18 15:49:03', '2025-10-18 15:49:03', NULL, NULL, '2025-10-18 16:54:25', '2025-10-18 17:49:02', 1, 60, '2025-10-18 15:49:03', 0, 0, NULL, NULL, '2025-10-18 15:49:02', '2025-10-18 16:54:25'),
(18, 23, 20, 8, 9, 1, 0, 'terminated', '2025-10-18 16:31:49', '2025-10-18 16:31:49', '2025-10-18 16:31:49', NULL, NULL, '2025-10-18 16:33:02', '2025-10-18 16:33:49', 1, 60, '2025-10-18 16:31:49', 0, 0, NULL, NULL, '2025-10-18 16:31:49', '2025-10-18 16:33:02'),
(19, 20, 16, 8, 5, 1, 0, 'terminated', '2025-10-18 16:54:58', '2025-10-18 16:54:58', '2025-10-18 16:54:58', NULL, NULL, '2025-10-18 16:56:21', '2025-10-18 16:56:58', 1, 60, '2025-10-18 16:54:58', 0, 0, NULL, NULL, '2025-10-18 16:54:58', '2025-10-18 16:56:21'),
(20, 25, 27, 8, 9, 1, 0, 'terminated', '2025-10-21 13:56:22', '2025-10-21 13:56:22', '2025-10-21 13:56:22', NULL, NULL, '2025-10-21 13:57:35', '2025-10-21 13:58:22', 1, 60, '2025-10-21 13:56:22', 0, 0, NULL, NULL, '2025-10-21 13:56:22', '2025-10-21 13:57:35'),
(21, 24, 40, 8, 9, 30, 0, 'terminated', '2025-10-21 13:59:52', '2025-10-21 13:59:52', '2025-10-21 13:59:52', NULL, NULL, '2025-10-21 14:34:57', '2025-10-21 14:59:52', 1, 60, '2025-10-21 13:59:52', 0, 0, NULL, NULL, '2025-10-21 13:59:52', '2025-10-21 14:34:57'),
(22, 37, 46, 25, 9, 30, 0, 'terminated', '2025-10-22 18:55:19', '2025-10-22 18:55:19', '2025-10-22 18:55:19', NULL, NULL, '2025-10-23 00:12:47', '2025-10-22 19:55:19', 1, 60, '2025-10-22 18:55:19', 0, 0, NULL, NULL, '2025-10-22 18:55:19', '2025-10-23 00:12:47'),
(23, 38, 47, 8, 9, 1, 0, 'terminated', '2025-10-22 19:09:00', '2025-10-22 19:09:00', '2025-10-22 19:09:00', NULL, NULL, '2025-10-22 19:18:55', '2025-10-22 19:11:00', 1, 60, '2025-10-22 19:09:00', 0, 0, NULL, NULL, '2025-10-22 19:09:00', '2025-10-22 19:18:55'),
(24, 26, 43, 8, 9, 30, 0, 'terminated', '2025-10-22 19:17:46', '2025-10-22 19:17:46', '2025-10-22 19:17:46', NULL, NULL, '2025-10-23 00:12:42', '2025-10-22 20:17:46', 1, 60, '2025-10-22 19:17:46', 0, 0, NULL, NULL, '2025-10-22 19:17:46', '2025-10-23 00:12:42'),
(25, 39, 48, 8, 1, 30, 0, 'terminated', '2025-10-22 19:22:00', '2025-10-22 19:22:00', '2025-10-22 19:22:00', NULL, NULL, '2025-10-23 00:12:40', '2025-10-22 20:22:00', 1, 60, '2025-10-22 19:22:00', 0, 0, NULL, NULL, '2025-10-22 19:22:00', '2025-10-23 00:12:40');

--
-- Déclencheurs `active_game_sessions_v2`
--
DELIMITER $$
CREATE TRIGGER `sync_session_to_purchase` AFTER UPDATE ON `active_game_sessions_v2` FOR EACH ROW BEGIN
  
  IF NEW.status != OLD.status THEN
    UPDATE purchases 
    SET session_status = NEW.status, updated_at = NOW()
    WHERE id = NEW.purchase_id;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `active_sessions`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `active_sessions` (
`id` int(11)
,`user_id` int(11)
,`username` varchar(100)
,`avatar_url` varchar(500)
,`level` varchar(100)
,`points` int(11)
,`game_id` int(11)
,`game_name` varchar(200)
,`game_slug` varchar(200)
,`game_image` varchar(500)
,`total_minutes` int(11)
,`used_minutes` int(11)
,`remaining_minutes` bigint(12)
,`progress_percent` decimal(16,2)
,`status` varchar(50)
,`started_at` datetime
,`paused_at` datetime
,`expires_at` datetime
,`purchase_id` int(11)
,`price` decimal(10,2)
,`payment_status` varchar(50)
);

-- --------------------------------------------------------

--
-- Structure de la table `badges`
--

CREATE TABLE `badges` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `category` enum('points','activity','social','achievement','special') NOT NULL DEFAULT 'achievement',
  `requirement_type` enum('points_total','points_earned','days_active','games_played','events_attended','friends_referred','login_streak','special') NOT NULL,
  `requirement_value` int(11) NOT NULL,
  `rarity` enum('common','rare','epic','legendary') NOT NULL DEFAULT 'common',
  `points_reward` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `badges`
--

INSERT INTO `badges` (`id`, `name`, `description`, `icon`, `category`, `requirement_type`, `requirement_value`, `rarity`, `points_reward`, `created_at`, `updated_at`) VALUES
(1, 'Premi├¿re Connexion', 'Se connecter pour la premi├¿re fois', '­ƒÄ«', 'activity', 'special', 1, 'common', 10, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(2, 'D├®butant', 'Atteindre 100 points', '­ƒîƒ', 'points', 'points_total', 100, 'common', 25, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(3, 'Collectionneur', 'Atteindre 500 points', '­ƒÆÄ', 'points', 'points_total', 500, 'rare', 50, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(4, 'Ma├«tre des Points', 'Atteindre 1000 points', '­ƒææ', 'points', 'points_total', 1000, 'epic', 100, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(5, 'L├®gende', 'Atteindre 5000 points', '­ƒÅå', 'points', 'points_total', 5000, 'legendary', 500, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(6, 'Joueur Actif', 'Jouer 10 parties', '­ƒÄ»', 'activity', 'games_played', 10, 'common', 50, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(7, 'Accro du Gaming', 'Jouer 50 parties', '­ƒöÑ', 'activity', 'games_played', 50, 'rare', 150, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(8, 'Participant Assidu', 'Assister ├á 5 ├®v├®nements', '­ƒÄ¬', 'activity', 'events_attended', 5, 'rare', 100, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(9, 'S├®rie de 7', 'Se connecter 7 jours d\'affil├®e', '­ƒôà', 'activity', 'login_streak', 7, 'epic', 200, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(10, 'S├®rie de 30', 'Se connecter 30 jours d\'affil├®e', '­ƒöÑ', 'activity', 'login_streak', 30, 'legendary', 1000, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(11, 'Social', 'Parrainer 3 amis', '­ƒæÑ', 'social', 'friends_referred', 3, 'rare', 300, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(12, 'Recruteur', 'Parrainer 10 amis', '­ƒîÉ', 'social', 'friends_referred', 10, 'legendary', 1500, '2025-10-14 21:49:34', '2025-10-14 21:49:34');

-- --------------------------------------------------------

--
-- Structure de la table `bonus_multipliers`
--

CREATE TABLE `bonus_multipliers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `multiplier` decimal(3,2) NOT NULL DEFAULT 1.00,
  `reason` varchar(255) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `bonus_multipliers`
--

INSERT INTO `bonus_multipliers` (`id`, `user_id`, `multiplier`, `reason`, `expires_at`, `created_at`) VALUES
(1, 8, 2.00, 'ice', '2025-10-15 22:32:12', '2025-10-14 22:32:12'),
(2, 8, 2.00, 'plaisir', '2025-10-18 21:32:46', '2025-10-18 20:32:46'),
(3, 31, 2.00, '55', '2025-10-22 14:54:22', '2025-10-21 14:54:22');

-- --------------------------------------------------------

--
-- Structure de la table `content`
--

CREATE TABLE `content` (
  `id` int(11) NOT NULL,
  `type` enum('news','event','stream','gallery') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `external_link` varchar(500) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `event_location` varchar(255) DEFAULT NULL,
  `stream_url` varchar(500) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `is_pinned` tinyint(1) DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `views_count` int(11) DEFAULT 0,
  `shares_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `content`
--

INSERT INTO `content` (`id`, `type`, `title`, `description`, `content`, `image_url`, `video_url`, `external_link`, `event_date`, `event_location`, `stream_url`, `is_published`, `is_pinned`, `published_at`, `views_count`, `shares_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'news', 'fbdfbd', 'ethet', 'ethgrtg', 'http://localhost/projet%20ismo/uploads/games/game_68f3b0ac5557c9.04825248.jpg', '', '', '0000-00-00 00:00:00', '', '', 1, 1, '2025-10-18 19:11:52', 0, 0, 9, '2025-10-18 19:11:52', '2025-10-18 19:11:52'),
(2, 'event', 'lijh,gn', 'pùmljh,n', 'ùplklj,', 'http://localhost/projet%20ismo/uploads/games/game_68f3ca9ad93680.24022614.jpg', '', '', '2025-10-31 02:50:00', '', '', 1, 0, '2025-10-18 19:13:17', 8, 0, 9, '2025-10-18 19:13:17', '2025-10-18 19:13:17'),
(3, 'gallery', 'xc c c', '', '', '', '', '', '0000-00-00 00:00:00', '', '', 1, 0, '2025-10-18 20:18:18', 0, 0, 9, '2025-10-18 20:18:18', '2025-10-18 20:18:18');

-- --------------------------------------------------------

--
-- Structure de la table `content_comments`
--

CREATE TABLE `content_comments` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `content_comments`
--

INSERT INTO `content_comments` (`id`, `content_id`, `user_id`, `comment`, `parent_id`, `is_approved`, `created_at`, `updated_at`) VALUES
(1, 2, 8, 'ok', NULL, 1, '2025-10-18 20:00:30', '2025-10-18 20:00:30'),
(2, 2, 8, 'cv ?', NULL, 1, '2025-10-18 20:00:38', '2025-10-18 20:00:38'),
(3, 2, 8, 'hhh', 2, 1, '2025-10-18 20:15:41', '2025-10-18 20:15:41');

-- --------------------------------------------------------

--
-- Structure de la table `content_items`
--

CREATE TABLE `content_items` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `content_type` enum('article','news','tutorial','update','announcement') NOT NULL DEFAULT 'article',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `author_id` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `content_likes`
--

CREATE TABLE `content_likes` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `content_reactions`
--

CREATE TABLE `content_reactions` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction_type` enum('like','love','wow','haha','sad','angry') NOT NULL DEFAULT 'like',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `content_shares`
--

CREATE TABLE `content_shares` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `platform` enum('facebook','twitter','whatsapp','telegram','copy_link') NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conversion_usage_log`
--

CREATE TABLE `conversion_usage_log` (
  `id` int(11) NOT NULL,
  `conversion_id` int(11) NOT NULL,
  `minutes_used` int(11) NOT NULL,
  `used_for` varchar(200) DEFAULT NULL COMMENT 'Description: achat FIFA, session GTA, etc.',
  `purchase_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Log utilisation temps converti';

-- --------------------------------------------------------

--
-- Structure de la table `daily_bonuses`
--

CREATE TABLE `daily_bonuses` (
  `user_id` int(11) NOT NULL,
  `last_claim_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `daily_bonuses`
--

INSERT INTO `daily_bonuses` (`user_id`, `last_claim_date`) VALUES
(8, '2025-10-22'),
(31, '2025-10-19');

-- --------------------------------------------------------

--
-- Structure de la table `deleted_users`
--

CREATE TABLE `deleted_users` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `deletion_reason` text NOT NULL,
  `deleted_by` int(11) NOT NULL,
  `deleted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `deleted_users`
--

INSERT INTO `deleted_users` (`id`, `user_id`, `username`, `email`, `deletion_reason`, `deleted_by`, `deleted_at`) VALUES
(1, 1, 'TestUser', 'testuser9172@example.com', 'test\netc...', 9, '2025-10-14 21:37:17');

-- --------------------------------------------------------

--
-- Structure de la table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `date` date NOT NULL,
  `type` enum('tournament','event','stream','news') NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `participants` int(11) DEFAULT NULL,
  `winner` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `likes` int(11) NOT NULL DEFAULT 0,
  `comments` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `events`
--

INSERT INTO `events` (`id`, `title`, `date`, `type`, `image_url`, `participants`, `winner`, `description`, `likes`, `comments`, `created_at`) VALUES
(1, 'Tournoi FIFA 24 - Finale', '2025-01-25', 'tournament', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=800&h=600&fit=crop', 32, 'GameMaster99', 'Une finale épique qui s\'est jouée aux tirs au but !', 54, 12, '2025-10-07 12:59:08'),
(2, 'Soirée Retro Gaming', '2025-01-20', 'event', 'https://images.unsplash.com/photo-1493711662062-fa541adb3fc8?w=800&h=600&fit=crop', 28, NULL, 'Une soirée nostalgique avec les classiques des années 80-90', 64, 18, '2025-10-07 12:59:08'),
(3, 'Championnat Apex Legends', '2025-01-18', 'tournament', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&h=600&fit=crop', 48, 'ProGamer2024', 'Battle royale intense avec des équipes de 3 joueurs', 89, 25, '2025-10-07 12:59:08'),
(4, 'Session Streaming Live', '2025-01-15', 'stream', 'https://images.unsplash.com/photo-1560472355-536de3962603?w=800&h=600&fit=crop', 156, NULL, 'Stream communautaire avec les meilleurs joueurs', 124, 67, '2025-10-07 12:59:08'),
(5, 'Inauguration Nouvelle Zone VR', '2025-01-12', 'news', 'https://images.unsplash.com/photo-1617802690992-15d93263d3a9?w=800&h=600&fit=crop', 85, NULL, 'Découverte de notre nouvel espace réalité virtuelle', 156, 43, '2025-10-07 12:59:08'),
(6, 'Tournoi FIFA 24 - Finale', '2025-01-25', 'tournament', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=800&h=600&fit=crop', 32, 'GameMaster99', 'Une finale épique qui s\'est jouée aux tirs au but !', 52, 12, '2025-10-14 19:39:58'),
(7, 'Soirée Retro Gaming', '2025-01-20', 'event', 'https://images.unsplash.com/photo-1493711662062-fa541adb3fc8?w=800&h=600&fit=crop', 28, NULL, 'Une soirée nostalgique avec les classiques des années 80-90', 68, 18, '2025-10-14 19:39:58'),
(8, 'Championnat Apex Legends', '2025-01-18', 'tournament', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&h=600&fit=crop', 48, 'ProGamer2024', 'Battle royale intense avec des équipes de 3 joueurs', 89, 25, '2025-10-14 19:39:58'),
(9, 'Session Streaming Live', '2025-01-15', 'stream', 'https://images.unsplash.com/photo-1560472355-536de3962603?w=800&h=600&fit=crop', 156, NULL, 'Stream communautaire avec les meilleurs joueurs', 124, 67, '2025-10-14 19:39:58'),
(10, 'Inauguration Nouvelle Zone VR', '2025-01-12', 'news', 'https://images.unsplash.com/photo-1617802690992-15d93263d3a9?w=800&h=600&fit=crop', 85, NULL, 'Découverte de notre nouvel espace réalité virtuelle', 156, 43, '2025-10-14 19:39:58');

-- --------------------------------------------------------

--
-- Structure de la table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `category` enum('tournament','event','stream','general','vr','retro') NOT NULL DEFAULT 'general',
  `event_id` int(11) DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `views` int(11) NOT NULL DEFAULT 0,
  `likes` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gallery`
--

INSERT INTO `gallery` (`id`, `title`, `description`, `image_url`, `thumbnail_url`, `category`, `event_id`, `status`, `display_order`, `views`, `likes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Zone Gaming Pro', 'Image de démonstration', 'https://via.placeholder.com/800x600', NULL, 'general', NULL, 'active', 0, 0, 0, NULL, '2025-10-15 12:13:02', NULL),
(2, 'Tournoi FIFA', 'Image de démonstration', 'https://via.placeholder.com/800x600', NULL, 'tournament', NULL, 'active', 0, 0, 0, NULL, '2025-10-15 12:13:02', NULL),
(3, 'Espace VR', 'Image de démonstration', 'https://via.placeholder.com/800x600', NULL, 'vr', NULL, 'active', 0, 0, 0, NULL, '2025-10-15 12:13:02', NULL),
(4, 'Console Rétro', 'Image de démonstration', 'https://via.placeholder.com/800x600', NULL, 'retro', NULL, 'active', 0, 0, 0, NULL, '2025-10-15 12:13:02', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'other',
  `platform` varchar(100) DEFAULT NULL,
  `min_players` int(11) NOT NULL DEFAULT 1,
  `max_players` int(11) NOT NULL DEFAULT 1,
  `age_rating` varchar(20) DEFAULT NULL,
  `points_per_hour` int(11) NOT NULL DEFAULT 10,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_reservable` tinyint(1) NOT NULL DEFAULT 0,
  `reservation_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `games`
--

INSERT INTO `games` (`id`, `name`, `slug`, `description`, `short_description`, `image_url`, `thumbnail_url`, `category`, `platform`, `min_players`, `max_players`, `age_rating`, `points_per_hour`, `base_price`, `is_reservable`, `reservation_fee`, `is_active`, `is_featured`, `display_order`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'fifa', 'fifa', '', 'jeu de foot', 'http://localhost/projet%20ismo/uploads/games/game_68f0dba0eed968.46393699.jpg', '', 'action', '', 1, 1, '', 1000, 0.00, 0, 0.00, 1, 1, 0, 9, '2025-10-16 13:22:34', '2025-10-16 13:48:49'),
(3, 'ufvvhjk', 'ufvvhjk', 'yhj', 'yguhjk', 'http://localhost/projet%20ismo/uploads/games/game_68f0f9731e80d6.38115405.jpg', '', 'action', '', 1, 1, '+18', 10, 15.00, 0, 0.00, 1, 0, 0, 9, '2025-10-16 15:56:23', '2025-10-16 15:56:23'),
(4, 'naruto', 'naruto', 'trop ice', 'combat game', 'http://localhost/projet%20ismo/uploads/games/game_68f26495c90da6.42376358.jpg', '', 'action', 'Xbox', 1, 1, '18', 10, 500.00, 0, 0.00, 1, 1, 0, 9, '2025-10-17 17:46:05', '2025-10-17 17:46:05'),
(5, '1min de jeu', '1min-de-jeu', 'zdzd', 'sdz', '', '', 'action', 'ddd', 1, 1, '', 10, 0.00, 0, 0.00, 1, 0, 0, 9, '2025-10-17 17:51:07', '2025-10-17 18:08:01'),
(6, 'Test Game Simple 1760797113', 'test-game-simple-1760797113', 'Jeu de test automatique', 'Test auto', '', '', 'action', '', 1, 1, '', 10, 1000.00, 1, 150.00, 1, 0, 0, 9, '2025-10-18 16:18:33', '2025-10-18 16:27:28'),
(7, 'Test Game Reservable 1760797113', 'test-game-reservable-1760797113', 'Jeu VR réservable de test', 'VR Test', NULL, NULL, 'vr', NULL, 1, 1, NULL, 25, 2000.00, 1, 500.00, 1, 1, 1, 9, '2025-10-18 16:18:33', '2025-10-18 16:18:33'),
(8, 'oiukyjtfhb', 'o', '', '', '', '', 'vr', '', 1, 1, '', 10, 0.00, 1, 1500.00, 1, 0, 0, 9, '2025-10-18 16:28:01', '2025-10-18 16:28:01'),
(9, 'fcqcsd', 'f', '', '', '', '', 'action', '', 1, 1, '', 10, 0.00, 1, 500.00, 1, 1, 0, 9, '2025-10-18 16:28:39', '2025-10-18 16:28:39');

-- --------------------------------------------------------

--
-- Structure de la table `game_packages`
--

CREATE TABLE `game_packages` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `points_earned` int(11) NOT NULL,
  `points_cost` int(11) DEFAULT NULL COMMENT 'Co├╗t en points si is_points_only = 1',
  `reward_id` int(11) DEFAULT NULL COMMENT 'ID de la r├®compense li├®e (si cr├®├®e via syst├¿me de r├®compenses)',
  `bonus_multiplier` decimal(3,2) NOT NULL DEFAULT 1.00,
  `is_promotional` tinyint(1) NOT NULL DEFAULT 0,
  `promotional_label` varchar(100) DEFAULT NULL,
  `max_purchases_per_user` int(11) DEFAULT NULL,
  `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_points_only` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Package payable uniquement en points (pas d''argent)',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `game_packages`
--

INSERT INTO `game_packages` (`id`, `game_id`, `name`, `duration_minutes`, `price`, `original_price`, `points_earned`, `points_cost`, `reward_id`, `bonus_multiplier`, `is_promotional`, `promotional_label`, `max_purchases_per_user`, `available_from`, `available_until`, `is_active`, `is_points_only`, `display_order`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, '1h', 10, 15.00, 12.00, 0, NULL, NULL, 1.00, 1, 'promo', 2, NULL, NULL, 1, 0, 0, 9, '2025-10-16 16:32:44', '2025-10-16 16:32:44'),
(2, 3, 'dtgfhjgb', 60, 5000.00, 10.00, 0, NULL, NULL, 1.00, 0, '', NULL, NULL, NULL, 1, 0, 0, 9, '2025-10-17 16:20:10', '2025-10-17 16:20:10'),
(3, 4, 'cc', 60, 150.00, 180.00, 0, NULL, NULL, 1.00, 1, 'xpromo', NULL, NULL, NULL, 1, 0, 0, 9, '2025-10-17 17:47:13', '2025-10-17 17:47:13'),
(4, 1, '1min', 1, 50.00, 1.00, 0, NULL, NULL, 1.00, 1, '', 20, NULL, NULL, 1, 0, 0, 9, '2025-10-17 17:52:20', '2025-10-17 18:00:57'),
(5, 5, 'ppp', 1, 500.00, NULL, 0, NULL, NULL, 1.00, 0, '', NULL, NULL, NULL, 1, 0, 0, 9, '2025-10-17 18:02:30', '2025-10-17 18:09:28'),
(6, 1, 'nnnn', 60, 500.00, NULL, 0, NULL, NULL, 1.00, 0, '', NULL, NULL, NULL, 1, 0, 0, 9, '2025-10-17 21:43:11', '2025-10-17 21:43:11'),
(7, 9, 'zefds', 1, 500.00, NULL, 0, NULL, NULL, 2.00, 1, '500', NULL, NULL, NULL, 1, 0, 0, 9, '2025-10-18 16:30:10', '2025-10-18 16:30:10'),
(8, 1, 'Récompense FIFA - 30 min', 30, 0.00, NULL, 5, 50, 12, 1.00, 0, NULL, NULL, NULL, NULL, 1, 1, 10, NULL, '2025-10-21 00:30:55', '2025-10-21 00:30:55'),
(9, 3, 'Récompense Action - 1h', 60, 0.00, NULL, 10, 100, 13, 1.00, 0, NULL, NULL, NULL, NULL, 1, 1, 10, NULL, '2025-10-21 00:30:55', '2025-10-21 00:30:55'),
(10, 4, 'Récompense Naruto - 30 min', 30, 0.00, NULL, 15, 150, 14, 1.00, 0, NULL, NULL, NULL, NULL, 1, 1, 10, NULL, '2025-10-21 00:30:55', '2025-10-21 00:30:55'),
(11, 3, 'TEST NOUVEAU Package - 45 min', 45, 0.00, NULL, 12, 200, 16, 1.00, 1, '🔥 NOUVEAU', NULL, NULL, NULL, 1, 1, 5, NULL, '2025-10-21 00:39:26', '2025-10-21 00:39:26'),
(12, 9, 'jbn  - 30 min', 30, 0.00, NULL, 50, 2, 18, 1.00, 0, '', 5000, NULL, NULL, 1, 1, 0, 9, '2025-10-21 12:09:58', '2025-10-21 12:09:58');

-- --------------------------------------------------------

--
-- Structure de la table `game_reservations`
--

CREATE TABLE `game_reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `scheduled_start` datetime NOT NULL,
  `scheduled_end` datetime NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Prix du package (hors frais de paiement) + frais de r??servation',
  `reservation_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Montant total pay?? (incluant frais de paiement) si connu',
  `currency` varchar(3) NOT NULL DEFAULT 'XOF',
  `status` enum('pending_payment','paid','cancelled','completed','no_show') NOT NULL DEFAULT 'pending_payment',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='R??servations de jeux ?? des cr??neaux pr??cis';

--
-- Déchargement des données de la table `game_reservations`
--

INSERT INTO `game_reservations` (`id`, `user_id`, `game_id`, `purchase_id`, `scheduled_start`, `scheduled_end`, `duration_minutes`, `base_price`, `reservation_fee`, `total_price`, `currency`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 31, 9, 22, '2025-02-22 14:54:00', '2025-02-22 14:55:00', 1, 1000.00, 500.00, 1000.00, 'XOF', 'pending_payment', NULL, '2025-10-19 17:44:38', '2025-10-19 17:44:38'),
(2, 8, 9, 23, '2025-10-22 10:00:00', '2025-10-22 10:01:00', 1, 1000.00, 500.00, 1000.00, 'XOF', 'pending_payment', NULL, '2025-10-20 14:45:27', '2025-10-20 14:45:27'),
(3, 8, 9, 44, '2025-10-30 08:50:00', '2025-10-30 08:51:00', 1, 1000.00, 500.00, 1000.00, 'XOF', 'cancelled', NULL, '2025-10-22 17:45:38', '2025-10-22 19:15:26'),
(4, 8, 9, 45, '2025-10-31 23:00:00', '2025-10-31 23:01:00', 1, 1000.00, 500.00, 1000.00, 'XOF', 'completed', NULL, '2025-10-22 18:24:44', '2025-10-22 19:16:34');

-- --------------------------------------------------------

--
-- Structure de la table `game_sessions`
--

CREATE TABLE `game_sessions` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `total_minutes` int(11) NOT NULL,
  `used_minutes` int(11) NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `paused_at` datetime DEFAULT NULL,
  `resumed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `remaining_minutes` int(11) GENERATED ALWAYS AS (`total_minutes` - `used_minutes`) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `game_sessions`
--

INSERT INTO `game_sessions` (`id`, `purchase_id`, `user_id`, `game_id`, `total_minutes`, `used_minutes`, `status`, `started_at`, `paused_at`, `resumed_at`, `completed_at`, `expires_at`, `last_activity_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, 8, 1, 10, 0, 'pending', NULL, NULL, NULL, NULL, '2025-11-16 14:12:47', NULL, NULL, '2025-10-17 14:12:47', '2025-10-17 14:12:47'),
(2, 3, 1, 1, 10, 0, 'pending', NULL, NULL, NULL, NULL, '2025-11-16 14:12:54', NULL, NULL, '2025-10-17 14:12:54', '2025-10-17 14:12:54'),
(3, 5, 8, 1, 10, 0, 'pending', NULL, NULL, NULL, NULL, '2025-11-16 14:53:31', NULL, NULL, '2025-10-17 14:53:31', '2025-10-17 14:53:31'),
(4, 7, 8, 3, 60, 0, 'pending', NULL, NULL, NULL, NULL, '2025-11-16 16:21:04', NULL, NULL, '2025-10-17 16:21:04', '2025-10-17 16:21:04'),
(5, 8, 8, 3, 60, 0, 'pending', NULL, NULL, NULL, NULL, '2025-11-16 17:44:21', NULL, NULL, '2025-10-17 17:44:21', '2025-10-17 17:44:21'),
(6, 9, 8, 4, 60, 0, 'pending', NULL, NULL, NULL, NULL, '2025-11-16 17:48:15', NULL, NULL, '2025-10-17 17:48:15', '2025-10-17 17:48:15'),
(7, 10, 8, 5, 1, 0, 'pending', NULL, NULL, NULL, NULL, '2025-11-16 18:10:00', NULL, NULL, '2025-10-17 18:10:00', '2025-10-17 18:10:00'),
(8, 11, 8, 5, 1, 1, 'completed', '2025-10-17 18:46:16', NULL, NULL, '2025-10-17 18:46:53', '2025-11-16 18:38:38', '2025-10-17 18:46:16', NULL, '2025-10-17 18:38:38', '2025-10-17 18:46:53'),
(9, 12, 8, 5, 1, 0, 'pending', NULL, NULL, NULL, NULL, '2025-11-16 22:11:52', NULL, NULL, '2025-10-17 22:11:52', '2025-10-17 22:11:52'),
(10, 13, 8, 1, 60, 0, 'pending', NULL, NULL, NULL, NULL, '2025-11-16 22:11:58', NULL, NULL, '2025-10-17 22:11:58', '2025-10-17 22:11:58'),
(11, 14, 8, 1, 1, 0, 'pending', NULL, NULL, NULL, NULL, '2025-11-17 00:23:48', NULL, NULL, '2025-10-18 00:23:47', '2025-10-18 00:23:47'),
(12, 38, 10, 9, 30, 30, 'active', '2025-10-21 12:34:04', NULL, NULL, NULL, '2025-10-21 13:04:04', '2025-10-21 12:34:04', NULL, '2025-10-21 12:34:04', '2025-10-21 12:34:04'),
(13, 39, 10, 9, 30, 30, 'completed', '2025-10-21 12:35:19', NULL, NULL, '2025-10-21 12:35:19', '2025-10-21 13:05:19', '2025-10-21 12:35:19', NULL, '2025-10-21 12:35:19', '2025-10-21 12:35:19');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `game_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `game_stats` (
`id` int(11)
,`name` varchar(200)
,`slug` varchar(200)
,`category` varchar(50)
,`is_active` tinyint(1)
,`total_purchases` bigint(21)
,`unique_players` bigint(21)
,`total_revenue` decimal(32,2)
,`total_minutes_sold` decimal(32,0)
,`avg_purchase_price` decimal(14,6)
,`completed_purchases` bigint(21)
,`pending_purchases` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure de la table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `validation_code` varchar(32) NOT NULL,
  `qr_code_data` text NOT NULL,
  `qr_code_hash` varchar(64) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'XOF',
  `duration_minutes` int(11) NOT NULL,
  `game_name` varchar(200) NOT NULL,
  `package_name` varchar(150) DEFAULT NULL,
  `status` enum('pending','active','used','expired','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `issued_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `activated_at` datetime DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `activated_by` int(11) DEFAULT NULL,
  `activation_ip` varchar(45) DEFAULT NULL,
  `activation_device` text DEFAULT NULL,
  `scan_attempts` int(11) NOT NULL DEFAULT 0,
  `last_scan_attempt` datetime DEFAULT NULL,
  `is_suspicious` tinyint(1) NOT NULL DEFAULT 0,
  `fraud_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `invoices`
--

INSERT INTO `invoices` (`id`, `purchase_id`, `user_id`, `invoice_number`, `validation_code`, `qr_code_data`, `qr_code_hash`, `amount`, `currency`, `duration_minutes`, `game_name`, `package_name`, `status`, `issued_at`, `expires_at`, `activated_at`, `used_at`, `activated_by`, `activation_ip`, `activation_device`, `scan_attempts`, `last_scan_attempt`, `is_suspicious`, `fraud_notes`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'INV-20251017-00003', '6ZPDDMSI08HFXA8M', '{\"type\":\"game_invoice\",\"code\":\"6ZPDDMSI08HFXA8M\",\"invoice\":\"INV-20251017-00003\",\"user_id\":1,\"game\":\"fifa\",\"duration\":10}', 'c014ea4f9d1f27feba5f7cc01ac583eac9b1acd23a14e4af61a12e070c8ca48a', 15.00, 'XOF', 10, 'fifa', '1h', 'used', '2025-10-17 14:50:52', '2025-12-17 14:50:52', '2025-10-18 14:02:26', '2025-10-18 14:52:30', 1, '127.0.0.1', 'TestAgent', 1, '2025-10-18 14:02:26', 0, NULL, NULL, '2025-10-17 14:50:52', '2025-10-18 14:52:30'),
(2, 4, 8, 'INV-20251017-00004', 'FFN7CZNRXVU2SG8L', '{\"type\":\"game_invoice\",\"code\":\"FFN7CZNRXVU2SG8L\",\"invoice\":\"INV-20251017-00004\",\"user_id\":8,\"game\":\"fifa\",\"duration\":10}', '53d45c5e936b9b6635db7a0624132366c31aa486c94a6e0e1ef5c5b57236d557', 15.00, 'XOF', 10, 'fifa', '1h', 'used', '2025-10-17 14:50:52', '2025-12-17 14:50:52', '2025-10-17 15:14:03', NULL, 9, '127.0.0.1', NULL, 0, NULL, 0, NULL, NULL, '2025-10-17 14:50:52', '2025-10-17 15:14:03'),
(3, 5, 8, 'INV-20251017-00005', 'SD6KYTY26E9SVSY6', '{\"type\": \"game_invoice\", \"code\": \"SD6KYTY26E9SVSY6\", \"invoice\": \"INV-20251017-00005\", \"user_id\": 8, \"game\": \"fifa\", \"duration\": 10}', '20ce0500134b9fd613dde3e3153c9375b4a73e268ee78bf5a1772195ee7bc85e', 15.00, 'XOF', 10, 'fifa', '1h', 'used', '2025-10-17 14:53:31', '2025-12-17 14:53:31', '2025-10-17 15:18:07', NULL, 9, '127.0.0.1', NULL, 0, NULL, 0, NULL, NULL, '2025-10-17 14:53:31', '2025-10-17 15:18:07'),
(4, 6, 1, 'INV-20251017-00006', '3R9TZ9ZV74NP9VCV', '{\"type\": \"game_invoice\", \"code\": \"3R9TZ9ZV74NP9VCV\", \"invoice\": \"INV-20251017-00006\", \"user_id\": 1, \"game\": \"Test Game\", \"duration\": 5}', '54296a47ad9f8b7047b9372966a2b541434ec16b5a961c1898351077ef8a6bf6', 500.00, 'XAF', 5, 'Test Game', 'Package 5min', 'used', '2025-10-17 15:26:53', '2025-12-17 15:26:53', '2025-10-17 15:26:54', '2025-10-17 16:01:18', 1, '127.0.0.1', NULL, 0, NULL, 0, NULL, NULL, '2025-10-17 15:26:53', '2025-10-17 16:01:18'),
(5, 7, 8, 'INV-20251017-00007', 'QVUBTVL5NKJQRC5F', '{\"type\": \"game_invoice\", \"code\": \"QVUBTVL5NKJQRC5F\", \"invoice\": \"INV-20251017-00007\", \"user_id\": 8, \"game\": \"ufvvhjk\", \"duration\": 60}', '339af91fa796b178577163577715c8833aab7fc02777f586cc6072a2560a0125', 5000.00, 'XOF', 60, 'ufvvhjk', 'dtgfhjgb', 'used', '2025-10-17 16:21:04', '2025-12-17 16:21:04', '2025-10-17 16:21:39', '2025-10-17 19:07:46', 9, '127.0.0.1', NULL, 0, NULL, 0, NULL, NULL, '2025-10-17 16:21:04', '2025-10-17 19:07:46'),
(6, 8, 8, 'INV-20251017-00008', '06MCJCTUGHR1NUZ6', '{\"type\": \"game_invoice\", \"code\": \"06MCJCTUGHR1NUZ6\", \"invoice\": \"INV-20251017-00008\", \"user_id\": 8, \"game\": \"ufvvhjk\", \"duration\": 60}', '0da9069175c20d8c3e29f94d6969ff9efa0e08352cf8eacf33c2b091a216dc14', 5000.00, 'XOF', 60, 'ufvvhjk', 'dtgfhjgb', 'used', '2025-10-17 17:44:21', '2025-12-17 17:44:21', '2025-10-18 13:57:39', '2025-10-18 15:02:28', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 3, '2025-10-18 14:10:09', 0, NULL, NULL, '2025-10-17 17:44:21', '2025-10-18 15:02:28'),
(7, 9, 8, 'INV-20251017-00009', '9CO4BV4R7HIR1O0P', '{\"type\": \"game_invoice\", \"code\": \"9CO4BV4R7HIR1O0P\", \"invoice\": \"INV-20251017-00009\", \"user_id\": 8, \"game\": \"naruto\", \"duration\": 60}', '733181d6e4aab4fe0c4eb7949fe6dc3ef0e54eaed190aa01d863ee11f8f04d14', 150.00, 'XOF', 60, 'naruto', 'cc', 'used', '2025-10-17 17:48:15', '2025-12-17 17:48:15', '2025-10-17 17:49:01', '2025-10-17 19:07:46', 9, '127.0.0.1', NULL, 0, NULL, 0, NULL, NULL, '2025-10-17 17:48:15', '2025-10-17 19:07:46'),
(8, 10, 8, 'INV-20251017-00010', 'AIFB0VZ22UQUS54W', '{\"type\": \"game_invoice\", \"code\": \"AIFB0VZ22UQUS54W\", \"invoice\": \"INV-20251017-00010\", \"user_id\": 8, \"game\": \"1min de jeu\", \"duration\": 1}', '690fbc69816e3370e29eeee1d330ead7062ad50b61de813609b104218a729026', 500.00, 'XOF', 1, '1min de jeu', 'ppp', 'used', '2025-10-17 18:10:00', '2025-12-17 18:10:00', '2025-10-17 18:11:28', '2025-10-17 19:07:46', 9, '127.0.0.1', NULL, 0, NULL, 0, NULL, NULL, '2025-10-17 18:10:00', '2025-10-17 19:07:46'),
(9, 11, 8, 'INV-20251017-00011', 'GX0YG54XYQI3MJN9', '{\"type\": \"game_invoice\", \"code\": \"GX0YG54XYQI3MJN9\", \"invoice\": \"INV-20251017-00011\", \"user_id\": 8, \"game\": \"1min de jeu\", \"duration\": 1}', 'e206a05452947c9a7690c701a5d7c6a49ff00a3c2e5c3d9bb12d806bbc8b986a', 500.00, 'XOF', 1, '1min de jeu', 'ppp', 'used', '2025-10-17 18:38:38', '2025-12-17 18:38:38', '2025-10-17 18:39:15', '2025-10-17 19:07:46', 9, '127.0.0.1', NULL, 0, NULL, 0, NULL, NULL, '2025-10-17 18:38:38', '2025-10-17 19:07:46'),
(10, 12, 8, 'INV-20251017-00012', 'NIBSPX984K73LCO3', '{\"type\": \"game_invoice\", \"code\": \"NIBSPX984K73LCO3\", \"invoice\": \"INV-20251017-00012\", \"user_id\": 8, \"game\": \"1min de jeu\", \"duration\": 1}', '0aee55154ef6a8e891deeb87c7b3f3edb9ac4d0e7883ef80055f9b15e00af615', 500.00, 'XOF', 1, '1min de jeu', 'ppp', 'used', '2025-10-17 22:11:52', '2025-12-17 22:11:52', '2025-10-18 13:56:31', '2025-10-18 14:00:49', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 3, '2025-10-18 13:57:13', 0, NULL, NULL, '2025-10-17 22:11:52', '2025-10-18 14:00:49'),
(11, 13, 8, 'INV-20251017-00013', 'I2GWT2LKRRC5D5D5', '{\"type\": \"game_invoice\", \"code\": \"I2GWT2LKRRC5D5D5\", \"invoice\": \"INV-20251017-00013\", \"user_id\": 8, \"game\": \"fifa\", \"duration\": 60}', 'e043f9279d8e3fd5f99dc3987db73276a5a61cc01fca5373917dbaceae2fcf0e', 500.00, 'XOF', 60, 'fifa', 'nnnn', 'used', '2025-10-17 22:11:58', '2025-12-17 22:11:58', '2025-10-17 22:13:50', '2025-10-18 14:00:49', 9, '127.0.0.1', NULL, 0, NULL, 0, NULL, NULL, '2025-10-17 22:11:58', '2025-10-18 14:00:49'),
(12, 14, 8, 'INV-20251018-00014', 'ATXZSS9PFR7INH63', '{\"type\": \"game_invoice\", \"code\": \"ATXZSS9PFR7INH63\", \"invoice\": \"INV-20251018-00014\", \"user_id\": 8, \"game\": \"fifa\", \"duration\": 1}', 'ae665a57cef4a87e0874ec7cd0bfe4b527524e4fc5bc52aebfc2dc21da797942', 50.00, 'XOF', 1, 'fifa', '1min', 'used', '2025-10-18 00:23:47', '2025-12-18 00:23:47', '2025-10-18 12:32:31', '2025-10-18 14:00:49', 9, '127.0.0.1', NULL, 0, NULL, 0, NULL, NULL, '2025-10-18 00:23:47', '2025-10-18 14:00:49'),
(18, 15, 8, 'INV-20251018-00015', 'B74748F8EADA856C', '{\"invoice_id\": 15, \"code\": \"B74748F8EADA856C\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-18 14:24:37\"}', 'a148fa51ed2545ef65563d97def8c4b6394f839b83965291ab7692503d2b509f', 500.00, 'XOF', 1, '1min de jeu', 'ppp', 'used', '2025-10-18 14:24:37', '2025-12-18 14:24:37', '2025-10-18 14:26:40', '2025-10-18 14:52:30', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1, '2025-10-18 14:26:40', 0, NULL, NULL, '2025-10-18 14:24:37', '2025-10-18 14:52:30'),
(19, 17, 8, 'INV-20251018-00017', 'CD27CE149C37159C', '{\"invoice_id\": 17, \"code\": \"CD27CE149C37159C\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-18 14:35:10\"}', '669eb53ded4289f3d74136b51d5d41faa727b0a1901a94772b1477f77a1efeaf', 500.00, 'XOF', 1, '1min de jeu', 'ppp', 'used', '2025-10-18 14:35:10', '2025-12-18 14:35:10', '2025-10-18 14:35:39', '2025-10-18 14:52:29', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 2, '2025-10-18 14:36:09', 0, NULL, NULL, '2025-10-18 14:35:10', '2025-10-18 14:52:29'),
(20, 16, 8, 'INV-20251018-00016', '1AA129EEEC05A9CB', '{\"invoice_id\": 16, \"code\": \"1AA129EEEC05A9CB\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-18 14:35:13\"}', 'cc655abffa1e57deaf5c8ae0c89a4e761776f650049f16dc8dbcf9d1b1a7d098', 500.00, 'XOF', 1, '1min de jeu', 'ppp', 'used', '2025-10-18 14:35:13', '2025-12-18 14:35:13', '2025-10-18 16:54:57', '2025-10-18 16:56:21', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1, '2025-10-18 16:54:58', 0, NULL, NULL, '2025-10-18 14:35:13', '2025-10-18 16:56:21'),
(21, 18, 8, 'INV-20251018-00018', '2E58D6B6C6720E19', '{\"invoice_id\": 18, \"code\": \"2E58D6B6C6720E19\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-18 15:06:05\"}', '9c165d4e0a2469a47680bbf17b223869f4320385d62a3cf0696a885dedc6bbde', 500.00, 'XOF', 1, '1min de jeu', 'ppp', 'used', '2025-10-18 15:06:05', '2025-12-18 15:06:05', '2025-10-18 15:06:21', '2025-10-18 15:07:38', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1, '2025-10-18 15:06:21', 0, NULL, NULL, '2025-10-18 15:06:05', '2025-10-18 15:07:38'),
(22, 19, 8, 'INV-20251018-00019', '11E4E4E8BC2CC5E4', '{\"invoice_id\": 19, \"code\": \"11E4E4E8BC2CC5E4\", \"user_id\": 8, \"amount\": 150.00, \"duration\": 60, \"issued\": \"2025-10-18 15:48:28\"}', '165198977d92662acddbde97fb3b95bc0bf0b21e4928a250e34c393dae609329', 150.00, 'XOF', 60, 'naruto', 'cc', 'used', '2025-10-18 15:48:28', '2025-12-18 15:48:28', '2025-10-18 15:49:02', '2025-10-18 16:54:25', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1, '2025-10-18 15:49:03', 0, NULL, NULL, '2025-10-18 15:48:28', '2025-10-18 16:54:25'),
(23, 20, 8, 'INV-20251018-00020', 'D70685F7B9F71741', '{\"invoice_id\": 20, \"code\": \"D70685F7B9F71741\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-18 16:31:21\"}', 'daca6a613bc6d73c3cb2a7f76852179cdf5f7e66bfff86f877ef5856a99df179', 500.00, 'XOF', 1, 'fcqcsd', 'zefds', 'used', '2025-10-18 16:31:21', '2025-12-18 16:31:21', '2025-10-18 16:31:49', '2025-10-18 16:33:02', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1, '2025-10-18 16:31:49', 0, NULL, NULL, '2025-10-18 16:31:21', '2025-10-18 16:33:02'),
(24, 40, 8, 'INV-20251021-000040', '81FEF97A', '', '', 0.00, 'XOF', 0, '', NULL, 'used', '0000-00-00 00:00:00', '2025-12-21 13:58:52', '2025-10-21 13:59:52', '2025-10-21 14:34:57', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 5, '2025-10-21 14:22:19', 0, NULL, NULL, '2025-10-21 12:54:39', '2025-10-21 14:34:57'),
(25, 27, 8, 'INV-20251021-00027', '3D2FD69CF85564BA', '{\"invoice_id\": 27, \"code\": \"3D2FD69CF85564BA\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-21 13:56:00\"}', '4823d9a64d3b52f70e7ead60b87fa19c341e6eaff2759f3e9394a289a4781530', 500.00, 'XOF', 1, 'fcqcsd', 'zefds', 'used', '2025-10-21 13:56:00', '2025-12-21 13:56:00', '2025-10-21 13:56:22', '2025-10-21 13:57:35', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1, '2025-10-21 13:56:22', 0, NULL, NULL, '2025-10-21 13:56:00', '2025-10-21 13:57:35'),
(26, 43, 8, 'INV-20251022-000043', 'F342-F9A2-C800-5815', '', '', 0.00, 'XOF', 0, '', NULL, 'used', '0000-00-00 00:00:00', '2025-12-22 16:49:22', '2025-10-22 19:17:46', '2025-10-23 00:12:42', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1, '2025-10-22 19:17:46', 0, NULL, NULL, '2025-10-22 16:49:22', '2025-10-23 00:12:42'),
(27, 42, 8, 'INV-20251022-00042', '5838F9DFA578F7AD', '{\"invoice_id\": 42, \"code\": \"5838F9DFA578F7AD\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-22 16:59:07\"}', 'bfdbd4ea3c6fb566c972d4ee9210130155c09a34808a82bd26e87b51310c2b92', 500.00, 'XOF', 1, 'fcqcsd', 'zefds', 'pending', '2025-10-22 16:59:07', '2025-12-22 16:59:07', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-10-22 16:59:07', '2025-10-22 16:59:07'),
(28, 41, 8, 'INV-20251022-00041', 'A0D84648D50786AC', '{\"invoice_id\": 41, \"code\": \"A0D84648D50786AC\", \"user_id\": 8, \"amount\": 0.00, \"duration\": 30, \"issued\": \"2025-10-22 16:59:30\"}', '56037d3df66f5ef883b19637b05375b556cdb239c31345e08fe1af18e26ec04d', 0.00, 'XOF', 30, 'fcqcsd', 'jbn  - 30 min', 'pending', '2025-10-22 16:59:30', '2025-12-22 16:59:30', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-10-22 16:59:30', '2025-10-22 16:59:30'),
(29, 44, 8, 'INV-20251022-00044', '2DF80986BE38FCC0', '{\"invoice_id\": 44, \"code\": \"2DF80986BE38FCC0\", \"user_id\": 8, \"amount\": 1000.00, \"duration\": 1, \"issued\": \"2025-10-22 18:23:08\"}', '3fc66a91a4005fb8893acb675b45323d718e7cb801815146cb153f894268c421', 1000.00, 'XOF', 1, 'fcqcsd', 'zefds', 'pending', '2025-10-22 18:23:08', '2025-12-22 18:23:08', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-10-22 18:23:08', '2025-10-22 18:23:08'),
(30, 45, 8, 'INV-20251022-00045', 'BEB785E49BD92BC1', '{\"invoice_id\": 45, \"code\": \"BEB785E49BD92BC1\", \"user_id\": 8, \"amount\": 1000.00, \"duration\": 1, \"issued\": \"2025-10-22 18:25:22\"}', '8634cd961362e23044a40223c91368b638fc39eabce43a73f0519ff4c5d805fa', 1000.00, 'XOF', 1, 'fcqcsd', 'zefds', 'pending', '2025-10-22 18:25:22', '2025-12-22 18:25:22', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-10-22 18:25:22', '2025-10-22 18:25:22'),
(31, 26, 8, 'INV-20251022-00026', '83EE185C3EEABEBB', '{\"invoice_id\": 26, \"code\": \"83EE185C3EEABEBB\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-22 18:28:00\"}', '24c4488aa269426c8199a265ca1682646284bcba7e3b9e8aa399201044729a21', 500.00, 'XOF', 1, 'fcqcsd', 'zefds', 'pending', '2025-10-22 18:28:00', '2025-12-22 18:28:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-10-22 18:28:00', '2025-10-22 18:28:00'),
(32, 25, 8, 'INV-20251022-00025', 'F30017AFD3E18B17', '{\"invoice_id\": 25, \"code\": \"F30017AFD3E18B17\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-22 18:28:05\"}', 'e6f97cb35e6c78e69a2298fc82809f05798394a2b4db62047a88d0381b8b9cf9', 500.00, 'XOF', 1, 'fcqcsd', 'zefds', 'pending', '2025-10-22 18:28:05', '2025-12-22 18:28:05', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-10-22 18:28:05', '2025-10-22 18:28:05'),
(33, 24, 8, 'INV-20251022-00024', '2DAAF05D17C09321', '{\"invoice_id\": 24, \"code\": \"2DAAF05D17C09321\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-22 18:28:08\"}', 'dee1468ed6bb62e2ec12af957b9853ae2d99caba944c3d3a7f0da887245a9baf', 500.00, 'XOF', 1, 'fcqcsd', 'zefds', 'pending', '2025-10-22 18:28:08', '2025-12-22 18:28:08', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-10-22 18:28:08', '2025-10-22 18:28:08'),
(34, 21, 31, 'INV-20251022-00021', '3845723F4C57F17E', '{\"invoice_id\": 21, \"code\": \"3845723F4C57F17E\", \"user_id\": 31, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-22 18:28:13\"}', 'c4cf344cfadf02de0e3c1701e530310294ee6551a45e2cbae1b4d556c7e10bad', 500.00, 'XOF', 1, 'fcqcsd', 'zefds', 'pending', '2025-10-22 18:28:13', '2025-12-22 18:28:13', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-10-22 18:28:13', '2025-10-22 18:28:13'),
(35, 23, 8, 'INV-20251022-00023', 'A258A148110330DB', '{\"invoice_id\": 23, \"code\": \"A258A148110330DB\", \"user_id\": 8, \"amount\": 1000.00, \"duration\": 1, \"issued\": \"2025-10-22 18:28:16\"}', 'f9c97d04d7e5b0cbbcbd56e042a722c34ea7b5a32447d62ebccc20c20e93c9c0', 1000.00, 'XOF', 1, 'fcqcsd', 'zefds', 'pending', '2025-10-22 18:28:16', '2025-12-22 18:28:16', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-10-22 18:28:16', '2025-10-22 18:28:16'),
(36, 22, 31, 'INV-20251022-00022', 'A393A2C22DB3780D', '{\"invoice_id\": 22, \"code\": \"A393A2C22DB3780D\", \"user_id\": 31, \"amount\": 1000.00, \"duration\": 1, \"issued\": \"2025-10-22 18:28:20\"}', '565005a21ee49103df84626fe6627ad9a83b60a18de42f3141b0a1baa53f8b94', 1000.00, 'XOF', 1, 'fcqcsd', 'zefds', 'pending', '2025-10-22 18:28:20', '2025-12-22 18:28:20', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-10-22 18:28:20', '2025-10-22 18:28:20'),
(37, 46, 25, 'INV-20251022-000046', '0043-2439-86AF-36A0', '', '', 0.00, 'XOF', 0, '', NULL, 'used', '0000-00-00 00:00:00', '2025-12-22 18:42:56', '2025-10-22 18:55:19', '2025-10-23 00:12:47', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 2, '2025-10-22 18:58:31', 0, NULL, NULL, '2025-10-22 18:42:55', '2025-10-23 00:12:47'),
(38, 47, 8, 'INV-20251022-00047', '2A52475FCE11FFAF', '{\"invoice_id\": 47, \"code\": \"2A52475FCE11FFAF\", \"user_id\": 8, \"amount\": 500.00, \"duration\": 1, \"issued\": \"2025-10-22 18:56:32\"}', '4206194f3358177e6d3598ca2bcee40b1e03936af7b73bc63f1dbfe69ce2fc2e', 500.00, 'XOF', 1, 'fcqcsd', 'zefds', 'used', '2025-10-22 18:56:32', '2025-12-22 18:56:32', '2025-10-22 19:09:00', '2025-10-22 19:18:55', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1, '2025-10-22 19:09:00', 0, NULL, NULL, '2025-10-22 18:56:32', '2025-10-22 19:18:55'),
(39, 48, 8, 'INV-20251022-000048', '10B6-C9FA-FAE7-C9B9', '', '', 0.00, 'XOF', 0, '', NULL, 'used', '0000-00-00 00:00:00', '2025-12-22 19:21:49', '2025-10-22 19:22:00', '2025-10-23 00:12:40', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1, '2025-10-22 19:22:00', 0, NULL, NULL, '2025-10-22 19:21:49', '2025-10-23 00:12:40');

-- --------------------------------------------------------

--
-- Structure de la table `invoice_audit_log`
--

CREATE TABLE `invoice_audit_log` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `action` enum('created','activated','used','expired','cancelled','refunded','scan_attempt','fraud_detected','modified','deleted') NOT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `performed_by_type` enum('user','admin','system') NOT NULL,
  `action_details` text DEFAULT NULL,
  `old_values` longtext DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `invoice_audit_log`
--

INSERT INTO `invoice_audit_log` (`id`, `invoice_id`, `action`, `performed_by`, `performed_by_type`, `action_details`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'activated', 9, 'admin', 'Facture activée via scanner admin', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-17 15:14:03'),
(2, 2, '', 9, 'admin', 'Session démarrée', NULL, NULL, NULL, NULL, '2025-10-17 15:17:12'),
(3, 3, 'activated', 9, 'admin', 'Facture activée via scanner admin', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-17 15:18:07'),
(4, 3, '', 9, 'admin', 'Session démarrée', NULL, NULL, NULL, NULL, '2025-10-17 15:18:11'),
(5, 4, 'activated', 1, 'admin', 'Facture activée via scanner admin', NULL, NULL, '127.0.0.1', 'Test Script', '2025-10-17 15:26:54'),
(6, 4, '', 1, 'admin', 'Session démarrée', NULL, NULL, NULL, NULL, '2025-10-17 15:26:54'),
(7, 5, 'activated', 9, 'admin', 'Facture activée via scanner admin', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-17 16:21:39'),
(8, 5, '', 9, 'admin', 'Session démarrée', NULL, NULL, NULL, NULL, '2025-10-17 16:21:44'),
(9, 7, 'activated', 9, 'admin', 'Facture activée via scanner admin', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-17 17:49:01'),
(10, 7, '', 9, 'admin', 'Session démarrée', NULL, NULL, NULL, NULL, '2025-10-17 17:49:05'),
(11, 8, 'activated', 9, 'admin', 'Facture activée via scanner admin', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-17 18:11:28'),
(12, 8, '', 9, 'admin', 'Session démarrée', NULL, NULL, NULL, NULL, '2025-10-17 18:11:33'),
(13, 9, 'activated', 9, 'admin', 'Facture activée via scanner admin', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-17 18:39:15'),
(14, 9, '', 9, 'admin', 'Session démarrée', NULL, NULL, NULL, NULL, '2025-10-17 18:39:21'),
(15, 11, 'activated', 9, 'admin', 'Facture activée via scanner admin', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-17 22:13:50'),
(16, 11, '', 9, 'admin', 'Session démarrée', NULL, NULL, NULL, NULL, '2025-10-17 22:13:55'),
(17, 12, 'activated', 9, 'admin', 'Facture activée via scanner admin', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-18 12:32:31'),
(18, 12, '', 9, 'admin', 'Session démarrée', NULL, NULL, NULL, NULL, '2025-10-18 12:32:40'),
(19, 10, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-18 13:56:31'),
(20, 6, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-18 13:57:39'),
(21, 1, 'activated', 1, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'TestAgent', '2025-10-18 14:02:26'),
(27, 18, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #15', NULL, NULL, NULL, NULL, '2025-10-18 14:24:37'),
(28, 18, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-18 14:26:40'),
(29, 19, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #17', NULL, NULL, NULL, NULL, '2025-10-18 14:35:10'),
(30, 20, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #16', NULL, NULL, NULL, NULL, '2025-10-18 14:35:13'),
(31, 19, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-18 14:35:39'),
(32, 21, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #18', NULL, NULL, NULL, NULL, '2025-10-18 15:06:05'),
(33, 21, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-18 15:06:21'),
(34, 22, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #19', NULL, NULL, NULL, NULL, '2025-10-18 15:48:28'),
(35, 22, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-18 15:49:03'),
(36, 23, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #20', NULL, NULL, NULL, NULL, '2025-10-18 16:31:21'),
(37, 23, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-18 16:31:49'),
(38, 20, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-18 16:54:58'),
(39, 25, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #27', NULL, NULL, NULL, NULL, '2025-10-21 13:56:00'),
(40, 25, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-21 13:56:22'),
(41, 24, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-21 13:59:52'),
(42, 27, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #42', NULL, NULL, NULL, NULL, '2025-10-22 16:59:07'),
(43, 28, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #41', NULL, NULL, NULL, NULL, '2025-10-22 16:59:30'),
(44, 29, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #44', NULL, NULL, NULL, NULL, '2025-10-22 18:23:08'),
(45, 30, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #45', NULL, NULL, NULL, NULL, '2025-10-22 18:25:22'),
(46, 31, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #26', NULL, NULL, NULL, NULL, '2025-10-22 18:28:00'),
(47, 32, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #25', NULL, NULL, NULL, NULL, '2025-10-22 18:28:05'),
(48, 33, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #24', NULL, NULL, NULL, NULL, '2025-10-22 18:28:08'),
(49, 34, 'created', 31, 'system', 'Facture cr├®├®e automatiquement pour achat #21', NULL, NULL, NULL, NULL, '2025-10-22 18:28:13'),
(50, 35, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #23', NULL, NULL, NULL, NULL, '2025-10-22 18:28:16'),
(51, 36, 'created', 31, 'system', 'Facture cr├®├®e automatiquement pour achat #22', NULL, NULL, NULL, NULL, '2025-10-22 18:28:20'),
(52, 37, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-22 18:55:19'),
(53, 38, 'created', 8, 'system', 'Facture cr├®├®e automatiquement pour achat #47', NULL, NULL, NULL, NULL, '2025-10-22 18:56:32'),
(54, 38, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-22 19:09:00'),
(55, 26, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-22 19:17:46'),
(56, 39, 'activated', 9, 'admin', 'Facture activ├®e par scan', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-22 19:22:00');

-- --------------------------------------------------------

--
-- Structure de la table `invoice_scans`
--

CREATE TABLE `invoice_scans` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `validation_code` varchar(32) NOT NULL,
  `scan_result` enum('success','invalid_code','already_used','expired','cancelled','fraud_detected','error') NOT NULL,
  `scan_message` text DEFAULT NULL,
  `scanned_by` int(11) DEFAULT NULL,
  `scanned_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_info` longtext DEFAULT NULL CHECK (json_valid(`device_info`)),
  `request_headers` longtext DEFAULT NULL CHECK (json_valid(`request_headers`)),
  `geolocation` longtext DEFAULT NULL CHECK (json_valid(`geolocation`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `invoice_scans`
--

INSERT INTO `invoice_scans` (`id`, `invoice_id`, `validation_code`, `scan_result`, `scan_message`, `scanned_by`, `scanned_at`, `ip_address`, `user_agent`, `device_info`, `request_headers`, `geolocation`) VALUES
(1, 2, 'FFN7CZNRXVU2SG8L', 'success', 'Facture activée avec succès', 9, '2025-10-17 15:14:03', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(2, 2, 'FFN7CZNRXVU2SG8L', '', NULL, 9, '2025-10-17 15:17:49', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(3, 3, 'SD6KYTY26E9SVSY6', 'success', 'Facture activée avec succès', 9, '2025-10-17 15:18:07', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(4, 4, '3R9TZ9ZV74NP9VCV', 'success', 'Facture activée avec succès', 1, '2025-10-17 15:26:54', '127.0.0.1', 'Test Script', NULL, NULL, NULL),
(5, 5, 'QVUBTVL5NKJQRC5F', 'success', 'Facture activée avec succès', 9, '2025-10-17 16:21:39', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(6, 7, '9CO4BV4R7HIR1O0P', 'success', 'Facture activée avec succès', 9, '2025-10-17 17:49:01', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(7, 8, 'AIFB0VZ22UQUS54W', 'success', 'Facture activée avec succès', 9, '2025-10-17 18:11:28', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(8, 9, 'GX0YG54XYQI3MJN9', 'success', 'Facture activée avec succès', 9, '2025-10-17 18:39:15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(9, NULL, 'IYJGHJKHGJHJIJLH', 'invalid_code', 'Code de validation invalide', 9, '2025-10-17 18:48:52', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(10, 11, 'I2GWT2LKRRC5D5D5', 'success', 'Facture activée avec succès', 9, '2025-10-17 22:13:50', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(11, 12, 'ATXZSS9PFR7INH63', 'success', 'Facture activée avec succès', 9, '2025-10-18 12:32:31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(12, 10, 'NIBSPX984K73LCO3', 'success', 'Activation r├®ussie', 9, '2025-10-18 13:56:31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(13, 10, 'NIBSPX984K73LCO3', '', 'Facture d├®j├á active', 9, '2025-10-18 13:56:53', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(14, 10, 'NIBSPX984K73LCO3', '', 'Facture d├®j├á active', 9, '2025-10-18 13:57:13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(15, 6, '06MCJCTUGHR1NUZ6', 'success', 'Activation r├®ussie', 9, '2025-10-18 13:57:39', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(16, 1, '6ZPDDMSI08HFXA8M', 'success', 'Activation r├®ussie', 1, '2025-10-18 14:02:26', '127.0.0.1', 'TestAgent', NULL, NULL, NULL),
(17, 6, '06MCJCTUGHR1NUZ6', '', 'Facture d├®j├á active', 9, '2025-10-18 14:03:35', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(18, 6, '06MCJCTUGHR1NUZ6', '', 'Facture d├®j├á active', 9, '2025-10-18 14:10:09', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(19, 18, 'B74748F8EADA856C', 'success', 'Activation r├®ussie', 9, '2025-10-18 14:26:40', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(20, 19, 'CD27CE149C37159C', 'success', 'Activation r├®ussie', 9, '2025-10-18 14:35:39', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(21, 19, 'CD27CE149C37159C', '', 'Facture d├®j├á active', 9, '2025-10-18 14:36:09', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(22, 21, '2E58D6B6C6720E19', 'success', 'Activation r├®ussie', 9, '2025-10-18 15:06:21', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(23, 22, '11E4E4E8BC2CC5E4', 'success', 'Activation r├®ussie', 9, '2025-10-18 15:49:03', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(24, 23, 'D70685F7B9F71741', 'success', 'Activation r├®ussie', 9, '2025-10-18 16:31:49', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(25, 20, '1AA129EEEC05A9CB', 'success', 'Activation r├®ussie', 9, '2025-10-18 16:54:58', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(26, 24, '81FEF97A', 'expired', 'Facture expir├®e', 9, '2025-10-21 13:16:31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(27, 24, '81FEF97A', '', 'Facture d├®j├á expired', 9, '2025-10-21 13:18:55', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(28, 25, '3D2FD69CF85564BA', 'success', 'Activation r├®ussie', 9, '2025-10-21 13:56:22', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(29, 24, '81FEF97A', 'success', 'Activation r├®ussie', 9, '2025-10-21 13:59:52', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(30, 24, '81FEF97A', '', 'Facture d├®j├á active', 9, '2025-10-21 14:21:37', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(31, 24, '81FEF97A', '', 'Facture d├®j├á active', 9, '2025-10-21 14:22:19', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(32, NULL, 'F342F9A2C8005815', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:35:51', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(33, NULL, 'F342F9A2C8005815', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:36:08', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(34, NULL, 'F342F9A2C8005815', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:36:29', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(35, NULL, 'BEB7-85E4-9BD9-2BC1', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:47:27', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(36, NULL, '2DF8-0986-BE38-FCC0', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:47:50', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(37, 37, '0043-2439-86AF-36A0', 'success', 'Activation r├®ussie', 9, '2025-10-22 18:55:19', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(38, NULL, '2DF8-0986-BE38-FCC0', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:55:33', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(39, NULL, '2DF8-0986-BE38-FCC0', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:55:34', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(40, NULL, '2DF8-0986-BE38-FCC0', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:55:45', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(41, NULL, 'A0D8-4648-D507-86AC', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:56:06', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(42, NULL, '2A52-475F-CE11-FFAF', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:56:47', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(43, NULL, '2A52-475F-CE11-FFAF', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:57:51', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(44, NULL, '2A52-475F-CE11-FFAF', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:58:24', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(45, 37, '0043-2439-86AF-36A0', '', 'Facture d├®j├á active', 9, '2025-10-22 18:58:31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(46, NULL, '2A52-475F-CE11-FFAF', 'invalid_code', 'Code invalide', 9, '2025-10-22 18:58:44', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(47, 38, '2A52475FCE11FFAF', 'success', 'Activation r├®ussie', 9, '2025-10-22 19:09:00', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(48, 26, 'F342-F9A2-C800-5815', 'success', 'Activation r├®ussie', 9, '2025-10-22 19:17:46', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL),
(49, 39, '10B6-C9FA-FAE7-C9B9', 'success', 'Activation r├®ussie', 9, '2025-10-22 19:22:00', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `levels`
--

CREATE TABLE `levels` (
  `id` int(11) NOT NULL,
  `level_number` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `points_required` int(11) NOT NULL,
  `points_bonus` int(11) NOT NULL DEFAULT 0,
  `color` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `levels`
--

INSERT INTO `levels` (`id`, `level_number`, `name`, `points_required`, `points_bonus`, `color`, `created_at`) VALUES
(1, 1, 'Novice', 0, 0, '#808080', '2025-10-14 21:49:34'),
(2, 2, 'Joueur', 100, 50, '#CD7F32', '2025-10-14 21:49:34'),
(3, 3, 'Passionn├®', 300, 100, '#C0C0C0', '2025-10-14 21:49:34'),
(4, 4, 'Expert', 600, 150, '#FFD700', '2025-10-14 21:49:34'),
(5, 5, 'Ma├«tre', 1000, 250, '#E5E4E2', '2025-10-14 21:49:34'),
(6, 6, 'Champion', 1500, 400, '#50C878', '2025-10-14 21:49:34'),
(7, 7, 'L├®gende', 2500, 600, '#9966CC', '2025-10-14 21:49:34'),
(8, 8, '├ëlite', 4000, 1000, '#FF6347', '2025-10-14 21:49:34'),
(9, 9, 'Titan', 6000, 1500, '#00CED1', '2025-10-14 21:49:34'),
(10, 10, 'Dieu du Gaming', 10000, 2500, '#FF00FF', '2025-10-14 21:49:34');

-- --------------------------------------------------------

--
-- Structure de la table `login_streaks`
--

CREATE TABLE `login_streaks` (
  `user_id` int(11) NOT NULL,
  `current_streak` int(11) NOT NULL DEFAULT 0,
  `longest_streak` int(11) NOT NULL DEFAULT 0,
  `last_login_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `login_streaks`
--

INSERT INTO `login_streaks` (`user_id`, `current_streak`, `longest_streak`, `last_login_date`) VALUES
(8, 3, 3, '2025-10-23'),
(21, 5, 29, '2025-10-16'),
(22, 6, 28, '2025-10-16'),
(23, 15, 9, '2025-10-16'),
(24, 13, 14, '2025-10-16'),
(25, 4, 20, '2025-10-16'),
(26, 2, 9, '2025-10-16'),
(27, 2, 26, '2025-10-16'),
(28, 13, 19, '2025-10-16'),
(29, 13, 19, '2025-10-16'),
(30, 7, 22, '2025-10-16');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `package_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `package_stats` (
`id` int(11)
,`name` varchar(150)
,`game_name` varchar(200)
,`game_slug` varchar(200)
,`duration_minutes` int(11)
,`price` decimal(10,2)
,`points_earned` int(11)
,`is_active` tinyint(1)
,`total_purchases` bigint(21)
,`total_revenue` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Structure de la table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `provider` varchar(100) DEFAULT NULL,
  `api_key_public` varchar(500) DEFAULT NULL,
  `api_key_secret` varchar(500) DEFAULT NULL,
  `api_endpoint` varchar(500) DEFAULT NULL,
  `webhook_secret` varchar(500) DEFAULT NULL,
  `requires_online_payment` tinyint(1) NOT NULL DEFAULT 1,
  `auto_confirm` tinyint(1) NOT NULL DEFAULT 0,
  `auto_confirm_payment` tinyint(1) NOT NULL DEFAULT 0,
  `fee_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fee_fixed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `instructions` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `slug`, `provider`, `api_key_public`, `api_key_secret`, `api_endpoint`, `webhook_secret`, `requires_online_payment`, `auto_confirm`, `auto_confirm_payment`, `fee_percentage`, `fee_fixed`, `is_active`, `display_order`, `instructions`, `created_at`, `updated_at`) VALUES
(1, '^pmlkj', 'pmpolj', 'mtn_momo', NULL, NULL, NULL, NULL, 1, 0, 0, 0.00, 0.00, 1, 0, NULL, '2025-10-16 16:48:33', '2025-10-17 13:59:20'),
(2, 'Sur place', 's', 'manual', NULL, NULL, NULL, NULL, 0, 0, 0, 0.00, 0.00, 1, 0, NULL, '2025-10-17 21:41:20', '2025-10-17 21:41:20'),
(3, 'Mobile Money (KkiaPay)', 'kkiapay', 'kkiapay', NULL, NULL, NULL, NULL, 1, 1, 0, 0.00, 0.00, 1, 3, 'Payez avec MTN Mobile Money, Orange Money, Moov Money ou Wave via KkiaPay. Paiement s??curis?? et instantan??.', '2025-10-23 00:04:06', '2025-10-23 00:04:06'),
(4, 'MTN Mobile Money', 'mtn_momo', 'kkiapay', NULL, NULL, NULL, NULL, 1, 1, 0, 0.00, 0.00, 1, 4, 'Payez avec votre compte MTN Mobile Money via KkiaPay.', '2025-10-23 00:04:06', '2025-10-23 00:04:06'),
(5, 'Orange Money', 'orange_money', 'kkiapay', NULL, NULL, NULL, NULL, 1, 1, 0, 0.00, 0.00, 1, 5, 'Payez avec votre compte Orange Money via KkiaPay.', '2025-10-23 00:04:06', '2025-10-23 00:04:06'),
(6, 'Moov Money', 'moov_money', 'kkiapay', NULL, NULL, NULL, NULL, 1, 1, 0, 0.00, 0.00, 1, 6, 'Payez avec votre compte Moov Money via KkiaPay.', '2025-10-23 00:04:06', '2025-10-23 00:04:06'),
(7, 'Wave', 'wave', 'kkiapay', NULL, NULL, NULL, NULL, 1, 1, 0, 0.00, 0.00, 1, 7, 'Payez avec votre compte Wave via KkiaPay.', '2025-10-23 00:04:06', '2025-10-23 00:04:06');

-- --------------------------------------------------------

--
-- Structure de la table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `transaction_type` enum('charge','refund','chargeback','adjustment') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'XOF',
  `provider_transaction_id` varchar(255) DEFAULT NULL,
  `provider_status` varchar(100) DEFAULT NULL,
  `provider_response` longtext DEFAULT NULL CHECK (json_valid(`provider_response`)),
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `payment_transactions`
--

INSERT INTO `payment_transactions` (`id`, `purchase_id`, `transaction_type`, `amount`, `currency`, `provider_transaction_id`, `provider_status`, `provider_response`, `notes`, `created_at`) VALUES
(1, 3, 'charge', 15.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 14:05:28'),
(2, 4, 'charge', 15.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 14:11:49'),
(3, 5, 'charge', 15.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 14:38:02'),
(4, 7, 'charge', 5000.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 16:20:41'),
(5, 8, 'charge', 5000.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 17:38:58'),
(6, 9, 'charge', 150.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 17:47:44'),
(7, 10, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 18:09:47'),
(8, 11, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 18:38:19'),
(9, 12, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 21:37:21'),
(10, 13, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 22:11:28'),
(11, 14, 'charge', 50.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-17 23:50:40'),
(12, 15, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-18 14:15:22'),
(13, 16, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-18 14:31:39'),
(14, 17, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-18 14:31:55'),
(15, 18, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-18 15:05:53'),
(16, 19, 'charge', 150.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-18 15:47:56'),
(17, 20, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-18 16:30:26'),
(18, 21, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-19 17:43:20'),
(19, 22, 'charge', 1000.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-19 17:44:38'),
(20, 23, 'charge', 1000.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-20 14:45:27'),
(21, 24, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-20 15:07:55'),
(22, 25, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-20 15:17:48'),
(23, 26, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-20 15:29:43'),
(24, 27, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-20 15:36:21'),
(25, 41, 'charge', 0.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-21 22:05:21'),
(26, 42, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-21 22:05:39'),
(27, 44, 'charge', 1000.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-22 17:45:38'),
(28, 45, 'charge', 1000.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-22 18:24:44'),
(29, 47, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-22 18:56:22'),
(30, 49, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-23 13:36:37'),
(31, 50, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-23 13:37:58'),
(32, 51, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-23 13:44:13'),
(33, 52, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-23 13:46:58'),
(34, 53, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-23 13:50:32'),
(35, 54, 'charge', 500.00, 'XOF', NULL, 'pending', NULL, 'Achat créé, en attente de paiement', '2025-10-23 13:57:01');

-- --------------------------------------------------------

--
-- Structure de la table `points_packages`
--

CREATE TABLE `points_packages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `points_amount` int(11) NOT NULL COMMENT 'Nombre de points dans ce package',
  `bonus_points` int(11) DEFAULT 0 COMMENT 'Points bonus offerts',
  `price` decimal(10,2) NOT NULL COMMENT 'Prix en devise r??elle',
  `currency` varchar(3) DEFAULT 'XOF',
  `discount_percentage` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `points_redemption_history`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `points_redemption_history` (
`purchase_id` int(11)
,`user_id` int(11)
,`username` varchar(100)
,`game_id` int(11)
,`game_name` varchar(200)
,`package_id` int(11)
,`package_name` varchar(150)
,`duration_minutes` int(11)
,`points_spent` int(11)
,`points_earned` int(11)
,`payment_status` varchar(50)
,`session_status` varchar(50)
,`created_at` datetime
,`points_cost` int(11)
,`reward_id` int(11)
,`reward_name` varchar(150)
);

-- --------------------------------------------------------

--
-- Structure de la table `points_rules`
--

CREATE TABLE `points_rules` (
  `id` int(11) NOT NULL,
  `action_type` enum('game_played','event_attended','tournament_win','tournament_participate','friend_referred','daily_login','profile_complete','first_purchase','review_written','share_social') NOT NULL,
  `points_amount` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `points_rules`
--

INSERT INTO `points_rules` (`id`, `action_type`, `points_amount`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'game_played', 10, 'Points pour chaque partie jou├®e', 1, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(2, 'event_attended', 50, 'Points pour participation ├á un ├®v├®nement', 0, '2025-10-14 21:49:34', '2025-10-23 14:26:36'),
(3, 'tournament_participate', 100, 'Points pour participation ├á un tournoi', 0, '2025-10-14 21:49:34', '2025-10-23 14:26:45'),
(4, 'tournament_win', 500, 'Points pour victoire dans un tournoi', 0, '2025-10-14 21:49:34', '2025-10-23 14:26:41'),
(5, 'friend_referred', 200, 'Points pour parrainage d\'un ami', 0, '2025-10-14 21:49:34', '2025-10-23 14:26:49'),
(6, 'daily_login', 50, 'Points pour connexion quotidienne', 1, '2025-10-14 21:49:34', '2025-10-23 14:26:58'),
(7, 'profile_complete', 100, 'Bonus pour profil compl├®t├® ├á 100%', 1, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(8, 'first_purchase', 150, 'Bonus pour premier achat/├®change', 1, '2025-10-14 21:49:34', '2025-10-14 21:49:34'),
(9, 'review_written', 1, 'Points pour avoir ├®crit un commentaire', 1, '2025-10-14 21:49:34', '2025-10-23 14:27:18'),
(10, 'share_social', 20, 'Points pour partage sur r├®seaux sociaux', 1, '2025-10-14 21:49:34', '2025-10-14 21:49:34');

-- --------------------------------------------------------

--
-- Structure de la table `points_transactions`
--

CREATE TABLE `points_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `change_amount` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `type` enum('game','tournament','bonus','reservation','friend','adjustment','reward') DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'Type de r??f??rence: game_session, reward, bonus, etc.',
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID de l''entit?? r??f??renc??e',
  `admin_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `points_transactions`
--

INSERT INTO `points_transactions` (`id`, `user_id`, `change_amount`, `reason`, `type`, `reference_type`, `reference_id`, `admin_id`, `created_at`) VALUES
(1, 8, 25, 'Bonus journalier', 'bonus', NULL, NULL, NULL, '2025-10-14 19:04:18'),
(2, 8, -100, 'SANCTION: Sanction personnalisée - tu es bête', 'adjustment', NULL, NULL, 9, '2025-10-14 20:25:54'),
(3, 8, 50, 'Ajustement admin', 'adjustment', NULL, NULL, 9, '2025-10-14 20:52:03'),
(4, 8, -50, 'Compte désactivé - Sanction administrative', 'adjustment', NULL, NULL, 9, '2025-10-14 21:10:59'),
(5, 2, -200, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-09 12:13:02'),
(6, 2, 124, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-21 12:13:02'),
(7, 2, 136, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-02 12:13:02'),
(8, 2, -28, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-14 12:13:02'),
(9, 2, -73, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-24 12:13:02'),
(10, 2, 146, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-24 12:13:02'),
(11, 2, 35, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-02 12:13:02'),
(12, 2, 120, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-29 12:13:02'),
(13, 2, -159, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-12 12:13:02'),
(14, 2, 20, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-10 12:13:02'),
(15, 2, -63, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-28 12:13:02'),
(16, 2, 33, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-18 12:13:02'),
(17, 2, 170, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-18 12:13:02'),
(18, 3, 14, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-05 12:13:02'),
(19, 3, 31, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-25 12:13:02'),
(20, 3, 146, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-03 12:13:02'),
(21, 3, 15, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-21 12:13:02'),
(22, 3, 42, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-04 12:13:02'),
(23, 3, 39, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-05 12:13:02'),
(24, 3, -167, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-20 12:13:02'),
(25, 3, 19, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-24 12:13:02'),
(26, 3, 86, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-22 12:13:02'),
(27, 3, 28, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-18 12:13:02'),
(28, 3, -182, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-16 12:13:02'),
(29, 3, 44, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-12 12:13:02'),
(30, 4, 51, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-16 12:13:02'),
(31, 4, 122, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-03 12:13:02'),
(32, 4, 66, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-23 12:13:02'),
(33, 4, 182, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-03 12:13:02'),
(34, 4, 71, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-15 12:13:02'),
(35, 4, -162, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-06 12:13:02'),
(36, 4, 41, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-16 12:13:02'),
(37, 4, -179, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-26 12:13:02'),
(38, 4, 192, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-06 12:13:02'),
(39, 4, 157, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-10 12:13:02'),
(40, 4, 150, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-06 12:13:02'),
(41, 5, 46, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-15 12:13:02'),
(42, 5, 173, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-02 12:13:02'),
(43, 5, 167, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-03 12:13:02'),
(44, 5, -95, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-19 12:13:02'),
(45, 5, 164, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-28 12:13:02'),
(46, 6, 11, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-21 12:13:02'),
(47, 6, -169, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-10 12:13:02'),
(48, 6, 43, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-05 12:13:02'),
(49, 6, 115, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-05 12:13:02'),
(50, 6, -95, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-09 12:13:02'),
(51, 6, 83, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-26 12:13:02'),
(52, 6, 57, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-03 12:13:02'),
(53, 6, -27, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-14 12:13:02'),
(54, 6, 181, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-02 12:13:02'),
(55, 6, 141, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-20 12:13:02'),
(56, 6, 199, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-12 12:13:02'),
(57, 7, 67, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-28 12:13:02'),
(58, 7, 119, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-30 12:13:02'),
(59, 7, 183, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-22 12:13:02'),
(60, 7, 91, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-01 12:13:02'),
(61, 7, -111, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-22 12:13:02'),
(62, 7, 118, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-29 12:13:02'),
(63, 7, 20, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-11 12:13:02'),
(64, 7, 143, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-02 12:13:02'),
(65, 7, 95, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-09 12:13:02'),
(66, 7, 195, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-20 12:13:02'),
(67, 7, -132, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-12 12:13:02'),
(68, 8, -60, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-02 12:13:02'),
(69, 8, -142, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-11 12:13:02'),
(70, 8, 128, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-05 12:13:02'),
(71, 8, 26, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-29 12:13:02'),
(72, 8, -167, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-14 12:13:02'),
(73, 8, -145, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-12 12:13:02'),
(74, 8, 163, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-02 12:13:02'),
(75, 8, -61, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-19 12:13:02'),
(76, 8, 179, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-23 12:13:02'),
(77, 10, 145, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-12 12:13:02'),
(78, 10, 59, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-14 12:13:02'),
(79, 10, 103, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-25 12:13:02'),
(80, 10, 168, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-23 12:13:02'),
(81, 10, 67, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-09 12:13:02'),
(82, 10, 15, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-09 12:13:02'),
(83, 10, 152, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-06 12:13:02'),
(84, 10, -153, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-06 12:13:02'),
(85, 10, 169, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-30 12:13:02'),
(86, 10, 104, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-11 12:13:02'),
(87, 10, 59, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-27 12:13:02'),
(88, 10, 162, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-04 12:13:02'),
(89, 11, 110, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-09 12:13:02'),
(90, 11, 197, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-04 12:13:02'),
(91, 11, 199, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-05 12:13:02'),
(92, 11, 168, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-30 12:13:02'),
(93, 11, 69, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-17 12:13:02'),
(94, 11, -21, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-25 12:13:02'),
(95, 11, 133, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-08 12:13:03'),
(96, 11, 179, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-05 12:13:03'),
(97, 11, -14, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-09 12:13:03'),
(98, 11, -72, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-19 12:13:03'),
(99, 12, 30, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-15 12:13:03'),
(100, 12, 103, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-04 12:13:03'),
(101, 12, 141, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-24 12:13:03'),
(102, 12, 17, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-16 12:13:03'),
(103, 12, 138, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-02 12:13:03'),
(104, 12, 149, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-03 12:13:03'),
(105, 12, 43, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-05 12:13:03'),
(106, 12, 115, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-11 12:13:03'),
(107, 12, -41, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-27 12:13:03'),
(108, 12, 95, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-04 12:13:03'),
(109, 12, -175, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-19 12:13:03'),
(110, 12, 85, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-19 12:13:03'),
(111, 12, 191, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-14 12:13:03'),
(112, 12, -44, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-19 12:13:03'),
(113, 6, -100, 'SANCTION: Avertissement - Comportement inapproprié', 'adjustment', NULL, NULL, NULL, '2025-10-10 12:13:03'),
(114, 2, 142, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-11 12:21:00'),
(115, 2, 138, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-24 12:21:00'),
(116, 2, -167, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-08 12:21:00'),
(117, 2, 52, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-06 12:21:00'),
(118, 2, -135, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-11 12:21:00'),
(119, 2, 45, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-09 12:21:00'),
(120, 2, 80, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-31 12:21:00'),
(121, 2, 74, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-21 12:21:00'),
(122, 2, 27, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-26 12:21:00'),
(123, 2, 147, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-27 12:21:00'),
(124, 2, 184, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-14 12:21:00'),
(125, 2, 185, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-13 12:21:00'),
(126, 2, -117, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-16 12:21:00'),
(127, 3, 93, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-06 12:21:00'),
(128, 3, 17, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-22 12:21:00'),
(129, 3, -136, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-11 12:21:00'),
(130, 3, 114, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-26 12:21:00'),
(131, 3, 144, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-03 12:21:00'),
(132, 3, -160, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-12 12:21:00'),
(133, 3, 103, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-13 12:21:00'),
(134, 3, 132, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-29 12:21:00'),
(135, 3, -46, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-15 12:21:00'),
(136, 3, 112, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-05 12:21:00'),
(137, 3, 77, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-07 12:21:00'),
(138, 3, 139, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-20 12:21:00'),
(139, 4, 171, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-30 12:21:00'),
(140, 4, 49, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-30 12:21:00'),
(141, 4, -147, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-05 12:21:00'),
(142, 4, -32, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-30 12:21:00'),
(143, 4, 91, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-25 12:21:00'),
(144, 4, 168, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-20 12:21:00'),
(145, 4, 85, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-02 12:21:00'),
(146, 4, -79, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-24 12:21:00'),
(147, 4, 199, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-03 12:21:00'),
(148, 4, 90, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-31 12:21:00'),
(149, 4, 59, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-30 12:21:00'),
(150, 4, -93, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-29 12:21:00'),
(151, 4, 178, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-21 12:21:00'),
(152, 4, 59, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-22 12:21:00'),
(153, 5, 71, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-18 12:21:00'),
(154, 5, 29, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-10 12:21:00'),
(155, 5, 112, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-28 12:21:00'),
(156, 5, 17, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-29 12:21:00'),
(157, 5, 178, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-14 12:21:00'),
(158, 5, 22, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-27 12:21:00'),
(159, 5, -53, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-19 12:21:00'),
(160, 5, 113, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-28 12:21:00'),
(161, 6, 68, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-15 12:21:00'),
(162, 6, 185, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-29 12:21:00'),
(163, 6, 27, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-02 12:21:00'),
(164, 6, 25, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-26 12:21:00'),
(165, 6, -35, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-11 12:21:00'),
(166, 6, -190, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-17 12:21:00'),
(167, 6, 170, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-17 12:21:00'),
(168, 6, 55, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-21 12:21:00'),
(169, 6, 100, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-08 12:21:00'),
(170, 6, 147, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-22 12:21:00'),
(171, 6, 115, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-25 12:21:00'),
(172, 6, -50, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-29 12:21:00'),
(173, 6, 158, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-12 12:21:00'),
(174, 6, 84, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-06 12:21:00'),
(175, 7, 64, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-08 12:21:00'),
(176, 7, 92, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-24 12:21:00'),
(177, 7, 41, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-16 12:21:00'),
(178, 7, 104, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-06 12:21:00'),
(179, 7, 91, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-21 12:21:00'),
(180, 7, -96, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-22 12:21:00'),
(181, 7, 60, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-13 12:21:00'),
(182, 7, 182, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-19 12:21:00'),
(183, 8, -23, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-12 12:21:00'),
(184, 8, 24, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-25 12:21:00'),
(185, 8, 109, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-09 12:21:00'),
(186, 8, -53, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-23 12:21:00'),
(187, 8, 19, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-03 12:21:00'),
(188, 8, 106, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-22 12:21:00'),
(189, 8, 26, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-17 12:21:00'),
(190, 8, 149, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-10 12:21:00'),
(191, 8, 18, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-08 12:21:00'),
(192, 8, 27, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-01 12:21:00'),
(193, 8, 123, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-27 12:21:00'),
(194, 10, 17, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-05 12:21:00'),
(195, 10, -10, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-27 12:21:00'),
(196, 10, 177, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-11 12:21:00'),
(197, 10, 168, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-07 12:21:00'),
(198, 10, 23, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-13 12:21:00'),
(199, 10, -116, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-05 12:21:00'),
(200, 10, 139, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-23 12:21:00'),
(201, 10, 32, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-03 12:21:00'),
(202, 11, 176, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-17 12:21:00'),
(203, 11, 87, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-03 12:21:00'),
(204, 11, 90, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-18 12:21:00'),
(205, 11, 157, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-01 12:21:00'),
(206, 11, 169, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-01 12:21:00'),
(207, 11, 190, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-22 12:21:00'),
(208, 12, 11, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-19 12:21:00'),
(209, 12, 137, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-21 12:21:00'),
(210, 12, 33, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-20 12:21:00'),
(211, 12, 142, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-09 12:21:00'),
(212, 12, 89, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-30 12:21:00'),
(213, 3, -100, 'SANCTION: Avertissement - Comportement inapproprié', 'adjustment', NULL, NULL, NULL, '2025-10-10 12:21:00'),
(214, 2, 47, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-24 12:21:17'),
(215, 2, -67, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-13 12:21:17'),
(216, 2, -32, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-27 12:21:17'),
(217, 2, 30, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-22 12:21:17'),
(218, 2, -181, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-14 12:21:17'),
(219, 2, 153, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-10 12:21:17'),
(220, 2, 163, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-06 12:21:17'),
(221, 2, 179, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-26 12:21:17'),
(222, 2, 114, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-20 12:21:17'),
(223, 2, 95, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-01 12:21:17'),
(224, 2, -52, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-25 12:21:17'),
(225, 2, 90, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-28 12:21:17'),
(226, 2, -170, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-19 12:21:17'),
(227, 2, 25, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-26 12:21:17'),
(228, 2, -146, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-17 12:21:17'),
(229, 3, 168, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-20 12:21:17'),
(230, 3, 144, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-01 12:21:17'),
(231, 3, -134, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-29 12:21:17'),
(232, 3, 87, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-22 12:21:17'),
(233, 3, -16, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-24 12:21:17'),
(234, 4, 60, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-21 12:21:17'),
(235, 4, 145, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-03 12:21:17'),
(236, 4, 16, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-09 12:21:17'),
(237, 4, 104, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-08 12:21:17'),
(238, 4, 57, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-18 12:21:17'),
(239, 4, 158, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-20 12:21:17'),
(240, 4, 200, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-28 12:21:17'),
(241, 5, -42, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-11 12:21:17'),
(242, 5, 58, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-20 12:21:17'),
(243, 5, 183, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-07 12:21:17'),
(244, 5, 130, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-23 12:21:17'),
(245, 5, 109, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-27 12:21:17'),
(246, 5, 82, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-29 12:21:17'),
(247, 5, -39, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-02 12:21:17'),
(248, 5, 18, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-21 12:21:17'),
(249, 5, -86, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-22 12:21:17'),
(250, 5, -108, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-01 12:21:17'),
(251, 5, -87, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-13 12:21:17'),
(252, 5, 92, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-18 12:21:17'),
(253, 5, 111, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-01 12:21:17'),
(254, 5, 126, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-01 12:21:17'),
(255, 6, 24, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-11 12:21:17'),
(256, 6, 61, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-05 12:21:17'),
(257, 6, 21, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-23 12:21:17'),
(258, 6, 110, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-05 12:21:17'),
(259, 6, 62, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-05 12:21:17'),
(260, 6, 198, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-02 12:21:17'),
(261, 7, 66, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-14 12:21:17'),
(262, 7, 85, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-18 12:21:17'),
(263, 7, 25, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-24 12:21:17'),
(264, 7, 113, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-01 12:21:17'),
(265, 7, -134, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-01 12:21:17'),
(266, 7, -144, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-08 12:21:17'),
(267, 7, 145, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-29 12:21:17'),
(268, 7, 44, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-05 12:21:17'),
(269, 7, -185, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-10 12:21:17'),
(270, 7, 41, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-08 12:21:17'),
(271, 7, 156, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-29 12:21:17'),
(272, 7, 129, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-04 12:21:17'),
(273, 7, 12, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-11 12:21:17'),
(274, 8, 145, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-05 12:21:17'),
(275, 8, 185, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-03 12:21:17'),
(276, 8, 72, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-31 12:21:17'),
(277, 8, 175, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-30 12:21:17'),
(278, 8, 55, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-29 12:21:17'),
(279, 8, -128, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-07 12:21:17'),
(280, 8, 101, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-29 12:21:17'),
(281, 8, 186, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-19 12:21:17'),
(282, 8, 31, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-23 12:21:17'),
(283, 8, 28, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-26 12:21:17'),
(284, 8, -29, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-19 12:21:17'),
(285, 8, 53, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-20 12:21:17'),
(286, 10, 149, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-24 12:21:17'),
(287, 10, 47, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-01 12:21:17'),
(288, 10, 16, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-18 12:21:17'),
(289, 10, 146, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-01 12:21:17'),
(290, 10, 135, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-26 12:21:17'),
(291, 11, 42, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-06 12:21:17'),
(292, 11, 199, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-06 12:21:17'),
(293, 11, 64, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-29 12:21:17'),
(294, 11, 151, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-27 12:21:17'),
(295, 11, -136, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-25 12:21:17'),
(296, 11, -56, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-25 12:21:17'),
(297, 11, 74, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-13 12:21:17'),
(298, 11, -66, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-19 12:21:17'),
(299, 11, 92, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-13 12:21:17'),
(300, 11, 47, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-28 12:21:17'),
(301, 12, 62, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-22 12:21:17'),
(302, 12, 51, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-23 12:21:17'),
(303, 12, 139, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-31 12:21:17'),
(304, 12, 181, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-05 12:21:17'),
(305, 12, -85, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-28 12:21:17'),
(306, 12, 153, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-09 12:21:17'),
(307, 12, 133, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-26 12:21:17'),
(308, 12, 163, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-03 12:21:17'),
(309, 12, 26, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-01 12:21:17'),
(310, 12, -122, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-20 12:21:17'),
(311, 12, -169, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-30 12:21:17'),
(312, 12, -100, 'SANCTION: Avertissement - Comportement inapproprié', 'adjustment', NULL, NULL, NULL, '2025-10-10 12:21:17'),
(313, 2, -41, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-21 12:21:34'),
(314, 2, 77, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-16 12:21:34'),
(315, 2, 146, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-25 12:21:34'),
(316, 2, 91, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-06 12:21:34'),
(317, 2, 10, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-05 12:21:34'),
(318, 2, 126, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-07 12:21:34'),
(319, 2, 133, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-26 12:21:34'),
(320, 2, 16, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-23 12:21:34'),
(321, 3, 44, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-11 12:21:34'),
(322, 3, 53, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-23 12:21:34'),
(323, 3, 10, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-13 12:21:34'),
(324, 3, 195, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-07 12:21:34'),
(325, 3, -127, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-12 12:21:34'),
(326, 4, 91, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-05 12:21:34'),
(327, 4, 164, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-22 12:21:34'),
(328, 4, -107, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-27 12:21:34'),
(329, 4, -31, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-22 12:21:34'),
(330, 4, 153, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-16 12:21:34'),
(331, 4, 39, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-25 12:21:34'),
(332, 4, 172, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-26 12:21:34'),
(333, 4, -123, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-08 12:21:34'),
(334, 4, -122, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-08 12:21:34'),
(335, 4, -110, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-08 12:21:34'),
(336, 4, 153, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-04 12:21:34'),
(337, 5, -32, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-27 12:21:34'),
(338, 5, 82, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-04 12:21:34'),
(339, 5, 101, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-08 12:21:34'),
(340, 5, -180, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-06 12:21:34'),
(341, 5, 171, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-18 12:21:34'),
(342, 5, 192, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-04 12:21:34'),
(343, 5, 56, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-06 12:21:34'),
(344, 5, 126, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-11 12:21:34'),
(345, 5, 149, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-16 12:21:34'),
(346, 5, 15, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-02 12:21:34'),
(347, 6, -88, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-21 12:21:34'),
(348, 6, 135, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-25 12:21:34'),
(349, 6, 115, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-15 12:21:34'),
(350, 6, 67, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-22 12:21:34'),
(351, 6, 14, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-10-01 12:21:34'),
(352, 6, 161, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-17 12:21:34'),
(353, 6, 154, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-24 12:21:34'),
(354, 6, 24, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-10 12:21:34'),
(355, 6, 163, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-10 12:21:34'),
(356, 6, 120, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-07 12:21:34'),
(357, 7, 194, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-25 12:21:34'),
(358, 7, -183, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-28 12:21:34'),
(359, 7, 56, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-03 12:21:34'),
(360, 7, 179, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-24 12:21:34'),
(361, 7, 144, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-05 12:21:34'),
(362, 7, 151, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-20 12:21:34'),
(363, 8, 79, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-05 12:21:34'),
(364, 8, 15, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-29 12:21:34'),
(365, 8, -133, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-17 12:21:34'),
(366, 8, -178, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-31 12:21:34'),
(367, 8, 123, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-28 12:21:34'),
(368, 8, -109, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-07 12:21:34'),
(369, 8, 139, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-29 12:21:34'),
(370, 8, 157, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-16 12:21:34'),
(371, 8, 184, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-18 12:21:34'),
(372, 8, 198, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-27 12:21:34'),
(373, 8, 174, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-20 12:21:34'),
(374, 8, 68, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-14 12:21:34'),
(375, 8, 149, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-14 12:21:34'),
(376, 8, 141, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-11 12:21:34'),
(377, 8, 68, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-03 12:21:34'),
(378, 10, 39, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-09-27 12:21:34'),
(379, 10, 28, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-01 12:21:34'),
(380, 10, 171, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-07 12:21:34'),
(381, 10, 32, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-01 12:21:34'),
(382, 10, 161, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-29 12:21:34'),
(383, 10, 20, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-06 12:21:34'),
(384, 10, 110, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-08-27 12:21:34'),
(385, 10, 63, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-19 12:21:34'),
(386, 10, 134, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-08 12:21:34'),
(387, 10, 136, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-19 12:21:34'),
(388, 10, -123, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-10-11 12:21:34'),
(389, 10, -187, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-17 12:21:34'),
(390, 11, 45, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-14 12:21:34'),
(391, 11, -120, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-08-27 12:21:34'),
(392, 11, 88, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-10-06 12:21:34'),
(393, 11, 13, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-16 12:21:34'),
(394, 11, 178, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-31 12:21:34'),
(395, 11, 49, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-02 12:21:34'),
(396, 11, 42, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-08 12:21:34'),
(397, 11, 125, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-25 12:21:34'),
(398, 11, 82, 'Bonus quotidien', 'bonus', NULL, NULL, NULL, '2025-08-31 12:21:34'),
(399, 12, 123, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-08-30 12:21:34'),
(400, 12, 148, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-19 12:21:34'),
(401, 12, -111, 'Achat récompense', 'reward', NULL, NULL, NULL, '2025-09-10 12:21:34'),
(402, 12, 156, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-09-17 12:21:34'),
(403, 12, 118, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-29 12:21:34'),
(404, 12, 109, 'Partie jouée', 'game', NULL, NULL, NULL, '2025-09-03 12:21:34'),
(405, 12, 64, 'Participation tournoi', 'tournament', NULL, NULL, NULL, '2025-10-12 12:21:34'),
(406, 3, -100, 'SANCTION: Avertissement - Comportement inapproprié', 'adjustment', NULL, NULL, NULL, '2025-10-10 12:21:34'),
(407, 8, 25, 'Bonus journalier', 'bonus', NULL, NULL, NULL, '2025-10-15 14:43:46'),
(408, 8, -12, 'Échange récompense: !:;', 'reward', NULL, NULL, NULL, '2025-10-15 14:43:55'),
(409, 8, -12, 'Échange récompense: !:;', 'reward', NULL, NULL, NULL, '2025-10-15 14:44:02'),
(410, 8, 25, 'Bonus journalier', 'bonus', NULL, NULL, NULL, '2025-10-16 14:15:09'),
(428, 21, 44, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-05 14:57:42'),
(429, 21, 95, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-17 14:57:42'),
(430, 21, 88, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-18 14:57:42'),
(431, 21, 46, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-12 14:57:42'),
(432, 21, 52, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-10 14:57:42'),
(433, 21, 25, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-10 14:57:42'),
(434, 21, 81, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-28 14:57:42'),
(435, 21, 43, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-12 14:57:42'),
(436, 21, 79, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-23 14:57:42'),
(437, 21, 88, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-08 14:57:42'),
(438, 21, 67, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-26 14:57:42'),
(439, 21, 76, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-13 14:57:42'),
(440, 21, 51, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-21 14:57:42'),
(441, 21, 55, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-28 14:57:42'),
(442, 21, 37, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-23 14:57:42'),
(443, 21, 30, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-21 14:57:42'),
(444, 21, 14, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-19 14:57:42'),
(445, 21, 74, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-22 14:57:42'),
(446, 21, 78, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-09 14:57:42'),
(447, 21, 45, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-12 14:57:42'),
(448, 22, 35, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-22 14:57:42'),
(449, 22, 45, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-09 14:57:42'),
(450, 22, 17, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-15 14:57:42'),
(451, 22, 67, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-16 14:57:42'),
(452, 22, 22, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-27 14:57:42'),
(453, 22, 84, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-29 14:57:42'),
(454, 22, 54, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-14 14:57:42'),
(455, 22, 97, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-22 14:57:42'),
(456, 22, 21, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-02 14:57:42'),
(457, 22, 24, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-07 14:57:42'),
(458, 22, 33, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-16 14:57:42'),
(459, 22, 81, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-04 14:57:42'),
(460, 22, 21, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-02 14:57:42'),
(461, 22, 30, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-01 14:57:42'),
(462, 23, 78, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-11 14:57:42'),
(463, 23, 14, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-07 14:57:42'),
(464, 23, 21, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-21 14:57:42'),
(465, 23, 37, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-01 14:57:42'),
(466, 23, 22, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-08 14:57:42'),
(467, 23, 74, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-24 14:57:42'),
(468, 23, 45, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-19 14:57:42'),
(469, 23, 65, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-28 14:57:42'),
(470, 23, 69, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-24 14:57:42'),
(471, 23, 63, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-14 14:57:42'),
(472, 23, 49, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-03 14:57:42'),
(473, 23, 55, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-13 14:57:42'),
(474, 23, 80, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-27 14:57:42'),
(475, 23, 99, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-17 14:57:42'),
(476, 24, 41, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-05 14:57:42'),
(477, 24, 88, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-22 14:57:42'),
(478, 24, 93, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-01 14:57:42'),
(479, 24, 57, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-20 14:57:42'),
(480, 24, 49, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-30 14:57:42'),
(481, 24, 44, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-09 14:57:42'),
(482, 24, 96, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-19 14:57:42'),
(483, 24, 57, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-07 14:57:42'),
(484, 24, 91, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-19 14:57:42'),
(485, 24, 81, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-21 14:57:42'),
(486, 24, 18, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-19 14:57:42'),
(487, 24, 33, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-19 14:57:42'),
(488, 24, 50, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-20 14:57:42'),
(489, 24, 96, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-26 14:57:42'),
(490, 24, 14, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-25 14:57:42'),
(491, 24, 74, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-26 14:57:42'),
(492, 24, 12, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-04 14:57:42'),
(493, 24, 55, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-30 14:57:42'),
(494, 24, 74, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-19 14:57:42'),
(495, 24, 86, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-24 14:57:42'),
(496, 24, 57, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-10 14:57:42'),
(497, 24, 95, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-31 14:57:42'),
(498, 24, 38, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-02 14:57:42'),
(499, 24, 66, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-29 14:57:42'),
(500, 24, 23, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-20 14:57:42'),
(501, 24, 20, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-28 14:57:42'),
(502, 24, 38, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-22 14:57:42'),
(503, 25, 88, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-12 14:57:42'),
(504, 25, 50, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-24 14:57:42'),
(505, 25, 83, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-23 14:57:42'),
(506, 25, 75, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-06 14:57:42'),
(507, 25, 51, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-11 14:57:42'),
(508, 25, 79, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-21 14:57:42'),
(509, 25, 25, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-10 14:57:42'),
(510, 25, 94, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-11 14:57:42'),
(511, 25, 54, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-02 14:57:42'),
(512, 25, 14, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-04 14:57:42'),
(513, 25, 83, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-03 14:57:42'),
(514, 25, 67, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-04 14:57:42'),
(515, 25, 63, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-19 14:57:42'),
(516, 25, 46, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-05 14:57:42'),
(517, 25, 93, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-24 14:57:42'),
(518, 25, 16, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-25 14:57:42'),
(519, 25, 98, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-13 14:57:42'),
(520, 25, 42, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-10 14:57:42'),
(521, 25, 81, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-30 14:57:42'),
(522, 25, 77, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-21 14:57:42'),
(523, 25, 20, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-18 14:57:42'),
(524, 25, 15, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-15 14:57:42'),
(525, 25, 31, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-30 14:57:42'),
(526, 25, 15, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-14 14:57:42'),
(527, 25, 10, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-08 14:57:42'),
(528, 25, 72, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-19 14:57:42'),
(529, 25, 29, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-06 14:57:42'),
(530, 25, 93, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-01 14:57:42'),
(531, 25, 71, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-23 14:57:42'),
(532, 26, 51, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-27 14:57:42'),
(533, 26, 49, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-22 14:57:42'),
(534, 26, 95, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-22 14:57:42'),
(535, 26, 60, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-18 14:57:42'),
(536, 26, 33, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-21 14:57:42'),
(537, 26, 46, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-13 14:57:42'),
(538, 26, 57, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-27 14:57:42'),
(539, 26, 86, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-08 14:57:42'),
(540, 26, 32, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-11 14:57:42'),
(541, 26, 66, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-12 14:57:42'),
(542, 26, 82, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-11 14:57:42'),
(543, 26, 85, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-07 14:57:42'),
(544, 26, 64, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-13 14:57:42'),
(545, 26, 22, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-29 14:57:42'),
(546, 26, 21, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-19 14:57:42'),
(547, 26, 55, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-06 14:57:42'),
(548, 26, 60, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-23 14:57:42'),
(549, 26, 82, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-25 14:57:42'),
(550, 26, 98, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-07 14:57:42'),
(551, 26, 40, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-22 14:57:42'),
(552, 26, 83, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-27 14:57:42'),
(553, 27, 10, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-24 14:57:42'),
(554, 27, 56, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-07 14:57:42'),
(555, 27, 15, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-15 14:57:42'),
(556, 27, 27, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-13 14:57:42'),
(557, 27, 29, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-02 14:57:42'),
(558, 27, 84, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-17 14:57:42'),
(559, 27, 51, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-29 14:57:42'),
(560, 27, 51, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-01 14:57:42'),
(561, 27, 76, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-06 14:57:42'),
(562, 27, 20, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-22 14:57:42'),
(563, 27, 87, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-27 14:57:42'),
(564, 27, 93, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-21 14:57:42'),
(565, 27, 13, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-23 14:57:42'),
(566, 27, 66, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-01 14:57:42'),
(567, 28, 51, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-14 14:57:42'),
(568, 28, 15, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-30 14:57:42'),
(569, 28, 46, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-12 14:57:42'),
(570, 28, 16, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-01 14:57:42'),
(571, 28, 83, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-08 14:57:42'),
(572, 28, 37, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-24 14:57:42'),
(573, 28, 25, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-02 14:57:42'),
(574, 28, 59, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-20 14:57:42'),
(575, 28, 40, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-05 14:57:42'),
(576, 28, 23, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-11 14:57:42'),
(577, 28, 51, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-20 14:57:42'),
(578, 28, 97, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-27 14:57:42'),
(579, 28, 78, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-04 14:57:42'),
(580, 28, 58, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-06 14:57:42'),
(581, 28, 65, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-16 14:57:42'),
(582, 28, 26, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-18 14:57:42'),
(583, 28, 19, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-03 14:57:42'),
(584, 28, 33, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-12 14:57:42'),
(585, 28, 83, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-05 14:57:42'),
(586, 28, 44, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-19 14:57:42'),
(587, 28, 53, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-11 14:57:42'),
(588, 28, 44, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-12 14:57:42'),
(589, 28, 33, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-13 14:57:42'),
(590, 28, 47, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-12 14:57:42'),
(591, 28, 27, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-21 14:57:42'),
(592, 28, 45, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-14 14:57:42'),
(593, 28, 65, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-23 14:57:42'),
(594, 28, 25, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-09 14:57:42'),
(595, 29, 53, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-21 14:57:42'),
(596, 29, 78, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-17 14:57:42'),
(597, 29, 95, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-22 14:57:42'),
(598, 29, 92, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-30 14:57:42'),
(599, 29, 12, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-02 14:57:42'),
(600, 29, 88, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-10 14:57:42'),
(601, 29, 45, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-01 14:57:42'),
(602, 29, 40, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-07 14:57:42'),
(603, 29, 24, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-15 14:57:42'),
(604, 29, 49, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-01 14:57:42'),
(605, 29, 58, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-30 14:57:42'),
(606, 29, 40, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-17 14:57:42'),
(607, 29, 79, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-14 14:57:42'),
(608, 29, 86, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-28 14:57:42'),
(609, 29, 46, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-10 14:57:42'),
(610, 29, 20, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-09 14:57:42'),
(611, 29, 37, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-25 14:57:42');
INSERT INTO `points_transactions` (`id`, `user_id`, `change_amount`, `reason`, `type`, `reference_type`, `reference_id`, `admin_id`, `created_at`) VALUES
(612, 29, 13, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-10 14:57:42'),
(613, 29, 23, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-04 14:57:42'),
(614, 29, 83, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-03 14:57:42'),
(615, 29, 16, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-23 14:57:42'),
(616, 29, 67, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-28 14:57:42'),
(617, 29, 56, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-27 14:57:42'),
(618, 29, 32, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-21 14:57:42'),
(619, 29, 99, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-10 14:57:42'),
(620, 29, 61, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-02 14:57:42'),
(621, 29, 56, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-22 14:57:42'),
(622, 29, 93, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-18 14:57:42'),
(623, 29, 49, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-22 14:57:42'),
(624, 30, 90, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-02 14:57:42'),
(625, 30, 13, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-06 14:57:42'),
(626, 30, 100, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-20 14:57:42'),
(627, 30, 53, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-03 14:57:42'),
(628, 30, 19, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-29 14:57:42'),
(629, 30, 62, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-11 14:57:42'),
(630, 30, 74, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-10 14:57:42'),
(631, 30, 33, 'Activité de test', 'game', NULL, NULL, NULL, '2025-09-20 14:57:42'),
(632, 30, 87, 'Activité de test', 'game', NULL, NULL, NULL, '2025-10-09 14:57:42'),
(633, 30, 14, 'Activité de test', 'game', NULL, NULL, NULL, '2025-08-25 14:57:42'),
(634, 8, 5, 'Connexion quotidienne (série de 1)', 'bonus', NULL, NULL, NULL, '2025-10-16 15:30:03'),
(635, 8, -12, 'Échange récompense: !:;', 'reward', NULL, NULL, NULL, '2025-10-16 15:34:44'),
(636, 8, 5, 'Connexion quotidienne (série de 2)', 'bonus', NULL, NULL, NULL, '2025-10-17 10:50:00'),
(637, 8, 25, 'Bonus journalier', 'bonus', NULL, NULL, NULL, '2025-10-17 11:28:42'),
(638, 8, 25, 'Bonus journalier', 'bonus', NULL, NULL, NULL, '2025-10-18 12:28:01'),
(639, 8, 10, 'Connexion quotidienne (série de 3) +5 bonus', 'bonus', NULL, NULL, NULL, '2025-10-18 16:37:49'),
(640, 8, -50, 'Échange récompense: dfsdf', 'reward', NULL, NULL, NULL, '2025-10-18 22:18:23'),
(641, 8, -15, 'Échange récompense: 111', 'reward', NULL, NULL, NULL, '2025-10-18 22:18:38'),
(642, 31, 25, 'Bonus journalier', 'bonus', NULL, NULL, NULL, '2025-10-19 17:40:36'),
(643, 25, -10, 'Échange récompense: moiljkh', 'reward', NULL, NULL, NULL, '2025-10-20 16:52:38'),
(644, 8, 5, 'Connexion quotidienne (série de 1)', 'bonus', NULL, NULL, NULL, '2025-10-21 00:03:05'),
(645, 8, 25, 'Bonus journalier', 'bonus', NULL, NULL, NULL, '2025-10-21 00:03:25'),
(646, 10, -2, 'Échange pour jbn  - 30 min - fcqcsd (Purchase #37)', '', NULL, NULL, NULL, '2025-10-21 12:32:49'),
(647, 10, -2, 'Échange pour jbn  - 30 min - fcqcsd (Purchase #38)', '', NULL, NULL, NULL, '2025-10-21 12:34:04'),
(648, 10, -2, 'Échange pour jbn  - 30 min - fcqcsd (Purchase #39)', '', NULL, NULL, NULL, '2025-10-21 12:35:19'),
(649, 10, 50, 'Points bonus pour avoir joué à fcqcsd (Purchase #39)', 'bonus', NULL, NULL, NULL, '2025-10-21 12:35:19'),
(650, 8, -2, 'Échange de points pour jbn  - 30 min - fcqcsd (Purchase #40)', '', NULL, NULL, NULL, '2025-10-21 12:36:07'),
(651, 8, 0, 'Achat: fcqcsd - zefds', 'game', NULL, NULL, NULL, '2025-10-21 13:56:11'),
(652, 8, -2, 'Échange récompense: jbn ', 'reward', NULL, NULL, NULL, '2025-10-22 16:33:16'),
(653, 8, -2, 'Échange récompense: jbn ', 'reward', NULL, NULL, NULL, '2025-10-22 16:33:22'),
(654, 8, -2, 'Échange récompense: fcqcsd', 'reward', NULL, NULL, NULL, '2025-10-22 16:49:11'),
(655, 8, 50, 'Achat confirmé: fcqcsd', '', NULL, NULL, 9, '2025-10-22 16:59:30'),
(656, 8, 25, 'Bonus journalier', 'bonus', NULL, NULL, NULL, '2025-10-22 17:44:48'),
(657, 8, 0, 'Achat: fcqcsd - zefds', 'game', NULL, NULL, NULL, '2025-10-22 18:27:22'),
(658, 8, 0, 'Achat: fcqcsd - zefds', 'game', NULL, NULL, NULL, '2025-10-22 18:28:51'),
(659, 8, 0, 'Achat: fcqcsd - zefds', 'game', NULL, NULL, NULL, '2025-10-22 18:56:37'),
(660, 8, -49, 'Échange récompense: fifa', 'reward', NULL, NULL, NULL, '2025-10-22 19:21:43'),
(661, 8, 5, 'Connexion quotidienne (série de 2)', 'bonus', NULL, NULL, NULL, '2025-10-22 19:22:51'),
(662, 8, -2, 'Échange récompense: jbn ', 'reward', NULL, NULL, NULL, '2025-10-23 11:51:48'),
(663, 8, -2, 'Échange récompense: jbn ', 'reward', NULL, NULL, NULL, '2025-10-23 11:51:54'),
(664, 8, 10, 'Connexion quotidienne (série de 3) +5 bonus', 'bonus', NULL, NULL, NULL, '2025-10-23 14:26:07');

-- --------------------------------------------------------

--
-- Structure de la table `point_conversions`
--

CREATE TABLE `point_conversions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `points_spent` int(11) NOT NULL COMMENT 'Points d??pens??s',
  `minutes_gained` int(11) NOT NULL COMMENT 'Minutes de jeu gagn??es',
  `game_id` int(11) DEFAULT NULL COMMENT 'Jeu choisi (NULL = tous les jeux)',
  `conversion_rate` int(11) NOT NULL COMMENT 'Rate au moment: X points = 1 minute',
  `fee_charged` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Frais appliqu??s',
  `status` enum('pending','active','used','expired','cancelled') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL COMMENT 'Date d''expiration du temps converti',
  `used_at` datetime DEFAULT NULL COMMENT 'Date d''utilisation',
  `minutes_used` int(11) NOT NULL DEFAULT 0 COMMENT 'Minutes d??j?? utilis??es',
  `minutes_remaining` int(11) GENERATED ALWAYS AS (`minutes_gained` - `minutes_used`) VIRTUAL,
  `purchase_id` int(11) DEFAULT NULL COMMENT 'Achat cr???? avec ce temps converti',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Historique conversions points ??? temps';

--
-- Déchargement des données de la table `point_conversions`
--

INSERT INTO `point_conversions` (`id`, `user_id`, `points_spent`, `minutes_gained`, `game_id`, `conversion_rate`, `fee_charged`, `status`, `created_at`, `expires_at`, `used_at`, `minutes_used`, `purchase_id`, `notes`) VALUES
(1, 25, 10, 5, NULL, 0, 0.00, 'active', '2025-10-20 16:52:38', '2025-11-19 16:52:38', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `point_conversion_config`
--

CREATE TABLE `point_conversion_config` (
  `id` int(11) NOT NULL DEFAULT 1,
  `points_per_minute` int(11) NOT NULL DEFAULT 10 COMMENT 'Ex: 10 points = 1 minute',
  `min_conversion_points` int(11) NOT NULL DEFAULT 100 COMMENT 'Minimum 100 points pour convertir',
  `max_conversion_per_day` int(11) DEFAULT 3 COMMENT 'Max 3 conversions par jour (NULL = illimit??)',
  `conversion_fee_percent` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Frais en % (ex: 5.00 = 5%)',
  `min_minutes_per_conversion` int(11) NOT NULL DEFAULT 10 COMMENT 'Minimum 10 minutes par conversion',
  `max_minutes_per_conversion` int(11) DEFAULT 300 COMMENT 'Maximum minutes par conversion (NULL = illimit??)',
  `converted_time_expiry_days` int(11) NOT NULL DEFAULT 30 COMMENT 'Le temps converti expire dans X jours',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL COMMENT 'Notes pour l''admin sur les r??gles',
  `updated_at` datetime NOT NULL,
  `updated_by` int(11) DEFAULT NULL COMMENT 'Admin qui a modifi?? la config'
) ;

--
-- Déchargement des données de la table `point_conversion_config`
--

INSERT INTO `point_conversion_config` (`id`, `points_per_minute`, `min_conversion_points`, `max_conversion_per_day`, `conversion_fee_percent`, `min_minutes_per_conversion`, `max_minutes_per_conversion`, `converted_time_expiry_days`, `is_active`, `notes`, `updated_at`, `updated_by`) VALUES
(1, 10, 100, 3, 0.00, 10, 300, 30, 1, 'Configuration par d??faut: 10 points = 1 minute, max 3 conversions/jour', '2025-10-18 15:39:05', NULL);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `point_packages`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `point_packages` (
`id` int(11)
,`game_id` int(11)
,`game_name` varchar(200)
,`game_slug` varchar(200)
,`game_image` varchar(500)
,`package_name` varchar(150)
,`duration_minutes` int(11)
,`points_cost` int(11)
,`points_earned` int(11)
,`bonus_multiplier` decimal(3,2)
,`is_promotional` tinyint(1)
,`promotional_label` varchar(100)
,`max_purchases_per_user` int(11)
,`available_from` datetime
,`available_until` datetime
,`is_active` tinyint(1)
,`display_order` int(11)
,`reward_id` int(11)
,`reward_name` varchar(150)
,`reward_description` text
,`reward_image` varchar(500)
,`reward_category` varchar(100)
,`reward_featured` tinyint(1)
,`total_redemptions` bigint(21)
,`unique_users` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure de la table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL COMMENT 'ID de la transaction sécurisée',
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `game_name` varchar(200) NOT NULL,
  `package_name` varchar(150) DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'XOF',
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `points_credited` tinyint(1) NOT NULL DEFAULT 0,
  `paid_with_points` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Achet├® avec des points au lieu d"argent',
  `points_spent` int(11) NOT NULL DEFAULT 0 COMMENT 'Points d├®pens├®s pour cet achat',
  `payment_method_id` int(11) DEFAULT NULL,
  `payment_method_name` varchar(100) DEFAULT NULL,
  `payment_status` varchar(50) NOT NULL DEFAULT 'pending',
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_details` longtext DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `session_status` varchar(50) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `purchases`
--

INSERT INTO `purchases` (`id`, `transaction_id`, `user_id`, `game_id`, `package_id`, `game_name`, `package_name`, `duration_minutes`, `price`, `currency`, `points_earned`, `points_credited`, `paid_with_points`, `points_spent`, `payment_method_id`, `payment_method_name`, `payment_status`, `payment_reference`, `payment_details`, `confirmed_by`, `confirmed_at`, `session_status`, `notes`, `created_at`, `updated_at`) VALUES
(3, NULL, 1, 1, 1, 'fifa', '1h', 10, 15.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-17 14:12:54', 'terminated', NULL, '2025-10-17 14:05:28', '2025-10-18 14:52:30'),
(4, NULL, 8, 1, 1, 'fifa', '1h', 10, 15.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-17 14:12:47', 'completed', NULL, '2025-10-17 14:11:49', '2025-10-18 13:37:43'),
(5, NULL, 8, 1, 1, 'fifa', '1h', 10, 15.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-17 14:53:31', 'completed', NULL, '2025-10-17 14:38:02', '2025-10-18 13:37:43'),
(6, NULL, 1, 3, NULL, 'Test Game', 'Package 5min', 5, 500.00, 'XAF', 0, 0, 0, 0, 1, NULL, 'completed', NULL, NULL, NULL, '2025-10-17 15:26:53', 'terminated', NULL, '2025-10-17 15:26:53', '2025-10-18 13:37:43'),
(7, NULL, 8, 3, 2, 'ufvvhjk', 'dtgfhjgb', 60, 5000.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-17 16:21:04', 'completed', NULL, '2025-10-17 16:20:41', '2025-10-17 19:07:46'),
(8, NULL, 8, 3, 2, 'ufvvhjk', 'dtgfhjgb', 60, 5000.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-17 17:44:21', 'terminated', NULL, '2025-10-17 17:38:58', '2025-10-18 15:02:28'),
(9, NULL, 8, 4, 3, 'naruto', 'cc', 60, 150.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-17 17:48:15', 'completed', NULL, '2025-10-17 17:47:44', '2025-10-17 19:07:46'),
(10, NULL, 8, 5, 5, '1min de jeu', 'ppp', 1, 500.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-17 18:10:00', 'completed', NULL, '2025-10-17 18:09:47', '2025-10-17 19:07:46'),
(11, NULL, 8, 5, 5, '1min de jeu', 'ppp', 1, 500.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-17 18:38:38', 'completed', NULL, '2025-10-17 18:38:19', '2025-10-17 19:07:46'),
(12, NULL, 8, 5, 5, '1min de jeu', 'ppp', 1, 500.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-17 22:11:52', 'completed', NULL, '2025-10-17 21:37:21', '2025-10-18 14:00:49'),
(13, NULL, 8, 1, 6, 'fifa', 'nnnn', 60, 500.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-17 22:11:58', 'completed', NULL, '2025-10-17 22:11:28', '2025-10-18 14:00:49'),
(14, NULL, 8, 1, 4, 'fifa', '1min', 1, 50.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-18 00:23:47', 'completed', NULL, '2025-10-17 23:50:40', '2025-10-18 14:00:49'),
(15, NULL, 8, 5, 5, '1min de jeu', 'ppp', 1, 500.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 1, '2025-10-18 14:24:37', 'terminated', NULL, '2025-10-18 14:15:22', '2025-10-18 14:52:30'),
(16, NULL, 8, 5, 5, '1min de jeu', 'ppp', 1, 500.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-18 14:35:13', 'terminated', NULL, '2025-10-18 14:31:39', '2025-10-18 16:56:21'),
(17, NULL, 8, 5, 5, '1min de jeu', 'ppp', 1, 500.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-18 14:35:10', 'terminated', NULL, '2025-10-18 14:31:55', '2025-10-18 14:52:29'),
(18, NULL, 8, 5, 5, '1min de jeu', 'ppp', 1, 500.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-18 15:06:05', 'terminated', NULL, '2025-10-18 15:05:53', '2025-10-18 15:07:38'),
(19, NULL, 8, 4, 3, 'naruto', 'cc', 60, 150.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-18 15:48:28', 'terminated', NULL, '2025-10-18 15:47:56', '2025-10-18 16:54:25'),
(20, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-18 16:31:21', 'terminated', NULL, '2025-10-18 16:30:26', '2025-10-18 16:33:02'),
(21, NULL, 31, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-22 18:28:13', 'pending', NULL, '2025-10-19 17:43:20', '2025-10-22 18:28:13'),
(22, NULL, 31, 9, 7, 'fcqcsd', 'zefds', 1, 1000.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-22 18:28:20', 'pending', NULL, '2025-10-19 17:44:38', '2025-10-22 18:28:20'),
(23, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 1000.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-22 18:28:16', 'pending', NULL, '2025-10-20 14:45:27', '2025-10-22 18:28:16'),
(24, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-22 18:28:08', 'pending', NULL, '2025-10-20 15:07:55', '2025-10-22 18:28:08'),
(25, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-22 18:28:05', 'pending', NULL, '2025-10-20 15:17:48', '2025-10-22 18:28:05'),
(26, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-22 18:28:00', 'pending', NULL, '2025-10-20 15:29:43', '2025-10-22 18:28:00'),
(27, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 1, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-21 13:56:00', 'terminated', NULL, '2025-10-20 15:36:21', '2025-10-21 13:57:35'),
(37, NULL, 10, 9, 12, 'fcqcsd', 'jbn  - 30 min', 30, 0.00, 'POI', 50, 0, 1, 2, NULL, 'Points Fidélité', 'completed', NULL, NULL, NULL, NULL, 'pending', NULL, '2025-10-21 12:32:49', '2025-10-21 12:32:49'),
(38, NULL, 10, 9, 12, 'fcqcsd', 'jbn  - 30 min', 30, 0.00, 'POI', 50, 0, 1, 2, NULL, 'Points Fidélité', 'completed', NULL, NULL, NULL, NULL, 'active', NULL, '2025-10-21 12:34:04', '2025-10-21 12:34:04'),
(39, NULL, 10, 9, 12, 'fcqcsd', 'jbn  - 30 min', 30, 0.00, 'POI', 50, 1, 1, 2, NULL, 'Points Fidélité', 'completed', NULL, NULL, NULL, NULL, 'completed', NULL, '2025-10-21 12:35:19', '2025-10-21 12:35:19'),
(40, NULL, 8, 9, 12, 'fcqcsd', 'jbn  - 30 min', 30, 0.00, 'POI', 50, 0, 1, 2, NULL, 'Points Fidélité', 'completed', NULL, NULL, NULL, NULL, 'terminated', NULL, '2025-10-21 12:36:07', '2025-10-21 14:34:57'),
(41, NULL, 8, 9, 12, 'fcqcsd', 'jbn  - 30 min', 30, 0.00, 'XOF', 50, 1, 0, 0, 1, '^pmlkj', 'completed', NULL, NULL, 9, '2025-10-22 16:59:30', 'pending', NULL, '2025-10-21 22:05:21', '2025-10-22 16:59:30'),
(42, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-22 16:59:07', 'pending', NULL, '2025-10-21 22:05:39', '2025-10-22 16:59:07'),
(43, 7, 8, 9, NULL, 'fcqcsd', 'jbn  - 30 min', 30, 0.00, 'XOF', 50, 0, 1, 0, NULL, 'points', 'completed', NULL, NULL, NULL, NULL, 'terminated', NULL, '2025-10-22 16:49:11', '2025-10-23 00:12:42'),
(44, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 1000.00, 'XOF', 0, 1, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-22 18:23:08', 'cancelled', NULL, '2025-10-22 17:45:38', '2025-10-22 19:15:26'),
(45, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 1000.00, 'XOF', 0, 1, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-22 18:25:22', 'pending', NULL, '2025-10-22 18:24:44', '2025-10-22 19:15:21'),
(46, NULL, 25, 9, 12, 'fcqcsd', 'jbn  - 30 min', 30, 0.00, 'POI', 50, 0, 1, 2, NULL, 'Points Fidélité', 'completed', NULL, NULL, NULL, NULL, 'terminated', NULL, '2025-10-22 18:42:55', '2025-10-23 00:12:47'),
(47, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 1, 0, 0, 2, 'Sur place', 'completed', NULL, NULL, 9, '2025-10-22 18:56:32', 'terminated', NULL, '2025-10-22 18:56:22', '2025-10-22 19:18:55'),
(48, 8, 8, 1, NULL, 'fifa', 'Récompense FIFA - 30 min', 30, 0.00, 'XOF', 5, 0, 1, 0, NULL, 'points', 'completed', NULL, NULL, NULL, NULL, 'terminated', NULL, '2025-10-22 19:21:43', '2025-10-23 00:12:40'),
(49, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 1, '^pmlkj', 'pending', NULL, NULL, NULL, NULL, 'pending', NULL, '2025-10-23 13:36:37', '2025-10-23 13:36:37'),
(50, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 3, 'Mobile Money (KkiaPay)', 'processing', NULL, NULL, NULL, NULL, 'pending', NULL, '2025-10-23 13:37:58', '2025-10-23 13:37:58'),
(51, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 3, 'Mobile Money (KkiaPay)', 'processing', NULL, NULL, NULL, NULL, 'pending', NULL, '2025-10-23 13:44:13', '2025-10-23 13:44:13'),
(52, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 3, 'Mobile Money (KkiaPay)', 'processing', NULL, NULL, NULL, NULL, 'pending', NULL, '2025-10-23 13:46:58', '2025-10-23 13:46:58'),
(53, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 4, 'MTN Mobile Money', 'processing', NULL, NULL, NULL, NULL, 'pending', NULL, '2025-10-23 13:50:32', '2025-10-23 13:50:32'),
(54, NULL, 8, 9, 7, 'fcqcsd', 'zefds', 1, 500.00, 'XOF', 0, 0, 0, 0, 4, 'MTN Mobile Money', 'processing', NULL, NULL, NULL, NULL, 'pending', NULL, '2025-10-23 13:57:01', '2025-10-23 13:57:01');

--
-- Déclencheurs `purchases`
--
DELIMITER $$
CREATE TRIGGER `after_purchase_completed` AFTER UPDATE ON `purchases` FOR EACH ROW BEGIN
  IF NEW.payment_status = 'completed' AND OLD.payment_status != 'completed' THEN
    IF NOT EXISTS (SELECT 1 FROM invoices WHERE purchase_id = NEW.id) THEN
      SET @invoice_num = CONCAT('INV-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(NEW.id, 5, '0'));
      SET @validation_code = UPPER(CONCAT(
        SUBSTRING(MD5(CONCAT(NEW.id, UNIX_TIMESTAMP(), RAND())), 1, 8),
        SUBSTRING(MD5(CONCAT(NEW.user_id, RAND())), 1, 8)
      ));
      SET @qr_data = JSON_OBJECT(
        'invoice_id', NEW.id,
        'code', @validation_code,
        'user_id', NEW.user_id,
        'amount', NEW.price,
        'duration', NEW.duration_minutes,
        'issued', NOW()
      );
      SET @qr_hash = SHA2(CONCAT(@qr_data, @validation_code, 'GAMEZONE_SECRET_2025'), 256);
      
      INSERT INTO invoices (
        purchase_id, user_id, invoice_number, validation_code,
        qr_code_data, qr_code_hash,
        amount, currency, duration_minutes,
        game_name, package_name,
        status, issued_at, expires_at,
        created_at, updated_at
      ) VALUES (
        NEW.id, NEW.user_id, @invoice_num, @validation_code,
        @qr_data, @qr_hash,
        NEW.price, NEW.currency, NEW.duration_minutes,
        NEW.game_name, NEW.package_name,
        'pending', NOW(), DATE_ADD(NOW(), INTERVAL 2 MONTH),
        NOW(), NOW()
      );
      
      INSERT INTO invoice_audit_log (
        invoice_id, action, performed_by, performed_by_type,
        action_details, created_at
      ) VALUES (
        LAST_INSERT_ID(), 'created', NEW.user_id, 'system',
        CONCAT('Facture cr├®├®e automatiquement pour achat #', NEW.id),
        NOW()
      );
    END IF;
    
    
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `purchase_session_overview`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `purchase_session_overview` (
`purchase_id` int(11)
,`user_id` int(11)
,`game_id` int(11)
,`game_name` varchar(200)
,`package_name` varchar(150)
,`price` decimal(10,2)
,`currency` varchar(3)
,`duration_minutes` int(11)
,`payment_status` varchar(50)
,`purchase_session_status` varchar(50)
,`purchase_created_at` datetime
,`session_id` int(11)
,`actual_session_status` enum('ready','active','paused','completed','expired','terminated')
,`total_minutes` int(11)
,`used_minutes` int(11)
,`remaining_minutes` int(11)
,`started_at` datetime
,`completed_at` datetime
,`invoice_number` varchar(50)
,`validation_code` varchar(32)
,`invoice_status` enum('pending','active','used','expired','cancelled','refunded')
,`username` varchar(100)
,`email` varchar(191)
,`sync_status` varchar(10)
);

-- --------------------------------------------------------

--
-- Structure de la table `purchase_transactions`
--

CREATE TABLE `purchase_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) DEFAULT NULL,
  `purchase_id` int(11) DEFAULT NULL COMMENT 'ID de l''achat créé (si succès)',
  `points_tx_id` int(11) DEFAULT NULL COMMENT 'ID de la transaction de points',
  `idempotency_key` varchar(255) NOT NULL COMMENT 'Clé unique pour éviter doublons',
  `status` enum('pending','processing','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `step` varchar(50) DEFAULT NULL COMMENT 'Étape actuelle du processus',
  `points_amount` int(11) DEFAULT NULL COMMENT 'Montant en points',
  `money_amount` decimal(10,2) DEFAULT NULL COMMENT 'Montant en argent',
  `currency` varchar(10) DEFAULT NULL,
  `failure_reason` text DEFAULT NULL COMMENT 'Raison de l''échec si failed',
  `refund_reason` text DEFAULT NULL COMMENT 'Raison du remboursement',
  `refunded_by` int(11) DEFAULT NULL COMMENT 'Admin qui a effectué le remboursement',
  `created_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Transactions sécurisées avec rollback';

--
-- Déchargement des données de la table `purchase_transactions`
--

INSERT INTO `purchase_transactions` (`id`, `user_id`, `reward_id`, `purchase_id`, `points_tx_id`, `idempotency_key`, `status`, `step`, `points_amount`, `money_amount`, `currency`, `failure_reason`, `refund_reason`, `refunded_by`, `created_at`, `completed_at`, `failed_at`, `refunded_at`) VALUES
(7, 8, 18, 43, 654, 'reward-12-1761144551234-f8zlgx5tm', 'completed', 'finished', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-22 16:49:11', '2025-10-22 16:49:11', NULL, NULL),
(8, 8, 12, 48, 660, 'reward-8-1761153701355-knoib1f6h', 'completed', 'finished', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-22 19:21:43', '2025-10-22 19:21:43', NULL, NULL);

--
-- Déclencheurs `purchase_transactions`
--
DELIMITER $$
CREATE TRIGGER `prevent_completed_tx_modification` BEFORE UPDATE ON `purchase_transactions` FOR EACH ROW BEGIN
IF OLD.status = 'completed' AND NEW.status != 'refunded' AND NEW.status != 'completed' THEN
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Impossible de modifier une transaction complétée';
END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `reward_type` enum('game_time','discount','item','badge','other','physical','digital','game_package') DEFAULT 'other',
  `game_package_id` int(11) DEFAULT NULL COMMENT 'ID du package de jeu associ├®',
  `game_time_minutes` int(11) DEFAULT 0,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `stock_quantity` int(11) DEFAULT NULL,
  `max_per_user` int(11) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `rewards`
--

INSERT INTO `rewards` (`id`, `name`, `description`, `cost`, `category`, `reward_type`, `game_package_id`, `game_time_minutes`, `available`, `stock_quantity`, `max_per_user`, `is_featured`, `display_order`, `image_url`, `created_at`, `updated_at`) VALUES
(11, 'aaaaa', NULL, 500, NULL, 'other', NULL, 0, 1, NULL, NULL, 0, 0, NULL, '2025-10-20 23:47:34', '2025-10-20 23:47:34'),
(12, 'FIFA 2024 - 30 minutes', 'Profitez de 30 minutes de jeu sur FIFA 2024 en ├®changeant vos points de fid├®lit├®. Gagnez 5 points bonus en jouant !', 49, 'gaming', 'game_package', 8, 0, 1, NULL, NULL, 1, 0, NULL, '2025-10-21 00:29:33', '2025-10-21 00:35:00'),
(13, 'Action Game - 1 heure', 'Une heure d\'action intense sur COD Modern Warfare 3. ├ëchangez vos points et gagnez-en 10 de plus en jouant !', 100, 'gaming', 'game_package', 9, 0, 1, NULL, NULL, 1, 0, NULL, '2025-10-21 00:29:33', '2025-10-21 00:29:33'),
(14, 'Naruto - 30 minutes', 'Jouez 30 minutes à Naruto avec vos points. +15 points bonus en jouant!', 150, 'gaming', 'game_package', 10, 0, 1, NULL, NULL, 1, 0, NULL, '2025-10-21 00:29:33', '2025-10-21 00:29:33'),
(15, 'dbz', NULL, 10, NULL, 'other', NULL, 0, 1, NULL, NULL, 0, 0, NULL, '2025-10-21 00:35:40', '2025-10-21 00:35:40'),
(16, 'TEST NOUVELLE Récompense - 45 minutes', 'Ceci est une NOUVELLE récompense créée pour tester. Échangez 200 points contre 45 minutes de jeu!', 200, 'gaming', 'game_package', 11, 0, 1, NULL, NULL, 1, 1, NULL, '2025-10-21 00:39:26', '2025-10-21 00:39:26'),
(17, 'dsd', NULL, 500, NULL, 'other', NULL, 0, 1, NULL, NULL, 0, 0, NULL, '2025-10-21 11:47:10', '2025-10-21 11:47:10'),
(18, 'jbn ', 'bn,', 2, 'r', 'game_package', 12, 0, 1, NULL, 5000, 1, 0, NULL, '2025-10-21 12:09:58', '2025-10-21 12:09:58');

-- --------------------------------------------------------

--
-- Structure de la table `reward_redemptions`
--

CREATE TABLE `reward_redemptions` (
  `id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cost` int(11) NOT NULL,
  `status` enum('pending','approved','delivered','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reward_redemptions`
--

INSERT INTO `reward_redemptions` (`id`, `reward_id`, `user_id`, `cost`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(7, 18, 8, 2, '', NULL, '2025-10-21 12:36:07', NULL),
(8, 18, 8, 2, 'pending', NULL, '2025-10-22 16:33:16', NULL),
(9, 18, 8, 2, 'pending', NULL, '2025-10-22 16:33:22', NULL),
(10, 18, 8, 2, 'pending', NULL, '2025-10-23 11:51:48', NULL),
(11, 18, 8, 2, 'pending', NULL, '2025-10-23 11:51:54', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `session_activities`
--

CREATE TABLE `session_activities` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `activity_type` enum('start','pause','resume','complete','expire','cancel','time_update') NOT NULL,
  `minutes_used` int(11) NOT NULL DEFAULT 0,
  `description` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `session_events`
--

CREATE TABLE `session_events` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `event_type` enum('ready','start','pause','resume','complete','expire','terminate','countdown_update','heartbeat','warning_low_time','admin_action') NOT NULL,
  `event_message` text DEFAULT NULL,
  `minutes_delta` int(11) DEFAULT NULL,
  `minutes_before` int(11) DEFAULT NULL,
  `minutes_after` int(11) DEFAULT NULL,
  `triggered_by` int(11) DEFAULT NULL,
  `triggered_by_system` tinyint(1) NOT NULL DEFAULT 0,
  `event_data` longtext DEFAULT NULL CHECK (json_valid(`event_data`)),
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `session_events`
--

INSERT INTO `session_events` (`id`, `session_id`, `event_type`, `event_message`, `minutes_delta`, `minutes_before`, `minutes_after`, `triggered_by`, `triggered_by_system`, `event_data`, `created_at`) VALUES
(1, 1, 'ready', 'Session créée et prête à démarrer', NULL, NULL, 10, 9, 0, NULL, '2025-10-17 15:14:03'),
(2, 1, 'start', 'Session démarrée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 15:17:12'),
(3, 2, 'ready', 'Session créée et prête à démarrer', NULL, NULL, 10, 9, 0, NULL, '2025-10-17 15:18:07'),
(4, 2, 'start', 'Session démarrée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 15:18:11'),
(5, 4, 'ready', 'Session créée et prête à démarrer', NULL, NULL, 5, 1, 0, NULL, '2025-10-17 15:26:54'),
(6, 4, 'start', 'Session démarrée par admin', NULL, NULL, NULL, 1, 0, NULL, '2025-10-17 15:26:54'),
(7, 4, 'countdown_update', NULL, -1, 5, 4, NULL, 1, NULL, '2025-10-17 15:52:11'),
(8, 4, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 16:01:18'),
(9, 5, 'ready', 'Session créée et prête à démarrer', NULL, NULL, 60, 9, 0, NULL, '2025-10-17 16:21:39'),
(10, 5, 'start', 'Session démarrée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 16:21:44'),
(11, 6, 'ready', 'Session créée et prête à démarrer', NULL, NULL, 60, 9, 0, NULL, '2025-10-17 17:49:01'),
(12, 6, 'start', 'Session démarrée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 17:49:05'),
(13, 7, 'ready', 'Session créée et prête à démarrer', NULL, NULL, 1, 9, 0, NULL, '2025-10-17 18:11:28'),
(14, 7, 'start', 'Session démarrée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 18:11:33'),
(15, 8, 'ready', 'Session créée et prête à démarrer', NULL, NULL, 1, 9, 0, NULL, '2025-10-17 18:39:15'),
(16, 8, 'start', 'Session démarrée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 18:39:21'),
(17, 8, 'complete', 'Session terminée automatiquement (temps écoulé)', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 19:07:46'),
(18, 7, 'complete', 'Session terminée automatiquement (temps écoulé)', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 19:07:46'),
(19, 6, 'complete', 'Session terminée automatiquement (temps écoulé)', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 19:07:46'),
(20, 5, 'complete', 'Session terminée automatiquement (temps écoulé)', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 19:07:46'),
(21, 9, 'ready', 'Session créée et prête à démarrer', NULL, NULL, 60, 9, 0, NULL, '2025-10-17 22:13:50'),
(22, 9, 'start', 'Session démarrée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-17 22:13:55'),
(23, 10, 'ready', 'Session créée et prête à démarrer', NULL, NULL, 1, 9, 0, NULL, '2025-10-18 12:32:31'),
(24, 10, 'start', 'Session démarrée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 12:32:40'),
(25, 11, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 13:56:31'),
(26, 11, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 13:56:31'),
(27, 12, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 13:57:39'),
(28, 12, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 13:57:39'),
(29, 9, 'complete', 'Session termin├®e - temps ├®coul├®', NULL, NULL, 0, NULL, 1, NULL, '2025-10-18 14:00:49'),
(30, 10, 'complete', 'Session termin├®e - temps ├®coul├®', NULL, NULL, 0, NULL, 1, NULL, '2025-10-18 14:00:49'),
(31, 11, 'complete', 'Session termin├®e - temps ├®coul├®', NULL, NULL, 0, NULL, 1, NULL, '2025-10-18 14:00:49'),
(32, 12, 'countdown_update', 'D├®compte: +3 min', 3, NULL, 57, NULL, 1, NULL, '2025-10-18 14:00:49'),
(33, 13, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 1, 0, NULL, '2025-10-18 14:02:26'),
(34, 13, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 1, 0, NULL, '2025-10-18 14:02:36'),
(35, 12, 'countdown_update', 'D├®compte: +7 min', 7, NULL, 50, NULL, 1, NULL, '2025-10-18 14:08:29'),
(36, 13, 'countdown_update', 'D├®compte: +5 min', 5, NULL, 5, NULL, 1, NULL, '2025-10-18 14:08:29'),
(37, 13, 'warning_low_time', 'Attention: 5 min restantes', NULL, NULL, 5, NULL, 1, NULL, '2025-10-18 14:08:29'),
(38, 14, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 14:26:40'),
(39, 14, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 14:26:40'),
(40, 15, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 14:35:39'),
(41, 15, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 14:35:39'),
(42, 15, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 14:52:29'),
(43, 14, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 14:52:30'),
(44, 13, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 14:52:30'),
(45, 12, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 15:02:28'),
(46, 16, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 15:06:21'),
(47, 16, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 15:06:21'),
(48, 16, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 15:07:38'),
(49, 17, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 15:49:02'),
(50, 17, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 15:49:03'),
(51, 18, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 16:31:49'),
(52, 18, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 16:31:49'),
(53, 18, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 16:33:02'),
(54, 17, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 16:54:25'),
(55, 19, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 16:54:58'),
(56, 19, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 16:54:58'),
(57, 19, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-18 16:56:21'),
(58, 20, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-21 13:56:22'),
(59, 20, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-21 13:56:22'),
(60, 20, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-21 13:57:35'),
(61, 21, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-21 13:59:52'),
(62, 21, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-21 13:59:52'),
(63, 21, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-21 14:34:57'),
(64, 22, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-22 18:55:19'),
(65, 22, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-22 18:55:19'),
(66, 23, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-22 19:09:00'),
(67, 23, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-22 19:09:00'),
(68, 24, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-22 19:17:46'),
(69, 24, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-22 19:17:46'),
(70, 23, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-22 19:18:55'),
(71, 25, 'ready', 'Session pr├¬te - facture activ├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-22 19:22:00'),
(72, 25, 'start', 'Session d├®marr├®e', NULL, NULL, NULL, 9, 0, NULL, '2025-10-22 19:22:00'),
(73, 25, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-23 00:12:40'),
(74, 24, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-23 00:12:42'),
(75, 22, 'terminate', 'Session terminée par admin', NULL, NULL, NULL, 9, 0, NULL, '2025-10-23 00:12:47');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `session_summary`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `session_summary` (
`id` int(11)
,`invoice_id` int(11)
,`purchase_id` int(11)
,`user_id` int(11)
,`game_id` int(11)
,`total_minutes` int(11)
,`used_minutes` int(11)
,`remaining_minutes` bigint(12)
,`progress_percent` decimal(15,1)
,`status` enum('ready','active','paused','completed','expired','terminated')
,`ready_at` datetime
,`started_at` datetime
,`paused_at` datetime
,`resumed_at` datetime
,`completed_at` datetime
,`last_heartbeat` datetime
,`last_countdown_update` datetime
,`pause_count` int(11)
,`total_pause_time` int(11)
,`auto_countdown` tinyint(1)
,`countdown_interval` int(11)
,`monitored_by` int(11)
,`created_at` datetime
,`updated_at` datetime
,`username` varchar(100)
,`email` varchar(191)
,`game_name` varchar(200)
,`invoice_number` varchar(50)
,`validation_code` varchar(32)
,`invoice_status` enum('pending','active','used','expired','cancelled','refunded')
);

-- --------------------------------------------------------

--
-- Structure de la table `tournaments`
--

CREATE TABLE `tournaments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `game_id` int(11) DEFAULT NULL,
  `type` enum('single_elimination','double_elimination','round_robin','swiss','free_for_all') DEFAULT 'single_elimination',
  `max_participants` int(11) NOT NULL,
  `entry_fee` int(11) DEFAULT 0 COMMENT 'Co??t en points pour participer',
  `prize_pool` int(11) DEFAULT 0 COMMENT 'Cagnotte totale',
  `first_place_prize` int(11) DEFAULT 0,
  `second_place_prize` int(11) DEFAULT 0,
  `third_place_prize` int(11) DEFAULT 0,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `registration_deadline` datetime DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `stream_url` varchar(500) DEFAULT NULL,
  `status` enum('upcoming','registration_open','registration_closed','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `is_featured` tinyint(1) DEFAULT 0,
  `winner_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tournament_matches`
--

CREATE TABLE `tournament_matches` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `round` int(11) NOT NULL,
  `match_number` int(11) NOT NULL,
  `player1_id` int(11) DEFAULT NULL,
  `player2_id` int(11) DEFAULT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `player1_score` int(11) DEFAULT 0,
  `player2_score` int(11) DEFAULT 0,
  `status` enum('pending','ongoing','completed','forfeit') DEFAULT 'pending',
  `scheduled_time` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tournament_participants`
--

CREATE TABLE `tournament_participants` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team_name` varchar(255) DEFAULT NULL,
  `status` enum('registered','confirmed','checked_in','disqualified','withdrawn') DEFAULT 'registered',
  `placement` int(11) DEFAULT NULL COMMENT 'Position finale dans le tournoi',
  `points_earned` int(11) DEFAULT 0,
  `prize_won` int(11) DEFAULT 0,
  `registered_at` datetime NOT NULL,
  `checked_in_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `transaction_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `transaction_stats` (
`status` enum('pending','processing','completed','failed','refunded')
,`count` bigint(21)
,`total_points` decimal(32,0)
,`total_money` decimal(32,2)
,`first_transaction` datetime
,`last_transaction` datetime
);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('player','admin') NOT NULL DEFAULT 'player',
  `avatar_url` varchar(500) DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `level` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `deactivation_reason` text DEFAULT NULL,
  `deactivation_date` datetime DEFAULT NULL,
  `deactivated_by` int(11) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `last_active` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `avatar_url`, `points`, `level`, `status`, `deactivation_reason`, `deactivation_date`, `deactivated_by`, `join_date`, `last_active`, `created_at`, `updated_at`) VALUES
(1, 'testuser', 'test@test.com', '$2y$10$JzKdrsaRfKRORR3O/9a2/.cW5rdRul7ui/WQobvDOazi.2iG3R4ni', 'player', NULL, 0, NULL, 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-17 14:03:17', '2025-10-17 14:03:17'),
(2, 'QuickTest', 'quicktest6494@test.local', '$2y$10$TyBBXLgk3n1o8e8xazpdduCYrQynhHCb7aspab4osVdS/B/OnRJbW', 'player', NULL, 0, 'Novice', 'active', NULL, NULL, NULL, '2025-10-07', '2025-10-07 14:14:53', '2025-10-07 14:14:53', '2025-10-07 14:14:53'),
(3, 'CurlTest', 'curltest@test.com', '$2y$10$Lvo0mApdv3pAQLHUITo.v.LSApD7qY9ScoFcEhArxIa52v.MaPYXu', 'player', NULL, 0, 'Novice', 'active', NULL, NULL, NULL, '2025-10-07', '2025-10-07 14:19:28', '2025-10-07 14:19:28', '2025-10-07 14:19:28'),
(4, 'TestCORS', 'cors@test.com', '$2y$10$KTgLr7Ez76ci2kdQh1BmGegtpb1pkAFCwn33BqC9uKrFysTnOBNBy', 'player', NULL, 0, 'Novice', 'active', NULL, NULL, NULL, '2025-10-14', '2025-10-14 18:35:59', '2025-10-14 18:35:59', '2025-10-14 18:35:59'),
(5, 'TestFinal', 'final@test.com', '$2y$10$R7Jsx6hb7yC4QWsMJqeMXu4uLJarKwoQ6pUp78kR063L8IeO4.a9K', 'player', NULL, 0, 'Novice', 'active', NULL, NULL, NULL, '2025-10-14', '2025-10-14 18:45:09', '2025-10-14 18:44:57', '2025-10-14 18:45:09'),
(6, 'Final', 'final2@test.com', '$2y$10$27Cq3jhK0xAeDMBhhCq1BOAJIALqvJr/MTRRWyyJ/vfUpxWBAcWli', 'player', NULL, 0, 'Novice', 'active', NULL, NULL, NULL, '2025-10-14', '2025-10-14 19:00:27', '2025-10-14 19:00:27', '2025-10-14 19:00:27'),
(7, 'FinalTest', 'final1760461314266@test.com', '$2y$10$VD2n5EvtKJhNOHyM5zt5rewA8kxI2mk4MPVTEinL0kTax90f6ze8q', 'player', NULL, 0, 'Novice', 'active', NULL, NULL, NULL, '2025-10-14', '2025-10-14 19:01:55', '2025-10-14 19:01:55', '2025-10-14 19:01:55'),
(8, 'jada', 'saccajeho@gmail.com', '$2y$10$uH5kNWGAqIWFw/knJp5x2uCvmv/GblEWD0iiDXl9DfNL2Zu9MzBcG', 'player', '/uploads/avatars/avatar_8_1760997020.jpg', 78, 'Novice', 'active', NULL, NULL, NULL, '2025-10-14', '2025-10-25 02:13:27', '2025-10-14 19:02:26', '2025-10-25 02:13:27'),
(9, 'Admin', 'admin@gmail.com', '$2y$10$HqpKpd4mbUZ5rzbYBQ1/Re.kH.GNvpJsx2BWur7nYV5FOteqgpOMC', 'admin', NULL, 0, 'Novice', 'active', NULL, NULL, NULL, '2025-10-14', '2025-10-23 00:31:30', '2025-10-14 19:43:13', '2025-10-23 00:31:30'),
(10, 'ProGamer', 'progamer@test.com', '$2y$10$NA1uIQGTOT79KnJE0ApNYuFuuCX1On7ZddXJkn2NzZHqME1YJO8DS', 'player', NULL, 2544, 'Expert', 'active', NULL, NULL, NULL, '2025-07-03', '2025-09-17 12:13:02', '2025-10-15 12:13:02', '2025-10-15 12:13:02'),
(11, 'NoobMaster', 'noob@test.com', '$2y$10$NA1uIQGTOT79KnJE0ApNYuFuuCX1On7ZddXJkn2NzZHqME1YJO8DS', 'player', NULL, 1800, 'Expert', 'active', NULL, NULL, NULL, '2025-07-03', '2025-10-12 12:13:02', '2025-10-15 12:13:02', '2025-10-15 12:13:02'),
(12, 'SpeedRunner', 'speed@test.com', '$2y$10$NA1uIQGTOT79KnJE0ApNYuFuuCX1On7ZddXJkn2NzZHqME1YJO8DS', 'player', NULL, 3200, 'Maître', 'active', NULL, NULL, NULL, '2025-07-03', '2025-09-17 12:13:02', '2025-10-15 12:13:02', '2025-10-15 12:13:02'),
(13, 'CasualPlayer', 'casual@test.com', '$2y$10$NA1uIQGTOT79KnJE0ApNYuFuuCX1On7ZddXJkn2NzZHqME1YJO8DS', 'player', NULL, 450, 'Novice', 'active', NULL, NULL, NULL, '2025-07-03', '2025-09-18 12:13:02', '2025-10-15 12:13:02', '2025-10-15 12:13:02'),
(14, 'EliteGamer', 'elite@test.com', '$2y$10$NA1uIQGTOT79KnJE0ApNYuFuuCX1On7ZddXJkn2NzZHqME1YJO8DS', 'player', NULL, 5600, 'Maître', 'active', NULL, NULL, NULL, '2025-07-03', '2025-09-25 12:13:02', '2025-10-15 12:13:02', '2025-10-15 12:13:02'),
(15, 'NewbieJoe', 'newbie@test.com', '$2y$10$NA1uIQGTOT79KnJE0ApNYuFuuCX1On7ZddXJkn2NzZHqME1YJO8DS', 'player', NULL, 120, 'Novice', 'active', NULL, NULL, NULL, '2025-07-03', '2025-09-18 12:13:02', '2025-10-15 12:13:02', '2025-10-15 12:13:02'),
(16, 'VeteranKing', 'veteran@test.com', '$2y$10$NA1uIQGTOT79KnJE0ApNYuFuuCX1On7ZddXJkn2NzZHqME1YJO8DS', 'player', NULL, 4300, 'Maître', 'active', NULL, NULL, NULL, '2025-07-03', '2025-10-15 12:13:02', '2025-10-15 12:13:02', '2025-10-15 12:13:02'),
(17, 'StreamerPro', 'streamer@test.com', '$2y$10$NA1uIQGTOT79KnJE0ApNYuFuuCX1On7ZddXJkn2NzZHqME1YJO8DS', 'player', NULL, 2900, 'Expert', 'active', NULL, NULL, NULL, '2025-07-03', '2025-10-07 12:13:02', '2025-10-15 12:13:02', '2025-10-15 12:13:02'),
(18, 'QuickTest', 'quicktest3763@test.local', '$2y$10$YihmAyZeyDF8UxABgtKMZO4k0NnmyWjE/TaMX9zHvUMzNKMhnTv96', 'player', NULL, 0, NULL, 'active', NULL, NULL, NULL, '2025-10-16', '2025-10-16 14:22:42', '2025-10-16 14:22:42', '2025-10-16 14:22:42'),
(21, 'testplayer1', 'test1@example.com', '$2y$10$0PYxTDtWM2monbXQejpR7.F9fXA3cV5co1vQ4EbfX91ZyuvRbLi4q', 'player', NULL, 5000, '8', 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-16 14:57:42', '2025-10-16 14:57:42'),
(22, 'testplayer2', 'test2@example.com', '$2y$10$0PYxTDtWM2monbXQejpR7.F9fXA3cV5co1vQ4EbfX91ZyuvRbLi4q', 'player', NULL, 3500, '7', 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-16 14:57:42', '2025-10-16 14:57:42'),
(23, 'testplayer3', 'test3@example.com', '$2y$10$0PYxTDtWM2monbXQejpR7.F9fXA3cV5co1vQ4EbfX91ZyuvRbLi4q', 'player', NULL, 8000, '9', 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-16 14:57:42', '2025-10-16 14:57:42'),
(24, 'testplayer4', 'test4@example.com', '$2y$10$0PYxTDtWM2monbXQejpR7.F9fXA3cV5co1vQ4EbfX91ZyuvRbLi4q', 'player', NULL, 1500, '6', 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-16 14:57:42', '2025-10-16 14:57:42'),
(25, 'testplayer5', 'test5@example.com', '$2y$10$0PYxTDtWM2monbXQejpR7.F9fXA3cV5co1vQ4EbfX91ZyuvRbLi4q', 'player', NULL, 11990, '10', 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-16 14:57:42', '2025-10-20 16:52:38'),
(26, 'testplayer6', 'test6@example.com', '$2y$10$0PYxTDtWM2monbXQejpR7.F9fXA3cV5co1vQ4EbfX91ZyuvRbLi4q', 'player', NULL, 2500, '7', 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-16 14:57:42', '2025-10-16 14:57:42'),
(27, 'testplayer7', 'test7@example.com', '$2y$10$0PYxTDtWM2monbXQejpR7.F9fXA3cV5co1vQ4EbfX91ZyuvRbLi4q', 'player', NULL, 6500, '9', 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-16 14:57:42', '2025-10-16 14:57:42'),
(28, 'testplayer8', 'test8@example.com', '$2y$10$0PYxTDtWM2monbXQejpR7.F9fXA3cV5co1vQ4EbfX91ZyuvRbLi4q', 'player', NULL, 4200, '8', 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-16 14:57:42', '2025-10-16 14:57:42'),
(29, 'testplayer9', 'test9@example.com', '$2y$10$0PYxTDtWM2monbXQejpR7.F9fXA3cV5co1vQ4EbfX91ZyuvRbLi4q', 'player', NULL, 9500, '9', 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-16 14:57:42', '2025-10-16 14:57:42'),
(30, 'testplayer10', 'test10@example.com', '$2y$10$0PYxTDtWM2monbXQejpR7.F9fXA3cV5co1vQ4EbfX91ZyuvRbLi4q', 'player', NULL, 7800, '9', 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-16 14:57:42', '2025-10-16 14:57:42'),
(31, 'Harris', 'lokoharris25@gmail.com', '$2y$10$/ur3XxREXg84eS/Bm3Hu8.IwItrywZnXy0lvJKRn./ZS6pcXva0aK', 'player', NULL, 25, NULL, 'active', NULL, NULL, NULL, '2025-10-19', '2025-10-19 17:38:02', '2025-10-19 17:37:41', '2025-10-19 17:40:36'),
(32, 'AdminDemo', 'admin@gamezone.fr', '$2y$10$FpQ/.pynmF7COtq7fINBxOcrVN/v.hUcn405Twqv6aciS975Qe1Ea', 'admin', NULL, 0, NULL, 'active', NULL, NULL, NULL, NULL, '2025-10-20 14:08:11', '2025-10-20 13:49:30', '2025-10-20 14:08:11'),
(33, 'PlayerDemo', 'player@gamezone.fr', '$2y$10$FpQ/.pynmF7COtq7fINBxOcrVN/v.hUcn405Twqv6aciS975Qe1Ea', 'player', NULL, 0, NULL, 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-20 13:49:30', '2025-10-20 13:49:30'),
(34, 'QuickTest', 'quicktest9194@test.local', '$2y$10$9R7wHKZtAJnRgTOO8lMztuawWrNMwO.mA1DH4oi.hKua7EiceDoh6', 'player', NULL, 0, NULL, 'active', NULL, NULL, NULL, '2025-10-24', '2025-10-24 22:00:33', '2025-10-24 22:00:33', '2025-10-24 22:00:33');

-- --------------------------------------------------------

--
-- Structure de la table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_badges`
--

INSERT INTO `user_badges` (`id`, `user_id`, `badge_id`, `earned_at`) VALUES
(1, 21, 1, '2025-10-05 14:57:42'),
(2, 21, 3, '2025-09-29 14:57:42'),
(3, 21, 5, '2025-09-29 14:57:42'),
(4, 21, 10, '2025-09-16 14:57:42'),
(5, 22, 10, '2025-09-25 14:57:42'),
(6, 22, 11, '2025-09-28 14:57:42'),
(7, 23, 4, '2025-09-18 14:57:42'),
(8, 23, 7, '2025-09-20 14:57:42'),
(9, 24, 2, '2025-10-12 14:57:42'),
(10, 24, 5, '2025-10-12 14:57:42'),
(11, 24, 12, '2025-09-18 14:57:42'),
(12, 25, 4, '2025-09-22 14:57:42'),
(13, 25, 10, '2025-09-26 14:57:42'),
(14, 26, 2, '2025-09-28 14:57:42'),
(15, 26, 4, '2025-09-25 14:57:42'),
(16, 26, 6, '2025-09-19 14:57:42'),
(17, 26, 7, '2025-09-20 14:57:42'),
(18, 26, 12, '2025-10-06 14:57:42'),
(19, 27, 4, '2025-09-24 14:57:42'),
(20, 27, 9, '2025-09-17 14:57:42'),
(21, 28, 8, '2025-10-04 14:57:42'),
(22, 28, 11, '2025-09-16 14:57:42'),
(23, 29, 5, '2025-09-18 14:57:42'),
(24, 29, 10, '2025-10-01 14:57:42'),
(25, 30, 2, '2025-09-26 14:57:42'),
(26, 30, 6, '2025-09-30 14:57:42'),
(27, 30, 10, '2025-09-30 14:57:42'),
(28, 30, 11, '2025-10-02 14:57:42'),
(29, 30, 12, '2025-10-05 14:57:42');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `user_converted_minutes_summary`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `user_converted_minutes_summary` (
`user_id` int(11)
,`total_conversions` bigint(21)
,`total_points_spent` decimal(32,0)
,`total_minutes_gained` decimal(32,0)
,`total_minutes_used` decimal(32,0)
,`minutes_available` decimal(33,0)
,`next_expiry` datetime
);

-- --------------------------------------------------------

--
-- Structure de la table `user_stats`
--

CREATE TABLE `user_stats` (
  `user_id` int(11) NOT NULL,
  `games_played` int(11) NOT NULL DEFAULT 0,
  `events_attended` int(11) NOT NULL DEFAULT 0,
  `tournaments_won` int(11) NOT NULL DEFAULT 0,
  `tournaments_participated` int(11) NOT NULL DEFAULT 0,
  `friends_referred` int(11) NOT NULL DEFAULT 0,
  `total_points_earned` int(11) NOT NULL DEFAULT 0,
  `total_points_spent` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL,
  `rewards_redeemed` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_stats`
--

INSERT INTO `user_stats` (`user_id`, `games_played`, `events_attended`, `tournaments_won`, `tournaments_participated`, `friends_referred`, `total_points_earned`, `total_points_spent`, `updated_at`, `rewards_redeemed`) VALUES
(2, 0, 0, 0, 0, 0, 0, 0, '2025-10-14 21:49:57', 0),
(3, 0, 0, 0, 0, 0, 0, 0, '2025-10-14 21:49:57', 0),
(4, 0, 0, 0, 0, 0, 0, 0, '2025-10-14 21:49:57', 0),
(5, 0, 0, 0, 0, 0, 0, 0, '2025-10-14 21:49:57', 0),
(6, 0, 0, 0, 0, 0, 0, 0, '2025-10-14 21:49:57', 0),
(7, 0, 0, 0, 0, 0, 0, 0, '2025-10-14 21:49:57', 0),
(8, 1, 0, 0, 0, 0, 115, 259, '2025-10-23 14:26:07', 3),
(9, 0, 0, 0, 0, 0, 0, 0, '2025-10-14 21:49:57', 0),
(10, 1, 0, 0, 0, 0, 0, 0, '2025-10-22 19:38:26', 3),
(21, 24, 0, 0, 0, 0, 5000, 0, '0000-00-00 00:00:00', 0),
(22, 20, 0, 0, 0, 0, 3500, 0, '0000-00-00 00:00:00', 0),
(23, 8, 0, 0, 0, 0, 8000, 0, '0000-00-00 00:00:00', 0),
(24, 48, 0, 0, 0, 0, 1500, 0, '0000-00-00 00:00:00', 0),
(25, 10, 0, 0, 0, 0, 12000, 0, '2025-10-22 19:38:26', 1),
(26, 31, 0, 0, 0, 0, 2500, 0, '0000-00-00 00:00:00', 0),
(27, 23, 0, 0, 0, 0, 6500, 0, '0000-00-00 00:00:00', 0),
(28, 43, 0, 0, 0, 0, 4200, 0, '0000-00-00 00:00:00', 0),
(29, 22, 0, 0, 0, 0, 9500, 0, '0000-00-00 00:00:00', 0),
(30, 39, 0, 0, 0, 0, 7800, 0, '0000-00-00 00:00:00', 0);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `user_transaction_history`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `user_transaction_history` (
`transaction_id` int(11)
,`user_id` int(11)
,`username` varchar(100)
,`status` enum('pending','processing','completed','failed','refunded')
,`step` varchar(50)
,`points_amount` int(11)
,`money_amount` decimal(10,2)
,`failure_reason` text
,`created_at` datetime
,`completed_at` datetime
,`failed_at` datetime
,`purchase_id` int(11)
,`game_name` varchar(200)
,`session_status` varchar(50)
);

-- --------------------------------------------------------

--
-- Structure de la vue `active_sessions`
--
DROP TABLE IF EXISTS `active_sessions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_sessions`  AS SELECT `s`.`id` AS `id`, `s`.`user_id` AS `user_id`, `u`.`username` AS `username`, `u`.`avatar_url` AS `avatar_url`, `u`.`level` AS `level`, `u`.`points` AS `points`, `s`.`game_id` AS `game_id`, `g`.`name` AS `game_name`, `g`.`slug` AS `game_slug`, `g`.`image_url` AS `game_image`, `s`.`total_minutes` AS `total_minutes`, `s`.`used_minutes` AS `used_minutes`, `s`.`total_minutes`- `s`.`used_minutes` AS `remaining_minutes`, round(`s`.`used_minutes` * 100.0 / `s`.`total_minutes`,2) AS `progress_percent`, `s`.`status` AS `status`, `s`.`started_at` AS `started_at`, `s`.`paused_at` AS `paused_at`, `s`.`expires_at` AS `expires_at`, `s`.`purchase_id` AS `purchase_id`, `p`.`price` AS `price`, `p`.`payment_status` AS `payment_status` FROM (((`game_sessions` `s` join `users` `u` on(`s`.`user_id` = `u`.`id`)) join `games` `g` on(`s`.`game_id` = `g`.`id`)) join `purchases` `p` on(`s`.`purchase_id` = `p`.`id`)) WHERE `s`.`status` in ('pending','active','paused') ;

-- --------------------------------------------------------

--
-- Structure de la vue `game_stats`
--
DROP TABLE IF EXISTS `game_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `game_stats`  AS SELECT `g`.`id` AS `id`, `g`.`name` AS `name`, `g`.`slug` AS `slug`, `g`.`category` AS `category`, `g`.`is_active` AS `is_active`, count(distinct `p`.`id`) AS `total_purchases`, count(distinct `p`.`user_id`) AS `unique_players`, coalesce(sum(`p`.`price`),0) AS `total_revenue`, coalesce(sum(`p`.`duration_minutes`),0) AS `total_minutes_sold`, coalesce(avg(`p`.`price`),0) AS `avg_purchase_price`, count(distinct case when `p`.`payment_status` = 'completed' then `p`.`id` end) AS `completed_purchases`, count(distinct case when `p`.`payment_status` = 'pending' then `p`.`id` end) AS `pending_purchases` FROM (`games` `g` left join `purchases` `p` on(`g`.`id` = `p`.`game_id`)) GROUP BY `g`.`id`, `g`.`name`, `g`.`slug`, `g`.`category`, `g`.`is_active` ;

-- --------------------------------------------------------

--
-- Structure de la vue `package_stats`
--
DROP TABLE IF EXISTS `package_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `package_stats`  AS SELECT `pkg`.`id` AS `id`, `pkg`.`name` AS `name`, `g`.`name` AS `game_name`, `g`.`slug` AS `game_slug`, `pkg`.`duration_minutes` AS `duration_minutes`, `pkg`.`price` AS `price`, `pkg`.`points_earned` AS `points_earned`, `pkg`.`is_active` AS `is_active`, count(distinct `p`.`id`) AS `total_purchases`, coalesce(sum(`p`.`price`),0) AS `total_revenue` FROM ((`game_packages` `pkg` join `games` `g` on(`pkg`.`game_id` = `g`.`id`)) left join `purchases` `p` on(`pkg`.`id` = `p`.`package_id`)) GROUP BY `pkg`.`id`, `pkg`.`name`, `g`.`name`, `g`.`slug`, `pkg`.`duration_minutes`, `pkg`.`price`, `pkg`.`points_earned`, `pkg`.`is_active` ;

-- --------------------------------------------------------

--
-- Structure de la vue `points_redemption_history`
--
DROP TABLE IF EXISTS `points_redemption_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `points_redemption_history`  AS SELECT `p`.`id` AS `purchase_id`, `p`.`user_id` AS `user_id`, `u`.`username` AS `username`, `p`.`game_id` AS `game_id`, `p`.`game_name` AS `game_name`, `p`.`package_id` AS `package_id`, `p`.`package_name` AS `package_name`, `p`.`duration_minutes` AS `duration_minutes`, `p`.`points_spent` AS `points_spent`, `p`.`points_earned` AS `points_earned`, `p`.`payment_status` AS `payment_status`, `p`.`session_status` AS `session_status`, `p`.`created_at` AS `created_at`, `pkg`.`points_cost` AS `points_cost`, `r`.`id` AS `reward_id`, `r`.`name` AS `reward_name` FROM (((`purchases` `p` join `users` `u` on(`p`.`user_id` = `u`.`id`)) left join `game_packages` `pkg` on(`p`.`package_id` = `pkg`.`id`)) left join `rewards` `r` on(`pkg`.`reward_id` = `r`.`id`)) WHERE `p`.`paid_with_points` = 1 ORDER BY `p`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `point_packages`
--
DROP TABLE IF EXISTS `point_packages`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `point_packages`  AS SELECT `pkg`.`id` AS `id`, `pkg`.`game_id` AS `game_id`, `g`.`name` AS `game_name`, `g`.`slug` AS `game_slug`, `g`.`image_url` AS `game_image`, `pkg`.`name` AS `package_name`, `pkg`.`duration_minutes` AS `duration_minutes`, `pkg`.`points_cost` AS `points_cost`, `pkg`.`points_earned` AS `points_earned`, `pkg`.`bonus_multiplier` AS `bonus_multiplier`, `pkg`.`is_promotional` AS `is_promotional`, `pkg`.`promotional_label` AS `promotional_label`, `pkg`.`max_purchases_per_user` AS `max_purchases_per_user`, `pkg`.`available_from` AS `available_from`, `pkg`.`available_until` AS `available_until`, `pkg`.`is_active` AS `is_active`, `pkg`.`display_order` AS `display_order`, `r`.`id` AS `reward_id`, `r`.`name` AS `reward_name`, `r`.`description` AS `reward_description`, `r`.`image_url` AS `reward_image`, `r`.`category` AS `reward_category`, `r`.`is_featured` AS `reward_featured`, count(distinct `p`.`id`) AS `total_redemptions`, count(distinct `p`.`user_id`) AS `unique_users` FROM (((`game_packages` `pkg` join `games` `g` on(`pkg`.`game_id` = `g`.`id`)) left join `rewards` `r` on(`pkg`.`reward_id` = `r`.`id`)) left join `purchases` `p` on(`pkg`.`id` = `p`.`package_id` and `p`.`paid_with_points` = 1)) WHERE `pkg`.`is_points_only` = 1 GROUP BY `pkg`.`id` ;

-- --------------------------------------------------------

--
-- Structure de la vue `purchase_session_overview`
--
DROP TABLE IF EXISTS `purchase_session_overview`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `purchase_session_overview`  AS SELECT `p`.`id` AS `purchase_id`, `p`.`user_id` AS `user_id`, `p`.`game_id` AS `game_id`, `p`.`game_name` AS `game_name`, `p`.`package_name` AS `package_name`, `p`.`price` AS `price`, `p`.`currency` AS `currency`, `p`.`duration_minutes` AS `duration_minutes`, `p`.`payment_status` AS `payment_status`, `p`.`session_status` AS `purchase_session_status`, `p`.`created_at` AS `purchase_created_at`, `s`.`id` AS `session_id`, `s`.`status` AS `actual_session_status`, `s`.`total_minutes` AS `total_minutes`, `s`.`used_minutes` AS `used_minutes`, `s`.`remaining_minutes` AS `remaining_minutes`, `s`.`started_at` AS `started_at`, `s`.`completed_at` AS `completed_at`, `i`.`invoice_number` AS `invoice_number`, `i`.`validation_code` AS `validation_code`, `i`.`status` AS `invoice_status`, `u`.`username` AS `username`, `u`.`email` AS `email`, CASE WHEN `s`.`id` is null THEN 'NO_SESSION' WHEN `p`.`session_status` = `s`.`status` THEN 'SYNCED' ELSE 'MISMATCH' END AS `sync_status` FROM (((`purchases` `p` left join `active_game_sessions_v2` `s` on(`p`.`id` = `s`.`purchase_id`)) left join `invoices` `i` on(`p`.`id` = `i`.`purchase_id`)) join `users` `u` on(`p`.`user_id` = `u`.`id`)) ORDER BY `p`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `session_summary`
--
DROP TABLE IF EXISTS `session_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `session_summary`  AS SELECT `s`.`id` AS `id`, `s`.`invoice_id` AS `invoice_id`, `s`.`purchase_id` AS `purchase_id`, `s`.`user_id` AS `user_id`, `s`.`game_id` AS `game_id`, `s`.`total_minutes` AS `total_minutes`, `s`.`used_minutes` AS `used_minutes`, `s`.`total_minutes`- `s`.`used_minutes` AS `remaining_minutes`, round(`s`.`used_minutes` / `s`.`total_minutes` * 100,1) AS `progress_percent`, `s`.`status` AS `status`, `s`.`ready_at` AS `ready_at`, `s`.`started_at` AS `started_at`, `s`.`paused_at` AS `paused_at`, `s`.`resumed_at` AS `resumed_at`, `s`.`completed_at` AS `completed_at`, `s`.`last_heartbeat` AS `last_heartbeat`, `s`.`last_countdown_update` AS `last_countdown_update`, `s`.`pause_count` AS `pause_count`, `s`.`total_pause_time` AS `total_pause_time`, `s`.`auto_countdown` AS `auto_countdown`, `s`.`countdown_interval` AS `countdown_interval`, `s`.`monitored_by` AS `monitored_by`, `s`.`created_at` AS `created_at`, `s`.`updated_at` AS `updated_at`, `u`.`username` AS `username`, `u`.`email` AS `email`, `g`.`name` AS `game_name`, `i`.`invoice_number` AS `invoice_number`, `i`.`validation_code` AS `validation_code`, `i`.`status` AS `invoice_status` FROM (((`active_game_sessions_v2` `s` join `users` `u` on(`s`.`user_id` = `u`.`id`)) join `games` `g` on(`s`.`game_id` = `g`.`id`)) left join `invoices` `i` on(`s`.`invoice_id` = `i`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure de la vue `transaction_stats`
--
DROP TABLE IF EXISTS `transaction_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `transaction_stats`  AS SELECT `purchase_transactions`.`status` AS `status`, count(0) AS `count`, sum(`purchase_transactions`.`points_amount`) AS `total_points`, sum(`purchase_transactions`.`money_amount`) AS `total_money`, min(`purchase_transactions`.`created_at`) AS `first_transaction`, max(`purchase_transactions`.`created_at`) AS `last_transaction` FROM `purchase_transactions` GROUP BY `purchase_transactions`.`status` ;

-- --------------------------------------------------------

--
-- Structure de la vue `user_converted_minutes_summary`
--
DROP TABLE IF EXISTS `user_converted_minutes_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_converted_minutes_summary`  AS SELECT `point_conversions`.`user_id` AS `user_id`, count(0) AS `total_conversions`, sum(`point_conversions`.`points_spent`) AS `total_points_spent`, sum(`point_conversions`.`minutes_gained`) AS `total_minutes_gained`, sum(`point_conversions`.`minutes_used`) AS `total_minutes_used`, sum(`point_conversions`.`minutes_gained` - `point_conversions`.`minutes_used`) AS `minutes_available`, min(`point_conversions`.`expires_at`) AS `next_expiry` FROM `point_conversions` WHERE `point_conversions`.`status` = 'active' AND `point_conversions`.`expires_at` > current_timestamp() GROUP BY `point_conversions`.`user_id` ;

-- --------------------------------------------------------

--
-- Structure de la vue `user_transaction_history`
--
DROP TABLE IF EXISTS `user_transaction_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_transaction_history`  AS SELECT `pt`.`id` AS `transaction_id`, `pt`.`user_id` AS `user_id`, `u`.`username` AS `username`, `pt`.`status` AS `status`, `pt`.`step` AS `step`, `pt`.`points_amount` AS `points_amount`, `pt`.`money_amount` AS `money_amount`, `pt`.`failure_reason` AS `failure_reason`, `pt`.`created_at` AS `created_at`, `pt`.`completed_at` AS `completed_at`, `pt`.`failed_at` AS `failed_at`, `p`.`id` AS `purchase_id`, `p`.`game_name` AS `game_name`, `p`.`session_status` AS `session_status` FROM ((`purchase_transactions` `pt` join `users` `u` on(`pt`.`user_id` = `u`.`id`)) left join `purchases` `p` on(`pt`.`purchase_id` = `p`.`id`)) ORDER BY `pt`.`created_at` DESC ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `active_game_sessions_v2`
--
ALTER TABLE `active_game_sessions_v2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_id` (`invoice_id`),
  ADD KEY `fk_sessions_v2_purchase` (`purchase_id`),
  ADD KEY `fk_sessions_v2_game` (`game_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Index pour la table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `bonus_multipliers`
--
ALTER TABLE `bonus_multipliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bm_user` (`user_id`);

--
-- Index pour la table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_published` (`is_published`,`published_at`),
  ADD KEY `idx_pinned` (`is_pinned`);

--
-- Index pour la table `content_comments`
--
ALTER TABLE `content_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_content` (`content_id`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Index pour la table `content_items`
--
ALTER TABLE `content_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_content_author` (`author_id`),
  ADD KEY `idx_content_status` (`status`),
  ADD KEY `idx_content_type` (`content_type`),
  ADD KEY `idx_content_published` (`published_at`);

--
-- Index pour la table `content_likes`
--
ALTER TABLE `content_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`content_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_content` (`content_id`);

--
-- Index pour la table `content_reactions`
--
ALTER TABLE `content_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`content_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_content` (`content_id`),
  ADD KEY `idx_type` (`reaction_type`);

--
-- Index pour la table `content_shares`
--
ALTER TABLE `content_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_content` (`content_id`),
  ADD KEY `idx_platform` (`platform`);

--
-- Index pour la table `conversion_usage_log`
--
ALTER TABLE `conversion_usage_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usage_purchase` (`purchase_id`),
  ADD KEY `fk_usage_session` (`session_id`),
  ADD KEY `idx_conversion` (`conversion_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Index pour la table `daily_bonuses`
--
ALTER TABLE `daily_bonuses`
  ADD PRIMARY KEY (`user_id`);

--
-- Index pour la table `deleted_users`
--
ALTER TABLE `deleted_users`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_active_featured` (`is_active`,`is_featured`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_games_reservable` (`is_reservable`);

--
-- Index pour la table `game_packages`
--
ALTER TABLE `game_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_game_active` (`game_id`,`is_active`),
  ADD KEY `idx_promotional` (`is_promotional`),
  ADD KEY `idx_points_only` (`is_points_only`,`is_active`),
  ADD KEY `fk_package_reward` (`reward_id`);

--
-- Index pour la table `game_reservations`
--
ALTER TABLE `game_reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_res_purchase` (`purchase_id`),
  ADD KEY `idx_res_user` (`user_id`),
  ADD KEY `idx_res_game_time` (`game_id`,`scheduled_start`,`scheduled_end`),
  ADD KEY `idx_res_status` (`status`);

--
-- Index pour la table `game_sessions`
--
ALTER TABLE `game_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_purchase` (`purchase_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Index pour la table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_id` (`purchase_id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD UNIQUE KEY `validation_code` (`validation_code`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_validation_code` (`validation_code`),
  ADD KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Index pour la table `invoice_audit_log`
--
ALTER TABLE `invoice_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice` (`invoice_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Index pour la table `invoice_scans`
--
ALTER TABLE `invoice_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice` (`invoice_id`),
  ADD KEY `idx_code` (`validation_code`),
  ADD KEY `idx_scanned_at` (`scanned_at`);

--
-- Index pour la table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `level_number` (`level_number`);

--
-- Index pour la table `login_streaks`
--
ALTER TABLE `login_streaks`
  ADD PRIMARY KEY (`user_id`);

--
-- Index pour la table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_slug` (`slug`);

--
-- Index pour la table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase` (`purchase_id`),
  ADD KEY `idx_provider_transaction` (`provider_transaction_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Index pour la table `points_packages`
--
ALTER TABLE `points_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`,`display_order`);

--
-- Index pour la table `points_rules`
--
ALTER TABLE `points_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `action_type` (`action_type`);

--
-- Index pour la table `points_transactions`
--
ALTER TABLE `points_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pt_user` (`user_id`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Index pour la table `point_conversions`
--
ALTER TABLE `point_conversions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_conversions_game` (`game_id`),
  ADD KEY `fk_conversions_purchase` (`purchase_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `point_conversion_config`
--
ALTER TABLE `point_conversion_config`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_conversion_config_admin` (`updated_by`);

--
-- Index pour la table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `idx_user_status` (`user_id`,`payment_status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_payment_reference` (`payment_reference`),
  ADD KEY `idx_session_status` (`session_status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_transaction` (`transaction_id`);

--
-- Index pour la table `purchase_transactions`
--
ALTER TABLE `purchase_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_idempotency` (`user_id`,`idempotency_key`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_idempotency` (`idempotency_key`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `fk_tx_reward` (`reward_id`),
  ADD KEY `fk_tx_purchase` (`purchase_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_status_created` (`status`,`created_at`);

--
-- Index pour la table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reward_package` (`game_package_id`);

--
-- Index pour la table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rr_reward` (`reward_id`),
  ADD KEY `fk_rr_user` (`user_id`);

--
-- Index pour la table `session_activities`
--
ALTER TABLE `session_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Index pour la table `session_events`
--
ALTER TABLE `session_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Index pour la table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `winner_id` (`winner_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_featured` (`is_featured`);

--
-- Index pour la table `tournament_matches`
--
ALTER TABLE `tournament_matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player1_id` (`player1_id`),
  ADD KEY `player2_id` (`player2_id`),
  ADD KEY `winner_id` (`winner_id`),
  ADD KEY `idx_tournament` (`tournament_id`),
  ADD KEY `idx_round` (`round`);

--
-- Index pour la table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participant` (`tournament_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_tournament` (`tournament_id`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_badge` (`user_id`,`badge_id`),
  ADD KEY `fk_ub_badge` (`badge_id`);

--
-- Index pour la table `user_stats`
--
ALTER TABLE `user_stats`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `active_game_sessions_v2`
--
ALTER TABLE `active_game_sessions_v2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `bonus_multipliers`
--
ALTER TABLE `bonus_multipliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `content`
--
ALTER TABLE `content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `content_comments`
--
ALTER TABLE `content_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `content_items`
--
ALTER TABLE `content_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `content_likes`
--
ALTER TABLE `content_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `content_reactions`
--
ALTER TABLE `content_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `content_shares`
--
ALTER TABLE `content_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `conversion_usage_log`
--
ALTER TABLE `conversion_usage_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `deleted_users`
--
ALTER TABLE `deleted_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `game_packages`
--
ALTER TABLE `game_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `game_reservations`
--
ALTER TABLE `game_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `game_sessions`
--
ALTER TABLE `game_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT pour la table `invoice_audit_log`
--
ALTER TABLE `invoice_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT pour la table `invoice_scans`
--
ALTER TABLE `invoice_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT pour la table `levels`
--
ALTER TABLE `levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `points_packages`
--
ALTER TABLE `points_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `points_rules`
--
ALTER TABLE `points_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `points_transactions`
--
ALTER TABLE `points_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=665;

--
-- AUTO_INCREMENT pour la table `point_conversions`
--
ALTER TABLE `point_conversions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT pour la table `purchase_transactions`
--
ALTER TABLE `purchase_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `session_activities`
--
ALTER TABLE `session_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `session_events`
--
ALTER TABLE `session_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT pour la table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tournament_matches`
--
ALTER TABLE `tournament_matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT pour la table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `active_game_sessions_v2`
--
ALTER TABLE `active_game_sessions_v2`
  ADD CONSTRAINT `fk_sessions_v2_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sessions_v2_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sessions_v2_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sessions_v2_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `bonus_multipliers`
--
ALTER TABLE `bonus_multipliers`
  ADD CONSTRAINT `fk_bm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `content_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `content_comments`
--
ALTER TABLE `content_comments`
  ADD CONSTRAINT `content_comments_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `content_comments` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `content_items`
--
ALTER TABLE `content_items`
  ADD CONSTRAINT `fk_content_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `content_likes`
--
ALTER TABLE `content_likes`
  ADD CONSTRAINT `content_likes_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `content_reactions`
--
ALTER TABLE `content_reactions`
  ADD CONSTRAINT `content_reactions_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `content_shares`
--
ALTER TABLE `content_shares`
  ADD CONSTRAINT `content_shares_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_shares_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `conversion_usage_log`
--
ALTER TABLE `conversion_usage_log`
  ADD CONSTRAINT `fk_usage_conversion` FOREIGN KEY (`conversion_id`) REFERENCES `point_conversions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_usage_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usage_session` FOREIGN KEY (`session_id`) REFERENCES `active_game_sessions_v2` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `daily_bonuses`
--
ALTER TABLE `daily_bonuses`
  ADD CONSTRAINT `fk_db_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `game_packages`
--
ALTER TABLE `game_packages`
  ADD CONSTRAINT `fk_game_packages_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_package_reward` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `game_packages_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `game_reservations`
--
ALTER TABLE `game_reservations`
  ADD CONSTRAINT `fk_res_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_res_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `game_sessions`
--
ALTER TABLE `game_sessions`
  ADD CONSTRAINT `game_sessions_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_sessions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_sessions_ibfk_3` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invoices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `invoice_audit_log`
--
ALTER TABLE `invoice_audit_log`
  ADD CONSTRAINT `fk_audit_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `invoice_scans`
--
ALTER TABLE `invoice_scans`
  ADD CONSTRAINT `fk_scans_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `login_streaks`
--
ALTER TABLE `login_streaks`
  ADD CONSTRAINT `fk_ls_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `fk_transactions_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `points_transactions`
--
ALTER TABLE `points_transactions`
  ADD CONSTRAINT `fk_pt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `point_conversions`
--
ALTER TABLE `point_conversions`
  ADD CONSTRAINT `fk_conversions_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_conversions_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_conversions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `point_conversion_config`
--
ALTER TABLE `point_conversion_config`
  ADD CONSTRAINT `fk_conversion_config_admin` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchases_ibfk_3` FOREIGN KEY (`package_id`) REFERENCES `game_packages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchases_ibfk_4` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `purchase_transactions`
--
ALTER TABLE `purchase_transactions`
  ADD CONSTRAINT `fk_tx_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_reward` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rewards`
--
ALTER TABLE `rewards`
  ADD CONSTRAINT `fk_reward_package` FOREIGN KEY (`game_package_id`) REFERENCES `game_packages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  ADD CONSTRAINT `fk_rr_reward` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `session_activities`
--
ALTER TABLE `session_activities`
  ADD CONSTRAINT `fk_activities_session` FOREIGN KEY (`session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `session_events`
--
ALTER TABLE `session_events`
  ADD CONSTRAINT `fk_events_session` FOREIGN KEY (`session_id`) REFERENCES `active_game_sessions_v2` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tournaments`
--
ALTER TABLE `tournaments`
  ADD CONSTRAINT `tournaments_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tournaments_ibfk_2` FOREIGN KEY (`winner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tournaments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `tournament_matches`
--
ALTER TABLE `tournament_matches`
  ADD CONSTRAINT `tournament_matches_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_matches_ibfk_2` FOREIGN KEY (`player1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tournament_matches_ibfk_3` FOREIGN KEY (`player2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tournament_matches_ibfk_4` FOREIGN KEY (`winner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  ADD CONSTRAINT `tournament_participants_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `fk_ub_badge` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_stats`
--
ALTER TABLE `user_stats`
  ADD CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Évènements
--
CREATE DEFINER=`root`@`localhost` EVENT `expire_old_conversions` ON SCHEDULE EVERY 1 HOUR STARTS '2025-10-18 15:39:05' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE point_conversions
  SET status = 'expired'
  WHERE status = 'active'
    AND expires_at < NOW()$$

CREATE DEFINER=`root`@`localhost` EVENT `cleanup_transactions_event` ON SCHEDULE EVERY 5 MINUTE STARTS '2025-10-21 14:13:39' ON COMPLETION NOT PRESERVE ENABLE DO CALL cleanup_stuck_transactions()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
