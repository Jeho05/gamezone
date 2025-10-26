<?php
// Test simple pour voir l'erreur exacte
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo json_encode([
    'test' => 'API rewards accessible',
    'timestamp' => date('Y-m-d H:i:s')
]);
