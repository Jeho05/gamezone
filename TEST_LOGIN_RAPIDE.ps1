# Script de test rapide pour le login admin
# Execute ce script pour diagnostiquer et résoudre le problème de connexion

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  TEST RAPIDE - Connexion Admin" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# 1. Vérifier XAMPP/Apache
Write-Host "1. Verification Apache..." -ForegroundColor Yellow
$apache = Get-Process -Name httpd -ErrorAction SilentlyContinue
if ($apache) {
    Write-Host "   ✓ Apache est en cours d'execution" -ForegroundColor Green
} else {
    Write-Host "   ✗ Apache n'est PAS en cours d'execution" -ForegroundColor Red
    Write-Host "   → Demarrez XAMPP et Apache!" -ForegroundColor Red
    pause
    exit
}

# 2. Vérifier le port 80
Write-Host ""
Write-Host "2. Verification port 80..." -ForegroundColor Yellow
$port80 = Test-NetConnection -ComputerName localhost -Port 80 -InformationLevel Quiet
if ($port80) {
    Write-Host "   ✓ Port 80 accessible" -ForegroundColor Green
} else {
    Write-Host "   ✗ Port 80 inaccessible" -ForegroundColor Red
}

# 3. Tester l'API directement
Write-Host ""
Write-Host "3. Test de l'API backend..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost/projet%20ismo/api/auth/check.php" -UseBasicParsing -ErrorAction Stop
    Write-Host "   ✓ Backend accessible (Status: $($response.StatusCode))" -ForegroundColor Green
} catch {
    if ($_.Exception.Response.StatusCode -eq 401) {
        Write-Host "   ✓ Backend OK (401 est normal quand non connecte)" -ForegroundColor Green
    } else {
        Write-Host "   ✗ Erreur backend: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# 4. Vérifier le serveur React
Write-Host ""
Write-Host "4. Verification serveur React..." -ForegroundColor Yellow
$react = Get-Process -Name node -ErrorAction SilentlyContinue | Where-Object { $_.ProcessName -eq "node" }
if ($react) {
    Write-Host "   ✓ Serveur React en cours d'execution" -ForegroundColor Green
    try {
        $reactTest = Invoke-WebRequest -Uri "http://localhost:4000" -UseBasicParsing -TimeoutSec 2 -ErrorAction Stop
        Write-Host "   ✓ React accessible sur http://localhost:4000" -ForegroundColor Green
    } catch {
        Write-Host "   ⚠ React pourrait ne pas etre pret (normal au demarrage)" -ForegroundColor Yellow
    }
} else {
    Write-Host "   ✗ Serveur React non detecte" -ForegroundColor Red
    Write-Host "   → Lancez 'npm run dev' dans le dossier web" -ForegroundColor Red
}

# 5. Test de connexion
Write-Host ""
Write-Host "5. Test de connexion admin..." -ForegroundColor Yellow
try {
    $body = @{
        email = "admin@gamezone.fr"
        password = "demo123"
    } | ConvertTo-Json

    $loginResponse = Invoke-WebRequest -Uri "http://localhost/projet%20ismo/api/auth/login.php" `
        -Method POST `
        -ContentType "application/json" `
        -Body $body `
        -UseBasicParsing `
        -SessionVariable session `
        -ErrorAction Stop

    $data = $loginResponse.Content | ConvertFrom-Json
    Write-Host "   ✓ Connexion reussie!" -ForegroundColor Green
    Write-Host "   → Utilisateur: $($data.user.username)" -ForegroundColor Green
    Write-Host "   → Role: $($data.user.role)" -ForegroundColor Green
} catch {
    Write-Host "   ✗ Echec de connexion" -ForegroundColor Red
    Write-Host "   → Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

# 6. Recommandations
Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  RECOMMANDATIONS" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

Write-Host ""
Write-Host "Si la connexion DIRECTE fonctionne mais pas via l'app:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Ouvrez le fichier:" -ForegroundColor White
Write-Host "   createxyz-project\_\apps\web\src\utils\apiBase.js" -ForegroundColor Cyan
Write-Host ""
Write-Host "2. Ligne 8, changez:" -ForegroundColor White
Write-Host "   API_BASE = '/php-api';" -ForegroundColor Red
Write-Host "   En:" -ForegroundColor White
Write-Host "   API_BASE = 'http://localhost/projet%20ismo/api';" -ForegroundColor Green
Write-Host ""
Write-Host "3. Redemarrez le serveur React (Ctrl+C puis npm run dev)" -ForegroundColor White
Write-Host ""

Write-Host "Ou testez avec l'outil de diagnostic:" -ForegroundColor Yellow
Write-Host "   http://localhost/projet%20ismo/TEST_API_CONNECTION.html" -ForegroundColor Cyan
Write-Host ""

Write-Host "Pour plus d'infos, consultez:" -ForegroundColor Yellow
Write-Host "   FIX_NETWORK_ERROR_LOGIN.md" -ForegroundColor Cyan
Write-Host ""

pause
