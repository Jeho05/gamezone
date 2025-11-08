@echo off
cd /d "C:\xampp\htdocs\projet ismo"
echo Adding setup_complete.php...
git add backend_infinityfree\api\setup_complete.php
git status backend_infinityfree\api\setup_complete.php
echo.
echo Committing...
git commit -m "Add-setup-complete-php-file"
echo.
echo Pushing...
git push origin backend-railway
echo.
echo Done! Wait 2 min then open:
echo https://overflowing-fulfillment-production-36c6.up.railway.app/setup_complete.php
pause
