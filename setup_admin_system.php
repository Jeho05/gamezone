<?php
// Configuration complète du système admin avec destruction automatique des factures
require_once __DIR__ . '/api/config.php';

echo "=== CONFIGURATION SYSTÈME ADMIN COMPLET ===\n\n";

try {
    $pdo = get_db();
    
    // 1. Créer la procédure activate_invoice
    echo "📝 Création de la procédure activate_invoice...\n";
    $pdo->exec("DROP PROCEDURE IF EXISTS activate_invoice");
    
    $activateInvoiceSQL = "
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
        DECLARE v_invoice_id INT;
        DECLARE v_invoice_status VARCHAR(50);
        DECLARE v_is_suspicious TINYINT;
        DECLARE v_user_id INT;
        DECLARE v_game_id INT;
        DECLARE v_purchase_id INT;
        DECLARE v_duration INT;
        DECLARE v_expires_at DATETIME;
        
        -- Vérifier si la facture existe
        SELECT id, status, user_id, game_name, duration_minutes, expires_at, is_suspicious, purchase_id
        INTO v_invoice_id, v_invoice_status, v_user_id, v_game_id, v_duration, v_expires_at, v_is_suspicious, v_purchase_id
        FROM invoices
        WHERE validation_code = p_validation_code
        LIMIT 1;
        
        -- Code invalide
        IF v_invoice_id IS NULL THEN
            SET p_result = 'invalid_code';
            SET p_invoice_id = NULL;
            SET p_session_id = NULL;
            
            -- Logger la tentative échouée
            INSERT INTO invoice_scans (validation_code, scan_result, scan_message, scanned_by, scanned_at, ip_address, user_agent)
            VALUES (p_validation_code, 'invalid_code', 'Code de validation invalide', p_admin_id, NOW(), p_ip_address, p_user_agent);
            
        -- Facture déjà active ou utilisée
        ELSEIF v_invoice_status IN ('active', 'used') THEN
            SET p_result = CONCAT('already_', v_invoice_status);
            SET p_invoice_id = v_invoice_id;
            SET p_session_id = NULL;
            
            INSERT INTO invoice_scans (invoice_id, validation_code, scan_result, scanned_by, scanned_at, ip_address, user_agent)
            VALUES (v_invoice_id, p_validation_code, p_result, p_admin_id, NOW(), p_ip_address, p_user_agent);
            
        -- Facture expirée
        ELSEIF v_expires_at < NOW() THEN
            SET p_result = 'expired';
            SET p_invoice_id = v_invoice_id;
            SET p_session_id = NULL;
            
            -- Mettre à jour le statut
            UPDATE invoices SET status = 'expired', updated_at = NOW() WHERE id = v_invoice_id;
            
            INSERT INTO invoice_scans (invoice_id, validation_code, scan_result, scanned_by, scanned_at, ip_address, user_agent)
            VALUES (v_invoice_id, p_validation_code, 'expired', p_admin_id, NOW(), p_ip_address, p_user_agent);
            
        -- Facture suspecte
        ELSEIF v_is_suspicious = 1 THEN
            SET p_result = 'fraud_detected';
            SET p_invoice_id = v_invoice_id;
            SET p_session_id = NULL;
            
            INSERT INTO invoice_scans (invoice_id, validation_code, scan_result, scanned_by, scanned_at, ip_address, user_agent)
            VALUES (v_invoice_id, p_validation_code, 'fraud_detected', p_admin_id, NOW(), p_ip_address, p_user_agent);
            
        -- Facture valide - ACTIVATION
        ELSEIF v_invoice_status = 'pending' THEN
            -- Récupérer le game_id depuis purchases
            SELECT game_id INTO v_game_id FROM purchases WHERE id = v_purchase_id;
            
            -- Activer la facture
            UPDATE invoices 
            SET status = 'active',
                activated_at = NOW(),
                activated_by = p_admin_id,
                activation_ip = p_ip_address,
                updated_at = NOW()
            WHERE id = v_invoice_id;
            
            -- Créer la session de jeu
            INSERT INTO active_game_sessions_v2 (
                invoice_id, purchase_id, user_id, game_id,
                total_minutes, used_minutes, status,
                ready_at, expires_at, auto_countdown, countdown_interval,
                monitored_by, created_at, updated_at
            ) VALUES (
                v_invoice_id, v_purchase_id, v_user_id, v_game_id,
                v_duration, 0, 'ready',
                NOW(), DATE_ADD(NOW(), INTERVAL v_duration MINUTE), 1, 60,
                p_admin_id, NOW(), NOW()
            );
            
            SET p_session_id = LAST_INSERT_ID();
            
            -- Logger le scan réussi
            INSERT INTO invoice_scans (invoice_id, validation_code, scan_result, scan_message, scanned_by, scanned_at, ip_address, user_agent)
            VALUES (v_invoice_id, p_validation_code, 'success', 'Facture activée avec succès', p_admin_id, NOW(), p_ip_address, p_user_agent);
            
            -- Event de session
            INSERT INTO session_events (session_id, event_type, event_message, minutes_before, minutes_after, triggered_by, created_at)
            VALUES (p_session_id, 'ready', 'Session créée et prête à démarrer', NULL, v_duration, p_admin_id, NOW());
            
            -- Audit log
            INSERT INTO invoice_audit_log (invoice_id, action, performed_by, performed_by_type, action_details, ip_address, user_agent, created_at)
            VALUES (v_invoice_id, 'activated', p_admin_id, 'admin', 'Facture activée via scanner admin', p_ip_address, p_user_agent, NOW());
            
            SET p_result = 'success';
            SET p_invoice_id = v_invoice_id;
        ELSE
            SET p_result = 'unknown_status';
            SET p_invoice_id = v_invoice_id;
            SET p_session_id = NULL;
        END IF;
    END
    ";
    
    $pdo->exec($activateInvoiceSQL);
    echo "✅ Procédure activate_invoice créée\n\n";
    
    // 2. Créer la procédure pour démarrer une session
    echo "📝 Création de la procédure start_game_session...\n";
    $pdo->exec("DROP PROCEDURE IF EXISTS start_game_session");
    
    $startSessionSQL = "
    CREATE PROCEDURE start_game_session(
        IN p_session_id INT,
        IN p_admin_id INT,
        OUT p_result VARCHAR(50)
    )
    BEGIN
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
    END
    ";
    
    $pdo->exec($startSessionSQL);
    echo "✅ Procédure start_game_session créée\n\n";
    
    // 3. Créer la procédure de décompte automatique (CRON)
    echo "📝 Création de la procédure countdown_active_sessions...\n";
    $pdo->exec("DROP PROCEDURE IF EXISTS countdown_active_sessions");
    
    $countdownSQL = "
    CREATE PROCEDURE countdown_active_sessions()
    BEGIN
        DECLARE done INT DEFAULT 0;
        DECLARE v_session_id INT;
        DECLARE v_invoice_id INT;
        DECLARE v_remaining INT;
        DECLARE v_used INT;
        
        DECLARE cur CURSOR FOR 
            SELECT id, invoice_id, remaining_minutes, used_minutes
            FROM active_game_sessions_v2
            WHERE status = 'active' 
            AND auto_countdown = 1
            AND TIMESTAMPDIFF(SECOND, last_countdown_update, NOW()) >= countdown_interval;
            
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
        
        OPEN cur;
        
        read_loop: LOOP
            FETCH cur INTO v_session_id, v_invoice_id, v_remaining, v_used;
            
            IF done THEN
                LEAVE read_loop;
            END IF;
            
            -- Déduire 1 minute
            UPDATE active_game_sessions_v2
            SET used_minutes = used_minutes + 1,
                last_countdown_update = NOW(),
                updated_at = NOW()
            WHERE id = v_session_id;
            
            SET v_remaining = v_remaining - 1;
            
            -- Logger l'événement
            INSERT INTO session_events (session_id, event_type, minutes_delta, minutes_before, minutes_after, triggered_by_system, created_at)
            VALUES (v_session_id, 'countdown_update', -1, v_remaining + 1, v_remaining, 1, NOW());
            
            -- Si temps écoulé, terminer la session et DÉTRUIRE la facture
            IF v_remaining <= 0 THEN
                UPDATE active_game_sessions_v2
                SET status = 'completed',
                    completed_at = NOW(),
                    updated_at = NOW()
                WHERE id = v_session_id;
                
                -- DÉTRUIRE LA FACTURE (statut 'used')
                UPDATE invoices
                SET status = 'used',
                    used_at = NOW(),
                    updated_at = NOW()
                WHERE id = v_invoice_id;
                
                INSERT INTO session_events (session_id, event_type, event_message, triggered_by_system, created_at)
                VALUES (v_session_id, 'complete', 'Session terminée - Temps écoulé - Facture détruite', 1, NOW());
                
                INSERT INTO invoice_audit_log (invoice_id, action, performed_by_type, action_details, created_at)
                VALUES (v_invoice_id, 'used', 'system', 'Facture automatiquement détruite après utilisation complète', NOW());
                
            -- Alerte si moins de 5 minutes
            ELSEIF v_remaining = 5 THEN
                INSERT INTO session_events (session_id, event_type, event_message, minutes_after, triggered_by_system, created_at)
                VALUES (v_session_id, 'warning_low_time', 'Plus que 5 minutes restantes', v_remaining, 1, NOW());
            END IF;
            
        END LOOP;
        
        CLOSE cur;
    END
    ";
    
    $pdo->exec($countdownSQL);
    echo "✅ Procédure countdown_active_sessions créée\n\n";
    
    // Vérifier que tout est créé
    echo "🔍 Vérification des procédures...\n";
    $stmt = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE()");
    $procedures = $stmt->fetchAll();
    
    echo "Procédures existantes:\n";
    foreach ($procedures as $proc) {
        echo "  ✓ {$proc['Name']}\n";
    }
    
    echo "\n🎉 SYSTÈME ADMIN COMPLET ET OPÉRATIONNEL !\n\n";
    echo "Fonctionnalités activées:\n";
    echo "  ✓ Scanner de factures avec validation\n";
    echo "  ✓ Activation automatique de sessions\n";
    echo "  ✓ Décompte automatique du temps\n";
    echo "  ✓ DESTRUCTION automatique des factures après utilisation\n";
    echo "  ✓ Alertes temps faible (5 min)\n";
    echo "  ✓ Logs et audit trail complets\n";
    
} catch (Exception $e) {
    echo "\n✗ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
