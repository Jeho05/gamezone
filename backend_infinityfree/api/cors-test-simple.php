<?php
// MUST be first - Load CORS handler
require_once __DIR__ . '/cors.php';

// CORS Test - Absolute minimum
header("Content-Type: application/json");

echo json_encode([
    'status' => 'CORS Working',
    'timestamp' => date('Y-m-d H:i:s'),
    'message' => 'If you can read this from Vercel, CORS is working!'
]);
