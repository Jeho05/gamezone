<?php
/**
 * Simple script to upload images to the Railway backend
 * This script demonstrates how to upload a single image file
 */

// Configuration
$backendUrl = 'https://overflowing-fulfillment-production-36c6.up.railway.app';
$uploadEndpoint = '/api/admin/upload_image.php';

// Example image to upload
$imagePath = __DIR__ . '/uploads/games/game_68f0dba0eed968.46393699.jpg';
$imageName = 'game_68f0dba0eed968.46393699.jpg';

if (!file_exists($imagePath)) {
    die("Image file not found: $imagePath\n");
}

// Create a simple form data request
$boundary = uniqid();
$data = "--" . $boundary . "\r\n";
$data .= 'Content-Disposition: form-data; name="image"; filename="' . $imageName . '"' . "\r\n";
$data .= "Content-Type: image/jpeg\r\n\r\n";
$data .= file_get_contents($imagePath) . "\r\n";
$data .= "--" . $boundary . "--\r\n";

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'Content-Length: ' . strlen($data)
        ],
        'content' => $data,
        'ignore_errors' => true
    ]
]);

echo "Uploading $imageName to $backendUrl$uploadEndpoint\n";

$result = file_get_contents($backendUrl . $uploadEndpoint, false, $context);

if ($result === false) {
    echo "Failed to upload image\n";
    print_r($http_response_header);
} else {
    echo "Upload response:\n";
    echo $result . "\n";
    
    // Parse the response to get the new URL
    $response = json_decode($result, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($response['url'])) {
        echo "New image URL: " . $response['url'] . "\n";
    }
}