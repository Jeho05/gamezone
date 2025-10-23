<?php
/**
 * Simple File-based Cache System
 * Improves performance by caching frequently accessed data
 */

class Cache {
    private static $cacheDir;
    
    /**
     * Initialize cache directory
     */
    public static function init(): void {
        self::$cacheDir = sys_get_temp_dir() . '/gamezone_cache';
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0777, true);
        }
    }
    
    /**
     * Get cached value
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found/expired
     */
    public static function get(string $key) {
        self::init();
        
        $file = self::getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (!$data || !isset($data['expires_at']) || !isset($data['value'])) {
            return null;
        }
        
        // Check expiration
        if (time() > $data['expires_at']) {
            @unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Set cached value
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (default: 5 minutes)
     * @return bool Success status
     */
    public static function set(string $key, $value, int $ttl = 300): bool {
        self::init();
        
        $file = self::getCacheFile($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];
        
        return file_put_contents($file, json_encode($data)) !== false;
    }
    
    /**
     * Delete cached value
     * @param string $key Cache key
     * @return bool Success status
     */
    public static function delete(string $key): bool {
        self::init();
        
        $file = self::getCacheFile($key);
        
        if (file_exists($file)) {
            return @unlink($file);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     * @return bool Success status
     */
    public static function clear(): bool {
        self::init();
        
        $files = glob(self::$cacheDir . '/*.cache');
        $success = true;
        
        foreach ($files as $file) {
            if (!@unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Remember: Get from cache or execute callback and cache result
     * @param string $key Cache key
     * @param callable $callback Callback to execute if cache miss
     * @param int $ttl Time to live in seconds
     * @return mixed Cached or fresh value
     */
    public static function remember(string $key, callable $callback, int $ttl = 300) {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Get cache file path for a key
     */
    private static function getCacheFile(string $key): string {
        return self::$cacheDir . '/' . md5($key) . '.cache';
    }
    
    /**
     * Clean expired cache files
     */
    public static function cleanExpired(): void {
        self::init();
        
        $files = glob(self::$cacheDir . '/*.cache');
        $now = time();
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            
            if (!$data || !isset($data['expires_at']) || $now > $data['expires_at']) {
                @unlink($file);
            }
        }
    }
}

// Initialize cache
Cache::init();
