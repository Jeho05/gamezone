<?php
/**
 * Endpoint de Health Check Complet
 * Vérifie la santé de tous les composants du système
 */

require_once __DIR__ . '/monitoring.php';

header('Content-Type: application/json');

$monitor = new SystemMonitor();
$health = $monitor->healthCheck();

// Ajouter des vérifications supplémentaires
$health['version'] = '1.0';
$health['environment'] = 'production';

// Vérifier les endpoints critiques
$criticalEndpoints = [
    'auth' => __DIR__ . '/auth/login.php',
    'shop' => __DIR__ . '/shop/games.php',
    'admin' => __DIR__ . '/admin/dashboard_stats.php'
];

$health['checks']['endpoints'] = [];
foreach ($criticalEndpoints as $name => $file) {
    $health['checks']['endpoints'][$name] = file_exists($file) ? 'ok' : 'missing';
}

// Code de statut HTTP selon la santé
if ($health['status'] === 'healthy') {
    http_response_code(200);
} elseif ($health['status'] === 'warning') {
    http_response_code(200); // Warning mais toujours opérationnel
} else {
    http_response_code(503); // Service Unavailable
}

echo json_encode($health, JSON_PRETTY_PRINT);
