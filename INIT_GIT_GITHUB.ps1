# Script d'initialisation Git et GitHub
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  INITIALISATION GIT + GITHUB" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier Git
Write-Host "Verification de Git..." -ForegroundColor Yellow
try {
    $gitVersion = git --version 2>&1
    Write-Host "[OK] Git installe: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "[ERREUR] Git n'est pas installe!" -ForegroundColor Red
    Write-Host "Telechargez-le sur: https://git-scm.com" -ForegroundColor Yellow
    exit 1
}
Write-Host ""

# Configuration utilisateur Git
Write-Host "Configuration utilisateur Git..." -ForegroundColor Yellow
$gitUser = git config user.name 2>&1
$gitEmail = git config user.email 2>&1

if ([string]::IsNullOrEmpty($gitUser)) {
    $userName = Read-Host "Entrez votre nom pour Git"
    git config --global user.name "$userName"
    Write-Host "[OK] Nom configure: $userName" -ForegroundColor Green
} else {
    Write-Host "[OK] Nom deja configure: $gitUser" -ForegroundColor Green
}

if ([string]::IsNullOrEmpty($gitEmail)) {
    $userEmail = Read-Host "Entrez votre email pour Git"
    git config --global user.email "$userEmail"
    Write-Host "[OK] Email configure: $userEmail" -ForegroundColor Green
} else {
    Write-Host "[OK] Email deja configure: $gitEmail" -ForegroundColor Green
}
Write-Host ""

# Vérifier si .git existe déjà
if (Test-Path ".git") {
    Write-Host "[!] Repository Git deja initialise!" -ForegroundColor Yellow
    $reinit = Read-Host "Voulez-vous reinitialiser? (o/n)"
    if ($reinit -eq "o") {
        Remove-Item -Recurse -Force .git
        Write-Host "[OK] Repository reinitialise" -ForegroundColor Green
    }
}

# Initialiser Git
if (-Not (Test-Path ".git")) {
    Write-Host "Initialisation du repository Git..." -ForegroundColor Yellow
    git init
    Write-Host "[OK] Repository initialise" -ForegroundColor Green
}
Write-Host ""

# Ajouter les fichiers
Write-Host "Ajout des fichiers au repository..." -ForegroundColor Yellow
Write-Host "(Cela peut prendre quelques secondes...)" -ForegroundColor Gray
git add .

$status = git status --short
$fileCount = ($status | Measure-Object).Count
Write-Host "[OK] $fileCount fichiers ajoutes" -ForegroundColor Green
Write-Host ""

# Premier commit
Write-Host "Creation du commit initial..." -ForegroundColor Yellow
git commit -m "Initial commit - GameZone v1.0

Architecture separee:
- Frontend React pour Vercel
- Backend PHP pour InfinityFree
- Systeme de points et recompenses
- Interface admin et joueur
- Integration KkiaPay
- Documentation complete"

Write-Host "[OK] Commit cree avec succes!" -ForegroundColor Green
Write-Host ""

# Instructions GitHub
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  PROCHAINES ETAPES - GITHUB" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Allez sur https://github.com/new" -ForegroundColor White
Write-Host "2. Repository name: gamezone" -ForegroundColor White
Write-Host "3. Description: Plateforme de gestion de cyber cafe" -ForegroundColor White
Write-Host "4. Public ou Private (votre choix)" -ForegroundColor White
Write-Host "5. NE PAS cocher 'Initialize with README'" -ForegroundColor White
Write-Host "6. Cliquez 'Create repository'" -ForegroundColor White
Write-Host ""

$configureNow = Read-Host "Repository GitHub deja cree? Voulez-vous configurer maintenant? (o/n)"

if ($configureNow -eq "o") {
    Write-Host ""
    $repoUrl = Read-Host "Entrez l'URL de votre repository (ex: https://github.com/username/gamezone.git)"
    
    if ([string]::IsNullOrEmpty($repoUrl)) {
        Write-Host "[ERREUR] URL non fournie" -ForegroundColor Red
        exit 1
    }
    
    # Vérifier si remote existe
    $existingRemote = git remote get-url origin 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "[!] Remote 'origin' existe deja: $existingRemote" -ForegroundColor Yellow
        $replace = Read-Host "Voulez-vous le remplacer? (o/n)"
        if ($replace -eq "o") {
            git remote remove origin
            git remote add origin $repoUrl
            Write-Host "[OK] Remote remplace" -ForegroundColor Green
        }
    } else {
        git remote add origin $repoUrl
        Write-Host "[OK] Remote ajoute: $repoUrl" -ForegroundColor Green
    }
    
    # Configurer branche main
    Write-Host ""
    Write-Host "Configuration de la branche principale..." -ForegroundColor Yellow
    git branch -M main
    Write-Host "[OK] Branche renommee en 'main'" -ForegroundColor Green
    
    # Push
    Write-Host ""
    Write-Host "Push vers GitHub..." -ForegroundColor Yellow
    Write-Host "(Vous devrez peut-etre entrer vos identifiants)" -ForegroundColor Gray
    git push -u origin main
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "=====================================" -ForegroundColor Cyan
        Write-Host "  [OK] PUSH REUSSI!" -ForegroundColor Green
        Write-Host "=====================================" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Votre code est maintenant sur GitHub!" -ForegroundColor Green
        Write-Host "URL: $repoUrl" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "PROCHAINE ETAPE: Deployer sur Vercel" -ForegroundColor Yellow
        Write-Host "Guide: DEPLOIEMENT_VERCEL.md" -ForegroundColor Cyan
        Write-Host ""
    } else {
        Write-Host ""
        Write-Host "[ERREUR] Erreur lors du push!" -ForegroundColor Red
        Write-Host ""
        Write-Host "Causes possibles:" -ForegroundColor Yellow
        Write-Host "- Identifiants GitHub incorrects" -ForegroundColor White
        Write-Host "- Repository GitHub n'existe pas" -ForegroundColor White
        Write-Host "- Pas de connexion internet" -ForegroundColor White
        Write-Host ""
    }
} else {
    Write-Host ""
    Write-Host "[OK] Repository Git local pret!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Pour connecter a GitHub plus tard:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "git remote add origin https://github.com/USERNAME/gamezone.git" -ForegroundColor Cyan
    Write-Host "git branch -M main" -ForegroundColor Cyan
    Write-Host "git push -u origin main" -ForegroundColor Cyan
    Write-Host ""
}

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Documentation disponible:" -ForegroundColor Yellow
Write-Host "- DEPLOIEMENT_SEPARE.md : Architecture complete" -ForegroundColor White
Write-Host "- DEPLOIEMENT_VERCEL.md : Guide Vercel" -ForegroundColor White
Write-Host "- PREPARER_BACKEND_INFINITYFREE.ps1 : Script backend" -ForegroundColor White
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
