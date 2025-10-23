@echo off
REM Script pour lancer le décompte automatique en boucle
REM Usage: start_auto_countdown.bat

echo ========================================
echo GAMEZONE - DECOMPTE AUTOMATIQUE
echo ========================================
echo.
echo Ce script va executer le decompte toutes les 60 secondes
echo Appuyez sur CTRL+C pour arreter
echo.
pause

:loop
echo.
echo [%date% %time%] Execution du decompte...
C:\xampp\php\php.exe "C:\xampp\htdocs\projet ismo\api\cron\countdown_sessions.php"

echo Attente 60 secondes...
timeout /t 60 /nobreak >nul
goto loop
