<?php
// Debug .env.railway parsing
header('Content-Type: application/json');

$railwayEnv = __DIR__ . '/.env.railway';
$result = [
    'file_exists' => file_exists($railwayEnv),
    'file_readable' => is_readable($railwayEnv),
    'file_size' => file_exists($railwayEnv) ? filesize($railwayEnv) : 0,
    'raw_content' => '',
    'parsed_lines' => [],
    'env_after_parse' => []
];

if (file_exists($railwayEnv)) {
    $content = file_get_contents($railwayEnv);
    $result['raw_content'] = $content;
    $result['raw_preview'] = substr($content, 0, 200);
    
    // Parse line by line
    $lines = file($railwayEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $index => $line) {
        $trimmed = trim($line);
        $result['parsed_lines'][] = [
            'index' => $index,
            'raw' => $line,
            'trimmed' => $trimmed,
            'is_comment' => strpos($trimmed, '#') === 0,
            'has_equals' => strpos($line, '=') !== false
        ];
        
        // Try to parse
        if (strpos($trimmed, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $result['env_after_parse'][$key] = $value;
        }
    }
}

// Get final env values
$result['final_env'] = [
    'SESSION_SAMESITE' => getenv('SESSION_SAMESITE') ?: 'NOT_SET',
    'SESSION_SECURE' => getenv('SESSION_SECURE') ?: 'NOT_SET',
    'DB_HOST' => getenv('DB_HOST') ?: 'NOT_SET',
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
