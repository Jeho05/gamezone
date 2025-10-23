Write-Host "=== REDEMARRAGE APACHE XAMPP ===" -ForegroundColor Cyan

# Arrêter Apache
Write-Host "`nArret d'Apache..." -ForegroundColor Yellow
Stop-Process -Name "httpd" -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

# Vérifier qu'Apache est arrêté
$running = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if ($running) {
    Write-Host "Erreur: Apache tourne encore" -ForegroundColor Red
    exit 1
} else {
    Write-Host "Apache arrete avec succes" -ForegroundColor Green
}

# Redémarrer Apache
Write-Host "`nDemarrage d'Apache..." -ForegroundColor Yellow
$xamppPath = "C:\xampp\apache\bin\httpd.exe"

if (Test-Path $xamppPath) {
    Start-Process -FilePath $xamppPath -WorkingDirectory "C:\xampp\apache\bin"
    Start-Sleep -Seconds 3
    
    # Vérifier qu'Apache est démarré
    $running = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
    if ($running) {
        Write-Host "Apache demarre avec succes!" -ForegroundColor Green
        Write-Host "PID: $($running.Id -join ', ')" -ForegroundColor Gray
    } else {
        Write-Host "Erreur: Apache n'a pas demarre" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "Erreur: Apache non trouve a $xamppPath" -ForegroundColor Red
    Write-Host "Utilisez le panneau de controle XAMPP pour redemarrer Apache" -ForegroundColor Yellow
    exit 1
}

# Test connexion BDD
Write-Host "`nTest de connexion BDD..." -ForegroundColor Yellow
Start-Sleep -Seconds 2
try {
    $response = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/test_db.php" -ErrorAction Stop
    if ($response.success) {
        Write-Host "BDD connectee avec succes!" -ForegroundColor Green
        Write-Host "Base de donnees: $($response.database)" -ForegroundColor Gray
    } else {
        Write-Host "Erreur BDD: $($response.error)" -ForegroundColor Red
    }
} catch {
    Write-Host "Erreur test BDD: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n=== TERMINE ===" -ForegroundColor Cyan
