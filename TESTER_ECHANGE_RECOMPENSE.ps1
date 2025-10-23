# Script de test pour l'échange de récompenses
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "TEST D'ÉCHANGE DE RÉCOMPENSE" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$baseUrl = "http://localhost/projet%20ismo/api"
$email = "test5@example.com"  # Utilisateur avec des points
$password = "password"  # Remplacez par le vrai mot de passe

Write-Host "1. TEST DE CONNEXION..." -ForegroundColor Yellow
Write-Host "   Email: $email"

# Créer une session web pour conserver les cookies
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

# Connexion
try {
    $loginBody = @{
        email = $email
        password = $password
    } | ConvertTo-Json

    $loginResponse = Invoke-WebRequest `
        -Uri "$baseUrl/auth/login.php" `
        -Method POST `
        -ContentType "application/json" `
        -Body $loginBody `
        -WebSession $session `
        -ErrorAction Stop

    $loginData = $loginResponse.Content | ConvertFrom-Json
    
    if ($loginData.success) {
        Write-Host "   ✅ Connexion réussie!" -ForegroundColor Green
        Write-Host "   Utilisateur: $($loginData.user.username)" -ForegroundColor Gray
        Write-Host "   Points: $($loginData.user.points)" -ForegroundColor Gray
        Write-Host ""
    } else {
        Write-Host "   ❌ Échec de connexion: $($loginData.error)" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "   ❌ Erreur de connexion: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Récupérer les packages disponibles
Write-Host "2. RÉCUPÉRATION DES PACKAGES DISPONIBLES..." -ForegroundColor Yellow

try {
    $packagesResponse = Invoke-WebRequest `
        -Uri "$baseUrl/shop/redeem_with_points.php" `
        -Method GET `
        -WebSession $session `
        -ErrorAction Stop

    $packagesData = $packagesResponse.Content | ConvertFrom-Json
    
    if ($packagesData.packages.Count -gt 0) {
        Write-Host "   ✅ $($packagesData.packages.Count) package(s) disponible(s)" -ForegroundColor Green
        Write-Host "   Points actuels: $($packagesData.user_points)" -ForegroundColor Gray
        Write-Host ""
        
        # Afficher les packages
        Write-Host "   PACKAGES DISPONIBLES:" -ForegroundColor Cyan
        foreach ($pkg in $packagesData.packages) {
            Write-Host "   - [$($pkg.id)] $($pkg.reward_name) - $($pkg.game_name)" -ForegroundColor White
            Write-Host "     Coût: $($pkg.points_cost) points | Durée: $($pkg.duration_minutes) min" -ForegroundColor Gray
        }
        Write-Host ""
        
        # Sélectionner le premier package
        $selectedPackage = $packagesData.packages[0]
        Write-Host "3. TEST D'ÉCHANGE..." -ForegroundColor Yellow
        Write-Host "   Package sélectionné: $($selectedPackage.reward_name)" -ForegroundColor Gray
        Write-Host "   Coût: $($selectedPackage.points_cost) points" -ForegroundColor Gray
        
        if ($packagesData.user_points -lt $selectedPackage.points_cost) {
            Write-Host "   ⚠️  Points insuffisants pour cet échange" -ForegroundColor Yellow
            Write-Host "   Nécessaire: $($selectedPackage.points_cost) | Disponible: $($packagesData.user_points)" -ForegroundColor Yellow
            exit 0
        }
        
        # Effectuer l'échange
        $redeemBody = @{
            package_id = $selectedPackage.id
        } | ConvertTo-Json

        $redeemResponse = Invoke-WebRequest `
            -Uri "$baseUrl/shop/redeem_with_points.php" `
            -Method POST `
            -ContentType "application/json" `
            -Body $redeemBody `
            -WebSession $session `
            -ErrorAction Stop

        $redeemData = $redeemResponse.Content | ConvertFrom-Json
        
        if ($redeemData.success) {
            Write-Host ""
            Write-Host "   ✅ ÉCHANGE RÉUSSI!" -ForegroundColor Green
            Write-Host ""
            Write-Host "   DÉTAILS:" -ForegroundColor Cyan
            Write-Host "   - Purchase ID: $($redeemData.purchase_id)" -ForegroundColor White
            Write-Host "   - Jeu: $($redeemData.game_name)" -ForegroundColor White
            Write-Host "   - Package: $($redeemData.package_name)" -ForegroundColor White
            Write-Host "   - Durée: $($redeemData.duration_minutes) minutes" -ForegroundColor White
            Write-Host "   - Points dépensés: $($redeemData.points_spent)" -ForegroundColor White
            Write-Host "   - Points restants: $($redeemData.remaining_points)" -ForegroundColor White
            Write-Host "   - Points à gagner: $($redeemData.points_earned)" -ForegroundColor White
            Write-Host ""
            Write-Host "   Message: $($redeemData.message)" -ForegroundColor Gray
        } else {
            Write-Host "   ❌ Échec de l'échange: $($redeemData.error)" -ForegroundColor Red
            if ($redeemData.details) {
                Write-Host "   Détails: $($redeemData.details)" -ForegroundColor Gray
            }
        }
        
    } else {
        Write-Host "   ⚠️  Aucun package disponible" -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "   ❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
    
    # Afficher les détails de l'erreur si disponible
    if ($_.ErrorDetails.Message) {
        try {
            $errorData = $_.ErrorDetails.Message | ConvertFrom-Json
            Write-Host "   Erreur API: $($errorData.error)" -ForegroundColor Red
            if ($errorData.details) {
                Write-Host "   Détails: $($errorData.details)" -ForegroundColor Gray
            }
        } catch {
            Write-Host "   Détails: $($_.ErrorDetails.Message)" -ForegroundColor Gray
        }
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "TEST TERMINÉ" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
