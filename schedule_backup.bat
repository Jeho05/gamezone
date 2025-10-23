@echo off
REM Script pour configurer le backup automatique quotidien
REM Execute a 2h du matin tous les jours

echo ================================================================
echo     CONFIGURATION BACKUP AUTOMATIQUE - GAMEZONE
echo ================================================================
echo.

echo Configuration du backup quotidien a 2h00...
echo.

REM Creer la tache planifiee Windows
schtasks /create /tn "GameZone_Backup_Daily" /tr "c:\xampp\php\php.exe %~dp0backup_database.php" /sc daily /st 02:00 /f

if errorlevel 1 (
    echo [ERREUR] Echec de la configuration
    echo Vous devez executer ce script en tant qu'administrateur
    pause
    exit /b 1
)

echo [OK] Backup automatique configure avec succes!
echo.
echo Le backup sera execute tous les jours a 2h00 du matin
echo.
echo Pour tester le backup manuellement:
echo   c:\xampp\php\php.exe backup_database.php
echo.
echo Pour voir les taches planifiees:
echo   schtasks /query /tn "GameZone_Backup_Daily"
echo.
echo Pour supprimer la tache:
echo   schtasks /delete /tn "GameZone_Backup_Daily" /f
echo.

pause
