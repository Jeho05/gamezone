<?php
// api/admin/payment_methods.php
// API Admin pour gérer les méthodes de paiement

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est admin
$user = require_auth('admin');
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================================
// GET: Récupérer toutes les méthodes ou une méthode spécifique
// ============================================================================
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Récupérer une méthode spécifique
        $stmt = $pdo->prepare('
            SELECT pm.*,
                   (SELECT COUNT(*) FROM purchases WHERE payment_method_id = pm.id) as total_transactions,
                   (SELECT SUM(price) FROM purchases WHERE payment_method_id = pm.id AND payment_status = "completed") as total_revenue
            FROM payment_methods pm
            WHERE pm.id = ?
        ');
        $stmt->execute([$id]);
        $method_data = $stmt->fetch();
        
        if (!$method_data) {
            json_response(['error' => 'Méthode de paiement non trouvée'], 404);
        }
        
        // Masquer les clés secrètes pour la sécurité
        $method_data['api_key_secret'] = $method_data['api_key_secret'] ? '********' : null;
        $method_data['webhook_secret'] = $method_data['webhook_secret'] ? '********' : null;
        
        json_response(['payment_method' => $method_data]);
    } else {
        // Récupérer toutes les méthodes
        $stmt = $pdo->query('
            SELECT pm.*,
                   (SELECT COUNT(*) FROM purchases WHERE payment_method_id = pm.id) as total_transactions,
                   (SELECT SUM(price) FROM purchases WHERE payment_method_id = pm.id AND payment_status = "completed") as total_revenue
            FROM payment_methods pm
            ORDER BY pm.display_order ASC, pm.name ASC
        ');
        $methods = $stmt->fetchAll();
        
        // Masquer les clés secrètes
        foreach ($methods as &$m) {
            $m['api_key_secret'] = $m['api_key_secret'] ? '********' : null;
            $m['webhook_secret'] = $m['webhook_secret'] ? '********' : null;
        }
        
        json_response(['payment_methods' => $methods]);
    }
}

// ============================================================================
// POST: Créer une nouvelle méthode de paiement
// ============================================================================
if ($method === 'POST') {
    $data = get_json_input();
    
    // Validation
    $required = ['name', 'slug'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            json_response(['error' => "Le champ '$field' est requis"], 400);
        }
    }
    
    // Vérifier que le slug est unique
    $stmt = $pdo->prepare('SELECT id FROM payment_methods WHERE slug = ?');
    $stmt->execute([$data['slug']]);
    if ($stmt->fetch()) {
        json_response(['error' => 'Ce slug existe déjà'], 400);
    }
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO payment_methods (
                name, slug, provider, api_key_public, api_key_secret,
                api_endpoint, webhook_secret, requires_online_payment, auto_confirm,
                fee_percentage, fee_fixed, is_active, display_order, instructions,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $ts = now();
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['provider'] ?? null,
            $data['api_key_public'] ?? null,
            $data['api_key_secret'] ?? null,
            $data['api_endpoint'] ?? null,
            $data['webhook_secret'] ?? null,
            $data['requires_online_payment'] ?? 1,
            $data['auto_confirm'] ?? 0,
            $data['fee_percentage'] ?? 0.00,
            $data['fee_fixed'] ?? 0.00,
            $data['is_active'] ?? 1,
            $data['display_order'] ?? 0,
            $data['instructions'] ?? null,
            $ts,
            $ts
        ]);
        
        $methodId = $pdo->lastInsertId();
        
        json_response([
            'success' => true,
            'message' => 'Méthode de paiement créée avec succès',
            'method_id' => $methodId
        ], 201);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la création', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// PUT/PATCH: Mettre à jour une méthode de paiement
// ============================================================================
if ($method === 'PUT' || $method === 'PATCH') {
    $data = get_json_input();
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID de la méthode requis'], 400);
    }
    
    // Vérifier que la méthode existe
    $stmt = $pdo->prepare('SELECT id FROM payment_methods WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Méthode de paiement non trouvée'], 404);
    }
    
    // Construire la requête de mise à jour dynamiquement
    $updateFields = [];
    $params = [];
    
    $allowedFields = [
        'name', 'slug', 'description', 'provider', 'api_key_public', 'api_endpoint',
        'requires_online_payment', 'auto_confirm', 'fee_percentage', 'fee_fixed',
        'is_active', 'display_order', 'instructions'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    // Gérer les champs secrets séparément (ne mettre à jour que s'ils sont fournis et différents de '********')
    if (isset($data['api_key_secret']) && $data['api_key_secret'] !== '********' && $data['api_key_secret'] !== '') {
        $updateFields[] = 'api_key_secret = ?';
        $params[] = $data['api_key_secret'];
    }
    
    if (isset($data['webhook_secret']) && $data['webhook_secret'] !== '********' && $data['webhook_secret'] !== '') {
        $updateFields[] = 'webhook_secret = ?';
        $params[] = $data['webhook_secret'];
    }
    
    if (empty($updateFields)) {
        json_response(['error' => 'Aucune donnée à mettre à jour'], 400);
    }
    
    // Ajouter updated_at
    $updateFields[] = 'updated_at = ?';
    $params[] = now();
    $params[] = $id;
    
    try {
        $sql = 'UPDATE payment_methods SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json_response([
            'success' => true,
            'message' => 'Méthode de paiement mise à jour avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// ============================================================================
// DELETE: Supprimer une méthode de paiement
// ============================================================================
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID de la méthode requis'], 400);
    }
    
    // Vérifier s'il y a des achats associés
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM purchases WHERE payment_method_id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        json_response([
            'error' => 'Impossible de supprimer cette méthode car elle a des transactions associées',
            'suggestion' => 'Désactivez la méthode plutôt que de la supprimer'
        ], 400);
    }
    
    try {
        $stmt = $pdo->prepare('DELETE FROM payment_methods WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response([
            'success' => true,
            'message' => 'Méthode de paiement supprimée avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405);
