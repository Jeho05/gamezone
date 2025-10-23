<?php
// update_countdown_procedure.php
// Script pour mettre à jour la procédure stockée countdown_active_sessions
// avec la synchronisation du session_status dans la table purchases

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/utils.php';

$pdo = get_db();

try {
    echo "🔄 Mise à jour de la procédure countdown_active_sessions...\n\n";
    
    // Supprimer l'ancienne procédure
    $pdo->exec('DROP PROCEDURE IF EXISTS countdown_active_sessions');
    echo "✓ Ancienne procédure supprimée\n";
    
    // Créer la nouvelle procédure avec les mises à jour
    $procedureSql = "
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
    END
    ";
    
    $pdo->exec($procedureSql);
    echo "✓ Nouvelle procédure créée\n\n";
    
    echo "✅ Procédure countdown_active_sessions mise à jour avec succès!\n\n";
    
    echo "📋 Modifications apportées:\n";
    echo "   - Ajout de la mise à jour de purchases.session_status = 'completed' quand une session se termine\n";
    echo "   - Ajout de la mise à jour de purchases.session_status = 'expired' quand une session expire\n\n";
    
    echo "🎯 Impact:\n";
    echo "   - Les factures ne seront plus visibles après la fin du temps de jeu\n";
    echo "   - L'historique des achats reste accessible\n\n";
    
    echo "✅ Mise à jour terminée!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
