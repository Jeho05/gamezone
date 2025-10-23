<?php
// Corriger les collations de toutes les tables
require_once __DIR__ . '/api/config.php';

echo "=== CORRECTION DES COLLATIONS ===\n\n";

try {
    $pdo = get_db();
    
    // Obtenir toutes les tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Tables trouvées: " . count($tables) . "\n\n";
    
    foreach ($tables as $table) {
        echo "🔧 Traitement: $table\n";
        
        try {
            // Convertir la table en utf8mb4_unicode_ci
            $pdo->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "  ✓ Collation mise à jour\n";
        } catch (Exception $e) {
            echo "  ⚠️ Erreur: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ TOUTES LES TABLES ONT ÉTÉ CONVERTIES !\n\n";
    
    // Vérification
    echo "🔍 Vérification des collations:\n";
    $stmt = $pdo->query("
        SELECT TABLE_NAME, TABLE_COLLATION 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE()
        ORDER BY TABLE_NAME
    ");
    
    $tables = $stmt->fetchAll();
    $allGood = true;
    
    foreach ($tables as $table) {
        $status = $table['TABLE_COLLATION'] === 'utf8mb4_unicode_ci' ? '✓' : '✗';
        echo "  $status {$table['TABLE_NAME']}: {$table['TABLE_COLLATION']}\n";
        
        if ($table['TABLE_COLLATION'] !== 'utf8mb4_unicode_ci') {
            $allGood = false;
        }
    }
    
    if ($allGood) {
        echo "\n🎉 PARFAIT ! Toutes les tables sont en utf8mb4_unicode_ci\n";
    } else {
        echo "\n⚠️ Certaines tables n'ont pas été converties\n";
    }
    
    // Recréer les procédures avec la bonne collation
    echo "\n🔄 Recréation des procédures...\n";
    
    // Drop et recréer activate_invoice
    $pdo->exec("DROP PROCEDURE IF EXISTS activate_invoice");
    
    $activateInvoiceSQL = "
    CREATE PROCEDURE activate_invoice(
        IN p_validation_code VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
        SELECT id, status, user_id, duration_minutes, expires_at, is_suspicious, purchase_id
        INTO v_invoice_id, v_invoice_status, v_user_id, v_duration, v_expires_at, v_is_suspicious, v_purchase_id
        FROM invoices
        WHERE validation_code = p_validation_code COLLATE utf8mb4_unicode_ci
        LIMIT 1;
        
        -- Code invalide
        IF v_invoice_id IS NULL THEN
            SET p_result = 'invalid_code';
            SET p_invoice_id = NULL;
            SET p_session_id = NULL;
            
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
            -- Récupérer le game_id
            SELECT game_id INTO v_game_id FROM purchases WHERE id = v_purchase_id;
            
            -- Activer la facture
            UPDATE invoices 
            SET status = 'active',
                activated_at = NOW(),
                activated_by = p_admin_id,
                activation_ip = p_ip_address,
                updated_at = NOW()
            WHERE id = v_invoice_id;
            
            -- Créer la session
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
            
            INSERT INTO invoice_scans (invoice_id, validation_code, scan_result, scan_message, scanned_by, scanned_at, ip_address, user_agent)
            VALUES (v_invoice_id, p_validation_code, 'success', 'Facture activée avec succès', p_admin_id, NOW(), p_ip_address, p_user_agent);
            
            INSERT INTO session_events (session_id, event_type, event_message, minutes_before, minutes_after, triggered_by, created_at)
            VALUES (p_session_id, 'ready', 'Session créée et prête à démarrer', NULL, v_duration, p_admin_id, NOW());
            
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
    echo "  ✓ Procédure activate_invoice recréée\n";
    
    echo "\n✅ CORRECTION TERMINÉE !\n";
    echo "Vous pouvez maintenant retester le scanner.\n";
    
} catch (Exception $e) {
    echo "\n✗ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
