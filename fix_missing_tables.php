<?php
// Script pour créer les tables manquantes
require_once __DIR__ . '/api/config.php';

echo "=== CRÉATION DES TABLES MANQUANTES ===\n\n";

try {
    $pdo = get_db();
    
    // Lire et exécuter la migration principale
    echo "📦 Exécution de add_game_purchase_system.sql...\n";
    $sql = file_get_contents(__DIR__ . '/api/migrations/add_game_purchase_system.sql');
    
    // Séparer les commandes SQL
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    $executed = 0;
    $errors = 0;
    
    foreach ($commands as $command) {
        if (empty($command) || strpos($command, '--') === 0) continue;
        
        try {
            $pdo->exec($command);
            $executed++;
        } catch (PDOException $e) {
            // Ignorer les erreurs "table already exists"
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "  ⚠ " . substr($e->getMessage(), 0, 100) . "...\n";
                $errors++;
            }
        }
    }
    
    echo "✓ Commandes exécutées: $executed\n";
    if ($errors > 0) echo "⚠ Erreurs ignorées: $errors\n";
    echo "\n";
    
    // Lire et exécuter la migration des factures
    echo "📦 Exécution de add_invoice_system.sql...\n";
    $sql = file_get_contents(__DIR__ . '/api/migrations/add_invoice_system.sql');
    
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    $executed = 0;
    $errors = 0;
    
    foreach ($commands as $command) {
        if (empty($command) || strpos($command, '--') === 0) continue;
        
        try {
            $pdo->exec($command);
            $executed++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "  ⚠ " . substr($e->getMessage(), 0, 100) . "...\n";
                $errors++;
            }
        }
    }
    
    echo "✓ Commandes exécutées: $executed\n";
    if ($errors > 0) echo "⚠ Erreurs ignorées: $errors\n";
    echo "\n";
    
    // Vérifier les tables créées
    echo "=== VÉRIFICATION DES TABLES ===\n";
    $tables = [
        'games', 'game_packages', 'payment_methods', 'purchases', 
        'payment_transactions', 'game_sessions', 'session_activities',
        'invoices', 'invoice_scans', 'active_game_sessions_v2', 
        'session_events', 'invoice_audit_log'
    ];
    
    $missing = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table\n";
        } else {
            echo "✗ $table MANQUANTE\n";
            $missing[] = $table;
        }
    }
    
    if (empty($missing)) {
        echo "\n✅ TOUTES LES TABLES SONT CRÉÉES !\n";
    } else {
        echo "\n⚠ Tables manquantes: " . implode(', ', $missing) . "\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
