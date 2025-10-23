# AUDIT FRONTEND REACT/NEXT.JS - PowerShell
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "     AUDIT FRONTEND REACT/NEXT.JS - SYSTEME GAMEZONE" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

$frontendPath = "createxyz-project\_\apps\web\src"
$totalFiles = 0
$jsxFiles = 0
$errors = @()
$warnings = @()
$passed = 0

Write-Host "[SCAN] Analyse des fichiers frontend..." -ForegroundColor Yellow
Write-Host ""

# Vérifier que le répertoire existe
if (-not (Test-Path $frontendPath)) {
    Write-Host "ERREUR: Repertoire frontend non trouve: $frontendPath" -ForegroundColor Red
    exit 1
}

# Scanner les fichiers
$files = Get-ChildItem -Path $frontendPath -Recurse -Include *.jsx,*.tsx,*.js,*.ts -Exclude node_modules,*.test.*,*.spec.* | Where-Object { $_.FullName -notmatch 'node_modules|\.next|dist|build' }

foreach ($file in $files) {
    $totalFiles++
    $relativePath = $file.FullName.Replace((Get-Location).Path + "\", "")
    
    if ($file.Extension -in @('.jsx', '.tsx')) {
        $jsxFiles++
    }
    
    try {
        $content = Get-Content $file.FullName -Raw -ErrorAction Stop
        
        # Vérifications basiques
        $hasError = $false
        
        # Vérifier console.log (warning)
        if ($content -match 'console\.log\(') {
            $warnings += @{
                File = $relativePath
                Message = "Console.log trouve (a retirer en prod)"
            }
        }
        
        # Vérifier debugger (erreur)
        if ($content -match '\bdebugger\b') {
            $errors += @{
                File = $relativePath
                Message = "Debugger trouve (a retirer)"
            }
            $hasError = $true
        }
        
        # Vérifier imports React manquants
        if ($content -match '\buseState\b' -and $content -notmatch "import.*from\s+['\"]react['\"]") {
            $errors += @{
                File = $relativePath
                Message = "Import React potentiellement manquant"
            }
            $hasError = $true
        }
        
        # Vérifier fetch sans error handling
        if ($content -match 'fetch\(' -and $content -notmatch '\.catch\(') {
            $warnings += @{
                File = $relativePath
                Message = "Fetch sans gestion d'erreur apparente"
            }
        }
        
        if (-not $hasError) {
            $passed++
        }
        
    } catch {
        $errors += @{
            File = $relativePath
            Message = "Erreur de lecture: $($_.Exception.Message)"
        }
    }
}

# Afficher les résultats
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "                       RESULTATS" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Fichiers analyses: $totalFiles" -ForegroundColor White
Write-Host "  - Fichiers JSX/TSX: $jsxFiles" -ForegroundColor Gray
Write-Host "  - Fichiers JS/TS: $($totalFiles - $jsxFiles)" -ForegroundColor Gray
Write-Host ""
Write-Host "Fichiers sans probleme: $passed" -ForegroundColor Green
Write-Host "Fichiers avec erreurs: $($errors.Count)" -ForegroundColor $(if ($errors.Count -gt 0) { 'Red' } else { 'Green' })
Write-Host "Avertissements: $($warnings.Count)" -ForegroundColor Yellow
Write-Host ""

if ($errors.Count -gt 0) {
    Write-Host "=== ERREURS DETECTEES ===" -ForegroundColor Red
    $errors | Select-Object -First 10 | ForEach-Object {
        Write-Host "  $($_.File)" -ForegroundColor Red
        Write-Host "    $($_.Message)" -ForegroundColor White
    }
    if ($errors.Count -gt 10) {
        Write-Host "  ... et $($errors.Count - 10) autres erreurs" -ForegroundColor Red
    }
    Write-Host ""
}

if ($warnings.Count -gt 0 -and $warnings.Count -le 20) {
    Write-Host "=== AVERTISSEMENTS ===" -ForegroundColor Yellow
    $warnings | Select-Object -First 10 | ForEach-Object {
        Write-Host "  $($_.File)" -ForegroundColor Yellow
        Write-Host "    $($_.Message)" -ForegroundColor White
    }
    if ($warnings.Count -gt 10) {
        Write-Host "  ... et $($warnings.Count - 10) autres avertissements" -ForegroundColor Yellow
    }
    Write-Host ""
}

$percentage = if ($totalFiles -gt 0) { [math]::Round(($passed / $totalFiles) * 100, 2) } else { 0 }

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "TAUX DE REUSSITE: $percentage% ($passed/$totalFiles)" -ForegroundColor $(if ($percentage -eq 100) { 'Green' } elseif ($percentage -ge 90) { 'Yellow' } else { 'Red' })
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

if ($percentage -eq 100) {
    Write-Host "EXCELLENT! Frontend sans erreurs critiques!" -ForegroundColor Green
} elseif ($percentage -ge 90) {
    Write-Host "TRES BON! Quelques optimisations recommandees." -ForegroundColor Yellow
} elseif ($percentage -ge 70) {
    Write-Host "BON! Des corrections sont necessaires." -ForegroundColor Yellow
} else {
    Write-Host "ATTENTION! Corrections importantes requises." -ForegroundColor Red
}

Write-Host ""

# Vérifier les composants critiques
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "     VERIFICATION DES COMPOSANTS CRITIQUES" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

$criticalComponents = @(
    "app\player\shop\[gameId]\page.jsx",
    "app\player\profile\page.jsx",
    "app\player\rewards\page.jsx",
    "app\player\my-purchases\page.jsx",
    "app\player\my-reservations\page.jsx",
    "app\admin\dashboard\page.jsx",
    "app\admin\shop\page.jsx",
    "app\admin\invoice-scanner\page.jsx",
    "components\InvoiceModal.jsx",
    "components\KkiapayWidget.jsx",
    "utils\apiBase.js",
    "utils\avatarUrl.js",
    "utils\gameImageUrl.js"
)

$missingComponents = @()
$existingComponents = @()

foreach ($component in $criticalComponents) {
    $componentPath = Join-Path $frontendPath $component
    if (Test-Path $componentPath) {
        Write-Host "[OK] $component" -ForegroundColor Green
        $existingComponents += $component
    } else {
        Write-Host "[MANQUANT] $component" -ForegroundColor Red
        $missingComponents += $component
    }
}

Write-Host ""
Write-Host "Composants critiques: $($existingComponents.Count)/$($criticalComponents.Count)" -ForegroundColor $(if ($missingComponents.Count -eq 0) { 'Green' } else { 'Red' })

if ($errors.Count -eq 0 -and $missingComponents.Count -eq 0) {
    Write-Host ""
    Write-Host "FRONTEND VALIDE! Tous les tests sont passes." -ForegroundColor Green
    exit 0
} else {
    Write-Host ""
    Write-Host "Frontend necessite des corrections." -ForegroundColor Yellow
    exit 1
}
