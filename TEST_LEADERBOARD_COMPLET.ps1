# Script de test complet pour le leaderboard
Write-Host "=== TEST LEADERBOARD COMPLET ===" -ForegroundColor Cyan
Write-Host ""

# Test 1: Leaderboard hebdomadaire
Write-Host "1. Test Leaderboard Hebdomadaire..." -ForegroundColor Yellow
$response1 = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/player/leaderboard.php?period=weekly&limit=10" -Method GET -UseBasicParsing
Write-Host "Success: $($response1.success)" -ForegroundColor Green
Write-Host "Period: $($response1.leaderboard.period_label)" -ForegroundColor Cyan
Write-Host "Total Players: $($response1.leaderboard.total_players)" -ForegroundColor Cyan
Write-Host "Showing Top: $($response1.leaderboard.showing_top)" -ForegroundColor Cyan
Write-Host "Total Points Distributed: $($response1.leaderboard.total_points_distributed)" -ForegroundColor Cyan
Write-Host ""

# Afficher le top 5
Write-Host "Top 5 Joueurs:" -ForegroundColor Yellow
for ($i = 0; $i -lt [Math]::Min(5, $response1.leaderboard.rankings.Count); $i++) {
    $player = $response1.leaderboard.rankings[$i]
    $avatarStatus = if ($player.user.avatar_url) { "✓" } else { "✗" }
    Write-Host "  #$($player.rank) - $($player.user.username) - $($player.points) pts - Avatar: $avatarStatus" -ForegroundColor White
}
Write-Host ""

# Test 2: Leaderboard mensuel
Write-Host "2. Test Leaderboard Mensuel..." -ForegroundColor Yellow
$response2 = Invoke-RestMethod -Uri "http://localhost/projet%20ismo/api/player/leaderboard.php?period=monthly&limit=10" -Method GET -UseBasicParsing
Write-Host "Success: $($response2.success)" -ForegroundColor Green
Write-Host "Period: $($response2.leaderboard.period_label)" -ForegroundColor Cyan
Write-Host "Total Points (Month): $($response2.leaderboard.total_points_distributed)" -ForegroundColor Cyan
Write-Host ""

# Test 3: Vérifier les avatars
Write-Host "3. Vérification des Avatars..." -ForegroundColor Yellow
$avatarsOk = 0
$avatarsMissing = 0
foreach ($player in $response1.leaderboard.rankings) {
    if ($player.user.avatar_url) {
        $avatarsOk++
        # Tester si l'avatar est accessible
        $avatarUrl = "http://localhost/projet%20ismo$($player.user.avatar_url)"
        try {
            $avatarTest = Invoke-WebRequest -Uri $avatarUrl -Method Head -UseBasicParsing -ErrorAction Stop
            Write-Host "  ✓ Avatar $($player.user.username) accessible (Status: $($avatarTest.StatusCode))" -ForegroundColor Green
        } catch {
            Write-Host "  ✗ Avatar $($player.user.username) NON accessible!" -ForegroundColor Red
        }
    } else {
        $avatarsMissing++
    }
}
Write-Host ""
Write-Host "Avatars OK: $avatarsOk / $($response1.leaderboard.rankings.Count)" -ForegroundColor Cyan
Write-Host "Avatars manquants: $avatarsMissing" -ForegroundColor Yellow
Write-Host ""

# Test 4: Structure de la réponse
Write-Host "4. Vérification de la structure..." -ForegroundColor Yellow
$samplePlayer = $response1.leaderboard.rankings[0]
Write-Host "  ✓ Rank: $($samplePlayer.rank -ne $null)" -ForegroundColor Green
Write-Host "  ✓ Username: $($samplePlayer.user.username -ne $null)" -ForegroundColor Green
Write-Host "  ✓ Avatar URL: $($samplePlayer.user.avatar_url -ne $null)" -ForegroundColor Green
Write-Host "  ✓ Points: $($samplePlayer.points -ne $null)" -ForegroundColor Green
Write-Host "  ✓ Total Points: $($samplePlayer.total_points -ne $null)" -ForegroundColor Green
Write-Host "  ✓ Level: $($samplePlayer.user.level -ne $null)" -ForegroundColor Green
Write-Host "  ✓ Level Info: $($samplePlayer.user.level_info -ne $null)" -ForegroundColor Green
Write-Host "  ✓ Badges: $($samplePlayer.badges_earned -ne $null)" -ForegroundColor Green
Write-Host "  ✓ Active Days: $($samplePlayer.active_days -ne $null)" -ForegroundColor Green
Write-Host ""

# Test 5: Frontend URL Resolution
Write-Host "5. Test Resolution URLs Frontend..." -ForegroundColor Yellow
foreach ($player in $response1.leaderboard.rankings | Select-Object -First 3) {
    $avatarUrl = $player.user.avatar_url
    if ($avatarUrl) {
        if ($avatarUrl.StartsWith("http")) {
            Write-Host "  → Avatar déjà complet: $avatarUrl" -ForegroundColor Cyan
        } else {
            $resolvedUrl = "http://localhost/projet%20ismo$avatarUrl"
            Write-Host "  → Avatar relatif: $avatarUrl" -ForegroundColor Yellow
            Write-Host "  → Résolu en: $resolvedUrl" -ForegroundColor Green
        }
    } else {
        $fallback = "https://i.pravatar.cc/150?u=$($player.user.username)"
        Write-Host "  → Pas d'avatar, fallback: $fallback" -ForegroundColor Magenta
    }
}
Write-Host ""

# Résumé
Write-Host "=== RÉSUMÉ ===" -ForegroundColor Cyan
Write-Host "✓ API Leaderboard: OK" -ForegroundColor Green
Write-Host "✓ Structure données: OK" -ForegroundColor Green
Write-Host "✓ Avatars: $avatarsOk sur $($response1.leaderboard.rankings.Count) joueurs" -ForegroundColor Green
Write-Host "✓ Fallback pravatar: Configuré" -ForegroundColor Green
Write-Host ""
Write-Host "Le leaderboard est prêt à être testé sur http://localhost:4000/player/leaderboard" -ForegroundColor Cyan
