@echo off
chcp 65001 > nul
color 0A

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                                                              ║
echo ║        🔍 DIAGNOSTIC COMPLET GAMEZONE 🔍                     ║
echo ║                                                              ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.
echo.
echo Choisissez une option :
echo.
echo  [1] 🖥️  Diagnostic LOCAL (PowerShell - Avant build)
echo  [2] 🌐 Diagnostic WEB (Ouvre le navigateur)
echo  [3] 📊 Diagnostic COMPLET (Les 2)
echo  [4] 📝 Afficher le guide
echo  [5] ❌ Quitter
echo.
set /p choice="Votre choix (1-5) : "

if "%choice%"=="1" goto local
if "%choice%"=="2" goto web
if "%choice%"=="3" goto complete
if "%choice%"=="4" goto guide
if "%choice%"=="5" goto end

echo.
echo ❌ Choix invalide !
timeout /t 2 > nul
goto end

:local
echo.
echo ========================================
echo   LANCEMENT DIAGNOSTIC LOCAL
echo ========================================
echo.
cd "createxyz-project\_\apps\web"
powershell -ExecutionPolicy Bypass -File diagnostic.ps1
pause
goto end

:web
echo.
echo ========================================
echo   OUVERTURE DIAGNOSTIC WEB
echo ========================================
echo.
echo Ouverture dans le navigateur...
start https://Jeho05.github.io/gamezone/diagnostic.html
timeout /t 2 > nul
echo.
echo ✅ Page ouverte !
echo.
echo 📋 INSTRUCTIONS:
echo 1. Cliquez sur "LANCER TOUS LES TESTS"
echo 2. Attendez la fin (30-60 secondes)
echo 3. Cliquez sur "COPIER RAPPORT"
echo 4. Collez dans un fichier texte
echo.
pause
goto end

:complete
echo.
echo ========================================
echo   DIAGNOSTIC COMPLET
echo ========================================
echo.
echo [1/2] Diagnostic LOCAL...
echo ----------------------------------------
cd "createxyz-project\_\apps\web"
powershell -ExecutionPolicy Bypass -File diagnostic.ps1
echo.
echo.
echo [2/2] Diagnostic WEB...
echo ----------------------------------------
echo Ouverture dans le navigateur...
start https://Jeho05.github.io/gamezone/diagnostic.html
echo.
echo ✅ Diagnostic local terminé !
echo 🌐 Diagnostic web ouvert dans le navigateur
echo.
echo 📋 INSTRUCTIONS WEB:
echo 1. Cliquez sur "LANCER TOUS LES TESTS"
echo 2. Attendez la fin
echo 3. Cliquez sur "COPIER RAPPORT"
echo.
pause
goto end

:guide
echo.
echo ========================================
echo   OUVERTURE DU GUIDE
echo ========================================
echo.
if exist "GUIDE_DIAGNOSTIC.md" (
    start GUIDE_DIAGNOSTIC.md
    echo ✅ Guide ouvert !
) else (
    echo ❌ Fichier GUIDE_DIAGNOSTIC.md introuvable
)
echo.
pause
goto end

:end
echo.
echo Au revoir ! 👋
echo.
timeout /t 2 > nul
