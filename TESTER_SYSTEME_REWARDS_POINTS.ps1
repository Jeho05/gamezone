# Script de test et démarrage du système de récompenses-packages payables en points
# Execute: .\TESTER_SYSTEME_REWARDS_POINTS.ps1

Write-Host "================================" -ForegroundColor Cyan
Write-Host "SYSTÈME RÉCOMPENSES + PACKAGES" -ForegroundColor Cyan
Write-Host "Payables en Points" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier que nous sommes dans le bon répertoire
if (!(Test-Path "api\migrations\add_reward_game_packages.sql")) {
    Write-Host "[ERREUR] Fichier de migration introuvable!" -ForegroundColor Red
    Write-Host "Assurez-vous d'être dans le répertoire racine du projet." -ForegroundColor Yellow
    exit 1
}

Write-Host "[1/5] Vérification des fichiers créés..." -ForegroundColor Green

$files = @(
    "api\migrations\add_reward_game_packages.sql",
    "api\admin\rewards.php",
    "api\shop\redeem_with_points.php",
    "admin\rewards_manager.html",
    "SYSTEME_REWARDS_PACKAGES_POINTS.md"
)

$allFilesExist = $true
foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "  ✓ $file" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $file MANQUANT!" -ForegroundColor Red
        $allFilesExist = $false
    }
}

if (!$allFilesExist) {
    Write-Host ""
    Write-Host "[ERREUR] Certains fichiers sont manquants!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2/5] Instructions pour la migration SQL..." -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANT: Vous devez appliquer la migration SQL manuellement:" -ForegroundColor Yellow
Write-Host "1. Ouvrir phpMyAdmin: http://localhost/phpmyadmin" -ForegroundColor White
Write-Host "2. Sélectionner la base de données 'gamezone'" -ForegroundColor White
Write-Host "3. Aller dans l'onglet 'SQL'" -ForegroundColor White
Write-Host "4. Copier-coller le contenu de:" -ForegroundColor White
Write-Host "   api\migrations\add_reward_game_packages.sql" -ForegroundColor Cyan
Write-Host "5. Cliquer 'Exécuter'" -ForegroundColor White
Write-Host ""

$response = Read-Host "Avez-vous appliqué la migration? (o/n)"
if ($response -ne "o" -and $response -ne "O") {
    Write-Host ""
    Write-Host "Veuillez d'abord appliquer la migration SQL, puis relancer ce script." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Commande rapide pour ouvrir le fichier:" -ForegroundColor Cyan
    Write-Host "notepad api\migrations\add_reward_game_packages.sql" -ForegroundColor White
    exit 0
}

Write-Host ""
Write-Host "[3/5] Test de la structure de la base de données..." -ForegroundColor Green
Write-Host ""
Write-Host "Vérifiez manuellement dans phpMyAdmin que:" -ForegroundColor Yellow
Write-Host "  • Table game_packages a les colonnes: is_points_only, points_cost, reward_id" -ForegroundColor White
Write-Host "  • Table rewards a les colonnes: reward_type, game_package_id" -ForegroundColor White
Write-Host "  • Table purchases a les colonnes: paid_with_points, points_spent" -ForegroundColor White
Write-Host "  • Vue point_packages existe" -ForegroundColor White
Write-Host "  • Vue points_redemption_history existe" -ForegroundColor White
Write-Host ""

Write-Host "[4/5] URLs à tester..." -ForegroundColor Green
Write-Host ""

# Déterminer l'URL de base
$baseUrl = "http://localhost/projet%20ismo"

Write-Host "Interface Admin - Gestion des Récompenses:" -ForegroundColor Cyan
Write-Host "$baseUrl/admin/rewards_manager.html" -ForegroundColor White
Write-Host ""

Write-Host "API Backend - Liste des packages échangeables:" -ForegroundColor Cyan
Write-Host "$baseUrl/api/shop/redeem_with_points.php" -ForegroundColor White
Write-Host ""

Write-Host "API Backend - Récompenses admin:" -ForegroundColor Cyan
Write-Host "$baseUrl/api/admin/rewards.php" -ForegroundColor White
Write-Host ""

Write-Host "[5/5] Guide de test rapide..." -ForegroundColor Green
Write-Host ""
Write-Host "=== TEST COMPLET ===" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. CRÉER UNE RÉCOMPENSE-PACKAGE (Admin)" -ForegroundColor Cyan
Write-Host "   a) Ouvrir: $baseUrl/admin/rewards_manager.html" -ForegroundColor White
Write-Host "   b) Cliquer 'Nouvelle Récompense'" -ForegroundColor White
Write-Host "   c) Sélectionner type: 'Package de Jeu'" -ForegroundColor White
Write-Host "   d) Remplir:" -ForegroundColor White
Write-Host "      - Nom: FIFA Test - 30 min" -ForegroundColor Gray
Write-Host "      - Coût: 50 points" -ForegroundColor Gray
Write-Host "      - Jeu: FIFA 2024" -ForegroundColor Gray
Write-Host "      - Durée: 30 minutes" -ForegroundColor Gray
Write-Host "      - Points gagnés: 5" -ForegroundColor Gray
Write-Host "   e) Enregistrer" -ForegroundColor White
Write-Host ""

Write-Host "2. VÉRIFIER LA CRÉATION" -ForegroundColor Cyan
Write-Host "   • La carte de la récompense apparaît avec un fond coloré" -ForegroundColor White
Write-Host "   • Le badge 'Package Jeu' est affiché" -ForegroundColor White
Write-Host "   • Les statistiques sont mises à jour" -ForegroundColor White
Write-Host ""

Write-Host "3. TESTER L'API (Avec curl ou Postman)" -ForegroundColor Cyan
Write-Host ""
Write-Host "   # Lister les packages échangeables" -ForegroundColor Gray
Write-Host "   GET $baseUrl/api/shop/redeem_with_points.php" -ForegroundColor White
Write-Host ""
Write-Host "   # Échanger des points (nécessite authentification)" -ForegroundColor Gray
Write-Host "   POST $baseUrl/api/shop/redeem_with_points.php" -ForegroundColor White
Write-Host "   Body: {`"package_id`": 15}" -ForegroundColor White
Write-Host ""

Write-Host "4. VÉRIFIER EN BASE DE DONNÉES" -ForegroundColor Cyan
Write-Host "   Exécuter dans phpMyAdmin:" -ForegroundColor White
Write-Host ""
Write-Host "   -- Voir tous les packages payables en points" -ForegroundColor Gray
Write-Host "   SELECT * FROM point_packages;" -ForegroundColor White
Write-Host ""
Write-Host "   -- Voir l'historique des échanges" -ForegroundColor Gray
Write-Host "   SELECT * FROM points_redemption_history;" -ForegroundColor White
Write-Host ""

Write-Host "=== RÉSULTATS ATTENDUS ===" -ForegroundColor Yellow
Write-Host ""
Write-Host "Après la création d'une récompense-package:" -ForegroundColor Cyan
Write-Host "  ✓ 1 entrée dans 'rewards' (reward_type = 'game_package')" -ForegroundColor Green
Write-Host "  ✓ 1 entrée dans 'game_packages' (is_points_only = 1)" -ForegroundColor Green
Write-Host "  ✓ Les deux entrées sont liées (reward_id ↔ game_package_id)" -ForegroundColor Green
Write-Host "  ✓ Le package apparaît dans la vue 'point_packages'" -ForegroundColor Green
Write-Host ""

Write-Host "Après un échange de points:" -ForegroundColor Cyan
Write-Host "  ✓ 1 entrée dans 'purchases' (paid_with_points = 1)" -ForegroundColor Green
Write-Host "  ✓ 1 entrée dans 'points_transactions' (type = 'spend')" -ForegroundColor Green
Write-Host "  ✓ 1 entrée dans 'reward_redemptions'" -ForegroundColor Green
Write-Host "  ✓ Points de l'utilisateur diminués" -ForegroundColor Green
Write-Host "  ✓ Session de jeu créée (status = 'pending')" -ForegroundColor Green
Write-Host ""

Write-Host "=== DÉPANNAGE ===" -ForegroundColor Yellow
Write-Host ""
Write-Host "Si l'interface admin ne charge pas:" -ForegroundColor Cyan
Write-Host "  • Vérifier que Apache est démarré" -ForegroundColor White
Write-Host "  • Ouvrir la console (F12) et vérifier les erreurs" -ForegroundColor White
Write-Host "  • Vérifier que l'API admin/rewards.php fonctionne" -ForegroundColor White
Write-Host ""

Write-Host "Si la création échoue:" -ForegroundColor Cyan
Write-Host "  • Vérifier que la migration SQL est bien appliquée" -ForegroundColor White
Write-Host "  • Vérifier les logs dans logs/api_*.log" -ForegroundColor White
Write-Host "  • Vérifier que le jeu sélectionné existe et est actif" -ForegroundColor White
Write-Host ""

Write-Host "Si l'échange de points échoue:" -ForegroundColor Cyan
Write-Host "  • Vérifier que l'utilisateur a assez de points" -ForegroundColor White
Write-Host "  • Vérifier que le package est actif (is_active = 1)" -ForegroundColor White
Write-Host "  • Vérifier que le jeu est actif (games.is_active = 1)" -ForegroundColor White
Write-Host "  • Vérifier la limite max_purchases_per_user" -ForegroundColor White
Write-Host ""

Write-Host "================================" -ForegroundColor Cyan
Write-Host "DOCUMENTATION COMPLÈTE" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Consultez le fichier:" -ForegroundColor Yellow
Write-Host "SYSTEME_REWARDS_PACKAGES_POINTS.md" -ForegroundColor White
Write-Host ""
Write-Host "Pour ouvrir:" -ForegroundColor Yellow
Write-Host "notepad SYSTEME_REWARDS_PACKAGES_POINTS.md" -ForegroundColor White
Write-Host ""

Write-Host "================================" -ForegroundColor Green
Write-Host "PRÊT À TESTER!" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host ""

# Demander si on veut ouvrir les URLs
$openBrowser = Read-Host "Voulez-vous ouvrir l'interface admin dans le navigateur? (o/n)"
if ($openBrowser -eq "o" -or $openBrowser -eq "O") {
    Start-Process "$baseUrl/admin/rewards_manager.html"
    Write-Host ""
    Write-Host "Interface admin ouverte dans le navigateur!" -ForegroundColor Green
}

Write-Host ""
Write-Host "Bon test! 🎮🎁" -ForegroundColor Magenta
Write-Host ""
