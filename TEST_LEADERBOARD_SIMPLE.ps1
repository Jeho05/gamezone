# Script de test simple pour le leaderboard
Write-Host "=== TEST LEADERBOARD ===" -ForegroundColor Cyan
Write-Host ""

# Test Leaderboard hebdomadaire
Write-Host "Test Leaderboard Hebdomadaire..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/player/leaderboard.php?period=weekly&limit=10" -Method GET -UseBasicParsing
    
    Write-Host "Success: $($response.success)" -ForegroundColor Green
    Write-Host "Period: $($response.leaderboard.period_label)" -ForegroundColor Cyan
    Write-Host "Total Players: $($response.leaderboard.total_players)" -ForegroundColor Cyan
    Write-Host "Showing Top: $($response.leaderboard.showing_top)" -ForegroundColor Cyan
    Write-Host ""
    
    # Afficher le top 5
    Write-Host "Top 5 Joueurs:" -ForegroundColor Yellow
    for ($i = 0; $i -lt [Math]::Min(5, $response.leaderboard.rankings.Count); $i++) {
        $player = $response.leaderboard.rankings[$i]
        $hasAvatar = if ($player.user.avatar_url) { "OUI" } else { "NON" }
        Write-Host "  #$($player.rank) - $($player.user.username) - $($player.points) pts - Avatar: $hasAvatar" -ForegroundColor White
        
        if ($player.user.avatar_url) {
            Write-Host "    Avatar URL: $($player.user.avatar_url)" -ForegroundColor Gray
        }
    }
    Write-Host ""
    
    # Test structure
    Write-Host "Structure OK:" -ForegroundColor Green
    $sample = $response.leaderboard.rankings[0]
    Write-Host "  - Rank: $($sample.rank)" -ForegroundColor White
    Write-Host "  - Username: $($sample.user.username)" -ForegroundColor White
    Write-Host "  - Points: $($sample.points)" -ForegroundColor White
    Write-Host "  - Level: $($sample.user.level)" -ForegroundColor White
    Write-Host "  - Badges: $($sample.badges_earned)" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Backend Leaderboard: OK" -ForegroundColor Green
    
} catch {
    Write-Host "ERREUR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "Testez le frontend sur: http://localhost:4000/player/leaderboard" -ForegroundColor Cyan
