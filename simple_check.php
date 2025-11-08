<?php
echo "Checking for games with missing images...\n";

// List all game images in the uploads directory
$uploadsDir = __DIR__ . '/uploads/games/';
echo "Looking for images in: $uploadsDir\n";

if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    echo "Found " . (count($files) - 2) . " files in uploads directory:\n"; // -2 for . and ..
    
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== 'README.txt') {
            echo "- $file\n";
        }
    }
} else {
    echo "Uploads directory not found!\n";
}

echo "\nTo fix the missing images:\n";
echo "1. Log in to the admin panel at https://gamezoneismo.vercel.app/auth/login\n";
echo "2. Go to the shop management page\n";
echo "3. For each game with a missing image, edit it and re-upload the corresponding image file\n";