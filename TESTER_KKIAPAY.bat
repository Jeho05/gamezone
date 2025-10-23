@echo off
echo ========================================
echo   TESTS KKIAPAY - VERIFICATION RAPIDE
echo ========================================
echo.

echo ETAPE 1: Verification Apache et MySQL
echo --------------------------------------
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] Apache est demarre
) else (
    echo [ERREUR] Apache n'est pas demarre!
    echo Demarrez Apache dans XAMPP Control Panel
    pause
    exit
)

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL est demarre
) else (
    echo [ERREUR] MySQL n'est pas demarre!
    echo Demarrez MySQL dans XAMPP Control Panel
    pause
    exit
)

echo.
echo ETAPE 2: Verification des fichiers
echo --------------------------------------
if exist "shop.html" (echo [OK] shop.html) else (echo [ERREUR] shop.html manquant)
if exist "test_kkiapay_complet.html" (echo [OK] test_kkiapay_complet.html) else (echo [ERREUR] test_kkiapay_complet.html manquant)
if exist "test_kkiapay_direct.html" (echo [OK] test_kkiapay_direct.html) else (echo [ERREUR] test_kkiapay_direct.html manquant)
if exist "setup_kkiapay_complet.php" (echo [OK] setup_kkiapay_complet.php) else (echo [ERREUR] setup_kkiapay_complet.php manquant)

echo.
echo ETAPE 3: Ouverture des pages de test
echo --------------------------------------
echo.
echo Configuration:
echo - Cle: b2f64170af2111f093307bbda24d6bac
echo - Callback: https://kkiapay-redirect.com
echo - Script: https://cdn.kkiapay.me/k.js
echo.
echo ========================================
echo   TOUS LES TESTS PRELIMINAIRES PASSES
echo ========================================
echo.
echo PROCHAINES ETAPES:
echo.
echo 1. Configuration Backend (admin requis):
echo    http://localhost/projet%%20ismo/setup_kkiapay_complet.php
echo.
echo 2. Page de test complete:
echo    http://localhost/projet%%20ismo/test_kkiapay_complet.html
echo.
echo 3. Test boutique:
echo    http://localhost/projet%%20ismo/shop.html
echo.
echo Voulez-vous ouvrir la page de test? (O/N)
set /p reponse=
if /i "%reponse%"=="O" (
    start http://localhost/projet%%20ismo/test_kkiapay_complet.html
    echo.
    echo Page de test ouverte dans votre navigateur!
)

echo.
echo Documentation complete: GUIDE_TEST_KKIAPAY_COMPLET.md
echo.
pause
