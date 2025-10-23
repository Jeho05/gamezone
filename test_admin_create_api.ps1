# Test de création de récompense via l'API admin

Write-Host "=== TEST CRÉATION RÉCOMPENSE VIA API ADMIN ===" -ForegroundColor Cyan
Write-Host ""

$apiUrl = "http://localhost/projet%20ismo/api/admin/rewards.php"

$body = @{
    name = "TEST Admin PowerShell - 90min"
    description = "Récompense créée via test PowerShell pour vérifier le flow"
    cost = 350
    reward_type = "game_package"
    category = "gaming"
    available = 1
    is_featured = 1
    display_order = 1
    # Champs spécifiques game_package
    game_id = 1
    duration_minutes = 90
    points_earned = 30
    bonus_multiplier = 2.0
    is_promotional = 1
    promotional_label = "💎 ULTIMATE"
} | ConvertTo-Json

Write-Host "📝 Données à envoyer:" -ForegroundColor Yellow
Write-Host $body -ForegroundColor Gray
Write-Host ""

try {
    Write-Host "🚀 Envoi de la requête..." -ForegroundColor Yellow
    
    $response = Invoke-WebRequest `
        -Uri $apiUrl `
        -Method POST `
        -Body $body `
        -ContentType "application/json" `
        -UseBasicParsing
    
    Write-Host "✅ Réponse reçue (Status: $($response.StatusCode))" -ForegroundColor Green
    Write-Host ""
    
    $result = $response.Content | ConvertFrom-Json
    
    if ($result.success) {
        Write-Host "========================================" -ForegroundColor Green
        Write-Host "  ✅ SUCCÈS!" -ForegroundColor Green
        Write-Host "========================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "Message: $($result.message)" -ForegroundColor White
        Write-Host "Reward ID: $($result.reward_id)" -ForegroundColor Cyan
        Write-Host "Package ID: $($result.package_id)" -ForegroundColor Cyan
        Write-Host ""
        
        # Vérifier dans l'API joueur
        Write-Host "🔍 Vérification dans l'API joueur..." -ForegroundColor Yellow
        $checkUrl = "http://localhost/projet%20ismo/test_backend_api_direct.php"
        $checkResponse = Invoke-WebRequest -Uri $checkUrl -UseBasicParsing
        $packages = ($checkResponse.Content | ConvertFrom-Json).packages
        
        $newPackage = $packages | Where-Object { $_.id -eq $result.package_id }
        
        if ($newPackage) {
            Write-Host "✅ La nouvelle récompense EST VISIBLE!" -ForegroundColor Green
            Write-Host ""
            Write-Host "📦 Détails:" -ForegroundColor Cyan
            Write-Host "   Nom: $($newPackage.reward_name)" -ForegroundColor White
            Write-Host "   Package: $($newPackage.package_name)" -ForegroundColor White
            Write-Host "   Jeu: $($newPackage.game_name)" -ForegroundColor White
            Write-Host "   Coût: $($newPackage.points_cost) points" -ForegroundColor White
            Write-Host "   Durée: $($newPackage.duration_minutes) minutes" -ForegroundColor White
            Write-Host "   Bonus: +$($newPackage.points_earned) points" -ForegroundColor White
            if ($newPackage.promotional_label) {
                Write-Host "   Label: $($newPackage.promotional_label)" -ForegroundColor Magenta
            }
        } else {
            Write-Host "❌ La nouvelle récompense N'EST PAS visible!" -ForegroundColor Red
        }
        
    } else {
        Write-Host "❌ ÉCHEC!" -ForegroundColor Red
        Write-Host "Erreur: $($result.error)" -ForegroundColor Red
        if ($result.details) {
            Write-Host "Détails: $($result.details)" -ForegroundColor Red
        }
    }
    
} catch {
    Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Détails:" -ForegroundColor Yellow
    Write-Host $_.Exception -ForegroundColor Gray
}

Write-Host ""
