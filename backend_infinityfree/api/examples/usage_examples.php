<?php
/**
 * USAGE EXAMPLES
 * 
 * This file demonstrates how to use the new helpers and middleware
 * DO NOT execute this file directly - it's for reference only
 */

// ============================================================================
// 1. USING CACHE
// ============================================================================

require_once __DIR__ . '/../helpers/database.php';

// Simple cache get/set
Cache::set('user_settings_123', $settings, 3600); // 1 hour
$settings = Cache::get('user_settings_123');

// Pattern "remember" - get from cache or execute callback
$popularGames = Cache::remember('games_popular', function() {
    $pdo = get_db();
    $stmt = $pdo->query('SELECT * FROM games ORDER BY play_count DESC LIMIT 10');
    return $stmt->fetchAll();
}, 600); // 10 minutes

// Invalidate cache after updates
Cache::delete('games_popular');

// ============================================================================
// 2. USING DATABASE HELPERS
// ============================================================================

// Query with automatic caching
$activeUsers = query_cached(
    'SELECT * FROM users WHERE status = ? ORDER BY points DESC',
    ['active'],
    'users_active_top',
    300  // 5 minutes TTL
);

// Query single row with cache
$game = query_row_cached(
    'SELECT * FROM games WHERE slug = ?',
    ['fifa-2024'],
    'game_fifa_2024',
    600
);

// Paginate results
$result = paginate_query(
    'SELECT * FROM points_transactions ORDER BY created_at DESC',
    [],
    $page,
    20  // per page
);
// Returns: ['items' => [...], 'pagination' => ['current_page' => 1, 'total' => 150, ...]]

// Batch insert
$transactions = [
    ['user_id' => 1, 'change_amount' => 50, 'reason' => 'Daily bonus', 'created_at' => now()],
    ['user_id' => 2, 'change_amount' => 100, 'reason' => 'Tournament win', 'created_at' => now()],
    ['user_id' => 3, 'change_amount' => 25, 'reason' => 'Friend referral', 'created_at' => now()],
];
$count = batch_insert('points_transactions', $transactions, ['user_id', 'change_amount', 'reason', 'created_at']);

// Batch update
$affectedRows = batch_update(
    'users',
    ['status' => 'inactive'],
    'last_active < ?',
    [date('Y-m-d H:i:s', strtotime('-90 days'))]
);

// ============================================================================
// 3. USING RESPONSE HELPERS
// ============================================================================

require_once __DIR__ . '/../helpers/response.php';

// Success response
success_response(['users' => $users], 'Users retrieved successfully');

// Error responses
error_response('Invalid input', 400);
not_found_response('User');
unauthorized_response();
forbidden_response('Admin access required');

// Validation error
validation_error_response([
    'email' => 'Email is required',
    'password' => 'Password must be at least 6 characters'
]);

// Created response (201)
created_response($newUser, 'User created successfully');

// ============================================================================
// 4. USING SECURITY HELPERS
// ============================================================================

require_once __DIR__ . '/../middleware/security.php';

// Rate limiting
if (!check_rate_limit('api_call_' . $userId, 100, 3600)) {
    error_response('Rate limit exceeded', 429);
}

// Input sanitization
$username = sanitize_input($_POST['username']);

// Validate multiple inputs
$validation = validate_inputs($_POST, [
    'email' => ['type' => 'email', 'required' => true],
    'username' => ['type' => 'string', 'required' => true, 'min' => 3, 'max' => 50],
    'age' => ['type' => 'int', 'required' => false, 'default' => 18],
]);

if (!$validation['success']) {
    validation_error_response($validation['errors']);
}

$sanitizedData = $validation['data'];

// ============================================================================
// 5. USING LOGGER
// ============================================================================

require_once __DIR__ . '/../middleware/logger.php';

// Log info
Logger::info('User logged in', [
    'user_id' => $userId,
    'ip' => $_SERVER['REMOTE_ADDR']
]);

// Log error
Logger::error('Payment failed', [
    'user_id' => $userId,
    'amount' => $amount,
    'error' => $errorMessage
]);

// Log warning
Logger::warning('Low stock alert', [
    'item_id' => $itemId,
    'quantity' => $quantity
]);

// Log debug (only in development)
Logger::debug('Cache hit', ['key' => $cacheKey]);

// ============================================================================
// 6. COMPLETE ENDPOINT EXAMPLE
// ============================================================================

/*
 * Example: Optimized Shop Items Endpoint
 */

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../helpers/database.php';
require_once __DIR__ . '/../helpers/response.php';

require_method(['GET']);

// Validate inputs
$category = $_GET['category'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));

// Rate limiting
if (!check_rate_limit('shop_view', 60, 60)) {
    error_response('Too many requests', 429);
}

// Cache key
$cacheKey = "shop_items_{$category}_page_{$page}";

try {
    // Get from cache or database
    $items = Cache::remember($cacheKey, function() use ($category, $page) {
        $sql = 'SELECT * FROM shop_items WHERE is_active = 1';
        $params = [];
        
        if ($category !== 'all') {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }
        
        $sql .= ' ORDER BY display_order ASC, created_at DESC';
        
        // Paginate
        return paginate_query($sql, $params, $page, 20);
    }, 300);
    
    // Log access
    Logger::info('Shop viewed', [
        'category' => $category,
        'page' => $page,
        'from_cache' => true
    ]);
    
    // Success response
    success_response($items);
    
} catch (Exception $e) {
    Logger::error('Shop endpoint failed', [
        'error' => $e->getMessage(),
        'category' => $category
    ]);
    
    error_response('Failed to load shop items', 500);
}

// ============================================================================
// 7. INVALIDATING CACHE AFTER UPDATES
// ============================================================================

/*
 * Example: When updating shop items, invalidate related caches
 */

// Update item
$stmt = $pdo->prepare('UPDATE shop_items SET price = ? WHERE id = ?');
$stmt->execute([$newPrice, $itemId]);

// Invalidate related caches
Cache::delete('shop_items_all_page_1');
Cache::delete('shop_items_all_page_2');
Cache::delete("shop_items_{$category}_page_1");
Cache::delete("shop_item_{$itemId}");

// Or clear all shop caches
// Note: Current implementation doesn't support pattern matching
// You'd need to track cache keys or implement a tagging system

Logger::info('Shop cache invalidated', ['item_id' => $itemId]);

// ============================================================================
// 8. HEALTH CHECK USAGE
// ============================================================================

/*
 * Check system health before critical operations
 */

require_once __DIR__ . '/../helpers/database.php';

if (!check_db_health()) {
    Logger::error('Database health check failed');
    error_response('Service temporarily unavailable', 503);
}

// Get database stats for admin panel
$stats = get_db_stats();
success_response($stats);
