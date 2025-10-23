@echo off
echo ========================================
echo   TEST COMPLET DU SCENARIO
echo ========================================
echo.
echo Ouverture du test dans le navigateur...
echo.

start http://localhost/projet%%20ismo/test_scenario_complet.php

echo.
echo ========================================
echo   Le test s'est ouvert dans votre
echo   navigateur par defaut
echo ========================================
echo.
echo Ce test verifie:
echo   1. Echange de points
echo   2. Demarrage de session
echo   3. Temps de jeu
echo   4. Fin de session
echo   5. Creditation points bonus
echo.
pause
