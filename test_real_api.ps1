# Test de l'API réelle redeem_with_points.php

Write-Host "=== TEST API RÉELLE ===" -ForegroundColor Cyan
Write-Host ""

$url = "http://localhost/projet%20ismo/api/shop/redeem_with_points.php"

try {
    $response = Invoke-WebRequest -Uri $url -Method GET -UseBasicParsing
    
    $json = $response.Content | ConvertFrom-Json
    
    Write-Host "✅ API répond avec succès!" -ForegroundColor Green
    Write-Host "Status Code: $($response.StatusCode)" -ForegroundColor Green
    Write-Host ""
    Write-Host "📦 Nombre de packages: $($json.count)" -ForegroundColor Yellow
    Write-Host ""
    
    if ($json.packages) {
        Write-Host "Liste des packages:" -ForegroundColor Cyan
        foreach ($pkg in $json.packages) {
            Write-Host "  🎁 $($pkg.reward_name)" -ForegroundColor White
            Write-Host "     Coût: $($pkg.points_cost) points" -ForegroundColor Gray
            Write-Host "     Durée: $($pkg.duration_minutes) min" -ForegroundColor Gray
            if ($pkg.promotional_label) {
                Write-Host "     Label: $($pkg.promotional_label)" -ForegroundColor Magenta
            }
            Write-Host ""
        }
    }
    
    # Sauvegarder la réponse
    $json | ConvertTo-Json -Depth 10 | Out-File "api_real_response.json" -Encoding UTF8
    Write-Host "✅ Réponse sauvegardée dans api_real_response.json" -ForegroundColor Green
    
} catch {
    Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Détails: $($_.Exception)" -ForegroundColor Red
}

Write-Host ""
