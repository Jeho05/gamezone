<?php
// Script pour créer la table uploaded_images
require_once __DIR__ . '/config.php';

try {
    $pdo = get_db();
    
    $sql = "CREATE TABLE IF NOT EXISTS uploaded_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        data_url LONGTEXT NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        size INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "Table uploaded_images créée avec succès\n";
    
} catch (Exception $e) {
    echo "Erreur lors de la création de la table: " . $e->getMessage() . "\n";
}
