@echo off
echo ============================================
echo   IMPORT COMPLET vers RAILWAY MySQL
echo ============================================
echo.

REM Credentials
set MYSQL_HOST=gondola.proxy.rlwy.net
set MYSQL_PORT=24653
set MYSQL_USER=root
set MYSQL_PASSWORD=lLNQgXguqytlIMQoXZPjdJJsmyJkheUM
set MYSQL_DB=railway

echo Host: %MYSQL_HOST%
echo Port: %MYSQL_PORT%
echo Database: %MYSQL_DB%
echo.
echo Import en cours...
echo.

REM Chemin vers mysql.exe de XAMPP
set MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe

REM Import avec option --default-auth pour compatibilité
"%MYSQL_BIN%" --default-auth=mysql_native_password -h %MYSQL_HOST% -P %MYSQL_PORT% -u %MYSQL_USER% -p%MYSQL_PASSWORD% %MYSQL_DB% < "gamezone.sql" 2>&1

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo   SUCCESS! Base importee!
    echo ============================================
    echo.
    echo Va sur: https://gamezoneismo.vercel.app/admin/dashboard
) else (
    echo.
    echo ============================================
    echo   ERREUR - Essai avec mysqldump...
    echo ============================================
    echo.
    echo Le client MySQL de XAMPP est trop ancien.
    echo.
    echo SOLUTION ALTERNATIVE:
    echo 1. Telecharge MySQL Workbench: https://dev.mysql.com/downloads/workbench/
    echo 2. Ou utilise phpMyAdmin distant si disponible
    echo 3. Ou je peux creer un script PHP qui importe via PDO
    echo.
    echo Veux-tu que je cree un script PHP d'import? (O/N)
)

echo.
pause
