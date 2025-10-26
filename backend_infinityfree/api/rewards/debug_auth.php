<?php
// Debug de l'authentification
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

$debug = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? null,
    'has_user' => isset($_SESSION['user']),
    'user_data' => $_SESSION['user'] ?? null,
    'is_admin' => isset($_SESSION['user']) ? is_admin($_SESSION['user']) : false,
    'cookies' => $_COOKIE,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'none',
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'none'
];

echo json_encode($debug, JSON_PRETTY_PRINT);
