Write-Host "========================================" -ForegroundColor Cyan
Write-Host "TEST GAMIFICATION AVEC/SANS AUTH" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "1. Test SANS authentification..." -ForegroundColor Yellow

try {
    $uri = "http://localhost/projet%20ismo/api/player/gamification.php"
    $response = Invoke-RestMethod -Uri $uri -Method Get -ErrorAction Stop
    
    Write-Host "  Statut: SUCCESS (inattendu)" -ForegroundColor Red
    Write-Host "  Response:" -ForegroundColor Gray
    $response | ConvertTo-Json -Depth 3
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    if ($statusCode -eq 401) {
        Write-Host "  [OK] Erreur 401 comme attendu" -ForegroundColor Green
        try {
            $errorContent = $_.ErrorDetails.Message | ConvertFrom-Json
            Write-Host "  Message: $($errorContent.message)" -ForegroundColor Cyan
        } catch {
            Write-Host "  Erreur: $($_.Exception.Message)" -ForegroundColor Gray
        }
    } else {
        Write-Host "  [ERREUR] Code $statusCode inattendu" -ForegroundColor Red
        Write-Host "  $($_.Exception.Message)" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "SOLUTION:" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Pour tester avec authentification:" -ForegroundColor White
Write-Host "1. Connectez-vous via le frontend React" -ForegroundColor Gray
Write-Host "   http://localhost:4000/auth/login" -ForegroundColor Cyan
Write-Host ""
Write-Host "2. Ou créez un utilisateur de test:" -ForegroundColor Gray
Write-Host "   C:\xampp\php\php.exe api\player\seed_sample_data.php" -ForegroundColor Cyan
Write-Host "   Username: testplayer1" -ForegroundColor Cyan
Write-Host "   Password: password123" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. L'endpoint retournera alors toutes les données" -ForegroundColor Gray
Write-Host ""
