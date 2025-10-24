# Script de Dépannage React - GameZone
# Résout les problèmes courants automatiquement

$ErrorActionPreference = "Continue"

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "   DÉPANNAGE AUTOMATIQUE - GAMEZONE REACT" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

$reactPath = "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

# Fonction pour afficher un statut
function Show-Status {
    param([string]$Message, [bool]$Success)
    if ($Success) {
        Write-Host "✅ $Message" -ForegroundColor Green
    } else {
        Write-Host "❌ $Message" -ForegroundColor Red
    }
}

# 1. Vérifier que le dossier existe
Write-Host "Vérification du dossier React..." -ForegroundColor Yellow
if (Test-Path $reactPath) {
    Show-Status "Dossier React trouvé" $true
} else {
    Show-Status "Dossier React introuvable!" $false
    Write-Host "Chemin attendu: $reactPath" -ForegroundColor Gray
    pause
    exit 1
}

Set-Location $reactPath

# 2. Vérifier Node.js
Write-Host ""
Write-Host "Vérification de Node.js..." -ForegroundColor Yellow
try {
    $nodeVersion = node --version
    Show-Status "Node.js installé ($nodeVersion)" $true
} catch {
    Show-Status "Node.js NON installé!" $false
    Write-Host ""
    Write-Host "SOLUTION:" -ForegroundColor Yellow
    Write-Host "1. Allez sur https://nodejs.org/" -ForegroundColor White
    Write-Host "2. Téléchargez la version LTS (recommandée)" -ForegroundColor White
    Write-Host "3. Installez et redémarrez PowerShell" -ForegroundColor White
    pause
    exit 1
}

# 3. Vérifier package.json
Write-Host ""
Write-Host "Vérification de package.json..." -ForegroundColor Yellow
if (Test-Path "package.json") {
    Show-Status "package.json trouvé" $true
} else {
    Show-Status "package.json manquant!" $false
    pause
    exit 1
}

# 4. Proposer de nettoyer et réinstaller
Write-Host ""
Write-Host "Que voulez-vous faire?" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Nettoyer et réinstaller les dépendances (RECOMMANDÉ si erreurs)" -ForegroundColor Green
Write-Host "2. Juste installer les dépendances manquantes" -ForegroundColor Green
Write-Host "3. Tuer les processus Node bloqués" -ForegroundColor Green
Write-Host "4. Réparer le cache npm" -ForegroundColor Green
Write-Host "5. Tout faire (nettoyage complet)" -ForegroundColor Cyan
Write-Host "0. Quitter" -ForegroundColor Red
Write-Host ""

$choice = Read-Host "Entrez votre choix (0-5)"

switch ($choice) {
    "1" {
        Write-Host ""
        Write-Host "=== NETTOYAGE ET RÉINSTALLATION ===" -ForegroundColor Cyan
        
        # Supprimer node_modules
        if (Test-Path "node_modules") {
            Write-Host "Suppression de node_modules..." -ForegroundColor Yellow
            Remove-Item -Recurse -Force "node_modules" -ErrorAction SilentlyContinue
            Show-Status "node_modules supprimé" $true
        }
        
        # Supprimer package-lock.json
        if (Test-Path "package-lock.json") {
            Write-Host "Suppression de package-lock.json..." -ForegroundColor Yellow
            Remove-Item -Force "package-lock.json" -ErrorAction SilentlyContinue
            Show-Status "package-lock.json supprimé" $true
        }
        
        # Réinstaller
        Write-Host ""
        Write-Host "Installation des dépendances..." -ForegroundColor Yellow
        Write-Host "(Cela peut prendre 5-10 minutes)" -ForegroundColor Gray
        npm install
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Show-Status "Dépendances installées avec succès!" $true
        } else {
            Show-Status "Erreur lors de l'installation!" $false
        }
    }
    
    "2" {
        Write-Host ""
        Write-Host "=== INSTALLATION DES DÉPENDANCES ===" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Installation en cours..." -ForegroundColor Yellow
        npm install
        
        if ($LASTEXITCODE -eq 0) {
            Show-Status "Installation réussie!" $true
        } else {
            Show-Status "Erreur lors de l'installation!" $false
        }
    }
    
    "3" {
        Write-Host ""
        Write-Host "=== ARRÊT DES PROCESSUS NODE ===" -ForegroundColor Cyan
        Write-Host ""
        
        # Tuer les processus node
        Write-Host "Arrêt des processus Node.js..." -ForegroundColor Yellow
        Get-Process -Name "node" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
        
        # Libérer le port 4000
        Write-Host "Libération du port 4000..." -ForegroundColor Yellow
        $processes = Get-NetTCPConnection -LocalPort 4000 -ErrorAction SilentlyContinue
        foreach ($proc in $processes) {
            Stop-Process -Id $proc.OwningProcess -Force -ErrorAction SilentlyContinue
        }
        
        Show-Status "Processus arrêtés!" $true
    }
    
    "4" {
        Write-Host ""
        Write-Host "=== RÉPARATION DU CACHE NPM ===" -ForegroundColor Cyan
        Write-Host ""
        
        Write-Host "Nettoyage du cache npm..." -ForegroundColor Yellow
        npm cache clean --force
        
        if ($LASTEXITCODE -eq 0) {
            Show-Status "Cache nettoyé!" $true
        } else {
            Show-Status "Erreur lors du nettoyage" $false
        }
    }
    
    "5" {
        Write-Host ""
        Write-Host "=== NETTOYAGE COMPLET ===" -ForegroundColor Cyan
        Write-Host ""
        
        # Tuer processus
        Write-Host "1. Arrêt des processus Node..." -ForegroundColor Yellow
        Get-Process -Name "node" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
        Show-Status "Processus arrêtés" $true
        
        # Nettoyer cache
        Write-Host ""
        Write-Host "2. Nettoyage du cache npm..." -ForegroundColor Yellow
        npm cache clean --force
        Show-Status "Cache nettoyé" $true
        
        # Supprimer node_modules
        Write-Host ""
        Write-Host "3. Suppression de node_modules..." -ForegroundColor Yellow
        if (Test-Path "node_modules") {
            Remove-Item -Recurse -Force "node_modules" -ErrorAction SilentlyContinue
            Show-Status "node_modules supprimé" $true
        }
        
        # Supprimer lock files
        Write-Host ""
        Write-Host "4. Suppression des fichiers de verrouillage..." -ForegroundColor Yellow
        Remove-Item -Force "package-lock.json" -ErrorAction SilentlyContinue
        Show-Status "Fichiers supprimés" $true
        
        # Supprimer build
        Write-Host ""
        Write-Host "5. Suppression du build..." -ForegroundColor Yellow
        if (Test-Path "build") {
            Remove-Item -Recurse -Force "build" -ErrorAction SilentlyContinue
        }
        if (Test-Path "dist") {
            Remove-Item -Recurse -Force "dist" -ErrorAction SilentlyContinue
        }
        Show-Status "Build supprimé" $true
        
        # Réinstaller
        Write-Host ""
        Write-Host "6. Réinstallation des dépendances..." -ForegroundColor Yellow
        Write-Host "(Cela peut prendre 5-10 minutes)" -ForegroundColor Gray
        npm install
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Show-Status "NETTOYAGE COMPLET RÉUSSI!" $true
            Write-Host ""
            Write-Host "Vous pouvez maintenant lancer: npm run dev" -ForegroundColor Cyan
        } else {
            Show-Status "Erreur lors de la réinstallation!" $false
        }
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

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Proposer de démarrer le serveur
$start = Read-Host "Voulez-vous démarrer le serveur de développement maintenant? (o/n)"
if ($start -eq "o") {
    Write-Host ""
    Write-Host "Démarrage du serveur..." -ForegroundColor Cyan
    Write-Host "L'application sera disponible sur: http://localhost:4000" -ForegroundColor Green
    Write-Host ""
    Write-Host "Appuyez sur Ctrl+C pour arrêter le serveur" -ForegroundColor Yellow
    Write-Host ""
    Start-Sleep -Seconds 2
    npm run dev
}

Write-Host ""
Write-Host "Terminé!" -ForegroundColor Green
pause
