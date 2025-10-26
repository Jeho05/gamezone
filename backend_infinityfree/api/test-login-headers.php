<?php
// test-login-headers.php - Test what headers login.php sends
// This simulates the login flow

// Manually set CORS first
header("Access-Control-Allow-Origin: https://gamezoneismo.vercel.app");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization, X-CSRF-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

// Then include config to see if it runs
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

echo json_encode([
    'test' => 'Login Headers Test',
    'config_loaded' => true,
    'headers_sent_by_script' => headers_list(),
    'message' => 'If CORS headers appear first in headers_sent_by_script, config is working!'
], JSON_PRETTY_PRINT);
