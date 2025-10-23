# Script de vérification rapide de l'installation

Write-Host "=== GameZone - Vérification de l'installation ===" -ForegroundColor Cyan
Write-Host ""

$allOk = $true

# 1. Vérifier Apache
Write-Host "1. Apache..." -NoNewline
$apache = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if ($apache) {
    Write-Host " [OK]" -ForegroundColor Green
} else {
    Write-Host " [KO] Non démarré" -ForegroundColor Red
    $allOk = $false
}

# 2. Vérifier MySQL
Write-Host "2. MySQL..." -NoNewline
$mysql = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if ($mysql) {
    Write-Host " [OK]" -ForegroundColor Green
} else {
    Write-Host " [KO] Non démarré" -ForegroundColor Red
    $allOk = $false
}

# 3. Vérifier Node.js
Write-Host "3. Node.js..." -NoNewline
try {
    $nodeVersion = node --version 2>$null
    Write-Host " [OK] $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host " [KO] Non installé" -ForegroundColor Red
    $allOk = $false
}

# 4. Vérifier l'API backend
Write-Host "4. API Backend..." -NoNewline
try {
    $response = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/test.php" -Method Get -TimeoutSec 5
    if ($response.method) {
        Write-Host " [OK]" -ForegroundColor Green
    } else {
        Write-Host " [KO] Réponse invalide" -ForegroundColor Red
        $allOk = $false
    }
} catch {
    Write-Host " [KO] Injoignable" -ForegroundColor Red
    $allOk = $false
}

# 5. Vérifier la base de données
Write-Host "5. Base de données..." -NoNewline
try {
    $response = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/events/index.php" -Method Get -TimeoutSec 5
    if ($response.items) {
        Write-Host " [OK]" -ForegroundColor Green
    } else {
        Write-Host " [KO] Pas d'événements" -ForegroundColor Yellow
        Write-Host "   -> Exécutez http://localhost/projet%20ismo/api/install.php" -ForegroundColor Yellow
    }
} catch {
    Write-Host " [KO] Erreur" -ForegroundColor Red
    Write-Host "   -> Exécutez http://localhost/projet%20ismo/api/install.php" -ForegroundColor Yellow
    $allOk = $false
}

# 6. Vérifier le frontend
Write-Host "6. Frontend..." -NoNewline
$frontPath = "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
if (Test-Path "$frontPath\package.json") {
    if (Test-Path "$frontPath\node_modules") {
        Write-Host " [OK]" -ForegroundColor Green
    } else {
        Write-Host " [KO] node_modules manquant" -ForegroundColor Yellow
        Write-Host "   -> Exécutez: cd '$frontPath' && npm install" -ForegroundColor Yellow
    }
} else {
    Write-Host " [KO] package.json introuvable" -ForegroundColor Red
    $allOk = $false
}

# 7. Vérifier si le serveur dev tourne
Write-Host "7. Serveur dev..." -NoNewline
try {
    $response = Invoke-WebRequest -Uri "http://localhost:4000" -Method Get -TimeoutSec 2 -UseBasicParsing
    Write-Host " [OK] Port 4000 actif" -ForegroundColor Green
} catch {
    Write-Host " [KO] Non démarré" -ForegroundColor Yellow
    Write-Host "   -> Exécutez: cd '$frontPath' && npm run dev" -ForegroundColor Yellow
}

Write-Host ""
if ($allOk) {
    Write-Host "=== Tout est OK! ===" -ForegroundColor Green
    Write-Host ""
    Write-Host "Frontend: http://localhost:4000" -ForegroundColor Cyan
    Write-Host "Backend:  http://localhost/projet%20ismo/api" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Compte admin: admin@gamezone.fr / demo123" -ForegroundColor Yellow
} else {
    Write-Host "=== Des problèmes ont été détectés ===" -ForegroundColor Red
    Write-Host "Consultez README_DEMARRAGE.md pour plus d'informations" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Appuyez sur Entrée pour quitter..." -ForegroundColor Gray
$null = Read-Host
