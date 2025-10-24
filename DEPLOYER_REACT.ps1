# Script de Déploiement Rapide React - GameZone
# Choisissez votre méthode de déploiement

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "     DÉPLOIEMENT REACT - GAMEZONE" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Choisissez votre option de déploiement:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Démarrage Local (Développement)" -ForegroundColor Green
Write-Host "   - Le plus simple pour tester" -ForegroundColor Gray
Write-Host "   - Nécessite XAMPP" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Build pour Production" -ForegroundColor Green
Write-Host "   - Prépare les fichiers pour hébergement" -ForegroundColor Gray
Write-Host "   - Pour InfinityFree, Hostinger, etc." -ForegroundColor Gray
Write-Host ""
Write-Host "3. Déployer sur GitHub Pages" -ForegroundColor Green
Write-Host "   - Gratuit, mais frontend seulement" -ForegroundColor Gray
Write-Host "   - Nécessite un compte GitHub" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Vérifier l'Installation" -ForegroundColor Green
Write-Host "   - Diagnostiquer les problèmes" -ForegroundColor Gray
Write-Host ""
Write-Host "0. Quitter" -ForegroundColor Red
Write-Host ""

$choice = Read-Host "Entrez votre choix (0-4)"

switch ($choice) {
    "1" {
        # Démarrage Local
        Write-Host ""
        Write-Host "=== DÉMARRAGE LOCAL ===" -ForegroundColor Cyan
        Write-Host ""
        
        # Vérifier XAMPP
        Write-Host "Vérification de XAMPP..." -ForegroundColor Yellow
        $apache = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
        $mysql = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
        
        if (-not $apache -or -not $mysql) {
            Write-Host "❌ XAMPP n'est pas démarré!" -ForegroundColor Red
            Write-Host ""
            Write-Host "Veuillez:" -ForegroundColor Yellow
            Write-Host "1. Ouvrir XAMPP Control Panel" -ForegroundColor White
            Write-Host "2. Démarrer Apache" -ForegroundColor White
            Write-Host "3. Démarrer MySQL" -ForegroundColor White
            Write-Host "4. Relancer ce script" -ForegroundColor White
            pause
            exit 1
        }
        
        Write-Host "✅ XAMPP est démarré" -ForegroundColor Green
        
        # Vérifier Node.js
        Write-Host "Vérification de Node.js..." -ForegroundColor Yellow
        try {
            $nodeVersion = node --version
            Write-Host "✅ Node.js $nodeVersion installé" -ForegroundColor Green
        } catch {
            Write-Host "❌ Node.js n'est pas installé!" -ForegroundColor Red
            Write-Host "Téléchargez depuis: https://nodejs.org/" -ForegroundColor Yellow
            pause
            exit 1
        }
        
        # Aller dans le dossier React
        $reactPath = "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
        Set-Location $reactPath
        
        # Installer les dépendances si nécessaire
        if (-not (Test-Path "node_modules")) {
            Write-Host ""
            Write-Host "Installation des dépendances npm..." -ForegroundColor Yellow
            Write-Host "(Cela peut prendre 2-3 minutes)" -ForegroundColor Gray
            npm install
            if ($LASTEXITCODE -ne 0) {
                Write-Host "❌ Erreur lors de l'installation!" -ForegroundColor Red
                pause
                exit 1
            }
            Write-Host "✅ Dépendances installées" -ForegroundColor Green
        }
        
        Write-Host ""
        Write-Host "================================================" -ForegroundColor Green
        Write-Host "  DÉMARRAGE DU SERVEUR DE DÉVELOPPEMENT" -ForegroundColor Green
        Write-Host "================================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "L'application sera disponible sur:" -ForegroundColor Cyan
        Write-Host "  http://localhost:4000" -ForegroundColor White
        Write-Host ""
        Write-Host "Appuyez sur Ctrl+C pour arrêter le serveur" -ForegroundColor Yellow
        Write-Host ""
        
        Start-Sleep -Seconds 2
        npm run dev
    }
    
    "2" {
        # Build Production
        Write-Host ""
        Write-Host "=== BUILD POUR PRODUCTION ===" -ForegroundColor Cyan
        Write-Host ""
        
        $buildScript = "c:\xampp\htdocs\projet ismo\BUILD_PRODUCTION.ps1"
        
        if (Test-Path $buildScript) {
            Write-Host "Lancement du script de build..." -ForegroundColor Yellow
            & $buildScript
        } else {
            Write-Host "❌ Script BUILD_PRODUCTION.ps1 introuvable!" -ForegroundColor Red
            pause
            exit 1
        }
    }
    
    "3" {
        # GitHub Pages
        Write-Host ""
        Write-Host "=== DÉPLOIEMENT GITHUB PAGES ===" -ForegroundColor Cyan
        Write-Host ""
        
        Write-Host "PRÉREQUIS:" -ForegroundColor Yellow
        Write-Host "1. Avoir un compte GitHub" -ForegroundColor White
        Write-Host "2. Avoir créé un repository (ex: gamezone)" -ForegroundColor White
        Write-Host "3. Avoir Git installé" -ForegroundColor White
        Write-Host ""
        
        $continue = Read-Host "Avez-vous ces prérequis? (o/n)"
        if ($continue -ne "o") {
            Write-Host "Veuillez d'abord créer un compte et un repository sur GitHub" -ForegroundColor Yellow
            pause
            exit 0
        }
        
        $username = Read-Host "Entrez votre nom d'utilisateur GitHub"
        $repoName = Read-Host "Entrez le nom de votre repository"
        
        $reactPath = "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
        Set-Location $reactPath
        
        # Installer gh-pages
        Write-Host ""
        Write-Host "Installation de gh-pages..." -ForegroundColor Yellow
        npm install gh-pages --save-dev
        
        # Modifier package.json
        Write-Host "Configuration de l'URL de déploiement..." -ForegroundColor Yellow
        $packageJson = Get-Content "package.json" -Raw | ConvertFrom-Json
        $packageJson.homepage = "https://$username.github.io/$repoName"
        $packageJson | ConvertTo-Json -Depth 100 | Set-Content "package.json"
        
        Write-Host "✅ Configuration mise à jour" -ForegroundColor Green
        
        # Initialiser Git si nécessaire
        if (-not (Test-Path ".git")) {
            Write-Host "Initialisation de Git..." -ForegroundColor Yellow
            git init
            git add .
            git commit -m "Initial commit"
            git branch -M main
            git remote add origin "https://github.com/$username/$repoName.git"
        }
        
        # Déployer
        Write-Host ""
        Write-Host "Déploiement sur GitHub Pages..." -ForegroundColor Yellow
        Write-Host "(Cela peut prendre 5-10 minutes)" -ForegroundColor Gray
        npm run deploy
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host "================================================" -ForegroundColor Green
            Write-Host "  ✅ DÉPLOIEMENT RÉUSSI!" -ForegroundColor Green
            Write-Host "================================================" -ForegroundColor Green
            Write-Host ""
            Write-Host "Votre site sera disponible dans 2-3 minutes sur:" -ForegroundColor Cyan
            Write-Host "  https://$username.github.io/$repoName" -ForegroundColor White
            Write-Host ""
            Write-Host "N'oubliez pas d'activer GitHub Pages dans les paramètres du repository!" -ForegroundColor Yellow
        } else {
            Write-Host "❌ Erreur lors du déploiement" -ForegroundColor Red
        }
        
        pause
    }
    
    "4" {
        # Vérification
        Write-Host ""
        Write-Host "=== VÉRIFICATION DE L'INSTALLATION ===" -ForegroundColor Cyan
        Write-Host ""
        
        # Vérifier XAMPP
        Write-Host "1. Apache..." -NoNewline
        $apache = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
        if ($apache) {
            Write-Host " ✅" -ForegroundColor Green
        } else {
            Write-Host " ❌" -ForegroundColor Red
        }
        
        Write-Host "2. MySQL..." -NoNewline
        $mysql = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
        if ($mysql) {
            Write-Host " ✅" -ForegroundColor Green
        } else {
            Write-Host " ❌" -ForegroundColor Red
        }
        
        # Vérifier Node.js
        Write-Host "3. Node.js..." -NoNewline
        try {
            $nodeVersion = node --version
            Write-Host " ✅ ($nodeVersion)" -ForegroundColor Green
        } catch {
            Write-Host " ❌" -ForegroundColor Red
        }
        
        # Vérifier npm
        Write-Host "4. npm..." -NoNewline
        try {
            $npmVersion = npm --version
            Write-Host " ✅ ($npmVersion)" -ForegroundColor Green
        } catch {
            Write-Host " ❌" -ForegroundColor Red
        }
        
        # Vérifier dossier React
        Write-Host "5. Dossier React..." -NoNewline
        $reactPath = "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
        if (Test-Path $reactPath) {
            Write-Host " ✅" -ForegroundColor Green
        } else {
            Write-Host " ❌" -ForegroundColor Red
        }
        
        # Vérifier node_modules
        Write-Host "6. Dépendances installées..." -NoNewline
        if (Test-Path "$reactPath\node_modules") {
            Write-Host " ✅" -ForegroundColor Green
        } else {
            Write-Host " ❌ (Lancez l'option 1 pour installer)" -ForegroundColor Yellow
        }
        
        # Vérifier API
        Write-Host "7. API Backend..." -NoNewline
        try {
            $response = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/test.php" -Method Get -TimeoutSec 3 -ErrorAction Stop
            Write-Host " ✅" -ForegroundColor Green
        } catch {
            Write-Host " ❌" -ForegroundColor Red
        }
        
        Write-Host ""
        Write-Host "Diagnostic terminé!" -ForegroundColor Cyan
        pause
    }
    
    "0" {
        Write-Host "Au revoir!" -ForegroundColor Cyan
        exit 0
    }
    
    default {
        Write-Host "Option invalide!" -ForegroundColor Red
        pause
        exit 1
    }
}
