@echo off
echo.
echo ============================================
echo    COPIE DES IMAGES VERS PUBLIC
echo ============================================
echo.

set SOURCE=images
set TARGET=createxyz-project\_\apps\web\public\images

echo [1/2] Verification du dossier source...
if not exist "%SOURCE%" (
    echo ERREUR: Le dossier 'images' n'existe pas!
    pause
    exit /b 1
)
echo OK - Dossier source existe

echo.
echo [2/2] Copie des fichiers...
xcopy "%SOURCE%" "%TARGET%" /E /I /Y /Q

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo    COPIE TERMINEE AVEC SUCCES!
    echo ============================================
    echo.
    echo Les images sont maintenant dans:
    echo %TARGET%
    echo.
    echo Prochaines etapes:
    echo 1. Rafraichir la page ^(Ctrl+F5^)
    echo 2. Tester: http://localhost:4000/
    echo.
) else (
    echo.
    echo ERREUR lors de la copie!
)

pause
