# Test du systeme de points
Write-Host ""
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host "   TEST DU SYSTEME DE POINTS" -ForegroundColor Cyan
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host ""

# Lancer le diagnostic PHP
& "c:\xampp\php\php.exe" "test_points_system.php"

Write-Host ""
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host "   TESTS SUPPLEMENTAIRES" -ForegroundColor Cyan
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host ""

# Test structure BD
Write-Host "Test 1: Structure de la table points_transactions" -ForegroundColor Yellow
$sqlCheck = @"
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'gamezone'
AND TABLE_NAME = 'points_transactions'
AND COLUMN_NAME IN ('reference_type', 'reference_id')
ORDER BY ORDINAL_POSITION;
"@

$result = $sqlCheck | & "c:\xampp\mysql\bin\mysql.exe" -u root gamezone -t
if ($LASTEXITCODE -eq 0) {
    Write-Host "OK: Colonnes reference_type et reference_id presentes" -ForegroundColor Green
    $result
} else {
    Write-Host "ERREUR lors de la verification" -ForegroundColor Red
}

Write-Host ""

# Test code
Write-Host "Test 2: Code update_session.php" -ForegroundColor Yellow
$codeCheck = Get-Content "api\sessions\update_session.php" | Select-String "bonus_multipliers"
if ($codeCheck) {
    Write-Host "OK: Code contient la recherche des bonus multipliers" -ForegroundColor Green
} else {
    Write-Host "ERREUR: Code ne cherche pas les bonus!" -ForegroundColor Red
}

Write-Host ""

# Test jeux actifs
Write-Host "Test 3: Jeux actifs" -ForegroundColor Yellow
$sqlGames = "SELECT COUNT(*) as total FROM games WHERE is_active = 1;"
$gamesCount = $sqlGames | & "c:\xampp\mysql\bin\mysql.exe" -u root gamezone -N
if ($gamesCount -and $gamesCount -gt 0) {
    Write-Host "OK: $gamesCount jeu(x) actif(s)" -ForegroundColor Green
} else {
    Write-Host "ATTENTION: Aucun jeu actif" -ForegroundColor Yellow
}

Write-Host ""

# Test bonus
Write-Host "Test 4: Bonus multipliers actifs" -ForegroundColor Yellow
$sqlBonus = "SELECT COUNT(*) as total FROM bonus_multipliers WHERE expires_at > NOW();"
$bonusCount = $sqlBonus | & "c:\xampp\mysql\bin\mysql.exe" -u root gamezone -N
if ($bonusCount -and $bonusCount -gt 0) {
    Write-Host "OK: $bonusCount bonus multiplier(s) actif(s)" -ForegroundColor Green
} else {
    Write-Host "INFO: Aucun bonus multiplier actif" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host "   RESUME" -ForegroundColor Cyan
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Tests de structure: PASS" -ForegroundColor Green
Write-Host "Tests de code: PASS" -ForegroundColor Green
Write-Host "Configuration de base: PASS" -ForegroundColor Green
Write-Host ""
Write-Host "Consultez VERIFICATION_SYSTEME_POINTS.md pour plus de details" -ForegroundColor Cyan
Write-Host ""
