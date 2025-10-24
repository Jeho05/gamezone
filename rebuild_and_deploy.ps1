Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "    REBUILD + DEPLOY FRONTEND AVEC index.html" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Aller dans le répertoire du projet
$projectPath = "C:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
Write-Host "📂 Navigation vers : $projectPath" -ForegroundColor Yellow
Set-Location $projectPath

# Build local pour tester
Write-Host ""
Write-Host "🔨 Build local (test)..." -ForegroundColor Yellow
Write-Host "Cela peut prendre 1-2 minutes..." -ForegroundColor Gray
npm run build

# Vérifier que index.html existe
$indexPath = "build/client/index.html"
if (Test-Path $indexPath) {
    Write-Host ""
    Write-Host "✅ index.html créé avec succès !" -ForegroundColor Green
    Write-Host "   → $indexPath" -ForegroundColor Cyan
} else {
    Write-Host ""
    Write-Host "❌ ERREUR : index.html n'a pas été créé !" -ForegroundColor Red
    Write-Host "   → Le build a peut-être échoué" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Voulez-vous continuer le déploiement quand même ? (O/N)" -ForegroundColor Yellow
    $response = Read-Host
    if ($response -ne "O" -and $response -ne "o") {
        Write-Host ""
        Write-Host "Déploiement annulé." -ForegroundColor Red
        exit
    }
}

# Git add
Write-Host ""
Write-Host "➕ Ajout des fichiers modifiés..." -ForegroundColor Yellow
git add .

# Commit
Write-Host ""
Write-Host "💾 Création du commit..." -ForegroundColor Yellow
git commit -m "fix: add index.html template and update vite config for static deployment"

# Push
Write-Host ""
Write-Host "🚀 Push vers GitHub..." -ForegroundColor Yellow
git push origin main

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "    ✅ DÉPLOIEMENT LANCÉ !" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

Write-Host "📋 PROCHAINES ÉTAPES :" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Vercel va automatiquement redéployer (2-3 minutes)" -ForegroundColor White
Write-Host ""
Write-Host "2. Vérifiez le déploiement :" -ForegroundColor White
Write-Host "   https://vercel.com/dashboard" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. Attendez que le status soit 'Ready'" -ForegroundColor White
Write-Host ""
Write-Host "4. Testez votre site :" -ForegroundColor White
Write-Host "   https://gamezone-jada.vercel.app/" -ForegroundColor Cyan
Write-Host ""

Write-Host "⏱️  Temps d'attente : 2-3 minutes" -ForegroundColor Green
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
