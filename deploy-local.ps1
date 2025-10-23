# Script de test de déploiement local avec Docker (Windows)
# Usage: .\deploy-local.ps1

Write-Host "================================" -ForegroundColor Cyan
Write-Host "DEPLOIEMENT LOCAL - GAMEZONE" -ForegroundColor Cyan
Write-Host "Test avec Docker Desktop" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier que Docker Desktop est installé
Write-Host "[1] Vérification de Docker Desktop..." -ForegroundColor Green
if (!(Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "ERREUR: Docker Desktop n'est pas installé!" -ForegroundColor Red
    Write-Host "Téléchargez-le depuis: https://www.docker.com/products/docker-desktop/" -ForegroundColor Yellow
    exit 1
}

Write-Host "  Docker est installé" -ForegroundColor Green
docker --version

if (!(Get-Command docker-compose -ErrorAction SilentlyContinue)) {
    Write-Host "ERREUR: Docker Compose n'est pas disponible!" -ForegroundColor Red
    exit 1
}

Write-Host "  Docker Compose est installé" -ForegroundColor Green
docker-compose --version

Write-Host ""

# Vérifier le fichier .env
Write-Host "[2] Vérification du fichier .env..." -ForegroundColor Green

if (!(Test-Path ".env")) {
    Write-Host "  Fichier .env manquant. Copie de .env.example..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
    
    Write-Host ""
    Write-Host "ATTENTION: Veuillez éditer le fichier .env avec vos vraies valeurs!" -ForegroundColor Yellow
    Write-Host "Fichier: .env" -ForegroundColor White
    Write-Host ""
    Write-Host "Valeurs à modifier au minimum:" -ForegroundColor Yellow
    Write-Host "  - DB_PASSWORD" -ForegroundColor White
    Write-Host "  - MYSQL_ROOT_PASSWORD" -ForegroundColor White
    Write-Host "  - SESSION_SECRET" -ForegroundColor White
    Write-Host ""
    
    $continue = Read-Host "Avez-vous configuré le fichier .env? (o/n)"
    if ($continue -ne "o" -and $continue -ne "O") {
        Write-Host "Configuration annulée." -ForegroundColor Yellow
        exit 0
    }
} else {
    Write-Host "  Fichier .env trouvé" -ForegroundColor Green
}

Write-Host ""

# Créer les dossiers nécessaires
Write-Host "[3] Création des dossiers..." -ForegroundColor Green

$folders = @("uploads", "ssl", "backups")
foreach ($folder in $folders) {
    if (!(Test-Path $folder)) {
        New-Item -ItemType Directory -Path $folder | Out-Null
        Write-Host "  Créé: $folder" -ForegroundColor Gray
    } else {
        Write-Host "  Existant: $folder" -ForegroundColor Gray
    }
}

Write-Host ""

# Arrêter les conteneurs existants
Write-Host "[4] Nettoyage des conteneurs existants..." -ForegroundColor Green
docker-compose down 2>$null
Write-Host "  Nettoyage terminé" -ForegroundColor Gray

Write-Host ""

# Build des images
Write-Host "[5] Build des images Docker..." -ForegroundColor Green
Write-Host "  Cela peut prendre plusieurs minutes la première fois..." -ForegroundColor Yellow

docker-compose build --no-cache

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "ERREUR lors du build des images!" -ForegroundColor Red
    exit 1
}

Write-Host "  Build terminé" -ForegroundColor Green

Write-Host ""

# Démarrer les conteneurs
Write-Host "[6] Démarrage des conteneurs..." -ForegroundColor Green

docker-compose up -d

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "ERREUR lors du démarrage des conteneurs!" -ForegroundColor Red
    docker-compose logs
    exit 1
}

Write-Host "  Conteneurs démarrés" -ForegroundColor Green

Write-Host ""

# Attendre que MySQL soit prêt
Write-Host "[7] Attente du démarrage de MySQL..." -ForegroundColor Green
Write-Host "  Patience, cela prend environ 30 secondes..." -ForegroundColor Yellow

Start-Sleep -Seconds 30

Write-Host "  MySQL devrait être prêt" -ForegroundColor Green

Write-Host ""

# Vérifier l'état des services
Write-Host "[8] Vérification de l'état des services..." -ForegroundColor Green
Write-Host ""

docker-compose ps

Write-Host ""

# Health checks
Write-Host "[9] Vérification de la santé des services..." -ForegroundColor Green
Write-Host ""

$allHealthy = $true

# Frontend
try {
    $response = Invoke-WebRequest -Uri "http://localhost:80" -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "  Frontend (Nginx):  OK" -ForegroundColor Green
    } else {
        Write-Host "  Frontend (Nginx):  WARN (Code: $($response.StatusCode))" -ForegroundColor Yellow
        $allHealthy = $false
    }
} catch {
    Write-Host "  Frontend (Nginx):  DOWN" -ForegroundColor Red
    $allHealthy = $false
}

# Backend
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8080/api/health.php" -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "  Backend (PHP):     OK" -ForegroundColor Green
    } else {
        Write-Host "  Backend (PHP):     WARN (Code: $($response.StatusCode))" -ForegroundColor Yellow
        $allHealthy = $false
    }
} catch {
    Write-Host "  Backend (PHP):     DOWN" -ForegroundColor Red
    $allHealthy = $false
}

# MySQL
$mysqlTest = docker-compose exec -T mysql mysqladmin ping -h localhost 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "  MySQL:             OK" -ForegroundColor Green
} else {
    Write-Host "  MySQL:             DOWN" -ForegroundColor Red
    $allHealthy = $false
}

Write-Host ""

if ($allHealthy) {
    Write-Host "================================" -ForegroundColor Green
    Write-Host "DEPLOIEMENT LOCAL REUSSI!" -ForegroundColor Green
    Write-Host "================================" -ForegroundColor Green
} else {
    Write-Host "================================" -ForegroundColor Yellow
    Write-Host "DEPLOIEMENT PARTIEL" -ForegroundColor Yellow
    Write-Host "================================" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Certains services ne répondent pas encore." -ForegroundColor Yellow
    Write-Host "Attendez quelques secondes et vérifiez les logs:" -ForegroundColor Yellow
    Write-Host "  docker-compose logs -f" -ForegroundColor White
}

Write-Host ""
Write-Host "URLs d'accès:" -ForegroundColor Cyan
Write-Host "  Frontend:  http://localhost" -ForegroundColor White
Write-Host "  Backend:   http://localhost/api" -ForegroundColor White
Write-Host "  Admin:     http://localhost/admin" -ForegroundColor White
Write-Host ""
Write-Host "Commandes utiles:" -ForegroundColor Cyan
Write-Host "  Voir les logs:       docker-compose logs -f" -ForegroundColor White
Write-Host "  Arrêter:             docker-compose down" -ForegroundColor White
Write-Host "  Redémarrer:          docker-compose restart" -ForegroundColor White
Write-Host "  État des services:   docker-compose ps" -ForegroundColor White
Write-Host ""

$openBrowser = Read-Host "Voulez-vous ouvrir l'application dans le navigateur? (o/n)"
if ($openBrowser -eq "o" -or $openBrowser -eq "O") {
    Start-Process "http://localhost"
    Write-Host ""
    Write-Host "Application ouverte dans le navigateur!" -ForegroundColor Green
}

Write-Host ""
Write-Host "Pour voir les logs en temps réel:" -ForegroundColor Yellow
Write-Host "  docker-compose logs -f" -ForegroundColor White
Write-Host ""
