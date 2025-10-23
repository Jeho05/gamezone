# Script de vérification - Design UI/UX GameZone
# Vérifie que tous les composants et fichiers ont été créés correctement

Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "   VERIFICATION DESIGN UI/UX - GAMEZONE   " -ForegroundColor Cyan
Write-Host "============================================`n" -ForegroundColor Cyan

$baseDir = "createxyz-project\_\apps\web\src"
$errors = 0
$warnings = 0
$success = 0

function Test-FileExists {
    param($path, $description)
    if (Test-Path $path) {
        Write-Host "[OK] $description" -ForegroundColor Green
        $script:success++
        return $true
    } else {
        Write-Host "[ERREUR] $description - Fichier manquant: $path" -ForegroundColor Red
        $script:errors++
        return $false
    }
}

function Test-DirectoryExists {
    param($path, $description)
    if (Test-Path $path -PathType Container) {
        Write-Host "[OK] $description" -ForegroundColor Green
        $script:success++
        return $true
    } else {
        Write-Host "[ERREUR] $description - Dossier manquant: $path" -ForegroundColor Red
        $script:errors++
        return $false
    }
}

# 1. COMPOSANTS UI
Write-Host "`n1. COMPOSANTS UI" -ForegroundColor Yellow
Write-Host "=================" -ForegroundColor Yellow
Test-FileExists "$baseDir\components\ui\VideoBackground.jsx" "VideoBackground.jsx"
Test-FileExists "$baseDir\components\ui\FloatingObjects.jsx" "FloatingObjects.jsx"
Test-FileExists "$baseDir\components\ui\GlassCard.jsx" "GlassCard.jsx"
Test-FileExists "$baseDir\components\ui\NeonText.jsx" "NeonText.jsx"
Test-FileExists "$baseDir\components\ui\ParallaxObject.jsx" "ParallaxObject.jsx"

# 2. SECTIONS
Write-Host "`n2. SECTIONS" -ForegroundColor Yellow
Write-Host "============" -ForegroundColor Yellow
Test-FileExists "$baseDir\components\sections\AboutAdmin.jsx" "AboutAdmin.jsx"

# 3. ANIMATIONS CSS
Write-Host "`n3. ANIMATIONS CSS" -ForegroundColor Yellow
Write-Host "=================" -ForegroundColor Yellow
Test-FileExists "$baseDir\styles\animations.css" "animations.css"

# 4. PAGES MODERNISEES
Write-Host "`n4. PAGES MODERNISEES" -ForegroundColor Yellow
Write-Host "====================" -ForegroundColor Yellow
Test-FileExists "$baseDir\app\page.jsx" "Page d'accueil"
Test-FileExists "$baseDir\app\auth\login\page.jsx" "Page Login"
Test-FileExists "$baseDir\app\auth\register\page.jsx" "Page Register"

# 5. ROOT LAYOUT
Write-Host "`n5. ROOT LAYOUT" -ForegroundColor Yellow
Write-Host "==============" -ForegroundColor Yellow
Test-FileExists "$baseDir\app\root.tsx" "root.tsx (modifié)"

# 6. DOCUMENTATION
Write-Host "`n6. DOCUMENTATION" -ForegroundColor Yellow
Write-Host "================" -ForegroundColor Yellow
Test-FileExists "DESIGN_UI_UX_COMPLET.md" "DESIGN_UI_UX_COMPLET.md"
Test-FileExists "GUIDE_RAPIDE_UI_UX.md" "GUIDE_RAPIDE_UI_UX.md"
Test-FileExists "ASSETS_DISPONIBLES.md" "ASSETS_DISPONIBLES.md"
Test-FileExists "README_DESIGN_UI_UX.md" "README_DESIGN_UI_UX.md"

# 7. ASSETS - VIDEOS
Write-Host "`n7. ASSETS - VIDEOS" -ForegroundColor Yellow
Write-Host "==================" -ForegroundColor Yellow
Test-FileExists "images\video\Arcade_Welcome_Manager_Loop.mp4" "Arcade_Welcome_Manager_Loop.mp4"
Test-FileExists "images\video\Cyber_Arcade_Neon_Ember.mp4" "Cyber_Arcade_Neon_Ember.mp4"
Test-FileExists "images\video\kling_20251010_Image_to_Video_Use_the_up_4875_0.mp4" "kling_20251010_Image_to_Video.mp4"

# 8. ASSETS - PHOTOS ADMIN
Write-Host "`n8. ASSETS - PHOTOS ADMIN" -ForegroundColor Yellow
Write-Host "========================" -ForegroundColor Yellow
Test-FileExists "images\gaming tof\Boss\ismo_PDG.jpg" "ismo_PDG.jpg"
Test-FileExists "images\gaming tof\Boss\ismo_Pro.jpg" "ismo_Pro.jpg"
Test-FileExists "images\gaming tof\Boss\ismo_décontracté_pro.jpg" "ismo_décontracté_pro.jpg"
Test-FileExists "images\gaming tof\Boss\ismo_pro1.jpg" "ismo_pro1.jpg"

# 9. VERIFICATION CONTENU
Write-Host "`n9. VERIFICATION CONTENU DES FICHIERS" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow

# Vérifier imports CSS dans root.tsx
if (Test-Path "$baseDir\app\root.tsx") {
    $rootContent = Get-Content "$baseDir\app\root.tsx" -Raw
    if ($rootContent -match "animations.css") {
        Write-Host "[OK] animations.css importé dans root.tsx" -ForegroundColor Green
        $success++
    } else {
        Write-Host "[ATTENTION] animations.css non importé dans root.tsx" -ForegroundColor Yellow
        $warnings++
    }
}

# Vérifier 'use client' dans les pages
$pagesWithUseClient = @(
    "$baseDir\app\page.jsx",
    "$baseDir\app\auth\login\page.jsx",
    "$baseDir\app\auth\register\page.jsx"
)

foreach ($page in $pagesWithUseClient) {
    if (Test-Path $page) {
        $content = Get-Content $page -Raw
        if ($content -match "'use client'") {
            Write-Host "[OK] 'use client' présent dans $(Split-Path $page -Leaf)" -ForegroundColor Green
            $success++
        } else {
            Write-Host "[ATTENTION] 'use client' manquant dans $(Split-Path $page -Leaf)" -ForegroundColor Yellow
            $warnings++
        }
    }
}

# 10. VERIFICATION OBJETS GAMING
Write-Host "`n10. VERIFICATION OBJETS GAMING (Échantillon)" -ForegroundColor Yellow
Write-Host "=============================================" -ForegroundColor Yellow
Test-FileExists "images\objet\Goku-Blue-PNG-Photo.png" "Goku Blue"
Test-FileExists "images\objet\Kratos-PNG-Clipart.png" "Kratos"
Test-FileExists "images\objet\Console-PNG-Clipart.png" "Console"
Test-FileExists "images\objet\Dragon-Ball-Z-Logo-PNG-HD.png" "Dragon Ball Logo"
Test-FileExists "images\objet\Naruto-Ashura-Transparent-PNG.png" "Naruto Ashura"

# RESUME
Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "              RESUME DE LA VERIFICATION     " -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

Write-Host "`n[SUCCES]    : $success vérifications OK" -ForegroundColor Green
Write-Host "[ERREURS]   : $errors fichiers manquants" -ForegroundColor Red
Write-Host "[ATTENTION] : $warnings avertissements" -ForegroundColor Yellow

# Calcul du score
$total = $success + $errors + $warnings
$percentage = [math]::Round(($success / $total) * 100, 2)

Write-Host "`nSCORE: $percentage%" -ForegroundColor $(if ($percentage -ge 90) { "Green" } elseif ($percentage -ge 70) { "Yellow" } else { "Red" })

if ($errors -eq 0 -and $warnings -eq 0) {
    Write-Host "`n[PARFAIT] Tous les fichiers sont en place!" -ForegroundColor Green
    Write-Host "Vous pouvez démarrer le serveur de développement." -ForegroundColor Green
} elseif ($errors -eq 0) {
    Write-Host "`n[BIEN] Tous les fichiers essentiels sont présents." -ForegroundColor Yellow
    Write-Host "Quelques avertissements à vérifier." -ForegroundColor Yellow
} else {
    Write-Host "`n[ACTION REQUISE] Des fichiers essentiels sont manquants." -ForegroundColor Red
    Write-Host "Veuillez créer les fichiers manquants avant de continuer." -ForegroundColor Red
}

# PROCHAINES ETAPES
Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "           PROCHAINES ETAPES                " -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

Write-Host "`n1. Démarrer le serveur de développement:" -ForegroundColor White
Write-Host "   cd createxyz-project\_\apps\web" -ForegroundColor Gray
Write-Host "   npm run dev" -ForegroundColor Gray

Write-Host "`n2. Vérifier les pages modernisées:" -ForegroundColor White
Write-Host "   - http://localhost:4000/ (Home)" -ForegroundColor Gray
Write-Host "   - http://localhost:4000/auth/login (Login)" -ForegroundColor Gray
Write-Host "   - http://localhost:4000/auth/register (Register)" -ForegroundColor Gray

Write-Host "`n3. Consulter la documentation:" -ForegroundColor White
Write-Host "   - README_DESIGN_UI_UX.md (Vue d'ensemble)" -ForegroundColor Gray
Write-Host "   - GUIDE_RAPIDE_UI_UX.md (Templates)" -ForegroundColor Gray
Write-Host "   - DESIGN_UI_UX_COMPLET.md (Documentation complète)" -ForegroundColor Gray

Write-Host "`n4. Moderniser les pages restantes:" -ForegroundColor White
Write-Host "   - Dashboard Player (priorité haute)" -ForegroundColor Gray
Write-Host "   - Shop (priorité haute)" -ForegroundColor Gray
Write-Host "   - Leaderboard, Profile, etc." -ForegroundColor Gray

Write-Host "`n============================================`n" -ForegroundColor Cyan

# Pause pour lire les résultats
Read-Host "Appuyez sur Entrée pour terminer"
