<?php
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

echo "=== CRÉATION DES PACKAGES RÉCOMPENSES ===\n\n";

// Package 1: FIFA - 30 min - 50 points
echo "📦 Création package FIFA...\n";
$stmt = $pdo->prepare("
    INSERT INTO game_packages (
        game_id, name, duration_minutes, price, points_earned, 
        is_points_only, points_cost, is_active, display_order, 
        created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([1, 'Récompense FIFA - 30 min', 30, 0.00, 5, 1, 50, 1, 10]);
$fifaPackageId = $pdo->lastInsertId();
echo "  ✅ Package créé avec ID: $fifaPackageId\n";

// Mettre à jour la récompense FIFA
$stmt = $pdo->prepare("UPDATE rewards SET game_package_id = ? WHERE id = 12");
$stmt->execute([$fifaPackageId]);

// Mettre à jour le package avec le reward_id
$stmt = $pdo->prepare("UPDATE game_packages SET reward_id = 12 WHERE id = ?");
$stmt->execute([$fifaPackageId]);
echo "  ✅ Lié à la récompense ID 12\n\n";

// Package 2: ufvvhjk - 60 min - 100 points
echo "📦 Création package ufvvhjk...\n";
$stmt = $pdo->prepare("
    INSERT INTO game_packages (
        game_id, name, duration_minutes, price, points_earned, 
        is_points_only, points_cost, is_active, display_order, 
        created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([3, 'Récompense Action - 1h', 60, 0.00, 10, 1, 100, 1, 10]);
$actionPackageId = $pdo->lastInsertId();
echo "  ✅ Package créé avec ID: $actionPackageId\n";

// Mettre à jour la récompense COD (on l'utilise pour ce jeu)
$stmt = $pdo->prepare("UPDATE rewards SET game_package_id = ?, name = ? WHERE id = 13");
$stmt->execute([$actionPackageId, 'Action Game - 1 heure']);

// Mettre à jour le package avec le reward_id
$stmt = $pdo->prepare("UPDATE game_packages SET reward_id = 13 WHERE id = ?");
$stmt->execute([$actionPackageId]);
echo "  ✅ Lié à la récompense ID 13\n\n";

// Package 3: naruto - 30 min - 150 points
echo "📦 Création package Naruto...\n";
$stmt = $pdo->prepare("
    INSERT INTO game_packages (
        game_id, name, duration_minutes, price, points_earned, 
        is_points_only, points_cost, is_active, display_order, 
        created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([4, 'Récompense Naruto - 30 min', 30, 0.00, 15, 1, 150, 1, 10]);
$narutoPackageId = $pdo->lastInsertId();
echo "  ✅ Package créé avec ID: $narutoPackageId\n";

// Mettre à jour la récompense VR (on l'utilise pour Naruto)
$stmt = $pdo->prepare("UPDATE rewards SET game_package_id = ?, name = ?, description = ? WHERE id = 14");
$stmt->execute([
    $narutoPackageId, 
    'Naruto - 30 minutes',
    'Jouez 30 minutes à Naruto avec vos points. +15 points bonus en jouant!'
]);

// Mettre à jour le package avec le reward_id
$stmt = $pdo->prepare("UPDATE game_packages SET reward_id = 14 WHERE id = ?");
$stmt->execute([$narutoPackageId]);
echo "  ✅ Lié à la récompense ID 14\n\n";

echo "========================================\n";
echo "✅ PACKAGES CRÉÉS AVEC SUCCÈS!\n";
echo "========================================\n\n";

// Vérification
echo "📊 Vérification des packages créés:\n\n";
$stmt = $pdo->query("
    SELECT 
        pkg.id, pkg.name, pkg.points_cost, pkg.is_points_only,
        g.name as game_name,
        r.name as reward_name
    FROM game_packages pkg
    INNER JOIN games g ON pkg.game_id = g.id
    LEFT JOIN rewards r ON pkg.reward_id = r.id
    WHERE pkg.is_points_only = 1
");
$packages = $stmt->fetchAll();

foreach ($packages as $p) {
    echo "  ✅ {$p['name']}\n";
    echo "     Jeu: {$p['game_name']}\n";
    echo "     Coût: {$p['points_cost']} points\n";
    echo "     Récompense: {$p['reward_name']}\n\n";
}

echo "Total: " . count($packages) . " packages disponibles pour les joueurs!\n";
