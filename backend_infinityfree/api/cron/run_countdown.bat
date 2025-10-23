@echo off
REM Script de décompte automatique des sessions
REM À exécuter via le Planificateur de Tâches Windows toutes les 1-2 minutes

C:\xampp\mysql\bin\mysql.exe -u root gamezone -e "CALL countdown_active_sessions();"

if %ERRORLEVEL% EQU 0 (
    echo [OK] Decompte execute avec succes - %date% %time%
) else (
    echo [ERREUR] Echec du decompte - %date% %time%
)
