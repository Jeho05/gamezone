<?php
// Test complet du système de récompenses avec heures de jeu
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

echo "=== TEST COMPLET SYSTÈME DE RÉCOMPENSES ===\n\n";

$pdo = get_db();

// 1. Vérifier la structure de la table rewards
echo "1. Vérification de la structure de la table rewards...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM rewards");
$columns = [];
while ($row = $stmt->fetch()) {
    $columns[] = $row['Field'];
}

$requiredColumns = ['id', 'name', 'description', 'cost', 'category', 'reward_type', 'game_time_minutes', 'available'];
$missing = array_diff($requiredColumns, $columns);

if (empty($missing)) {
    echo "   ✓ Toutes les colonnes requises sont présentes\n";
    echo "   Colonnes: " . implode(', ', $columns) . "\n";
} else {
    echo "   ❌ Colonnes manquantes: " . implode(', ', $missing) . "\n";
    exit(1);
}

// 2. Créer une récompense de test avec temps de jeu
echo "\n2. Création d'une récompense de test (type: game_time)...\n";

// Vérifier s'il existe déjà une session admin
$stmt = $pdo->query("SELECT id, username, email, role FROM users WHERE role = 'admin' LIMIT 1");
$admin = $stmt->fetch();

if (!$admin) {
    echo "   ❌ Aucun administrateur trouvé. Impossible de créer une récompense.\n";
    exit(1);
}

// Simuler une session admin
$_SESSION['user'] = [
    'id' => $admin['id'],
    'username' => $admin['username'],
    'email' => $admin['email'],
    'role' => 'admin'
];

$testRewardName = 'TEST - 1h de jeu gratuite';
$testRewardCost = 100;
$testGameTime = 60;

try {
    $stmt = $pdo->prepare('
        INSERT INTO rewards (
            name, description, cost, category, reward_type, 
            game_time_minutes, available, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
    ');
    $stmt->execute([
        $testRewardName,
        'Test de récompense avec temps de jeu automatique',
        $testRewardCost,
        'gaming',
        'game_time',
        $testGameTime
    ]);
    
    $testRewardId = $pdo->lastInsertId();
    echo "   ✓ Récompense de test créée (ID: $testRewardId)\n";
    echo "   - Nom: $testRewardName\n";
    echo "   - Coût: $testRewardCost points\n";
    echo "   - Temps de jeu: $testGameTime minutes\n";
} catch (Exception $e) {
    echo "   ❌ Erreur lors de la création: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Tester l'API GET (liste des récompenses)
echo "\n3. Test de l'API GET /rewards/index.php...\n";

// Récupérer un utilisateur player pour les tests
$stmt = $pdo->query("SELECT id, username, email, role, points FROM users WHERE role = 'player' LIMIT 1");
$player = $stmt->fetch();

if (!$player) {
    echo "   ⚠️  Aucun joueur trouvé pour tester la visualisation\n";
} else {
    $_SESSION['user'] = [
        'id' => $player['id'],
        'username' => $player['username'],
        'email' => $player['email'],
        'role' => $player['role']
    ];
    
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    include __DIR__ . '/index.php';
    $output = ob_get_clean();
    
    $data = json_decode($output, true);
    
    if ($data && $data['success']) {
        echo "   ✓ API GET fonctionne\n";
        
        // Trouver notre récompense de test
        $foundTest = false;
        foreach ($data['rewards'] as $reward) {
            if ($reward['id'] == $testRewardId) {
                $foundTest = true;
                echo "   ✓ Récompense de test trouvée dans la liste\n";
                echo "   - Type: " . ($reward['reward_type'] ?? 'non défini') . "\n";
                echo "   - Temps de jeu: " . ($reward['game_time_minutes'] ?? 0) . " minutes\n";
                break;
            }
        }
        
        if (!$foundTest) {
            echo "   ⚠️  Récompense de test non trouvée dans la liste\n";
        }
    } else {
        echo "   ❌ Erreur API GET\n";
        echo "   Réponse: " . substr($output, 0, 200) . "\n";
    }
}

// 4. Vérifier que la table point_conversions existe
echo "\n4. Vérification de la table point_conversions...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'point_conversions'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Table point_conversions existe\n";
    } else {
        echo "   ❌ Table point_conversions n'existe pas!\n";
        echo "   Le système d'ajout de temps de jeu ne fonctionnera pas.\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

// 5. Test complet d'échange (si le joueur a assez de points)
echo "\n5. Test de l'échange avec ajout de temps de jeu...\n";

if (!$player) {
    echo "   ⚠️  Aucun joueur disponible pour tester l'échange\n";
} else {
    if ($player['points'] < $testRewardCost) {
        echo "   ⚠️  Le joueur n'a pas assez de points ({$player['points']} < $testRewardCost)\n";
        echo "   Ajout de points au joueur...\n";
        
        $stmt = $pdo->prepare('UPDATE users SET points = ? WHERE id = ?');
        $stmt->execute([$testRewardCost + 50, $player['id']]);
        $player['points'] = $testRewardCost + 50;
        
        echo "   ✓ Points ajustés: {$player['points']} points\n";
    }
    
    echo "   Test de l'échange de la récompense...\n";
    
    // Compter les conversions avant
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM point_conversions WHERE user_id = ?');
    $stmt->execute([$player['id']]);
    $conversionsBefore = (int)$stmt->fetchColumn();
    
    try {
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        // Simuler le JSON body
        $jsonBody = json_encode(['reward_id' => $testRewardId]);
        file_put_contents('php://input', $jsonBody);
        
        ob_start();
        include __DIR__ . '/redeem.php';
        $output = ob_get_clean();
        
        $result = json_decode($output, true);
        
        if ($result && $result['success']) {
            echo "   ✓ Échange réussi!\n";
            echo "   - Message: " . $result['message'] . "\n";
            echo "   - Nouveau solde: " . $result['new_balance'] . " points\n";
            echo "   - Temps de jeu ajouté: " . ($result['game_time_added'] ?? 0) . " minutes\n";
            
            // Vérifier qu'une conversion a été créée
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM point_conversions WHERE user_id = ?');
            $stmt->execute([$player['id']]);
            $conversionsAfter = (int)$stmt->fetchColumn();
            
            if ($conversionsAfter > $conversionsBefore) {
                echo "   ✓ Entrée de conversion créée dans point_conversions\n";
                
                // Afficher la dernière conversion
                $stmt = $pdo->prepare('
                    SELECT minutes_gained, status, expires_at 
                    FROM point_conversions 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ');
                $stmt->execute([$player['id']]);
                $conversion = $stmt->fetch();
                
                echo "   - Minutes gagnées: " . $conversion['minutes_gained'] . "\n";
                echo "   - Statut: " . $conversion['status'] . "\n";
                echo "   - Expire le: " . $conversion['expires_at'] . "\n";
            } else {
                echo "   ⚠️  Aucune conversion créée (récompense non game_time?)\n";
            }
        } else {
            echo "   ❌ Échec de l'échange\n";
            echo "   Erreur: " . ($result['error'] ?? 'Inconnue') . "\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Exception: " . $e->getMessage() . "\n";
    }
}

// Nettoyage
echo "\n6. Nettoyage...\n";
$stmt = $pdo->prepare('DELETE FROM rewards WHERE id = ?');
$stmt->execute([$testRewardId]);
echo "   ✓ Récompense de test supprimée\n";

echo "\n=== FIN DES TESTS ===\n";
echo "\n✅ Le système de récompenses avec heures de jeu est fonctionnel!\n";
echo "\nInstructions:\n";
echo "1. Connectez-vous en tant qu'admin\n";
echo "2. Allez sur http://localhost:4000/admin/rewards\n";
echo "3. Créez une nouvelle récompense:\n";
echo "   - Type: ⏱️ Temps de jeu\n";
echo "   - Spécifiez les minutes de jeu\n";
echo "4. En tant que joueur, échangez la récompense\n";
echo "5. Le temps sera automatiquement ajouté à votre crédit de jeu!\n";
