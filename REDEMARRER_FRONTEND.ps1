Write-Host "=== REDEMARRAGE DU SERVEUR FRONTEND ===" -ForegroundColor Cyan

# Arrêter tous les processus Node.js
Write-Host "`nArret des serveurs Node.js..." -ForegroundColor Yellow
Get-Process -Name "node" -ErrorAction SilentlyContinue | Stop-Process -Force
Start-Sleep -Seconds 2

$stopped = Get-Process -Name "node" -ErrorAction SilentlyContinue
if ($stopped) {
    Write-Host "Attention: Certains processus Node tournent encore" -ForegroundColor Yellow
} else {
    Write-Host "Tous les processus Node arretes" -ForegroundColor Green
}

# Redémarrer le serveur dev
Write-Host "`nDemarrage du serveur frontend..." -ForegroundColor Yellow
Write-Host "Repertoire: createxyz-project\_\apps\web" -ForegroundColor Gray

Set-Location "createxyz-project\_\apps\web"

Write-Host "`nLancement de 'npm run dev'..." -ForegroundColor Cyan
Write-Host "Le serveur va demarrer dans une nouvelle fenetre" -ForegroundColor Gray
Write-Host "Attendez le message: 'Local: http://localhost:4000'" -ForegroundColor Gray

# Démarrer dans une nouvelle fenêtre PowerShell
Start-Process powershell -ArgumentList "-NoExit", "-Command", "npm run dev"

Write-Host "`n=== INSTRUCTIONS ===" -ForegroundColor Cyan
Write-Host "1. Attendez que le serveur demarre (nouvelle fenetre)" -ForegroundColor White
Write-Host "2. Allez sur: http://localhost:4000/auth/login" -ForegroundColor White
Write-Host "3. Videz le cache: Ctrl+Shift+R" -ForegroundColor White
Write-Host "4. Connectez-vous avec:" -ForegroundColor White
Write-Host "   Email: admin@gamezone.fr" -ForegroundColor Gray
Write-Host "   Password: demo123" -ForegroundColor Gray
