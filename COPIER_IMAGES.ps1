# Script pour copier les images dans le dossier public

Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "   COPIE DES IMAGES VERS PUBLIC          " -ForegroundColor Cyan
Write-Host "============================================`n" -ForegroundColor Cyan

$sourceDir = "images"
$targetDir = "createxyz-project\_\apps\web\public\images"

# Vérifier que le dossier source existe
if (-not (Test-Path $sourceDir)) {
    Write-Host "❌ Erreur: Le dossier 'images' n'existe pas!" -ForegroundColor Red
    Write-Host "   Chemin attendu: $(Get-Location)\images" -ForegroundColor Yellow
    exit 1
}

Write-Host "[1/3] Création du dossier de destination..." -ForegroundColor Yellow

# Créer le dossier de destination s'il n'existe pas
if (-not (Test-Path $targetDir)) {
    New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
    Write-Host "      ✅ Dossier créé: $targetDir" -ForegroundColor Green
} else {
    Write-Host "      ℹ️  Dossier existe déjà" -ForegroundColor Gray
}

Write-Host "`n[2/3] Copie des fichiers..." -ForegroundColor Yellow

# Copier tous les fichiers
try {
    Copy-Item -Path "$sourceDir\*" -Destination $targetDir -Recurse -Force
    Write-Host "      ✅ Copie terminée!" -ForegroundColor Green
} catch {
    Write-Host "      ❌ Erreur lors de la copie: $_" -ForegroundColor Red
    exit 1
}

Write-Host "`n[3/3] Vérification..." -ForegroundColor Yellow

# Compter les fichiers copiés
$videoCount = (Get-ChildItem "$targetDir\video" -File -ErrorAction SilentlyContinue).Count
$objetCount = (Get-ChildItem "$targetDir\objet" -File -ErrorAction SilentlyContinue).Count
$bossCount = (Get-ChildItem "$targetDir\gaming tof\Boss" -File -ErrorAction SilentlyContinue).Count

Write-Host "      📹 Vidéos copiées: $videoCount" -ForegroundColor Cyan
Write-Host "      🎮 Objets copiés: $objetCount" -ForegroundColor Cyan
Write-Host "      👤 Photos Boss copiées: $bossCount" -ForegroundColor Cyan

Write-Host "`n============================================" -ForegroundColor Green
Write-Host "   ✅ COPIE TERMINÉE AVEC SUCCÈS!         " -ForegroundColor Green
Write-Host "============================================`n" -ForegroundColor Green

Write-Host "📍 Les images sont maintenant dans:" -ForegroundColor Yellow
Write-Host "   $targetDir`n" -ForegroundColor White

Write-Host "🔄 Prochaines étapes:" -ForegroundColor Yellow
Write-Host "   1. Le serveur Vite devrait détecter les changements automatiquement" -ForegroundColor White
Write-Host "   2. Rafraîchir la page (Ctrl+F5)" -ForegroundColor White
Write-Host "   3. Tester: http://localhost:4000/`n" -ForegroundColor Cyan

Read-Host "Appuyez sur Entrée pour terminer"
