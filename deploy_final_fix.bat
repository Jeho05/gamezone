@echo off
echo =============================================
echo   FIX FINAL - DOCKERFILE A LA RACINE
echo =============================================
cd /d "C:\xampp\htdocs\projet ismo"

echo Adding files...
git add Dockerfile
git add railway.json
git add setup_complete.php
git add init_all_tables.php

echo Committing...
git commit -m "Fix-Dockerfile-at-root-with-correct-paths"

echo Pushing...
git push origin backend-railway

echo.
echo Done! Attendre 3 min puis ouvrir:
echo https://overflowing-fulfillment-production-36c6.up.railway.app/setup_complete.php
pause
