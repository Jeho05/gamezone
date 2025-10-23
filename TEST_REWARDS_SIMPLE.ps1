# Script de test simplifie - Systeme Recompenses + Packages
# Execute: .\TEST_REWARDS_SIMPLE.ps1

Write-Host "================================" -ForegroundColor Cyan
Write-Host "TEST SYSTEME REWARDS + PACKAGES" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

$files = @(
    "api\migrations\add_reward_game_packages.sql",
    "api\admin\rewards.php",
    "api\shop\redeem_with_points.php",
    "admin\rewards_manager.html",
    "SYSTEME_REWARDS_PACKAGES_POINTS.md"
)

Write-Host "[1] Verification des fichiers..." -ForegroundColor Green
Write-Host ""

$allFilesExist = $true
foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "  OK - $file" -ForegroundColor Green
    } else {
        Write-Host "  MANQUANT - $file" -ForegroundColor Red
        $allFilesExist = $false
    }
}

Write-Host ""

if ($allFilesExist) {
    Write-Host "Tous les fichiers sont presents!" -ForegroundColor Green
} else {
    Write-Host "ERREUR: Certains fichiers sont manquants!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2] URLs a tester:" -ForegroundColor Green
Write-Host ""

$baseUrl = "http://localhost/projet%20ismo"

Write-Host "Interface Admin:" -ForegroundColor Cyan
Write-Host "$baseUrl/admin/rewards_manager.html" -ForegroundColor White
Write-Host ""

Write-Host "API Liste packages:" -ForegroundColor Cyan
Write-Host "$baseUrl/api/shop/redeem_with_points.php" -ForegroundColor White
Write-Host ""

Write-Host "API Admin rewards:" -ForegroundColor Cyan
Write-Host "$baseUrl/api/admin/rewards.php" -ForegroundColor White
Write-Host ""

Write-Host "[3] IMPORTANT - Migration SQL:" -ForegroundColor Yellow
Write-Host ""
Write-Host "Vous devez appliquer la migration SQL manuellement:" -ForegroundColor White
Write-Host "1. Ouvrir phpMyAdmin: http://localhost/phpmyadmin" -ForegroundColor Gray
Write-Host "2. Selectionner la base 'gamezone'" -ForegroundColor Gray
Write-Host "3. Aller dans l'onglet SQL" -ForegroundColor Gray
Write-Host "4. Copier-coller le contenu du fichier:" -ForegroundColor Gray
Write-Host "   api\migrations\add_reward_game_packages.sql" -ForegroundColor Cyan
Write-Host "5. Cliquer Executer" -ForegroundColor Gray
Write-Host ""

Write-Host "[4] Test rapide:" -ForegroundColor Yellow
Write-Host ""
Write-Host "Apres avoir applique la migration SQL:" -ForegroundColor White
Write-Host ""
Write-Host "1. Ouvrir l'interface admin" -ForegroundColor Gray
Write-Host "2. Cliquer 'Nouvelle Recompense'" -ForegroundColor Gray
Write-Host "3. Selectionner type 'Package de Jeu'" -ForegroundColor Gray
Write-Host "4. Remplir les champs et enregistrer" -ForegroundColor Gray
Write-Host ""

Write-Host "[5] Verification en base de donnees:" -ForegroundColor Yellow
Write-Host ""
Write-Host "Executer dans phpMyAdmin:" -ForegroundColor White
Write-Host ""
Write-Host "SELECT * FROM point_packages;" -ForegroundColor Cyan
Write-Host "SELECT * FROM points_redemption_history;" -ForegroundColor Cyan
Write-Host ""

Write-Host "================================" -ForegroundColor Green
Write-Host "VERIFICATION COMPLETE" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host ""

$openBrowser = Read-Host "Voulez-vous ouvrir l'interface admin? (o/n)"
if ($openBrowser -eq "o" -or $openBrowser -eq "O") {
    Start-Process "$baseUrl/admin/rewards_manager.html"
    Write-Host ""
    Write-Host "Interface ouverte dans le navigateur!" -ForegroundColor Green
}

Write-Host ""
Write-Host "Documentation complete: SYSTEME_REWARDS_PACKAGES_POINTS.md" -ForegroundColor Cyan
Write-Host ""
