<?php
/**
 * API Admin: Remboursement de Transaction
 * 
 * Permet aux admins de rembourser une transaction en cas de problème
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

// Vérifier que c'est un admin
$user = require_auth();
if (!is_admin($user)) {
    json_response(['error' => 'Accès refusé - Admin uniquement'], 403);
}

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// GET: Lister les transactions pouvant être remboursées
if ($method === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    $status = $_GET['status'] ?? 'completed';
    
    $sql = '
        SELECT 
            pt.*,
            u.username, u.email,
            p.game_name, p.session_status
        FROM purchase_transactions pt
        INNER JOIN users u ON pt.user_id = u.id
        LEFT JOIN purchases p ON pt.purchase_id = p.id
        WHERE 1=1
    ';
    
    $params = [];
    
    if ($userId) {
        $sql .= ' AND pt.user_id = ?';
        $params[] = $userId;
    }
    
    if ($status) {
        $sql .= ' AND pt.status = ?';
        $params[] = $status;
    }
    
    $sql .= ' ORDER BY pt.created_at DESC LIMIT 100';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    json_response([
        'success' => true,
        'transactions' => $transactions
    ]);
}

// POST: Effectuer un remboursement
if ($method === 'POST') {
    $data = get_json_input();
    
    $transactionId = $data['transaction_id'] ?? null;
    $reason = $data['reason'] ?? 'Remboursement par admin';
    
    if (!$transactionId) {
        json_response(['error' => 'transaction_id requis'], 400);
    }
    
    try {
        // Appeler la procédure stockée
        $stmt = $pdo->prepare('CALL refund_transaction(?, ?, ?, @result)');
        $stmt->execute([$transactionId, $reason, $user['id']]);
        
        // Récupérer le résultat
        $result = $pdo->query('SELECT @result as result')->fetch();
        
        if ($result['result'] === 'success') {
            // Récupérer les détails de la transaction remboursée
            $stmt = $pdo->prepare('
                SELECT pt.*, u.username, u.email, u.points as new_balance
                FROM purchase_transactions pt
                INNER JOIN users u ON pt.user_id = u.id
                WHERE pt.id = ?
            ');
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();
            
            log_info('Remboursement effectué', [
                'transaction_id' => $transactionId,
                'user_id' => $transaction['user_id'],
                'points_refunded' => $transaction['points_amount'],
                'admin_id' => $user['id'],
                'reason' => $reason
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Remboursement effectué avec succès',
                'transaction' => $transaction
            ]);
        } else {
            // Mapper les codes d'erreur
            $errors = [
                'transaction_not_found' => 'Transaction introuvable',
                'already_refunded' => 'Transaction déjà remboursée',
                'cannot_refund_uncompleted' => 'Impossible de rembourser une transaction non complétée'
            ];
            
            $message = $errors[$result['result']] ?? 'Erreur inconnue';
            
            json_response([
                'error' => $message,
                'code' => $result['result']
            ], 400);
        }
        
    } catch (Exception $e) {
        log_error('Erreur remboursement', [
            'transaction_id' => $transactionId,
            'error' => $e->getMessage()
        ]);
        
        json_response([
            'error' => 'Erreur lors du remboursement',
            'message' => $e->getMessage()
        ], 500);
    }
}
