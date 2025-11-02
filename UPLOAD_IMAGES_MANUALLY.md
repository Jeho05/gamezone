# Manual Image Upload Instructions

This document explains how to manually upload the existing game images to the Railway backend so they can be served correctly.

## Problem

The game images are currently stored locally in the `uploads/games/` directory, but they're not available on the deployed Railway backend. This causes 404 errors when trying to view the images in the admin shop.

## Solution

You need to upload the existing images to the Railway backend through the admin interface.

## Steps

1. **Log in to the admin panel**
   - Go to: https://gamezoneismo.vercel.app/auth/login
   - Log in with admin credentials

2. **Go to the shop management page**
   - Navigate to: https://gamezoneismo.vercel.app/admin/shop

3. **Edit each game that has a missing image**
   - For each game with a missing image:
     - Click the "Modifier" button
     - Re-upload the image using the image upload field
     - Save the changes

## Alternative: Direct API Upload

If you prefer to upload images directly through the API, you can use a tool like curl:

```bash
curl -X POST \
  -H "Cookie: PHPSESSID=your_session_id" \
  -F "image=@/path/to/your/image.jpg" \
  https://overflowing-fulfillment-production-36c6.up.railway.app/api/admin/upload_image.php
```

This will return a JSON response with the new image URL that you can then use to update the game records in the database.

## List of Images to Upload

The following images need to be uploaded:

```
<?php
// List all game images
$uploadsDir = __DIR__ . '/uploads/games/';
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== 'README.txt') {
            echo "- $file\n";
        }
    }
}
?>
```

## After Uploading

After uploading all images, the games in the admin shop should display correctly instead of showing placeholder images.