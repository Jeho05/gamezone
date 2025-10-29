@echo off
echo ========================================
echo   FIX DOCKERFILE - COPIER SETUP FILES
echo ========================================
cd /d "C:\xampp\htdocs\projet ismo"

echo Adding Dockerfile...
git add Dockerfile

echo Committing...
git commit -m "Fix-Dockerfile-copy-setup-files"

echo Pushing to main...
git push origin main

echo.
echo Done! Railway va rebuild.
echo Attendre 2 min puis rafraichir:
echo https://overflowing-fulfillment-production-36c6.up.railway.app/setup_complete.php
pause
