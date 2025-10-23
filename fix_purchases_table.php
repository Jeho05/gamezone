<?php
/**
 * Script de correction: Vérifie et corrige la table purchases
 * À exécuter pour diagnostiquer/corriger l'erreur de confirmation d'achat
 */

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/utils.php';

$pdo = get_db();

echo "=== Diagnostic et Correction de la table purchases ===\n\n";

try {
    // 1. Vérifier si la table existe
    echo "1. Vérification de l'existence de la table purchases...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'purchases'");
    if ($stmt->rowCount() == 0) {
        echo "❌ ERREUR: La table 'purchases' n'existe pas!\n";
        echo "   Vous devez exécuter la migration: api/migrations/add_game_purchase_system.sql\n\n";
        exit(1);
    }
    echo "✅ Table 'purchases' existe\n\n";

    // 2. Afficher la structure actuelle
    echo "2. Structure actuelle de la table purchases:\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $pdo->query("DESCRIBE purchases");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existingColumns = [];
    foreach ($columns as $col) {
        $existingColumns[] = $col['Field'];
        printf("%-30s %-25s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'] == 'YES' ? 'NULL' : 'NOT NULL'
        );
    }
    echo "\n";

    // 3. Vérifier les colonnes nécessaires
    echo "3. Vérification des colonnes nécessaires:\n";
    $requiredColumns = [
        'id', 'user_id', 'game_id', 'package_id', 'game_name', 'package_name',
        'duration_minutes', 'price', 'currency', 'points_earned', 'points_credited',
        'payment_method_id', 'payment_method_name', 'payment_status', 'payment_reference',
        'payment_details', 'confirmed_by', 'confirmed_at', 'session_status', 'notes',
        'created_at', 'updated_at'
    ];

    $missingColumns = [];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "  ✅ $col\n";
        } else {
            echo "  ❌ $col MANQUANTE\n";
            $missingColumns[] = $col;
        }
    }
    echo "\n";

    // 4. Si des colonnes manquent, proposer correction
    if (!empty($missingColumns)) {
        echo "⚠️  ATTENTION: " . count($missingColumns) . " colonne(s) manquante(s)\n";
        echo "   Colonnes manquantes: " . implode(', ', $missingColumns) . "\n\n";
        echo "   SOLUTION: Exécutez la migration SQL complète:\n";
        echo "   php -f api/migrations/add_game_purchase_system.sql\n\n";
    } else {
        echo "✅ Toutes les colonnes nécessaires sont présentes\n\n";
    }

    // 5. Tester une requête de confirmation
    echo "4. Test de la requête de confirmation (sans exécution):\n";
    $testSql = "
        UPDATE purchases 
        SET payment_status = 'completed', 
            session_status = 'pending',
            confirmed_by = 1,
            confirmed_at = NOW(),
            updated_at = NOW()
        WHERE id = 999999
    ";
    
    try {
        $stmt = $pdo->prepare($testSql);
        echo "✅ Requête SQL valide\n\n";
    } catch (PDOException $e) {
        echo "❌ Erreur dans la requête: " . $e->getMessage() . "\n\n";
    }

    // 6. Compter les achats par statut
    echo "5. Statistiques des achats:\n";
    $stmt = $pdo->query("
        SELECT payment_status, COUNT(*) as count 
        FROM purchases 
        GROUP BY payment_status
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($stats)) {
        echo "  Aucun achat dans la base de données\n";
    } else {
        foreach ($stats as $stat) {
            printf("  - %s: %d achat(s)\n", $stat['payment_status'], $stat['count']);
        }
    }
    echo "\n";

    echo "✅ DIAGNOSTIC TERMINÉ\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
