<?php
/**
 * Script de correction: Ajoute les colonnes manquantes à payment_methods
 * À exécuter une seule fois pour corriger la structure de la table
 */

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/utils.php';

$pdo = get_db();

echo "=== Correction de la table payment_methods ===\n\n";

try {
    // Vérifier et ajouter auto_confirm si elle n'existe pas
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_methods LIKE 'auto_confirm'");
    if ($stmt->rowCount() == 0) {
        echo "Ajout de la colonne 'auto_confirm'...\n";
        $pdo->exec("ALTER TABLE payment_methods 
                    ADD COLUMN auto_confirm TINYINT(1) NOT NULL DEFAULT 0 
                    COMMENT 'Confirmation automatique ou manuelle par admin' 
                    AFTER requires_online_payment");
        echo "✅ Colonne 'auto_confirm' ajoutée\n\n";
    } else {
        echo "✓ Colonne 'auto_confirm' existe déjà\n\n";
    }
    
    // Vérifier et ajouter instructions si elle n'existe pas
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_methods LIKE 'instructions'");
    if ($stmt->rowCount() == 0) {
        echo "Ajout de la colonne 'instructions'...\n";
        $pdo->exec("ALTER TABLE payment_methods 
                    ADD COLUMN instructions TEXT NULL 
                    COMMENT 'Instructions de paiement affichées à l\'utilisateur' 
                    AFTER display_order");
        echo "✅ Colonne 'instructions' ajoutée\n\n";
    } else {
        echo "✓ Colonne 'instructions' existe déjà\n\n";
    }
    
    // Afficher la structure actuelle
    echo "Structure actuelle de la table payment_methods:\n";
    echo str_repeat("-", 70) . "\n";
    $stmt = $pdo->query("DESCRIBE payment_methods");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        printf("%-25s %-20s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'] == 'YES' ? 'NULL' : 'NOT NULL'
        );
    }
    
    echo "\n✅ CORRECTION TERMINÉE!\n";
    echo "Vous pouvez maintenant créer des méthodes de paiement.\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
