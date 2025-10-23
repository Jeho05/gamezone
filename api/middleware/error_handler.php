<?php
/**
 * Global Error Handler
 * Provides consistent error handling and logging
 */

require_once __DIR__ . '/logger.php';

class ErrorHandler {
    /**
     * Register error handlers
     */
    public static function register(): void {
        // Set custom error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set custom exception handler
        set_exception_handler([self::class, 'handleException']);
        
        // Register shutdown function to catch fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
        // Don't handle suppressed errors (@)
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorType = self::getErrorType($errno);
        
        Logger::error("PHP Error: {$errorType}", [
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
        ]);
        
        // Only show error in development
        if (self::isDevelopment()) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException(Throwable $exception): void {
        Logger::error('Uncaught Exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        // Send appropriate response
        header('Content-Type: application/json');
        http_response_code(500);
        
        if (self::isDevelopment()) {
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => 'Une erreur est survenue. Veuillez réessayer plus tard.',
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    /**
     * Handle shutdown (catch fatal errors)
     */
    public static function handleShutdown(): void {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            Logger::error('Fatal Error', [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
            
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(500);
                
                echo json_encode([
                    'error' => 'Fatal Error',
                    'message' => self::isDevelopment() ? $error['message'] : 'Une erreur critique est survenue.',
                ], JSON_UNESCAPED_UNICODE);
            }
        }
    }
    
    /**
     * Get human-readable error type
     */
    private static function getErrorType(int $errno): string {
        $errorTypes = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];
        
        return $errorTypes[$errno] ?? 'UNKNOWN';
    }
    
    /**
     * Check if in development mode
     */
    private static function isDevelopment(): bool {
        return ($_SERVER['SERVER_NAME'] ?? '') === 'localhost' || 
               strpos($_SERVER['SERVER_NAME'] ?? '', '127.0.0.1') !== false;
    }
}

// Auto-register error handler
ErrorHandler::register();
