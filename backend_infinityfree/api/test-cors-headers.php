<?php
// Simple test to verify CORS headers are being sent
// This should be uploaded and tested at: https://ismo.gamer.gd/api/test-cors-headers.php

// Log all server variables
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'NONE';
$referer = $_SERVER['HTTP_REFERER'] ?? 'NONE';

// Manually set CORS headers FIRST
header("Access-Control-Allow-Origin: https://gamezoneismo.vercel.app");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization, X-CSRF-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

// Then set content type
header('Content-Type: application/json');

// Output debug info
echo json_encode([
    'test' => 'CORS Headers Test',
    'timestamp' => date('Y-m-d H:i:s'),
    'origin_header' => $origin,
    'referer_header' => $referer,
    'all_headers' => headers_list(),
    'config_loaded' => file_exists(__DIR__ . '/config.php'),
    'message' => 'If you see Access-Control-Allow-Origin in all_headers, CORS is working!'
], JSON_PRETTY_PRINT);
