# Script pour redémarrer le serveur de développement
Write-Host "🔄 Arrêt du serveur de développement..." -ForegroundColor Yellow

# Tuer tous les processus node (serveur React)
Get-Process -Name "node" -ErrorAction SilentlyContinue | Stop-Process -Force
Start-Sleep -Seconds 2

Write-Host "✅ Serveur arrêté" -ForegroundColor Green
Write-Host ""
Write-Host "🚀 Démarrage du nouveau serveur..." -ForegroundColor Cyan

# Aller dans le dossier du projet
Set-Location "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

# Démarrer le serveur
Write-Host "📦 Lancement de npm run dev..." -ForegroundColor Magenta
npm run dev
