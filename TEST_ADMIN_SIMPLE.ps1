# Test simple et rapide des fonctionnalités admin

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  TEST ADMIN GAMEZONE" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Test Apache avec netstat
Write-Host "Test Apache..." -ForegroundColor Cyan
$apachePort = netstat -ano | findstr ":80 "
if ($apachePort) {
    Write-Host "  ✅ Apache actif" -ForegroundColor Green
} else {
    Write-Host "  ❌ Apache inactif" -ForegroundColor Red
}

# Test MySQL
Write-Host ""
Write-Host "Test MySQL..." -ForegroundColor Cyan
$mysqlPort = netstat -ano | findstr ":3306 "
if ($mysqlPort) {
    Write-Host "  ✅ MySQL actif" -ForegroundColor Green
} else {
    Write-Host "  ❌ MySQL inactif" -ForegroundColor Red
}

# Test API
Write-Host ""
Write-Host "Test API..." -ForegroundColor Cyan
try {
    $test = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/auth/check.php" -TimeoutSec 3
    Write-Host "  ✅ API accessible" -ForegroundColor Green
} catch {
    Write-Host "  ⚠️  API: $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "DIAGNOSTIC COMPLET:" -ForegroundColor Yellow
Write-Host "Ouvrez dans votre navigateur:" -ForegroundColor White
Write-Host "  DIAGNOSTIC_ADMIN_COMPLET.html" -ForegroundColor Cyan
Write-Host ""
Write-Host "Identifiants:" -ForegroundColor White
Write-Host "  Email: admin@gamezone.com" -ForegroundColor Gray
Write-Host "  Mot de passe: Admin123!" -ForegroundColor Gray
Write-Host ""

pause
