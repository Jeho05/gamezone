@echo off
echo ================================================================
echo     LANCEMENT DES TESTS COMPLETS - SYSTEME GAMEZONE
echo ================================================================
echo.

echo [1/3] Test Base de donnees...
c:\xampp\php\php.exe test_complet_systeme.php
echo.
echo [OK] Test 1/3 termine
echo.

timeout /t 2 >nul

echo [2/3] Test API Endpoints...
c:\xampp\php\php.exe test_api_endpoints.php
echo.
echo [OK] Test 2/3 termine
echo.

timeout /t 2 >nul

echo [3/3] Validation finale...
c:\xampp\php\php.exe VALIDATION_FINALE.php
echo.
echo [OK] Test 3/3 termine
echo.

echo ================================================================
echo              TOUS LES TESTS SONT TERMINES
echo ================================================================
echo.
echo Consultez RAPPORT_AUDIT_FINAL.md pour le rapport detaille
echo Consultez RESUME_AUDIT.txt pour le resume visuel
echo.

pause
