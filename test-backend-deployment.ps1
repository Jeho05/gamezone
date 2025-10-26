# Script PowerShell pour tester le déploiement du backend
# Usage: .\test-backend-deployment.ps1 -BackendUrl "https://votre-service.up.railway.app"

param(
    [Parameter(Mandatory=$true)]
    [string]$BackendUrl
)

Write-Host "🔍 TEST DE DÉPLOIEMENT BACKEND" -ForegroundColor Cyan
Write-Host "URL du backend: $BackendUrl" -ForegroundColor Yellow
Write-Host ""

# Test 1: Health check
Write-Host "🧪 Test 1: Health Check" -ForegroundColor Green
try {
    $health = Invoke-RestMethod -Uri "$BackendUrl/health.php" -Method GET
    Write-Host "   Status: $($health.status)" -ForegroundColor Green
    Write-Host "   Détails:" -ForegroundColor Gray
    foreach ($check in $health.checks.PSObject.Properties) {
        $status = if ($check.Value.status -eq "up") { "✅" } else { "❌" }
        Write-Host "     $status $($check.Name): $($check.Value.message)" -ForegroundColor Gray
    }
} catch {
    Write-Host "   ❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 2: Database connection
Write-Host "🧪 Test 2: Connexion Base de Données" -ForegroundColor Green
try {
    $db = Invoke-RestMethod -Uri "$BackendUrl/test-db-connection.php" -Method GET
    if ($db.status -eq "SUCCESS") {
        Write-Host "   ✅ Connexion réussie!" -ForegroundColor Green
        Write-Host "   Version MySQL: $($db.server_version)" -ForegroundColor Gray
        Write-Host "   Base: $($db.debug.database)" -ForegroundColor Gray
    } else {
        Write-Host "   ❌ Échec: $($db.error)" -ForegroundColor Red
        Write-Host "   Debug: $($db.debug | ConvertTo-Json -Depth 10)" -ForegroundColor Gray
    }
} catch {
    Write-Host "   ❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 3: Installation de la base de données
Write-Host "🧪 Test 3: Installation Base de Données" -ForegroundColor Green
try {
    $install = Invoke-RestMethod -Uri "$BackendUrl/install.php" -Method GET
    if ($install.status -eq "success" -or $install.status -eq "partial") {
        Write-Host "   ✅ Installation terminée!" -ForegroundColor Green
        Write-Host "   Tables créées: $($install.executed)" -ForegroundColor Gray
        if ($install.errors) {
            Write-Host "   ⚠️  Erreurs: $($install.errors.Count)" -ForegroundColor Yellow
        }
    } else {
        Write-Host "   ❌ Échec: $($install.details)" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 4: Vérification du compte admin
Write-Host "🧪 Test 4: Compte Administrateur" -ForegroundColor Green
try {
    $admin = Invoke-RestMethod -Uri "$BackendUrl/check-admin.php" -Method GET
    if ($admin.admin_exists) {
        Write-Host "   ✅ Compte admin trouvé!" -ForegroundColor Green
        Write-Host "   Email: $($admin.admin_details.email)" -ForegroundColor Gray
        Write-Host "   Username: $($admin.admin_details.username)" -ForegroundColor Gray
        if ($admin.password_test.hash_matches) {
            Write-Host "   ✅ Mot de passe correct (demo123)" -ForegroundColor Green
        } else {
            Write-Host "   ❌ Mot de passe incorrect" -ForegroundColor Red
        }
    } else {
        Write-Host "   ❌ Aucun compte admin trouvé" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 5: Test de login
Write-Host "🧪 Test 5: Connexion" -ForegroundColor Green
try {
    $login = Invoke-RestMethod -Uri "$BackendUrl/test-login.php" -Method GET
    if ($login.status -eq "success") {
        Write-Host "   ✅ Login réussi!" -ForegroundColor Green
        Write-Host "   Utilisateur: $($login.user_details.username)" -ForegroundColor Gray
        Write-Host "   Rôle: $($login.user_details.role)" -ForegroundColor Gray
    } elseif ($login.status -eq "failed") {
        Write-Host "   ❌ Login échoué - mot de passe incorrect" -ForegroundColor Red
    } else {
        Write-Host "   ❌ Login échoué: $($login.message)" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 6: Configuration des sessions
Write-Host "🧪 Test 6: Configuration des Sessions" -ForegroundColor Green
try {
    $session = Invoke-RestMethod -Uri "$BackendUrl/check-session-config.php" -Method GET
    Write-Host "   Configuration des cookies:" -ForegroundColor Gray
    Write-Host "     SameSite: $($session.php_ini_settings.'session.cookie_samesite')" -ForegroundColor Gray
    Write-Host "     Secure: $($session.php_ini_settings.'session.cookie_secure')" -ForegroundColor Gray
    Write-Host "     HttpOnly: $($session.php_ini_settings.'session.cookie_httponly')" -ForegroundColor Gray
    
    if ($session.php_ini_settings.'session.cookie_samesite' -eq "None" -and 
        $session.php_ini_settings.'session.cookie_secure' -eq "1") {
        Write-Host "   ✅ Configuration correcte pour cross-site!" -ForegroundColor Green
    } else {
        Write-Host "   ⚠️  Configuration peut causer des problèmes de session" -ForegroundColor Yellow
    }
} catch {
    Write-Host "   ❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "🏁 TESTS TERMINÉS" -ForegroundColor Cyan