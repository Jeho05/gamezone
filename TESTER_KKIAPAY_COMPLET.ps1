# Script de Test Automatique Complet pour Kkiapay
# Exécute tous les tests et vérifie que tout fonctionne

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  TESTS KKIAPAY - VERIFICATION COMPLETE" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$baseUrl = "http://localhost/projet%20ismo"
$allTestsPassed = $true

# Fonction pour tester une URL
function Test-Url {
    param($url, $description)
    Write-Host "Test: $description..." -NoNewline
    try {
        $response = Invoke-WebRequest -Uri $url -Method GET -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
        if ($response.StatusCode -eq 200) {
            Write-Host " OK" -ForegroundColor Green
            return $true
        } else {
            Write-Host " ECHEC (Status: $($response.StatusCode))" -ForegroundColor Red
            return $false
        }
    } catch {
        Write-Host " ERREUR: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

Write-Host "ETAPE 1: Configuration automatique" -ForegroundColor Yellow
Write-Host "------------------------------------" -ForegroundColor Gray

# Vérifier que XAMPP/Apache est démarré
Write-Host "Verification Apache..." -NoNewline
$apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if ($apacheRunning) {
    Write-Host " OK" -ForegroundColor Green
} else {
    Write-Host " NON DEMARRE!" -ForegroundColor Red
    Write-Host ""
    Write-Host "ERREUR: Apache n'est pas démarré!" -ForegroundColor Red
    Write-Host "Demarrez Apache dans XAMPP Control Panel" -ForegroundColor Yellow
    Write-Host ""
    exit
}

# Vérifier MySQL
Write-Host "Verification MySQL..." -NoNewline
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if ($mysqlRunning) {
    Write-Host " OK" -ForegroundColor Green
} else {
    Write-Host " NON DEMARRE!" -ForegroundColor Red
    Write-Host ""
    Write-Host "ERREUR: MySQL n'est pas démarré!" -ForegroundColor Red
    Write-Host "Demarrez MySQL dans XAMPP Control Panel" -ForegroundColor Yellow
    Write-Host ""
    exit
}

Write-Host ""
Write-Host "ETAPE 2: Verification des fichiers" -ForegroundColor Yellow
Write-Host "------------------------------------" -ForegroundColor Gray

$files = @(
    @{Path="shop.html"; Desc="Page boutique principale"},
    @{Path="test_kkiapay_complet.html"; Desc="Page de test complete"},
    @{Path="test_kkiapay_direct.html"; Desc="Page de test direct"},
    @{Path="setup_kkiapay_complet.php"; Desc="Script de configuration"},
    @{Path="api\shop\payment_methods.php"; Desc="API méthodes de paiement"},
    @{Path="api\shop\create_purchase.php"; Desc="API création achat"}
)

foreach ($file in $files) {
    Write-Host "Fichier: $($file.Desc)..." -NoNewline
    if (Test-Path $file.Path) {
        Write-Host " OK" -ForegroundColor Green
    } else {
        Write-Host " MANQUANT!" -ForegroundColor Red
        $allTestsPassed = $false
    }
}

Write-Host ""
Write-Host "ETAPE 3: Configuration Backend" -ForegroundColor Yellow
Write-Host "------------------------------------" -ForegroundColor Gray

Write-Host "Configuration de la methode Kkiapay..." -NoNewline
try {
    # Note: Ce test nécessite que l'utilisateur soit connecté comme admin
    Write-Host " MANUEL" -ForegroundColor Yellow
    Write-Host "   -> Ouvrez: $baseUrl/setup_kkiapay_complet.php" -ForegroundColor Cyan
} catch {
    Write-Host " ERREUR" -ForegroundColor Red
}

Write-Host ""
Write-Host "ETAPE 4: Tests des URLs" -ForegroundColor Yellow
Write-Host "------------------------------------" -ForegroundColor Gray

$urls = @(
    @{Url="$baseUrl/test_kkiapay_complet.html"; Desc="Page test complete"},
    @{Url="$baseUrl/test_kkiapay_direct.html"; Desc="Page test direct"},
    @{Url="$baseUrl/shop.html"; Desc="Boutique"},
    @{Url="$baseUrl/api/shop/payment_methods.php"; Desc="API paiements"}
)

foreach ($url in $urls) {
    if (-not (Test-Url $url.Url $url.Desc)) {
        $allTestsPassed = $false
    }
}

Write-Host ""
Write-Host "ETAPE 5: Verification Script Kkiapay" -ForegroundColor Yellow
Write-Host "------------------------------------" -ForegroundColor Gray

Write-Host "Test CDN Kkiapay..." -NoNewline
try {
    $response = Invoke-WebRequest -Uri "https://cdn.kkiapay.me/k.js" -Method GET -UseBasicParsing -TimeoutSec 5
    if ($response.StatusCode -eq 200 -and $response.Content.Length -gt 1000) {
        Write-Host " OK" -ForegroundColor Green
        Write-Host "   -> Script CDN accessible (Taille: $($response.Content.Length) octets)" -ForegroundColor Gray
    } else {
        Write-Host " PROBLEME" -ForegroundColor Yellow
        $allTestsPassed = $false
    }
} catch {
    Write-Host " ERREUR" -ForegroundColor Red
    Write-Host "   -> Verifiez votre connexion internet" -ForegroundColor Red
    $allTestsPassed = $false
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan

if ($allTestsPassed) {
    Write-Host "  TOUS LES TESTS AUTOMATIQUES PASSES !" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "PROCHAINES ETAPES (TESTS MANUELS):" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "1. Configuration Backend:" -ForegroundColor White
    Write-Host "   -> Connectez-vous comme admin" -ForegroundColor Gray
    Write-Host "   -> Ouvrez: $baseUrl/setup_kkiapay_complet.php" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "2. Test Widget Complet:" -ForegroundColor White
    Write-Host "   -> Ouvrez: $baseUrl/test_kkiapay_complet.html" -ForegroundColor Cyan
    Write-Host "   -> Executez les 5 tests un par un" -ForegroundColor Gray
    Write-Host "   -> Verifiez que tous les tests passent" -ForegroundColor Gray
    Write-Host ""
    Write-Host "3. Test dans la Boutique:" -ForegroundColor White
    Write-Host "   -> Ouvrez: $baseUrl/shop.html" -ForegroundColor Cyan
    Write-Host "   -> Selectionnez un jeu et un package" -ForegroundColor Gray
    Write-Host "   -> Choisissez 'Kkiapay' comme methode de paiement" -ForegroundColor Gray
    Write-Host "   -> Verifiez que le widget s'affiche" -ForegroundColor Gray
    Write-Host ""
    Write-Host "4. Configuration Kkiapay:" -ForegroundColor White
    Write-Host "   Cle: b2f64170af2111f093307bbda24d6bac" -ForegroundColor Cyan
    Write-Host "   Callback: https://kkiapay-redirect.com" -ForegroundColor Cyan
    Write-Host "   Script: https://cdn.kkiapay.me/k.js" -ForegroundColor Cyan
    Write-Host ""
} else {
    Write-Host "  CERTAINS TESTS ONT ECHOUE !" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Veuillez corriger les erreurs ci-dessus avant de continuer." -ForegroundColor Yellow
    Write-Host ""
}

Write-Host "DOCUMENTATION COMPLETE:" -ForegroundColor Yellow
Write-Host "-> GUIDE_TEST_KKIAPAY_COMPLET.md" -ForegroundColor Cyan
Write-Host ""

# Proposer d'ouvrir la page de test
Write-Host "Voulez-vous ouvrir la page de test complete? (O/N): " -NoNewline -ForegroundColor Yellow
$response = Read-Host

if ($response -eq "O" -or $response -eq "o") {
    Start-Process "$baseUrl/test_kkiapay_complet.html"
    Write-Host ""
    Write-Host "Page de test ouverte dans votre navigateur!" -ForegroundColor Green
}

Write-Host ""
Write-Host "Appuyez sur une touche pour continuer..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
