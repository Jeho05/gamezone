<?php
// Version check - to verify if the updated config.php is on the server
header('Content-Type: application/json');

$configFile = __DIR__ . '/config.php';
$configContent = file_get_contents($configFile);

// Check for the new version markers
$hasNewCORS = strpos($configContent, '// CORS Configuration - MUST be set BEFORE any other headers') !== false;
$hasAddSecurityAfter = strpos($configContent, '// Add security headers AFTER CORS') !== false;
$hasFallbackVercel = strpos($configContent, '// Fallback: allow Vercel in production') !== false;

// Get file modification time
$fileModTime = date('Y-m-d H:i:s', filemtime($configFile));
$fileSize = filesize($configFile);

// Check first 200 characters
$first200 = substr($configContent, 0, 200);

echo json_encode([
    'test' => 'Config Version Check',
    'timestamp' => date('Y-m-d H:i:s'),
    'config_exists' => file_exists($configFile),
    'file_modified' => $fileModTime,
    'file_size_bytes' => $fileSize,
    'has_new_cors_comment' => $hasNewCORS,
    'has_security_after_comment' => $hasAddSecurityAfter,
    'has_fallback_vercel' => $hasFallbackVercel,
    'first_200_chars' => $first200,
    'verdict' => ($hasNewCORS && $hasAddSecurityAfter && $hasFallbackVercel) 
        ? '✅ NEW VERSION UPLOADED' 
        : '❌ OLD VERSION STILL ON SERVER'
], JSON_PRETTY_PRINT);
