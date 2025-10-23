-- ============================================================================
-- Migration: Correction de la synchronisation Sessions <-> Achats
-- Date: 2025-01-18
-- Description: Corrections professionnelles pour garantir la cohérence
--              entre purchases.session_status et active_game_sessions_v2.status
-- ============================================================================

USE `gamezone`;

-- ============================================================================
-- ÉTAPE 1: Procédure de synchronisation manuelle (pour le nettoyage)
-- ============================================================================

DROP PROCEDURE IF EXISTS sync_purchase_session_status;

DELIMITER $$

CREATE PROCEDURE sync_purchase_session_status()
BEGIN
  -- Synchroniser tous les statuts incohérents
  UPDATE purchases p
  INNER JOIN active_game_sessions_v2 s ON p.id = s.purchase_id
  SET p.session_status = s.status,
      p.updated_at = NOW()
  WHERE p.session_status != s.status;
  
  -- Marquer comme pending les achats complétés sans session
  UPDATE purchases p
  LEFT JOIN active_game_sessions_v2 s ON p.id = s.purchase_id
  SET p.session_status = 'pending',
      p.updated_at = NOW()
  WHERE p.payment_status = 'completed' 
    AND s.id IS NULL
    AND p.session_status NOT IN ('pending', 'cancelled');
  
  -- Marquer comme cancelled les achats échoués
  UPDATE purchases 
  SET session_status = 'cancelled',
      updated_at = NOW()
  WHERE payment_status IN ('failed', 'cancelled', 'refunded')
    AND session_status != 'cancelled';

  SELECT 
    'Synchronisation terminée' as message,
    (SELECT COUNT(*) FROM purchases p INNER JOIN active_game_sessions_v2 s ON p.id = s.purchase_id WHERE p.session_status = s.status) as synced,
    (SELECT COUNT(*) FROM purchases p INNER JOIN active_game_sessions_v2 s ON p.id = s.purchase_id WHERE p.session_status != s.status) as remaining_mismatches;
END$$

DELIMITER ;

-- ============================================================================
-- ÉTAPE 2: Amélioration du trigger de création de facture
-- ============================================================================

DELIMITER $$

DROP TRIGGER IF EXISTS after_purchase_completed$$

CREATE TRIGGER after_purchase_completed
AFTER UPDATE ON purchases
FOR EACH ROW
BEGIN
  -- Déclenché quand un achat passe à "completed"
  IF NEW.payment_status = 'completed' AND OLD.payment_status != 'completed' THEN
    
    -- Créer la facture si elle n'existe pas encore
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
        CONCAT('Facture créée automatiquement pour achat #', NEW.id),
        NOW()
      );
    END IF;
    
    -- Mettre à jour le session_status si pas déjà défini
    IF NEW.session_status = 'pending' OR NEW.session_status IS NULL THEN
      -- Vérifier si c'est une réservation
      SET @is_reservation = (SELECT COUNT(*) FROM game_reservations WHERE purchase_id = NEW.id);
      
      IF @is_reservation > 0 THEN
        -- Pour une réservation, garder 'pending' jusqu'à l'activation
        UPDATE purchases SET session_status = 'pending', updated_at = NOW() WHERE id = NEW.id;
      ELSE
        -- Pour un achat standard, garder 'pending' jusqu'au scan de la facture
        UPDATE purchases SET session_status = 'pending', updated_at = NOW() WHERE id = NEW.id;
      END IF;
    END IF;
  END IF;
END$$

DELIMITER ;

-- ============================================================================
-- ÉTAPE 3: Amélioration de la procédure activate_invoice
-- ============================================================================

DELIMITER $$

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
    -- Activer la facture
    UPDATE invoices SET
      status = 'active',
      activated_at = NOW(),
      activated_by = p_admin_id,
      activation_ip = p_ip_address,
      activation_device = p_user_agent,
      updated_at = NOW()
    WHERE id = p_invoice_id;
    
    -- Récupérer les infos du jeu et de la durée
    SELECT game_id, duration_minutes INTO v_game_id, v_duration
    FROM purchases WHERE id = v_purchase_id;
    
    -- Créer la session
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
    
    -- IMPORTANT: Mettre à jour le session_status dans purchases
    UPDATE purchases 
    SET session_status = 'ready', updated_at = NOW()
    WHERE id = v_purchase_id;
    
    -- Événement de session
    INSERT INTO session_events (
      session_id, event_type, event_message,
      triggered_by, created_at
    ) VALUES (
      p_session_id, 'ready', 'Session prête - facture activée',
      p_admin_id, NOW()
    );
    
    -- Audit log
    INSERT INTO invoice_audit_log (
      invoice_id, action, performed_by, performed_by_type,
      action_details, ip_address, user_agent, created_at
    ) VALUES (
      p_invoice_id, 'activated', p_admin_id, 'admin',
      'Facture activée par scan', p_ip_address, p_user_agent, NOW()
    );
    
    SET p_result = 'success';
  END IF;
  
  -- Enregistrer la tentative de scan
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
  
  -- Mettre à jour les stats de scan
  IF p_invoice_id IS NOT NULL THEN
    UPDATE invoices SET
      scan_attempts = scan_attempts + 1,
      last_scan_attempt = NOW()
    WHERE id = p_invoice_id;
  END IF;
END$$

DELIMITER ;

-- ============================================================================
-- ÉTAPE 4: Amélioration de la procédure start_session
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS start_session$$

CREATE PROCEDURE start_session(
  IN p_session_id INT,
  IN p_admin_id INT,
  OUT p_result VARCHAR(50)
)
BEGIN
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
    -- Démarrer la session
    UPDATE active_game_sessions_v2 SET
      status = 'active',
      started_at = NOW(),
      last_heartbeat = NOW(),
      last_countdown_update = NOW(),
      updated_at = NOW()
    WHERE id = p_session_id;
    
    -- IMPORTANT: Synchroniser le statut dans purchases
    UPDATE purchases 
    SET session_status = 'active', updated_at = NOW()
    WHERE id = v_purchase_id;
    
    -- Événement
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

DELIMITER ;

-- ============================================================================
-- ÉTAPE 5: Trigger de synchronisation automatique (Sessions -> Purchases)
-- ============================================================================

DELIMITER $$

DROP TRIGGER IF EXISTS sync_session_to_purchase$$

CREATE TRIGGER sync_session_to_purchase
AFTER UPDATE ON active_game_sessions_v2
FOR EACH ROW
BEGIN
  -- Synchroniser automatiquement le statut de session vers purchase
  IF NEW.status != OLD.status THEN
    UPDATE purchases 
    SET session_status = NEW.status, updated_at = NOW()
    WHERE id = NEW.purchase_id;
  END IF;
END$$

DELIMITER ;

-- ============================================================================
-- ÉTAPE 6: Amélioration de la procédure countdown_active_sessions
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS countdown_active_sessions$$

CREATE PROCEDURE countdown_active_sessions()
BEGIN
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
    
    -- Calculer le temps écoulé
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
      
      -- Session terminée (temps écoulé)
      IF v_used_minutes >= v_total_minutes THEN
        UPDATE active_game_sessions_v2 SET
          status = 'completed',
          used_minutes = v_total_minutes,
          completed_at = NOW(),
          last_countdown_update = NOW(),
          updated_at = NOW()
        WHERE id = v_session_id;
        
        -- Marquer la facture comme utilisée
        UPDATE invoices SET
          status = 'used',
          used_at = NOW(),
          updated_at = NOW()
        WHERE id = v_invoice_id;
        
        -- Le trigger sync_session_to_purchase s'occupera de mettre à jour purchases.session_status
        
        INSERT INTO session_events (
          session_id, event_type, event_message,
          minutes_after, triggered_by_system, created_at
        ) VALUES (
          v_session_id, 'complete', 'Session terminée - temps écoulé',
          0, 1, NOW()
        );
        
      ELSE
        -- Mise à jour du temps utilisé
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
        
        -- Alerte de temps faible
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
  
  -- Expirer les sessions qui ont dépassé leur date limite
  UPDATE active_game_sessions_v2 SET status = 'expired', updated_at = NOW()
  WHERE status IN ('ready', 'active', 'paused') AND expires_at < NOW();
  
  -- Le trigger sync_session_to_purchase s'occupera de mettre à jour purchases.session_status
  
  -- Expirer les factures en attente
  UPDATE invoices SET status = 'expired', updated_at = NOW()
  WHERE status = 'pending' AND expires_at < NOW();
END$$

DELIMITER ;

-- ============================================================================
-- ÉTAPE 7: Nettoyage et synchronisation initiale
-- ============================================================================

-- Appeler la procédure de synchronisation
CALL sync_purchase_session_status();

-- ============================================================================
-- ÉTAPE 8: Vues améliorées
-- ============================================================================

-- Vue consolidée des achats avec leurs sessions
CREATE OR REPLACE VIEW purchase_session_overview AS
SELECT 
  p.id as purchase_id,
  p.user_id,
  p.game_id,
  p.game_name,
  p.package_name,
  p.price,
  p.currency,
  p.duration_minutes,
  p.payment_status,
  p.session_status as purchase_session_status,
  p.created_at as purchase_created_at,
  s.id as session_id,
  s.status as actual_session_status,
  s.total_minutes,
  s.used_minutes,
  s.remaining_minutes,
  s.started_at,
  s.completed_at,
  i.invoice_number,
  i.validation_code,
  i.status as invoice_status,
  u.username,
  u.email,
  -- Indicateur de cohérence
  CASE 
    WHEN s.id IS NULL THEN 'NO_SESSION'
    WHEN p.session_status = s.status THEN 'SYNCED'
    ELSE 'MISMATCH'
  END as sync_status
FROM purchases p
LEFT JOIN active_game_sessions_v2 s ON p.id = s.purchase_id
LEFT JOIN invoices i ON p.id = i.purchase_id
INNER JOIN users u ON p.user_id = u.id
ORDER BY p.created_at DESC;

-- ============================================================================
-- RAPPORT FINAL
-- ============================================================================

SELECT '========================================' as '';
SELECT 'MIGRATION TERMINÉE AVEC SUCCÈS' as 'STATUT';
SELECT '========================================' as '';
SELECT '' as '';
SELECT 'Procédures créées:' as '';
SELECT '  - sync_purchase_session_status' as '';
SELECT '  - activate_invoice (améliorée)' as '';
SELECT '  - start_session (améliorée)' as '';
SELECT '  - countdown_active_sessions (améliorée)' as '';
SELECT '' as '';
SELECT 'Triggers créés:' as '';
SELECT '  - after_purchase_completed (amélioré)' as '';
SELECT '  - sync_session_to_purchase (nouveau)' as '';
SELECT '' as '';
SELECT 'Vues créées:' as '';
SELECT '  - purchase_session_overview' as '';
SELECT '' as '';
SELECT '========================================' as '';
SELECT 'Pour vérifier la cohérence:' as '';
SELECT 'SELECT * FROM purchase_session_overview WHERE sync_status = "MISMATCH";' as '';
SELECT '========================================' as '';
