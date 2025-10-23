@echo off
echo.
echo ============================================
echo    CORRECTION VIDEO CYBER ARCADE
echo ============================================
echo.

set SOURCE=images\video\Cyber_Arcade_Neon_Ember.mp4
set TARGET1=createxyz-project\_\apps\web\public\images\video\Cyber_Arcade_Neon_Ember.mp4
set TARGET2=createxyz-project\_\apps\web\public\images\video\cyber-arcade-neon.mp4

echo [1/3] Suppression de l'ancienne video...
if exist "%TARGET1%" (
    del "%TARGET1%"
    echo OK - Ancienne video supprimee
)

echo.
echo [2/3] Copie de la video avec nouveau nom...
copy "%SOURCE%" "%TARGET2%" >nul
if %ERRORLEVEL% EQU 0 (
    echo OK - Video copiee: cyber-arcade-neon.mp4
) else (
    echo ERREUR lors de la copie
    pause
    exit /b 1
)

echo.
echo [3/3] Copie aussi avec nom original...
copy "%SOURCE%" "%TARGET1%" >nul
if %ERRORLEVEL% EQU 0 (
    echo OK - Video copiee: Cyber_Arcade_Neon_Ember.mp4
)

echo.
echo ============================================
echo    CORRECTION TERMINEE !
echo ============================================
echo.
echo Deux versions disponibles maintenant:
echo 1. Cyber_Arcade_Neon_Ember.mp4 (nom original)
echo 2. cyber-arcade-neon.mp4 (nouveau nom)
echo.
echo Prochaines etapes:
echo 1. Tester: http://localhost:4000/test-video.html
echo 2. Rafraichir avec Ctrl+Shift+R
echo.

pause
