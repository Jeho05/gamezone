<?php
/**
 * Script d'installation du système de transactions sécurisées
 */
// Support both layouts: production (config.php at web root) and local (api/config.php)
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    require_once __DIR__ . '/api/config.php';
}

set_time_limit(300); // 5 minutes max

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .success { color: #059669; font-weight: bold; }
    .error { color: #dc2626; font-weight: bold; }
    .info { color: #3b82f6; font-weight: bold; }
    pre { background: #1f2937; color: #fff; padding: 15px; border-radius: 8px; white-space: pre-wrap; }
    .step { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #3b82f6; }
</style></head><body>";

echo "<h1>🔒 Installation du Système de Transactions Sécurisées</h1>";

try {
    $pdo = get_db();
    
    // Lire le fichier SQL
    echo "<div class='step'>";
    echo "<h2>1️⃣ Lecture du fichier de migration</h2>";
    
    // Migration file path (supports prod copy where api/ is flattened)
    $sqlFile = __DIR__ . '/api/migrations/add_secure_transactions.sql';
    if (!file_exists($sqlFile)) {
        $sqlFile = __DIR__ . '/migrations/add_secure_transactions.sql';
    }
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Fichier de migration introuvable: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "<p class='success'>✅ Fichier chargé (" . strlen($sql) . " caractères)</p>";
    echo "</div>";
    
    // Exécuter le SQL
    echo "<div class='step'>";
    echo "<h2>2️⃣ Exécution de la migration</h2>";
    
    // Split par les délimiteurs et exécuter chaque partie
    $statements = [];
    $current = '';
    $inProcedure = false;
    
    foreach (explode("\n", $sql) as $line) {
        $line = trim($line);
        
        // Ignorer les commentaires
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        // Détecter les délimiteurs
        if (stripos($line, 'DELIMITER') === 0) {
            if (strpos($line, '$$') !== false) {
                $inProcedure = true;
            } else {
                $inProcedure = false;
                if (!empty($current)) {
                    $statements[] = $current;
                    $current = '';
                }
            }
            continue;
        }
        
        $current .= $line . "\n";
        
        // Si pas dans une procédure et ligne se termine par ;
        if (!$inProcedure && substr(rtrim($line), -1) === ';') {
            $statements[] = $current;
            $current = '';
        }
        
        // Si dans une procédure et ligne contient END$$
        if ($inProcedure && strpos($line, 'END$$') !== false) {
            $statements[] = $current;
            $current = '';
        }
    }
    
    if (!empty($current)) {
        $statements[] = $current;
    }
    
    echo "<p class='info'>📋 " . count($statements) . " instructions à exécuter</p>";
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $i => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            // Nettoyer les délimiteurs $$ si présents
            $statement = str_replace('$$', ';', $statement);
            
            $pdo->exec($statement);
            $executed++;
            
            // Afficher un résumé de ce qui a été exécuté
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE[^`]*`?(\w+)`?/i', $statement, $matches);
                echo "<p class='success'>✅ Table créée: " . ($matches[1] ?? 'unknown') . "</p>";
            } elseif (stripos($statement, 'CREATE PROCEDURE') !== false) {
                preg_match('/CREATE PROCEDURE\s+(\w+)/i', $statement, $matches);
                echo "<p class='success'>✅ Procédure créée: " . ($matches[1] ?? 'unknown') . "</p>";
            } elseif (stripos($statement, 'CREATE EVENT') !== false) {
                preg_match('/CREATE EVENT[^`]*`?(\w+)`?/i', $statement, $matches);
                echo "<p class='success'>✅ Event créé: " . ($matches[1] ?? 'unknown') . "</p>";
            } elseif (stripos($statement, 'CREATE OR REPLACE VIEW') !== false) {
                preg_match('/CREATE OR REPLACE VIEW\s+(\w+)/i', $statement, $matches);
                echo "<p class='success'>✅ Vue créée: " . ($matches[1] ?? 'unknown') . "</p>";
            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $matches);
                echo "<p class='success'>✅ Table modifiée: " . ($matches[1] ?? 'unknown') . "</p>";
            }
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            
            // Ignorer certaines erreurs non critiques
            if (
                stripos($errorMsg, 'already exists') !== false ||
                stripos($errorMsg, 'Duplicate column') !== false ||
                stripos($errorMsg, 'Duplicate key') !== false
            ) {
                echo "<p class='info'>ℹ️ Déjà existant (ignoré)</p>";
            } else {
                $errors[] = "Instruction " . ($i + 1) . ": " . $errorMsg;
                echo "<p class='error'>❌ Erreur: " . htmlspecialchars($errorMsg) . "</p>";
            }
        }
    }
    
    echo "<p class='success'><strong>✅ {$executed} instructions exécutées avec succès</strong></p>";
    
    if (!empty($errors)) {
        echo "<p class='error'><strong>" . count($errors) . " erreur(s):</strong></p>";
        echo "<pre>" . implode("\n", array_map('htmlspecialchars', $errors)) . "</pre>";
    }
    
    echo "</div>";
    
    // Vérification
    echo "<div class='step'>";
    echo "<h2>3️⃣ Vérification de l'installation</h2>";
    
    // Vérifier la table
    $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_transactions'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Table `purchase_transactions` créée</p>";
        
        // Compter les colonnes
        $stmt = $pdo->query("SHOW COLUMNS FROM purchase_transactions");
        $columnCount = $stmt->rowCount();
        echo "<p class='info'>📊 {$columnCount} colonnes</p>";
    } else {
        echo "<p class='error'>❌ Table `purchase_transactions` non trouvée</p>";
    }
    
    // Vérifier les procédures
    $stmt = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE()");
    $procedures = $stmt->fetchAll();
    $procedureNames = array_column($procedures, 'Name');
    
    $required = ['refund_transaction', 'cleanup_stuck_transactions'];
    foreach ($required as $proc) {
        if (in_array($proc, $procedureNames)) {
            echo "<p class='success'>✅ Procédure `{$proc}` créée</p>";
        } else {
            echo "<p class='error'>❌ Procédure `{$proc}` manquante</p>";
        }
    }
    
    // Vérifier l'event
    $stmt = $pdo->query("SELECT * FROM INFORMATION_SCHEMA.EVENTS WHERE EVENT_SCHEMA = DATABASE() AND EVENT_NAME = 'cleanup_transactions_event'");
    $event = $stmt->fetch();
    
    if ($event) {
        echo "<p class='success'>✅ Event `cleanup_transactions_event` est actif</p>";
        echo "<table>";
        echo "<tr><th>Propriété</th><th>Valeur</th></tr>";
        echo "<tr><td>Status</td><td>" . ($event['Status'] ?? 'N/A') . "</td></tr>";
        
        // Récupérer l'interval avec gestion des différentes versions MySQL
        $interval = 'N/A';
        if (isset($event['Interval_field']) && isset($event['Interval_value'])) {
            $interval = $event['Interval_value'] . ' ' . $event['Interval_field'];
        } elseif (isset($event['INTERVAL_VALUE']) && isset($event['INTERVAL_FIELD'])) {
            $interval = $event['INTERVAL_VALUE'] . ' ' . $event['INTERVAL_FIELD'];
        } elseif (isset($event['Execute_at'])) {
            $interval = 'AT ' . $event['Execute_at'];
        }
        
        echo "<tr><td>Interval</td><td>{$interval}</td></tr>";
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Event de cleanup n'est pas actif</p>";
    }
    
    echo "</div>";
    
    // Résumé final
    echo "<div class='step'>";
    echo "<h2>🎉 Installation Terminée</h2>";
    echo "<p class='success'><strong>Le système de transactions sécurisées est maintenant installé !</strong></p>";
    echo "<h3>Prochaines étapes:</h3>";
    echo "<ol>";
    echo "<li><a href='test_secure_transactions.php'>Tester le système</a></li>";
    echo "<li>Lire la documentation: <code>SYSTEME_TRANSACTIONS_SECURISEES.md</code></li>";
    echo "<li>Migrer le code frontend pour utiliser la nouvelle API</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='step'>";
    echo "<h2 class='error'>❌ Erreur Fatale</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>
