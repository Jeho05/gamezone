<?php
/**
 * Script de Configuration Automatique de la Boutique
 * Exécute toutes les vérifications et configurations nécessaires
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$dbHost = 'localhost';
$dbName = 'gamezone';
$dbUser = 'root';
$dbPass = '';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Configuration Boutique - GameZone</title>
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
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .step h2 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .success h2 { color: #28a745; }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .error h2 { color: #dc3545; }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .warning h2 { color: #ffc107; }
        .code {
            background: #2d3748;
            color: #48bb78;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
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
        .icon {
            font-size: 1.5em;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Configuration Automatique</h1>
        <p class="subtitle">Configuration et vérification du système de boutique</p>

<?php

// Étape 1 : Connexion à la base de données
echo '<div class="step">';
echo '<h2><span class="icon">1️⃣</span>Connexion à la Base de Données</h2>';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    echo '<p>✅ Connexion réussie à la base de données <strong>' . $dbName . '</strong></p>';
    echo '</div>';
    $dbConnected = true;
} catch (PDOException $e) {
    echo '<p class="error">❌ Erreur de connexion : ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<div class="code">Vérifiez que MySQL est démarré dans XAMPP</div>';
    echo '</div>';
    $dbConnected = false;
}

if (!$dbConnected) {
    echo '</div></body></html>';
    exit;
}

// Étape 2 : Vérification des tables
echo '<div class="step">';
echo '<h2><span class="icon">2️⃣</span>Vérification des Tables</h2>';

$requiredTables = ['games', 'game_packages', 'payment_methods', 'purchases', 'game_sessions'];
$missingTables = [];
$existingTables = [];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        $existingTables[] = $table;
    } catch (PDOException $e) {
        $missingTables[] = $table;
    }
}

if (empty($missingTables)) {
    echo '<p>✅ Toutes les tables requises existent :</p>';
    echo '<ul>';
    foreach ($existingTables as $table) {
        echo '<li>✓ ' . $table . '</li>';
    }
    echo '</ul>';
    echo '</div>';
    $tablesOk = true;
} else {
    echo '<p class="warning">⚠️ Tables manquantes détectées :</p>';
    echo '<ul>';
    foreach ($missingTables as $table) {
        echo '<li>✗ ' . $table . '</li>';
    }
    echo '</ul>';
    echo '<div class="code">';
    echo '📝 Action requise :<br>';
    echo '1. Ouvrez phpMyAdmin<br>';
    echo '2. Sélectionnez la base "gamezone"<br>';
    echo '3. Cliquez sur SQL<br>';
    echo '4. Exécutez le fichier: api/migrations/add_game_purchase_system.sql';
    echo '</div>';
    echo '</div>';
    $tablesOk = false;
}

// Étape 3 : Vérification des données
if ($tablesOk) {
    echo '<div class="step">';
    echo '<h2><span class="icon">3️⃣</span>Vérification des Données</h2>';
    
    $counts = [];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        $counts[$table] = $result['count'];
    }
    
    if ($counts['games'] == 0) {
        echo '<p class="warning">⚠️ Aucun jeu dans la base de données</p>';
        echo '<div class="code">';
        echo '📝 Pour insérer des données de test :<br>';
        echo '1. Connectez-vous en tant qu\'admin<br>';
        echo '2. Visitez: <a href="api/shop/seed_test_data.php" target="_blank">api/shop/seed_test_data.php</a>';
        echo '</div>';
    } else {
        echo '<p>✅ Base de données peuplée :</p>';
        echo '<ul>';
        echo '<li>🎮 <strong>' . $counts['games'] . '</strong> jeux</li>';
        echo '<li>📦 <strong>' . $counts['game_packages'] . '</strong> packages</li>';
        echo '<li>💳 <strong>' . $counts['payment_methods'] . '</strong> méthodes de paiement</li>';
        echo '<li>🛍️ <strong>' . $counts['purchases'] . '</strong> achats</li>';
        echo '<li>🎯 <strong>' . $counts['game_sessions'] . '</strong> sessions</li>';
        echo '</ul>';
    }
    echo '</div>';
}

// Étape 4 : Vérification des fichiers API
echo '<div class="step">';
echo '<h2><span class="icon">4️⃣</span>Vérification des Fichiers API</h2>';

$apiFiles = [
    'api/shop/games.php',
    'api/shop/my_purchases.php',
    'api/shop/create_purchase.php',
    'api/shop/payment_methods.php'
];

$missingFiles = [];
$existingFiles = [];

foreach ($apiFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $existingFiles[] = $file;
    } else {
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    echo '<p>✅ Tous les fichiers API sont présents</p>';
} else {
    echo '<p class="error">❌ Fichiers manquants :</p>';
    echo '<ul>';
    foreach ($missingFiles as $file) {
        echo '<li>✗ ' . $file . '</li>';
    }
    echo '</ul>';
}
echo '</div>';

// Étape 5 : Test des endpoints
if ($tablesOk && empty($missingFiles)) {
    echo '<div class="step success">';
    echo '<h2><span class="icon">5️⃣</span>Tests des Endpoints</h2>';
    echo '<p>✅ Tous les composants sont prêts !</p>';
    echo '<p>Vous pouvez tester les endpoints suivants :</p>';
    echo '<ul>';
    echo '<li>🎮 <a href="api/shop/games.php" target="_blank">api/shop/games.php</a> - Liste des jeux</li>';
    echo '<li>💳 <a href="api/shop/payment_methods.php" target="_blank">api/shop/payment_methods.php</a> - Méthodes de paiement</li>';
    echo '<li>🛍️ <a href="api/shop/my_purchases.php" target="_blank">api/shop/my_purchases.php</a> - Mes achats (nécessite connexion)</li>';
    echo '</ul>';
    echo '</div>';
}

// Résumé final
echo '<div class="step ' . ($tablesOk && empty($missingFiles) && $counts['games'] > 0 ? 'success' : 'warning') . '">';
echo '<h2><span class="icon">🎯</span>Résumé</h2>';

if ($tablesOk && empty($missingFiles) && $counts['games'] > 0) {
    echo '<p><strong>🎉 Configuration complète et opérationnelle !</strong></p>';
    echo '<p>Votre boutique est prête à être utilisée.</p>';
    echo '<a href="http://localhost:4000/player/shop" class="button">🚀 Ouvrir la Boutique</a>';
    echo '<a href="TEST_SHOP_DEBUG.html" class="button" style="margin-left: 10px;">🔧 Outils de Test</a>';
} else {
    echo '<p><strong>⚠️ Configuration incomplète</strong></p>';
    echo '<p>Veuillez suivre les instructions ci-dessus pour terminer la configuration.</p>';
    echo '<a href="?" class="button">🔄 Recharger la vérification</a>';
}

echo '</div>';

?>

        <div class="step">
            <h2><span class="icon">📚</span>Documentation</h2>
            <p>Pour plus d'informations, consultez :</p>
            <ul>
                <li>📖 <a href="FIX_SHOP_ERRORS.md" target="_blank">Guide de Résolution des Erreurs</a></li>
                <li>🧪 <a href="TEST_SHOP_DEBUG.html" target="_blank">Page de Diagnostic</a></li>
                <li>📊 <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
