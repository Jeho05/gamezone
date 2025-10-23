# Script PowerShell pour lancer le diagnostic admin complet
# Ouvre le fichier de diagnostic dans le navigateur par défaut

Write-Host "═══════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "🔍 DIAGNOSTIC ADMIN - GAMEZONE" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$diagnosticFile = "$PSScriptRoot\DIAGNOSTIC_ADMIN_COMPLET.html"

if (Test-Path $diagnosticFile) {
    Write-Host "✅ Fichier de diagnostic trouvé" -ForegroundColor Green
    Write-Host "📂 Chemin: $diagnosticFile" -ForegroundColor Gray
    Write-Host ""
    Write-Host "🌐 Ouverture dans le navigateur..." -ForegroundColor Cyan
    
    Start-Process $diagnosticFile
    
    Write-Host ""
    Write-Host "═══════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "📋 INSTRUCTIONS:" -ForegroundColor Yellow
    Write-Host "═══════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "1. Connectez-vous avec les identifiants admin" -ForegroundColor White
    Write-Host "   Email: admin@gamezone.com" -ForegroundColor Gray
    Write-Host "   Mot de passe: Admin123!" -ForegroundColor Gray
    Write-Host ""
    Write-Host "2. Cliquez sur 'Tester Tous les Endpoints'" -ForegroundColor White
    Write-Host ""
    Write-Host "3. Examinez les résultats:" -ForegroundColor White
    Write-Host "   ✅ SUCCÈS = L'endpoint fonctionne correctement" -ForegroundColor Green
    Write-Host "   ❌ ERREUR = L'endpoint a un problème" -ForegroundColor Red
    Write-Host ""
    Write-Host "4. Exportez les résultats si nécessaire" -ForegroundColor White
    Write-Host ""
    Write-Host "═══════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "💡 ASTUCE:" -ForegroundColor Yellow
    Write-Host "═══════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "• Assurez-vous que XAMPP Apache est en cours d'exécution" -ForegroundColor Gray
    Write-Host "• Vérifiez que la base de données 'gamezone' existe" -ForegroundColor Gray
    Write-Host "• Si erreur 401/403: problème d'authentification" -ForegroundColor Gray
    Write-Host "• Si erreur 500: erreur serveur PHP" -ForegroundColor Gray
    Write-Host "• Si erreur réseau: vérifiez Apache" -ForegroundColor Gray
    Write-Host ""
    
} else {
    Write-Host "❌ Fichier de diagnostic introuvable!" -ForegroundColor Red
    Write-Host "Chemin attendu: $diagnosticFile" -ForegroundColor Gray
}

Write-Host ""
Write-Host "Appuyez sur une touche pour fermer..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
