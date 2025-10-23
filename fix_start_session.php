<?php
// Corriger la procédure start_session
require_once __DIR__ . '/api/config.php';

echo "=== CORRECTION PROCÉDURE START_SESSION ===\n\n";

try {
    $pdo = get_db();
    
    // Vérifier quelles procédures existent
    echo "🔍 Vérification des procédures existantes...\n";
    $stmt = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE()");
    $procedures = $stmt->fetchAll();
    
    echo "Procédures trouvées:\n";
    foreach ($procedures as $proc) {
        echo "  - {$proc['Name']}\n";
    }
    echo "\n";
    
    // Créer ou recréer la procédure start_session (sans 'game' dans le nom)
    echo "📝 Création de la procédure start_session...\n";
    $pdo->exec("DROP PROCEDURE IF EXISTS start_session");
    
    $startSessionSQL = "
    CREATE PROCEDURE start_session(
        IN p_session_id INT,
        IN p_admin_id INT,
        OUT p_result VARCHAR(50)
    )
    BEGIN
        DECLARE v_status VARCHAR(50);
        DECLARE v_invoice_id INT;
        
        -- Vérifier la session
        SELECT status, invoice_id INTO v_status, v_invoice_id 
        FROM active_game_sessions_v2 
        WHERE id = p_session_id;
        
        IF v_status IS NULL THEN
            SET p_result = 'session_not_found';
        ELSEIF v_status NOT IN ('ready', 'paused') THEN
            SET p_result = 'invalid_status';
        ELSE
            -- Démarrer la session
            UPDATE active_game_sessions_v2
            SET status = 'active',
                started_at = NOW(),
                last_heartbeat = NOW(),
                last_countdown_update = NOW(),
                updated_at = NOW()
            WHERE id = p_session_id;
            
            -- Logger l'événement
            INSERT INTO session_events (
                session_id, event_type, event_message, triggered_by, created_at
            ) VALUES (
                p_session_id, 'start', 'Session démarrée par admin', p_admin_id, NOW()
            );
            
            -- Audit log facture
            INSERT INTO invoice_audit_log (
                invoice_id, action, performed_by, performed_by_type, 
                action_details, created_at
            ) VALUES (
                v_invoice_id, 'session_started', p_admin_id, 'admin',
                'Session démarrée', NOW()
            );
            
            SET p_result = 'success';
        END IF;
    END
    ";
    
    $pdo->exec($startSessionSQL);
    echo "✅ Procédure start_session créée\n\n";
    
    // Tester si on peut l'appeler
    echo "🧪 Test de la procédure...\n";
    try {
        $stmt = $pdo->prepare("CALL start_session(999999, 1, @result)");
        $stmt->execute();
        $stmt = $pdo->query("SELECT @result as result");
        $result = $stmt->fetch();
        echo "  ✓ Test OK (résultat attendu: 'session_not_found'): {$result['result']}\n";
    } catch (Exception $e) {
        echo "  ✗ Erreur test: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ CORRECTION TERMINÉE !\n";
    echo "Vous pouvez maintenant retester le démarrage de session.\n";
    
    // Afficher les sessions ready
    echo "\n📋 Sessions disponibles pour démarrage:\n";
    $stmt = $pdo->query("
        SELECT s.id, s.status, u.username, i.invoice_number
        FROM active_game_sessions_v2 s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN invoices i ON s.invoice_id = i.id
        WHERE s.status IN ('ready', 'paused')
        ORDER BY s.created_at DESC
        LIMIT 5
    ");
    
    $sessions = $stmt->fetchAll();
    if (empty($sessions)) {
        echo "  Aucune session disponible\n";
    } else {
        foreach ($sessions as $sess) {
            echo "  ID: {$sess['id']} | Status: {$sess['status']} | User: {$sess['username']} | Invoice: {$sess['invoice_number']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n✗ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
