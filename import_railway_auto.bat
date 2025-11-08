@echo off
echo ============================================
echo   IMPORT AUTOMATIQUE vers RAILWAY
echo ============================================
echo.

REM Credentials Railway
set MYSQL_HOST=mysql.railway.internal
set MYSQL_PORT=3306
set MYSQL_USER=root
set MYSQL_PASSWORD=lLNQgXguqytlIMQoXZPjdJJsmyJkheUM
set MYSQL_DB=railway

echo Host: %MYSQL_HOST%
echo Port: %MYSQL_PORT%
echo Database: %MYSQL_DB%
echo User: %MYSQL_USER%
echo.
echo Demarrage de l'import...
echo.

REM Chemin vers mysql.exe de XAMPP
set MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe

REM Import du fichier SQL
"%MYSQL_BIN%" -h %MYSQL_HOST% -P %MYSQL_PORT% -u %MYSQL_USER% -p%MYSQL_PASSWORD% %MYSQL_DB% < "gamezone.sql" 2>&1

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo   SUCCESS! Base importee avec succes!
    echo ============================================
    echo.
    echo Verifie maintenant: https://gamezoneismo.vercel.app/admin/dashboard
) else (
    echo.
    echo ============================================
    echo   ERREUR lors de l'import!
    echo ============================================
    echo.
    echo Possible raison: mysql.railway.internal n'est pas accessible depuis l'exterieur.
    echo Il faut utiliser l'adresse publique a la place.
    echo.
    echo Va sur Railway Dashboard et cherche MYSQLDATABASE_URL ou l'adresse publique.
)

echo.
pause
