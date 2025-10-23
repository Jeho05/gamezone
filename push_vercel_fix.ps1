Write-Host "=== PUSH CONFIGURATION VERCEL ===" -ForegroundColor Green
Write-Host ""

git add -A
git commit -m "Fix: Update vercel.json with correct backend URL"
git push

Write-Host ""
Write-Host "=== FAIT ! ===" -ForegroundColor Green
Write-Host "Vercel va redeployer avec la bonne URL backend !" -ForegroundColor Cyan
