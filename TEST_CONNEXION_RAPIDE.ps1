# TEST_CONNEXION_RAPIDE.ps1
# Test rapide de la connexion API et BDD

Write-Host "`n=== TEST DE CONNEXION API ET BDD ===" -ForegroundColor Cyan
Write-Host "Timestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray

# Test 1: Base de données
Write-Host "`n1. Test connexion base de donnees..." -ForegroundColor Yellow
$dbTestUrl = "http://localhost/projet%20ismo/api/test.php"
try {
    $response = Invoke-RestMethod -Uri $dbTestUrl -Method GET -ErrorAction Stop
    Write-Host "   ✓ BDD connectee: $($response.status)" -ForegroundColor Green
    Write-Host "   DB: $($response.database)" -ForegroundColor Gray
} catch {
    Write-Host "   ✗ Erreur BDD: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "   Details: $responseBody" -ForegroundColor Red
    }
}

# Test 2: Session check
Write-Host "`n2. Test session/auth..." -ForegroundColor Yellow
$authUrl = "http://localhost/projet%20ismo/api/auth/check.php"
try {
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $response = Invoke-RestMethod -Uri $authUrl -Method GET -WebSession $session -ErrorAction Stop
    Write-Host "   ✓ Auth endpoint accessible" -ForegroundColor Green
    Write-Host "   Authenticated: $($response.authenticated)" -ForegroundColor Gray
} catch {
    Write-Host "   ✗ Erreur auth: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 3: Endpoints admin
Write-Host "`n3. Test endpoints admin..." -ForegroundColor Yellow
$adminUrls = @(
    "http://localhost/projet%20ismo/api/admin/games.php",
    "http://localhost/projet%20ismo/api/admin/active_sessions.php"
)
foreach ($url in $adminUrls) {
    try {
        $response = Invoke-WebRequest -Uri $url -Method GET -ErrorAction Stop
        Write-Host "   ✓ $($url.Split('/')[-1]): Status $($response.StatusCode)" -ForegroundColor Green
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "   ⚠ $($url.Split('/')[-1]): Status $statusCode" -ForegroundColor Yellow
    }
}

# Test 4: Verifier Apache
Write-Host "`n4. Verification serveur Apache..." -ForegroundColor Yellow
$apacheTest = "http://localhost"
try {
    $response = Invoke-WebRequest -Uri $apacheTest -Method GET -TimeoutSec 2 -ErrorAction Stop
    Write-Host "   ✓ Apache accessible sur port 80" -ForegroundColor Green
} catch {
    Write-Host "   ✗ Apache non accessible: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "   → Verifiez que XAMPP Apache est demarre" -ForegroundColor Yellow
}

# Test 5: Verifier MySQL
Write-Host "`n5. Verification MySQL..." -ForegroundColor Yellow
$mysqlCheck = Get-Process -Name mysqld -ErrorAction SilentlyContinue
if ($mysqlCheck) {
    Write-Host "   ✓ MySQL en cours d'execution (PID: $($mysqlCheck.Id))" -ForegroundColor Green
} else {
    Write-Host "   ✗ MySQL non detecte" -ForegroundColor Red
    Write-Host "   → Verifiez que XAMPP MySQL est demarre" -ForegroundColor Yellow
}

Write-Host "`n=== FIN DES TESTS ===" -ForegroundColor Cyan
Write-Host "`nSi vous voyez des erreurs:" -ForegroundColor Yellow
Write-Host "1. Verifiez que XAMPP Apache et MySQL sont demarres" -ForegroundColor White
Write-Host "2. Verifiez les identifiants BDD dans api/config.php" -ForegroundColor White
Write-Host "3. Actualisez la page dans le navigateur (Ctrl+Shift+R)" -ForegroundColor White
Write-Host ""
