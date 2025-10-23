@echo off
echo.
echo ============================================
echo    NETTOYAGE DU CACHE VITE
echo ============================================
echo.

cd "createxyz-project\_\apps\web"

echo [1/3] Suppression du cache Vite...
if exist ".react-router" (
    rmdir /s /q ".react-router"
    echo OK - Cache .react-router supprime
) else (
    echo Info - .react-router n'existe pas
)

echo.
echo [2/3] Suppression du cache node_modules/.vite...
if exist "node_modules\.vite" (
    rmdir /s /q "node_modules\.vite"
    echo OK - Cache .vite supprime
) else (
    echo Info - .vite n'existe pas
)

echo.
echo [3/3] Suppression du cache dist...
if exist "dist" (
    rmdir /s /q "dist"
    echo OK - Dossier dist supprime
) else (
    echo Info - dist n'existe pas
)

echo.
echo ============================================
echo    CACHE NETTOYE !
echo ============================================
echo.
echo Prochaines etapes:
echo 1. Redemarrer le serveur (npm run dev)
echo 2. Vider le cache du navigateur (Ctrl+Shift+R)
echo 3. Retester: http://localhost:4000/test-video.html
echo.

pause
