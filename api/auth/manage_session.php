<?php
// api/auth/manage_session.php
// Simple endpoint to keep the authenticated session alive

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

try {
    // Vérifie que l'utilisateur est bien authentifié
    $user = require_auth();

    // La mise à jour de last_active est déjà gérée dans config.php
    // lorsqu'une session avec user[id] est présente.

    json_response([
        'success' => true,
        'user' => [
            'id' => (int)($user['id'] ?? 0),
            'username' => $user['username'] ?? null,
            'role' => $user['role'] ?? 'player',
        ],
        'message' => 'Session active',
    ]);
} catch (Throwable $e) {
    json_response([
        'success' => false,
        'error' => 'Session invalide',
        'details' => $e->getMessage(),
    ], 401);
}
