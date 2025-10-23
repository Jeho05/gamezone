Write-Host "=== CORRECTION BUILD VERCEL ===" -ForegroundColor Green
Write-Host ""

# Ajout des changements
Write-Host "Ajout des changements..." -ForegroundColor Yellow
git add createxyz-project/_/apps/web/package.json

# Commit
Write-Host "Création du commit..." -ForegroundColor Yellow
git commit -m "Fix: Move vite-plugin-babel to dependencies for Vercel build"

# Push
Write-Host "Push vers GitHub..." -ForegroundColor Yellow
git push

Write-Host ""
Write-Host "=== FAIT ! ===" -ForegroundColor Green
Write-Host ""
Write-Host "Vercel va automatiquement redéployer avec les bonnes dépendances !" -ForegroundColor Cyan
Write-Host "Attendez 2-3 minutes et vérifiez votre dashboard Vercel." -ForegroundColor Cyan
