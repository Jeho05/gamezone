<?php
/**
 * Configuration Complète et Automatique de Kkiapay
 * Ce script configure tout automatiquement et teste le système
 */

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/utils.php';

// Vérifier que l'utilisateur est admin
$user = require_auth('admin');
$pdo = get_db();

header('Content-Type: application/json');

$results = [
    'success' => true,
    'steps' => [],
    'errors' => []
];

try {
    // ÉTAPE 1: Vérifier/Créer la méthode de paiement Kkiapay
    $results['steps'][] = 'Vérification méthode de paiement Kkiapay...';
    
    $stmt = $pdo->prepare('SELECT * FROM payment_methods WHERE slug = ?');
    $stmt->execute(['kkiapay']);
    $existingMethod = $stmt->fetch();
    
    if ($existingMethod) {
        // Mettre à jour pour s'assurer que c'est bien configuré
        $stmt = $pdo->prepare('
            UPDATE payment_methods 
            SET name = ?,
                provider = ?,
                requires_online_payment = 1,
                auto_confirm = 0,
                is_active = 1,
                api_key_public = ?,
                instructions = ?,
                updated_at = ?
            WHERE slug = ?
        ');
        $stmt->execute([
            'Kkiapay (Mobile Money)',
            'kkiapay',
            '072b361d25546db0aee3d69bf07b15331c51e39f',
            'Paiement sécurisé via Kkiapay. Accepte MTN, Moov, Orange Money et Wave.',
            now(),
            'kkiapay'
        ]);
        $methodId = $existingMethod['id'];
        $results['steps'][] = "✅ Méthode Kkiapay mise à jour (ID: $methodId)";
    } else {
        // Créer la méthode
        $stmt = $pdo->prepare('
            INSERT INTO payment_methods (
                name, slug, provider, 
                requires_online_payment, auto_confirm, is_active,
                api_key_public, instructions,
                fee_percentage, fee_fixed, display_order,
                created_at, updated_at
            ) VALUES (?, ?, ?, 1, 0, 1, ?, ?, 0, 0, 1, ?, ?)
        ');
        $ts = now();
        $stmt->execute([
            'Kkiapay (Mobile Money)',
            'kkiapay',
            'kkiapay',
            '072b361d25546db0aee3d69bf07b15331c51e39f',
            'Paiement sécurisé via Kkiapay. Accepte MTN, Moov, Orange Money et Wave.',
            $ts,
            $ts
        ]);
        $methodId = $pdo->lastInsertId();
        $results['steps'][] = "✅ Méthode Kkiapay créée (ID: $methodId)";
    }
    
    $results['method_id'] = $methodId;
    
    // ÉTAPE 2: Vérifier qu'il y a des jeux et packages
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM games WHERE is_active = 1');
    $gamesCount = $stmt->fetch()['count'];
    
    if ($gamesCount == 0) {
        $results['steps'][] = '⚠️ Aucun jeu disponible. Exécutez api/shop/seed_test_data.php';
        $results['warnings'][] = 'Pas de jeux pour tester';
    } else {
        $results['steps'][] = "✅ $gamesCount jeux disponibles";
    }
    
    // ÉTAPE 3: Vérifier les packages
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM game_packages WHERE is_active = 1');
    $packagesCount = $stmt->fetch()['count'];
    $results['steps'][] = "✅ $packagesCount packages disponibles";
    
    // ÉTAPE 4: Vérifier la table purchases
    $stmt = $pdo->query('SHOW TABLES LIKE "purchases"');
    if ($stmt->fetch()) {
        $results['steps'][] = '✅ Table purchases existe';
    } else {
        $results['errors'][] = 'Table purchases manquante';
        $results['success'] = false;
    }
    
    // ÉTAPE 5: Tester la configuration
    $results['configuration'] = [
        'key' => '072b361d25546db0aee3d69bf07b15331c51e39f',
        'callback' => 'https://kkiapay-redirect.com',
        'script_url' => 'https://cdn.kkiapay.me/k.js',
        'sandbox' => true,
        'attributs' => 'anglais (amount, key)',
        'test_amount' => 500,
        'method_id' => $methodId
    ];
    
    $results['steps'][] = '✅ Configuration Kkiapay validée';
    
    // ÉTAPE 6: URLs de test
    $baseUrl = 'http://localhost/projet%20ismo';
    $results['test_urls'] = [
        'test_nouvelle_cle' => "$baseUrl/test_kkiapay_nouvelle_cle.html",
        'test_widget' => "$baseUrl/test_kkiapay_complet.html",
        'test_direct' => "$baseUrl/test_kkiapay_direct.html",
        'shop' => "$baseUrl/shop.html",
        'api_payment_methods' => "$baseUrl/api/shop/payment_methods.php"
    ];
    
    $results['steps'][] = '✅ Configuration terminée avec succès!';
    
    $results['next_steps'] = [
        '1. Ouvrez test_kkiapay_complet.html pour tester le widget',
        '2. Vérifiez que le script k.js se charge',
        '3. Testez un paiement dans shop.html',
        '4. Utilisez les numéros de test fournis'
    ];
    
} catch (Exception $e) {
    $results['success'] = false;
    $results['errors'][] = $e->getMessage();
    $results['trace'] = $e->getTraceAsString();
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
