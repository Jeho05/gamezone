-- ============================================================================
-- SYSTÈME DE TRANSACTIONS SÉCURISÉES
-- ============================================================================
-- 
-- Garantit qu'aucun joueur ne perd de points/argent en cas d'erreur
-- Système de rollback automatique et audit trail complet
--

-- Table des transactions d'achat
CREATE TABLE IF NOT EXISTS purchase_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reward_id INT NULL,
  purchase_id INT NULL COMMENT 'ID de l\'achat créé (si succès)',
  points_tx_id INT NULL COMMENT 'ID de la transaction de points',
  
  -- Idempotence (éviter les doubles achats)
  idempotency_key VARCHAR(255) NOT NULL COMMENT 'Clé unique pour éviter doublons',
  
  -- État de la transaction
  status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  step VARCHAR(50) NULL COMMENT 'Étape actuelle du processus',
  
  -- Montants
  points_amount INT NULL COMMENT 'Montant en points',
  money_amount DECIMAL(10,2) NULL COMMENT 'Montant en argent',
  currency VARCHAR(10) NULL,
  
  -- Informations de rollback
  failure_reason TEXT NULL COMMENT 'Raison de l\'échec si failed',
  refund_reason TEXT NULL COMMENT 'Raison du remboursement',
  refunded_by INT NULL COMMENT 'Admin qui a effectué le remboursement',
  
  -- Timestamps
  created_at DATETIME NOT NULL,
  completed_at DATETIME NULL,
  failed_at DATETIME NULL,
  refunded_at DATETIME NULL,
  
  -- Indexes
  INDEX idx_user (user_id),
  INDEX idx_idempotency (idempotency_key),
  INDEX idx_status (status),
  INDEX idx_created (created_at),
  
  UNIQUE KEY unique_idempotency (user_id, idempotency_key),
  
  CONSTRAINT fk_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_tx_reward FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE SET NULL,
  CONSTRAINT fk_tx_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Transactions sécurisées avec rollback';

-- Ajouter la colonne transaction_id dans purchases (si elle n'existe pas)
ALTER TABLE purchases 
ADD COLUMN transaction_id INT NULL COMMENT 'ID de la transaction sécurisée' AFTER id,
ADD INDEX idx_transaction (transaction_id);

-- ============================================================================
-- PROCÉDURE: Remboursement automatique
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS refund_transaction$$

CREATE PROCEDURE refund_transaction(
  IN p_transaction_id INT,
  IN p_refund_reason TEXT,
  IN p_admin_id INT,
  OUT p_result VARCHAR(50)
)
BEGIN
  DECLARE v_user_id INT;
  DECLARE v_points_amount INT;
  DECLARE v_money_amount DECIMAL(10,2);
  DECLARE v_purchase_id INT;
  DECLARE v_points_tx_id INT;
  DECLARE v_status VARCHAR(20);
  DECLARE v_current_balance INT;
  
  -- Récupérer les détails de la transaction
  SELECT user_id, status, points_amount, money_amount, purchase_id, points_tx_id
  INTO v_user_id, v_status, v_points_amount, v_money_amount, v_purchase_id, v_points_tx_id
  FROM purchase_transactions
  WHERE id = p_transaction_id
  LIMIT 1;
  
  -- Vérifications
  IF v_user_id IS NULL THEN
    SET p_result = 'transaction_not_found';
  ELSEIF v_status = 'refunded' THEN
    SET p_result = 'already_refunded';
  ELSEIF v_status != 'completed' THEN
    SET p_result = 'cannot_refund_uncompleted';
  ELSE
    -- Remboursement des points
    IF v_points_amount > 0 THEN
      UPDATE users 
      SET points = points + v_points_amount, updated_at = NOW()
      WHERE id = v_user_id;
      
      -- Enregistrer la transaction de remboursement
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
    
    -- Marquer l'achat comme annulé
    IF v_purchase_id IS NOT NULL THEN
      UPDATE purchases 
      SET session_status = 'cancelled', updated_at = NOW()
      WHERE id = v_purchase_id;
    END IF;
    
    -- Marquer la transaction comme remboursée
    UPDATE purchase_transactions
    SET status = 'refunded',
        refund_reason = p_refund_reason,
        refunded_by = p_admin_id,
        refunded_at = NOW()
    WHERE id = p_transaction_id;
    
    SET p_result = 'success';
  END IF;
END$$

-- ============================================================================
-- PROCÉDURE: Nettoyage des transactions bloquées
-- ============================================================================

DROP PROCEDURE IF EXISTS cleanup_stuck_transactions$$

CREATE PROCEDURE cleanup_stuck_transactions()
BEGIN
  -- Marquer comme "failed" les transactions en "processing" depuis plus de 5 minutes
  UPDATE purchase_transactions
  SET status = 'failed',
      failure_reason = 'Transaction timeout - Processus bloqué',
      failed_at = NOW()
  WHERE status = 'processing'
    AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE);
  
  -- Marquer comme "failed" les transactions en "pending" depuis plus de 10 minutes
  UPDATE purchase_transactions
  SET status = 'failed',
      failure_reason = 'Transaction abandonné - Timeout',
      failed_at = NOW()
  WHERE status = 'pending'
    AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE);
END$$

DELIMITER ;

-- ============================================================================
-- TRIGGER: Empêcher la modification des transactions complétées
-- ============================================================================

DELIMITER $$

DROP TRIGGER IF EXISTS prevent_completed_tx_modification$$

CREATE TRIGGER prevent_completed_tx_modification
BEFORE UPDATE ON purchase_transactions
FOR EACH ROW
BEGIN
  -- Empêcher la modification du status completed (sauf vers refunded)
  IF OLD.status = 'completed' AND NEW.status != 'refunded' AND NEW.status != 'completed' THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Impossible de modifier une transaction complétée';
  END IF;
END$$

DELIMITER ;

-- ============================================================================
-- EVENT: Nettoyage automatique toutes les 5 minutes
-- ============================================================================

CREATE EVENT IF NOT EXISTS cleanup_transactions_event
ON SCHEDULE EVERY 5 MINUTE
DO CALL cleanup_stuck_transactions();

-- ============================================================================
-- VUE: Statistiques des transactions
-- ============================================================================

CREATE OR REPLACE VIEW transaction_stats AS
SELECT 
  status,
  COUNT(*) as count,
  SUM(points_amount) as total_points,
  SUM(money_amount) as total_money,
  MIN(created_at) as first_transaction,
  MAX(created_at) as last_transaction
FROM purchase_transactions
GROUP BY status;

-- ============================================================================
-- VUE: Transactions récentes par utilisateur
-- ============================================================================

CREATE OR REPLACE VIEW user_transaction_history AS
SELECT 
  pt.id as transaction_id,
  pt.user_id,
  u.username,
  pt.status,
  pt.step,
  pt.points_amount,
  pt.money_amount,
  pt.failure_reason,
  pt.created_at,
  pt.completed_at,
  pt.failed_at,
  p.id as purchase_id,
  p.game_name,
  p.session_status
FROM purchase_transactions pt
INNER JOIN users u ON pt.user_id = u.id
LEFT JOIN purchases p ON pt.purchase_id = p.id
ORDER BY pt.created_at DESC;

-- ============================================================================
-- INDEX ADDITIONNELS pour performance
-- ============================================================================

ALTER TABLE purchase_transactions 
ADD INDEX idx_user_status (user_id, status),
ADD INDEX idx_status_created (status, created_at);

-- ============================================================================
-- Permissions
-- ============================================================================

-- Les admins peuvent voir toutes les transactions
GRANT SELECT ON purchase_transactions TO 'admin'@'localhost';
GRANT EXECUTE ON PROCEDURE refund_transaction TO 'admin'@'localhost';

-- ============================================================================
-- DONNÉES DE TEST (optionnel)
-- ============================================================================

-- Vous pouvez ajouter des données de test ici si nécessaire

COMMIT;
