@echo off
cd /d "C:\xampp\htdocs\projet ismo"
git add setup_complete.php
git commit -m "Fix-MySQL-env-railway-loading"
git push origin backend-railway
echo Done! Attendre 2 min puis rafraichir:
echo https://overflowing-fulfillment-production-36c6.up.railway.app/setup_complete.php
pause
