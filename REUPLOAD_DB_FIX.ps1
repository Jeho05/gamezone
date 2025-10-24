Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "    RE-UPLOAD db.php CORRIGE VERS INFINITYFREE" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

Write-Host "CORRECTION APPLIQUEE :" -ForegroundColor Yellow
Write-Host "  - db.php maintenant LIT le fichier .env" -ForegroundColor White
Write-Host "  - Connexion vers InfinityFree au lieu de localhost" -ForegroundColor White
Write-Host ""

Write-Host "FICHIERS A RE-UPLOADER (via FileZilla) :" -ForegroundColor Yellow
Write-Host "  1. backend_infinityfree/api/db.php" -ForegroundColor Cyan
Write-Host "  2. backend_infinityfree/api/diagnostic_env.php (nouveau)" -ForegroundColor Cyan
Write-Host ""

Write-Host "DESTINATION SUR SERVEUR :" -ForegroundColor Yellow
Write-Host "  /htdocs/api/" -ForegroundColor Cyan
Write-Host ""

Write-Host "TESTS APRES UPLOAD :" -ForegroundColor Yellow
Write-Host "  1. http://ismo.gamer.gd/api/diagnostic_env.php" -ForegroundColor Cyan
Write-Host "     (doit afficher les valeurs du .env)" -ForegroundColor Gray
Write-Host ""
Write-Host "  2. http://ismo.gamer.gd/api/health.php" -ForegroundColor Cyan
Write-Host "     (doit afficher 'healthy' avec connexion BD)" -ForegroundColor Gray
Write-Host ""
Write-Host "  3. http://ismo.gamer.gd/api/auth/check.php" -ForegroundColor Cyan
Write-Host "     (doit afficher 'authenticated: false')" -ForegroundColor Gray
Write-Host ""

Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "OUVREZ FILEZILLA ET UPLOADEZ LES 2 FICHIERS !" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

Write-Host "Connexion FileZilla :" -ForegroundColor Yellow
Write-Host "  Host : ftpupload.net" -ForegroundColor White
Write-Host "  User : if0_40238088" -ForegroundColor White
Write-Host "  Pass : OTnlRESWse7lVB" -ForegroundColor White
Write-Host ""

Write-Host "Duree estimee : 30 secondes" -ForegroundColor Green
Write-Host ""
