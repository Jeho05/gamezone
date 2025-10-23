<?php
// api/admin/payment_methods_simple.php
// Version simplifiée sans colonnes optionnelles

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth('admin');
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// GET: Récupérer les méthodes
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM payment_methods WHERE id = ?');
        $stmt->execute([$id]);
        $method_data = $stmt->fetch();
        
        if (!$method_data) {
            json_response(['error' => 'Méthode non trouvée'], 404);
        }
        
        json_response(['payment_method' => $method_data]);
    } else {
        $stmt = $pdo->query('SELECT * FROM payment_methods ORDER BY display_order ASC, name ASC');
        $methods = $stmt->fetchAll();
        json_response(['payment_methods' => $methods]);
    }
}

// POST: Créer une méthode (VERSION SIMPLIFIÉE)
if ($method === 'POST') {
    $data = get_json_input();
    
    // Validation minimale
    if (!isset($data['name']) || $data['name'] === '') {
        json_response(['error' => "Le champ 'name' est requis"], 400);
    }
    
    if (!isset($data['slug']) || $data['slug'] === '') {
        json_response(['error' => "Le champ 'slug' est requis"], 400);
    }
    
    // Vérifier que le slug est unique
    $stmt = $pdo->prepare('SELECT id FROM payment_methods WHERE slug = ?');
    $stmt->execute([$data['slug']]);
    if ($stmt->fetch()) {
        json_response(['error' => 'Ce slug existe déjà'], 400);
    }
    
    try {
        // Vérifier quelles colonnes existent
        $stmt = $pdo->query("SHOW COLUMNS FROM payment_methods");
        $existingColumns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingColumns[] = $row['Field'];
        }
        
        // Construire INSERT dynamiquement avec seulement les colonnes qui existent
        $columns = ['name', 'slug'];
        $values = [$data['name'], $data['slug']];
        $placeholders = ['?', '?'];
        
        // Ajouter les colonnes optionnelles si elles existent
        $optionalFields = [
            'provider' => $data['provider'] ?? null,
            'description' => $data['description'] ?? null,
            'fee_percentage' => $data['fee_percentage'] ?? 0,
            'fee_fixed' => $data['fee_fixed'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'display_order' => $data['display_order'] ?? 0,
            'requires_online_payment' => $data['requires_online_payment'] ?? 0,
        ];
        
        // Ajouter seulement si la colonne existe
        foreach ($optionalFields as $field => $value) {
            if (in_array($field, $existingColumns)) {
                $columns[] = $field;
                $values[] = $value;
                $placeholders[] = '?';
            }
        }
        
        // Ajouter timestamps si ils existent
        $ts = now();
        if (in_array('created_at', $existingColumns)) {
            $columns[] = 'created_at';
            $values[] = $ts;
            $placeholders[] = '?';
        }
        if (in_array('updated_at', $existingColumns)) {
            $columns[] = 'updated_at';
            $values[] = $ts;
            $placeholders[] = '?';
        }
        
        $sql = 'INSERT INTO payment_methods (' . implode(', ', $columns) . ') 
                VALUES (' . implode(', ', $placeholders) . ')';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        $methodId = $pdo->lastInsertId();
        
        json_response([
            'success' => true,
            'message' => 'Méthode de paiement créée avec succès',
            'method_id' => $methodId
        ], 201);
        
    } catch (PDOException $e) {
        json_response([
            'error' => 'Erreur lors de la création', 
            'details' => $e->getMessage(),
            'sql_state' => $e->getCode()
        ], 500);
    }
}

// PUT: Mettre à jour
if ($method === 'PUT' || $method === 'PATCH') {
    $data = get_json_input();
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis'], 400);
    }
    
    // Vérifier que la méthode existe
    $stmt = $pdo->prepare('SELECT id FROM payment_methods WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Méthode non trouvée'], 404);
    }
    
    try {
        // Vérifier colonnes existantes
        $stmt = $pdo->query("SHOW COLUMNS FROM payment_methods");
        $existingColumns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingColumns[] = $row['Field'];
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = [
            'name', 'slug', 'provider', 'description',
            'fee_percentage', 'fee_fixed', 'is_active', 'display_order',
            'requires_online_payment'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && in_array($field, $existingColumns)) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            json_response(['error' => 'Aucune donnée à mettre à jour'], 400);
        }
        
        if (in_array('updated_at', $existingColumns)) {
            $updateFields[] = 'updated_at = ?';
            $params[] = now();
        }
        
        $params[] = $id;
        
        $sql = 'UPDATE payment_methods SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json_response([
            'success' => true,
            'message' => 'Méthode mise à jour avec succès'
        ]);
        
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la mise à jour', 'details' => $e->getMessage()], 500);
    }
}

// DELETE: Supprimer
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_response(['error' => 'ID requis'], 400);
    }
    
    try {
        $stmt = $pdo->prepare('DELETE FROM payment_methods WHERE id = ?');
        $stmt->execute([$id]);
        
        json_response([
            'success' => true,
            'message' => 'Méthode supprimée avec succès'
        ]);
    } catch (PDOException $e) {
        json_response(['error' => 'Erreur lors de la suppression', 'details' => $e->getMessage()], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405);
