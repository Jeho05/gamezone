<?php
/**
 * CORS Handler - MUST be included FIRST in every PHP file
 * This MUST be the very first thing executed
 */

// Prevent any output before headers
ob_start();

// Set CORS headers immediately
header("Access-Control-Allow-Origin: https://gamezoneismo.vercel.app");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization, X-CSRF-Token");

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
