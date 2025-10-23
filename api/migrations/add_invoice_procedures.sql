-- ============================================================================
-- Procédures stockées et triggers pour le système de facturation
-- ============================================================================

USE `gamezone`;

-- Trigger: Créer automatiquement une facture lors d'un achat complété
DELIMITER $$

DROP TRIGGER IF EXISTS after_purchase_completed$$
CREATE TRIGGER after_purchase_completed
AFTER UPDATE ON purchases
FOR EACH ROW
BEGIN
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
        CONCAT('Facture créée pour achat #', NEW.id),
        NOW()
      );
    END IF;
  END IF;
END$$

-- Procédure: Activer une facture après scan
DROP PROCEDURE IF EXISTS activate_invoice$$
CREATE PROCEDURE activate_invoice(
  IN p_validation_code VARCHAR(32),
  IN p_admin_id INT,
  IN p_ip_address VARCHAR(45),
  IN p_user_agent TEXT,
  OUT p_result VARCHAR(50),
  OUT p_invoice_id INT,
  OUT p_session_id INT
)
BEGIN
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
    
    INSERT INTO session_events (
      session_id, event_type, event_message,
      triggered_by, created_at
    ) VALUES (
      p_session_id, 'ready', 'Session prête - facture activée',
      p_admin_id, NOW()
    );
    
    INSERT INTO invoice_audit_log (
      invoice_id, action, performed_by, performed_by_type,
      action_details, ip_address, user_agent, created_at
    ) VALUES (
      p_invoice_id, 'activated', p_admin_id, 'admin',
      'Facture activée par scan', p_ip_address, p_user_agent, NOW()
    );
    
    SET p_result = 'success';
  END IF;
  
  INSERT INTO invoice_scans (
    invoice_id, validation_code, scan_result, scan_message,
    scanned_by, scanned_at, ip_address, user_agent
  ) VALUES (
    p_invoice_id, p_validation_code, p_result,
    CASE p_result
      WHEN 'success' THEN 'Activation réussie'
      WHEN 'invalid_code' THEN 'Code invalide'
      WHEN 'expired' THEN 'Facture expirée'
      WHEN 'fraud_detected' THEN 'Fraude détectée'
      ELSE CONCAT('Facture déjà ', v_invoice_status)
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

-- Procédure: Démarrer une session
DROP PROCEDURE IF EXISTS start_session$$
CREATE PROCEDURE start_session(
  IN p_session_id INT,
  IN p_admin_id INT,
  OUT p_result VARCHAR(50)
)
BEGIN
  DECLARE v_status VARCHAR(20);
  
  SELECT status INTO v_status FROM active_game_sessions_v2 WHERE id = p_session_id;
  
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
    
    INSERT INTO session_events (
      session_id, event_type, event_message,
      triggered_by, created_at
    ) VALUES (
      p_session_id, 'start', 'Session démarrée',
      p_admin_id, NOW()
    );
    
    SET p_result = 'success';
  END IF;
END$$

-- Procédure: Décompte automatique du temps
DROP PROCEDURE IF EXISTS countdown_active_sessions$$
CREATE PROCEDURE countdown_active_sessions()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE v_session_id INT;
  DECLARE v_invoice_id INT;
  DECLARE v_last_update DATETIME;
  DECLARE v_used_minutes INT;
  DECLARE v_total_minutes INT;
  DECLARE v_minutes_to_add INT;
  
  DECLARE session_cursor CURSOR FOR
    SELECT id, invoice_id, last_countdown_update, used_minutes, total_minutes
    FROM active_game_sessions_v2
    WHERE status = 'active' AND auto_countdown = 1;
  
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  
  OPEN session_cursor;
  
  read_loop: LOOP
    FETCH session_cursor INTO v_session_id, v_invoice_id, v_last_update, v_used_minutes, v_total_minutes;
    
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
        
        -- Mettre à jour le session_status dans purchases
        UPDATE purchases SET
          session_status = 'completed',
          updated_at = NOW()
        WHERE id = (SELECT purchase_id FROM active_game_sessions_v2 WHERE id = v_session_id);
        
        INSERT INTO session_events (
          session_id, event_type, event_message,
          minutes_after, triggered_by_system, created_at
        ) VALUES (
          v_session_id, 'complete', 'Session terminée - temps écoulé',
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
          v_session_id, 'countdown_update', CONCAT('Décompte: +', v_minutes_to_add, ' min'),
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
  
  -- Mettre à jour le session_status dans purchases pour les sessions expirées
  UPDATE purchases SET session_status = 'expired', updated_at = NOW()
  WHERE id IN (
    SELECT purchase_id FROM active_game_sessions_v2 
    WHERE status = 'expired' AND updated_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
  );
  
  UPDATE invoices SET status = 'expired', updated_at = NOW()
  WHERE status = 'pending' AND expires_at < NOW();
END$$

DELIMITER ;

-- Vues utiles
CREATE OR REPLACE VIEW active_invoices AS
SELECT 
  i.*,
  u.username,
  u.email,
  TIMESTAMPDIFF(MINUTE, NOW(), i.expires_at) as minutes_until_expiry,
  DATEDIFF(i.expires_at, NOW()) as days_until_expiry,
  s.status as session_status,
  s.remaining_minutes as session_remaining_minutes
FROM invoices i
INNER JOIN users u ON i.user_id = u.id
LEFT JOIN active_game_sessions_v2 s ON i.id = s.invoice_id
WHERE i.status IN ('pending', 'active')
ORDER BY i.created_at DESC;

CREATE OR REPLACE VIEW session_summary AS
SELECT 
  s.*,
  i.invoice_number,
  i.validation_code,
  u.username,
  g.name as game_name,
  ROUND((s.used_minutes / s.total_minutes) * 100, 1) as progress_percent
FROM active_game_sessions_v2 s
INNER JOIN invoices i ON s.invoice_id = i.id
INNER JOIN users u ON s.user_id = u.id
INNER JOIN games g ON s.game_id = g.id
ORDER BY s.created_at DESC;
