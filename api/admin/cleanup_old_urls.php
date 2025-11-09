<?php
/**
 * Script de nettoyage des anciennes URLs filesystem
 * À exécuter une seule fois pour migrer vers BASE64
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

// Authentification avec clé secrète
if (!isset($_GET['cleanup_key']) || $_GET['cleanup_key'] !== 'gamezone2025') {
    json_response([
        'error' => 'Clé secrète requise',
        'usage' => 'Ajoutez ?cleanup_key=gamezone2025 à l\'URL'
    ], 401);
}

try {
    $pdo = get_db();
    $results = [];
    
    // 1. Nettoyer les avatars avec anciennes URLs filesystem
    $stmt = $pdo->query("
        SELECT id, username, avatar_url 
        FROM users 
        WHERE avatar_url LIKE '%localhost%' 
           OR avatar_url LIKE '%/uploads/avatars/%'
    ");
    $usersToClean = $stmt->fetchAll();
    
    if (count($usersToClean) > 0) {
        foreach ($usersToClean as $user) {
            // Mettre à NULL les anciennes URLs
            $stmt = $pdo->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
        }
        $results['avatars_cleaned'] = count($usersToClean);
        $results['users_affected'] = array_map(function($u) {
            return ['id' => $u['id'], 'username' => $u['username']];
        }, $usersToClean);
    } else {
        $results['avatars_cleaned'] = 0;
    }
    
    // 2. Nettoyer les images de jeux avec anciennes URLs
    $stmt = $pdo->query("
        SELECT id, name, image_url, thumbnail_url 
        FROM games 
        WHERE image_url LIKE '%localhost%' 
           OR image_url LIKE '%via.placeholder%'
           OR image_url LIKE '%/uploads/games/%'
    ");
    $gamesToClean = $stmt->fetchAll();
    
    if (count($gamesToClean) > 0) {
        foreach ($gamesToClean as $game) {
            // Vérifier s'il existe une version BASE64
            $stmt = $pdo->prepare("
                SELECT id FROM game_images 
                WHERE filename LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute(['%' . $game['id'] . '%']);
            $base64Image = $stmt->fetch();
            
            if ($base64Image) {
                // Mettre à jour avec l'URL BASE64
                $newUrl = 'https://overflowing-fulfillment-production-36c6.up.railway.app/api/admin/get_image.php?id=' . $base64Image['id'];
                $stmt = $pdo->prepare("UPDATE games SET image_url = ? WHERE id = ?");
                $stmt->execute([$newUrl, $game['id']]);
                $results['games_migrated_to_base64'][] = [
                    'id' => $game['id'],
                    'name' => $game['name'],
                    'new_url' => $newUrl
                ];
            } else {
                // Pas de version BASE64, mettre à NULL
                $stmt = $pdo->prepare("UPDATE games SET image_url = NULL, thumbnail_url = NULL WHERE id = ?");
                $stmt->execute([$game['id']]);
                $results['games_need_reupload'][] = [
                    'id' => $game['id'],
                    'name' => $game['name']
                ];
            }
        }
    }
    
    $results['summary'] = [
        'avatars_cleaned' => $results['avatars_cleaned'] ?? 0,
        'games_migrated' => count($results['games_migrated_to_base64'] ?? []),
        'games_need_reupload' => count($results['games_need_reupload'] ?? [])
    ];
    
    json_response([
        'success' => true,
        'message' => 'Nettoyage terminé',
        'results' => $results
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors du nettoyage',
        'details' => $e->getMessage()
    ], 500);
}
