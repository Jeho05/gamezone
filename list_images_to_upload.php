<?php
/**
 * Script to list all game images that need to be uploaded to the Railway backend
 */

echo "Game images that need to be uploaded to Railway backend:\n";
echo "=====================================================\n\n";

$uploadsDir = __DIR__ . '/uploads/games/';
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    $imageCount = 0;
    
    foreach ($files as $file) {
        // Skip directory entries and README file
        if ($file !== '.' && $file !== '..' && $file !== 'README.txt') {
            $filePath = $uploadsDir . $file;
            if (is_file($filePath)) {
                $fileSize = filesize($filePath);
                $fileSizeFormatted = formatBytes($fileSize);
                echo "- $file ($fileSizeFormatted)\n";
                $imageCount++;
            }
        }
    }
    
    echo "\nTotal images to upload: $imageCount\n";
    
    if ($imageCount > 0) {
        echo "\nInstructions:\n";
        echo "1. Log in to the admin panel at https://gamezoneismo.vercel.app/auth/login\n";
        echo "2. Go to the shop management page at https://gamezoneismo.vercel.app/admin/shop\n";
        echo "3. For each game with a missing image:\n";
        echo "   - Click the 'Modifier' button\n";
        echo "   - Re-upload the corresponding image file\n";
        echo "   - Save the changes\n";
    }
} else {
    echo "Uploads directory not found: $uploadsDir\n";
}

/**
 * Format bytes to human readable format
 * @param int $bytes Number of bytes
 * @param int $precision Decimal precision
 * @return string Formatted bytes
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}