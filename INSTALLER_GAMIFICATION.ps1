# Script d'installation du système de gamification GameZone
# Ce script applique la migration et vérifie que tout fonctionne

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Installation Système de Gamification" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier que XAMPP est démarré
Write-Host "[1/4] Vérification de MySQL..." -ForegroundColor Yellow
$mysqlRunning = Get-Process mysqld -ErrorAction SilentlyContinue
if (-not $mysqlRunning) {
    Write-Host "ERREUR: MySQL n'est pas démarré. Veuillez démarrer XAMPP." -ForegroundColor Red
    exit 1
}
Write-Host "✓ MySQL est actif" -ForegroundColor Green
Write-Host ""

# Appliquer la migration
Write-Host "[2/4] Application de la migration..." -ForegroundColor Yellow
$migrationScript = "c:\xampp\htdocs\projet ismo\api\migrations\apply_gamification.php"

if (-not (Test-Path $migrationScript)) {
    Write-Host "ERREUR: Script de migration introuvable: $migrationScript" -ForegroundColor Red
    exit 1
}

$result = php $migrationScript
Write-Host $result
Write-Host ""

# Vérifier que les tables ont été créées
Write-Host "[3/4] Vérification des tables..." -ForegroundColor Yellow
$checkScript = @"
<?php
require_once 'c:/xampp/htdocs/projet ismo/api/config.php';
`$pdo = get_db();
`$tables = ['badges', 'user_badges', 'levels', 'points_rules', 'login_streaks', 'bonus_multipliers', 'user_stats'];
`$success = true;
foreach (`$tables as `$table) {
    `$stmt = `$pdo->query("SHOW TABLES LIKE '`$table'");
    if (`$stmt->rowCount() === 0) {
        echo "✗ Table `$table manquante\n";
        `$success = false;
    }
}
if (`$success) {
    echo "✓ Toutes les tables sont présentes\n";
}
exit(`$success ? 0 : 1);
"@

$tempFile = [System.IO.Path]::GetTempFileName() + ".php"
$checkScript | Out-File -FilePath $tempFile -Encoding UTF8
$verifyResult = php $tempFile
Remove-Item $tempFile
Write-Host $verifyResult

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERREUR: Certaines tables n'ont pas été créées" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Test basique des endpoints
Write-Host "[4/4] Test des endpoints..." -ForegroundColor Yellow
Write-Host "→ Test de récupération des niveaux..." -ForegroundColor Gray

try {
    $response = Invoke-WebRequest -Uri "http://localhost/api/gamification/levels.php" -UseBasicParsing
    if ($response.StatusCode -eq 200) {
        Write-Host "✓ Endpoint levels.php fonctionne" -ForegroundColor Green
    }
} catch {
    Write-Host "✗ Erreur lors du test de levels.php: $_" -ForegroundColor Red
}

Write-Host "→ Test de récupération des badges..." -ForegroundColor Gray
try {
    $response = Invoke-WebRequest -Uri "http://localhost/api/gamification/badges.php" -UseBasicParsing
    if ($response.StatusCode -eq 200) {
        Write-Host "✓ Endpoint badges.php fonctionne" -ForegroundColor Green
    }
} catch {
    Write-Host "✗ Erreur lors du test de badges.php: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Installation terminée avec succès!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Prochaines étapes:" -ForegroundColor Yellow
Write-Host "1. Consultez SYSTEME_GAMIFICATION.md pour la documentation complète" -ForegroundColor White
Write-Host "2. Testez les endpoints avec Postman ou curl" -ForegroundColor White
Write-Host "3. Intégrez le système dans votre frontend" -ForegroundColor White
Write-Host ""
Write-Host "Endpoints disponibles:" -ForegroundColor Yellow
Write-Host "  - POST /api/gamification/award_points.php" -ForegroundColor Cyan
Write-Host "  - POST /api/gamification/login_streak.php" -ForegroundColor Cyan
Write-Host "  - GET  /api/gamification/badges.php" -ForegroundColor Cyan
Write-Host "  - GET  /api/gamification/levels.php" -ForegroundColor Cyan
Write-Host "  - GET  /api/gamification/user_stats.php" -ForegroundColor Cyan
Write-Host "  - POST /api/gamification/bonus_multiplier.php (admin)" -ForegroundColor Cyan
Write-Host ""
