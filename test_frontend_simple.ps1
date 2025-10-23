# AUDIT FRONTEND REACT/NEXT.JS - Version Simple
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "     AUDIT FRONTEND REACT/NEXT.JS - SYSTEME GAMEZONE" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

$frontendPath = "createxyz-project\_\apps\web\src"

# Verifier que le repertoire existe
if (-not (Test-Path $frontendPath)) {
    Write-Host "ERREUR: Repertoire frontend non trouve" -ForegroundColor Red
    exit 1
}

# Scanner les fichiers
$allFiles = Get-ChildItem -Path $frontendPath -Recurse -Include *.jsx,*.tsx,*.js,*.ts -ErrorAction SilentlyContinue | Where-Object { $_.FullName -notlike '*node_modules*' -and $_.FullName -notlike '*.next*' }

$totalFiles = $allFiles.Count
$jsxFiles = ($allFiles | Where-Object { $_.Extension -in @('.jsx', '.tsx') }).Count
$jsFiles = $totalFiles - $jsxFiles

Write-Host "Fichiers analyses: $totalFiles" -ForegroundColor White
Write-Host "  - Fichiers JSX/TSX: $jsxFiles" -ForegroundColor Gray
Write-Host "  - Fichiers JS/TS: $jsFiles" -ForegroundColor Gray
Write-Host ""

# Verifier les composants critiques
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "     VERIFICATION DES COMPOSANTS CRITIQUES" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

$criticalPaths = @{
    "Page Shop Jeu" = "app\player\shop\[gameId]\page.jsx"
    "Page Profil Joueur" = "app\player\profile\page.jsx"
    "Page Recompenses" = "app\player\rewards\page.jsx"
    "Page Mes Achats" = "app\player\my-purchases\page.jsx"
    "Page Mes Reservations" = "app\player\my-reservations\page.jsx"
    "Dashboard Admin" = "app\admin\dashboard\page.jsx"
    "Admin Shop" = "app\admin\shop\page.jsx"
    "Scanner Factures" = "app\admin\invoice-scanner\page.jsx"
    "Modal Facture" = "components\InvoiceModal.jsx"
    "Widget KkiaPay" = "components\KkiapayWidget.jsx"
    "API Base" = "utils\apiBase.js"
    "Avatar URL Utils" = "utils\avatarUrl.js"
    "Game Image Utils" = "utils\gameImageUrl.js"
}

$passed = 0
$failed = 0

foreach ($key in $criticalPaths.Keys) {
    $componentPath = Join-Path $frontendPath $criticalPaths[$key]
    if (Test-Path $componentPath) {
        Write-Host "[OK] $key" -ForegroundColor Green
        $passed++
    } else {
        Write-Host "[MANQUANT] $key - $($criticalPaths[$key])" -ForegroundColor Red
        $failed++
    }
}

Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
$percentage = [math]::Round(($passed / ($passed + $failed)) * 100, 2)
Write-Host "COMPOSANTS CRITIQUES: $passed/$($passed + $failed) ($percentage%)" -ForegroundColor $(if ($percentage -eq 100) { 'Green' } else { 'Yellow' })
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

# Verifier la structure des dossiers
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "     VERIFICATION STRUCTURE DES DOSSIERS" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

$criticalDirs = @(
    "app\player",
    "app\admin",
    "components",
    "utils",
    "hooks"
)

foreach ($dir in $criticalDirs) {
    $dirPath = Join-Path $frontendPath $dir
    if (Test-Path $dirPath) {
        $filesInDir = (Get-ChildItem -Path $dirPath -Recurse -Include *.jsx,*.tsx,*.js,*.ts -ErrorAction SilentlyContinue).Count
        Write-Host "[OK] $dir ($filesInDir fichiers)" -ForegroundColor Green
    } else {
        Write-Host "[MANQUANT] $dir" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "                     RESULTAT FINAL" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Total fichiers frontend: $totalFiles" -ForegroundColor White
Write-Host "Composants critiques: $passed/$($passed + $failed)" -ForegroundColor $(if ($failed -eq 0) { 'Green' } else { 'Yellow' })
Write-Host ""

if ($failed -eq 0 -and $totalFiles -gt 0) {
    Write-Host "EXCELLENT! Tous les composants critiques sont presents!" -ForegroundColor Green
    exit 0
} elseif ($passed -ge ($passed + $failed) * 0.9) {
    Write-Host "TRES BON! Structure frontend complete a $percentage%" -ForegroundColor Yellow
    exit 0
} else {
    Write-Host "ATTENTION! Certains composants critiques sont manquants." -ForegroundColor Red
    exit 1
}
