@echo off
echo ============================================
echo   REPARATION RAPIDE DES SESSIONS
echo ============================================
echo.

echo [1/3] Execution du decompte automatique...
C:\xampp\mysql\bin\mysql.exe -u root gamezone -e "CALL countdown_active_sessions();"
echo [OK] Decompte execute
echo.

echo [2/3] Synchronisation purchases et sessions...
C:\xampp\mysql\bin\mysql.exe -u root gamezone -e "CALL sync_purchase_session_status();"
echo [OK] Synchronisation effectuee
echo.

echo [3/3] Verification des incoherences...
C:\xampp\mysql\bin\mysql.exe -u root gamezone -e "SELECT COUNT(*) as incoherences FROM purchase_session_overview WHERE sync_status = 'MISMATCH';"
echo.

echo ============================================
echo   REPARATION TERMINEE
echo ============================================
echo.
echo Si le probleme persiste:
echo 1. Verifiez que la facture est activee
echo 2. Verifiez que la session existe
echo 3. Rechargez la page dans le navigateur
echo.

pause
