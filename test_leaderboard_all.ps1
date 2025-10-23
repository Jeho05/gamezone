Write-Host "=== TEST CLASSEMENT GENERAL ===" -ForegroundColor Cyan

$url = "http://localhost/projet%20ismo/api/player/leaderboard.php?period=all&limit=10"
$response = Invoke-RestMethod -Uri $url -Method GET

Write-Host "Success: $($response.success)" -ForegroundColor Green
Write-Host "Total joueurs: $($response.leaderboard.total_players)" -ForegroundColor Cyan
Write-Host "Top affiches: $($response.leaderboard.showing_top)" -ForegroundColor Cyan
Write-Host ""

if ($response.leaderboard.rankings.Count -gt 0) {
    Write-Host "TOP 10 GENERAL:" -ForegroundColor Yellow
    foreach ($player in $response.leaderboard.rankings) {
        $hasAvatar = if ($player.user.avatar_url) { "[AVATAR]" } else { "[NO AVATAR]" }
        Write-Host "#$($player.rank) - $($player.user.username) - $($player.points) pts $hasAvatar" -ForegroundColor White
        if ($player.user.avatar_url) {
            Write-Host "   -> $($player.user.avatar_url)" -ForegroundColor Gray
        }
    }
} else {
    Write-Host "Aucun joueur dans le classement!" -ForegroundColor Red
}

Write-Host ""
Write-Host "Test termine!" -ForegroundColor Green
