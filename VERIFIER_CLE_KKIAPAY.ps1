# Script pour vérifier la clé API Kkiapay
# Teste si la clé est valide et détermine son environnement

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  VERIFICATION CLE API KKIAPAY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$apiKey = "b2f64170af2111f093307bbda24d6bac"

Write-Host "Cle testee: $apiKey" -ForegroundColor Yellow
Write-Host ""

# Test 1: Vérifier si le script CDN est accessible
Write-Host "[1/4] Test CDN Kkiapay..." -NoNewline
try {
    $response = Invoke-WebRequest -Uri "https://cdn.kkiapay.me/k.js" -Method GET -UseBasicParsing -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Write-Host " OK" -ForegroundColor Green
        Write-Host "   -> Script k.js accessible (Taille: $($response.Content.Length) octets)" -ForegroundColor Gray
    }
} catch {
    Write-Host " ERREUR" -ForegroundColor Red
    Write-Host "   -> $($_.Exception.Message)" -ForegroundColor Red
}

# Test 2: Analyser la clé
Write-Host ""
Write-Host "[2/4] Analyse de la cle..." -ForegroundColor Yellow
Write-Host "   -> Longueur: $($apiKey.Length) caracteres" -ForegroundColor Gray
Write-Host "   -> Format: " -NoNewline -ForegroundColor Gray

if ($apiKey.Length -eq 40) {
    Write-Host "Valide (40 caracteres)" -ForegroundColor Green
} else {
    Write-Host "Inhabituel (attendu: 40)" -ForegroundColor Yellow
}

# Test 3: Détecter l'environnement probable
Write-Host ""
Write-Host "[3/4] Detection de l'environnement..." -ForegroundColor Yellow

# Les clés sandbox Kkiapay commencent souvent par certains préfixes
# Note: Ceci est une estimation, seul Kkiapay peut confirmer
$firstChars = $apiKey.Substring(0, [Math]::Min(10, $apiKey.Length))
Write-Host "   -> Premiers caracteres: $firstChars" -ForegroundColor Gray

# Test 4: Recommandations
Write-Host ""
Write-Host "[4/4] Recommandations..." -ForegroundColor Yellow
Write-Host ""
Write-Host "TESTS A EFFECTUER:" -ForegroundColor Cyan
Write-Host "==================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Testez avec sandbox='true':" -ForegroundColor White
Write-Host "   -> Ouvrez: http://localhost/projet%20ismo/test_kkiapay_debug.html" -ForegroundColor Cyan
Write-Host "   -> Cliquez sur Test 1 (Widget avec sandbox='true')" -ForegroundColor Gray
Write-Host "   -> Si OK: Utilisez sandbox='true' partout" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Testez avec sandbox='false':" -ForegroundColor White
Write-Host "   -> Cliquez sur Test 3 (Widget avec sandbox='false')" -ForegroundColor Gray
Write-Host "   -> Si OK: Utilisez sandbox='false' partout" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Testez sans parametre sandbox:" -ForegroundColor White
Write-Host "   -> Cliquez sur Test 2 (Widget sans sandbox)" -ForegroundColor Gray
Write-Host "   -> Si OK: Laissez Kkiapay detecter automatiquement" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Verifiez dans votre Dashboard Kkiapay:" -ForegroundColor White
Write-Host "   -> https://app.kkiapay.me" -ForegroundColor Cyan
Write-Host "   -> Allez dans Parametres > API Keys" -ForegroundColor Gray
Write-Host "   -> Verifiez si cette cle est SANDBOX ou LIVE" -ForegroundColor Gray
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Proposer d'ouvrir la page de test
Write-Host "Voulez-vous ouvrir la page de debug maintenant? (O/N): " -NoNewline -ForegroundColor Yellow
$response = Read-Host

if ($response -eq "O" -or $response -eq "o") {
    Start-Process "http://localhost/projet%20ismo/test_kkiapay_debug.html"
    Write-Host ""
    Write-Host "Page de debug ouverte!" -ForegroundColor Green
    Write-Host ""
    Write-Host "INSTRUCTIONS:" -ForegroundColor Yellow
    Write-Host "-------------" -ForegroundColor Gray
    Write-Host "1. Testez les 6 widgets/boutons" -ForegroundColor White
    Write-Host "2. Notez quel test NE montre PAS 'cle incorrecte'" -ForegroundColor White
    Write-Host "3. Revenez ici et notez le resultat" -ForegroundColor White
    Write-Host ""
}

Write-Host ""
Write-Host "NUMEROS DE TEST (si mode sandbox):" -ForegroundColor Yellow
Write-Host "-----------------------------------" -ForegroundColor Gray
Write-Host "Succes:  97000000 (ou 97xxxxxxxx)" -ForegroundColor Green
Write-Host "Echec:   96000000 (ou 96xxxxxxxx)" -ForegroundColor Red
Write-Host "OTP:     123456" -ForegroundColor Cyan
Write-Host ""

Write-Host "Appuyez sur une touche pour continuer..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
