<?php
/**
 * Script pour corriger les problèmes d'encodage dans la base de données
 * Convertit toutes les tables en utf8mb4_unicode_ci
 */

require_once __DIR__ . '/api/config.php';

echo "=== Correction des problèmes d'encodage ===\n\n";

$pdo = get_db();

try {
    // Obtenir le nom de la base de données
    $dbName = 'gamezone';
    
    echo "Base de données: $dbName\n\n";
    
    // Convertir la base de données
    echo "Conversion de la base de données en utf8mb4...\n";
    $pdo->exec("ALTER DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Base de données convertie\n\n";
    
    // Obtenir toutes les tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Conversion de " . count($tables) . " tables...\n";
    
    foreach ($tables as $table) {
        echo "  - $table... ";
        
        try {
            // Convertir la table
            $pdo->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✓\n";
        } catch (PDOException $e) {
            echo "❌ Erreur: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✓ Conversion terminée avec succès!\n\n";
    
    echo "Vérification de l'encodage...\n";
    
    // Vérifier l'encodage de quelques tables importantes
    $importantTables = ['users', 'games', 'content', 'rewards', 'tournaments'];
    
    foreach ($importantTables as $table) {
        if (in_array($table, $tables)) {
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (strpos($result['Create Table'], 'utf8mb4_unicode_ci') !== false) {
                echo "  ✓ $table: utf8mb4_unicode_ci\n";
            } else {
                echo "  ❌ $table: encodage incorrect\n";
            }
        }
    }
    
    echo "\n=== Correction terminée ===\n";
    echo "\nRecommandations:\n";
    echo "1. Vérifiez que vos fichiers PHP sont encodés en UTF-8 sans BOM\n";
    echo "2. Assurez-vous que votre serveur web utilise UTF-8\n";
    echo "3. Testez l'affichage des caractères accentués dans l'application\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
