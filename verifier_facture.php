<?php
// Vérifier si les factures sont créées
require_once __DIR__ . '/api/config.php';

echo "=== VÉRIFICATION DES FACTURES ===\n\n";

try {
    $pdo = get_db();
    
    // Vérifier les achats récents
    echo "📦 ACHATS RÉCENTS (dernières 24h):\n";
    $stmt = $pdo->query("
        SELECT id, user_id, game_name, package_name, payment_status, 
               created_at, confirmed_at
        FROM purchases 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY created_at DESC
    ");
    
    $purchases = $stmt->fetchAll();
    
    if (empty($purchases)) {
        echo "  ❌ Aucun achat récent\n\n";
    } else {
        foreach ($purchases as $p) {
            echo "  ID: {$p['id']} | User: {$p['user_id']} | {$p['game_name']} - {$p['package_name']}\n";
            echo "    Status: {$p['payment_status']} | Confirmé: " . ($p['confirmed_at'] ?: 'Non') . "\n";
        }
        echo "\n";
    }
    
    // Vérifier les factures
    echo "🎫 FACTURES CRÉÉES:\n";
    $stmt = $pdo->query("
        SELECT i.id, i.purchase_id, i.invoice_number, i.validation_code, 
               i.status, i.created_at, p.game_name
        FROM invoices i
        JOIN purchases p ON i.purchase_id = p.id
        ORDER BY i.created_at DESC
        LIMIT 10
    ");
    
    $invoices = $stmt->fetchAll();
    
    if (empty($invoices)) {
        echo "  ❌ AUCUNE FACTURE TROUVÉE !\n\n";
        echo "  ⚠️ Le trigger ne fonctionne pas ou n'a pas été créé\n\n";
    } else {
        foreach ($invoices as $inv) {
            echo "  ID: {$inv['id']} | Purchase: {$inv['purchase_id']} | {$inv['game_name']}\n";
            echo "    Numéro: {$inv['invoice_number']}\n";
            echo "    Code: {$inv['validation_code']}\n";
            echo "    Status: {$inv['status']}\n";
            echo "    Créé: {$inv['created_at']}\n\n";
        }
    }
    
    // Vérifier que le trigger existe
    echo "🔍 VÉRIFICATION DU TRIGGER:\n";
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'purchases'");
    $triggers = $stmt->fetchAll();
    
    if (empty($triggers)) {
        echo "  ❌ TRIGGER MANQUANT !\n";
        echo "  Le trigger 'after_purchase_completed' n'existe pas\n\n";
        
        echo "🔧 SOLUTION: Exécuter le script de création du trigger\n";
        echo "  Fichier: api/migrations/add_invoice_procedures.sql\n\n";
    } else {
        echo "  ✅ Trigger(s) trouvé(s):\n";
        foreach ($triggers as $trigger) {
            echo "    - {$trigger['Trigger']}\n";
        }
        echo "\n";
    }
    
    // Tester la création manuelle d'une facture
    echo "🧪 TEST: Création manuelle d'une facture\n";
    
    // Trouver un achat sans facture
    $stmt = $pdo->query("
        SELECT p.id, p.user_id, p.game_id, p.game_name, p.package_name, 
               p.duration_minutes, p.price
        FROM purchases p
        LEFT JOIN invoices i ON p.id = i.purchase_id
        WHERE p.payment_status = 'completed' 
        AND i.id IS NULL
        LIMIT 1
    ");
    
    $purchaseWithoutInvoice = $stmt->fetch();
    
    if ($purchaseWithoutInvoice) {
        echo "  📦 Achat trouvé sans facture: ID {$purchaseWithoutInvoice['id']}\n";
        echo "  Voulez-vous créer une facture manuellement? (Oui/Non)\n";
        echo "  Commande: php create_invoice_manually.php {$purchaseWithoutInvoice['id']}\n\n";
    } else {
        echo "  ✅ Tous les achats completed ont une facture\n\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERREUR: " . $e->getMessage() . "\n";
}
