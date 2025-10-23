<?php
// api/shop/games.php
// API publique pour voir les jeux et packages disponibles

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// Détection des colonnes (compatibilité si migration non appliquée)
$hasReservable = false;
$hasReservationFee = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM games LIKE 'is_reservable'");
    $hasReservable = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM games LIKE 'reservation_fee'");
    $hasReservationFee = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

// Colonnes à sélectionner selon disponibilité
$colsSingle = 'g.id, g.name, g.slug, g.description, g.short_description, '
    . 'g.image_url, g.thumbnail_url, g.category, g.platform, '
    . 'g.min_players, g.max_players, g.age_rating, '
    . 'g.points_per_hour, g.base_price';
$colsSingle .= $hasReservable ? ', g.is_reservable' : ', 0 as is_reservable';
$colsSingle .= $hasReservationFee ? ', g.reservation_fee' : ', 0.00 as reservation_fee';
$colsSingle .= ', g.is_featured';

$colsList = 'g.id, g.name, g.slug, g.short_description, '
    . 'g.image_url, g.thumbnail_url, g.category, g.platform, '
    . 'g.min_players, g.max_players, g.age_rating, '
    . 'g.points_per_hour, g.base_price';
$colsList .= $hasReservable ? ', g.is_reservable' : ', 0 as is_reservable';
$colsList .= $hasReservationFee ? ', g.reservation_fee' : ', 0.00 as reservation_fee';
$colsList .= ', g.is_featured';

// ============================================================================
// GET: Récupérer les jeux disponibles
// ============================================================================
if ($method === 'GET') {
    $slug = $_GET['slug'] ?? null;
    $id = $_GET['id'] ?? null;
    
    if ($slug || $id) {
        // Récupérer un jeu spécifique avec ses packages
        $sql = '
            SELECT ' . $colsSingle . '
            FROM games g
            WHERE g.is_active = 1 AND (' . ($slug ? 'g.slug = ?' : 'g.id = ?') . ')
        ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slug ?? $id]);
        $game = $stmt->fetch();
        
        if (!$game) {
            json_response(['error' => 'Jeu non trouvé ou indisponible'], 404);
        }
        
        // Récupérer les packages actifs du jeu
        $stmt = $pdo->prepare('
            SELECT id, name, duration_minutes, price, original_price,
                   points_earned, bonus_multiplier, is_promotional, promotional_label,
                   available_from, available_until, max_purchases_per_user
            FROM game_packages
            WHERE game_id = ? AND is_active = 1
            AND (available_from IS NULL OR available_from <= NOW())
            AND (available_until IS NULL OR available_until >= NOW())
            ORDER BY display_order ASC, duration_minutes ASC
        ');
        $stmt->execute([$game['id']]);
        $packages = $stmt->fetchAll();
        
        // Vérifier les limites d'achat par utilisateur si connecté
        $currentUser = current_user();
        if ($currentUser) {
            foreach ($packages as &$pkg) {
                if ($pkg['max_purchases_per_user']) {
                    $stmt = $pdo->prepare('
                        SELECT COUNT(*) as count 
                        FROM purchases 
                        WHERE user_id = ? AND package_id = ? AND payment_status = "completed"
                    ');
                    $stmt->execute([$currentUser['id'], $pkg['id']]);
                    $result = $stmt->fetch();
                    $pkg['user_purchases_count'] = $result['count'];
                    $pkg['can_purchase'] = $result['count'] < $pkg['max_purchases_per_user'];
                } else {
                    $pkg['can_purchase'] = true;
                }
            }
        } else {
            foreach ($packages as &$pkg) {
                $pkg['can_purchase'] = true;
            }
        }
        
        $game['packages'] = $packages;
        
        json_response(['game' => $game]);
    } else {
        // Récupérer tous les jeux actifs
        $category = $_GET['category'] ?? '';
        $featured = $_GET['featured'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $sql = '
            SELECT ' . $colsList . ',
                   (SELECT COUNT(*) FROM game_packages WHERE game_id = g.id AND is_active = 1) as packages_count,
                   (SELECT MIN(price) FROM game_packages WHERE game_id = g.id AND is_active = 1) as min_price
            FROM games g
            WHERE g.is_active = 1
        ';
        $params = [];
        
        if ($category) {
            $sql .= ' AND g.category = ?';
            $params[] = $category;
        }
        
        if ($featured === '1' || $featured === 'true') {
            $sql .= ' AND g.is_featured = 1';
        }
        
        if ($search) {
            $sql .= ' AND (g.name LIKE ? OR g.description LIKE ?)';
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= ' ORDER BY g.is_featured DESC, g.display_order ASC, g.name ASC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $games = $stmt->fetchAll();
        
        json_response(['games' => $games]);
    }
}

json_response(['error' => 'Method not allowed'], 405);
