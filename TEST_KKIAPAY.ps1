# Script de test pour l'intégration KkiaPay
# Vérifie que tous les fichiers nécessaires sont en place

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  TEST INTEGRATION KKIAPAY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$allOk = $true

# 1. Vérifier le script dans root.tsx
Write-Host "[1/6] Vérification du script KkiaPay dans root.tsx..." -NoNewline
$rootFile = "createxyz-project\_\apps\web\src\app\root.tsx"
if (Test-Path $rootFile) {
    $content = Get-Content $rootFile -Raw
    if ($content -match "cdn\.kkiapay\.me/k\.js") {
        Write-Host " OK" -ForegroundColor Green
    } else {
        Write-Host " MANQUANT" -ForegroundColor Red
        $allOk = $false
    }
} else {
    Write-Host " FICHIER NON TROUVE" -ForegroundColor Red
    $allOk = $false
}

# 2. Vérifier le composant KkiapayWidget
Write-Host "[2/6] Vérification du composant KkiapayWidget.jsx..." -NoNewline
$widgetFile = "createxyz-project\_\apps\web\src\components\KkiapayWidget.jsx"
if (Test-Path $widgetFile) {
    Write-Host " OK" -ForegroundColor Green
} else {
    Write-Host " MANQUANT" -ForegroundColor Red
    $allOk = $false
}

# 3. Vérifier la configuration frontend
Write-Host "[3/6] Vérification de la configuration frontend (.env.local)..." -NoNewline
$envFile = "createxyz-project\_\apps\web\.env.local"
if (Test-Path $envFile) {
    $content = Get-Content $envFile -Raw
    if ($content -match "NEXT_PUBLIC_KKIAPAY_PUBLIC_KEY" -and $content -match "9d566a94b64a9a8ebf552e4a4a8acdecf0d3337383") {
        Write-Host " OK" -ForegroundColor Green
    } else {
        Write-Host " CLE API MANQUANTE" -ForegroundColor Yellow
        $allOk = $false
    }
} else {
    Write-Host " FICHIER NON TROUVE" -ForegroundColor Red
    $allOk = $false
}

# 4. Vérifier l'intégration dans la page de paiement
Write-Host "[4/6] Vérification de l'intégration dans page.jsx..." -NoNewline
$pageFile = "createxyz-project\_\apps\web\src\app\player\shop\[gameId]\page.jsx"
if (Test-Path $pageFile) {
    $content = Get-Content $pageFile -Raw
    if ($content -match "KkiapayWidget" -and $content -match "import.*KkiapayWidget") {
        Write-Host " OK" -ForegroundColor Green
    } else {
        Write-Host " WIDGET NON IMPORTE" -ForegroundColor Red
        $allOk = $false
    }
} else {
    Write-Host " FICHIER NON TROUVE" -ForegroundColor Red
    $allOk = $false
}

# 5. Vérifier le backend
Write-Host "[5/6] Vérification de la configuration backend..." -NoNewline
$backendFile = "api\shop\create_purchase.php"
if (Test-Path $backendFile) {
    $content = Get-Content $backendFile -Raw
    if ($content -match "kkiapay" -and $content -match "KKIAPAY_PUBLIC_KEY") {
        Write-Host " OK" -ForegroundColor Green
    } else {
        Write-Host " CONFIGURATION INCOMPLETE" -ForegroundColor Yellow
    }
} else {
    Write-Host " FICHIER NON TROUVE" -ForegroundColor Red
    $allOk = $false
}

# 6. Vérifier la documentation
Write-Host "[6/6] Vérification de la documentation..." -NoNewline
$docFile = "INTEGRATION_KKIAPAY.md"
if (Test-Path $docFile) {
    Write-Host " OK" -ForegroundColor Green
} else {
    Write-Host " MANQUANTE" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan

if ($allOk) {
    Write-Host "  INTEGRATION COMPLETE !" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Prochaines étapes:" -ForegroundColor Yellow
    Write-Host "1. Configurez les variables d'environnement backend (voir .htaccess.example)" -ForegroundColor White
    Write-Host "2. Créez une méthode de paiement 'KkiaPay' dans l'admin" -ForegroundColor White
    Write-Host "3. Testez un paiement en mode sandbox" -ForegroundColor White
    Write-Host ""
    Write-Host "Documentation complète: INTEGRATION_KKIAPAY.md" -ForegroundColor Cyan
} else {
    Write-Host "  PROBLEMES DETECTES !" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Veuillez corriger les erreurs ci-dessus." -ForegroundColor Yellow
    Write-Host "Consultez la documentation: INTEGRATION_KKIAPAY.md" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "Appuyez sur une touche pour continuer..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
