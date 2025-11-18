<?php
require_once __DIR__ . '/../../config.php';

$pdo = get_db();

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) NOT NULL DEFAULT 0,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_password_resets_user_id (user_id),
        INDEX idx_password_resets_expires_at (expires_at),
        CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "Table password_resets créée avec succès !\n";
} catch (PDOException $e) {
    echo "Erreur lors de la création de la table: " . $e->getMessage() . "\n";
    exit(1);
}
