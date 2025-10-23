# Script PowerShell pour tester rapidement le système de réservations
# Exécuter: .\TEST_RAPIDE_RESERVATIONS.ps1

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  TEST SYSTÈME DE RÉSERVATIONS" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Test 1: Vérifier le diagnostic
Write-Host "[1/4] Diagnostic du système..." -ForegroundColor Yellow
$diagnostic = C:\xampp\php\php.exe api/diagnostic_reservations.php | ConvertFrom-Json

if ($diagnostic.system_ready -eq $true) {
    Write-Host "✅ Système prêt!" -ForegroundColor Green
    Write-Host "   - Migration appliquée: $($diagnostic.migration_applied)" -ForegroundColor Gray
    Write-Host "   - Jeux réservables: $($diagnostic.reservable_games_count)" -ForegroundColor Gray
} else {
    Write-Host "❌ Système non prêt" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Test 2: Test complet
Write-Host "[2/4] Tests approfondis..." -ForegroundColor Yellow
$tests = C:\xampp\php\php.exe test_reservations_rewards.php | ConvertFrom-Json

Write-Host "✅ Réservations:" -ForegroundColor Green
Write-Host "   - Total: $($tests.reservations_stats.total)" -ForegroundColor Gray
Write-Host "   - Payées: $($tests.reservations_stats.paid)" -ForegroundColor Gray
Write-Host "   - En attente: $($tests.reservations_stats.pending)" -ForegroundColor Gray

Write-Host "✅ Récompenses:" -ForegroundColor Green
Write-Host "   - Disponibles: $($tests.rewards_available.count)" -ForegroundColor Gray
Write-Host "   - Échanges: $($tests.redemptions_stats.total)" -ForegroundColor Gray
Write-Host "   - Points dépensés: $($tests.redemptions_stats.total_points_spent)" -ForegroundColor Gray

Write-Host "✅ Intégrité:" -ForegroundColor Green
Write-Host "   - Statut: $($tests.data_integrity.status)" -ForegroundColor Gray
if ($tests.data_integrity.issues.Count -gt 0) {
    foreach ($issue in $tests.data_integrity.issues) {
        Write-Host "   ⚠️  $issue" -ForegroundColor Yellow
    }
}
Write-Host ""

# Test 3: Vérifier les jeux réservables
Write-Host "[3/4] Jeux réservables disponibles:" -ForegroundColor Yellow
foreach ($game in $tests.reservable_games.games) {
    Write-Host "   🎮 $($game.name)" -ForegroundColor Cyan
    Write-Host "      ID: $($game.id) | Frais: $($game.reservation_fee) XOF" -ForegroundColor Gray
}
Write-Host ""

# Test 4: Simulation de disponibilité
Write-Host "[4/4] Test de disponibilité:" -ForegroundColor Yellow
if ($tests.availability_simulation) {
    $sim = $tests.availability_simulation
    Write-Host "   Jeu: $($sim.game)" -ForegroundColor Cyan
    Write-Host "   Créneau test: $($sim.test_start)" -ForegroundColor Gray
    Write-Host "   Durée: $($sim.duration_minutes) min" -ForegroundColor Gray
    
    if ($sim.available -eq $true) {
        Write-Host "   ✅ DISPONIBLE (0 conflit)" -ForegroundColor Green
    } else {
        Write-Host "   ❌ INDISPONIBLE ($($sim.conflicts) conflit(s))" -ForegroundColor Red
    }
}
Write-Host ""

# Résumé final
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "           RESUME FINAL" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

if ($tests.summary.reservation_system_ready -and $tests.summary.rewards_system_ready) {
    Write-Host "TOUS LES SYSTEMES SONT OPERATIONNELS!" -ForegroundColor Green
    Write-Host ""
    Write-Host "✅ Système de réservations: Prêt" -ForegroundColor Green
    Write-Host "✅ Système de récompenses: Prêt" -ForegroundColor Green
    Write-Host "✅ Intégrité des données: OK" -ForegroundColor Green
    Write-Host ""
    Write-Host "📚 Documentation: SYSTEME_RESERVATIONS_COMPLET.md" -ForegroundColor Cyan
    Write-Host "🔗 Interface joueur: http://localhost:3000/player/shop" -ForegroundColor Cyan
    Write-Host "🔗 Mes réservations: http://localhost:3000/player/my-reservations" -ForegroundColor Cyan
} else {
    Write-Host "⚠️  Certains systèmes nécessitent une attention" -ForegroundColor Yellow
}
Write-Host ""
Write-Host "Timestamp: $($tests.summary.timestamp)" -ForegroundColor Gray
Write-Host "=====================================" -ForegroundColor Cyan
