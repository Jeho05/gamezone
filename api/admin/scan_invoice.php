<?php
// api/admin/scan_invoice.php
// API pour scanner et activer une facture (ADMIN ONLY)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que c'est un admin
$user = require_auth();
if (!is_admin($user)) {
    json_response(['error' => 'Accès refusé - Admin uniquement'], 403);
}

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// Détecter si la table game_reservations existe (compatibilité si migration non appliquée)
$hasReservationsTable = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'game_reservations'");
    $hasReservationsTable = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {
    $hasReservationsTable = false;
}

// POST: Scanner et activer une facture
if ($method === 'POST') {
    $data = get_json_input();
    $validationCodeRaw = trim($data['validation_code'] ?? '');
    
    if (!$validationCodeRaw) {
        json_response(['error' => 'Code de validation requis'], 400);
    }
    
    // Nettoyer le code : enlever tirets, espaces, mettre en majuscules
    $validationCode = strtoupper(preg_replace('/[-\s]/', '', $validationCodeRaw));
    
    // Validation du format du code (8 OU 16 caractères alphanumériques pour compatibilité)
    if (!preg_match('/^[A-Z0-9]{8}$/', $validationCode) && !preg_match('/^[A-Z0-9]{16}$/', $validationCode)) {
        json_response(['error' => 'Format de code invalide (8 ou 16 caractères requis)'], 400);
    }
    
    // Reformater le code pour la recherche en BD (avec tirets si 16 chars)
    $validationCodeFormatted = $validationCode;
    if (strlen($validationCode) === 16) {
        $validationCodeFormatted = substr($validationCode, 0, 4) . '-' . 
                                   substr($validationCode, 4, 4) . '-' . 
                                   substr($validationCode, 8, 4) . '-' . 
                                   substr($validationCode, 12, 4);
    }
    
    // Vérifier les tentatives de scan multiples (anti-fraude)
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count 
        FROM invoice_scans 
        WHERE ip_address = ? AND scanned_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ');
    $stmt->execute([$_SERVER['REMOTE_ADDR']]);
    $result = $stmt->fetch();
    
    if ($result['count'] >= 10) {
        json_response([
            'error' => 'Trop de tentatives de scan',
            'message' => 'Veuillez patienter quelques minutes avant de réessayer'
        ], 429);
    }
    
    // Vérifier une éventuelle réservation liée à la facture (si la table existe)
    // IMPORTANT: Chercher à la fois avec ET sans tirets car les codes peuvent être dans les 2 formats
    $sql = 'SELECT i.id as invoice_id, i.purchase_id';
    if ($hasReservationsTable) {
        $sql .= ', r.scheduled_start, r.scheduled_end, r.status as reservation_status';
    }
    $sql .= ' FROM invoices i ';
    if ($hasReservationsTable) {
        $sql .= 'LEFT JOIN game_reservations r ON r.purchase_id = i.purchase_id ';
    }
    $sql .= 'WHERE (i.validation_code = ? OR i.validation_code = ?) LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$validationCode, $validationCodeFormatted]); // Chercher les 2 formats
    $invoiceRow = $stmt->fetch();
    
    // Vérifier UNIQUEMENT si une réservation existe réellement (scheduled_start ET reservation_status non NULL)
    if ($hasReservationsTable && $invoiceRow && 
        !empty($invoiceRow['scheduled_start']) && 
        !is_null($invoiceRow['reservation_status'])) {
        // Si une réservation existe: elle doit être payée et dans la fenêtre horaire
        if ($invoiceRow['reservation_status'] !== 'paid') {
            json_response([
                'success' => false,
                'error' => 'reservation_not_paid',
                'message' => 'La réservation associée n\'est pas encore payée'
            ], 400);
        }
        $now = new DateTime();
        $start = new DateTime($invoiceRow['scheduled_start']);
        $end = new DateTime($invoiceRow['scheduled_end']);
        if ($now < $start) {
            $minutes = (int) floor(($start->getTimestamp() - $now->getTimestamp()) / 60);
            json_response([
                'success' => false,
                'error' => 'reservation_too_early',
                'message' => 'Activation trop tôt: la session ne peut être activée qu\'à l\'heure réservée',
                'minutes_until_start' => $minutes,
                'scheduled_start' => $start->format('Y-m-d H:i:s')
            ], 400);
        }
        if ($now > $end) {
            json_response([
                'success' => false,
                'error' => 'reservation_expired',
                'message' => 'Le créneau de réservation est dépassé',
                'scheduled_end' => $end->format('Y-m-d H:i:s')
            ], 400);
        }
    }
    
    // Récupérer le code exact tel qu'il est en BD (pour activate_invoice)
    $stmt = $pdo->prepare('SELECT validation_code FROM invoices WHERE validation_code = ? OR validation_code = ? LIMIT 1');
    $stmt->execute([$validationCode, $validationCodeFormatted]);
    $invoiceCodeInDB = $stmt->fetchColumn();
    
    if (!$invoiceCodeInDB) {
        json_response([
            'success' => false,
            'error' => 'invalid_code',
            'message' => 'Code de validation invalide'
        ], 400);
    }
    
    // Appeler la procédure stockée pour activer la facture avec le code exact de la BD
    $stmt = $pdo->prepare('CALL activate_invoice(?, ?, ?, ?, @result, @invoice_id, @session_id)');
    $stmt->execute([
        $invoiceCodeInDB, // Utiliser le code exact tel qu'il est en BD
        $user['id'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    // Récupérer les résultats
    $stmt = $pdo->query('SELECT @result as result, @invoice_id as invoice_id, @session_id as session_id');
    $result = $stmt->fetch();
    
    if ($result['result'] === 'success') {
        // Démarrer automatiquement la session créée par activate_invoice
        if (!empty($result['session_id'])) {
            $stmt = $pdo->prepare('CALL start_session(?, ?, @start_result)');
            $stmt->execute([$result['session_id'], $user['id']]);
            $stmt = $pdo->query('SELECT @start_result as start_result');
            $start = $stmt->fetch();
            // Note: Le trigger sync_session_to_purchase synchronise automatiquement
            // purchases.session_status quand start_session modifie le statut de la session
        }
        // Récupérer les détails de la facture et session
        $stmt = $pdo->prepare('
            SELECT i.*, 
                   u.username, u.email,
                   s.id as session_id, s.total_minutes, s.status as session_status
            FROM invoices i
            INNER JOIN users u ON i.user_id = u.id
            LEFT JOIN active_game_sessions_v2 s ON i.id = s.invoice_id
            WHERE i.id = ?
        ');
        $stmt->execute([$result['invoice_id']]);
        $invoice = $stmt->fetch();
        
        json_response([
            'success' => true,
            'message' => 'Facture activée avec succès',
            'invoice' => $invoice,
            'next_action' => 'session_started'
        ], 200);
    } else {
        // Mapper les codes d'erreur en messages
        $errorMessages = [
            'invalid_code' => 'Code de validation invalide',
            'already_active' => 'Cette facture a déjà été activée',
            'already_used' => 'Cette facture a déjà été utilisée',
            'already_cancelled' => 'Cette facture a été annulée',
            'expired' => 'Cette facture a expiré (valide 2 mois)',
            'fraud_detected' => 'Activité suspecte détectée - Facture bloquée',
            'payment_pending' => 'Le paiement n\'a pas encore été confirmé',
            'payment_failed' => 'Le paiement a échoué',
            'no_invoice' => 'Aucune facture trouvée pour ce code'
        ];
        
        $message = $errorMessages[$result['result']] ?? 'Erreur lors de l\'activation';
        
        // Log pour debug
        log_error('Erreur activation facture', [
            'validation_code_raw' => $validationCodeRaw,
            'validation_code_formatted' => $validationCodeFormatted,
            'error_code' => $result['result'],
            'message' => $message,
            'admin_id' => $user['id']
        ]);
        
        json_response([
            'success' => false,
            'error' => $result['result'],
            'message' => $message,
            'debug_info' => $result['result'] // Pour voir le code exact
        ], 400);
    }
}

// GET: Vérifier un code sans l'activer
if ($method === 'GET') {
    $validationCodeRaw = trim($_GET['code'] ?? '');
    
    if (!$validationCodeRaw) {
        json_response(['error' => 'Code requis'], 400);
    }
    
    // Nettoyer le code : enlever tirets, espaces, mettre en majuscules
    $validationCode = strtoupper(preg_replace('/[-\s]/', '', $validationCodeRaw));
    
    // Reformater le code pour la recherche en BD (avec tirets si 16 chars)
    $validationCodeFormatted = $validationCode;
    if (strlen($validationCode) === 16) {
        $validationCodeFormatted = substr($validationCode, 0, 4) . '-' . 
                                   substr($validationCode, 4, 4) . '-' . 
                                   substr($validationCode, 8, 4) . '-' . 
                                   substr($validationCode, 12, 4);
    }
    
    $stmt = $pdo->prepare('
        SELECT i.*,
               u.username, u.email,
               TIMESTAMPDIFF(MINUTE, NOW(), i.expires_at) as minutes_until_expiry,
               s.status as session_status
        FROM invoices i
        INNER JOIN users u ON i.user_id = u.id
        LEFT JOIN active_game_sessions_v2 s ON i.id = s.invoice_id
        WHERE i.validation_code = ? OR i.validation_code = ?
    ');
    $stmt->execute([$validationCode, $validationCodeFormatted]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        json_response([
            'valid' => false,
            'error' => 'Code invalide'
        ], 404);
    }
    
    // Vérifier le statut
    $canActivate = $invoice['status'] === 'pending' && 
                   strtotime($invoice['expires_at']) > time() &&
                   $invoice['is_suspicious'] == 0;
    
    json_response([
        'valid' => true,
        'can_activate' => $canActivate,
        'invoice' => [
            'invoice_number' => $invoice['invoice_number'],
            'status' => $invoice['status'],
            'user' => [
                'username' => $invoice['username'],
                'email' => $invoice['email']
            ],
            'game_name' => $invoice['game_name'],
            'package_name' => $invoice['package_name'],
            'duration_minutes' => $invoice['duration_minutes'],
            'amount' => (float)$invoice['amount'],
            'issued_at' => $invoice['issued_at'],
            'expires_at' => $invoice['expires_at'],
            'minutes_until_expiry' => $invoice['minutes_until_expiry'],
            'is_suspicious' => (bool)$invoice['is_suspicious']
        ]
    ]);
}

json_response(['error' => 'Method not allowed'], 405);
