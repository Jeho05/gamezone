<?php
/**
 * Page d'accueil du backend GameZone
 * Redirige vers /api/health.php pour vérifier que l'API fonctionne
 */

header('Content-Type: application/json');

echo json_encode([
    'status' => 'online',
    'message' => 'GameZone Backend API',
    'version' => '1.0',
    'endpoints' => [
        'health' => '/api/health.php',
        'auth' => '/api/auth/check.php',
        'test' => '/api/test.php'
    ],
    'documentation' => 'Pour tester l\'API, visitez /api/health.php'
]);
