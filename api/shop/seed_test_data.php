<?php
// Script pour insérer des données de test dans la boutique
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Nécessite admin
$user = require_auth('admin');

$pdo = get_db();

try {
    $pdo->beginTransaction();
    
    // Vérifier si des jeux existent déjà
    $stmt = $pdo->query("SELECT COUNT(*) FROM games");
    $gamesCount = $stmt->fetchColumn();
    
    if ($gamesCount == 0) {
        echo "Insertion de jeux de test...\n";
        
        // Insérer quelques jeux
        $games = [
            [
                'name' => 'FIFA 2024',
                'slug' => 'fifa-2024',
                'description' => 'Le jeu de football le plus populaire au monde !',
                'short_description' => 'Football simulation',
                'category' => 'sports',
                'platform' => 'PS5',
                'min_players' => 1,
                'max_players' => 4,
                'age_rating' => '3+',
                'points_per_hour' => 15,
                'base_price' => 1000,
                'is_featured' => 1,
                'is_active' => 1
            ],
            [
                'name' => 'Call of Duty: Warzone',
                'slug' => 'cod-warzone',
                'description' => 'Battle royale intense et action militaire',
                'short_description' => 'FPS Battle Royale',
                'category' => 'action',
                'platform' => 'PC',
                'min_players' => 1,
                'max_players' => 4,
                'age_rating' => '18+',
                'points_per_hour' => 20,
                'base_price' => 1500,
                'is_featured' => 1,
                'is_active' => 1
            ],
            [
                'name' => 'Grand Theft Auto V',
                'slug' => 'gta-v',
                'description' => 'Explorez Los Santos dans ce jeu en monde ouvert',
                'short_description' => 'Open World Action',
                'category' => 'action',
                'platform' => 'PS5',
                'min_players' => 1,
                'max_players' => 1,
                'age_rating' => '18+',
                'points_per_hour' => 18,
                'base_price' => 1200,
                'is_featured' => 0,
                'is_active' => 1
            ],
            [
                'name' => 'Need for Speed Heat',
                'slug' => 'nfs-heat',
                'description' => 'Course de rue illégale dans Palm City',
                'short_description' => 'Racing',
                'category' => 'racing',
                'platform' => 'PS5',
                'min_players' => 1,
                'max_players' => 2,
                'age_rating' => '12+',
                'points_per_hour' => 12,
                'base_price' => 800,
                'is_featured' => 0,
                'is_active' => 1
            ]
        ];
        
        $gameIds = [];
        foreach ($games as $game) {
            $stmt = $pdo->prepare('
                INSERT INTO games (name, slug, description, short_description, category, platform,
                                 min_players, max_players, age_rating, points_per_hour, base_price,
                                 is_featured, is_active, display_order, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
            ');
            $stmt->execute([
                $game['name'],
                $game['slug'],
                $game['description'],
                $game['short_description'],
                $game['category'],
                $game['platform'],
                $game['min_players'],
                $game['max_players'],
                $game['age_rating'],
                $game['points_per_hour'],
                $game['base_price'],
                $game['is_featured'],
                $game['is_active']
            ]);
            $gameIds[$game['slug']] = $pdo->lastInsertId();
        }
        
        echo "Jeux insérés: " . count($gameIds) . "\n";
        
        // Insérer des packages pour chaque jeu
        echo "Insertion de packages...\n";
        foreach ($gameIds as $slug => $gameId) {
            $packages = [
                [
                    'name' => 'Découverte (15 min)',
                    'duration_minutes' => 15,
                    'price' => 500,
                    'points_earned' => 5,
                    'bonus_multiplier' => 1.0
                ],
                [
                    'name' => 'Standard (1h)',
                    'duration_minutes' => 60,
                    'price' => 1500,
                    'points_earned' => 20,
                    'bonus_multiplier' => 1.0
                ],
                [
                    'name' => 'Premium (2h)',
                    'duration_minutes' => 120,
                    'price' => 2500,
                    'points_earned' => 45,
                    'bonus_multiplier' => 1.2,
                    'is_promotional' => 1,
                    'promotional_label' => '⭐ POPULAIRE'
                ]
            ];
            
            foreach ($packages as $pkg) {
                $stmt = $pdo->prepare('
                    INSERT INTO game_packages (game_id, name, duration_minutes, price, points_earned,
                                             bonus_multiplier, is_promotional, promotional_label,
                                             is_active, display_order, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, NOW(), NOW())
                ');
                $stmt->execute([
                    $gameId,
                    $pkg['name'],
                    $pkg['duration_minutes'],
                    $pkg['price'],
                    $pkg['points_earned'],
                    $pkg['bonus_multiplier'],
                    $pkg['is_promotional'] ?? 0,
                    $pkg['promotional_label'] ?? null
                ]);
            }
        }
        
        echo "Packages insérés\n";
    }
    
    // Vérifier et insérer des méthodes de paiement
    $stmt = $pdo->query("SELECT COUNT(*) FROM payment_methods");
    $pmCount = $stmt->fetchColumn();
    
    if ($pmCount == 0) {
        echo "Insertion de méthodes de paiement...\n";
        
        $methods = [
            [
                'name' => 'Espèces (sur place)',
                'requires_online' => 0,
                'auto_confirm' => 0,
                'instructions' => 'Payez en espèces à la caisse lors de votre arrivée.'
            ],
            [
                'name' => 'Mobile Money',
                'requires_online' => 1,
                'auto_confirm' => 1,
                'instructions' => 'Envoyez le montant au numéro affiché et conservez la référence.'
            ],
            [
                'name' => 'Carte bancaire',
                'requires_online' => 1,
                'auto_confirm' => 1,
                'instructions' => 'Paiement sécurisé par carte bancaire.'
            ]
        ];
        
        foreach ($methods as $method) {
            $stmt = $pdo->prepare('
                INSERT INTO payment_methods (name, requires_online_payment, auto_confirm_payment,
                                           instructions, is_active, display_order, created_at, updated_at)
                VALUES (?, ?, ?, ?, 1, 0, NOW(), NOW())
            ');
            $stmt->execute([
                $method['name'],
                $method['requires_online'],
                $method['auto_confirm'],
                $method['instructions']
            ]);
        }
        
        echo "Méthodes de paiement insérées\n";
    }
    
    $pdo->commit();
    
    json_response([
        'success' => true,
        'message' => 'Données de test insérées avec succès',
        'games_count' => count($gameIds ?? [])
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    json_response([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
