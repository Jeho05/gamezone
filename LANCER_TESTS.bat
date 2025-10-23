@echo off
chcp 65001 > nul
echo =====================================
echo   TEST SYSTEME DE RESERVATIONS
echo =====================================
echo.

echo [1/2] Diagnostic du systeme...
C:\xampp\php\php.exe api/diagnostic_reservations.php
echo.

echo [2/2] Tests complets...
C:\xampp\php\php.exe test_reservations_rewards.php
echo.

echo =====================================
echo        TESTS TERMINES
echo =====================================
echo.
echo Documentation complete : SYSTEME_RESERVATIONS_COMPLET.md
echo Interface joueur       : http://localhost:3000/player/shop
echo Mes reservations       : http://localhost:3000/player/my-reservations
echo.
pause
