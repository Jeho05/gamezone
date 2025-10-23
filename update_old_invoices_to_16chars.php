<?php
/**
 * Script pour mettre à jour les anciennes factures avec des codes de 8 caractères
 * vers le nouveau format de 16 caractères
 */

require_once __DIR__ . '/api/config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .success { color: #059669; font-weight: bold; }
    .warning { color: #f59e0b; font-weight: bold; }
    .error { color: #dc2626; font-weight: bold; }
    .info { color: #3b82f6; font-weight: bold; }
    pre { background: #1f2937; color: #fff; padding: 15px; border-radius: 8px; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
    th { background: #374151; color: white; }
</style></head><body>";

echo "<h1>🔄 Mise à Jour des Codes de Validation</h1>";
echo "<p>Ce script met à jour les anciennes factures (8 caractères) vers le nouveau format (16 caractères).</p>";

try {
    $pdo = get_db();
    
    // Trouver les factures avec codes de 8 caractères
    echo "<h2>1️⃣ Recherche des anciennes factures...</h2>";
    
    $stmt = $pdo->query("
        SELECT id, purchase_id, validation_code, invoice_number
        FROM invoices
        WHERE LENGTH(REPLACE(validation_code, '-', '')) = 8
    ");
    
    $oldInvoices = $stmt->fetchAll();
    $count = count($oldInvoices);
    
    if ($count === 0) {
        echo "<p class='success'>✅ Aucune facture à mettre à jour. Toutes les factures utilisent déjà le format 16 caractères !</p>";
    } else {
        echo "<p class='warning'>⚠️ Trouvé {$count} facture(s) avec l'ancien format (8 caractères)</p>";
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Purchase ID</th><th>Ancien Code</th><th>Nouveau Code</th><th>Status</th></tr>";
        
        $updated = 0;
        $errors = 0;
        
        foreach ($oldInvoices as $invoice) {
            try {
                // Générer un nouveau code de 16 caractères
                $rawCode = strtoupper(substr(md5($invoice['purchase_id'] . time() . $invoice['id']), 0, 16));
                $newValidationCode = substr($rawCode, 0, 4) . '-' . 
                                   substr($rawCode, 4, 4) . '-' . 
                                   substr($rawCode, 8, 4) . '-' . 
                                   substr($rawCode, 12, 4);
                
                // Mettre à jour la facture
                $updateStmt = $pdo->prepare("
                    UPDATE invoices 
                    SET validation_code = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $updateStmt->execute([$newValidationCode, $invoice['id']]);
                
                echo "<tr>";
                echo "<td>{$invoice['id']}</td>";
                echo "<td>{$invoice['purchase_id']}</td>";
                echo "<td><code>{$invoice['validation_code']}</code></td>";
                echo "<td><code>{$newValidationCode}</code></td>";
                echo "<td class='success'>✅ Mis à jour</td>";
                echo "</tr>";
                
                $updated++;
                
            } catch (Exception $e) {
                echo "<tr>";
                echo "<td>{$invoice['id']}</td>";
                echo "<td>{$invoice['purchase_id']}</td>";
                echo "<td><code>{$invoice['validation_code']}</code></td>";
                echo "<td colspan='2' class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</td>";
                echo "</tr>";
                
                $errors++;
            }
        }
        
        echo "</table>";
        
        echo "<h2>📊 Résumé</h2>";
        echo "<ul>";
        echo "<li class='success'>✅ {$updated} facture(s) mise(s) à jour avec succès</li>";
        if ($errors > 0) {
            echo "<li class='error'>❌ {$errors} erreur(s)</li>";
        }
        echo "</ul>";
        
        if ($updated > 0) {
            echo "<div style='background: #dcfce7; border: 2px solid #16a34a; border-radius: 8px; padding: 20px; margin: 20px 0;'>";
            echo "<h3 style='color: #166534; margin-top: 0;'>✅ Mise à jour terminée !</h3>";
            echo "<p><strong>Prochaines étapes :</strong></p>";
            echo "<ul>";
            echo "<li>Les anciennes factures ont maintenant des codes de 16 caractères</li>";
            echo "<li>Les joueurs devront utiliser les nouveaux codes pour scanner</li>";
            echo "<li>L'admin peut scanner les anciens ET nouveaux codes (compatibilité maintenue)</li>";
            echo "<li>Toutes les nouvelles factures utiliseront automatiquement le format 16 caractères</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<h3>🔍 Vérification :</h3>";
            echo "<pre>";
            echo "-- Vérifier toutes les factures maintenant\n";
            echo "SELECT id, validation_code, \n";
            echo "       LENGTH(REPLACE(validation_code, '-', '')) as length\n";
            echo "FROM invoices\n";
            echo "ORDER BY id DESC\n";
            echo "LIMIT 10;";
            echo "</pre>";
        }
    }
    
    // Afficher un résumé de toutes les factures
    echo "<h2>📋 État Actuel de Toutes les Factures</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN LENGTH(REPLACE(validation_code, '-', '')) = 8 THEN 1 ELSE 0 END) as codes_8,
            SUM(CASE WHEN LENGTH(REPLACE(validation_code, '-', '')) = 16 THEN 1 ELSE 0 END) as codes_16,
            SUM(CASE WHEN LENGTH(REPLACE(validation_code, '-', '')) NOT IN (8, 16) THEN 1 ELSE 0 END) as codes_other
        FROM invoices
    ");
    
    $stats = $stmt->fetch();
    
    echo "<table>";
    echo "<tr><th>Total Factures</th><td class='info'>{$stats['total']}</td></tr>";
    echo "<tr><th>Codes 8 caractères (ancien)</th><td" . ($stats['codes_8'] > 0 ? " class='warning'" : " class='success'") . ">{$stats['codes_8']}</td></tr>";
    echo "<tr><th>Codes 16 caractères (nouveau)</th><td class='success'>{$stats['codes_16']}</td></tr>";
    if ($stats['codes_other'] > 0) {
        echo "<tr><th>Codes autre format</th><td class='error'>{$stats['codes_other']}</td></tr>";
    }
    echo "</table>";
    
    if ($stats['codes_8'] == 0 && $stats['codes_16'] > 0) {
        echo "<div style='background: #dcfce7; border: 2px solid #16a34a; border-radius: 8px; padding: 15px; margin: 20px 0;'>";
        echo "<p class='success' style='margin: 0; font-size: 1.2em;'>🎉 Parfait ! Toutes les factures utilisent maintenant le format 16 caractères !</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Erreur</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='http://localhost/projet%20ismo/'>← Retour à l'accueil</a></p>";
echo "</body></html>";
?>
