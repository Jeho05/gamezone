<?php
// Script d'installation du système de facturation GameZone
require_once __DIR__ . '/api/config.php';

function executeSQLFile($pdo, $filePath) {
    if (!file_exists($filePath)) {
        return ['success' => false, 'error' => "Fichier non trouvé: $filePath"];
    }
    
    $sql = file_get_contents($filePath);
    $commands = array_filter(explode(';', $sql));
    
    $executed = 0;
    $errors = [];
    
    foreach ($commands as $command) {
        $command = trim($command);
        if (empty($command) || strpos($command, '--') === 0) continue;
        
        try {
            $pdo->exec($command);
            $executed++;
        } catch (PDOException $e) {
            $errors[] = ['command' => substr($command, 0, 100), 'error' => $e->getMessage()];
        }
    }
    
    return ['success' => count($errors) === 0, 'executed' => $executed, 'errors' => $errors];
}

$isCliMode = php_sapi_name() === 'cli';
$adminPassword = $_GET['password'] ?? '';

if (!$isCliMode && $adminPassword !== 'GAMEZONE_INSTALL_2025') {
    http_response_code(403);
    die(json_encode(['error' => 'Accès refusé']));
}

try {
    $pdo = get_db();
    $results = [];
    
    echo "🚀 Installation du système de facturation\n";
    
    // Créer les tables
    echo "📦 Création des tables...\n";
    $result = executeSQLFile($pdo, __DIR__ . '/api/migrations/add_invoice_system.sql');
    $results['tables'] = $result;
    echo $result['success'] ? "✅ Tables créées\n" : "❌ Erreurs tables\n";
    
    // Créer les procédures
    echo "⚙️ Création des procédures...\n";
    $result = executeSQLFile($pdo, __DIR__ . '/api/migrations/add_invoice_procedures.sql');
    $results['procedures'] = $result;
    echo $result['success'] ? "✅ Procédures créées\n" : "❌ Erreurs procédures\n";
    
    // Créer dossier logs
    $logsDir = __DIR__ . '/logs';
    if (!is_dir($logsDir)) mkdir($logsDir, 0755, true);
    file_put_contents($logsDir . '/.htaccess', "Order Deny,Allow\nDeny from all");
    
    echo "\n✅ INSTALLATION TERMINÉE !\n";
    echo "\nConfigurer le CRON:\n";
    echo "* * * * * php " . __DIR__ . "/api/cron/countdown_sessions.php\n";
    echo "\nOu URL avec token:\n";
    echo "http://yourdomain.com/api/cron/countdown_sessions.php?token=GAMEZONE_CRON_SECRET_2025\n";
    
    if (!$isCliMode) {
        header('Content-Type: application/json');
        echo json_encode($results, JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
