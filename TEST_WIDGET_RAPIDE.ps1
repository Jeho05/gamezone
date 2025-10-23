# Test rapide du widget KkiaPay
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  TEST RAPIDE WIDGET KKIAPAY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Vérifier que le serveur dev tourne
Write-Host "Vérification du serveur dev..." -NoNewline
try {
    $response = Invoke-WebRequest -Uri "http://localhost:4000" -TimeoutSec 2 -UseBasicParsing -ErrorAction Stop
    Write-Host " OK (Port 4000 actif)" -ForegroundColor Green
} catch {
    Write-Host " ERREUR" -ForegroundColor Red
    Write-Host "Le serveur dev n'est pas démarré sur le port 4000" -ForegroundColor Yellow
    Write-Host "Lancez: cd createxyz-project\_\apps\web && npm run dev" -ForegroundColor White
    Write-Host ""
    exit
}

# 2. Vérifier la clé API dans .env.local
Write-Host "Vérification de la clé API KkiaPay..." -NoNewline
$envPath = "createxyz-project\_\apps\web\.env.local"
if (Test-Path $envPath) {
    $content = Get-Content $envPath -Raw
    if ($content -match "NEXT_PUBLIC_KKIAPAY_PUBLIC_KEY=9d566a94b64a9a8ebf552e4a4a8acdecf0d3337383") {
        Write-Host " OK" -ForegroundColor Green
    } else {
        Write-Host " MANQUANTE" -ForegroundColor Red
        Write-Host "La clé API n'est pas configurée correctement" -ForegroundColor Yellow
    }
} else {
    Write-Host " FICHIER MANQUANT" -ForegroundColor Red
}

# 3. Vérifier le composant KkiapayWidget
Write-Host "Vérification du composant KkiapayWidget..." -NoNewline
$widgetPath = "createxyz-project\_\apps\web\src\components\KkiapayWidget.jsx"
if (Test-Path $widgetPath) {
    $widgetContent = Get-Content $widgetPath -Raw
    if ($widgetContent -match "Payer Maintenant") {
        Write-Host " OK (bouton présent)" -ForegroundColor Green
    } else {
        Write-Host " ANCIEN VERSION" -ForegroundColor Yellow
    }
} else {
    Write-Host " MANQUANT" -ForegroundColor Red
}

# 4. Vérifier le script KkiaPay dans root.tsx
Write-Host "Vérification du script KkiaPay..." -NoNewline
$rootPath = "createxyz-project\_\apps\web\src\app\root.tsx"
if (Test-Path $rootPath) {
    $rootContent = Get-Content $rootPath -Raw
    if ($rootContent -match "cdn\.kkiapay\.me/k\.js") {
        Write-Host " OK" -ForegroundColor Green
    } else {
        Write-Host " MANQUANT" -ForegroundColor Red
    }
} else {
    Write-Host " FICHIER MANQUANT" -ForegroundColor Red
}

# 5. Vérifier la page de paiement
Write-Host "Vérification de la page de paiement..." -NoNewline
$pagePath = "createxyz-project\_\apps\web\src\app\player\shop\[gameId]\page.jsx"
if (Test-Path $pagePath) {
    $pageContent = Get-Content $pagePath -Raw
    if ($pageContent -match "mtn_momo" -and $pageContent -match "KkiapayWidget") {
        Write-Host " OK (tous providers supportés)" -ForegroundColor Green
    } else {
        Write-Host " MISE A JOUR NECESSAIRE" -ForegroundColor Yellow
    }
} else {
    Write-Host " FICHIER MANQUANT" -ForegroundColor Red
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  INSTRUCTIONS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. REDEMARREZ le serveur dev si ce n'est pas déjà fait:" -ForegroundColor Yellow
Write-Host "   cd createxyz-project\_\apps\web" -ForegroundColor White
Write-Host "   Ctrl+C (pour arrêter)" -ForegroundColor White
Write-Host "   npm run dev" -ForegroundColor White
Write-Host ""
Write-Host "2. Ouvrez votre navigateur:" -ForegroundColor Yellow
Write-Host "   http://localhost:4000/player/shop" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. Sélectionnez un jeu et un package" -ForegroundColor Yellow
Write-Host ""
Write-Host "4. Choisissez 'MTN Mobile Money' comme méthode" -ForegroundColor Yellow
Write-Host ""
Write-Host "5. Vous devriez voir:" -ForegroundColor Yellow
Write-Host "   ┌────────────────────────────┐" -ForegroundColor Gray
Write-Host "   │ 💳 Payer Maintenant        │" -ForegroundColor Green
Write-Host "   └────────────────────────────┘" -ForegroundColor Gray
Write-Host ""
Write-Host "6. Si le bouton n'apparaît pas:" -ForegroundColor Yellow
Write-Host "   - Appuyez sur F12 (console)" -ForegroundColor White
Write-Host "   - Cherchez les erreurs en rouge" -ForegroundColor White
Write-Host "   - Vérifiez que window.openKkiapayWidget existe" -ForegroundColor White
Write-Host ""
Write-Host "Documentation: DEMARRAGE_RAPIDE_KKIAPAY.md" -ForegroundColor Cyan
Write-Host ""
Write-Host "Appuyez sur une touche pour continuer..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
