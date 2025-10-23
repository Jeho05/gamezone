<?php
/**
 * Script d'installation complète du système
 * Crée toutes les tables nécessaires et insère les données de base
 */

require_once __DIR__ . '/api/config.php';

echo "=== Installation complète du système GameZone ===\n\n";

$pdo = get_db();

try {
    // Désactiver les vérifications de clés étrangères temporairement
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    echo "Lecture du fichier SQL de migration...\n";
    $sqlFile = __DIR__ . '/api/migrations/create_content_tables.sql';
    
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Séparer les requêtes
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
        );
        
        foreach ($statements as $statement) {
            if (trim($statement)) {
                try {
                    $pdo->exec($statement);
                    echo ".";
                } catch (PDOException $e) {
                    echo "\nErreur sur requête: " . substr($statement, 0, 50) . "...\n";
                    echo "Erreur: " . $e->getMessage() . "\n";
                }
            }
        }
        echo "\n✓ Tables créées avec succès\n\n";
    }
    
    // Vérifier et améliorer la table rewards
    echo "Amélioration de la table rewards...\n";
    
    // Vérifier si les colonnes existent déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM rewards LIKE 'category'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            ALTER TABLE rewards
            ADD COLUMN category VARCHAR(50) DEFAULT 'general' AFTER cost,
            ADD COLUMN type ENUM('physical', 'digital', 'discount', 'privilege') DEFAULT 'physical' AFTER category,
            ADD COLUMN image_url VARCHAR(500) AFTER type,
            ADD COLUMN stock_quantity INT DEFAULT NULL AFTER image_url,
            ADD COLUMN max_per_user INT DEFAULT NULL AFTER stock_quantity,
            ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER available,
            ADD COLUMN display_order INT DEFAULT 0 AFTER is_featured,
            ADD COLUMN description TEXT AFTER name
        ");
        echo "✓ Table rewards améliorée\n";
    } else {
        echo "✓ Table rewards déjà à jour\n";
    }
    
    // Vérifier et améliorer reward_redemptions
    $stmt = $pdo->query("SHOW COLUMNS FROM reward_redemptions LIKE 'status'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            ALTER TABLE reward_redemptions
            ADD COLUMN status ENUM('pending', 'approved', 'delivered', 'cancelled') DEFAULT 'pending' AFTER cost,
            ADD COLUMN redeemed_at DATETIME NOT NULL AFTER reward_id,
            ADD COLUMN updated_at DATETIME AFTER redeemed_at
        ");
        
        // Mettre à jour les anciennes entrées
        $pdo->exec("UPDATE reward_redemptions SET redeemed_at = created_at, updated_at = created_at WHERE redeemed_at IS NULL");
        
        echo "✓ Table reward_redemptions améliorée\n";
    } else {
        echo "✓ Table reward_redemptions déjà à jour\n";
    }
    
    // Insérer des récompenses de base si la table est vide
    $count = $pdo->query('SELECT COUNT(*) FROM rewards')->fetchColumn();
    if ($count == 0) {
        echo "\nInsertion des récompenses de base...\n";
        
        $rewards = [
            ['Boisson offerte', 'Une boisson gratuite de votre choix', 50, 'food_drink', 'physical', null, 100, 10, 1, 1],
            ['Snack gratuit', 'Un snack de votre choix', 75, 'food_drink', 'physical', null, 50, 5, 1, 2],
            ['1h de jeu bonus', 'Une heure de jeu supplémentaire gratuite', 100, 'gaming', 'privilege', null, null, 2, 1, 3],
            ['T-shirt GameZone', 'T-shirt officiel GameZone (taille au choix)', 500, 'merchandise', 'physical', null, 20, 1, 1, 4],
            ['Casquette GameZone', 'Casquette officielle GameZone', 400, 'merchandise', 'physical', null, 30, 1, 1, 5],
            ['Badge VIP 1 mois', 'Accès VIP pendant 1 mois avec avantages exclusifs', 1000, 'privilege', 'digital', null, null, 1, 1, 6],
            ['Porte-clés GameZone', 'Porte-clés collector GameZone', 150, 'merchandise', 'physical', null, 50, 3, 1, 7],
            ['Carte cadeau 5000 XOF', 'Carte cadeau valable sur tous les services', 2000, 'gift_card', 'digital', null, 10, 1, 1, 8],
            ['Badge Collector', 'Badge collector édition limitée', 750, 'merchandise', 'physical', null, 15, 1, 1, 9],
            ['Accès tournoi VIP', 'Inscription gratuite à un tournoi VIP', 1500, 'gaming', 'privilege', null, null, 1, 1, 10],
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO rewards (name, description, cost, category, type, image_url, stock_quantity, max_per_user, available, display_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        foreach ($rewards as $reward) {
            $stmt->execute($reward);
        }
        
        echo "✓ " . count($rewards) . " récompenses créées\n";
    }
    
    // Insérer des packages de points de base
    $count = $pdo->query('SELECT COUNT(*) FROM points_packages')->fetchColumn();
    if ($count == 0) {
        echo "\nInsertion des packages de points de base...\n";
        
        $packages = [
            ['Starter Pack', '100 points pour commencer', 100, 0, 500, 'XOF', 0, 0, 1, 1],
            ['Bronze Pack', '250 points + 10 bonus', 250, 10, 1000, 'XOF', 0, 0, 1, 2],
            ['Silver Pack', '500 points + 25 bonus', 500, 25, 1800, 'XOF', 5, 1, 1, 3],
            ['Gold Pack', '1000 points + 100 bonus', 1000, 100, 3500, 'XOF', 10, 1, 1, 4],
            ['Platinum Pack', '2500 points + 300 bonus', 2500, 300, 8000, 'XOF', 15, 1, 1, 5],
            ['Diamond Pack', '5000 points + 750 bonus', 5000, 750, 15000, 'XOF', 20, 1, 1, 6],
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO points_packages (name, description, points_amount, bonus_points, price, currency, discount_percentage, is_featured, is_active, display_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        foreach ($packages as $package) {
            $stmt->execute($package);
        }
        
        echo "✓ " . count($packages) . " packages de points créés\n";
    }
    
    // Réactiver les vérifications de clés étrangères
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "\n=== Installation terminée avec succès ! ===\n";
    echo "\nProchaines étapes:\n";
    echo "1. Créez un compte admin si ce n'est pas déjà fait\n";
    echo "2. Configurez les jeux et les packages dans l'interface admin\n";
    echo "3. Créez du contenu (news, events, etc.)\n";
    echo "4. Testez le système de points et de récompenses\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
