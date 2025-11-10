<?php
/**
 * VERSION ULTRA-SIMPLE - Scan facture avec débogage complet
 */

// Headers CORS en premier
header('Access-Control-Allow-Origin: https://gamezoneismo.vercel.app');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Activer les erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../utils.php';
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur chargement config', 'details' => $e->getMessage()]);
    exit;
}

// Vérifier authentification admin
try {
    $user = require_auth();
    if (!is_admin($user)) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès refusé - Admin uniquement', 'role' => $user['role'] ?? 'unknown']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié', 'details' => $e->getMessage()]);
    exit;
}

$pdo = get_db();

// GET: Vérifier un code
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $code = trim($_GET['code'] ?? '');
    if (!$code) {
        echo json_encode(['error' => 'Code requis']);
        exit;
    }
    
    // Nettoyer
    $code = strtoupper(preg_replace('/[-\s]/', '', $code));
    $codeFormatted = $code;
    if (strlen($code) === 16) {
        $codeFormatted = substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . 
                         substr($code, 8, 4) . '-' . substr($code, 12, 4);
    }
    
    try {
        $stmt = $pdo->prepare('
            SELECT i.*, u.username, u.email
            FROM invoices i
            INNER JOIN users u ON i.user_id = u.id
            WHERE i.validation_code = ? OR i.validation_code = ?
        ');
        $stmt->execute([$code, $codeFormatted]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            echo json_encode(['valid' => false, 'error' => 'Code invalide']);
            exit;
        }
        
        echo json_encode([
            'valid' => true,
            'can_activate' => $invoice['status'] === 'pending',
            'invoice' => [
                'status' => $invoice['status'],
                'game_name' => $invoice['game_name'],
                'duration_minutes' => $invoice['duration_minutes']
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erreur SQL', 'details' => $e->getMessage()]);
    }
    exit;
}

// POST: Activer
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Lire le body JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'JSON invalide', 'json_error' => json_last_error_msg()]);
    exit;
}

$codeRaw = trim($data['validation_code'] ?? '');
if (!$codeRaw) {
    echo json_encode(['error' => 'Code de validation requis', 'received_data' => $data]);
    exit;
}

// Nettoyer
$code = strtoupper(preg_replace('/[-\s]/', '', $codeRaw));
$codeFormatted = $code;
if (strlen($code) === 16) {
    $codeFormatted = substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . 
                     substr($code, 8, 4) . '-' . substr($code, 12, 4);
}

try {
    $pdo->beginTransaction();
    
    // Récupérer facture avec game_id ET payment_status
    $stmt = $pdo->prepare('
        SELECT i.*, p.id as purchase_id, p.game_id, p.payment_status
        FROM invoices i
        INNER JOIN purchases p ON i.purchase_id = p.id
        WHERE (i.validation_code = ? OR i.validation_code = ?)
    ');
    $stmt->execute([$code, $codeFormatted]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'invalid_code',
            'message' => 'Code invalide',
            'searched_codes' => [$code, $codeFormatted]
        ]);
        exit;
    }
    
    // Vérifier statut facture
    if ($invoice['status'] !== 'pending') {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'already_active',
            'message' => 'Facture déjà activée ou utilisée',
            'current_status' => $invoice['status']
        ]);
        exit;
    }
    
    // CRITIQUE: Vérifier que le paiement a été confirmé
    if ($invoice['payment_status'] !== 'confirmed' && $invoice['payment_status'] !== 'completed') {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'payment_pending',
            'message' => 'Le paiement n\'a pas encore été confirmé',
            'payment_status' => $invoice['payment_status']
        ]);
        exit;
    }
    
    // Logger scan (optionnel - ignorer si table n'existe pas)
    try {
        $stmt = $pdo->prepare('
            INSERT INTO invoice_scans (invoice_id, admin_user_id, ip_address, user_agent, scanned_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            $invoice['id'],
            $user['id'],
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (PDOException $e) {
        // Ignorer si la table n'existe pas
    }
    
    // Activer facture et enregistrer qui l'a activée
    $stmt = $pdo->prepare('UPDATE invoices SET status = "active", activated_at = NOW(), activated_by = ? WHERE id = ?');
    $stmt->execute([$user['id'], $invoice['id']]);
    
    // Créer session DIRECTEMENT en statut 'active' (pas de phase 'ready')
    $sessionId = null;
    $sessionPayload = null;
    $now = date('Y-m-d H:i:s');
    
    try {
        // Calculer expiration (2 heures après le début ou durée totale + 30min marge)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . ($invoice['duration_minutes'] + 30) . ' minutes'));
        
        // Créer session directement ACTIVE (sans passer par 'ready')
        $stmt = $pdo->prepare('
            INSERT INTO active_game_sessions_v2 
            (invoice_id, purchase_id, user_id, game_id, total_minutes, used_minutes, 
             status, ready_at, started_at, last_heartbeat, last_countdown_update, 
             expires_at, auto_countdown, countdown_interval, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 0, 
                    "active", ?, ?, ?, ?, 
                    ?, 1, 60, NOW(), NOW())
        ');
        $stmt->execute([
            $invoice['id'],
            $invoice['purchase_id'],
            $invoice['user_id'],
            $invoice['game_id'],
            $invoice['duration_minutes'],
            $now,  // ready_at
            $now,  // started_at
            $now,  // last_heartbeat
            $now,  // last_countdown_update
            $expiresAt
        ]);
        $sessionId = $pdo->lastInsertId();
        
        // Récupérer la session créée
        $stmt = $pdo->prepare('SELECT * FROM active_game_sessions_v2 WHERE id = ?');
        $stmt->execute([$sessionId]);
        $sessionPayload = $stmt->fetch();
        
    } catch (PDOException $e) {
        // Si échec de création, rollback complet
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'session_creation_failed',
            'message' => 'Impossible de créer la session de jeu',
            'details' => $e->getMessage()
        ]);
        exit;
    }
    
    // Mettre à jour purchase: session active
    try {
        $stmt = $pdo->prepare('
            UPDATE purchases 
            SET session_status = "active", updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$invoice['purchase_id']]);
    } catch (PDOException $e) {
        // Colonne optionnelle
    }
    
    $pdo->commit();
    
    // Récupérer détails
    $stmt = $pdo->prepare('
        SELECT i.*, u.username, u.email
        FROM invoices i
        INNER JOIN users u ON i.user_id = u.id
        WHERE i.id = ?
    ');
    $stmt->execute([$invoice['id']]);
    $invoiceDetails = $stmt->fetch();
    
    if ($sessionId) {
        $invoiceDetails['session_id'] = $sessionId;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Facture activée et session démarrée',
        'invoice' => $invoiceDetails,
        'session' => $sessionPayload,
        'session_status' => $sessionPayload['status'] ?? 'unknown',
        'next_action' => 'session_started'
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'error' => 'Erreur base de données',
        'details' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'error' => 'Erreur inattendue',
        'details' => $e->getMessage()
    ]);
}
