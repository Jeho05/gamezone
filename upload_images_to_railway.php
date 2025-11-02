<?php
/**
 * Script to upload existing game images to the Railway backend
 * 
 * This script will:
 * 1. Authenticate with the backend API
 * 2. Upload all existing game images from the local uploads directory
 * 3. Update the database with the new image URLs
 * 
 * Usage: Run this script locally from the command line
 * php upload_images_to_railway.php
 */

// Configuration
$backendUrl = 'https://overflowing-fulfillment-production-36c6.up.railway.app';
$loginEndpoint = '/api/auth/login.php';
$uploadEndpoint = '/api/admin/upload_image.php';
$gamesEndpoint = '/api/admin/games.php';

// Admin credentials (you need to fill these in)
$adminUsername = 'admin';  // Replace with actual admin username
$adminPassword = 'password';  // Replace with actual admin password

// Get database connection
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/utils.php';
$pdo = get_db();

echo "Starting image upload process...\n";

// Step 1: Authenticate with the backend
echo "Authenticating with backend...\n";
$sessionCookie = authenticate($backendUrl . $loginEndpoint, $adminUsername, $adminPassword);

if (!$sessionCookie) {
    die("Failed to authenticate with backend\n");
}

echo "Authentication successful!\n";

// Step 2: Get all games with images
echo "Fetching games with images...\n";
$games = getGamesWithImages($backendUrl . $gamesEndpoint, $sessionCookie);

if (empty($games)) {
    die("No games with images found\n");
}

echo "Found " . count($games) . " games with images\n";

// Step 3: Process each game and upload images
foreach ($games as $game) {
    echo "Processing game: " . $game['name'] . " (ID: " . $game['id'] . ")\n";
    
    // Process image_url
    if (!empty($game['image_url']) && strpos($game['image_url'], 'uploads/games/') !== false) {
        $filename = basename($game['image_url']);
        $localPath = __DIR__ . '/uploads/games/' . $filename;
        
        if (file_exists($localPath)) {
            echo "  Uploading image: $filename\n";
            $newUrl = uploadImage($backendUrl . $uploadEndpoint, $localPath, $filename, $sessionCookie);
            
            if ($newUrl) {
                echo "  Uploaded successfully. New URL: $newUrl\n";
                // Update database
                updateGameImage($pdo, $game['id'], 'image_url', $newUrl);
                echo "  Database updated\n";
            } else {
                echo "  Failed to upload image\n";
            }
        } else {
            echo "  Local file not found: $localPath\n";
        }
    }
    
    // Process thumbnail_url
    if (!empty($game['thumbnail_url']) && strpos($game['thumbnail_url'], 'uploads/games/') !== false) {
        $filename = basename($game['thumbnail_url']);
        $localPath = __DIR__ . '/uploads/games/' . $filename;
        
        if (file_exists($localPath)) {
            echo "  Uploading thumbnail: $filename\n";
            $newUrl = uploadImage($backendUrl . $uploadEndpoint, $localPath, $filename, $sessionCookie);
            
            if ($newUrl) {
                echo "  Uploaded successfully. New URL: $newUrl\n";
                // Update database
                updateGameImage($pdo, $game['id'], 'thumbnail_url', $newUrl);
                echo "  Database updated\n";
            } else {
                echo "  Failed to upload thumbnail\n";
            }
        } else {
            echo "  Local file not found: $localPath\n";
        }
    }
    
    echo "\n";
}

echo "Image upload process completed!\n";

/**
 * Authenticate with the backend API
 * @param string $loginUrl Login endpoint URL
 * @param string $username Admin username
 * @param string $password Admin password
 * @return string|null Session cookie if successful, null otherwise
 */
function authenticate($loginUrl, $username, $password) {
    $postData = http_build_query([
        'username' => $username,
        'password' => $password
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: ' . strlen($postData)
            ],
            'content' => $postData,
            'ignore_errors' => true
        ]
    ]);
    
    $result = file_get_contents($loginUrl, false, $context);
    
    // Check if authentication was successful by looking for session cookie
    $headers = $http_response_header ?? [];
    foreach ($headers as $header) {
        if (preg_match('/^Set-Cookie:\s*(PHPSESSID=[^;]+)/i', $header, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Get all games that have image URLs
 * @param string $gamesUrl Games endpoint URL
 * @param string $sessionCookie Authentication session cookie
 * @return array Array of games with images
 */
function getGamesWithImages($gamesUrl, $sessionCookie) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Cookie: ' . $sessionCookie,
                'Accept: application/json'
            ],
            'ignore_errors' => true
        ]
    ]);
    
    $result = file_get_contents($gamesUrl, false, $context);
    
    if ($result === false) {
        return [];
    }
    
    $data = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }
    
    return $data['games'] ?? [];
}

/**
 * Upload an image file to the backend
 * @param string $uploadUrl Upload endpoint URL
 * @param string $filePath Local file path
 * @param string $filename Original filename
 * @param string $sessionCookie Authentication session cookie
 * @return string|null New image URL if successful, null otherwise
 */
function uploadImage($uploadUrl, $filePath, $filename, $sessionCookie) {
    // Create a temporary file with the image data for upload
    $boundary = uniqid();
    $data = '';
    
    // Add file data
    $data .= "--" . $boundary . "\r\n";
    $data .= 'Content-Disposition: form-data; name="image"; filename="' . $filename . '"' . "\r\n";
    $data .= "Content-Type: image/jpeg\r\n\r\n";
    $data .= file_get_contents($filePath) . "\r\n";
    $data .= "--" . $boundary . "--\r\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Cookie: ' . $sessionCookie,
                'Content-Type: multipart/form-data; boundary=' . $boundary,
                'Content-Length: ' . strlen($data)
            ],
            'content' => $data,
            'ignore_errors' => true
        ]
    ]);
    
    $result = file_get_contents($uploadUrl, false, $context);
    
    if ($result === false) {
        return null;
    }
    
    $response = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $response['url'] ?? null;
}

/**
 * Update a game's image URL in the database
 * @param PDO $pdo Database connection
 * @param int $gameId Game ID
 * @param string $field Field to update (image_url or thumbnail_url)
 * @param string $newUrl New image URL
 */
function updateGameImage($pdo, $gameId, $field, $newUrl) {
    $stmt = $pdo->prepare("UPDATE games SET {$field} = ?, updated_at = ? WHERE id = ?");
    $stmt->execute([$newUrl, date('Y-m-d H:i:s'), $gameId]);
}