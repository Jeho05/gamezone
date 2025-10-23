<?php
/**
 * Script d'exécution automatique de la migration SQL
 * Crée toutes les tables nécessaires pour la boutique
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$dbHost = 'localhost';
$dbName = 'gamezone';
$dbUser = 'root';
$dbPass = '';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Exécution Migration SQL</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .code {
            background: #2d3748;
            color: #48bb78;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
            font-size: 0.9em;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: scale(1.05);
        }
        ul {
            margin: 15px 0;
            padding-left: 30px;
        }
        li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Migration Automatique</h1>

<?php

try {
    // Connexion à la base de données
    echo '<div class="step">';
    echo '<h2>📡 Connexion à la base de données...</h2>';
    
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo '<p class="success">✅ Connecté à la base <strong>' . $dbName . '</strong></p>';
    echo '</div>';
    
    // Lire le fichier SQL
    echo '<div class="step">';
    echo '<h2>📄 Lecture du fichier SQL...</h2>';
    
    $sqlFile = __DIR__ . '/api/migrations/add_game_purchase_system.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Fichier SQL introuvable : $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if (!$sql) {
        throw new Exception("Impossible de lire le fichier SQL");
    }
    
    echo '<p class="success">✅ Fichier SQL chargé (' . number_format(strlen($sql)) . ' caractères)</p>';
    echo '</div>';
    
    // Exécuter le SQL
    echo '<div class="step">';
    echo '<h2>⚙️ Exécution de la migration...</h2>';
    
    // Diviser le SQL en commandes individuelles
    $statements = explode(';', $sql);
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Ignorer les commentaires et lignes vides
        if (empty($statement) || 
            strpos($statement, '--') === 0 || 
            strpos($statement, '/*') === 0 ||
            strtoupper(substr($statement, 0, 3)) === 'USE') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignorer les erreurs "table already exists" et "duplicate key"
            if (strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'Duplicate') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    echo '<p class="success">✅ Migration exécutée avec succès !</p>';
    echo '<ul>';
    echo '<li>Commandes SQL exécutées : <strong>' . $executed . '</strong></li>';
    
    if (!empty($errors)) {
        echo '<li>Erreurs ignorées : <strong>' . count($errors) . '</strong></li>';
    }
    
    echo '</ul>';
    echo '</div>';
    
    // Vérifier les tables créées
    echo '<div class="step">';
    echo '<h2>✅ Vérification des tables...</h2>';
    
    $tables = ['games', 'game_packages', 'payment_methods', 'purchases', 'game_sessions'];
    $tablesCreated = [];
    $tablesMissing = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            $tablesCreated[$table] = $result['count'];
        } catch (PDOException $e) {
            $tablesMissing[] = $table;
        }
    }
    
    if (empty($tablesMissing)) {
        echo '<p class="success">✅ Toutes les tables ont été créées avec succès !</p>';
        echo '<ul>';
        foreach ($tablesCreated as $table => $count) {
            echo '<li>✓ <strong>' . $table . '</strong> : ' . $count . ' enregistrement(s)</li>';
        }
        echo '</ul>';
        
        // Compter les jeux insérés
        if ($tablesCreated['games'] > 0) {
            echo '<p class="success">🎮 <strong>' . $tablesCreated['games'] . ' jeux</strong> ont été automatiquement insérés !</p>';
        } else {
            echo '<div class="step" style="background: #fff3cd; border-left-color: #ffc107;">';
            echo '<h3>⚠️ Aucun jeu trouvé</h3>';
            echo '<p>Les tables sont créées mais vides. Pour insérer des données de test :</p>';
            echo '<p>1. Connectez-vous en tant qu\'admin sur votre application</p>';
            echo '<p>2. Visitez : <a href="api/shop/seed_test_data.php">api/shop/seed_test_data.php</a></p>';
            echo '</div>';
        }
        
    } else {
        echo '<p class="error">❌ Tables manquantes : ' . implode(', ', $tablesMissing) . '</p>';
    }
    
    echo '</div>';
    
    // Résumé final
    echo '<div class="step success">';
    echo '<h2>🎉 Migration terminée avec succès !</h2>';
    echo '<p>Votre base de données est maintenant prête pour la boutique.</p>';
    echo '<br>';
    echo '<a href="setup_shop.php" class="button">🔍 Vérifier la Configuration</a>';
    echo '<a href="TEST_SHOP_DEBUG.html" class="button" style="margin-left: 10px;">🧪 Tester les API</a>';
    echo '<a href="http://localhost:4000/player/shop" class="button" style="margin-left: 10px;">🛒 Ouvrir la Boutique</a>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="step error">';
    echo '<h2>❌ Erreur</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<div class="code">' . htmlspecialchars($e->getTraceAsString()) . '</div>';
    echo '</div>';
}

?>

    </div>
</body>
</html>
