# Script de démarrage rapide pour GameZone

Write-Host "=== GameZone - Démarrage ===" -ForegroundColor Cyan
Write-Host ""

# Vérifier si XAMPP est installé
$xamppPath = "C:\xampp\xampp_start.exe"
if (Test-Path $xamppPath) {
    Write-Host "[OK] XAMPP trouvé" -ForegroundColor Green
} else {
    Write-Host "[ERREUR] XAMPP non trouvé à C:\xampp\" -ForegroundColor Red
    Write-Host "Veuillez installer XAMPP ou ajuster le chemin dans ce script" -ForegroundColor Yellow
    pause
    exit
}

# Vérifier si Node.js est installé
try {
    $nodeVersion = node --version 2>$null
    Write-Host "[OK] Node.js $nodeVersion trouvé" -ForegroundColor Green
} catch {
    Write-Host "[ERREUR] Node.js non installé" -ForegroundColor Red
    Write-Host "Téléchargez Node.js depuis https://nodejs.org/" -ForegroundColor Yellow
    pause
    exit
}

Write-Host ""
Write-Host "=== Étape 1: Vérification XAMPP ===" -ForegroundColor Cyan

# Vérifier si Apache tourne
$apache = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if ($apache) {
    Write-Host "[OK] Apache est démarré" -ForegroundColor Green
} else {
    Write-Host "[!] Apache n'est pas démarré" -ForegroundColor Yellow
    Write-Host "Démarrage d'Apache..." -ForegroundColor Yellow
    Start-Process "C:\xampp\apache_start.bat" -WindowStyle Hidden
    Start-Sleep -Seconds 3
}

# Vérifier si MySQL tourne
$mysql = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if ($mysql) {
    Write-Host "[OK] MySQL est démarré" -ForegroundColor Green
} else {
    Write-Host "[!] MySQL n'est pas démarré" -ForegroundColor Yellow
    Write-Host "Démarrage de MySQL..." -ForegroundColor Yellow
    Start-Process "C:\xampp\mysql_start.bat" -WindowStyle Hidden
    Start-Sleep -Seconds 5
}

Write-Host ""
Write-Host "=== Étape 2: Installation de la base de données ===" -ForegroundColor Cyan
Write-Host "Accédez à: http://localhost/projet%20ismo/api/install.php" -ForegroundColor Yellow
Write-Host "Appuyez sur Entrée après avoir vérifié l'installation..." -ForegroundColor Yellow
$null = Read-Host

Write-Host ""
Write-Host "=== Étape 3: Démarrage du frontend ===" -ForegroundColor Cyan
$frontPath = "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

if (Test-Path "$frontPath\package.json") {
    Write-Host "[OK] Frontend trouvé" -ForegroundColor Green
    
    # Vérifier si node_modules existe
    if (-not (Test-Path "$frontPath\node_modules")) {
        Write-Host "[!] Installation des dépendances (cela peut prendre quelques minutes)..." -ForegroundColor Yellow
        Set-Location $frontPath
        npm install
    }
    
    Write-Host ""
    Write-Host "=== Démarrage du serveur de développement ===" -ForegroundColor Cyan
    Write-Host "Le serveur va démarrer sur http://localhost:4000" -ForegroundColor Green
    Write-Host "Appuyez sur Ctrl+C pour arrêter le serveur" -ForegroundColor Yellow
    Write-Host ""
    
    Set-Location $frontPath
    npm run dev
} else {
    Write-Host "[ERREUR] Frontend non trouvé à $frontPath" -ForegroundColor Red
    pause
}
