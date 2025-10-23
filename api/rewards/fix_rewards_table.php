<?php
// Script pour corriger la structure de la table rewards
require_once __DIR__ . '/../config.php';

echo "=== CORRECTION DE LA TABLE REWARDS ===\n\n";

$pdo = get_db();

// Vérifier les colonnes existantes
echo "1. Vérification de la structure actuelle...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM rewards");
$existingColumns = [];
while ($row = $stmt->fetch()) {
    $existingColumns[] = $row['Field'];
    echo "   - {$row['Field']} ({$row['Type']})\n";
}

echo "\n2. Ajout des colonnes manquantes...\n";

$columnsToAdd = [
    'category' => "ALTER TABLE rewards ADD COLUMN category VARCHAR(100) NULL AFTER cost",
    'stock_quantity' => "ALTER TABLE rewards ADD COLUMN stock_quantity INT NULL AFTER available",
    'max_per_user' => "ALTER TABLE rewards ADD COLUMN max_per_user INT NULL AFTER stock_quantity",
    'is_featured' => "ALTER TABLE rewards ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER max_per_user",
    'display_order' => "ALTER TABLE rewards ADD COLUMN display_order INT DEFAULT 0 AFTER is_featured",
    'image_url' => "ALTER TABLE rewards ADD COLUMN image_url VARCHAR(500) NULL AFTER display_order"
];

foreach ($columnsToAdd as $column => $sql) {
    if (!in_array($column, $existingColumns)) {
        try {
            $pdo->exec($sql);
            echo "   ✓ Colonne '$column' ajoutée\n";
        } catch (Exception $e) {
            echo "   ⚠️  Erreur lors de l'ajout de '$column': " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ℹ️  Colonne '$column' déjà existante\n";
    }
}

// Vérifier la table reward_redemptions
echo "\n3. Vérification de la table reward_redemptions...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM reward_redemptions");
$redemptionColumns = [];
while ($row = $stmt->fetch()) {
    $redemptionColumns[] = $row['Field'];
}

// Ajouter la colonne status si elle n'existe pas
if (!in_array('status', $redemptionColumns)) {
    try {
        $pdo->exec("ALTER TABLE reward_redemptions ADD COLUMN status ENUM('pending', 'approved', 'delivered', 'cancelled') DEFAULT 'pending' AFTER cost");
        echo "   ✓ Colonne 'status' ajoutée\n";
    } catch (Exception $e) {
        echo "   ⚠️  Erreur: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ℹ️  Colonne 'status' déjà existante\n";
}

// Ajouter la colonne notes si elle n'existe pas
if (!in_array('notes', $redemptionColumns)) {
    try {
        $pdo->exec("ALTER TABLE reward_redemptions ADD COLUMN notes TEXT NULL AFTER status");
        echo "   ✓ Colonne 'notes' ajoutée\n";
    } catch (Exception $e) {
        echo "   ⚠️  Erreur: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ℹ️  Colonne 'notes' déjà existante\n";
}

// Ajouter la colonne updated_at si elle n'existe pas
if (!in_array('updated_at', $redemptionColumns)) {
    try {
        $pdo->exec("ALTER TABLE reward_redemptions ADD COLUMN updated_at DATETIME NULL");
        echo "   ✓ Colonne 'updated_at' ajoutée\n";
    } catch (Exception $e) {
        echo "   ⚠️  Erreur: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ℹ️  Colonne 'updated_at' déjà existante\n";
}

echo "\n=== CORRECTION TERMINÉE ===\n";
echo "✅ La table rewards a été mise à jour avec succès!\n";
