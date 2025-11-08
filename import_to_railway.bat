@echo off
echo ============================================
echo   IMPORT BASE DE DONNEES vers RAILWAY
echo ============================================
echo.

REM Demander les infos Railway
set /p MYSQL_HOST="Entre MYSQLHOST (ex: containers-us-west-xxx.railway.app): "
set /p MYSQL_PORT="Entre MYSQLPORT (ex: 6379): "
set /p MYSQL_PASSWORD="Entre MYSQLPASSWORD: "

echo.
echo ============================================
echo   IMPORT EN COURS...
echo ============================================
echo Host: %MYSQL_HOST%
echo Port: %MYSQL_PORT%
echo Database: railway
echo.

REM Chemin vers mysql.exe de XAMPP
set MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe

REM Import du fichier SQL
"%MYSQL_BIN%" -h %MYSQL_HOST% -P %MYSQL_PORT% -u root -p%MYSQL_PASSWORD% railway < "gamezone.sql"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo   SUCCESS! Base de donnees importee!
    echo ============================================
) else (
    echo.
    echo ============================================
    echo   ERREUR lors de l'import!
    echo ============================================
)

echo.
pause
