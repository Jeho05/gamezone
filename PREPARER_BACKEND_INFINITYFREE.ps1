# Préparation du Backend pour InfinityFree
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  PREPARATION BACKEND INFINITYFREE" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$outputDir = "backend_infinityfree"

# Supprimer ancien dossier
if (Test-Path $outputDir) {
    Remove-Item -Recurse -Force $outputDir
}

# Créer structure
Write-Host "Creation de la structure..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
New-Item -ItemType Directory -Path "$outputDir\uploads" -Force | Out-Null
New-Item -ItemType Directory -Path "$outputDir\uploads\avatars" -Force | Out-Null
New-Item -ItemType Directory -Path "$outputDir\uploads\games" -Force | Out-Null
New-Item -ItemType Directory -Path "$outputDir\images" -Force | Out-Null

# Copier le backend
Write-Host "Copie du backend PHP..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path "$outputDir\api" -Force | Out-Null
Copy-Item -Path "api\*" -Destination "$outputDir\api" -Recurse -Force -Exclude @("*.log", ".env")

# Copier les images
if (Test-Path "images") {
    Copy-Item -Path "images\*" -Destination "$outputDir\images" -Recurse -Force
}

# Copier uploads necessaires
Copy-Item -Path "uploads\.htaccess" -Destination "$outputDir\uploads\" -Force -ErrorAction SilentlyContinue
Copy-Item -Path "uploads\games\README.txt" -Destination "$outputDir\uploads\games\" -Force -ErrorAction SilentlyContinue

# Créer .env.example pour le backend
$envExample = @"
# Configuration Backend PHP pour InfinityFree
DB_HOST=sqlXXX.infinityfreeapp.com
DB_NAME=epiz_XXXXXXXX_gamezone
DB_USER=epiz_XXXXXXXX
DB_PASS=votre_mot_de_passe_mysql
APP_URL=https://gamezone-api.infinityfreeapp.com
KKIAPAY_PUBLIC_KEY=072b361d25546db0aee3d69bf07b15331c51e39f
KKIAPAY_PRIVATE_KEY=votre_cle_privee
KKIAPAY_SANDBOX=false
SESSION_LIFETIME=1440
SESSION_SECURE=true
"@

Set-Content -Path "$outputDir\api\.env.example" -Value $envExample

# Créer .htaccess racine avec CORS
$htaccess = @"
RewriteEngine On

# CORS Headers pour Vercel
<IfModule mod_headers.c>
    # IMPORTANT: Remplacez par votre URL Vercel reelle
    Header set Access-Control-Allow-Origin "https://gamezone.vercel.app"
    Header set Access-Control-Allow-Credentials "true"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
</IfModule>

# Handle preflight
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ $1 [R=200,L]

# Security
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
"@

Set-Content -Path "$outputDir\.htaccess" -Value $htaccess

# Créer README pour InfinityFree
$readme = @"
# Backend GameZone pour InfinityFree

## Installation

1. Uploadez tout le contenu de ce dossier vers /htdocs/ sur InfinityFree

2. Structure finale sur InfinityFree :
   /htdocs/
   ├── api/
   ├── uploads/
   ├── images/
   └── .htaccess

3. Créez api/.env avec vos informations InfinityFree

4. Importez api/database/schema.sql dans phpMyAdmin

5. Modifiez .htaccess avec votre URL Vercel

6. Testez : https://votre-domaine.infinityfreeapp.com/api/auth/check.php

## Configuration CORS

Dans .htaccess, remplacez :
  Header set Access-Control-Allow-Origin "https://gamezone.vercel.app"

Par votre vraie URL Vercel.

## Variables .env

Copiez api/.env.example vers api/.env et remplissez avec vos valeurs InfinityFree.
"@

Set-Content -Path "$outputDir\README.txt" -Value $readme

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  [OK] BACKEND PRET!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Dossier cree: $outputDir" -ForegroundColor Green
Write-Host ""
Write-Host "PROCHAINES ETAPES:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Creez compte InfinityFree et site web" -ForegroundColor White
Write-Host "2. Creez base MySQL et importez api/database/schema.sql" -ForegroundColor White
Write-Host "3. Editez api/.env.example -> api/.env avec vos infos DB" -ForegroundColor White
Write-Host "4. Uploadez $outputDir/* vers /htdocs/ via FTP" -ForegroundColor White
Write-Host "5. Modifiez .htaccess avec votre URL Vercel" -ForegroundColor White
Write-Host ""
Write-Host "Guide complet: DEPLOIEMENT_SEPARE.md" -ForegroundColor Cyan
Write-Host ""

# Ouvrir le dossier
$open = Read-Host "Ouvrir le dossier? (o/n)"
if ($open -eq "o") {
    explorer $outputDir
}
