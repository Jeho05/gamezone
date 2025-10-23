# Script temporaire de build
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  BUILD PRODUCTION - GAMEZONE" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Etape 1: Copier les fichiers de configuration
Write-Host "Etape 1: Creation fichiers de configuration..." -ForegroundColor Yellow

$envProdSrc = "createxyz-project\_\apps\web\.env.production.example"
$envProdDst = "createxyz-project\_\apps\web\.env.production"

if (Test-Path $envProdSrc) {
    Copy-Item $envProdSrc $envProdDst -Force
    Write-Host "[OK] .env.production cree" -ForegroundColor Green
} else {
    Write-Host "[ERREUR] .env.production.example introuvable" -ForegroundColor Red
}

$envApiSrc = "api\.env.example"
$envApiDst = "api\.env"

if (Test-Path $envApiSrc) {
    Copy-Item $envApiSrc $envApiDst -Force
    Write-Host "[OK] api/.env cree" -ForegroundColor Green
} else {
    Write-Host "[ERREUR] api/.env.example introuvable" -ForegroundColor Red
}

Write-Host ""

# Etape 2: Build du frontend
Write-Host "Etape 2: Build du frontend React..." -ForegroundColor Yellow
$frontendPath = "createxyz-project\_\apps\web"

if (Test-Path $frontendPath) {
    Set-Location $frontendPath
    
    # Verifier node_modules
    if (-Not (Test-Path "node_modules")) {
        Write-Host "Installation des dependances..." -ForegroundColor Yellow
        npm install
    }
    
    # Build
    Write-Host "Compilation en cours (peut prendre 5-10 minutes)..." -ForegroundColor Yellow
    $env:NODE_ENV = "production"
    npm run build
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "[OK] Build React termine!" -ForegroundColor Green
    } else {
        Write-Host "[ERREUR] Erreur lors du build React" -ForegroundColor Red
        Set-Location ..\..\..
        exit 1
    }
    
    Set-Location ..\..\..
} else {
    Write-Host "[ERREUR] Dossier frontend introuvable" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[OK] Build termine avec succes!" -ForegroundColor Green
