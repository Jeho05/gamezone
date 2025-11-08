<?php
/**
 * Script d'import de gamezone.sql vers Railway MySQL
 * Utilise PDO pour contourner les problèmes de compatibilité
 */

set_time_limit(300); // 5 minutes max
ini_set('memory_limit', '512M');

// Credentials Railway
$host = 'gondola.proxy.rlwy.net';
$port = 24653;
$user = 'root';
$password = 'lLNQgXguqytlIMQoXZPjdJJsmyJkheUM';
$database = 'railway';

$sqlFile = __DIR__ . '/gamezone.sql';

echo "============================================\n";
echo "  IMPORT VIA PHP vers RAILWAY MySQL\n";
echo "============================================\n\n";

echo "Host: {$host}:{$port}\n";
echo "Database: {$database}\n";
echo "Fichier: {$sqlFile}\n\n";

// Vérifier que le fichier existe
if (!file_exists($sqlFile)) {
    die("ERREUR: Le fichier gamezone.sql est introuvable!\n");
}

echo "Connexion à Railway MySQL...\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "✓ Connecté avec succès!\n\n";
    
    echo "Lecture du fichier SQL...\n";
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        die("ERREUR: Impossible de lire le fichier SQL!\n");
    }
    
    echo "✓ Fichier lu (" . round(strlen($sql) / 1024, 2) . " KB)\n\n";
    
    echo "Import en cours...\n";
    echo "Cela peut prendre 1-2 minutes, patience...\n\n";
    
    // Désactiver les vérifications pour accélérer l'import
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("SET UNIQUE_CHECKS = 0");
    $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    
    // Diviser le SQL en commandes individuelles
    $statements = preg_split('/;\s*$/m', $sql, -1, PREG_SPLIT_NO_EMPTY);
    
    $total = count($statements);
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        
        // Ignorer les commentaires et lignes vides
        if (empty($statement) || 
            strpos($statement, '--') === 0 || 
            strpos($statement, '/*') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $success++;
            
            // Afficher la progression tous les 50 statements
            if ($success % 50 === 0) {
                $percent = round(($index / $total) * 100);
                echo "  → {$percent}% ({$success} requêtes exécutées)\n";
            }
        } catch (PDOException $e) {
            $errors++;
            // Afficher seulement les vraies erreurs (pas les warnings)
            if (strpos($e->getMessage(), 'Duplicate entry') === false &&
                strpos($e->getMessage(), 'already exists') === false) {
                echo "  ⚠ Avertissement: " . substr($e->getMessage(), 0, 100) . "...\n";
            }
        }
    }
    
    // Réactiver les vérifications
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    $pdo->exec("SET UNIQUE_CHECKS = 1");
    
    echo "\n============================================\n";
    echo "  ✓ IMPORT TERMINÉ!\n";
    echo "============================================\n\n";
    echo "Statistiques:\n";
    echo "  - Requêtes exécutées: {$success}\n";
    echo "  - Avertissements: {$errors}\n\n";
    echo "Prochaines étapes:\n";
    echo "1. Va sur: https://gamezoneismo.vercel.app/admin/dashboard\n";
    echo "2. Connecte-toi avec tes identifiants\n";
    echo "3. Tout devrait fonctionner maintenant!\n\n";
    
} catch (PDOException $e) {
    echo "\n============================================\n";
    echo "  ✗ ERREUR DE CONNEXION\n";
    echo "============================================\n\n";
    echo "Message: " . $e->getMessage() . "\n\n";
    echo "Vérifie:\n";
    echo "- Que les credentials Railway sont corrects\n";
    echo "- Que ta connexion internet fonctionne\n";
    echo "- Que le firewall n'est pas bloqué\n\n";
    exit(1);
}
