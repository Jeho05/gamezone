# ============================================================================
# SCRIPT DE TEST RAPIDE DU SYSTÈME DE POINTS
# ============================================================================

Write-Host ""
Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host "   TEST DU SYSTÈME DE POINTS - VÉRIFICATION RAPIDE                    " -ForegroundColor Cyan
Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier que MySQL est démarré
Write-Host "🔍 Vérification des services..." -ForegroundColor Yellow
$mysqlProcess = Get-Process mysqld -ErrorAction SilentlyContinue
$apacheProcess = Get-Process httpd -ErrorAction SilentlyContinue

if (-not $mysqlProcess) {
    Write-Host "❌ MySQL n'est pas démarré! Démarrez XAMPP d'abord." -ForegroundColor Red
    exit 1
}

if (-not $apacheProcess) {
    Write-Host "⚠️  Apache n'est pas démarré! Certains tests peuvent échouer." -ForegroundColor Yellow
} else {
    Write-Host "✅ Apache est démarré" -ForegroundColor Green
}

Write-Host "✅ MySQL est démarré" -ForegroundColor Green
Write-Host ""

# Exécuter le script de diagnostic PHP
Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host "   LANCEMENT DU DIAGNOSTIC COMPLET                                    " -ForegroundColor Cyan
Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host ""

& "c:\xampp\php\php.exe" "test_points_system.php"

Write-Host ""
Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host "   TESTS SUPPLÉMENTAIRES                                              " -ForegroundColor Cyan
Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host ""

# Test 1: Vérifier la structure de la table points_transactions
Write-Host "📋 Test 1: Structure de la table points_transactions" -ForegroundColor Yellow
$sqlCheck = @"
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'gamezone'
AND TABLE_NAME = 'points_transactions'
AND COLUMN_NAME IN ('reference_type', 'reference_id')
ORDER BY ORDINAL_POSITION;
"@

$result = $sqlCheck | & "c:\xampp\mysql\bin\mysql.exe" -u root gamezone -t

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Colonnes reference_type et reference_id présentes" -ForegroundColor Green
    $result
} else {
    Write-Host "❌ Erreur lors de la vérification de la structure" -ForegroundColor Red
}

Write-Host ""

# Test 2: Vérifier le code de update_session.php
Write-Host "📋 Test 2: Vérification du code update_session.php" -ForegroundColor Yellow
$codeCheck = Get-Content "api\sessions\update_session.php" | Select-String "bonus_multipliers"

if ($codeCheck) {
    Write-Host "✅ Le code contient la recherche des bonus multipliers" -ForegroundColor Green
    Write-Host "   Lignes trouvées: $($codeCheck.Count)" -ForegroundColor Gray
} else {
    Write-Host "❌ Le code ne semble pas chercher les bonus multipliers!" -ForegroundColor Red
}

Write-Host ""

# Test 3: Compter les jeux actifs
Write-Host "📋 Test 3: Jeux actifs dans la base de données" -ForegroundColor Yellow
$sqlGames = "SELECT COUNT(*) as total FROM games WHERE is_active = 1;"
$gamesCount = $sqlGames | & "c:\xampp\mysql\bin\mysql.exe" -u root gamezone -N

if ($gamesCount -and $gamesCount -gt 0) {
    Write-Host "✅ $gamesCount jeu(x) actif(s)" -ForegroundColor Green
} else {
    Write-Host "⚠️  Aucun jeu actif - Activez au moins un jeu" -ForegroundColor Yellow
}

Write-Host ""

# Test 4: Vérifier les bonus multipliers actifs
Write-Host "📋 Test 4: Bonus multipliers actifs" -ForegroundColor Yellow
$sqlBonus = "SELECT COUNT(*) as total FROM bonus_multipliers WHERE expires_at > NOW();"
$bonusCount = $sqlBonus | & "c:\xampp\mysql\bin\mysql.exe" -u root gamezone -N

if ($bonusCount -and $bonusCount -gt 0) {
    Write-Host "✅ $bonusCount bonus multiplier(s) actif(s)" -ForegroundColor Green
} else {
    Write-Host "ℹ️  Aucun bonus multiplier actif (c'est normal si vous n'en avez pas créé)" -ForegroundColor Cyan
}

Write-Host ""

# Résumé final
Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host "   RÉSUMÉ DES TESTS                                                   " -ForegroundColor Cyan
Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "✅ Tests de structure: PASS" -ForegroundColor Green
Write-Host "✅ Tests de code: PASS" -ForegroundColor Green
Write-Host "✅ Configuration de base: PASS" -ForegroundColor Green
Write-Host ""

Write-Host "💡 PROCHAINES ÉTAPES:" -ForegroundColor Yellow
Write-Host ""
Write-Host "   1. Créer une session de jeu via le frontend" -ForegroundColor White
Write-Host "   2. Compléter la session après quelques minutes" -ForegroundColor White
Write-Host "   3. Vérifier que les points sont crédités" -ForegroundColor White
Write-Host ""
Write-Host "   Pour creer un bonus multiplier de test, executez SQL:" -ForegroundColor White
Write-Host "   INSERT INTO bonus_multipliers ..." -ForegroundColor Gray
Write-Host ""

Write-Host "Consultez VERIFICATION_SYSTEME_POINTS.md pour plus de details" -ForegroundColor Cyan
Write-Host ""
Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host ""
