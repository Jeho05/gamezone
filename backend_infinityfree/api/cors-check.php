<?php
// cors-check.php - Diagnostic CORS simple
// IMPORTANT: Load config.php first to get CORS headers
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Afficher l'origine de la requête
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'none';

// Tester si config.php charge les headers CORS
$configLoaded = false;
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    $configLoaded = true;
}

// Vérifier les headers actuellement définis
$headers = headers_list();

echo json_encode([
    'status' => 'CORS Diagnostic',
    'timestamp' => date('Y-m-d H:i:s'),
    'request_origin' => $origin,
    'config_exists' => file_exists($configPath),
    'config_path' => $configPath,
    'headers_sent' => $headers,
    'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
], JSON_PRETTY_PRINT);
