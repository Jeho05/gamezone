# Quick test des endpoints player
Write-Host "Testing Leaderboard..." -ForegroundColor Cyan
$uri = "http://localhost/projet%20ismo/api/player/leaderboard.php?period=weekly&limit=10"
$response = Invoke-RestMethod -Uri $uri
Write-Host "Success: $($response.success)" -ForegroundColor Green
Write-Host "Total players: $($response.leaderboard.total_players)" -ForegroundColor Yellow
Write-Host "Rankings shown: $($response.leaderboard.rankings.Count)" -ForegroundColor Yellow
if ($response.leaderboard.rankings.Count -gt 0) {
    Write-Host "Top player: $($response.leaderboard.rankings[0].user.username) with $($response.leaderboard.rankings[0].points) points" -ForegroundColor Cyan
}
Write-Host ""
Write-Host "Full response:" -ForegroundColor Gray
$response | ConvertTo-Json -Depth 10
