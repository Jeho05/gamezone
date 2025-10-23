@echo off
echo ========================================
echo   VERIFICATION CLE API KKIAPAY
echo ========================================
echo.
echo Cle testee: b2f64170af2111f093307bbda24d6bac
echo.
echo ========================================
echo.
echo INSTRUCTIONS:
echo -------------
echo.
echo 1. Je vais ouvrir la page de test debug
echo    dans votre navigateur
echo.
echo 2. Testez les 6 widgets/boutons:
echo    - Test 1: Widget avec sandbox="true"
echo    - Test 2: Widget sans sandbox (auto)
echo    - Test 3: Widget avec sandbox="false"
echo    - Test 4: API JS avec sandbox=true
echo    - Test 5: API JS avec sandbox=false
echo    - Test 6: API JS sans sandbox
echo.
echo 3. NOTEZ quel test NE montre PAS l'erreur
echo    "Votre cle d'api est incorrecte"
echo.
echo 4. Le test qui fonctionne sera utilise
echo    dans shop.html
echo.
echo ========================================
echo.
echo NUMEROS DE TEST (si mode sandbox):
echo ----------------------------------
echo Succes: 97000000 (ou 97xxxxxxxx)
echo Echec:  96000000 (ou 96xxxxxxxx)
echo OTP:    123456
echo.
echo ========================================
echo.
pause

echo.
echo Ouverture de la page de test...
start http://localhost/projet%%20ismo/test_kkiapay_debug.html
echo.
echo Page ouverte!
echo.
echo Testez maintenant et notez le resultat.
echo.
pause
