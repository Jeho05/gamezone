# Script PowerShell pour tester le système de récompenses

Write-Host "=== TEST DU SYSTEME DE RECOMPENSES ===" -ForegroundColor Cyan
Write-Host ""

# 1. Vérifier que XAMPP est en cours d'exécution
Write-Host "1. Vérification de XAMPP..." -ForegroundColor Yellow
$apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue

if (-not $apacheRunning) {
    Write-Host "   [X] Apache n'est pas en cours d'execution!" -ForegroundColor Red
    Write-Host "   Veuillez demarrer XAMPP et relancer ce script." -ForegroundColor Red
    exit 1
} else {
    Write-Host "   [OK] Apache en cours d'execution" -ForegroundColor Green
}

if (-not $mysqlRunning) {
    Write-Host "   [X] MySQL n'est pas en cours d'execution!" -ForegroundColor Red
    Write-Host "   Veuillez demarrer XAMPP et relancer ce script." -ForegroundColor Red
    exit 1
} else {
    Write-Host "   [OK] MySQL en cours d'execution" -ForegroundColor Green
}

# 2. Tester le backend
Write-Host ""
Write-Host "2. Test du backend..." -ForegroundColor Yellow
$testResult = & "c:\xampp\php\php.exe" "c:\xampp\htdocs\projet ismo\api\rewards\test_rewards_system.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "   [OK] Backend fonctionnel" -ForegroundColor Green
} else {
    Write-Host "   [X] Erreur backend" -ForegroundColor Red
    Write-Host $testResult
    exit 1
}

# 3. Vérifier si le frontend est en cours d'exécution
Write-Host ""
Write-Host "3. Verification du frontend..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:4000" -TimeoutSec 2 -ErrorAction SilentlyContinue
    Write-Host "   [OK] Frontend deja en cours d'execution sur http://localhost:4000" -ForegroundColor Green
    $frontendRunning = $true
} catch {
    Write-Host "   [!] Frontend non detecte" -ForegroundColor Yellow
    $frontendRunning = $false
}

# 4. Instructions pour le test manuel
Write-Host ""
Write-Host "=== INSTRUCTIONS DE TEST ===" -ForegroundColor Cyan
Write-Host ""

if (-not $frontendRunning) {
    Write-Host "ETAPE 1: Demarrer le frontend React" -ForegroundColor Yellow
    Write-Host "  cd 'c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web'" -ForegroundColor White
    Write-Host "  npm run dev" -ForegroundColor White
    Write-Host ""
}

Write-Host "ETAPE 2: Ouvrir le navigateur" -ForegroundColor Yellow
Write-Host "  URL: http://localhost:4000/player/gamification" -ForegroundColor White
Write-Host ""

Write-Host "ETAPE 3: Tester le systeme de recompenses" -ForegroundColor Yellow
Write-Host "  1. Cliquer sur l'onglet '🎁 Boutique'" -ForegroundColor White
Write-Host "  2. Verifier que les recompenses s'affichent" -ForegroundColor White
Write-Host "  3. Tester les filtres (Toutes, Accessibles, Indisponibles)" -ForegroundColor White
Write-Host "  4. Echanger une recompense si vous avez assez de points" -ForegroundColor White
Write-Host ""

Write-Host "=== ENDPOINTS API ===" -ForegroundColor Cyan
Write-Host "  GET:  http://localhost/projet%20ismo/api/rewards/index.php" -ForegroundColor White
Write-Host "  POST: http://localhost/projet%20ismo/api/rewards/redeem.php" -ForegroundColor White
Write-Host ""

Write-Host "=== DOCUMENTATION ===" -ForegroundColor Cyan
Write-Host "  Voir: TEST_SYSTEME_RECOMPENSES.md" -ForegroundColor White
Write-Host ""

# Option pour ouvrir le navigateur automatiquement
Write-Host "Voulez-vous ouvrir le navigateur maintenant? (O/N): " -NoNewline -ForegroundColor Yellow
$response = Read-Host

if ($response -eq "O" -or $response -eq "o") {
    Start-Process "http://localhost:4000/player/gamification"
    Write-Host ""
    Write-Host "[OK] Navigateur ouvert!" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== FIN ===" -ForegroundColor Cyan
