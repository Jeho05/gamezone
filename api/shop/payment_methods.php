<?php
// api/shop/payment_methods.php
// API publique pour récupérer les méthodes de paiement disponibles

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();

require_method(['GET']);

// Récupérer toutes les méthodes de paiement actives
$stmt = $pdo->query('
    SELECT id, name, slug, provider, requires_online_payment, instructions, display_order
    FROM payment_methods
    WHERE is_active = 1
    ORDER BY display_order ASC, name ASC
');

$methods = $stmt->fetchAll();

json_response(['payment_methods' => $methods]);
