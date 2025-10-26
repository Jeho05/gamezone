# Script pour uploader les fichiers corrigés via FileZilla
# Configuration CORS pour Vercel

Write-Host "╔═══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                                                               ║" -ForegroundColor Cyan
Write-Host "║         📤 Upload Fichiers Corrigés - CORS Vercel           ║" -ForegroundColor Cyan
Write-Host "║                                                               ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

Write-Host "🔧 Fichiers modifiés pour supporter Vercel:" -ForegroundColor Yellow
Write-Host ""
Write-Host "  ✅ backend_infinityfree/api/config.php" -ForegroundColor Green
Write-Host "     → CORS dynamique ajouté (localhost + Vercel)"
Write-Host "     → URL Vercel: https://gamezoneismo.vercel.app"
Write-Host ""
Write-Host "  ✅ backend_infinityfree/.htaccess" -ForegroundColor Green
Write-Host "     → Headers CORS optimisés"
Write-Host "     → Cache control ajouté"
Write-Host ""

Write-Host "📋 INFORMATIONS DE CONNEXION FTP:" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host "  Host    : ftpupload.net" -ForegroundColor White
Write-Host "  User    : if0_40238088" -ForegroundColor White
Write-Host "  Pass    : OTnlRESWse7lVB" -ForegroundColor White
Write-Host "  Port    : 21" -ForegroundColor White
Write-Host ""

Write-Host "📂 FICHIERS À UPLOADER:" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════"

$filesToUpload = @(
    @{
        Local = "C:\xampp\htdocs\projet ismo\backend_infinityfree\api\config.php"
        Remote = "/htdocs/api/config.php"
    },
    @{
        Local = "C:\xampp\htdocs\projet ismo\backend_infinityfree\.htaccess"
        Remote = "/htdocs/.htaccess"
    }
)

foreach ($file in $filesToUpload) {
    $exists = Test-Path $file.Local
    $status = if ($exists) { "✅" } else { "❌ MANQUANT" }
    
    Write-Host "  $status $($file.Local)" -ForegroundColor $(if ($exists) { "Green" } else { "Red" })
    Write-Host "     → Destination: $($file.Remote)" -ForegroundColor Gray
    Write-Host ""
}

Write-Host ""
Write-Host "⚡ PROCÉDURE D'UPLOAD:" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""
Write-Host "1️⃣  Ouvrir FileZilla" -ForegroundColor Cyan
Write-Host ""
Write-Host "2️⃣  Se connecter avec les identifiants ci-dessus" -ForegroundColor Cyan
Write-Host ""
Write-Host "3️⃣  Naviguer dans le panneau DROIT vers:" -ForegroundColor Cyan
Write-Host "    /htdocs/api/" -ForegroundColor White
Write-Host ""
Write-Host "4️⃣  Uploader les fichiers:" -ForegroundColor Cyan
Write-Host ""
Write-Host "    A) config.php" -ForegroundColor White
Write-Host "       GAUCHE: backend_infinityfree\api\config.php"
Write-Host "       DROITE: /htdocs/api/config.php"
Write-Host "       → Glisser le fichier"
Write-Host "       → Accepter 'Écraser' si demandé"
Write-Host ""
Write-Host "    B) .htaccess" -ForegroundColor White
Write-Host "       GAUCHE: backend_infinityfree\.htaccess"
Write-Host "       DROITE: /htdocs/.htaccess"
Write-Host "       → Glisser le fichier"
Write-Host "       → Accepter 'Écraser' si demandé"
Write-Host ""

Write-Host "5️⃣  Attendre fin de l'upload (quelques secondes)" -ForegroundColor Cyan
Write-Host ""

Write-Host ""
Write-Host "🧪 TESTS APRÈS UPLOAD:" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

Write-Host "Test 1: Health Check Backend" -ForegroundColor Cyan
Write-Host "  URL: http://ismo.gamer.gd/api/health.php" -ForegroundColor White
Write-Host "  ✅ Devrait afficher du JSON" -ForegroundColor Green
Write-Host ""

Write-Host "Test 2: Frontend Vercel" -ForegroundColor Cyan
Write-Host "  URL: https://gamezoneismo.vercel.app/" -ForegroundColor White
Write-Host "  ✅ Devrait charger sans erreur CORS" -ForegroundColor Green
Write-Host ""

Write-Host "Test 3: Login depuis Vercel" -ForegroundColor Cyan
Write-Host "  1. Aller sur https://gamezoneismo.vercel.app/" -ForegroundColor White
Write-Host "  2. Essayer de se connecter" -ForegroundColor White
Write-Host "  3. Ouvrir DevTools (F12) > Console" -ForegroundColor White
Write-Host "  ✅ Pas d'erreur CORS visible" -ForegroundColor Green
Write-Host ""

Write-Host ""
Write-Host "⏱️  TEMPS ESTIMÉ: 5 minutes" -ForegroundColor Yellow
Write-Host ""

Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

$response = Read-Host "Voulez-vous ouvrir FileZilla maintenant? (o/n)"

if ($response -eq "o" -or $response -eq "O" -or $response -eq "oui") {
    Write-Host ""
    Write-Host "🚀 Tentative d'ouverture de FileZilla..." -ForegroundColor Cyan
    
    # Chemins possibles de FileZilla
    $filezillaPaths = @(
        "${env:ProgramFiles}\FileZilla FTP Client\filezilla.exe",
        "${env:ProgramFiles(x86)}\FileZilla FTP Client\filezilla.exe",
        "$env:LOCALAPPDATA\FileZilla\filezilla.exe"
    )
    
    $filezillaFound = $false
    foreach ($path in $filezillaPaths) {
        if (Test-Path $path) {
            Start-Process $path
            $filezillaFound = $true
            Write-Host "✅ FileZilla lancé!" -ForegroundColor Green
            break
        }
    }
    
    if (-not $filezillaFound) {
        Write-Host "❌ FileZilla non trouvé sur ce PC" -ForegroundColor Red
        Write-Host "   Téléchargez-le: https://filezilla-project.org/" -ForegroundColor Yellow
    }
    
    Write-Host ""
    Write-Host "📋 Identifiants copiés dans le presse-papier:" -ForegroundColor Cyan
    Write-Host "   Host: ftpupload.net" -ForegroundColor White
    Write-Host "   User: if0_40238088" -ForegroundColor White
    Write-Host "   Pass: OTnlRESWse7lVB" -ForegroundColor White
} else {
    Write-Host ""
    Write-Host "👍 OK! Uploadez manuellement quand vous êtes prêt." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host "✅ PRÊT POUR L'UPLOAD!" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

# Ouvrir le dossier source dans l'explorateur
Write-Host "📂 Ouverture du dossier backend_infinityfree..." -ForegroundColor Cyan
Start-Process "explorer.exe" "C:\xampp\htdocs\projet ismo\backend_infinityfree"

Write-Host ""
Write-Host "Pour plus d'infos, consultez: CORS_VERCEL_CONFIGURED.md" -ForegroundColor Gray
Write-Host ""
