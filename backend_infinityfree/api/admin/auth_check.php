<?php
// api/admin/auth_check.php
// Middleware to ensure only admins can access admin endpoints

require_once __DIR__ . '/../utils.php';

function require_admin(): array {
    $user = require_auth();
    
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Accès refusé',
            'message' => 'Vous devez être administrateur pour accéder à cette ressource'
        ]);
        exit;
    }
    
    return $user;
}
