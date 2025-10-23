@echo off
chcp 65001 >nul
color 0A
cls

echo.
echo ═══════════════════════════════════════════════════════════════
echo     🎮 VÉRIFICATION RAPIDE - SYSTÈME DE RÉCOMPENSES
echo ═══════════════════════════════════════════════════════════════
echo.

echo [1/3] Vérification de la structure de la base de données...
C:\xampp\php\php.exe verify_rewards_real.php > verification_rapide.log 2>&1
if %errorlevel% equ 0 (
    echo ✅ Structure BD: OK
) else (
    echo ❌ Erreur structure BD
)

echo.
echo [2/3] Test du système d'échange...
C:\xampp\php\php.exe final_verification.php > verification_finale.log 2>&1
if %errorlevel% equ 0 (
    echo ✅ Échange: OK
) else (
    echo ❌ Erreur échange
)

echo.
echo [3/3] Affichage des résultats...
echo.
C:\xampp\php\php.exe afficher_resultats.php

echo.
echo ═══════════════════════════════════════════════════════════════
echo.
echo 📄 Logs sauvegardés dans:
echo    - verification_rapide.log
echo    - verification_finale.log
echo.
echo 🌐 Accéder à l'application:
echo    http://localhost:4000/player/rewards
echo.
pause
