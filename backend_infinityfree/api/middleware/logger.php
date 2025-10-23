<?php
/**
 * Logging Middleware
 * Provides structured logging for API requests and errors
 */

class Logger {
    private static $logDir;
    private static $logFile;
    
    /**
     * Initialize logger
     */
    public static function init(): void {
        self::$logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0777, true);
        }
        self::$logFile = self::$logDir . '/api_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Log an info message
     */
    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log a warning message
     */
    public static function warning(string $message, array $context = []): void {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log an error message
     */
    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Log a debug message
     */
    public static function debug(string $message, array $context = []): void {
        // Only log debug in development
        if (self::isDevelopment()) {
            self::log('DEBUG', $message, $context);
        }
    }
    
    /**
     * Log API request
     */
    public static function logRequest(): void {
        $data = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
        ];
        
        if (isset($_SESSION['user'])) {
            $data['user_id'] = $_SESSION['user']['id'];
            $data['username'] = $_SESSION['user']['username'];
        }
        
        self::info('API Request', $data);
    }
    
    /**
     * Internal log method
     */
    private static function log(string $level, string $message, array $context = []): void {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $logLine = "[{$timestamp}] [{$level}] {$message}";
        if ($contextStr) {
            $logLine .= " | Context: {$contextStr}";
        }
        $logLine .= PHP_EOL;
        
        @file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check if in development mode
     */
    private static function isDevelopment(): bool {
        return ($_SERVER['SERVER_NAME'] ?? '') === 'localhost' || 
               strpos($_SERVER['SERVER_NAME'] ?? '', '127.0.0.1') !== false;
    }
    
    /**
     * Clean old log files (keep last 30 days)
     */
    public static function cleanOldLogs(): void {
        self::init();
        
        $files = glob(self::$logDir . '/api_*.log');
        $cutoff = strtotime('-30 days');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }
}

// Initialize logger
Logger::init();
