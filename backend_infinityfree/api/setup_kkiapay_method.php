<?php
// Script pour créer/mettre à jour la méthode de paiement KkiaPay
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');

$pdo = get_db();

try {
    // Vérifier si une méthode KkiaPay existe déjà
    $stmt = $pdo->prepare('SELECT * FROM payment_methods WHERE provider = ? OR slug = ?');
    $stmt->execute(['kkiapay', 'kkiapay']);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Mettre à jour la méthode existante
        $stmt = $pdo->prepare('
            UPDATE payment_methods 
            SET 
                name = ?,
                slug = ?,
                provider = ?,
                requires_online_payment = 1,
                auto_confirm = 1,
                is_active = 1,
                display_order = 1,
                description = ?,
                updated_at = NOW()
            WHERE id = ?
        ');
        
        $stmt->execute([
            'KkiaPay',
            'kkiapay',
            'kkiapay',
            'Paiement via Mobile Money (Orange Money, MTN, Moov, Wave)',
            $existing['id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Méthode de paiement KkiaPay mise à jour',
            'method_id' => $existing['id'],
            'action' => 'updated'
        ]);
    } else {
        // Créer une nouvelle méthode
        $stmt = $pdo->prepare('
            INSERT INTO payment_methods (
                name, slug, provider, 
                requires_online_payment, auto_confirm, 
                is_active, display_order, description,
                fee_percentage, fee_fixed,
                created_at, updated_at
            ) VALUES (?, ?, ?, 1, 1, 1, 1, ?, 0, 0, NOW(), NOW())
        ');
        
        $stmt->execute([
            'KkiaPay',
            'kkiapay',
            'kkiapay',
            'Paiement via Mobile Money (Orange Money, MTN, Moov, Wave)'
        ]);
        
        $methodId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Méthode de paiement KkiaPay créée',
            'method_id' => $methodId,
            'action' => 'created'
        ]);
    }
    
    // Afficher toutes les méthodes de paiement
    $stmt = $pdo->query('SELECT id, name, slug, provider, is_active FROM payment_methods ORDER BY display_order');
    $methods = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'all_methods' => $methods
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
