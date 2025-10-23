<?php
/**
 * Security Middleware
 * Provides security headers and rate limiting
 */

/**
 * Add security headers to all responses
 */
function add_security_headers(): void {
    // Prevent XSS attacks
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Content Security Policy (adjust for production)
    $env = getenv('APP_ENV') ?: 'development';
    if ($env === 'production') {
        $csp = "default-src 'self'; script-src 'self' https://kit.fontawesome.com; style-src 'self'; img-src 'self' data: https:; font-src 'self' data: https://ka-f.fontawesome.com;";
    } else {
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://kit.fontawesome.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data: https://ka-f.fontawesome.com;";
    }
    header("Content-Security-Policy: $csp");
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Simple rate limiting based on IP address
 * @param string $action Action identifier (e.g., 'login', 'register')
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $windowSeconds Time window in seconds
 * @return bool True if rate limit not exceeded, false otherwise
 */
function check_rate_limit(string $action, int $maxAttempts = 5, int $windowSeconds = 300): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "rate_limit_{$action}_{$ip}";
    
    // Use file-based rate limiting (simple implementation)
    $rateLimitDir = sys_get_temp_dir() . '/gamezone_rate_limits';
    if (!is_dir($rateLimitDir)) {
        @mkdir($rateLimitDir, 0777, true);
    }
    
    $rateLimitFile = $rateLimitDir . '/' . md5($key) . '.txt';
    $now = time();
    
    // Read current attempts
    $attempts = [];
    if (file_exists($rateLimitFile)) {
        $content = file_get_contents($rateLimitFile);
        $attempts = $content ? json_decode($content, true) : [];
    }
    
    // Clean old attempts outside the window
    $attempts = array_filter($attempts, function($timestamp) use ($now, $windowSeconds) {
        return ($now - $timestamp) < $windowSeconds;
    });
    
    // Check if limit exceeded
    if (count($attempts) >= $maxAttempts) {
        return false;
    }
    
    // Add current attempt
    $attempts[] = $now;
    file_put_contents($rateLimitFile, json_encode($attempts));
    
    return true;
}

/**
 * Sanitize input string to prevent XSS
 * @param string $input Raw input
 * @return string Sanitized input
 */
function sanitize_input(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate and sanitize array of inputs
 * @param array $inputs Array of inputs
 * @param array $rules Validation rules
 * @return array Sanitized inputs or error
 */
function validate_inputs(array $inputs, array $rules): array {
    $sanitized = [];
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $inputs[$field] ?? null;
        
        // Required check
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[$field] = "{$field} est requis";
            continue;
        }
        
        if (empty($value)) {
            $sanitized[$field] = $rule['default'] ?? null;
            continue;
        }
        
        // Type validation
        switch ($rule['type'] ?? 'string') {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "Email invalide";
                } else {
                    $sanitized[$field] = filter_var($value, FILTER_SANITIZE_EMAIL);
                }
                break;
                
            case 'int':
                if (!is_numeric($value)) {
                    $errors[$field] = "{$field} doit être un nombre";
                } else {
                    $sanitized[$field] = (int)$value;
                }
                break;
                
            case 'string':
            default:
                $sanitized[$field] = sanitize_input((string)$value);
                
                // Min length
                if (isset($rule['min']) && strlen($sanitized[$field]) < $rule['min']) {
                    $errors[$field] = "{$field} doit contenir au moins {$rule['min']} caractères";
                }
                
                // Max length
                if (isset($rule['max']) && strlen($sanitized[$field]) > $rule['max']) {
                    $errors[$field] = "{$field} ne peut pas dépasser {$rule['max']} caractères";
                }
                break;
        }
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    return ['success' => true, 'data' => $sanitized];
}
