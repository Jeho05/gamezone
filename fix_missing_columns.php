<?php
// Corriger les colonnes manquantes
require_once __DIR__ . '/api/config.php';

echo "=== CORRECTION DES COLONNES MANQUANTES ===\n\n";

try {
    $pdo = get_db();
    
    // 1. Vérifier et ajouter la colonne 'points' dans users
    echo "📦 Vérification colonne 'points' dans users...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'points'");
    if ($stmt->rowCount() == 0) {
        echo "  → Colonne manquante, ajout en cours...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN points INT NOT NULL DEFAULT 0 AFTER role");
        echo "✓ Colonne 'points' ajoutée\n\n";
    } else {
        echo "✓ Colonne 'points' existe déjà\n\n";
    }
    
    // 2. Vérifier et ajouter la colonne 'auto_confirm' dans payment_methods
    echo "📦 Vérification colonne 'auto_confirm' dans payment_methods...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_methods LIKE 'auto_confirm'");
    if ($stmt->rowCount() == 0) {
        echo "  → Colonne manquante, ajout en cours...\n";
        $pdo->exec("ALTER TABLE payment_methods ADD COLUMN auto_confirm TINYINT(1) NOT NULL DEFAULT 0 AFTER requires_online_payment");
        echo "✓ Colonne 'auto_confirm' ajoutée\n\n";
    } else {
        echo "✓ Colonne 'auto_confirm' existe déjà\n\n";
    }
    
    // 3. Vérifier d'autres colonnes importantes
    echo "📦 Vérification colonnes supplémentaires...\n";
    
    // requires_online_payment
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_methods LIKE 'requires_online_payment'");
    if ($stmt->rowCount() == 0) {
        echo "  → Ajout 'requires_online_payment'...\n";
        $pdo->exec("ALTER TABLE payment_methods ADD COLUMN requires_online_payment TINYINT(1) NOT NULL DEFAULT 1");
        echo "✓ Colonne 'requires_online_payment' ajoutée\n";
    }
    
    // fee_percentage et fee_fixed
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_methods LIKE 'fee_percentage'");
    if ($stmt->rowCount() == 0) {
        echo "  → Ajout 'fee_percentage'...\n";
        $pdo->exec("ALTER TABLE payment_methods ADD COLUMN fee_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00");
        echo "✓ Colonne 'fee_percentage' ajoutée\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_methods LIKE 'fee_fixed'");
    if ($stmt->rowCount() == 0) {
        echo "  → Ajout 'fee_fixed'...\n";
        $pdo->exec("ALTER TABLE payment_methods ADD COLUMN fee_fixed DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        echo "✓ Colonne 'fee_fixed' ajoutée\n";
    }
    
    echo "\n";
    
    // 4. Mettre à jour les users existants avec des points par défaut
    echo "📦 Mise à jour des users existants...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE points IS NULL");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        $pdo->exec("UPDATE users SET points = 0 WHERE points IS NULL");
        echo "✓ {$result['count']} utilisateurs mis à jour avec points=0\n\n";
    } else {
        echo "✓ Tous les utilisateurs ont des points\n\n";
    }
    
    // 5. Afficher la structure finale des tables
    echo "=== STRUCTURE FINALE ===\n\n";
    
    echo "Table users:\n";
    $stmt = $pdo->query("DESCRIBE users");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\nTable payment_methods:\n";
    $stmt = $pdo->query("DESCRIBE payment_methods");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n✅ TOUTES LES COLONNES SONT CORRIGÉES !\n";
    echo "\n🎉 Vous pouvez maintenant créer des achats depuis React !\n";
    
} catch (Exception $e) {
    echo "\n✗ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
