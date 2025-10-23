# Script d'initialisation GitHub pour GameZone
# À exécuter APRÈS avoir créé votre repo sur GitHub.com

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  INITIALISATION GITHUB - GAMEZONE" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier Git
Write-Host "🔍 Vérification de Git..." -ForegroundColor Yellow
$gitVersion = git --version 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Git n'est pas installé!" -ForegroundColor Red
    Write-Host "   Téléchargez-le sur: https://git-scm.com" -ForegroundColor Yellow
    exit 1
}
Write-Host "✅ Git installé: $gitVersion" -ForegroundColor Green
Write-Host ""

# Configuration utilisateur Git (si pas déjà fait)
Write-Host "👤 Configuration utilisateur Git..." -ForegroundColor Yellow
$gitUser = git config user.name 2>&1
$gitEmail = git config user.email 2>&1

if ([string]::IsNullOrEmpty($gitUser)) {
    $userName = Read-Host "Entrez votre nom pour Git"
    git config --global user.name "$userName"
    Write-Host "✅ Nom configuré: $userName" -ForegroundColor Green
} else {
    Write-Host "✅ Nom déjà configuré: $gitUser" -ForegroundColor Green
}

if ([string]::IsNullOrEmpty($gitEmail)) {
    $userEmail = Read-Host "Entrez votre email pour Git"
    git config --global user.email "$userEmail"
    Write-Host "✅ Email configuré: $userEmail" -ForegroundColor Green
} else {
    Write-Host "✅ Email déjà configuré: $gitEmail" -ForegroundColor Green
}
Write-Host ""

# Vérifier si .git existe déjà
if (Test-Path ".git") {
    Write-Host "⚠️  Repository Git déjà initialisé!" -ForegroundColor Yellow
    $reinit = Read-Host "Voulez-vous réinitialiser? (o/n)"
    if ($reinit -eq "o") {
        Remove-Item -Recurse -Force .git
        Write-Host "✅ Repository réinitialisé" -ForegroundColor Green
    } else {
        Write-Host "ℹ️  Utilisation du repository existant" -ForegroundColor Cyan
    }
}

# Initialiser Git
if (-Not (Test-Path ".git")) {
    Write-Host "📦 Initialisation du repository Git..." -ForegroundColor Yellow
    git init
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Erreur lors de l'initialisation!" -ForegroundColor Red
        exit 1
    }
    Write-Host "✅ Repository initialisé" -ForegroundColor Green
}
Write-Host ""

# Ajouter les fichiers
Write-Host "📋 Ajout des fichiers au repository..." -ForegroundColor Yellow
Write-Host "   (Cela peut prendre quelques secondes...)" -ForegroundColor Gray

git add .

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erreur lors de l'ajout des fichiers!" -ForegroundColor Red
    exit 1
}

# Afficher statut
$status = git status --short
$fileCount = ($status | Measure-Object).Count
Write-Host "✅ $fileCount fichiers ajoutés" -ForegroundColor Green
Write-Host ""

# Premier commit
Write-Host "💾 Création du commit initial..." -ForegroundColor Yellow
git commit -m "Initial commit - GameZone Application v1.0

- Frontend React avec React Router
- Backend PHP/MySQL
- Système de points et récompenses
- Interface admin et joueur
- Intégration KkiaPay
- Documentation complète"

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erreur lors du commit!" -ForegroundColor Red
    exit 1
}
Write-Host "✅ Commit créé avec succès!" -ForegroundColor Green
Write-Host ""

# Instructions pour le remote
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  📝 PROCHAINES ÉTAPES" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1️⃣  Allez sur https://github.com" -ForegroundColor White
Write-Host "2️⃣  Cliquez sur le bouton '+' en haut à droite" -ForegroundColor White
Write-Host "3️⃣  Sélectionnez 'New repository'" -ForegroundColor White
Write-Host "4️⃣  Nom du repository: gamezone" -ForegroundColor White
Write-Host "5️⃣  Description: Plateforme de gestion de cyber café avec React et PHP" -ForegroundColor White
Write-Host "6️⃣  Choisissez: Public ou Private" -ForegroundColor White
Write-Host "7️⃣  NE cochez PAS 'Initialize with README' (on en a déjà un)" -ForegroundColor White
Write-Host "8️⃣  Cliquez sur 'Create repository'" -ForegroundColor White
Write-Host ""
Write-Host "9️⃣  GitHub vous donnera des commandes. Utilisez:" -ForegroundColor White
Write-Host ""
Write-Host "    git remote add origin https://github.com/VOTRE-USERNAME/gamezone.git" -ForegroundColor Cyan
Write-Host "    git branch -M main" -ForegroundColor Cyan
Write-Host "    git push -u origin main" -ForegroundColor Cyan
Write-Host ""

# Demander l'URL du repo
Write-Host "=====================================" -ForegroundColor Cyan
$configureNow = Read-Host "Voulez-vous configurer le remote maintenant? (o/n)"

if ($configureNow -eq "o") {
    Write-Host ""
    $repoUrl = Read-Host "Entrez l'URL de votre repository GitHub (ex: https://github.com/username/gamezone.git)"
    
    if ([string]::IsNullOrEmpty($repoUrl)) {
        Write-Host "❌ URL non fournie. Configuration manuelle requise." -ForegroundColor Red
        exit 1
    }
    
    # Vérifier si remote existe déjà
    $existingRemote = git remote get-url origin 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "⚠️  Remote 'origin' existe déjà: $existingRemote" -ForegroundColor Yellow
        $replace = Read-Host "Voulez-vous le remplacer? (o/n)"
        if ($replace -eq "o") {
            git remote remove origin
            git remote add origin $repoUrl
            Write-Host "✅ Remote remplacé" -ForegroundColor Green
        }
    } else {
        git remote add origin $repoUrl
        Write-Host "✅ Remote ajouté: $repoUrl" -ForegroundColor Green
    }
    
    # Configurer la branche main
    Write-Host ""
    Write-Host "🌿 Configuration de la branche principale..." -ForegroundColor Yellow
    git branch -M main
    Write-Host "✅ Branche renommée en 'main'" -ForegroundColor Green
    
    # Push
    Write-Host ""
    Write-Host "🚀 Push vers GitHub..." -ForegroundColor Yellow
    Write-Host "   (Vous devrez peut-être entrer vos identifiants GitHub)" -ForegroundColor Gray
    git push -u origin main
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "=====================================" -ForegroundColor Cyan
        Write-Host "  ✅ PUSH RÉUSSI!" -ForegroundColor Green
        Write-Host "=====================================" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "🎉 Votre code est maintenant sur GitHub!" -ForegroundColor Green
        Write-Host ""
        Write-Host "📋 URL du repository:" -ForegroundColor Yellow
        Write-Host "   $repoUrl" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "💡 Pour les prochains push:" -ForegroundColor Yellow
        Write-Host "   git add ." -ForegroundColor White
        Write-Host "   git commit -m 'Description des changements'" -ForegroundColor White
        Write-Host "   git push" -ForegroundColor White
        Write-Host ""
    } else {
        Write-Host ""
        Write-Host "❌ Erreur lors du push!" -ForegroundColor Red
        Write-Host ""
        Write-Host "Causes possibles:" -ForegroundColor Yellow
        Write-Host "  - Identifiants GitHub incorrects" -ForegroundColor White
        Write-Host "  - Repository GitHub n'existe pas" -ForegroundColor White
        Write-Host "  - Pas de connexion internet" -ForegroundColor White
        Write-Host ""
        Write-Host "Essayez de push manuellement:" -ForegroundColor Yellow
        Write-Host "  git push -u origin main" -ForegroundColor Cyan
        Write-Host ""
    }
} else {
    Write-Host ""
    Write-Host "✅ Repository Git local prêt!" -ForegroundColor Green
    Write-Host ""
    Write-Host "📋 Pour connecter à GitHub plus tard:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  git remote add origin https://github.com/VOTRE-USERNAME/gamezone.git" -ForegroundColor Cyan
    Write-Host "  git branch -M main" -ForegroundColor Cyan
    Write-Host "  git push -u origin main" -ForegroundColor Cyan
    Write-Host ""
}

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Documentation disponible:" -ForegroundColor Yellow
Write-Host "  - README.md : Documentation générale" -ForegroundColor White
Write-Host "  - DEPLOIEMENT_INFINITYFREE.md : Guide de déploiement" -ForegroundColor White
Write-Host "  - BUILD_PRODUCTION.ps1 : Script de build" -ForegroundColor White
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
