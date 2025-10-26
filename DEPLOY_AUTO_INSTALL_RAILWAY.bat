@echo off
echo ============================================
echo   DEPLOIEMENT AUTO-INSTALL RAILWAY
echo ============================================
echo.

echo [1/5] Switching to backend-railway branch...
git checkout backend-railway
echo.

echo [2/5] Adding modified files...
git add backend_infinityfree\api\auto_install.php backend_infinityfree\api\config.php
echo.

echo [3/5] Committing changes...
git commit -m "Fix: Auto-install database on Railway first run"
echo.

echo [4/5] Pushing to GitHub (Railway will auto-deploy)...
git push origin backend-railway
echo.

echo [5/5] Done!
echo.
echo ============================================
echo   RESULTATS ATTENDUS
echo ============================================
echo.
echo 1. Railway detecte le push et rebuild
echo 2. Au premier chargement, auto_install.php s'execute
echo 3. Toutes les tables sont creees automatiquement
echo 4. Admin cree: admin@gmail.com / demo123
echo 5. Plus d'erreurs 500 !
echo.
echo Attendre 3-5 minutes pour le redeploiement Railway.
echo.
pause
