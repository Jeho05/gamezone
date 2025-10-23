<?php
require_once __DIR__ . '/api/db.php';
$pdo = get_db_connection();

echo "=== VÉRIFICATION STRUCTURE GAME_SESSIONS ===\n\n";
$stmt = $pdo->query("DESCRIBE game_sessions");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Colonnes existantes:\n";
foreach ($columns as $col) {
    echo "- {$col['Field']} ({$col['Type']}) - {$col['Extra']}\n";
}

echo "\n=== VÉRIFICATION VUES SQL ===\n\n";
$views = ['game_stats', 'package_stats', 'active_sessions', 'point_packages', 'session_summary'];
foreach ($views as $view) {
    try {
        $stmt = $pdo->query("SHOW CREATE VIEW $view");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            echo "✅ Vue $view existe\n";
        }
    } catch (Exception $e) {
        echo "❌ Vue $view manquante: " . $e->getMessage() . "\n";
    }
}

echo "\n=== VÉRIFICATION PAYMENT_METHODS ===\n\n";
$stmt = $pdo->query("SELECT id, name, slug, provider, is_active FROM payment_methods");
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Méthodes de paiement:\n";
foreach ($methods as $method) {
    echo "- [{$method['id']}] {$method['name']} (provider: {$method['provider']}, actif: {$method['is_active']})\n";
}
