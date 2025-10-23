<?php
/**
 * Initialise les règles de points par défaut
 * À exécuter une seule fois pour créer les règles de base
 */

require_once __DIR__ . '/../utils.php';

$admin = require_auth('admin');
$pdo = get_db();

try {
    // Vérifier si des règles existent déjà
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM points_rules');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        json_response([
            'message' => 'Des règles existent déjà',
            'count' => $result['count']
        ]);
    }
    
    // Règles de points par défaut
    $defaultRules = [
        [
            'action_type' => 'session_complete',
            'points_amount' => 100,
            'description' => 'Points gagnés à la fin d\'une session de jeu complète',
            'is_active' => 1
        ],
        [
            'action_type' => 'daily_login',
            'points_amount' => 10,
            'description' => 'Bonus de connexion quotidien',
            'is_active' => 1
        ],
        [
            'action_type' => 'first_purchase',
            'points_amount' => 50,
            'description' => 'Bonus pour le premier achat',
            'is_active' => 1
        ],
        [
            'action_type' => 'referral',
            'points_amount' => 200,
            'description' => 'Points pour avoir parrainé un ami',
            'is_active' => 1
        ],
        [
            'action_type' => 'achievement',
            'points_amount' => 150,
            'description' => 'Points pour avoir débloqué un succès',
            'is_active' => 1
        ]
    ];
    
    $stmt = $pdo->prepare('
        INSERT INTO points_rules 
        (action_type, points_amount, description, is_active, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    
    $now = now();
    $created = 0;
    
    foreach ($defaultRules as $rule) {
        $stmt->execute([
            $rule['action_type'],
            $rule['points_amount'],
            $rule['description'],
            $rule['is_active'],
            $now,
            $now
        ]);
        $created++;
    }
    
    json_response([
        'success' => true,
        'message' => 'Règles de points créées avec succès',
        'created' => $created
    ], 201);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors de la création des règles',
        'details' => $e->getMessage()
    ], 500);
}
