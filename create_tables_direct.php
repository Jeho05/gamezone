<?php
/**
 * Création directe des tables - Version simplifiée
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbHost = 'localhost';
$dbName = 'gamezone';
$dbUser = 'root';
$dbPass = '';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🔧 Création des Tables</title>
    <style>
        body { font-family: Arial; background: #667eea; color: white; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; color: #333; padding: 30px; border-radius: 10px; }
        .success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #17a2b8; }
        pre { background: #2d3748; color: #48bb78; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="container">
<h1>🔧 Création des Tables de la Boutique</h1>

<?php

try {
    // Connexion
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo '<div class="success">✅ Connecté à MySQL</div>';
    
    // 1. TABLE GAMES
    echo '<div class="info"><h3>Création table: games</h3>';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS games (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(200) NOT NULL,
          slug VARCHAR(200) NOT NULL UNIQUE,
          description TEXT NULL,
          short_description VARCHAR(500) NULL,
          image_url VARCHAR(500) NULL,
          thumbnail_url VARCHAR(500) NULL,
          category VARCHAR(50) NOT NULL DEFAULT 'other',
          platform VARCHAR(100) NULL,
          min_players INT NOT NULL DEFAULT 1,
          max_players INT NOT NULL DEFAULT 1,
          age_rating VARCHAR(20) NULL,
          points_per_hour INT NOT NULL DEFAULT 10,
          base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          is_featured TINYINT(1) NOT NULL DEFAULT 0,
          display_order INT NOT NULL DEFAULT 0,
          created_by INT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          INDEX idx_active_featured (is_active, is_featured),
          INDEX idx_category (category),
          INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo '✅ Table games créée</div>';
    
    // 2. TABLE GAME_PACKAGES
    echo '<div class="info"><h3>Création table: game_packages</h3>';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS game_packages (
          id INT AUTO_INCREMENT PRIMARY KEY,
          game_id INT NOT NULL,
          name VARCHAR(150) NOT NULL,
          duration_minutes INT NOT NULL,
          price DECIMAL(10,2) NOT NULL,
          original_price DECIMAL(10,2) NULL,
          points_earned INT NOT NULL,
          bonus_multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00,
          is_promotional TINYINT(1) NOT NULL DEFAULT 0,
          promotional_label VARCHAR(100) NULL,
          max_purchases_per_user INT NULL,
          available_from DATETIME NULL,
          available_until DATETIME NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          display_order INT NOT NULL DEFAULT 0,
          created_by INT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
          INDEX idx_game_active (game_id, is_active),
          INDEX idx_promotional (is_promotional)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo '✅ Table game_packages créée</div>';
    
    // 3. TABLE PAYMENT_METHODS
    echo '<div class="info"><h3>Création table: payment_methods</h3>';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payment_methods (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(100) NOT NULL,
          slug VARCHAR(100) NOT NULL UNIQUE,
          provider VARCHAR(100) NULL,
          api_key_public VARCHAR(500) NULL,
          api_key_secret VARCHAR(500) NULL,
          api_endpoint VARCHAR(500) NULL,
          webhook_secret VARCHAR(500) NULL,
          requires_online_payment TINYINT(1) NOT NULL DEFAULT 1,
          auto_confirm_payment TINYINT(1) NOT NULL DEFAULT 0,
          fee_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
          fee_fixed DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          display_order INT NOT NULL DEFAULT 0,
          instructions TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          INDEX idx_active (is_active),
          INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo '✅ Table payment_methods créée</div>';
    
    // 4. TABLE PURCHASES
    echo '<div class="info"><h3>Création table: purchases</h3>';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS purchases (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NOT NULL,
          game_id INT NOT NULL,
          package_id INT NULL,
          game_name VARCHAR(200) NOT NULL,
          package_name VARCHAR(150) NULL,
          duration_minutes INT NOT NULL,
          price DECIMAL(10,2) NOT NULL,
          currency VARCHAR(3) NOT NULL DEFAULT 'XOF',
          points_earned INT NOT NULL DEFAULT 0,
          points_credited TINYINT(1) NOT NULL DEFAULT 0,
          payment_method_id INT NULL,
          payment_method_name VARCHAR(100) NULL,
          payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
          payment_reference VARCHAR(255) NULL,
          payment_details JSON NULL,
          confirmed_by INT NULL,
          confirmed_at DATETIME NULL,
          session_status VARCHAR(50) NOT NULL DEFAULT 'pending',
          notes TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
          FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
          FOREIGN KEY (package_id) REFERENCES game_packages(id) ON DELETE SET NULL,
          FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
          INDEX idx_user_status (user_id, payment_status),
          INDEX idx_payment_status (payment_status),
          INDEX idx_payment_reference (payment_reference),
          INDEX idx_session_status (session_status),
          INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo '✅ Table purchases créée</div>';
    
    // 5. TABLE GAME_SESSIONS
    echo '<div class="info"><h3>Création table: game_sessions</h3>';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS game_sessions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          purchase_id INT NOT NULL,
          user_id INT NOT NULL,
          game_id INT NOT NULL,
          total_minutes INT NOT NULL,
          used_minutes INT NOT NULL DEFAULT 0,
          status VARCHAR(50) NOT NULL DEFAULT 'pending',
          started_at DATETIME NULL,
          paused_at DATETIME NULL,
          resumed_at DATETIME NULL,
          completed_at DATETIME NULL,
          expires_at DATETIME NULL,
          last_activity_at DATETIME NULL,
          notes TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
          FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
          INDEX idx_user_status (user_id, status),
          INDEX idx_purchase (purchase_id),
          INDEX idx_status (status),
          INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo '✅ Table game_sessions créée</div>';
    
    echo '<div class="success"><h2>🎉 Toutes les tables ont été créées avec succès !</h2></div>';
    
    // Vérification
    echo '<div class="info"><h3>Vérification:</h3><ul>';
    $tables = ['games', 'game_packages', 'payment_methods', 'purchases', 'game_sessions'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "<li>✅ <strong>$table</strong>: $count enregistrement(s)</li>";
    }
    echo '</ul></div>';
    
    echo '<div class="success">';
    echo '<h3>✅ Prochaines étapes:</h3>';
    echo '<p>1. <a href="setup_shop.php">Retourner à la vérification</a></p>';
    echo '<p>2. Insérer des données de test (nécessite connexion admin): <a href="api/shop/seed_test_data.php">seed_test_data.php</a></p>';
    echo '<p>3. <a href="http://localhost:4000/player/shop">Ouvrir la boutique</a></p>';
    echo '</div>';
    
} catch (PDOException $e) {
    echo '<div class="error">';
    echo '<h3>❌ Erreur SQL:</h3>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '</div>';
}

?>

</div>
</body>
</html>
