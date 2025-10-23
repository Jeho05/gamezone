<?php
// api/admin/payment_packages.php
// Gestion des achats de packages (points et jeux) par l'admin
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer tous les achats de packages
// ============================================================================
if ($method === 'GET') {
    $type = $_GET['type'] ?? 'all'; // points, games, all
    $status = $_GET['status'] ?? null;
    $userId = $_GET['user_id'] ?? null;
    
    // Récupérer les achats de packages de points
    if ($type === 'points' || $type === 'all') {
        $sql = '
            SELECT ppp.*, 
                   u.username, u.email,
                   pp.name as package_name
            FROM points_package_purchases ppp
            INNER JOIN users u ON ppp.user_id = u.id
            INNER JOIN points_packages pp ON ppp.package_id = pp.id
            WHERE 1=1
        ';
        $params = [];
        
        if ($status) {
            $sql .= ' AND ppp.payment_status = ?';
            $params[] = $status;
        }
        
        if ($userId) {
            $sql .= ' AND ppp.user_id = ?';
            $params[] = $userId;
        }
        
        $sql .= ' ORDER BY ppp.created_at DESC LIMIT 100';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pointsPurchases = $stmt->fetchAll();
    } else {
        $pointsPurchases = [];
    }
    
    // Récupérer les achats de packages de jeux
    if ($type === 'games' || $type === 'all') {
        $sql = '
            SELECT p.*, 
                   u.username, u.email,
                   g.name as game_name,
                   gp.name as package_name,
                   pm.name as payment_method_name
            FROM purchases p
            INNER JOIN users u ON p.user_id = u.id
            INNER JOIN games g ON p.game_id = g.id
            INNER JOIN game_packages gp ON p.package_id = gp.id
            INNER JOIN payment_methods pm ON p.payment_method_id = pm.id
            WHERE 1=1
        ';
        $params = [];
        
        if ($status) {
            $sql .= ' AND p.payment_status = ?';
            $params[] = $status;
        }
        
        if ($userId) {
            $sql .= ' AND p.user_id = ?';
            $params[] = $userId;
        }
        
        $sql .= ' ORDER BY p.created_at DESC LIMIT 100';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $gamePurchases = $stmt->fetchAll();
    } else {
        $gamePurchases = [];
    }
    
    json_response([
        'success' => true,
        'points_purchases' => $pointsPurchases,
        'game_purchases' => $gamePurchases,
        'type' => $type
    ]);
}

// ============================================================================
// POST: Approuver ou refuser un paiement
// ============================================================================
if ($method === 'POST') {
    $data = get_json_input();
    $action = $data['action'] ?? null; // approve, reject, refund
    $purchaseType = $data['purchase_type'] ?? null; // points, game
    $purchaseId = $data['purchase_id'] ?? null;
    
    if (!$action || !$purchaseType || !$purchaseId) {
        json_response(['error' => 'action, purchase_type et purchase_id requis'], 400);
    }
    
    $pdo->beginTransaction();
    
    try {
        $ts = now();
        
        if ($purchaseType === 'points') {
            // Gérer un achat de package de points
            $stmt = $pdo->prepare('SELECT * FROM points_package_purchases WHERE id = ?');
            $stmt->execute([$purchaseId]);
            $purchase = $stmt->fetch();
            
            if (!$purchase) {
                $pdo->rollBack();
                json_response(['error' => 'Achat non trouvé'], 404);
            }
            
            if ($action === 'approve') {
                // Approuver et créditer les points
                if ($purchase['payment_status'] === 'completed' && $purchase['points_credited']) {
                    $pdo->rollBack();
                    json_response(['error' => 'Achat déjà approuvé et points crédités'], 400);
                }
                
                // Créditer les points
                $stmt = $pdo->prepare('UPDATE users SET points = points + ?, updated_at = ? WHERE id = ?');
                $stmt->execute([$purchase['total_points'], $ts, $purchase['user_id']]);
                
                // Marquer comme complété
                $stmt = $pdo->prepare('
                    UPDATE points_package_purchases 
                    SET payment_status = "completed", points_credited = 1, credited_at = ?, updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $ts, $purchaseId]);
                
                // Enregistrer la transaction
                $stmt = $pdo->prepare('
                    INSERT INTO points_transactions (
                        user_id, type, change_amount, reason, created_at
                    ) VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $purchase['user_id'],
                    'game',
                    $purchase['total_points'],
                    "Achat approuvé par admin: Package de {$purchase['total_points']} points",
                    $ts
                ]);
                
                $message = 'Achat approuvé et points crédités';
                
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare('
                    UPDATE points_package_purchases 
                    SET payment_status = "failed", updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $purchaseId]);
                $message = 'Achat rejeté';
                
            } elseif ($action === 'refund') {
                // Rembourser en retirant les points si déjà crédités
                if ($purchase['points_credited']) {
                    $stmt = $pdo->prepare('UPDATE users SET points = points - ?, updated_at = ? WHERE id = ?');
                    $stmt->execute([$purchase['total_points'], $ts, $purchase['user_id']]);
                    
                    $stmt = $pdo->prepare('
                        INSERT INTO points_transactions (
                            user_id, type, change_amount, reason, created_at
                        ) VALUES (?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([
                        $purchase['user_id'],
                        'adjustment',
                        -$purchase['total_points'],
                        "Remboursement achat package",
                        $ts
                    ]);
                }
                
                $stmt = $pdo->prepare('
                    UPDATE points_package_purchases 
                    SET payment_status = "refunded", points_credited = 0, updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $purchaseId]);
                $message = 'Achat remboursé';
            }
            
        } elseif ($purchaseType === 'game') {
            // Gérer un achat de package de jeu
            $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ?');
            $stmt->execute([$purchaseId]);
            $purchase = $stmt->fetch();
            
            if (!$purchase) {
                $pdo->rollBack();
                json_response(['error' => 'Achat non trouvé'], 404);
            }
            
            if ($action === 'approve') {
                $stmt = $pdo->prepare('
                    UPDATE purchases 
                    SET payment_status = "completed", updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $purchaseId]);
                $message = 'Achat de jeu approuvé - L\'utilisateur peut maintenant démarrer sa session';
                
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare('
                    UPDATE purchases 
                    SET payment_status = "failed", updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $purchaseId]);
                $message = 'Achat de jeu rejeté';
                
            } elseif ($action === 'refund') {
                // Vérifier qu'il n'y a pas de session active
                $stmt = $pdo->prepare('SELECT id FROM game_sessions WHERE purchase_id = ? AND status IN ("active", "paused")');
                $stmt->execute([$purchaseId]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    json_response(['error' => 'Impossible de rembourser: une session est active'], 400);
                }
                
                $stmt = $pdo->prepare('
                    UPDATE purchases 
                    SET payment_status = "refunded", session_status = "cancelled", updated_at = ?
                    WHERE id = ?
                ');
                $stmt->execute([$ts, $purchaseId]);
                $message = 'Achat de jeu remboursé';
            }
        }
        
        $pdo->commit();
        
        json_response([
            'success' => true,
            'message' => $message,
            'action' => $action,
            'purchase_type' => $purchaseType
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['error' => 'Erreur lors du traitement', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Méthode non autorisée'], 405);
