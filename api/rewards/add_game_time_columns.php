<?php
// Ajouter les colonnes pour gérer les heures de jeu
require_once __DIR__ . '/../config.php';

echo "=== AJOUT DES COLONNES POUR HEURES DE JEU ===\n\n";

$pdo = get_db();

// Vérifier les colonnes existantes
echo "1. Vérification de la structure actuelle...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM rewards");
$existingColumns = [];
while ($row = $stmt->fetch()) {
    $existingColumns[] = $row['Field'];
}

echo "   Colonnes existantes: " . implode(', ', $existingColumns) . "\n\n";

// Ajouter les nouvelles colonnes
echo "2. Ajout des colonnes...\n";

$columnsToAdd = [
    'description' => "ALTER TABLE rewards ADD COLUMN description TEXT NULL AFTER name",
    'reward_type' => "ALTER TABLE rewards ADD COLUMN reward_type ENUM('game_time', 'discount', 'item', 'badge', 'other') DEFAULT 'other' AFTER category",
    'game_time_minutes' => "ALTER TABLE rewards ADD COLUMN game_time_minutes INT DEFAULT 0 AFTER reward_type"
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

echo "\n3. Vérification finale...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM rewards");
$finalColumns = [];
while ($row = $stmt->fetch()) {
    $finalColumns[] = $row['Field'];
}
echo "   Colonnes finales: " . implode(', ', $finalColumns) . "\n";

echo "\n=== TERMINÉ ===\n";
echo "✅ Les colonnes pour gérer les heures de jeu ont été ajoutées!\n";
