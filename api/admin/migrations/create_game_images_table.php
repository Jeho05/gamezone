<?php
// Migration pour créer la table game_images
// À exécuter une seule fois pour créer la table

require_once __DIR__ . '/../../config.php';

$pdo = get_db();

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS game_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        data LONGTEXT NOT NULL,
        mime_type VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_filename (filename),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "Table game_images créée avec succès !\n";
    
} catch (PDOException $e) {
    echo "Erreur lors de la création de la table: " . $e->getMessage() . "\n";
    exit(1);
}