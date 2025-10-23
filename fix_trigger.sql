USE gamezone;

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
        CONCAT('Facture créée automatiquement pour achat #', NEW.id),
        NOW()
      );
    END IF;
    
    -- Le session_status est déjà défini dans l'UPDATE qui a déclenché ce trigger
  END IF;
END$$

DELIMITER ;
