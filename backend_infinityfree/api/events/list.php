<?php
/**
 * API Events - Liste des événements
 * Retourne la liste des événements publics
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

require_method(['GET']);

$pdo = get_db();

try {
    $type = $_GET['type'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $where = "1=1";
    $params = [];
    
    if ($type) {
        $where .= " AND e.type = ?";
        $params[] = $type;
    }
    
    $stmt = $pdo->prepare("
        SELECT e.*
        FROM events e
        WHERE $where
        ORDER BY e.date DESC, e.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    
    $events = $stmt->fetchAll();
    
    // Compter le total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE $where");
    $stmt->execute(array_slice($params, 0, -2));
    $total = $stmt->fetchColumn();
    
    json_response([
        'success' => true,
        'events' => $events,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset
        ],
        'timestamp' => now()
    ]);
    
} catch (Exception $e) {
    json_response([
        'error' => 'Erreur lors du chargement des événements',
        'details' => $e->getMessage()
    ], 500);
}
