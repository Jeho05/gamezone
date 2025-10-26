<?php
// Test simple sans dépendances
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:4000');
header('Access-Control-Allow-Credentials: true');

echo json_encode([
    'success' => true,
    'message' => 'Test simple réussi',
    'timestamp' => date('Y-m-d H:i:s')
]);
