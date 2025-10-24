<?php
/**
 * DIAGNOSTIC - Test de lecture du fichier .env
 * Utilisez ce fichier pour diagnostiquer les problèmes de connexion MySQL
 * URL: http://ismo.gamer.gd/api/diagnostic_env.php
 */

header('Content-Type: application/json');

$env_path = __DIR__ . '/.env';
$result = [
    'test' => 'Diagnostic .env',
    'php_version' => phpversion(),
    'current_dir' => __DIR__,
    'env_file_path' => $env_path,
    'env_file_exists' => file_exists($env_path),
    'env_file_readable' => is_readable($env_path),
    'env_values' => []
];

// Si le fichier existe, essayez de le lire
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parser KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            
            // Masquer les valeurs sensibles (garder juste longueur)
            if (in_array($key, ['DB_PASS', 'KKIAPAY_PRIVATE_KEY'])) {
                $result['env_values'][$key] = '***' . strlen($value) . ' chars***';
            } else {
                $result['env_values'][$key] = trim($value);
            }
        }
    }
    
    $result['env_lines_count'] = count($lines);
}

// Tester les variables d'environnement via getenv()
$result['getenv'] = [
    'DB_HOST' => getenv('DB_HOST') ?: 'NOT SET',
    'DB_NAME' => getenv('DB_NAME') ?: 'NOT SET',
    'DB_USER' => getenv('DB_USER') ?: 'NOT SET',
    'DB_PASS' => getenv('DB_PASS') ? '***' . strlen(getenv('DB_PASS')) . ' chars***' : 'NOT SET'
];

// Vérifier si parse_ini_file fonctionne
$result['parse_ini_file_available'] = function_exists('parse_ini_file');
if (function_exists('parse_ini_file') && file_exists($env_path)) {
    try {
        $parsed = parse_ini_file($env_path);
        $result['parse_ini_success'] = !empty($parsed);
        $result['parse_ini_keys'] = array_keys($parsed);
    } catch (Exception $e) {
        $result['parse_ini_error'] = $e->getMessage();
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
