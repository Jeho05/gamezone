<?php
// Initialize user stats for existing users
require_once __DIR__ . '/../config.php';

try {
    $pdo = get_db();
    
    echo "=== Initialisation des statistiques utilisateurs ===\n\n";
    
    // Get all users
    $stmt = $pdo->query('SELECT id, username, points FROM users');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Utilisateurs trouvés: " . count($users) . "\n\n";
    
    foreach ($users as $user) {
        $userId = $user['id'];
        $username = $user['username'];
        $points = (int)$user['points'];
        
        // Calculate points from transactions
        $stmt = $pdo->prepare('
            SELECT 
                COALESCE(SUM(CASE WHEN change_amount > 0 THEN change_amount ELSE 0 END), 0) as earned,
                COALESCE(SUM(CASE WHEN change_amount < 0 THEN ABS(change_amount) ELSE 0 END), 0) as spent
            FROM points_transactions
            WHERE user_id = ?
        ');
        $stmt->execute([$userId]);
        $pointsData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $earned = (int)$pointsData['earned'];
        $spent = (int)$pointsData['spent'];
        
        // Insert or update user_stats
        $stmt = $pdo->prepare('
            INSERT INTO user_stats (user_id, total_points_earned, total_points_spent, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                total_points_earned = ?,
                total_points_spent = ?,
                updated_at = NOW()
        ');
        $stmt->execute([$userId, $earned, $spent, $earned, $spent]);
        
        // Update user level based on points
        $stmt = $pdo->prepare('SELECT name FROM levels WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1');
        $stmt->execute([$points]);
        $level = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($level) {
            $stmt = $pdo->prepare('UPDATE users SET level = ? WHERE id = ?');
            $stmt->execute([$level['name'], $userId]);
            echo "✓ {$username}: {$points} pts → Niveau '{$level['name']}' (Gagné: {$earned}, Dépensé: {$spent})\n";
        } else {
            echo "✓ {$username}: {$points} pts (Gagné: {$earned}, Dépensé: {$spent})\n";
        }
    }
    
    echo "\n=== Initialisation terminée ===\n";
    
    // Display stats summary
    echo "\n=== Résumé ===\n";
    $stmt = $pdo->query('SELECT COUNT(*) FROM badges');
    echo "Badges disponibles: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM levels');
    echo "Niveaux disponibles: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM points_rules');
    echo "Règles de points: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM user_stats');
    echo "Utilisateurs initialisés: " . $stmt->fetchColumn() . "\n";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
