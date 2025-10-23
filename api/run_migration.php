<?php
/**
 * Script d'exécution automatique de la migration
 * Accès: http://localhost/projet%20ismo/api/run_migration.php
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/utils.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Migration - Système de Désactivation</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #333; 
            border-bottom: 4px solid #667eea; 
            padding-bottom: 15px;
            margin-top: 0;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .success { 
            background: #d4edda; 
            border-left-color: #28a745;
            color: #155724; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px 0;
            font-size: 16px;
        }
        .error { 
            background: #f8d7da; 
            border-left-color: #dc3545;
            color: #721c24; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px 0;
            font-size: 16px;
        }
        .warning { 
            background: #fff3cd; 
            border-left-color: #ffc107;
            color: #856404; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px 0;
        }
        .code {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .progress {
            margin: 20px 0;
        }
        .progress-item {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        .progress-item.done {
            background: #d4edda;
            color: #155724;
        }
        .progress-item.pending {
            background: #f8f9fa;
            color: #666;
        }
        .icon {
            font-size: 24px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔄 Migration de la Base de Données</h1>
        <p style='font-size: 16px; color: #666;'>Ajout des champs pour le système de désactivation avec motifs</p>
";

try {
    $pdo = get_db();
    
    echo "<div class='progress'>";
    
    // Check current state
    echo "<div class='step'>";
    echo "<h3>📋 Vérification de l'état actuel...</h3>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'deactivation_reason'");
    $hasDeactivationReason = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'deactivation_date'");
    $hasDeactivationDate = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'deactivated_by'");
    $hasDeactivatedBy = $stmt->rowCount() > 0;
    
    if ($hasDeactivationReason && $hasDeactivationDate && $hasDeactivatedBy) {
        echo "<div class='warning'>";
        echo "⚠️ <strong>Migration déjà exécutée !</strong><br>";
        echo "Tous les champs sont déjà présents dans la base de données.";
        echo "</div>";
        echo "<a href='diagnostic_deactivation.php' class='btn'>📊 Voir le Diagnostic</a>";
        echo "</div></div></div></body></html>";
        exit;
    }
    
    echo "État actuel :<br>";
    echo "<ul>";
    echo "<li>" . ($hasDeactivationReason ? "✅" : "❌") . " deactivation_reason</li>";
    echo "<li>" . ($hasDeactivationDate ? "✅" : "❌") . " deactivation_date</li>";
    echo "<li>" . ($hasDeactivatedBy ? "✅" : "❌") . " deactivated_by</li>";
    echo "</ul>";
    echo "</div>";
    
    // Execute migrations
    $migrations = [];
    $success = 0;
    $errors = 0;
    
    echo "<div class='step'>";
    echo "<h3>🚀 Exécution des migrations...</h3>";
    
    // Migration 1: deactivation_reason
    if (!$hasDeactivationReason) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN deactivation_reason TEXT NULL AFTER status");
            echo "<div class='progress-item done'><span class='icon'>✅</span> Colonne 'deactivation_reason' ajoutée</div>";
            $success++;
        } catch (PDOException $e) {
            echo "<div class='progress-item' style='background: #f8d7da; color: #721c24;'><span class='icon'>❌</span> Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
            $errors++;
        }
    } else {
        echo "<div class='progress-item pending'><span class='icon'>⏭️</span> Colonne 'deactivation_reason' déjà présente</div>";
    }
    
    // Migration 2: deactivation_date
    if (!$hasDeactivationDate) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN deactivation_date DATETIME NULL AFTER deactivation_reason");
            echo "<div class='progress-item done'><span class='icon'>✅</span> Colonne 'deactivation_date' ajoutée</div>";
            $success++;
        } catch (PDOException $e) {
            echo "<div class='progress-item' style='background: #f8d7da; color: #721c24;'><span class='icon'>❌</span> Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
            $errors++;
        }
    } else {
        echo "<div class='progress-item pending'><span class='icon'>⏭️</span> Colonne 'deactivation_date' déjà présente</div>";
    }
    
    // Migration 3: deactivated_by
    if (!$hasDeactivatedBy) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN deactivated_by INT NULL AFTER deactivation_date");
            echo "<div class='progress-item done'><span class='icon'>✅</span> Colonne 'deactivated_by' ajoutée</div>";
            $success++;
        } catch (PDOException $e) {
            echo "<div class='progress-item' style='background: #f8d7da; color: #721c24;'><span class='icon'>❌</span> Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
            $errors++;
        }
    } else {
        echo "<div class='progress-item pending'><span class='icon'>⏭️</span> Colonne 'deactivated_by' déjà présente</div>";
    }
    
    echo "</div>";
    
    // Final verification
    echo "<div class='step'>";
    echo "<h3>🔍 Vérification finale...</h3>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'deactivation_reason'");
    $hasDeactivationReason = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'deactivation_date'");
    $hasDeactivationDate = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'deactivated_by'");
    $hasDeactivatedBy = $stmt->rowCount() > 0;
    
    if ($hasDeactivationReason && $hasDeactivationDate && $hasDeactivatedBy) {
        echo "<div class='success'>";
        echo "<h2 style='margin-top: 0;'>🎉 Migration Réussie !</h2>";
        echo "<p style='font-size: 18px; margin: 15px 0;'><strong>Toutes les colonnes ont été ajoutées avec succès.</strong></p>";
        echo "<p>Le système de désactivation avec motifs est maintenant pleinement opérationnel.</p>";
        echo "<hr style='border: none; border-top: 1px solid rgba(0,0,0,0.1); margin: 20px 0;'>";
        echo "<p><strong>Nouvelles fonctionnalités activées :</strong></p>";
        echo "<ul style='line-height: 1.8;'>";
        echo "<li>✅ Motif obligatoire lors de la désactivation</li>";
        echo "<li>✅ Affichage du motif à l'utilisateur désactivé</li>";
        echo "<li>✅ Traçabilité complète (qui, quand, pourquoi)</li>";
        echo "<li>✅ Suppression avec motif enregistré</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div style='text-align: center; margin-top: 30px;'>";
        echo "<a href='diagnostic_deactivation.php' class='btn'>📊 Voir le Diagnostic Complet</a>";
        echo "<a href='../createxyz-project/_/apps/web/public/index.html' class='btn' style='background: #28a745;'>🎮 Retour à l'Application</a>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Problème Détecté</h3>";
        echo "<p>Certaines colonnes n'ont pas été ajoutées correctement.</p>";
        echo "<p><strong>Colonnes manquantes :</strong></p>";
        echo "<ul>";
        if (!$hasDeactivationReason) echo "<li>deactivation_reason</li>";
        if (!$hasDeactivationDate) echo "<li>deactivation_date</li>";
        if (!$hasDeactivatedBy) echo "<li>deactivated_by</li>";
        echo "</ul>";
        echo "<p>Vous pouvez essayer de les ajouter manuellement via phpMyAdmin.</p>";
        echo "</div>";
        
        echo "<div style='text-align: center;'>";
        echo "<a href='http://localhost/phpmyadmin' class='btn' target='_blank'>🔧 Ouvrir phpMyAdmin</a>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Summary
    echo "<div class='step'>";
    echo "<h3>📊 Résumé de la Migration</h3>";
    echo "<p><strong>Colonnes ajoutées :</strong> {$success}</p>";
    if ($errors > 0) {
        echo "<p style='color: #dc3545;'><strong>Erreurs :</strong> {$errors}</p>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Erreur de Connexion à la Base de Données</h3>";
    echo "<p><strong>Message d'erreur :</strong></p>";
    echo "<div class='code'>" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><strong>Vérifications à effectuer :</strong></p>";
    echo "<ul>";
    echo "<li>XAMPP est démarré (MySQL en vert)</li>";
    echo "<li>La base de données 'gamezone' existe</li>";
    echo "<li>Les identifiants dans config.php sont corrects</li>";
    echo "</ul>";
    echo "</div>";
}

echo "
    </div>
</body>
</html>";
?>
