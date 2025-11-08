@echo off
echo =============================================
echo   DEPLOY TO RAILWAY (MAIN BRANCH)
echo =============================================
cd /d "C:\xampp\htdocs\projet ismo"
echo.
echo Pushing backend-railway to main...
git push origin backend-railway:main
echo.
echo Done! Railway va rebuild depuis main
echo Attendre 2 min puis rafraichir:
echo https://overflowing-fulfillment-production-36c6.up.railway.app/setup_complete.php
pause
