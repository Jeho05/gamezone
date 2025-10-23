<?php
/**
 * Script de vérification du système de récompenses
 * À accéder via: http://localhost/projet%20ismo/api/test/rewards_check.php
 */

// Permettre l'accès sans authentification pour ce test
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require_once __DIR__ . '/../config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Système de Récompenses</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        h2 {
            color: #4CAF50;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .badge-success { background: #4CAF50; color: white; }
        .badge-warning { background: #ff9800; color: white; }
        .badge-info { background: #2196F3; color: white; }
        .badge-danger { background: #f44336; color: white; }
    </style>
</head>
<body>
    <h1>🎮 Test du Système de Récompenses</h1>
    
    <?php
    try {
        $db = getDBConnection();
        
        // 1. Statistiques globales
        echo '<div class="section">';
        echo '<h2>📊 Statistiques Globales</h2>';
        echo '<div class="stats">';
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM rewards WHERE is_active = 1");
        $totalRewards = $stmt->fetchColumn();
        echo "<div class='stat-card'><div class='stat-label'>Récompenses Actives</div><div class='stat-value'>{$totalRewards}</div></div>";
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM rewards WHERE reward_type = 'game_package' AND is_active = 1");
        $gamePackages = $stmt->fetchColumn();
        echo "<div class='stat-card'><div class='stat-label'>Packages de Jeu</div><div class='stat-value'>{$gamePackages}</div></div>";
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM reward_redemptions");
        $totalRedemptions = $stmt->fetchColumn();
        echo "<div class='stat-card'><div class='stat-label'>Total Échanges</div><div class='stat-value'>{$totalRedemptions}</div></div>";
        
        $stmt = $db->query("SELECT COALESCE(SUM(points_spent), 0) as total FROM reward_redemptions");
        $totalPoints = $stmt->fetchColumn();
        echo "<div class='stat-card'><div class='stat-label'>Points Échangés</div><div class='stat-value'>" . number_format($totalPoints) . "</div></div>";
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM purchases WHERE paid_with_points = 1");
        $sessionsFromRewards = $stmt->fetchColumn();
        echo "<div class='stat-card'><div class='stat-label'>Sessions depuis Récompenses</div><div class='stat-value'>{$sessionsFromRewards}</div></div>";
        
        echo '</div></div>';
        
        // 2. Récompenses disponibles
        echo '<div class="section">';
        echo '<h2>🎁 Récompenses Disponibles</h2>';
        $stmt = $db->query("
            SELECT 
                r.reward_id,
                r.title,
                r.description,
                r.reward_type,
                r.points_required,
                r.is_active,
                gp.package_id,
                gp.points_cost,
                gp.duration_minutes,
                g.title as game_title
            FROM rewards r
            LEFT JOIN game_packages gp ON r.game_package_id = gp.package_id
            LEFT JOIN games g ON gp.game_id = g.game_id
            WHERE r.is_active = 1
            ORDER BY r.reward_type, r.points_required
        ");
        
        $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rewards) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Titre</th><th>Type</th><th>Jeu</th><th>Durée</th><th>Points</th><th>Actif</th></tr>';
            foreach ($rewards as $reward) {
                $type = $reward['reward_type'] === 'game_package' ? '<span class="badge badge-success">Package Jeu</span>' : '<span class="badge badge-info">Autre</span>';
                $active = $reward['is_active'] ? '<span class="badge badge-success">Oui</span>' : '<span class="badge badge-danger">Non</span>';
                echo "<tr>";
                echo "<td>{$reward['reward_id']}</td>";
                echo "<td><strong>{$reward['title']}</strong><br><small>{$reward['description']}</small></td>";
                echo "<td>{$type}</td>";
                echo "<td>" . ($reward['game_title'] ?? 'N/A') . "</td>";
                echo "<td>" . ($reward['duration_minutes'] ?? 'N/A') . " min</td>";
                echo "<td><strong>" . number_format($reward['points_required']) . "</strong> pts</td>";
                echo "<td>{$active}</td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<p>⚠️ Aucune récompense active trouvée.</p>';
        }
        echo '</div>';
        
        // 3. Utilisateurs avec leurs points
        echo '<div class="section">';
        echo '<h2>👥 Utilisateurs et Points</h2>';
        $stmt = $db->query("
            SELECT 
                u.user_id,
                u.username,
                u.level,
                u.points,
                COUNT(DISTINCT pt.transaction_id) as total_transactions,
                COALESCE(SUM(CASE WHEN pt.points_change > 0 THEN pt.points_change ELSE 0 END), 0) as points_earned,
                COALESCE(SUM(CASE WHEN pt.points_change < 0 THEN ABS(pt.points_change) ELSE 0 END), 0) as points_spent
            FROM users u
            LEFT JOIN points_transactions pt ON u.user_id = pt.user_id
            WHERE u.is_active = 1 AND u.role = 'player'
            GROUP BY u.user_id
            ORDER BY u.points DESC
            LIMIT 10
        ");
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($users) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Utilisateur</th><th>Level</th><th>Points Actuels</th><th>Transactions</th><th>Points Gagnés</th><th>Points Dépensés</th></tr>';
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['user_id']}</td>";
                echo "<td><strong>{$user['username']}</strong></td>";
                echo "<td><span class='badge badge-info'>Lvl {$user['level']}</span></td>";
                echo "<td><strong>" . number_format($user['points']) . "</strong></td>";
                echo "<td>{$user['total_transactions']}</td>";
                echo "<td class='text-success'>" . number_format($user['points_earned']) . "</td>";
                echo "<td class='text-danger'>" . number_format($user['points_spent']) . "</td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<p>⚠️ Aucun utilisateur trouvé.</p>';
        }
        echo '</div>';
        
        // 4. Historique des échanges
        echo '<div class="section">';
        echo '<h2>📜 Historique des Échanges de Récompenses</h2>';
        $stmt = $db->query("
            SELECT 
                rr.redemption_id,
                u.username,
                r.title as reward_title,
                r.reward_type,
                rr.points_spent,
                rr.redeemed_at,
                CASE 
                    WHEN p.purchase_id IS NOT NULL AND gs.session_id IS NOT NULL THEN 'Session créée'
                    WHEN p.purchase_id IS NOT NULL THEN 'Achat créé'
                    ELSE 'En attente'
                END as status
            FROM reward_redemptions rr
            JOIN users u ON rr.user_id = u.user_id
            JOIN rewards r ON rr.reward_id = r.reward_id
            LEFT JOIN purchases p ON rr.redemption_id = p.redemption_id
            LEFT JOIN game_sessions gs ON p.purchase_id = gs.purchase_id
            ORDER BY rr.redeemed_at DESC
            LIMIT 20
        ");
        
        $redemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($redemptions) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Utilisateur</th><th>Récompense</th><th>Type</th><th>Points</th><th>Date</th><th>Statut</th></tr>';
            foreach ($redemptions as $red) {
                $statusBadge = $red['status'] === 'Session créée' ? 'badge-success' : ($red['status'] === 'Achat créé' ? 'badge-warning' : 'badge-info');
                echo "<tr>";
                echo "<td>{$red['redemption_id']}</td>";
                echo "<td><strong>{$red['username']}</strong></td>";
                echo "<td>{$red['reward_title']}</td>";
                echo "<td>{$red['reward_type']}</td>";
                echo "<td><strong>" . number_format($red['points_spent']) . "</strong> pts</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($red['redeemed_at'])) . "</td>";
                echo "<td><span class='badge {$statusBadge}'>{$red['status']}</span></td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<p>⚠️ Aucun échange trouvé.</p>';
        }
        echo '</div>';
        
        // 5. Sessions de jeu créées depuis récompenses
        echo '<div class="section">';
        echo '<h2>🎮 Sessions de Jeu créées depuis Récompenses</h2>';
        $stmt = $db->query("
            SELECT 
                gs.session_id,
                u.username,
                g.title as game_title,
                gs.duration_minutes,
                gs.session_status,
                gs.progress_percent,
                p.points_spent,
                gs.created_at
            FROM game_sessions gs
            JOIN users u ON gs.user_id = u.user_id
            JOIN games g ON gs.game_id = g.game_id
            JOIN purchases p ON gs.purchase_id = p.purchase_id
            WHERE p.paid_with_points = 1
            ORDER BY gs.created_at DESC
            LIMIT 20
        ");
        
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($sessions) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Utilisateur</th><th>Jeu</th><th>Durée</th><th>Statut</th><th>Progrès</th><th>Points</th><th>Créée le</th></tr>';
            foreach ($sessions as $session) {
                $statusBadge = 'badge-info';
                if ($session['session_status'] === 'active') $statusBadge = 'badge-success';
                if ($session['session_status'] === 'completed') $statusBadge = 'badge-warning';
                if ($session['progress_percent'] >= 100) $statusBadge = 'badge-success';
                
                echo "<tr>";
                echo "<td>{$session['session_id']}</td>";
                echo "<td><strong>{$session['username']}</strong></td>";
                echo "<td>{$session['game_title']}</td>";
                echo "<td>{$session['duration_minutes']} min</td>";
                echo "<td><span class='badge {$statusBadge}'>{$session['session_status']}</span></td>";
                echo "<td><strong>{$session['progress_percent']}%</strong></td>";
                echo "<td>" . number_format($session['points_spent']) . " pts</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($session['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<p>⚠️ Aucune session créée depuis récompenses.</p>';
        }
        echo '</div>';
        
        // 6. Packages payables uniquement en points
        echo '<div class="section">';
        echo '<h2>💎 Packages Payables Uniquement en Points</h2>';
        $stmt = $db->query("
            SELECT 
                gp.package_id,
                g.title as game_title,
                gp.name as package_name,
                gp.duration_minutes,
                gp.is_points_only,
                gp.points_cost,
                gp.reward_id,
                r.title as reward_title
            FROM game_packages gp
            JOIN games g ON gp.game_id = g.game_id
            LEFT JOIN rewards r ON gp.reward_id = r.reward_id
            WHERE gp.is_points_only = 1
            ORDER BY g.title, gp.duration_minutes
        ");
        
        $pointsPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($pointsPackages) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Jeu</th><th>Package</th><th>Durée</th><th>Coût Points</th><th>Récompense Liée</th></tr>';
            foreach ($pointsPackages as $pkg) {
                echo "<tr>";
                echo "<td>{$pkg['package_id']}</td>";
                echo "<td><strong>{$pkg['game_title']}</strong></td>";
                echo "<td>{$pkg['package_name']}</td>";
                echo "<td>{$pkg['duration_minutes']} min</td>";
                echo "<td><strong>" . number_format($pkg['points_cost']) . "</strong> pts</td>";
                echo "<td>" . ($pkg['reward_title'] ?? '<em>Aucune</em>') . "</td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<p>⚠️ Aucun package points-only trouvé.</p>';
        }
        echo '</div>';
        
        echo '<div class="section">';
        echo '<h2>✅ Diagnostic Final</h2>';
        if ($totalRewards > 0 && $gamePackages > 0) {
            echo '<p style="color: green; font-size: 1.2em;">✅ Le système de récompenses est configuré et fonctionnel!</p>';
            echo '<ul>';
            echo "<li><strong>{$totalRewards}</strong> récompenses actives dont <strong>{$gamePackages}</strong> packages de jeu</li>";
            echo "<li><strong>{$totalRedemptions}</strong> échanges effectués pour <strong>" . number_format($totalPoints) . "</strong> points</li>";
            echo "<li><strong>{$sessionsFromRewards}</strong> sessions de jeu créées depuis les récompenses</li>";
            echo '</ul>';
        } else {
            echo '<p style="color: orange;">⚠️ Le système est fonctionnel mais nécessite de créer des récompenses.</p>';
        }
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="section" style="background: #ffebee;">';
        echo '<h2 style="color: #c62828;">❌ Erreur</h2>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Fichier:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p><strong>Ligne:</strong> ' . $e->getLine() . '</p>';
        echo '</div>';
    }
    ?>
    
    <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f0f0f0; border-radius: 8px;">
        <p><strong>🧪 Test exécuté le:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><a href="http://localhost:4000" style="color: #4CAF50; text-decoration: none; font-weight: bold;">➡️ Accéder à l'application frontend</a></p>
    </div>
</body>
</html>
