@echo off
echo ================================================
echo DEPLOIEMENT COMPLET DU BACKEND VERS RAILWAY
echo ================================================
echo.

cd /d "C:\xampp\htdocs\projet ismo"

echo [1/5] Checkout backend-railway...
git checkout backend-railway
echo.

echo [2/5] Add all API files...
git add backend_infinityfree\api\
echo.

echo [3/5] Commit...
git commit -m "Deploy-complete-backend-with-all-files"
echo.

echo [4/5] Push to Railway...
git push origin backend-railway
echo.

echo [5/5] Done!
echo.
echo ================================================
echo BACKEND COMPLET DEPLOYE SUR RAILWAY
echo ================================================
echo.
echo Railway va rebuild (3-5 min)
echo Tous les fichiers PHP sont maintenant presents
echo.
pause
