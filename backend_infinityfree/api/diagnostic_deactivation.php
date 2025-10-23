<?php
/**
 * Script de diagnostic pour vérifier le système de désactivation
 * Accès: http://localhost/projet%20ismo/api/diagnostic_deactivation.php
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/utils.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Diagnostic Désactivation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .sql-code { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Diagnostic du Système de Désactivation</h1>
";

try {
    $pdo = get_db();
    
    echo "<h2>1. Vérification de la Base de Données</h2>";
    
    // Check if columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasDeactivationReason = false;
    $hasDeactivationDate = false;
    $hasDeactivatedBy = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'deactivation_reason') $hasDeactivationReason = true;
        if ($col['Field'] === 'deactivation_date') $hasDeactivationDate = true;
        if ($col['Field'] === 'deactivated_by') $hasDeactivatedBy = true;
    }
    
    if ($hasDeactivationReason && $hasDeactivationDate && $hasDeactivatedBy) {
        echo "<div class='success'>✅ <strong>Migration exécutée avec succès !</strong><br>Toutes les colonnes nécessaires sont présentes.</div>";
    } else {
        echo "<div class='error'>❌ <strong>Migration non exécutée !</strong><br>Les colonnes de désactivation sont manquantes.</div>";
        echo "<div class='warning'>";
        echo "<strong>Colonnes manquantes :</strong><ul>";
        if (!$hasDeactivationReason) echo "<li><code>deactivation_reason</code></li>";
        if (!$hasDeactivationDate) echo "<li><code>deactivation_date</code></li>";
        if (!$hasDeactivatedBy) echo "<li><code>deactivated_by</code></li>";
        echo "</ul>";
        echo "<strong>⚡ Solution :</strong> Exécutez la migration SQL ci-dessous :";
        echo "<div class='sql-code'>ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_reason TEXT NULL AFTER status;<br>";
        echo "ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_date DATETIME NULL AFTER deactivation_reason;<br>";
        echo "ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivated_by INT NULL AFTER deactivation_date;</div>";
        echo "</div>";
    }
    
    echo "<h2>2. Structure de la Table 'users'</h2>";
    echo "<table>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Défaut</th></tr>";
    foreach ($columns as $col) {
        $highlight = in_array($col['Field'], ['deactivation_reason', 'deactivation_date', 'deactivated_by']) ? " style='background-color: #ffffcc;'" : "";
        echo "<tr{$highlight}>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>3. Utilisateurs Désactivés</h2>";
    
    $query = "SELECT id, username, email, status";
    if ($hasDeactivationReason) $query .= ", deactivation_reason";
    if ($hasDeactivationDate) $query .= ", deactivation_date";
    if ($hasDeactivatedBy) $query .= ", deactivated_by";
    $query .= " FROM users WHERE status = 'inactive'";
    
    $stmt = $pdo->query($query);
    $inactiveUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($inactiveUsers) > 0) {
        echo "<div class='info'>📋 <strong>" . count($inactiveUsers) . " utilisateur(s) désactivé(s) trouvé(s)</strong></div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th>";
        if ($hasDeactivationReason) echo "<th>Motif</th>";
        if ($hasDeactivationDate) echo "<th>Date</th>";
        if ($hasDeactivatedBy) echo "<th>Par Admin ID</th>";
        echo "</tr>";
        
        foreach ($inactiveUsers as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            if ($hasDeactivationReason) {
                $reason = $user['deactivation_reason'] ?? '<em>Non spécifié</em>';
                echo "<td>" . htmlspecialchars($reason) . "</td>";
            }
            if ($hasDeactivationDate) {
                $date = $user['deactivation_date'] ?? '<em>Non spécifié</em>';
                echo "<td>{$date}</td>";
            }
            if ($hasDeactivatedBy) {
                $by = $user['deactivated_by'] ?? '<em>Non spécifié</em>';
                echo "<td>{$by}</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ Aucun utilisateur désactivé actuellement.</div>";
    }
    
    echo "<h2>4. Historique des Désactivations (points_transactions)</h2>";
    
    $stmt = $pdo->query("SELECT * FROM points_transactions WHERE reason LIKE 'Compte désactivé%' ORDER BY created_at DESC LIMIT 10");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($transactions) > 0) {
        echo "<div class='info'>📊 <strong>" . count($transactions) . " transaction(s) de désactivation trouvée(s)</strong></div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Points</th><th>Raison</th><th>Admin ID</th><th>Date</th></tr>";
        
        foreach ($transactions as $trans) {
            echo "<tr>";
            echo "<td>{$trans['id']}</td>";
            echo "<td>{$trans['user_id']}</td>";
            echo "<td style='color: red;'>{$trans['change_amount']}</td>";
            echo "<td>" . htmlspecialchars($trans['reason']) . "</td>";
            echo "<td>{$trans['admin_id']}</td>";
            echo "<td>{$trans['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ Aucune transaction de désactivation trouvée.</div>";
    }
    
    echo "<h2>5. Table 'deleted_users'</h2>";
    
    $checkDeletedTable = $pdo->query("SHOW TABLES LIKE 'deleted_users'");
    if ($checkDeletedTable->rowCount() > 0) {
        echo "<div class='success'>✅ La table <code>deleted_users</code> existe.</div>";
        
        $stmt = $pdo->query("SELECT * FROM deleted_users ORDER BY deleted_at DESC LIMIT 10");
        $deleted = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($deleted) > 0) {
            echo "<div class='info'>🗑️ <strong>" . count($deleted) . " suppression(s) enregistrée(s)</strong></div>";
            echo "<table>";
            echo "<tr><th>ID</th><th>User ID</th><th>Username</th><th>Email</th><th>Raison</th><th>Par Admin</th><th>Date</th></tr>";
            
            foreach ($deleted as $del) {
                echo "<tr>";
                echo "<td>{$del['id']}</td>";
                echo "<td>{$del['user_id']}</td>";
                echo "<td>{$del['username']}</td>";
                echo "<td>{$del['email']}</td>";
                echo "<td>" . htmlspecialchars($del['deletion_reason']) . "</td>";
                echo "<td>{$del['deleted_by']}</td>";
                echo "<td>{$del['deleted_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info'>ℹ️ Aucune suppression enregistrée.</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ La table <code>deleted_users</code> n'existe pas encore. Elle sera créée automatiquement lors de la première suppression.</div>";
    }
    
    echo "<h2>6. Résumé & Recommandations</h2>";
    
    if ($hasDeactivationReason && $hasDeactivationDate && $hasDeactivatedBy) {
        echo "<div class='success'>";
        echo "<h3>✅ Système Opérationnel</h3>";
        echo "<ul>";
        echo "<li>✅ Migration exécutée</li>";
        echo "<li>✅ Toutes les colonnes présentes</li>";
        echo "<li>✅ Prêt à utiliser</li>";
        echo "</ul>";
        echo "<p><strong>Actions disponibles :</strong></p>";
        echo "<ul>";
        echo "<li>Désactivation avec motif obligatoire</li>";
        echo "<li>Affichage du motif lors de la tentative de connexion</li>";
        echo "<li>Traçabilité complète</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Action Requise</h3>";
        echo "<p>La migration n'a pas été exécutée. Le système fonctionne en mode limité.</p>";
        echo "<p><strong>Pour activer toutes les fonctionnalités :</strong></p>";
        echo "<ol>";
        echo "<li>Ouvrir phpMyAdmin : <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
        echo "<li>Sélectionner la base <code>gamezone</code></li>";
        echo "<li>Cliquer sur l'onglet 'SQL'</li>";
        echo "<li>Copier/coller le code SQL ci-dessus</li>";
        echo "<li>Cliquer sur 'Exécuter'</li>";
        echo "<li>Actualiser cette page pour vérifier</li>";
        echo "</ol>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Erreur de Base de Données</h3>";
    echo "<p><strong>Message :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Vérifiez que :</p>";
    echo "<ul>";
    echo "<li>MySQL est démarré dans XAMPP</li>";
    echo "<li>La base de données 'gamezone' existe</li>";
    echo "<li>Les identifiants de connexion sont corrects</li>";
    echo "</ul>";
    echo "</div>";
}

echo "
    </div>
</body>
</html>";
?>
