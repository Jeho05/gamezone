@echo off
cd /d "C:\xampp\htdocs\projet ismo"
git add setup_complete.php
git commit -m "Use-DB-vars-from-env-railway"
git push origin backend-railway
echo Done! Attendre 2 min:
echo https://overflowing-fulfillment-production-36c6.up.railway.app/setup_complete.php
pause
