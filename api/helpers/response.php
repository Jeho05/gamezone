<?php
/**
 * HTTP Response Helpers
 * Standardized response formats and helpers
 */

/**
 * Send success response with data
 * @param mixed $data Response data
 * @param string|null $message Optional success message
 * @param int $statusCode HTTP status code
 */
function success_response($data, ?string $message = null, int $statusCode = 200): void {
    $response = ['success' => true];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    if (is_array($data) && isset($data['items']) && isset($data['pagination'])) {
        // Paginated response
        $response = array_merge($response, $data);
    } else {
        $response['data'] = $data;
    }
    
    json_response($response, $statusCode);
}

/**
 * Send error response
 * @param string $message Error message
 * @param int $statusCode HTTP status code
 * @param array $details Optional error details
 */
function error_response(string $message, int $statusCode = 400, array $details = []): void {
    $response = [
        'success' => false,
        'error' => $message
    ];
    
    if (!empty($details)) {
        $response['details'] = $details;
    }
    
    // Log error if it's a server error (5xx)
    if ($statusCode >= 500) {
        Logger::error("API Error: {$message}", array_merge($details, [
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ]));
    }
    
    json_response($response, $statusCode);
}

/**
 * Send validation error response
 * @param array $errors Array of validation errors
 */
function validation_error_response(array $errors): void {
    error_response('Validation failed', 422, ['validation_errors' => $errors]);
}

/**
 * Send not found response
 * @param string $resource Resource name that was not found
 */
function not_found_response(string $resource = 'Resource'): void {
    error_response("{$resource} not found", 404);
}

/**
 * Send unauthorized response
 * @param string $message Optional message
 */
function unauthorized_response(string $message = 'Unauthorized'): void {
    error_response($message, 401);
}

/**
 * Send forbidden response
 * @param string $message Optional message
 */
function forbidden_response(string $message = 'Forbidden'): void {
    error_response($message, 403);
}

/**
 * Send created response
 * @param mixed $data Created resource data
 * @param string $message Optional success message
 */
function created_response($data, string $message = 'Resource created successfully'): void {
    success_response($data, $message, 201);
}

/**
 * Send no content response
 */
function no_content_response(): void {
    http_response_code(204);
    exit;
}

/**
 * Send file download response
 * @param string $filePath Path to file
 * @param string|null $downloadName Optional download filename
 */
function file_response(string $filePath, ?string $downloadName = null): void {
    if (!file_exists($filePath)) {
        not_found_response('File');
        return;
    }
    
    $downloadName = $downloadName ?? basename($filePath);
    $mimeType = mime_content_type($filePath);
    $fileSize = filesize($filePath);
    
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    
    readfile($filePath);
    exit;
}

/**
 * Redirect to URL
 * @param string $url Target URL
 * @param int $statusCode HTTP status code (301 or 302)
 */
function redirect_response(string $url, int $statusCode = 302): void {
    header("Location: {$url}", true, $statusCode);
    exit;
}
