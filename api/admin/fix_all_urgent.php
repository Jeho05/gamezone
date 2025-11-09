<?php
/**
 * Script de correction urgente pour tous les problèmes
 * À exécuter UNE FOIS
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

// Sécurité
if (!isset($_GET['fix_key']) || $_GET['fix_key'] !== 'gamezone2025') {
    json_response(['error' => 'Clé requise: ?fix_key=gamezone2025'], 401);
}

$pdo = get_db();
$results = [];

try {
    // FIX 1: Nettoyer les avatars avec anciennes URLs localhost
    $stmt = $pdo->query("
        SELECT id, username, avatar_url 
        FROM users 
        WHERE avatar_url IS NOT NULL 
          AND (avatar_url LIKE '%localhost%' OR avatar_url LIKE '%uploads/avatars/%')
          AND avatar_url NOT LIKE '%get_avatar.php%'
    ");
    $usersToFix = $stmt->fetchAll();
    
    $results['avatars_fixed'] = 0;
    foreach ($usersToFix as $user) {
        // Mettre à NULL pour forcer un nouvel upload
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        $results['avatars_fixed']++;
    }
    
    $results['users_affected'] = array_map(function($u) {
        return ['id' => $u['id'], 'username' => $u['username']];
    }, $usersToFix);
    
    // FIX 2: Vérifier que la procédure activate_invoice existe
    $stmt = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name = 'activate_invoice'");
    $procedureExists = $stmt->rowCount() > 0;
    $results['activate_invoice_exists'] = $procedureExists;
    
    if (!$procedureExists) {
        $results['warning'] = 'Procédure activate_invoice manquante - le scan de factures ne fonctionnera pas';
    }
    
    // FIX 3: Vérifier que check_availability.php est accessible
    $checkFile = __DIR__ . '/../shop/check_availability.php';
    $results['check_availability_exists'] = file_exists($checkFile);
    $results['check_availability_path'] = $checkFile;
    $results['check_availability_readable'] = is_readable($checkFile);
    
    // FIX 4: Vérifier les tables nécessaires
    $tables = ['user_avatars', 'game_images', 'game_reservations', 'invoice_scans'];
    $results['tables_status'] = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $results['tables_status'][$table] = $stmt->rowCount() > 0;
    }
    
    // FIX 5: Compter les avatars BASE64 existants
    if ($results['tables_status']['user_avatars']) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM user_avatars");
        $results['avatars_base64_count'] = $stmt->fetch()['cnt'];
    }
    
    $results['success'] = true;
    $results['message'] = 'Corrections appliquées';
    $results['timestamp'] = date('Y-m-d H:i:s');
    
    json_response($results);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors des corrections',
        'details' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 500);
}
