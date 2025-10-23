<?php
// Test CORS simple
header('Access-Control-Allow-Origin: http://localhost:4000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'CORS fonctionne!',
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'none',
    'method' => $_SERVER['REQUEST_METHOD']
]);
