<?php
// Simple test file to debug HTTP requests
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$debug = [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NONE',
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'NONE',
    'php_input' => file_get_contents('php://input'),
    'post' => $_POST,
    'get' => $_GET,
    'session_started' => session_status() === PHP_SESSION_ACTIVE,
];

echo json_encode($debug, JSON_PRETTY_PRINT);
