<?php
/**
 * Script to upload all game images to the Railway backend
 */

// Configuration
$backendUrl = 'https://overflowing-fulfillment-production-36c6.up.railway.app';
$uploadEndpoint = '/api/admin/upload_image.php';
$uploadsDir = __DIR__ . '/uploads/games/';

echo "Starting image upload process...\n";
echo "Backend URL: $backendUrl\n";
echo "Upload endpoint: $uploadEndpoint\n";
echo "Uploads directory: $uploadsDir\n\n";

if (!is_dir($uploadsDir)) {
    die("Uploads directory not found: $uploadsDir\n");
}

// Get all image files
$files = scandir($uploadsDir);
$imageFiles = [];

foreach ($files as $file) {
    if ($file !== '.' && $file !== '..' && $file !== 'README.txt' && pathinfo($file, PATHINFO_EXTENSION) !== '') {
        $imageFiles[] = $file;
    }
}

echo "Found " . count($imageFiles) . " image files to upload:\n";
foreach ($imageFiles as $file) {
    echo "- $file\n";
}
echo "\n";

// Upload each image
foreach ($imageFiles as $filename) {
    $imagePath = $uploadsDir . $filename;
    
    echo "Uploading $filename...\n";
    
    if (!file_exists($imagePath)) {
        echo "  File not found, skipping...\n";
        continue;
    }
    
    // Create a simple form data request
    $boundary = uniqid();
    $data = "--" . $boundary . "\r\n";
    $data .= 'Content-Disposition: form-data; name="image"; filename="' . $filename . '"' . "\r\n";
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
    
    $result = file_get_contents($backendUrl . $uploadEndpoint, false, $context);
    
    if ($result === false) {
        echo "  Failed to upload $filename\n";
        if (isset($http_response_header)) {
            print_r($http_response_header);
        }
    } else {
        $response = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($response['url'])) {
            echo "  Success! New URL: " . $response['url'] . "\n";
        } else {
            echo "  Upload completed but unexpected response:\n";
            echo $result . "\n";
        }
    }
    
    echo "\n";
}

echo "Image upload process completed!\n";