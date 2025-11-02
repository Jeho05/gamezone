# Manual Image Upload Instructions

## Problem
The game images are returning 404 errors because they exist locally but haven't been uploaded to the Railway backend.

## Solution
Manually upload the existing images through the admin interface.

## Steps

1. **Log in to the admin panel**
   - Go to: https://gamezoneismo.vercel.app/auth/login
   - Log in with admin credentials

2. **Go to the shop management page**
   - Navigate to: https://gamezoneismo.vercel.app/admin/shop

3. **For each game with a missing image, edit and re-upload the image:**

### Game Images to Upload

There are 10 images that need to be uploaded:

1. **game_68f0d71c149a40.32602844.jpg** (375.74 KB)
   - Local path: `uploads/games/game_68f0d71c149a40.32602844.jpg`

2. **game_68f0dba0eed968.46393699.jpg** (162.06 KB)
   - Local path: `uploads/games/game_68f0dba0eed968.46393699.jpg`

3. **game_68f0f9731e80d6.38115405.jpg** (134.19 KB)
   - Local path: `uploads/games/game_68f0f9731e80d6.38115405.jpg`

4. **game_68f26495c90da6.42376358.jpg** (161.9 KB)
   - Local path: `uploads/games/game_68f26495c90da6.42376358.jpg`

5. **game_68f394b18e7f48.27099222.jpg** (134.19 KB)
   - Local path: `uploads/games/game_68f394b18e7f48.27099222.jpg`

6. **game_68f39a0db36e43.18515578.jpg** (134.19 KB)
   - Local path: `uploads/games/game_68f39a0db36e43.18515578.jpg`

7. **game_68f39c120c17a0.15415663.jpg** (375.74 KB)
   - Local path: `uploads/games/game_68f39c120c17a0.15415663.jpg`

8. **game_68f3b0ac5557c9.04825248.jpg** (134.19 KB)
   - Local path: `uploads/games/game_68f3b0ac5557c9.04825248.jpg`

9. **game_68f3ca9ad93680.24022614.jpg** (162.06 KB)
   - Local path: `uploads/games/game_68f3ca9ad93680.24022614.jpg`

10. **game_68f3d9e902c785.26579253.jpg** (162.06 KB)
    - Local path: `uploads/games/game_68f3d9e902c785.26579253.jpg`

### How to Upload Each Image

1. Find the corresponding game in the admin shop
2. Click the "Modifier" (Edit) button for that game
3. Click on the current image to open the image upload dialog
4. Select the corresponding image file from your local `uploads/games/` directory
5. Click "Save" or "Update" to upload the image
6. Repeat for all 10 images

### After Uploading

After uploading all images, the games in the admin shop should display correctly instead of showing placeholder images.