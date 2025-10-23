<?php
// Créer le trigger qui génère automatiquement les factures
require_once __DIR__ . '/api/config.php';

echo "=== CRÉATION DU TRIGGER DE FACTURATION ===\n\n";

try {
    $pdo = get_db();
    
    // Supprimer le trigger s'il existe déjà
    echo "🗑️ Suppression de l'ancien trigger (si existe)...\n";
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS after_purchase_completed");
        echo "✓ Ancien trigger supprimé\n\n";
    } catch (Exception $e) {
        echo "  Note: Pas d'ancien trigger\n\n";
    }
    
    // Créer le nouveau trigger
    echo "📝 Création du trigger 'after_purchase_completed'...\n";
    
    $triggerSQL = "
    CREATE TRIGGER after_purchase_completed
    AFTER UPDATE ON purchases
    FOR EACH ROW
    BEGIN
        -- Si le statut passe à 'completed' et qu'il n'y a pas encore de facture
        IF NEW.payment_status = 'completed' 
           AND OLD.payment_status != 'completed'
           AND NOT EXISTS (SELECT 1 FROM invoices WHERE purchase_id = NEW.id)
        THEN
            -- Générer un code de validation unique (16 caractères alphanumériques)
            SET @validation_code = CONCAT(
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1),
                SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(1 + RAND() * 36), 1)
            );
            
            -- Générer le numéro de facture
            SET @invoice_number = CONCAT('INV-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(NEW.id, 5, '0'));
            
            -- Données QR (format JSON)
            SET @qr_data = JSON_OBJECT(
                'type', 'game_invoice',
                'code', @validation_code,
                'invoice', @invoice_number,
                'user_id', NEW.user_id,
                'game', NEW.game_name,
                'duration', NEW.duration_minutes
            );
            
            -- Hash SHA256 pour intégrité
            SET @qr_hash = SHA2(@qr_data, 256);
            
            -- Insérer la facture
            INSERT INTO invoices (
                purchase_id,
                user_id,
                invoice_number,
                validation_code,
                qr_code_data,
                qr_code_hash,
                amount,
                currency,
                duration_minutes,
                game_name,
                package_name,
                status,
                issued_at,
                expires_at,
                created_at,
                updated_at
            ) VALUES (
                NEW.id,
                NEW.user_id,
                @invoice_number,
                @validation_code,
                @qr_data,
                @qr_hash,
                NEW.price,
                NEW.currency,
                NEW.duration_minutes,
                NEW.game_name,
                NEW.package_name,
                'pending',
                NOW(),
                DATE_ADD(NOW(), INTERVAL 2 MONTH),
                NOW(),
                NOW()
            );
        END IF;
    END
    ";
    
    $pdo->exec($triggerSQL);
    echo "✅ TRIGGER CRÉÉ AVEC SUCCÈS !\n\n";
    
    // Vérifier que le trigger existe
    echo "🔍 Vérification...\n";
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'purchases'");
    $triggers = $stmt->fetchAll();
    
    if (!empty($triggers)) {
        echo "✓ Trigger confirmé:\n";
        foreach ($triggers as $trigger) {
            echo "  - {$trigger['Trigger']}\n";
        }
        echo "\n";
    }
    
    // Créer des factures pour les achats existants completed
    echo "📦 Création de factures pour les achats existants...\n";
    $stmt = $pdo->query("
        SELECT id, user_id, game_name, package_name, duration_minutes, price, currency
        FROM purchases 
        WHERE payment_status = 'completed' 
        AND id NOT IN (SELECT purchase_id FROM invoices)
    ");
    
    $purchasesWithoutInvoice = $stmt->fetchAll();
    
    if (empty($purchasesWithoutInvoice)) {
        echo "  ℹ️ Aucun achat sans facture\n\n";
    } else {
        $created = 0;
        foreach ($purchasesWithoutInvoice as $purchase) {
            // Générer code unique
            $validationCode = '';
            for ($i = 0; $i < 16; $i++) {
                $validationCode .= substr('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', rand(0, 35), 1);
            }
            
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($purchase['id'], 5, '0', STR_PAD_LEFT);
            
            $qrData = json_encode([
                'type' => 'game_invoice',
                'code' => $validationCode,
                'invoice' => $invoiceNumber,
                'user_id' => $purchase['user_id'],
                'game' => $purchase['game_name'],
                'duration' => $purchase['duration_minutes']
            ]);
            
            $qrHash = hash('sha256', $qrData);
            
            $stmt = $pdo->prepare("
                INSERT INTO invoices (
                    purchase_id, user_id, invoice_number, validation_code,
                    qr_code_data, qr_code_hash, amount, currency,
                    duration_minutes, game_name, package_name,
                    status, issued_at, expires_at, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 2 MONTH), NOW(), NOW())
            ");
            
            $stmt->execute([
                $purchase['id'],
                $purchase['user_id'],
                $invoiceNumber,
                $validationCode,
                $qrData,
                $qrHash,
                $purchase['price'],
                $purchase['currency'],
                $purchase['duration_minutes'],
                $purchase['game_name'],
                $purchase['package_name']
            ]);
            
            $created++;
            echo "  ✓ Facture créée pour achat #{$purchase['id']}\n";
            echo "    Code: $validationCode\n";
        }
        echo "\n✅ $created facture(s) créée(s) !\n\n";
    }
    
    echo "🎉 SYSTÈME DE FACTURATION OPÉRATIONNEL !\n\n";
    echo "Maintenant, testez à nouveau:\n";
    echo "1. Allez sur /player/my-purchases\n";
    echo "2. Cliquez 'Démarrer la Session'\n";
    echo "3. La facture va s'afficher !\n";
    
} catch (Exception $e) {
    echo "\n✗ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
