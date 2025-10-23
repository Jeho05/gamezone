# Test rapide du système de contenu
Write-Host "========================================"  -ForegroundColor Cyan
Write-Host "TEST RAPIDE DU SYSTEME DE CONTENU" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Test 1: Tables DB
Write-Host "[1] Vérification des tables DB..." -ForegroundColor Yellow
$tables = & "C:\xampp\mysql\bin\mysql.exe" -u root gamezone -e "SHOW TABLES LIKE 'content%';"
Write-Host $tables
Write-Host ""

# Test 2: Compter le contenu
Write-Host "[2] Comptage du contenu en DB..." -ForegroundColor Yellow
& "C:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web\src\app\admin\content\page.jsx" -u root gamezone -e "SELECT COUNT(*) as total FROM content;"
Write-Host ""

# Test 3: API publique
Write-Host "[3] Test de l'API publique..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost/projet%20ismo/api/content/public.php?type=news" -UseBasicParsing
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Green
    $data = $response.Content | ConvertFrom-Json
    Write-Host "Total contenu: $($data.total)" -ForegroundColor Cyan
} catch {
    Write-Host "Erreur: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Tests terminés !" -ForegroundColor Green
Write-Host ""
Write-Host "URLs à tester:" -ForegroundColor Cyan
Write-Host "  - Page joueur: http://localhost:4000/player/gallery" -ForegroundColor White
Write-Host "  - Page admin: http://localhost:4000/admin/content" -ForegroundColor White
Write-Host ""
