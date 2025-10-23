<?php
/**
 * Script pour corriger les factures sans expires_at ou déjà expirées
 */
require_once __DIR__ . '/api/config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .success { color: #059669; font-weight: bold; }
    .warning { color: #f59e0b; font-weight: bold; }
    .error { color: #dc2626; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
    th { background: #374151; color: white; }
    pre { background: #1f2937; color: #fff; padding: 15px; border-radius: 8px; }
</style></head><body>";

echo "<h1>🔧 Correction des Factures Expirées</h1>";

try {
    $pdo = get_db();
    
    // 1. Trouver les factures sans expires_at ou déjà expirées
    echo "<h2>1️⃣ Recherche des factures à corriger</h2>";
    
    $stmt = $pdo->query("
        SELECT id, purchase_id, validation_code, status, expires_at, created_at
        FROM invoices
        WHERE expires_at IS NULL 
           OR (expires_at < NOW() AND status = 'pending')
        ORDER BY id DESC
    ");
    
    $invoices = $stmt->fetchAll();
    $count = count($invoices);
    
    if ($count === 0) {
        echo "<p class='success'>✅ Aucune facture à corriger. Toutes les factures ont une date d'expiration valide !</p>";
    } else {
        echo "<p class='warning'>⚠️ Trouvé {$count} facture(s) à corriger</p>";
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Code</th><th>Status</th><th>Expires At (Avant)</th><th>Expires At (Après)</th><th>Action</th></tr>";
        
        $fixed = 0;
        $errors = 0;
        
        foreach ($invoices as $invoice) {
            try {
                // Définir une nouvelle date d'expiration: 2 mois à partir de maintenant
                $newExpiresAt = date('Y-m-d H:i:s', strtotime('+2 months'));
                
                $stmt = $pdo->prepare("
                    UPDATE invoices 
                    SET expires_at = ?, 
                        status = 'pending',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([$newExpiresAt, $invoice['id']]);
                
                echo "<tr>";
                echo "<td>{$invoice['id']}</td>";
                echo "<td><code>{$invoice['validation_code']}</code></td>";
                echo "<td>{$invoice['status']}</td>";
                echo "<td>" . ($invoice['expires_at'] ?? '<em>NULL</em>') . "</td>";
                echo "<td class='success'>{$newExpiresAt}</td>";
                echo "<td class='success'>✅ Corrigé</td>";
                echo "</tr>";
                
                $fixed++;
                
            } catch (Exception $e) {
                echo "<tr>";
                echo "<td>{$invoice['id']}</td>";
                echo "<td><code>{$invoice['validation_code']}</code></td>";
                echo "<td colspan='4' class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</td>";
                echo "</tr>";
                
                $errors++;
            }
        }
        
        echo "</table>";
        
        echo "<h2>📊 Résumé</h2>";
        echo "<ul>";
        echo "<li class='success'>✅ {$fixed} facture(s) corrigée(s)</li>";
        if ($errors > 0) {
            echo "<li class='error'>❌ {$errors} erreur(s)</li>";
        }
        echo "</ul>";
        
        if ($fixed > 0) {
            echo "<div style='background: #dcfce7; border: 2px solid #16a34a; border-radius: 8px; padding: 20px; margin: 20px 0;'>";
            echo "<h3 style='color: #166534; margin-top: 0;'>✅ Correction terminée !</h3>";
            echo "<p><strong>Ce qui a été fait :</strong></p>";
            echo "<ul>";
            echo "<li>Les factures ont maintenant une date d'expiration valide (2 mois)</li>";
            echo "<li>Le statut a été remis sur 'pending' pour permettre le scan</li>";
            echo "<li>Les anciennes erreurs 'already_expired' ne devraient plus apparaître</li>";
            echo "</ul>";
            
            echo "<p><strong>Test maintenant :</strong></p>";
            echo "<ol>";
            echo "<li>Va sur <code>/player/my-purchases</code></li>";
            echo "<li>Clique 'Démarrer la Session'</li>";
            echo "<li>La facture devrait s'afficher et rester visible</li>";
            echo "<li>Le scan admin devrait fonctionner sans erreur 'expired'</li>";
            echo "</ol>";
            echo "</div>";
        }
    }
    
    // 2. Vérifier l'état actuel
    echo "<h2>📋 État Actuel de Toutes les Factures</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END) as no_expiry,
            SUM(CASE WHEN expires_at < NOW() THEN 1 ELSE 0 END) as expired,
            SUM(CASE WHEN expires_at >= NOW() THEN 1 ELSE 0 END) as valid,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM invoices
    ");
    
    $stats = $stmt->fetch();
    
    echo "<table>";
    echo "<tr><th>Total Factures</th><td>{$stats['total']}</td></tr>";
    echo "<tr><th>Sans expiration</th><td" . ($stats['no_expiry'] > 0 ? " class='error'" : " class='success'") . ">{$stats['no_expiry']}</td></tr>";
    echo "<tr><th>Expirées</th><td" . ($stats['expired'] > 0 ? " class='warning'" : " class='success'") . ">{$stats['expired']}</td></tr>";
    echo "<tr><th>Valides</th><td class='success'>{$stats['valid']}</td></tr>";
    echo "<tr><th>Status: Pending</th><td>{$stats['pending']}</td></tr>";
    echo "</table>";
    
    if ($stats['no_expiry'] == 0 && $stats['expired'] == 0) {
        echo "<div style='background: #dcfce7; border: 2px solid #16a34a; border-radius: 8px; padding: 15px; margin: 20px 0;'>";
        echo "<p class='success' style='margin: 0; font-size: 1.2em;'>🎉 Parfait ! Toutes les factures ont une date d'expiration valide !</p>";
        echo "</div>";
    }
    
    // 3. Exemple de requête pour vérifier une facture spécifique
    echo "<h2>🔍 Vérifier une Facture Spécifique</h2>";
    echo "<form method='GET'>";
    echo "<input type='text' name='code' placeholder='Code de validation' style='padding: 10px; font-size: 16px; font-family: monospace; width: 300px;'>";
    echo "<button type='submit' style='padding: 10px 20px; font-size: 16px; cursor: pointer; margin-left: 10px;'>Vérifier</button>";
    echo "</form>";
    
    if (isset($_GET['code'])) {
        $code = trim($_GET['code']);
        $stmt = $pdo->prepare("
            SELECT id, purchase_id, status, expires_at, created_at,
                   TIMESTAMPDIFF(DAY, NOW(), expires_at) as days_until_expiry
            FROM invoices
            WHERE validation_code = ?
        ");
        $stmt->execute([$code]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            echo "<p class='error'>❌ Aucune facture trouvée avec ce code</p>";
        } else {
            $isValid = $invoice['expires_at'] && strtotime($invoice['expires_at']) > time();
            
            echo "<table>";
            echo "<tr><th>ID</th><td>{$invoice['id']}</td></tr>";
            echo "<tr><th>Purchase ID</th><td>{$invoice['purchase_id']}</td></tr>";
            echo "<tr><th>Status</th><td>{$invoice['status']}</td></tr>";
            echo "<tr><th>Créée le</th><td>{$invoice['created_at']}</td></tr>";
            echo "<tr><th>Expire le</th><td>{$invoice['expires_at']}</td></tr>";
            echo "<tr><th>Jours restants</th><td>{$invoice['days_until_expiry']} jours</td></tr>";
            echo "<tr><th>Valide ?</th><td class='" . ($isValid ? 'success' : 'error') . "'>" . ($isValid ? '✅ OUI' : '❌ NON') . "</td></tr>";
            echo "</table>";
            
            if ($isValid) {
                echo "<p class='success'>✅ Cette facture peut être scannée !</p>";
            } else {
                echo "<p class='error'>❌ Cette facture a expiré. Utilisez le bouton ci-dessous pour la réactiver.</p>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='fix_id' value='{$invoice['id']}'>";
                echo "<button type='submit' style='padding: 10px 20px; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer;'>Réactiver cette facture</button>";
                echo "</form>";
            }
        }
    }
    
    // Gérer la réactivation
    if (isset($_POST['fix_id'])) {
        $fixId = (int)$_POST['fix_id'];
        $newExpiry = date('Y-m-d H:i:s', strtotime('+2 months'));
        
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET expires_at = ?, status = 'pending', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newExpiry, $fixId]);
        
        echo "<div style='background: #dcfce7; border: 2px solid #16a34a; border-radius: 8px; padding: 15px; margin: 20px 0;'>";
        echo "<p class='success'>✅ Facture #{$fixId} réactivée avec succès ! Nouvelle expiration: {$newExpiry}</p>";
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
