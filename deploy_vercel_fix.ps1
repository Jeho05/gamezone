Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "    DÉPLOIEMENT FIX VERCEL 404 VERS GITHUB" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Aller dans le répertoire du projet
$projectPath = "C:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
Write-Host "📂 Navigation vers : $projectPath" -ForegroundColor Yellow
Set-Location $projectPath

# Vérifier le statut Git
Write-Host ""
Write-Host "📊 Statut Git actuel :" -ForegroundColor Yellow
git status

# Ajouter vercel.json
Write-Host ""
Write-Host "➕ Ajout de vercel.json..." -ForegroundColor Yellow
git add vercel.json

# Commit
Write-Host ""
Write-Host "💾 Création du commit..." -ForegroundColor Yellow
git commit -m "fix: simplify vercel.json to resolve 404 error"

# Push vers GitHub
Write-Host ""
Write-Host "🚀 Push vers GitHub..." -ForegroundColor Yellow
git push origin main

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "    ✅ DÉPLOIEMENT RÉUSSI !" -ForegroundColor Green
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
