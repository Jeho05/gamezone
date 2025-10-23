<?php
/**
 * Database Helper Functions
 * Provides optimized database operations and query builders
 */

require_once __DIR__ . '/../config.php';

/**
 * Execute a query with caching support
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @param string|null $cacheKey Optional cache key
 * @param int $cacheTtl Cache TTL in seconds
 * @return array Query results
 */
function query_cached(string $sql, array $params = [], ?string $cacheKey = null, int $cacheTtl = 300): array {
    if ($cacheKey) {
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    $pdo = get_db();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    if ($cacheKey) {
        Cache::set($cacheKey, $results, $cacheTtl);
    }
    
    return $results;
}

/**
 * Get single row with caching
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @param string|null $cacheKey Optional cache key
 * @param int $cacheTtl Cache TTL in seconds
 * @return array|null Single row or null
 */
function query_row_cached(string $sql, array $params = [], ?string $cacheKey = null, int $cacheTtl = 300): ?array {
    if ($cacheKey) {
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    $pdo = get_db();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    if ($cacheKey && $result) {
        Cache::set($cacheKey, $result, $cacheTtl);
    }
    
    return $result ?: null;
}

/**
 * Invalidate related caches
 * @param array $patterns Cache key patterns to invalidate
 */
function invalidate_caches(array $patterns): void {
    foreach ($patterns as $pattern) {
        Cache::delete($pattern);
    }
}

/**
 * Paginate query results
 * @param string $sql Base SQL query (without LIMIT)
 * @param array $params Query parameters
 * @param int $page Current page (1-indexed)
 * @param int $perPage Items per page
 * @return array Paginated results with metadata
 */
function paginate_query(string $sql, array $params, int $page = 1, int $perPage = 20): array {
    $pdo = get_db();
    
    // Get total count
    $countSql = preg_replace('/^SELECT .+ FROM/i', 'SELECT COUNT(*) as total FROM', $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    
    // Calculate pagination
    $page = max(1, $page);
    $totalPages = (int)ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    
    // Get paginated results
    $stmt = $pdo->prepare($sql . " LIMIT ? OFFSET ?");
    $allParams = array_merge($params, [$perPage, $offset]);
    $stmt->execute($allParams);
    $items = $stmt->fetchAll();
    
    return [
        'items' => $items,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1,
        ]
    ];
}

/**
 * Batch insert multiple rows efficiently
 * @param string $table Table name
 * @param array $rows Array of rows to insert
 * @param array $columns Column names
 * @return int Number of affected rows
 */
function batch_insert(string $table, array $rows, array $columns): int {
    if (empty($rows)) {
        return 0;
    }
    
    $pdo = get_db();
    $columnList = implode(', ', $columns);
    $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholders));
    
    $sql = "INSERT INTO {$table} ({$columnList}) VALUES {$allPlaceholders}";
    
    // Flatten values
    $values = [];
    foreach ($rows as $row) {
        foreach ($columns as $col) {
            $values[] = $row[$col] ?? null;
        }
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    
    return $stmt->rowCount();
}

/**
 * Update multiple rows with same values
 * @param string $table Table name
 * @param array $updates Column => value pairs to update
 * @param string $whereClause WHERE clause
 * @param array $whereParams WHERE parameters
 * @return int Number of affected rows
 */
function batch_update(string $table, array $updates, string $whereClause, array $whereParams): int {
    $pdo = get_db();
    
    $setParts = [];
    $setValues = [];
    foreach ($updates as $col => $value) {
        $setParts[] = "{$col} = ?";
        $setValues[] = $value;
    }
    
    $setClause = implode(', ', $setParts);
    $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
    
    $allParams = array_merge($setValues, $whereParams);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($allParams);
    
    return $stmt->rowCount();
}

/**
 * Check if database connection is healthy
 * @return bool True if database is accessible
 */
function check_db_health(): bool {
    try {
        $pdo = get_db();
        $stmt = $pdo->query('SELECT 1');
        return $stmt->fetchColumn() === 1;
    } catch (Exception $e) {
        Logger::error('Database health check failed', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Get database statistics
 * @return array Database statistics
 */
function get_db_stats(): array {
    try {
        $pdo = get_db();
        
        $stats = [];
        
        // Get table sizes
        $stmt = $pdo->query("
            SELECT 
                table_name,
                table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
        ");
        
        $stats['tables'] = $stmt->fetchAll();
        
        return $stats;
    } catch (Exception $e) {
        Logger::error('Failed to get database stats', ['error' => $e->getMessage()]);
        return ['error' => 'Unable to fetch database statistics'];
    }
}
