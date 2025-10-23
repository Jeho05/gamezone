Write-Host "========================================" -ForegroundColor Cyan
Write-Host "VERIFICATION DES ENDPOINTS PLAYER" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier que les fichiers existent
Write-Host "1. Vérification des fichiers..." -ForegroundColor Yellow
$files = @(
    "api\player\leaderboard.php",
    "api\player\gamification.php",
    "api\player\README.md",
    "api\player\seed_sample_data.php",
    "test_player_endpoints.html"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "  [OK] $file" -ForegroundColor Green
    } else {
        Write-Host "  [MANQUANT] $file" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "2. Test de l'endpoint leaderboard..." -ForegroundColor Yellow

try {
    $uri = "http://localhost/projet%20ismo/api/player/leaderboard.php?period=all"
    $response = Invoke-RestMethod -Uri $uri -Method Get
    
    if ($response.success) {
        Write-Host "  [SUCCESS] Endpoint accessible!" -ForegroundColor Green
        Write-Host "  Total joueurs: $($response.leaderboard.total_players)" -ForegroundColor White
        Write-Host "  Classement affiché: $($response.leaderboard.rankings.Count)" -ForegroundColor White
        
        if ($response.leaderboard.rankings.Count -gt 0) {
            $top = $response.leaderboard.rankings[0]
            Write-Host "  Top 1: $($top.user.username) avec $($top.points) points" -ForegroundColor Cyan
        }
    } else {
        Write-Host "  [ERREUR] $($response.error)" -ForegroundColor Red
    }
} catch {
    Write-Host "  [ERREUR] Impossible d'accéder à l'endpoint" -ForegroundColor Red
    Write-Host "  Détails: $($_.Exception.Message)" -ForegroundColor Gray
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "COMMENT ACCEDER AUX ENDPOINTS:" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Option 1 - Interface Web:" -ForegroundColor White
Write-Host "  Ouvrez dans votre navigateur:" -ForegroundColor Gray
Write-Host "  http://localhost/projet%20ismo/test_player_endpoints.html" -ForegroundColor Cyan
Write-Host ""
Write-Host "Option 2 - API Direct:" -ForegroundColor White
Write-Host "  Leaderboard:" -ForegroundColor Gray
Write-Host "  http://localhost/projet%20ismo/api/player/leaderboard.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Option 3 - Via le port 4000 (si proxy configuré):" -ForegroundColor White
Write-Host "  http://localhost:4000/api/player/leaderboard.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
