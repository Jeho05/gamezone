git commit -m "Fix: Move all Babel packages to dependencies for production build"
git push
Write-Host "=== POUSSE ! ===" -ForegroundColor Green
Write-Host "Vercel va automatiquement rebuilder avec TOUS les packages Babel." -ForegroundColor Cyan
