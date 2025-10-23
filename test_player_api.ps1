# Script de test des endpoints Player
# PowerShell script pour tester les API player/leaderboard et player/gamification

$baseUrl = "http://localhost/projet%20ismo/api/player"

Write-Host "================================" -ForegroundColor Cyan
Write-Host "🎮 TEST DES ENDPOINTS PLAYER" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Test 1: Leaderboard Weekly
Write-Host "📊 Test 1: Leaderboard (Weekly)" -ForegroundColor Yellow
Write-Host "GET $baseUrl/leaderboard.php?period=weekly&limit=10" -ForegroundColor Gray

try {
    $uri = "$baseUrl/leaderboard.php?period=weekly&limit=10"
    $response = Invoke-RestMethod -Uri $uri -Method Get -ErrorAction Stop
    
    if ($response.success) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
        Write-Host "   Période: $($response.leaderboard.period_label)" -ForegroundColor White
        Write-Host "   Total joueurs: $($response.leaderboard.total_players)" -ForegroundColor White
        Write-Host "   Points distribués: $($response.leaderboard.total_points_distributed)" -ForegroundColor White
        Write-Host "   Top affichés: $($response.leaderboard.showing_top)" -ForegroundColor White
        
        if ($response.leaderboard.rankings.Count -gt 0) {
            Write-Host "   Premier joueur: $($response.leaderboard.rankings[0].user.username) - $($response.leaderboard.rankings[0].points) pts" -ForegroundColor Cyan
        }
    } else {
        Write-Host "❌ FAILED" -ForegroundColor Red
        Write-Host "   Error: $($response.error)" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 2: Leaderboard All Time
Write-Host "📊 Test 2: Leaderboard (All Time)" -ForegroundColor Yellow
Write-Host "GET $baseUrl/leaderboard.php?period=all&limit=25" -ForegroundColor Gray

try {
    $uri = "$baseUrl/leaderboard.php?period=all&limit=25"
    $response = Invoke-RestMethod -Uri $uri -Method Get -ErrorAction Stop
    
    if ($response.success) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
        Write-Host "   Période: $($response.leaderboard.period_label)" -ForegroundColor White
        Write-Host "   Total joueurs: $($response.leaderboard.total_players)" -ForegroundColor White
        Write-Host "   Top affichés: $($response.leaderboard.showing_top)" -ForegroundColor White
    } else {
        Write-Host "❌ FAILED" -ForegroundColor Red
        Write-Host "   Error: $($response.error)" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 3: Leaderboard Monthly
Write-Host "📊 Test 3: Leaderboard (Monthly)" -ForegroundColor Yellow
Write-Host "GET $baseUrl/leaderboard.php?period=monthly&limit=50" -ForegroundColor Gray

try {
    $uri = "$baseUrl/leaderboard.php?period=monthly&limit=50"
    $response = Invoke-RestMethod -Uri $uri -Method Get -ErrorAction Stop
    
    if ($response.success) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
        Write-Host "   Période: $($response.leaderboard.period_label)" -ForegroundColor White
        Write-Host "   Total joueurs: $($response.leaderboard.total_players)" -ForegroundColor White
        Write-Host "   Top affichés: $($response.leaderboard.showing_top)" -ForegroundColor White
    } else {
        Write-Host "❌ FAILED" -ForegroundColor Red
        Write-Host "   Error: $($response.error)" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan

# Test 4: Gamification (nécessite authentification)
Write-Host "🎯 Test 4: Gamification" -ForegroundColor Yellow
Write-Host "GET $baseUrl/gamification.php" -ForegroundColor Gray
Write-Host "   ⚠️  Authentification requise" -ForegroundColor DarkYellow

try {
    # Tester sans authentification pour voir l'erreur attendue
    $response = Invoke-RestMethod -Uri "$baseUrl/gamification.php" -Method Get -ErrorAction Stop
    
    if ($response.success) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
        Write-Host "   Utilisateur: $($response.user.username)" -ForegroundColor White
        Write-Host "   Points: $($response.user.points)" -ForegroundColor White
        Write-Host "   Niveau: $($response.user.level)" -ForegroundColor White
        Write-Host "   Rang global: #$($response.leaderboard.global_rank)" -ForegroundColor White
        Write-Host "   Badges: $($response.badges.total_earned)/$($response.badges.total_available)" -ForegroundColor White
        Write-Host "   Série: $($response.streak.current) jours" -ForegroundColor White
    } else {
        Write-Host "❌ FAILED" -ForegroundColor Red
        Write-Host "   Error: $($response.error)" -ForegroundColor Red
    }
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    if ($statusCode -eq 401) {
        Write-Host "⚠️  Authentification requise (comme attendu)" -ForegroundColor Yellow
        Write-Host "   Pour tester avec authentification:" -ForegroundColor Gray
        Write-Host "   1. Connectez-vous via l'interface web" -ForegroundColor Gray
        Write-Host "   2. Ou utilisez test_player_endpoints.html" -ForegroundColor Gray
    } else {
        Write-Host "❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "📋 RÉSUMÉ DES TESTS" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Les endpoints suivants sont disponibles:" -ForegroundColor White
Write-Host "  ✓ GET /api/player/leaderboard.php" -ForegroundColor Green
Write-Host "  ✓ GET /api/player/gamification.php (auth requise)" -ForegroundColor Green
Write-Host ""
Write-Host "Pour tester visuellement:" -ForegroundColor Yellow
Write-Host "  Ouvrez: http://localhost:4000/test_player_endpoints.html" -ForegroundColor Cyan
Write-Host ""
Write-Host "Documentation complète:" -ForegroundColor Yellow
Write-Host "  Voir: api/player/README.md" -ForegroundColor Cyan
Write-Host ""
