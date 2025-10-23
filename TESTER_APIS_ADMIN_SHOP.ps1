# Script de test des APIs Admin Shop
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "TEST DES APIS ADMIN SHOP" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$baseUrl = "http://localhost/projet%20ismo/api"
$email = "admin@gmail.com"
$password = "demo123"

# Créer une session web
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

Write-Host "1. CONNEXION ADMIN..." -ForegroundColor Yellow
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
    
    if ($loginData.success -and $loginData.user.role -eq 'admin') {
        Write-Host "   ✅ Connexion admin réussie!" -ForegroundColor Green
        Write-Host "   Admin: $($loginData.user.username)" -ForegroundColor Gray
        Write-Host ""
    } else {
        Write-Host "   ❌ Pas un compte admin" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "   ❌ Erreur de connexion: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Test Games API
Write-Host "2. TEST GAMES API..." -ForegroundColor Yellow
try {
    $gamesResponse = Invoke-WebRequest `
        -Uri "$baseUrl/admin/games.php" `
        -Method GET `
        -WebSession $session `
        -ErrorAction Stop

    $gamesData = $gamesResponse.Content | ConvertFrom-Json
    
    if ($gamesData.games) {
        Write-Host "   ✅ Games API: $($gamesData.games.Count) jeu(x)" -ForegroundColor Green
        if ($gamesData.games.Count -gt 0) {
            Write-Host "   Exemples:" -ForegroundColor Gray
            $gamesData.games | Select-Object -First 3 | ForEach-Object {
                Write-Host "   - $($_.name) (ID: $($_.id), Actif: $($_.is_active))" -ForegroundColor White
            }
        }
    } else {
        Write-Host "   ⚠️  Aucun jeu trouvé" -ForegroundColor Yellow
    }
    Write-Host ""
} catch {
    Write-Host "   ❌ Erreur Games API: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        $errorData = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host "   Détails: $($errorData.error)" -ForegroundColor Gray
    }
    Write-Host ""
}

# Test Packages API
Write-Host "3. TEST PACKAGES API..." -ForegroundColor Yellow
try {
    $packagesResponse = Invoke-WebRequest `
        -Uri "$baseUrl/admin/game_packages.php" `
        -Method GET `
        -WebSession $session `
        -ErrorAction Stop

    $packagesData = $packagesResponse.Content | ConvertFrom-Json
    
    if ($packagesData.packages) {
        Write-Host "   ✅ Packages API: $($packagesData.packages.Count) package(s)" -ForegroundColor Green
        if ($packagesData.packages.Count -gt 0) {
            Write-Host "   Exemples:" -ForegroundColor Gray
            $packagesData.packages | Select-Object -First 3 | ForEach-Object {
                Write-Host "   - $($_.name) (Prix: $($_.price) XOF, Durée: $($_.duration_minutes)min)" -ForegroundColor White
            }
        }
    } else {
        Write-Host "   ⚠️  Aucun package trouvé" -ForegroundColor Yellow
    }
    Write-Host ""
} catch {
    Write-Host "   ❌ Erreur Packages API: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        $errorData = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host "   Détails: $($errorData.error)" -ForegroundColor Gray
    }
    Write-Host ""
}

# Test Payment Methods API
Write-Host "4. TEST PAYMENT METHODS API..." -ForegroundColor Yellow
try {
    $paymentResponse = Invoke-WebRequest `
        -Uri "$baseUrl/admin/payment_methods_simple.php" `
        -Method GET `
        -WebSession $session `
        -ErrorAction Stop

    $paymentData = $paymentResponse.Content | ConvertFrom-Json
    
    if ($paymentData.payment_methods) {
        Write-Host "   ✅ Payment Methods API: $($paymentData.payment_methods.Count) méthode(s)" -ForegroundColor Green
        if ($paymentData.payment_methods.Count -gt 0) {
            Write-Host "   Exemples:" -ForegroundColor Gray
            $paymentData.payment_methods | Select-Object -First 3 | ForEach-Object {
                Write-Host "   - $($_.name) (Actif: $($_.is_active))" -ForegroundColor White
            }
        }
    } else {
        Write-Host "   ⚠️  Aucune méthode de paiement trouvée" -ForegroundColor Yellow
    }
    Write-Host ""
} catch {
    Write-Host "   ❌ Erreur Payment Methods API: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        $errorData = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host "   Détails: $($errorData.error)" -ForegroundColor Gray
    }
    Write-Host ""
}

# Test Purchases API
Write-Host "5. TEST PURCHASES API..." -ForegroundColor Yellow
try {
    $purchasesResponse = Invoke-WebRequest `
        -Uri "$baseUrl/admin/purchases.php" `
        -Method GET `
        -WebSession $session `
        -ErrorAction Stop

    $purchasesData = $purchasesResponse.Content | ConvertFrom-Json
    
    if ($purchasesData.purchases) {
        Write-Host "   ✅ Purchases API: $($purchasesData.purchases.Count) achat(s)" -ForegroundColor Green
        if ($purchasesData.purchases.Count -gt 0) {
            Write-Host "   Exemples:" -ForegroundColor Gray
            $purchasesData.purchases | Select-Object -First 3 | ForEach-Object {
                Write-Host "   - Achat #$($_.id): $($_.game_name) ($($_.payment_status))" -ForegroundColor White
            }
        }
    } else {
        Write-Host "   ⚠️  Aucun achat trouvé" -ForegroundColor Yellow
    }
    Write-Host ""
} catch {
    Write-Host "   ❌ Erreur Purchases API: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        $errorData = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host "   Détails: $($errorData.error)" -ForegroundColor Gray
    }
    Write-Host ""
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "RÉSUMÉ" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Si toutes les APIs retournent des données, la page admin/shop" -ForegroundColor White
Write-Host "devrait s'afficher correctement après avoir ajouté 'use client'." -ForegroundColor White
Write-Host ""
Write-Host "Actions à faire:" -ForegroundColor Yellow
Write-Host "1. Redémarrer le serveur dev (npm run dev)" -ForegroundColor Gray
Write-Host "2. Vider le cache du navigateur (Ctrl+Shift+R)" -ForegroundColor Gray
Write-Host "3. Recharger http://localhost:4000/admin/shop" -ForegroundColor Gray
Write-Host ""
