<?php
// api/invoices/my_invoices.php
// API pour que l'utilisateur consulte ses factures

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

// GET: Récupérer les factures de l'utilisateur
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer une facture spécifique
        $stmt = $pdo->prepare('
            SELECT i.*,
                   g.name as game_name, g.image_url,
                   s.status as session_status, s.remaining_minutes,
                   s.used_minutes as session_used_minutes,
                   s.started_at as session_started_at,
                   TIMESTAMPDIFF(MINUTE, NOW(), i.expires_at) as minutes_until_expiry
            FROM invoices i
            INNER JOIN purchases p ON i.purchase_id = p.id
            INNER JOIN games g ON p.game_id = g.id
            LEFT JOIN active_game_sessions_v2 s ON i.id = s.invoice_id
            WHERE i.id = ? AND i.user_id = ?
        ');
        $stmt->execute([$id, $user['id']]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            json_response(['error' => 'Facture non trouvée'], 404);
        }
        
        // Récupérer les événements de session si disponible
        if ($invoice['session_status']) {
            $stmt = $pdo->prepare('
                SELECT * FROM session_events 
                WHERE session_id = (SELECT id FROM active_game_sessions_v2 WHERE invoice_id = ?)
                ORDER BY created_at DESC
                LIMIT 20
            ');
            $stmt->execute([$id]);
            $invoice['session_events'] = $stmt->fetchAll();
        }
        
        json_response(['invoice' => $invoice]);
    } else {
        // Récupérer toutes les factures de l'utilisateur
        $status = $_GET['status'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 20), 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = '
            SELECT i.*,
                   g.name as game_name, g.image_url, g.thumbnail_url,
                   s.status as session_status, s.remaining_minutes,
                   TIMESTAMPDIFF(MINUTE, NOW(), i.expires_at) as minutes_until_expiry,
                   DATEDIFF(i.expires_at, NOW()) as days_until_expiry
            FROM invoices i
            INNER JOIN purchases p ON i.purchase_id = p.id
            INNER JOIN games g ON p.game_id = g.id
            LEFT JOIN active_game_sessions_v2 s ON i.id = s.invoice_id
            WHERE i.user_id = ?
        ';
        $params = [$user['id']];
        
        if ($status) {
            $sql .= ' AND i.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY i.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $invoices = $stmt->fetchAll();
        
        // Statistiques
        $stmt = $pdo->prepare('
            SELECT 
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = "used" THEN 1 ELSE 0 END) as used,
                SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired,
                SUM(amount) as total_spent
            FROM invoices
            WHERE user_id = ?
        ');
        $stmt->execute([$user['id']]);
        $stats = $stmt->fetch();
        
        json_response([
            'invoices' => $invoices,
            'stats' => $stats
        ]);
    }
}

json_response(['error' => 'Method not allowed'], 405);
