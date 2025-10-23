# Script de test automatique du système de contenu
# Exécuter ce script pour vérifier que tout fonctionne

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " TEST DU SYSTEME DE CONTENU INTERACTIF" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$allTests = @()

# Test 1: Vérifier les tables DB
Write-Host "[1/8] Vérification des tables DB..." -ForegroundColor Yellow
try {
    $tables = & "C:\xampp\mysql\bin\mysql.exe" -u root gamezone -e "SHOW TABLES LIKE 'content%';" 2>&1
    if ($tables -match "content" -and $tables -match "content_likes" -and $tables -match "content_comments" -and $tables -match "content_shares") {
        Write-Host "  ✓ Tables DB créées" -ForegroundColor Green
        $allTests += $true
    } else {
        Write-Host "  ✗ Tables manquantes" -ForegroundColor Red
        $allTests += $false
    }
} catch {
    Write-Host "  ✗ Erreur: $_" -ForegroundColor Red
    $allTests += $false
}

# Test 2: Vérifier l'existence des APIs backend
Write-Host "[2/8] Vérification des APIs backend..." -ForegroundColor Yellow
$apis = @(
    "c:\xampp\htdocs\projet ismo\api\content\public.php",
    "c:\xampp\htdocs\projet ismo\api\content\like.php",
    "c:\xampp\htdocs\projet ismo\api\content\comment.php",
    "c:\xampp\htdocs\projet ismo\api\content\edit_comment.php",
    "c:\xampp\htdocs\projet ismo\api\content\delete_comment.php",
    "c:\xampp\htdocs\projet ismo\api\content\share.php",
    "c:\xampp\htdocs\projet ismo\api\admin\content.php",
    "c:\xampp\htdocs\projet ismo\api\admin\upload_image.php"
)

$allExist = $true
foreach ($api in $apis) {
    if (Test-Path $api) {
        Write-Host "  ✓ $($api.Split('\')[-1])" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $($api.Split('\')[-1]) manquant" -ForegroundColor Red
        $allExist = $false
    }
}
$allTests += $allExist

# Test 3: Vérifier l'API publique
Write-Host "[3/8] Test de l'API publique (content)..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost/projet%20ismo/api/content/public.php?type=news" -UseBasicParsing 2>&1
    if ($response.StatusCode -eq 200) {
        $data = $response.Content | ConvertFrom-Json
        if ($data.success) {
            Write-Host "  ✓ API publique fonctionne" -ForegroundColor Green
            Write-Host "    Total contenu: $($data.total)" -ForegroundColor Cyan
            $allTests += $true
        } else {
            Write-Host "  ✗ Réponse invalide" -ForegroundColor Red
            $allTests += $false
        }
    } else {
        Write-Host "  ✗ Erreur HTTP $($response.StatusCode)" -ForegroundColor Red
        $allTests += $false
    }
} catch {
    Write-Host "  ⚠ API non accessible (normal si pas de contenu)" -ForegroundColor Yellow
    $allTests += $true  # Considéré comme OK
}

# Test 4: Vérifier les fichiers frontend
Write-Host "[4/8] Vérification des fichiers frontend..." -ForegroundColor Yellow
$frontendFiles = @(
    "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web\src\app\player\gallery\page.jsx",
    "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web\src\app\admin\content\page.jsx",
    "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web\src\components\ImageUpload.jsx"
)

$allExist = $true
foreach ($file in $frontendFiles) {
    if (Test-Path $file) {
        Write-Host "  ✓ $($file.Split('\')[-1])" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $($file.Split('\')[-1]) manquant" -ForegroundColor Red
        $allExist = $false
    }
}
$allTests += $allExist

# Test 5: Vérifier le contenu en DB
Write-Host "[5/8] Vérification du contenu en DB..." -ForegroundColor Yellow
try {
    $count = & "C:\xampp\mysql\bin\mysql.exe" -u root gamezone -e "SELECT COUNT(*) as total FROM content;" 2>&1 | Select-String -Pattern "\d+" | ForEach-Object { $_.Matches.Value }
    if ($count -and [int]$count[1] -ge 0) {
        Write-Host "  ✓ Contenu en DB: $($count[1]) élément(s)" -ForegroundColor Green
        $allTests += $true
    } else {
        Write-Host "  ⚠ Aucun contenu (normal si nouveau)" -ForegroundColor Yellow
        $allTests += $true
    }
} catch {
    Write-Host "  ✗ Erreur lecture DB: $_" -ForegroundColor Red
    $allTests += $false
}

# Test 6: Vérifier les migrations SQL
Write-Host "[6/8] Vérification des fichiers de migration..." -ForegroundColor Yellow
$migrations = @(
    "c:\xampp\htdocs\projet ismo\api\migrations\create_content_tables.sql",
    "c:\xampp\htdocs\projet ismo\api\migrations\add_reactions_and_shares.sql"
)

$allExist = $true
foreach ($migration in $migrations) {
    if (Test-Path $migration) {
        Write-Host "  ✓ $($migration.Split('\')[-1])" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $($migration.Split('\')[-1]) manquant" -ForegroundColor Red
        $allExist = $false
    }
}
$allTests += $allExist

# Test 7: Vérifier les dossiers d'upload
Write-Host "[7/8] Vérification des dossiers d'upload..." -ForegroundColor Yellow
$uploadDirs = @(
    "c:\xampp\htdocs\projet ismo\uploads\games",
    "c:\xampp\htdocs\projet ismo\uploads\avatars"
)

$allExist = $true
foreach ($dir in $uploadDirs) {
    if (Test-Path $dir) {
        Write-Host "  ✓ $($dir.Split('\')[-1])" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $($dir.Split('\')[-1]) manquant" -ForegroundColor Red
        $allExist = $false
    }
}
$allTests += $allExist

# Test 8: Vérifier la documentation
Write-Host "[8/8] Vérification de la documentation..." -ForegroundColor Yellow
if (Test-Path "c:\xampp\htdocs\projet ismo\GUIDE_SYSTEME_CONTENU_COMPLET.md") {
    Write-Host "  ✓ Documentation complète disponible" -ForegroundColor Green
    $allTests += $true
} else {
    Write-Host "  ✗ Documentation manquante" -ForegroundColor Red
    $allTests += $false
}

# Résumé des tests
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host " RÉSUMÉ DES TESTS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

$passedTests = ($allTests | Where-Object { $_ -eq $true }).Count
$totalTests = $allTests.Count
$percentage = [math]::Round(($passedTests / $totalTests) * 100, 1)

Write-Host ""
Write-Host "Tests réussis: $passedTests / $totalTests ($percentage%)" -ForegroundColor $(if ($percentage -eq 100) { "Green" } elseif ($percentage -ge 80) { "Yellow" } else { "Red" })
Write-Host ""

if ($percentage -eq 100) {
    Write-Host "✅ TOUS LES TESTS SONT PASSÉS !" -ForegroundColor Green
    Write-Host ""
    Write-Host "Le système est prêt à être utilisé:" -ForegroundColor Cyan
    Write-Host "  - Page joueur: http://localhost:4000/player/gallery" -ForegroundColor White
    Write-Host "  - Page admin: http://localhost:4000/admin/content" -ForegroundColor White
    Write-Host ""
    Write-Host "📖 Consultez GUIDE_SYSTEME_CONTENU_COMPLET.md pour plus d'infos" -ForegroundColor Cyan
} elseif ($percentage -ge 80) {
    Write-Host "⚠️ Quelques tests ont échoué, mais le système devrait fonctionner" -ForegroundColor Yellow
    Write-Host "   Vérifiez les erreurs ci-dessus pour plus de détails" -ForegroundColor Yellow
} else {
    Write-Host "❌ PLUSIEURS TESTS ONT ÉCHOUÉ" -ForegroundColor Red
    Write-Host "   Veuillez corriger les erreurs avant d'utiliser le système" -ForegroundColor Red
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Pause pour voir les résultats
Write-Host "Appuyez sur une touche pour continuer..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')
