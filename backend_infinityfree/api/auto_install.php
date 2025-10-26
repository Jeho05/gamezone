<?php
// Auto-installation de la base de données si les tables n'existent pas
// Ce fichier est appelé par config.php au premier chargement

if (!defined('AUTO_INSTALL_DONE')) {
    define('AUTO_INSTALL_DONE', true);
    
    try {
        // Vérifier si la table users existe
        $pdo = get_db();
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $exists = $stmt->fetch();
        
        if (!$exists) {
            error_log('🔄 AUTO-INSTALL: Tables non trouvées, installation en cours...');
            
            // Charger et exécuter schema.sql
            $schemaPath = __DIR__ . '/schema.sql';
            if (file_exists($schemaPath)) {
                $sql = file_get_contents($schemaPath);
                
                // Supprimer CREATE DATABASE et USE (déjà connecté)
                $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
                $sql = preg_replace('/USE [^;]+;/i', '', $sql);
                
                // Exécuter chaque statement
                $statements = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function($stmt) {
                        return !empty($stmt) && strpos($stmt, '--') !== 0;
                    }
                );
                
                foreach ($statements as $stmt) {
                    if (!empty($stmt)) {
                        try {
                            $pdo->exec($stmt);
                        } catch (PDOException $e) {
                            error_log('❌ AUTO-INSTALL Error: ' . $e->getMessage());
                        }
                    }
                }
                
                error_log('✅ AUTO-INSTALL: Base de données initialisée avec succès!');
                
                // Créer un admin par défaut
                $hashedPassword = password_hash('demo123', PASSWORD_BCRYPT);
                $pdo->exec("INSERT IGNORE INTO users (username, email, password_hash, role, points, created_at, updated_at) 
                           VALUES ('Admin', 'admin@gmail.com', '$hashedPassword', 'admin', 0, NOW(), NOW())");
                
                error_log('✅ AUTO-INSTALL: Compte admin créé (admin@gmail.com / demo123)');
            }
        }
    } catch (Exception $e) {
        error_log('❌ AUTO-INSTALL CRITICAL ERROR: ' . $e->getMessage());
    }
}
