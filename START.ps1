# Script de demarrage complet GameZone avec verifications

$ErrorActionPreference = "SilentlyContinue"

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "       GameZone - Demarrage Complet" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Fonction pour afficher un statut
function Show-Status {
    param(
        [string]$Message,
        [string]$Status
    )
    $padding = 45 - $Message.Length
    $dots = "." * $padding
    Write-Host "$Message$dots" -NoNewline
    if ($Status -eq "OK") {
        Write-Host " [OK]" -ForegroundColor Green
        return $true
    } elseif ($Status -eq "WARN") {
        Write-Host " [!]" -ForegroundColor Yellow
        return $true
    } else {
        Write-Host " [KO]" -ForegroundColor Red
        return $false
    }
}

$allOk = $true

# 1. Verifier Apache
$apache = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if (-not (Show-Status "Apache" $(if ($apache) { "OK" } else { "KO" }))) {
    Write-Host "   > Demarrez Apache depuis XAMPP Control Panel" -ForegroundColor Yellow
    $allOk = $false
}

# 2. Verifier MySQL
$mysql = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not (Show-Status "MySQL" $(if ($mysql) { "OK" } else { "KO" }))) {
    Write-Host "   > Demarrez MySQL depuis XAMPP Control Panel" -ForegroundColor Yellow
    $allOk = $false
}

# 3. Verifier Node.js
try {
    $nodeVersion = node --version 2>$null
    Show-Status "Node.js $nodeVersion" "OK" | Out-Null
} catch {
    Show-Status "Node.js" "KO" | Out-Null
    Write-Host "   > Installez Node.js depuis https://nodejs.org/" -ForegroundColor Yellow
    $allOk = $false
}

if (-not $allOk) {
    Write-Host ""
    Write-Host "ATTENTION: Corrigez les problemes ci-dessus avant de continuer" -ForegroundColor Red
    Write-Host ""
    pause
    exit 1
}

# 4. Verifier l'API
Write-Host ""
Write-Host "> Test de l'API..." -ForegroundColor Cyan
try {
    $response = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/test.php" -Method Get -TimeoutSec 5 -ErrorAction Stop
    Show-Status "API Backend accessible" "OK" | Out-Null
} catch {
    Show-Status "API Backend accessible" "KO" | Out-Null
    Write-Host "   ATTENTION: L'API ne repond pas. Verifiez Apache." -ForegroundColor Yellow
    Write-Host ""
    pause
    exit 1
}

# 5. Verifier/Installer la base de donnees
Write-Host ""
Write-Host "> Verification de la base de donnees..." -ForegroundColor Cyan
try {
    $events = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/events/index.php" -Method Get -TimeoutSec 5 -ErrorAction Stop
    if ($events.items) {
        Show-Status "Base de donnees initialisee" "OK" | Out-Null
    } else {
        throw "No events"
    }
} catch {
    Show-Status "Base de donnees initialisee" "WARN" | Out-Null
    Write-Host "   > Installation de la base de donnees..." -ForegroundColor Yellow
    try {
        $install = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/install.php" -Method Get -TimeoutSec 10
        Write-Host "   [OK] Base de donnees installee avec succes" -ForegroundColor Green
    } catch {
        Write-Host "   [ERREUR] Erreur lors de l'installation de la base" -ForegroundColor Red
        Write-Host "   > Ouvrez manuellement: http://localhost/projet%20ismo/api/install.php" -ForegroundColor Yellow
        pause
        exit 1
    }
}

# 6. Verifier le frontend
$frontPath = "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
Write-Host ""
Write-Host "> Verification du frontend..." -ForegroundColor Cyan

if (-not (Test-Path "$frontPath\package.json")) {
    Show-Status "Frontend trouve" "KO" | Out-Null
    Write-Host "   [ERREUR] Le dossier frontend est introuvable" -ForegroundColor Red
    pause
    exit 1
}
Show-Status "Frontend trouve" "OK" | Out-Null

if (-not (Test-Path "$frontPath\node_modules")) {
    Show-Status "Dependances installees" "WARN" | Out-Null
    Write-Host ""
    Write-Host "> Installation des dependances npm..." -ForegroundColor Yellow
    Write-Host "   (Cela peut prendre 2-3 minutes)" -ForegroundColor Gray
    Set-Location $frontPath
    npm install --silent
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   [OK] Dependances installees avec succes" -ForegroundColor Green
    } else {
        Write-Host "   [ERREUR] Erreur lors de l'installation" -ForegroundColor Red
        pause
        exit 1
    }
} else {
    Show-Status "Dependances installees" "OK" | Out-Null
}

# 7. Test rapide de l'API d'inscription
Write-Host ""
Write-Host "> Test de l'API d'inscription..." -ForegroundColor Cyan
$testEmail = "quicktest$(Get-Random -Maximum 9999)@test.local"
$testBody = @{
    username = "QuickTest"
    email = $testEmail
    password = "test1234"
} | ConvertTo-Json

try {
    $testRegister = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/auth/register.php" -Method Post -ContentType "application/json" -Body $testBody -TimeoutSec 5
    if ($testRegister.user) {
        Show-Status "Inscription API fonctionnelle" "OK" | Out-Null
    } else {
        throw "Invalid response"
    }
} catch {
    Show-Status "Inscription API fonctionnelle" "KO" | Out-Null
    Write-Host "   ATTENTION: L'API d'inscription ne fonctionne pas correctement" -ForegroundColor Yellow
    Write-Host "   Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

# Resume
Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "          [OK] Tout est pret!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""

Write-Host "Backend API:" -ForegroundColor Cyan
Write-Host "  http://localhost/projet%20ismo/api" -ForegroundColor White
Write-Host ""
Write-Host "Frontend Web:" -ForegroundColor Cyan
Write-Host "  http://localhost:4000" -ForegroundColor White
Write-Host "  (va demarrer dans quelques secondes...)" -ForegroundColor Gray
Write-Host ""
Write-Host "Compte Admin:" -ForegroundColor Cyan
Write-Host "  Email:    admin@gamezone.fr" -ForegroundColor White
Write-Host "  Password: demo123" -ForegroundColor White
Write-Host ""

Write-Host "================================================" -ForegroundColor Gray
Write-Host ""
Write-Host "> Demarrage du serveur de developpement..." -ForegroundColor Cyan
Write-Host "   Appuyez sur Ctrl+C pour arreter le serveur" -ForegroundColor Yellow
Write-Host ""
Start-Sleep -Seconds 2

Set-Location $frontPath
npm run dev
