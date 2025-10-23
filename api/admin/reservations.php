<?php
// api/admin/reservations.php
// Admin endpoint: list and inspect game reservations

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare('
            SELECT r.*, 
                   u.username, u.email,
                   g.name AS game_name, g.slug AS game_slug,
                   p.payment_status, p.price AS purchase_price, p.currency AS purchase_currency
            FROM game_reservations r
            INNER JOIN users u ON r.user_id = u.id
            INNER JOIN games g ON r.game_id = g.id
            LEFT JOIN purchases p ON r.purchase_id = p.id
            WHERE r.id = ?
        ');
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        if (!$res) {
            json_response(['error' => 'Réservation non trouvée'], 404);
        }
        json_response(['reservation' => $res]);
    }

    $status = $_GET['status'] ?? '';
    $game_id = $_GET['game_id'] ?? '';
    $user_id = $_GET['user_id'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 50), 200);
    $offset = (int)($_GET['offset'] ?? 0);

    $sql = '
        SELECT r.*, 
               u.username, u.email,
               g.name AS game_name, g.slug AS game_slug
        FROM game_reservations r
        INNER JOIN users u ON r.user_id = u.id
        INNER JOIN games g ON r.game_id = g.id
        WHERE 1=1
    ';
    $params = [];

    if ($status !== '') { $sql .= ' AND r.status = ?'; $params[] = $status; }
    if ($game_id !== '') { $sql .= ' AND r.game_id = ?'; $params[] = $game_id; }
    if ($user_id !== '') { $sql .= ' AND r.user_id = ?'; $params[] = $user_id; }
    if ($date_from !== '') { $sql .= ' AND DATE(r.scheduled_start) >= ?'; $params[] = $date_from; }
    if ($date_to !== '') { $sql .= ' AND DATE(r.scheduled_start) <= ?'; $params[] = $date_to; }

    // Count
    $countSql = 'SELECT COUNT(*) AS total FROM (' . $sql . ') t';
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)($stmt->fetch()['total'] ?? 0);

    $sql .= ' ORDER BY r.scheduled_start DESC LIMIT ? OFFSET ?';
    $params2 = array_merge($params, [$limit, $offset]);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params2);
    $rows = $stmt->fetchAll();

    json_response([
        'reservations' => $rows,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);
}

// PATCH: Confirmer ou modifier le statut d'une réservation
if ($method === 'PATCH') {
    $data = get_json_input();
    $id = $data['id'] ?? null;
    $action = $data['action'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID de réservation requis'], 400);
    }
    
    // Vérifier que la réservation existe
    $stmt = $pdo->prepare('SELECT * FROM game_reservations WHERE id = ?');
    $stmt->execute([$id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        json_response(['error' => 'Réservation non trouvée'], 404);
    }
    
    $pdo->beginTransaction();
    
    try {
        $ts = date('Y-m-d H:i:s');
        
        // Actions disponibles
        switch ($action) {
            case 'confirm':
                // Confirmer la réservation (passage de pending_payment à paid)
                if ($reservation['status'] === 'pending_payment') {
                    $stmt = $pdo->prepare('
                        UPDATE game_reservations 
                        SET status = "paid", updated_at = ?
                        WHERE id = ?
                    ');
                    $stmt->execute([$ts, $id]);
                    
                    // Mettre à jour le purchase associé
                    if ($reservation['purchase_id']) {
                        $stmt = $pdo->prepare('
                            UPDATE purchases 
                            SET payment_status = "completed", updated_at = ?
                            WHERE id = ?
                        ');
                        $stmt->execute([$ts, $reservation['purchase_id']]);
                    }
                    
                    $pdo->commit();
                    json_response([
                        'success' => true,
                        'message' => 'Réservation confirmée avec succès'
                    ]);
                } else {
                    $pdo->rollBack();
                    json_response([
                        'error' => 'La réservation doit être en statut pending_payment pour être confirmée',
                        'current_status' => $reservation['status']
                    ], 400);
                }
                break;
                
            case 'cancel':
                // Annuler la réservation
                $stmt = $pdo->prepare('
                    UPDATE game_reservations 
                    SET status = "cancelled", updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $id]);
                
                // Mettre à jour le purchase si nécessaire
                if ($reservation['purchase_id']) {
                    $stmt = $pdo->prepare('
                        UPDATE purchases 
                        SET session_status = "cancelled", updated_at = ?
                        WHERE id = ?
                    ');
                    $stmt->execute([$ts, $reservation['purchase_id']]);
                }
                
                $pdo->commit();
                json_response([
                    'success' => true,
                    'message' => 'Réservation annulée avec succès'
                ]);
                break;
                
            case 'mark_completed':
                // Marquer comme complétée
                $stmt = $pdo->prepare('
                    UPDATE game_reservations 
                    SET status = "completed", updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $id]);
                
                $pdo->commit();
                json_response([
                    'success' => true,
                    'message' => 'Réservation marquée comme complétée'
                ]);
                break;
                
            case 'mark_no_show':
                // Marquer comme no-show
                $stmt = $pdo->prepare('
                    UPDATE game_reservations 
                    SET status = "no_show", updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $id]);
                
                $pdo->commit();
                json_response([
                    'success' => true,
                    'message' => 'Réservation marquée comme no-show'
                ]);
                break;
                
            default:
                $pdo->rollBack();
                json_response(['error' => 'Action non reconnue. Actions disponibles: confirm, cancel, mark_completed, mark_no_show'], 400);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response([
            'error' => 'Erreur lors de la mise à jour de la réservation',
            'details' => $e->getMessage()
        ], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405);
