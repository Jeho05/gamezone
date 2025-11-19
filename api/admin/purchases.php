<?php
// api/admin/purchases.php
// API Admin pour gérer les achats et sessions de jeu

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est admin
$user = require_auth('admin');
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer tous les achats ou un achat spécifique
// ============================================================================
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer un achat spécifique avec ses détails
        $stmt = $pdo->prepare('
            SELECT p.*,
                   u.username, u.email,
                   g.name as game_name, g.slug as game_slug,
                   pkg.name as package_name,
                   pm.name as payment_method_name,
                   confirmer.username as confirmed_by_username,
                   (SELECT status FROM game_sessions WHERE purchase_id = p.id LIMIT 1) as session_status_actual
            FROM purchases p
            INNER JOIN users u ON p.user_id = u.id
            INNER JOIN games g ON p.game_id = g.id
            LEFT JOIN game_packages pkg ON p.package_id = pkg.id
            LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
            LEFT JOIN users confirmer ON p.confirmed_by = confirmer.id
            WHERE p.id = ?
        ');
        $stmt->execute([$id]);
        $purchase = $stmt->fetch();
        
        if (!$purchase) {
            json_response(['error' => 'Achat non trouvé'], 404);
        }
        
        // Récupérer la session de jeu associée
        $stmt = $pdo->prepare('
            SELECT * FROM game_sessions WHERE purchase_id = ?
        ');
        $stmt->execute([$id]);
        $purchase['session'] = $stmt->fetch();
        
        // Récupérer les transactions de paiement
        $stmt = $pdo->prepare('
            SELECT * FROM payment_transactions WHERE purchase_id = ? ORDER BY created_at DESC
        ');
        $stmt->execute([$id]);
        $purchase['transactions'] = $stmt->fetchAll();
        
        json_response(['purchase' => $purchase]);
    } else {
        // Récupérer tous les achats avec filtres
        $payment_status = $_GET['payment_status'] ?? '';
        $session_status = $_GET['session_status'] ?? '';
        $user_id = $_GET['user_id'] ?? '';
        $game_id = $_GET['game_id'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = '
            SELECT p.*,
                   u.username, u.email,
                   g.name as game_name,
                   pm.name as payment_method_name
            FROM purchases p
            INNER JOIN users u ON p.user_id = u.id
            INNER JOIN games g ON p.game_id = g.id
            LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
            WHERE 1=1
        ';
        $params = [];
        
        if ($payment_status) {
            $sql .= ' AND p.payment_status = ?';
            $params[] = $payment_status;
        }
        
        if ($session_status) {
            $sql .= ' AND p.session_status = ?';
            $params[] = $session_status;
        }
        
        if ($user_id) {
            $sql .= ' AND p.user_id = ?';
            $params[] = $user_id;
        }
        
        if ($game_id) {
            $sql .= ' AND p.game_id = ?';
            $params[] = $game_id;
        }
        
        if ($date_from) {
            $sql .= ' AND DATE(p.created_at) >= ?';
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $sql .= ' AND DATE(p.created_at) <= ?';
            $params[] = $date_to;
        }
        
        // Compter le total
        $countSql = str_replace('SELECT p.*,', 'SELECT COUNT(*) as total', $sql);
        $countSql = preg_replace('/FROM purchases.*$/s', 'FROM purchases p INNER JOIN users u ON p.user_id = u.id INNER JOIN games g ON p.game_id = g.id LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id WHERE 1=1' . substr($sql, strpos($sql, 'WHERE 1=1') + 9), $countSql);
        $stmt = $pdo->prepare(preg_replace('/SELECT.*?FROM/s', 'SELECT COUNT(*) as total FROM', $sql));
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];
        
        $sql .= ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $purchases = $stmt->fetchAll();
        
        json_response([
            'purchases' => $purchases,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);
    }
}

// ============================================================================
// PATCH: Confirmer/Modifier un achat
// ============================================================================
if ($method === 'PATCH') {
    $data = get_json_input();
    $id = $data['id'] ?? null;
    $action = $data['action'] ?? '';
    
    if (!$id) {
        json_response(['error' => 'ID de l\'achat requis'], 400);
    }
    
    // Récupérer l'achat
    $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ?');
    $stmt->execute([$id]);
    $purchase = $stmt->fetch();
    
    if (!$purchase) {
        json_response(['error' => 'Achat non trouvé'], 404);
    }
    
    $pdo->beginTransaction();
    
    try {
        switch ($action) {
            case 'confirm_payment':
                // Confirmer le paiement
                if ($purchase['payment_status'] !== 'pending') {
                    $pdo->rollBack();
                    json_response(['error' => 'Cet achat ne peut pas être confirmé (statut: ' . $purchase['payment_status'] . ')'], 400);
                }
                
                $ts = now();
                
                // Mettre à jour le statut de paiement
                $stmt = $pdo->prepare('
                    UPDATE purchases 
                    SET payment_status = "completed", 
                        session_status = "pending",
                        confirmed_by = ?,
                        confirmed_at = ?,
                        updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$user['id'], $ts, $ts, $id]);
                
                // Vérifier que la mise à jour a réussi
                if ($stmt->rowCount() === 0) {
                    $pdo->rollBack();
                    json_response(['error' => 'Impossible de mettre à jour l\'achat'], 500);
                }
                
                // Le trigger 'after_purchase_completed' créera automatiquement:
                // 1. La facture avec code QR unique
                // 2. L'audit log
                // La session sera créée dans active_game_sessions_v2 lors du scan de la facture
                
                // Créditer les points immédiatement
                if ($purchase['points_earned'] > 0 && !$purchase['points_credited']) {
                    $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
                    $stmt->execute([$purchase['points_earned'], $ts, $purchase['user_id']]);
                    
                    try {
                        $stmt = $pdo->prepare('
                            INSERT INTO points_transactions (user_id, change_amount, reason, type, admin_id, created_at)
                            VALUES (?, ?, ?, "purchase", ?, ?)
                        ');
                        $stmt->execute([
                            $purchase['user_id'],
                            $purchase['points_earned'],
                            "Achat confirmé: {$purchase['game_name']}",
                            $user['id'],
                            $ts
                        ]);
                    } catch (Exception $e) {
                        error_log('[admin/purchases] Failed to log purchase points transaction: ' . $e->getMessage());
                    }
                    
                    $stmt = $pdo->prepare('UPDATE purchases SET points_credited = 1 WHERE id = ?');
                    $stmt->execute([$id]);
                }
                
                $pdo->commit();
                
                // Récupérer la facture créée par le trigger
                $stmt = $pdo->prepare('SELECT invoice_number, validation_code FROM invoices WHERE purchase_id = ?');
                $stmt->execute([$id]);
                $invoice = $stmt->fetch();
                
                json_response([
                    'success' => true, 
                    'message' => 'Paiement confirmé avec succès! La facture a été générée.',
                    'invoice' => $invoice
                ]);
                break;
                
            case 'cancel_payment':
                // Annuler le paiement
                $stmt = $pdo->prepare('
                    UPDATE purchases 
                    SET payment_status = "cancelled", 
                        session_status = "cancelled",
                        updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([now(), $id]);
                
                $pdo->commit();
                json_response(['success' => true, 'message' => 'Paiement annulé']);
                break;
                
            case 'refund':
                // Rembourser
                if ($purchase['payment_status'] !== 'completed') {
                    $pdo->rollBack();
                    json_response(['error' => 'Seuls les paiements complétés peuvent être remboursés'], 400);
                }
                
                $ts = now();
                
                // Mettre à jour le statut de l'achat
                $stmt = $pdo->prepare('
                    UPDATE purchases 
                    SET payment_status = "refunded", 
                        session_status = "cancelled",
                        updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $id]);
                
                // Retirer les points crédités pour cet achat
                if ($purchase['points_credited'] && $purchase['points_earned'] > 0) {
                    $pointsToRemove = (int)$purchase['points_earned'];
                    
                    $stmt = $pdo->prepare('UPDATE users SET points = GREATEST(0, points - ?), updated_at = ? WHERE id = ?');
                    $stmt->execute([$pointsToRemove, $ts, $purchase['user_id']]);
                    
                    try {
                        $stmt = $pdo->prepare('
                            INSERT INTO points_transactions (user_id, change_amount, reason, type, admin_id, created_at)
                            VALUES (?, ?, ?, "refund", ?, ?)
                        ');
                        $stmt->execute([
                            $purchase['user_id'],
                            -$pointsToRemove,
                            "Remboursement: {$purchase['game_name']}",
                            $user['id'],
                            $ts
                        ]);
                    } catch (Exception $e) {
                        error_log('[admin/purchases] Failed to log refund points transaction: ' . $e->getMessage());
                    }
                    
                    $stmt = $pdo->prepare('UPDATE purchases SET points_credited = 0 WHERE id = ?');
                    $stmt->execute([$id]);
                }
                
                // Annuler la session si elle existe dans active_game_sessions_v2
                $stmt = $pdo->prepare('
                    UPDATE active_game_sessions_v2 
                    SET status = "terminated", 
                        completed_at = ?,
                        updated_at = ? 
                    WHERE purchase_id = ? AND status NOT IN ("completed", "terminated", "expired")
                ');
                $stmt->execute([$ts, $ts, $id]);
                
                // Marquer la facture comme annulée
                $stmt = $pdo->prepare('
                    UPDATE invoices 
                    SET status = "cancelled", 
                        updated_at = ? 
                    WHERE purchase_id = ? AND status NOT IN ("used", "cancelled")
                ');
                $stmt->execute([$ts, $id]);
                
                // Enregistrer la transaction de remboursement
                $stmt = $pdo->prepare('
                    INSERT INTO payment_transactions (purchase_id, transaction_type, amount, currency, notes, created_at)
                    VALUES (?, "refund", ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $id,
                    $purchase['price'],
                    $purchase['currency'],
                    $data['notes'] ?? 'Remboursement par admin',
                    $ts
                ]);
                
                $pdo->commit();
                json_response(['success' => true, 'message' => 'Achat remboursé']);
                break;
                
            case 'update_notes':
                // Mettre à jour les notes
                $stmt = $pdo->prepare('UPDATE purchases SET notes = ?, updated_at = ? WHERE id = ?');
                $stmt->execute([$data['notes'] ?? '', now(), $id]);
                
                $pdo->commit();
                json_response(['success' => true, 'message' => 'Notes mises à jour']);
                break;
                
            default:
                json_response(['error' => 'Action non reconnue'], 400);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['error' => 'Erreur lors du traitement', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405);
