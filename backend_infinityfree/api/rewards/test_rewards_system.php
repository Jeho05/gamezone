<?php
// Script de test et de vérification du système de récompenses
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

echo "=== TEST DU SYSTÈME DE RÉCOMPENSES ===\n\n";

$pdo = get_db();

// 1. Vérifier que la table rewards existe
echo "1. Vérification de la table 'rewards'...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'rewards'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "   ❌ ERREUR: La table 'rewards' n'existe pas!\n";
        echo "   Création de la table...\n";
        
        $pdo->exec("CREATE TABLE rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            cost INT NOT NULL,
            category VARCHAR(100),
            available TINYINT(1) DEFAULT 1,
            stock_quantity INT NULL,
            max_per_user INT NULL,
            is_featured TINYINT(1) DEFAULT 0,
            display_order INT DEFAULT 0,
            image_url VARCHAR(500),
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
        
        echo "   ✓ Table 'rewards' créée avec succès\n";
    } else {
        echo "   ✓ La table 'rewards' existe\n";
    }
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Vérifier que la table reward_redemptions existe
echo "\n2. Vérification de la table 'reward_redemptions'...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'reward_redemptions'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "   ❌ ERREUR: La table 'reward_redemptions' n'existe pas!\n";
        echo "   Création de la table...\n";
        
        $pdo->exec("CREATE TABLE reward_redemptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reward_id INT NOT NULL,
            user_id INT NOT NULL,
            cost INT NOT NULL,
            status ENUM('pending', 'approved', 'delivered', 'cancelled') DEFAULT 'pending',
            notes TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME,
            FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        echo "   ✓ Table 'reward_redemptions' créée avec succès\n";
    } else {
        echo "   ✓ La table 'reward_redemptions' existe\n";
    }
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Vérifier le nombre de récompenses existantes
echo "\n3. Vérification des récompenses existantes...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM rewards");
    $result = $stmt->fetch();
    $count = $result['count'];
    
    echo "   ✓ Nombre de récompenses: $count\n";
    
    if ($count === 0) {
        echo "   ℹ️  Aucune récompense trouvée. Ajout de récompenses de démonstration...\n";
        
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO rewards (name, description, cost, category, available, is_featured, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $demoRewards = [
            ['Temps de jeu gratuit - 30 min', 'Profitez de 30 minutes de jeu gratuites', 100, 'gaming', 1, 1, 1],
            ['Temps de jeu gratuit - 1 heure', 'Profitez d\'1 heure de jeu gratuite', 180, 'gaming', 1, 1, 2],
            ['Réduction 20% - Prochain achat', 'Obtenez 20% de réduction sur votre prochain achat', 150, 'discount', 1, 0, 3],
            ['Badge Exclusif', 'Débloquez un badge exclusif pour votre profil', 250, 'cosmetic', 1, 0, 4],
            ['Pack de points bonus', 'Recevez 500 points bonus', 300, 'points', 1, 0, 5]
        ];
        
        foreach ($demoRewards as $reward) {
            $stmt->execute([$reward[0], $reward[1], $reward[2], $reward[3], $reward[4], $reward[5], $reward[6], $now, $now]);
        }
        
        echo "   ✓ " . count($demoRewards) . " récompenses de démonstration ajoutées\n";
    } else {
        // Afficher les récompenses existantes
        $stmt = $pdo->query("SELECT id, name, cost, available FROM rewards ORDER BY display_order ASC");
        $rewards = $stmt->fetchAll();
        
        echo "\n   Récompenses disponibles:\n";
        foreach ($rewards as $reward) {
            $status = $reward['available'] ? '✓' : '✗';
            echo "   $status #{$reward['id']}: {$reward['name']} ({$reward['cost']} points)\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Test de l'endpoint API GET
echo "\n4. Test de l'endpoint API GET /rewards/index.php...\n";
try {
    // Simuler une session utilisateur
    if (!isset($_SESSION['user'])) {
        // Récupérer le premier utilisateur player
        $stmt = $pdo->query("SELECT id, username, email, role, points FROM users WHERE role = 'player' LIMIT 1");
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            echo "   ✓ Session utilisateur créée: {$user['username']} ({$user['points']} points)\n";
        } else {
            echo "   ⚠️  Aucun utilisateur player trouvé pour les tests\n";
        }
    }
    
    // Capturer la sortie de l'endpoint
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    include __DIR__ . '/index.php';
    $output = ob_get_clean();
    
    $data = json_decode($output, true);
    
    if ($data && isset($data['success']) && $data['success']) {
        echo "   ✓ API fonctionne correctement\n";
        echo "   ✓ Nombre de récompenses retournées: " . $data['count'] . "\n";
        echo "   ✓ Points de l'utilisateur: " . $data['user_points'] . "\n";
    } else {
        echo "   ❌ ERREUR: Réponse API invalide\n";
        echo "   Réponse: " . substr($output, 0, 200) . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DES TESTS ===\n";
echo "\n✅ Le système de récompenses est prêt à être utilisé!\n";
echo "\nAccès:\n";
echo "- API GET: http://localhost/projet%20ismo/api/rewards/index.php\n";
echo "- API POST (redeem): http://localhost/projet%20ismo/api/rewards/redeem.php\n";
echo "- Frontend: http://localhost:4000/player/gamification (onglet Boutique)\n";
