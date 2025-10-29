@echo off
echo =============================================
echo   FIX DOCKERFILE - RAILWAY PATHS
echo =============================================
echo.
cd /d "C:\xampp\htdocs\projet ismo"

echo [1/4] Adding files...
git add backend_infinityfree\api\Dockerfile
git add backend_infinityfree\api\railway.json
echo.

echo [2/4] Committing...
git commit -m "Fix-Dockerfile-paths-for-Railway"
echo.

echo [3/4] Pushing...
git push origin backend-railway
echo.

echo [4/4] Done!
echo.
echo =============================================
echo   ATTENDRE 3 MIN PUIS TESTER
echo =============================================
echo.
echo Railway va rebuild avec les bons chemins
echo Ensuite ouvrir:
echo https://overflowing-fulfillment-production-36c6.up.railway.app/setup_complete.php
echo.
pause
