# Script de Build Production pour InfinityFree
# Exécutez ce script avant de déployer

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  BUILD PRODUCTION - GAMEZONE" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$frontendPath = "createxyz-project\_\apps\web"
$outputPath = "production_build"

# Vérifier que le frontend existe
if (-Not (Test-Path $frontendPath)) {
    Write-Host "❌ Erreur: Dossier frontend introuvable!" -ForegroundColor Red
    exit 1
}

# Étape 1: Vérifier .env.production
Write-Host "📋 Étape 1: Configuration environnement..." -ForegroundColor Yellow

$envProdExample = Join-Path $frontendPath ".env.production.example"
$envProd = Join-Path $frontendPath ".env.production"

if (-Not (Test-Path $envProd)) {
    Write-Host "⚠️  .env.production n'existe pas. Copie depuis .example..." -ForegroundColor Yellow
    Copy-Item $envProdExample $envProd
    Write-Host "⚠️  IMPORTANT: Éditez .env.production avec votre domaine InfinityFree!" -ForegroundColor Red
    Write-Host "   Fichier: $envProd" -ForegroundColor Yellow
    notepad $envProd
    $response = Read-Host "Avez-vous configuré .env.production? (o/n)"
    if ($response -ne "o") {
        Write-Host "❌ Configuration annulée." -ForegroundColor Red
        exit 1
    }
}

Write-Host "✅ Configuration OK" -ForegroundColor Green
Write-Host ""

# Étape 2: Build du frontend
Write-Host "🔨 Étape 2: Build du frontend React..." -ForegroundColor Yellow
Set-Location $frontendPath

# Vérifier node_modules
if (-Not (Test-Path "node_modules")) {
    Write-Host "📦 Installation des dépendances..." -ForegroundColor Yellow
    npm install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Erreur lors de l'installation des dépendances!" -ForegroundColor Red
        Set-Location ..\..\..\..
        exit 1
    }
}

# Build production
Write-Host "⚙️  Compilation en cours (peut prendre 5-10 minutes)..." -ForegroundColor Yellow
$env:NODE_ENV = "production"
npm run build

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erreur lors du build!" -ForegroundColor Red
    Set-Location ..\..\..\..
    exit 1
}

Write-Host "✅ Build terminé!" -ForegroundColor Green
Set-Location ..\..\..\..
Write-Host ""

# Étape 3: Préparer le dossier de production
Write-Host "📦 Étape 3: Préparation du package de déploiement..." -ForegroundColor Yellow

# Supprimer ancien dossier si existe
if (Test-Path $outputPath) {
    Remove-Item -Recurse -Force $outputPath
}

# Créer structure
New-Item -ItemType Directory -Path $outputPath -Force | Out-Null
New-Item -ItemType Directory -Path "$outputPath\api" -Force | Out-Null
New-Item -ItemType Directory -Path "$outputPath\uploads" -Force | Out-Null
New-Item -ItemType Directory -Path "$outputPath\images" -Force | Out-Null
New-Item -ItemType Directory -Path "$outputPath\uploads\avatars" -Force | Out-Null
New-Item -ItemType Directory -Path "$outputPath\uploads\games" -Force | Out-Null

# Copier le build frontend
Write-Host "   📁 Copie du frontend..." -ForegroundColor Gray
$buildPath = Join-Path $frontendPath "build\client"
if (Test-Path $buildPath) {
    Copy-Item -Path "$buildPath\*" -Destination $outputPath -Recurse -Force
} else {
    Write-Host "⚠️  Build client non trouvé, tentative avec dist..." -ForegroundColor Yellow
    $buildPath = Join-Path $frontendPath "dist"
    if (Test-Path $buildPath) {
        Copy-Item -Path "$buildPath\*" -Destination $outputPath -Recurse -Force
    } else {
        Write-Host "❌ Dossier build introuvable!" -ForegroundColor Red
        exit 1
    }
}

# Copier le backend
Write-Host "   📁 Copie du backend PHP..." -ForegroundColor Gray
Copy-Item -Path "api\*" -Destination "$outputPath\api" -Recurse -Force -Exclude @("*.log", ".env")

# Copier les images de base
Write-Host "   📁 Copie des images..." -ForegroundColor Gray
if (Test-Path "images") {
    Copy-Item -Path "images\*" -Destination "$outputPath\images" -Recurse -Force
}

# Copier uploads necessaires
Copy-Item -Path "uploads\.htaccess" -Destination "$outputPath\uploads\" -Force -ErrorAction SilentlyContinue
Copy-Item -Path "uploads\games\README.txt" -Destination "$outputPath\uploads\games\" -Force -ErrorAction SilentlyContinue

# Créer .htaccess racine
Write-Host "   📁 Création .htaccess..." -ForegroundColor Gray
$htaccessContent = @"
# GameZone - Configuration Apache
RewriteEngine On

# API routes
RewriteRule ^api/(.*)$ api/$1 [L]

# Frontend React Router
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/api/
RewriteRule ^(.*)$ index.html [L]

# Security
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
"@

Set-Content -Path "$outputPath\.htaccess" -Value $htaccessContent

Write-Host "✅ Package prêt!" -ForegroundColor Green
Write-Host ""

# Étape 4: Instructions finales
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  ✅ BUILD TERMINÉ AVEC SUCCÈS!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "📦 Dossier de déploiement créé: $outputPath" -ForegroundColor Green
Write-Host ""
Write-Host "📋 PROCHAINES ÉTAPES:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1️⃣  Créez votre base de données sur InfinityFree" -ForegroundColor White
Write-Host "2️⃣  Créez le fichier api/.env avec vos infos DB" -ForegroundColor White
Write-Host "3️⃣  Uploadez le contenu de '$outputPath' vers /htdocs/ via FTP" -ForegroundColor White
Write-Host "4️⃣  Importez la base de données (api/database/schema.sql)" -ForegroundColor White
Write-Host ""
Write-Host "📖 Guide complet: DEPLOIEMENT_INFINITYFREE.md" -ForegroundColor Cyan
Write-Host ""
Write-Host "💡 Conseil: Utilisez FileZilla pour uploader (plus rapide)" -ForegroundColor Yellow
Write-Host ""

# Ouvrir le dossier
$openFolder = Read-Host "Voulez-vous ouvrir le dossier de production? (o/n)"
if ($openFolder -eq "o") {
    explorer $outputPath
}

Write-Host "✨ Prêt pour le déploiement!" -ForegroundColor Green
