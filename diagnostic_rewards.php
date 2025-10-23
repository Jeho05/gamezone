<?php
// diagnostic_rewards.php
// Diagnostic complet du système de récompenses

// Connexion directe à la DB
$host = 'localhost';
$dbname = 'gamezone';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion: " . $e->getMessage());
}

echo "=== DIAGNOSTIC DU SYSTÈME DE RÉCOMPENSES ===\n\n";

// 1. Vérifier toutes les récompenses
echo "1️⃣  RÉCOMPENSES DANS LA TABLE 'rewards'\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query("
    SELECT 
        id, name, cost, reward_type, available, 
        is_featured, game_package_id
    FROM rewards 
    ORDER BY id
");
$allRewards = $stmt->fetchAll();
echo "Total: " . count($allRewards) . " récompenses\n\n";
foreach ($allRewards as $r) {
    echo "  ID: {$r['id']}\n";
    echo "  Nom: {$r['name']}\n";
    echo "  Type: {$r['reward_type']}\n";
    echo "  Coût: {$r['cost']} points\n";
    echo "  Disponible: " . ($r['available'] ? "✅ Oui" : "❌ Non") . "\n";
    echo "  En vedette: " . ($r['is_featured'] ? "⭐ Oui" : "Non") . "\n";
    echo "  Package ID: " . ($r['game_package_id'] ?? 'NULL') . "\n";
    echo "\n";
}

// 2. Vérifier les packages de jeu
echo "\n2️⃣  PACKAGES DE JEU DANS LA TABLE 'game_packages'\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query("
    SELECT 
        gp.id, gp.game_id, gp.name, gp.duration_minutes,
        gp.points_cost, gp.points_earned, gp.is_points_only,
        gp.is_active, gp.reward_id, gp.available_from, gp.available_until,
        g.name as game_name, g.is_active as game_active
    FROM game_packages gp
    LEFT JOIN games g ON gp.game_id = g.id
    ORDER BY gp.id
");
$allPackages = $stmt->fetchAll();
echo "Total: " . count($allPackages) . " packages\n\n";
foreach ($allPackages as $p) {
    echo "  Package ID: {$p['id']}\n";
    echo "  Nom: {$p['name']}\n";
    echo "  Jeu: {$p['game_name']} (ID: {$p['game_id']})\n";
    echo "  Durée: {$p['duration_minutes']} min\n";
    echo "  Points only: " . ($p['is_points_only'] ? "✅ Oui" : "❌ Non") . "\n";
    echo "  Coût: " . ($p['points_cost'] ?? 'NULL') . " points\n";
    echo "  Points gagnés: {$p['points_earned']} points\n";
    echo "  Package actif: " . ($p['is_active'] ? "✅ Oui" : "❌ Non") . "\n";
    echo "  Jeu actif: " . ($p['game_active'] ? "✅ Oui" : "❌ Non") . "\n";
    echo "  Reward ID: " . ($p['reward_id'] ?? 'NULL') . "\n";
    echo "  Dispo de: " . ($p['available_from'] ?? 'NULL') . "\n";
    echo "  Dispo jusqu'à: " . ($p['available_until'] ?? 'NULL') . "\n";
    echo "\n";
}

// 3. Packages visibles pour les joueurs (selon les critères de l'API)
echo "\n3️⃣  PACKAGES VISIBLES POUR LES JOUEURS\n";
echo str_repeat("-", 80) . "\n";
echo "Critères: is_points_only=1, package actif, jeu actif, dates OK\n\n";
$stmt = $pdo->query("
    SELECT 
        pkg.id,
        pkg.name as package_name,
        g.name as game_name,
        pkg.points_cost,
        pkg.duration_minutes,
        pkg.is_active,
        g.is_active as game_active,
        r.name as reward_name,
        r.available as reward_available
    FROM game_packages pkg
    INNER JOIN games g ON pkg.game_id = g.id
    LEFT JOIN rewards r ON pkg.reward_id = r.id
    WHERE pkg.is_points_only = 1 
      AND pkg.is_active = 1
      AND g.is_active = 1
      AND (pkg.available_from IS NULL OR pkg.available_from <= NOW())
      AND (pkg.available_until IS NULL OR pkg.available_until >= NOW())
");
$visiblePackages = $stmt->fetchAll();
echo "Total: " . count($visiblePackages) . " packages visibles\n\n";
if (count($visiblePackages) > 0) {
    foreach ($visiblePackages as $vp) {
        echo "  ✅ {$vp['package_name']}\n";
        echo "     Jeu: {$vp['game_name']}\n";
        echo "     Coût: {$vp['points_cost']} points\n";
        echo "     Durée: {$vp['duration_minutes']} min\n";
        echo "     Récompense liée: " . ($vp['reward_name'] ?? 'Aucune') . "\n";
        echo "\n";
    }
} else {
    echo "  ❌ AUCUN PACKAGE VISIBLE !\n\n";
}

// 4. Diagnostique les problèmes
echo "\n4️⃣  DIAGNOSTIQUE DES PROBLÈMES\n";
echo str_repeat("-", 80) . "\n";

// Packages points-only mais inactifs
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM game_packages 
    WHERE is_points_only = 1 AND is_active = 0
");
$inactivePackages = $stmt->fetchColumn();
if ($inactivePackages > 0) {
    echo "⚠️  {$inactivePackages} package(s) points-only mais INACTIF(S)\n";
}

// Packages points-only avec jeu inactif
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM game_packages pkg
    INNER JOIN games g ON pkg.game_id = g.id
    WHERE pkg.is_points_only = 1 AND pkg.is_active = 1 AND g.is_active = 0
");
$inactiveGames = $stmt->fetchColumn();
if ($inactiveGames > 0) {
    echo "⚠️  {$inactiveGames} package(s) avec JEU INACTIF\n";
}

// Packages avec dates de disponibilité
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM game_packages 
    WHERE is_points_only = 1 
      AND is_active = 1
      AND (
        (available_from IS NOT NULL AND available_from > NOW())
        OR (available_until IS NOT NULL AND available_until < NOW())
      )
");
$dateIssues = $stmt->fetchColumn();
if ($dateIssues > 0) {
    echo "⚠️  {$dateIssues} package(s) avec DATES DE DISPONIBILITÉ non valides\n";
}

// Rewards de type game_package sans package
$stmt = $pdo->query("
    SELECT id, name 
    FROM rewards 
    WHERE reward_type = 'game_package' AND game_package_id IS NULL
");
$orphanRewards = $stmt->fetchAll();
if (count($orphanRewards) > 0) {
    echo "⚠️  " . count($orphanRewards) . " récompense(s) de type game_package SANS PACKAGE:\n";
    foreach ($orphanRewards as $or) {
        echo "    - ID {$or['id']}: {$or['name']}\n";
    }
}

echo "\n=== FIN DU DIAGNOSTIC ===\n";
