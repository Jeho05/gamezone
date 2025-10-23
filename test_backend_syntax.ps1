# AUDIT SYNTAXE PHP - BACKEND
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "     AUDIT SYNTAXE PHP - BACKEND GAMEZONE" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

$phpPath = "c:\xampp\php\php.exe"
$totalFiles = 0
$passedFiles = 0
$failedFiles = 0
$errors = @()

# Scanner les fichiers PHP
$phpFiles = Get-ChildItem -Path "api" -Recurse -Include *.php -Exclude test_*,*_test.php -ErrorAction SilentlyContinue

Write-Host "Verification de la syntaxe PHP..." -ForegroundColor Yellow
Write-Host ""

foreach ($file in $phpFiles) {
    $totalFiles++
    $relativePath = $file.FullName.Replace((Get-Location).Path + "\", "")
    
    try {
        $result = & $phpPath -l $file.FullName 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            $passedFiles++
            Write-Host "." -NoNewline -ForegroundColor Green
        } else {
            $failedFiles++
            Write-Host "E" -NoNewline -ForegroundColor Red
            $errors += @{
                File = $relativePath
                Error = $result -join "`n"
            }
        }
    } catch {
        $failedFiles++
        Write-Host "E" -NoNewline -ForegroundColor Red
        $errors += @{
            File = $relativePath
            Error = $_.Exception.Message
        }
    }
    
    # Nouvelle ligne tous les 50 fichiers
    if ($totalFiles % 50 -eq 0) {
        Write-Host ""
    }
}

Write-Host ""
Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "                       RESULTATS" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Fichiers PHP analyses: $totalFiles" -ForegroundColor White
Write-Host "Fichiers valides: $passedFiles" -ForegroundColor Green
Write-Host "Fichiers avec erreurs: $failedFiles" -ForegroundColor $(if ($failedFiles -gt 0) { 'Red' } else { 'Green' })
Write-Host ""

if ($errors.Count -gt 0) {
    Write-Host "=== ERREURS DETECTEES ===" -ForegroundColor Red
    Write-Host ""
    foreach ($error in $errors) {
        Write-Host "Fichier: $($error.File)" -ForegroundColor Red
        Write-Host "Erreur: $($error.Error)" -ForegroundColor White
        Write-Host ("-" * 60) -ForegroundColor Gray
    }
    Write-Host ""
}

$percentage = if ($totalFiles -gt 0) { [math]::Round(($passedFiles / $totalFiles) * 100, 2) } else { 0 }

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "TAUX DE REUSSITE: $percentage% ($passedFiles/$totalFiles)" -ForegroundColor $(if ($percentage -eq 100) { 'Green' } elseif ($percentage -ge 95) { 'Yellow' } else { 'Red' })
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

if ($percentage -eq 100) {
    Write-Host "EXCELLENT! Aucune erreur de syntaxe PHP detectee!" -ForegroundColor Green
    exit 0
} elseif ($percentage -ge 95) {
    Write-Host "TRES BON! Quelques erreurs mineures a corriger." -ForegroundColor Yellow
    exit 0
} else {
    Write-Host "ATTENTION! Corrections requises." -ForegroundColor Red
    exit 1
}
