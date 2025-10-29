@echo off
echo =============================================
echo   DEPLOIEMENT FICHIERS A LA RACINE
echo =============================================
echo.
cd /d "C:\xampp\htdocs\projet ismo"

echo [1/4] Adding files...
git add setup_complete.php
git add init_all_tables.php
echo.

echo [2/4] Status...
git status
echo.

echo [3/4] Committing...
git commit -m "Add-setup-files-at-root"
echo.

echo [4/4] Pushing...
git push origin backend-railway
echo.

echo Done!
echo.
echo =============================================
echo   ATTENDRE 3 MIN PUIS OUVRIR
echo =============================================
echo.
echo https://overflowing-fulfillment-production-36c6.up.railway.app/setup_complete.php
echo.
pause
