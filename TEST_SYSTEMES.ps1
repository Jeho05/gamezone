# Script de test pour tous les systèmes

Write-Host "=== Test des Systèmes GameZone ===" -ForegroundColor Green

$baseUrl = "http://localhost/projet%20ismo/api"

# Test 1: Créer les tables
Write-Host "`n1. Installation des tables..." -ForegroundColor Yellow
php install_complete_system.php

# Test 2: Corriger l'encodage
Write-Host "`n2. Correction de l'encodage..." -ForegroundColor Yellow
php fix_encoding.php

# Test 3: Vérifier les récompenses
Write-Host "`n3. Test des récompenses..." -ForegroundColor Yellow
$response = Invoke-WebRequest -Uri "$baseUrl/rewards/index.php?available=1" -UseBasicParsing
Write-Host "Récompenses disponibles: $($response.Content)"

# Test 4: Vérifier les packages
Write-Host "`n4. Test des packages de points..." -ForegroundColor Yellow
$response = Invoke-WebRequest -Uri "$baseUrl/shop/points_packages.php" -UseBasicParsing
Write-Host "Packages: $($response.Content)"

Write-Host "`n=== Tests terminés ===" -ForegroundColor Green
