@echo off
echo ============================================
echo   IMPORT COMPLET vers RAILWAY MySQL
echo ============================================
echo.

REM Credentials extraits de MYSQL_PUBLIC_URL
set MYSQL_HOST=gondola.proxy.rlwy.net
set MYSQL_PORT=24653
set MYSQL_USER=root
set MYSQL_PASSWORD=lLNQgXguqytlIMQoXZPjdJJsmyJkheUM
set MYSQL_DB=railway

echo Host: %MYSQL_HOST%
echo Port: %MYSQL_PORT%
echo Database: %MYSQL_DB%
echo Fichier: gamezone.sql
echo.
echo ============================================
echo   IMPORT EN COURS... (peut prendre 1-2 min)
echo ============================================
echo.

REM Chemin vers mysql.exe de XAMPP
set MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe

REM Vérifier que le fichier SQL existe
if not exist "gamezone.sql" (
    echo ERREUR: gamezone.sql introuvable!
    echo Verifie que tu es dans le bon dossier.
    pause
    exit /b 1
)

REM Import du fichier SQL complet
"%MYSQL_BIN%" -h %MYSQL_HOST% -P %MYSQL_PORT% -u %MYSQL_USER% -p%MYSQL_PASSWORD% %MYSQL_DB% < "gamezone.sql" 2>&1

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo   ✓ SUCCESS! Base importee avec succes!
    echo ============================================
    echo.
    echo TOUTES les tables et donnees ont ete importees.
    echo.
    echo Prochaines etapes:
    echo 1. Va sur: https://gamezoneismo.vercel.app/admin/dashboard
    echo 2. Connecte-toi avec tes identifiants
    echo 3. Verifie que tout fonctionne!
    echo.
) else (
    echo.
    echo ============================================
    echo   ✗ ERREUR lors de l'import!
    echo ============================================
    echo.
    echo Verifie:
    echo - Que XAMPP MySQL est installe
    echo - Que le fichier gamezone.sql est complet
    echo - Que ta connexion internet fonctionne
)

echo.
pause
