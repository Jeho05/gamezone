@echo off
echo.
echo ============================================
echo    REDEMARRAGE SERVEUR VITE
echo ============================================
echo.

cd "createxyz-project\_\apps\web"

echo [1/3] Arret du serveur en cours...
echo Appuyez sur Ctrl+C dans le terminal du serveur
echo.
pause

echo.
echo [2/3] Nettoyage du cache Vite...
if exist "node_modules\.vite" (
    rmdir /s /q "node_modules\.vite"
    echo OK - Cache .vite supprime
)

if exist ".react-router" (
    rmdir /s /q ".react-router"
    echo OK - Cache .react-router supprime
)

echo.
echo [3/3] Redemarrage du serveur...
echo.
echo Executez maintenant: npm run dev
echo.

pause
