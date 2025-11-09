<?php
/**
 * Script d'installation automatique du système d'images BASE64
 * Crée les tables nécessaires pour stocker images et avatars en base de données
 * 
 * Usage: Accéder via navigateur ou exécuter en CLI
 * URL: https://your-domain.com/api/admin/setup_images_system.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Authentification requise (admin)
$user = require_auth('admin');

header('Content-Type: application/json');

try {
    $pdo = get_db();
    $results = [];
    
    // ============================================================================
    // 1. Créer la table game_images (pour les images de jeux)
    // ============================================================================
    $sql_game_images = "
    CREATE TABLE IF NOT EXISTS `game_images` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `filename` varchar(255) NOT NULL,
      `data` longtext NOT NULL COMMENT 'Image encodée en base64',
      `mime_type` varchar(50) NOT NULL DEFAULT 'image/jpeg',
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_filename` (`filename`),
      KEY `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $pdo->exec($sql_game_images);
        $results['game_images'] = [
            'status' => 'success',
            'message' => 'Table game_images créée ou déjà existante'
        ];
    } catch (PDOException $e) {
        $results['game_images'] = [
            'status' => 'error',
            'message' => 'Erreur lors de la création de game_images: ' . $e->getMessage()
        ];
    }
    
    // ============================================================================
    // 2. Créer la table user_avatars (pour les avatars des utilisateurs)
    // ============================================================================
    $sql_user_avatars = "
    CREATE TABLE IF NOT EXISTS `user_avatars` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `filename` varchar(255) NOT NULL,
      `data` longtext NOT NULL COMMENT 'Avatar encodé en base64',
      `mime_type` varchar(50) NOT NULL DEFAULT 'image/jpeg',
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `idx_user_id` (`user_id`),
      KEY `idx_created` (`created_at`),
      CONSTRAINT `fk_avatar_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $pdo->exec($sql_user_avatars);
        $results['user_avatars'] = [
            'status' => 'success',
            'message' => 'Table user_avatars créée ou déjà existante'
        ];
    } catch (PDOException $e) {
        $results['user_avatars'] = [
            'status' => 'error',
            'message' => 'Erreur lors de la création de user_avatars: ' . $e->getMessage()
        ];
    }
    
    // ============================================================================
    // 3. Vérifier que les tables existent
    // ============================================================================
    $check_tables = $pdo->query("SHOW TABLES LIKE 'game_images'");
    $game_images_exists = $check_tables->rowCount() > 0;
    
    $check_tables = $pdo->query("SHOW TABLES LIKE 'user_avatars'");
    $user_avatars_exists = $check_tables->rowCount() > 0;
    
    // ============================================================================
    // 4. Compter les enregistrements
    // ============================================================================
    $game_images_count = 0;
    $user_avatars_count = 0;
    
    if ($game_images_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM game_images");
        $game_images_count = $stmt->fetch()['cnt'];
    }
    
    if ($user_avatars_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM user_avatars");
        $user_avatars_count = $stmt->fetch()['cnt'];
    }
    
    // ============================================================================
    // Résultat final
    // ============================================================================
    json_response([
        'success' => true,
        'message' => 'Installation du système d\'images BASE64 terminée',
        'results' => $results,
        'verification' => [
            'game_images' => [
                'exists' => $game_images_exists,
                'count' => $game_images_count
            ],
            'user_avatars' => [
                'exists' => $user_avatars_exists,
                'count' => $user_avatars_count
            ]
        ],
        'next_steps' => [
            '1. Les tables sont prêtes',
            '2. Testez l\'upload d\'image de jeu via /admin/shop',
            '3. Testez l\'upload d\'avatar via /player/profile',
            '4. Les images seront stockées en base64 dans MySQL',
            '5. Accès via: /api/admin/get_image.php?id=X et /api/users/get_avatar.php?id=X'
        ],
        'admin' => [
            'username' => $user['username'],
            'role' => $user['role']
        ]
    ]);
    
} catch (Exception $e) {
    json_response([
        'success' => false,
        'error' => 'Erreur lors de l\'installation: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 500);
}
