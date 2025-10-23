Write-Host "Test connexion API..." -ForegroundColor Cyan

# Test BDD
Write-Host "`n1. Test base de donnees..."
try {
    $response = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/test.php" -Method GET
    Write-Host "OK: $($response.status)" -ForegroundColor Green
} catch {
    Write-Host "ERREUR BDD" -ForegroundColor Red
    Write-Host $_.Exception.Message
}

# Test Auth
Write-Host "`n2. Test auth endpoint..."
try {
    $response = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/auth/check.php" -Method GET
    Write-Host "OK: Authenticated = $($response.authenticated)" -ForegroundColor Green
} catch {
    Write-Host "ERREUR Auth" -ForegroundColor Red
}

Write-Host "`nFait!" -ForegroundColor Cyan
