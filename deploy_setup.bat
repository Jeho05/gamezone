@echo off
cd /d "C:\xampp\htdocs\projet ismo"
git add backend_infinityfree\api\setup_complete.php
git commit -m "Add-setup-complete-script"
git push origin backend-railway
echo.
echo Done! Attendre 2 min puis ouvrir:
echo https://overflowing-fulfillment-production-36c6.up.railway.app/setup_complete.php
pause
