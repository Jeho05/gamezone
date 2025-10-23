<?php
// Script to apply gamification migration
require_once __DIR__ . '/../config.php';

try {
    $pdo = get_db();
    
    echo "=== Application de la migration du système de gamification ===\n\n";
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/add_gamification_system.sql');
    
    if (!$sql) {
        die("Erreur: Impossible de lire le fichier SQL\n");
    }
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Show progress for major operations
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Table créée: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Données insérées dans: {$matches[1]}\n";
                }
            }
        } catch (PDOException $e) {
            $errors++;
            echo "✗ Erreur: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Migration terminée ===\n";
    echo "Requêtes exécutées: $executed\n";
    echo "Erreurs: $errors\n\n";
    
    // Verify tables were created
    echo "=== Vérification des tables ===\n";
    $tables = ['badges', 'user_badges', 'levels', 'points_rules', 'login_streaks', 'bonus_multipliers', 'user_stats'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✓ Table '$table' existe ($count enregistrements)\n";
        } else {
            echo "✗ Table '$table' n'existe pas\n";
        }
    }
    
    echo "\n=== Initialisation des statistiques pour les utilisateurs existants ===\n";
    
    // Initialize user_stats for existing users
    $stmt = $pdo->query('SELECT id FROM users');
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($users as $userId) {
        // Calculate total points earned and spent from transactions
        $stmt = $pdo->prepare('
            SELECT 
                COALESCE(SUM(CASE WHEN change_amount > 0 THEN change_amount ELSE 0 END), 0) as earned,
                COALESCE(SUM(CASE WHEN change_amount < 0 THEN ABS(change_amount) ELSE 0 END), 0) as spent
            FROM points_transactions
            WHERE user_id = ?
        ');
        $stmt->execute([$userId]);
        $points = $stmt->fetch();
        
        $stmt = $pdo->prepare('
            INSERT INTO user_stats (user_id, total_points_earned, total_points_spent, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                total_points_earned = ?,
                total_points_spent = ?,
                updated_at = NOW()
        ');
        $stmt->execute([
            $userId,
            (int)$points['earned'],
            (int)$points['spent'],
            (int)$points['earned'],
            (int)$points['spent']
        ]);
    }
    
    echo "✓ Statistiques initialisées pour " . count($users) . " utilisateurs\n";
    
    // Update user levels based on current points
    echo "\n=== Mise à jour des niveaux des utilisateurs ===\n";
    
    $stmt = $pdo->query('SELECT id, points FROM users');
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $stmt = $pdo->prepare('SELECT name FROM levels WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1');
        $stmt->execute([(int)$user['points']]);
        $level = $stmt->fetch();
        
        if ($level) {
            $stmt = $pdo->prepare('UPDATE users SET level = ? WHERE id = ?');
            $stmt->execute([$level['name'], $user['id']]);
            echo "✓ Utilisateur ID {$user['id']}: {$user['points']} points → Niveau '{$level['name']}'\n";
        }
    }
    
    echo "\n=== Migration complète avec succès! ===\n";
    
} catch (Exception $e) {
    echo "ERREUR FATALE: " . $e->getMessage() . "\n";
    exit(1);
}
