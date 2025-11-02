<?php
// Script to upload existing game images to the Railway backend
// This script should be run locally to upload images through the API

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/utils.php';

// Get database connection
$pdo = get_db();

// Get all games that have image URLs
$stmt = $pdo->prepare('SELECT id, name, image_url, thumbnail_url FROM games WHERE image_url IS NOT NULL OR thumbnail_url IS NOT NULL');
$stmt->execute();
$games = $stmt->fetchAll();

echo "Found " . count($games) . " games with images\n";

// Process each game
foreach ($games as $game) {
    echo "Processing game: " . $game['name'] . " (ID: " . $game['id'] . ")\n";
    
    // Check if the image URL is a local path (indicating it's an existing uploaded file)
    $imageUrl = $game['image_url'];
    $thumbnailUrl = $game['thumbnail_url'];
    
    // Process image_url
    if ($imageUrl && strpos($imageUrl, 'uploads/games/') !== false) {
        $filename = basename($imageUrl);
        $localPath = __DIR__ . '/uploads/games/' . $filename;
        
        if (file_exists($localPath)) {
            echo "  Uploading image: $filename\n";
            $newUrl = uploadImageFile($localPath, $filename);
            if ($newUrl) {
                // Update the database with the new URL
                $updateStmt = $pdo->prepare('UPDATE games SET image_url = ? WHERE id = ?');
                $updateStmt->execute([$newUrl, $game['id']]);
                echo "  Updated image URL to: $newUrl\n";
            } else {
                echo "  Failed to upload image: $filename\n";
            }
        } else {
            echo "  Local file not found: $localPath\n";
        }
    }
    
    // Process thumbnail_url
    if ($thumbnailUrl && strpos($thumbnailUrl, 'uploads/games/') !== false) {
        $filename = basename($thumbnailUrl);
        $localPath = __DIR__ . '/uploads/games/' . $filename;
        
        if (file_exists($localPath)) {
            echo "  Uploading thumbnail: $filename\n";
            $newUrl = uploadImageFile($localPath, $filename);
            if ($newUrl) {
                // Update the database with the new URL
                $updateStmt = $pdo->prepare('UPDATE games SET thumbnail_url = ? WHERE id = ?');
                $updateStmt->execute([$newUrl, $game['id']]);
                echo "  Updated thumbnail URL to: $newUrl\n";
            } else {
                echo "  Failed to upload thumbnail: $filename\n";
            }
        } else {
            echo "  Local file not found: $localPath\n";
        }
    }
    
    echo "\n";
}

echo "Upload process completed!\n";

/**
 * Upload an image file to the backend API
 * @param string $filePath Local path to the image file
 * @param string $filename Original filename
 * @return string|null New URL if successful, null otherwise
 */
function uploadImageFile($filePath, $filename) {
    // Railway backend URL
    $uploadUrl = 'https://overflowing-fulfillment-production-36c6.up.railway.app/api/admin/upload_image.php';
    
    // Note: This would require authentication which is complex to handle in a script
    // For now, we'll just show what would need to be done
    
    echo "    Would upload $filePath to $uploadUrl\n";
    echo "    (This requires authentication which is not implemented in this script)\n";
    
    // In a real implementation, you would:
    // 1. Authenticate with the API (get session cookie or auth token)
    // 2. Create a multipart form data request with the file
    // 3. Send the request to the upload endpoint
    // 4. Parse the response to get the new URL
    
    return null; // Placeholder
}