# Script de redémarrage du serveur Vite avec test des images

Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "   REDÉMARRAGE SERVEUR VITE - GAMEZONE   " -ForegroundColor Cyan
Write-Host "============================================`n" -ForegroundColor Cyan

$webDir = "createxyz-project\_\apps\web"

# Vérifier que Apache est démarré
Write-Host "[1/4] Vérification Apache..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost/projet%20ismo/api/health_check.php" -Method GET -TimeoutSec 3 -UseBasicParsing
    if ($response.StatusCode -eq 200) {
        Write-Host "      ✅ Apache OK (port 80)" -ForegroundColor Green
    }
} catch {
    Write-Host "      ⚠️  Apache non accessible" -ForegroundColor Red
    Write-Host "      Veuillez démarrer XAMPP (Apache)" -ForegroundColor Yellow
    Read-Host "`nAppuyez sur Entrée une fois Apache démarré"
}

# Arrêter les processus Node/Vite existants
Write-Host "`n[2/4] Arrêt des processus existants..." -ForegroundColor Yellow
$nodeProcesses = Get-Process -Name "node" -ErrorAction SilentlyContinue
if ($nodeProcesses) {
    Write-Host "      Arrêt de $($nodeProcesses.Count) processus Node..." -ForegroundColor Gray
    $nodeProcesses | Stop-Process -Force
    Start-Sleep -Seconds 2
    Write-Host "      ✅ Processus arrêtés" -ForegroundColor Green
} else {
    Write-Host "      ℹ️  Aucun processus Node en cours" -ForegroundColor Gray
}

# Vérifier que le dossier existe
Write-Host "`n[3/4] Vérification du projet..." -ForegroundColor Yellow
if (Test-Path $webDir) {
    Write-Host "      ✅ Dossier trouvé: $webDir" -ForegroundColor Green
} else {
    Write-Host "      ❌ Dossier introuvable: $webDir" -ForegroundColor Red
    exit 1
}

# Démarrer le serveur Vite
Write-Host "`n[4/4] Démarrage du serveur Vite..." -ForegroundColor Yellow
Write-Host "      Configuration proxy:" -ForegroundColor Gray
Write-Host "        • /php-api/* → http://localhost/projet%20ismo/api/*" -ForegroundColor Gray
Write-Host "        • /uploads/* → http://localhost/projet%20ismo/uploads/*" -ForegroundColor Gray
Write-Host "        • /images/*  → http://localhost/projet%20ismo/images/*" -ForegroundColor Cyan
Write-Host "`n" -ForegroundColor Gray

Set-Location $webDir

Write-Host "============================================" -ForegroundColor Green
Write-Host "   SERVEUR VITE EN COURS DE DÉMARRAGE...  " -ForegroundColor Green
Write-Host "============================================`n" -ForegroundColor Green

Write-Host "📍 URLs importantes:" -ForegroundColor Yellow
Write-Host "   • Application:    http://localhost:4000/" -ForegroundColor White
Write-Host "   • Test Images:    http://localhost:4000/test-images.html" -ForegroundColor Cyan
Write-Host "   • Login:          http://localhost:4000/auth/login" -ForegroundColor White
Write-Host "   • Register:       http://localhost:4000/auth/register" -ForegroundColor White

Write-Host "`n⚠️  Une fois le serveur démarré (message 'ready in X ms'), testez:" -ForegroundColor Yellow
Write-Host "   http://localhost:4000/test-images.html`n" -ForegroundColor Cyan

Write-Host "🛑 Pour arrêter le serveur: Ctrl+C`n" -ForegroundColor Red

# Démarrer npm run dev
npm run dev
