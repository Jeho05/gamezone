<?php
// Vider le cache OpCache de PHP
header('Content-Type: application/json');

$result = [
    'opcache_enabled' => function_exists('opcache_reset'),
    'opcache_reset' => false,
    'message' => ''
];

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        $result['opcache_reset'] = true;
        $result['message'] = 'OpCache vidé avec succès';
    } else {
        $result['message'] = 'Échec du vidage OpCache (permissions ?)';
    }
} else {
    $result['message'] = 'OpCache non activé - aucun cache à vider';
}

// Ajouter timestamp pour forcer le rechargement
$result['timestamp'] = time();
$result['datetime'] = date('Y-m-d H:i:s');

echo json_encode($result, JSON_PRETTY_PRINT);
