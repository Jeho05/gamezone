<?php
require_once __DIR__ . '/../../config.php';

$pdo = get_db();

try {
    // Vérifier si la colonne existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'recovery_code_hash'");
    $columnExists = $stmt->fetch(PDO::FETCH_ASSOC) !== false;

    if ($columnExists) {
        echo "Column recovery_code_hash already exists on users table.\n";
        return;
    }

    $sql = "ALTER TABLE users ADD COLUMN recovery_code_hash VARCHAR(255) NULL AFTER password_hash";
    $pdo->exec($sql);
    echo "Column recovery_code_hash added successfully to users table!\n";
} catch (PDOException $e) {
    echo "Error while altering users table: " . $e->getMessage() . "\n";
    exit(1);
}
