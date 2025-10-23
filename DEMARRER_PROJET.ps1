# Script de demarrage GameZone avec verification complete

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "   GameZone - Demarrage avec verification" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verifier Apache
Write-Host "[1/5] Verification Apache..." -ForegroundColor Yellow
$apache = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if ($apache) {
    Write-Host "  [OK] Apache est demarre" -ForegroundColor Green
} else {
    Write-Host "  [ERREUR] Apache n'est pas demarre!" -ForegroundColor Red
    Write-Host "  > Ouvrez XAMPP Control Panel et demarrez Apache" -ForegroundColor Yellow
    pause
    exit 1
}

# 2. Verifier MySQL
Write-Host "[2/5] Verification MySQL..." -ForegroundColor Yellow
$mysql = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if ($mysql) {
    Write-Host "  [OK] MySQL est demarre" -ForegroundColor Green
} else {
    Write-Host "  [ERREUR] MySQL n'est pas demarre!" -ForegroundColor Red
    Write-Host "  > Ouvrez XAMPP Control Panel et demarrez MySQL" -ForegroundColor Yellow
    pause
    exit 1
}

# 3. Tester l'API PHP
Write-Host "[3/5] Test de l'API PHP..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/test.php" -Method Get -TimeoutSec 5 -ErrorAction Stop
    Write-Host "  [OK] API PHP accessible" -ForegroundColor Green
} catch {
    Write-Host "  [ERREUR] API PHP inaccessible!" -ForegroundColor Red
    Write-Host "  > Verifiez qu'Apache sert bien le dossier 'projet ismo'" -ForegroundColor Yellow
    pause
    exit 1
}

# 4. Verifier la base de donnees
Write-Host "[4/5] Verification base de donnees..." -ForegroundColor Yellow
try {
    $events = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/events/index.php" -Method Get -TimeoutSec 5 -ErrorAction Stop
    Write-Host "  [OK] Base de donnees initialisee" -ForegroundColor Green
} catch {
    Write-Host "  [ATTENTION] Base de donnees non initialisee" -ForegroundColor Yellow
    Write-Host "  > Installation automatique..." -ForegroundColor Yellow
    try {
        $install = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/install.php" -Method Get -TimeoutSec 10
        Write-Host "  [OK] Base de donnees installee" -ForegroundColor Green
    } catch {
        Write-Host "  [ERREUR] Impossible d'installer la base" -ForegroundColor Red
        Write-Host "  > Ouvrez: http://localhost/projet%20ismo/api/install.php" -ForegroundColor Yellow
        pause
        exit 1
    }
}

# 5. Verifier Node.js et dependances
Write-Host "[5/5] Verification frontend..." -ForegroundColor Yellow
$frontPath = "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

if (-not (Test-Path "$frontPath\node_modules")) {
    Write-Host "  [ATTENTION] Dependances npm manquantes" -ForegroundColor Yellow
    Write-Host "  > Installation des dependances (2-3 minutes)..." -ForegroundColor Yellow
    Set-Location $frontPath
    npm install --silent
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  [ERREUR] Installation npm echouee" -ForegroundColor Red
        pause
        exit 1
    }
    Write-Host "  [OK] Dependances installees" -ForegroundColor Green
} else {
    Write-Host "  [OK] Dependances npm presentes" -ForegroundColor Green
}

# Tout est OK
Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "   [OK] Tous les services sont prets!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""

Write-Host "Configuration:" -ForegroundColor Cyan
Write-Host "  Backend PHP:  http://localhost/projet%20ismo/api" -ForegroundColor White
Write-Host "  Frontend:     http://localhost:4000" -ForegroundColor White
Write-Host "  Proxy Vite:   /php-api -> Backend PHP (pas de CORS/CSP)" -ForegroundColor White
Write-Host "  Routes Hono:  /api (pas de conflit)" -ForegroundColor White
Write-Host ""
Write-Host "Compte Admin:" -ForegroundColor Cyan
Write-Host "  Email:    admin@gmail.com" -ForegroundColor White
Write-Host "  Password: demo123" -ForegroundColor White
Write-Host ""
Write-Host "================================================" -ForegroundColor Gray
Write-Host ""
Write-Host "Demarrage du serveur de developpement..." -ForegroundColor Cyan
Write-Host "Appuyez sur Ctrl+C pour arreter" -ForegroundColor Yellow
Write-Host ""
Start-Sleep -Seconds 2

Set-Location $frontPath
npm run dev
