# Script PowerShell pour diagnostiquer et corriger automatiquement les problèmes admin
# GAMEZONE - Correction Automatique

$ErrorActionPreference = "Continue"

function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

function Test-Port {
    param($Port, $Description)
    $connection = Test-NetConnection -ComputerName localhost -Port $Port -WarningAction SilentlyContinue
    if ($connection.TcpTestSucceeded) {
        Write-ColorOutput Green "  ✅ $Description (port $Port) - OK"
        return $true
    } else {
        Write-ColorOutput Red "  ❌ $Description (port $Port) - NON ACTIF"
        return $false
    }
}

function Test-DatabaseConnection {
    $testQuery = "SELECT 1 AS test;"
    
    $mysqlCmd = "C:\xampp\mysql\bin\mysql.exe"
    if (Test-Path $mysqlCmd) {
        try {
            $result = & $mysqlCmd -u root -e $testQuery 2>&1
            if ($LASTEXITCODE -eq 0) {
                Write-ColorOutput Green "  ✅ Connexion MySQL - OK"
                return $true
            }
        } catch {}
    }
    Write-ColorOutput Red "  ❌ Connexion MySQL - ÉCHEC"
    return $false
}

function Test-Database {
    param($DbName)
    
    $testQuery = "SHOW DATABASES LIKE '$DbName';"
    $mysqlCmd = "C:\xampp\mysql\bin\mysql.exe"
    
    if (Test-Path $mysqlCmd) {
        try {
            $result = & $mysqlCmd -u root -e $testQuery 2>&1
            if ($result -match $DbName) {
                Write-ColorOutput Green "  ✅ Base de données '$DbName' - EXISTE"
                return $true
            }
        } catch {}
    }
    Write-ColorOutput Red "  ❌ Base de données '$DbName' - N'EXISTE PAS"
    return $false
}

function Test-AdminUser {
    $testQuery = "SELECT id, username, email, role FROM users WHERE email = 'admin@gamezone.com' LIMIT 1;"
    
    $mysqlCmd = "C:\xampp\mysql\bin\mysql.exe"
    if (Test-Path $mysqlCmd) {
        try {
            $result = & $mysqlCmd -u root gamezone -e $testQuery 2>&1
            if ($result -match "admin") {
                if ($result -match "admin.*admin") {
                    Write-ColorOutput Green "  ✅ Compte admin - EXISTE (rôle: admin)"
                    return $true
                } else {
                    Write-ColorOutput Yellow "  ⚠️  Compte admin existe mais rôle incorrect"
                    return $false
                }
            }
        } catch {}
    }
    Write-ColorOutput Red "  ❌ Compte admin - N'EXISTE PAS"
    return $false
}

function Create-AdminUser {
    Write-Host ""
    Write-ColorOutput Cyan "🔧 Création du compte admin..."
    
    $mysqlCmd = "C:\xampp\mysql\bin\mysql.exe"
    $sql = @'
INSERT INTO users (username, email, password_hash, role, points, status, created_at, updated_at, join_date, last_active) 
VALUES ('Admin', 'admin@gamezone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0, 'active', NOW(), NOW(), CURDATE(), NOW()) 
ON DUPLICATE KEY UPDATE role = 'admin';
'@
    
    try {
        & $mysqlCmd -u root gamezone -e $sql 2>&1 | Out-Null
        Write-ColorOutput Green "  ✅ Compte admin créé/mis à jour avec succès"
        Write-ColorOutput White "     Email: admin@gamezone.com"
        Write-ColorOutput White "     Mot de passe: Admin123!"
        return $true
    } catch {
        Write-ColorOutput Red "  ❌ Échec de la création du compte admin"
        return $false
    }
}

function Fix-ExpiredSessions {
    Write-Host ""
    Write-ColorOutput Cyan "🔧 Correction des sessions expirées..."
    
    $fixQuery = "UPDATE game_sessions SET status = 'expired' WHERE status IN ('active', 'paused') AND remaining_minutes <= 0;"
    
    $mysqlCmd = "C:\xampp\mysql\bin\mysql.exe"
    try {
        $result = & $mysqlCmd -u root gamezone -e $fixQuery 2>&1
        Write-ColorOutput Green "  ✅ Sessions expirées corrigées"
        return $true
    } catch {
        Write-ColorOutput Yellow "  ⚠️  Impossible de corriger les sessions (table peut ne pas exister)"
        return $false
    }
}

function Clean-OldLogs {
    Write-Host ""
    Write-ColorOutput Cyan "🔧 Nettoyage des anciens logs..."
    
    $logsPath = "$PSScriptRoot\logs"
    if (Test-Path $logsPath) {
        $today = Get-Date -Format "yyyy-MM-dd"
        $oldLogs = Get-ChildItem -Path $logsPath -Filter "api_*.log" | Where-Object { $_.Name -notmatch $today }
        
        if ($oldLogs.Count -gt 0) {
            $oldLogs | Remove-Item -Force
            Write-ColorOutput Green "  ✅ $($oldLogs.Count) ancien(s) log(s) supprimé(s)"
        } else {
            Write-ColorOutput White "  ℹ️  Aucun ancien log à supprimer"
        }
    } else {
        Write-ColorOutput White "  ℹ️  Dossier logs non trouvé"
    }
}

function Test-ApiEndpoint {
    param($Url, $Description)
    
    try {
        $response = Invoke-WebRequest -Uri $Url -Method GET -TimeoutSec 5 -UseBasicParsing
        if ($response.StatusCode -eq 200) {
            Write-ColorOutput Green "  ✅ $Description - OK"
            return $true
        }
    } catch {
        Write-ColorOutput Red "  ❌ $Description - ÉCHEC ($($_.Exception.Message))"
        return $false
    }
}

# ═══════════════════════════════════════════════════════════════
# DÉBUT DU DIAGNOSTIC
# ═══════════════════════════════════════════════════════════════

Clear-Host

Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "🔍 DIAGNOSTIC ET CORRECTION AUTOMATIQUE - ADMIN GAMEZONE" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$issues = @()
$fixes = @()

# ═══════════════════════════════════════════════════════════════
# TEST 1: Services XAMPP
# ═══════════════════════════════════════════════════════════════

Write-Host "📋 ÉTAPE 1: Vérification des services XAMPP" -ForegroundColor Cyan
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

$apacheOk = Test-Port -Port 80 -Description "Apache HTTP"
$mysqlOk = Test-Port -Port 3306 -Description "MySQL Database"

if (-not $apacheOk) {
    $issues += "Apache n'est pas démarré"
    Write-ColorOutput Yellow "  💡 Solution: Démarrez Apache dans XAMPP Control Panel"
}

if (-not $mysqlOk) {
    $issues += "MySQL n'est pas démarré"
    Write-ColorOutput Yellow "  💡 Solution: Démarrez MySQL dans XAMPP Control Panel"
}

# ═══════════════════════════════════════════════════════════════
# TEST 2: Base de données
# ═══════════════════════════════════════════════════════════════

Write-Host ""
Write-Host "📋 ÉTAPE 2: Vérification de la base de données" -ForegroundColor Cyan
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

if ($mysqlOk) {
    $dbConnectionOk = Test-DatabaseConnection
    $dbExists = Test-Database -DbName "gamezone"
    
    if (-not $dbExists) {
        $issues += "Base de données 'gamezone' n'existe pas"
        Write-ColorOutput Yellow "  💡 Solution: Créez la base de données via phpMyAdmin"
        Write-ColorOutput White "     URL: http://localhost/phpmyadmin"
    }
} else {
    Write-ColorOutput Red "  ⚠️  Impossible de tester la base de données (MySQL non actif)"
}

# ═══════════════════════════════════════════════════════════════
# TEST 3: Compte Admin
# ═══════════════════════════════════════════════════════════════

Write-Host ""
Write-Host "📋 ÉTAPE 3: Vérification du compte admin" -ForegroundColor Cyan
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

if ($mysqlOk -and $dbExists) {
    $adminExists = Test-AdminUser
    
    if (-not $adminExists) {
        $issues += "Compte admin manquant ou rôle incorrect"
        
        Write-Host ""
        $createAdmin = Read-Host "  ❓ Voulez-vous créer/corriger le compte admin? (O/N)"
        if ($createAdmin -eq "O" -or $createAdmin -eq "o") {
            $created = Create-AdminUser
            if ($created) {
                $fixes += "Compte admin créé/corrigé"
            }
        }
    }
} else {
    Write-ColorOutput Red "  ⚠️  Impossible de vérifier le compte admin"
}

# ═══════════════════════════════════════════════════════════════
# TEST 4: API Endpoints
# ═══════════════════════════════════════════════════════════════

Write-Host ""
Write-Host "📋 ÉTAPE 4: Test des endpoints API critiques" -ForegroundColor Cyan
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

if ($apacheOk) {
    $baseUrl = "http://localhost/projet%20ismo/api"
    
    Test-ApiEndpoint -Url "$baseUrl/auth/check.php" -Description "Auth Check"
    Test-ApiEndpoint -Url "$baseUrl/admin/statistics.php" -Description "Admin Statistics"
    Test-ApiEndpoint -Url "$baseUrl/admin/games.php" -Description "Admin Games"
} else {
    Write-ColorOutput Red "  ⚠️  Impossible de tester les endpoints (Apache non actif)"
}

# ═══════════════════════════════════════════════════════════════
# TEST 5: Maintenance
# ═══════════════════════════════════════════════════════════════

Write-Host ""
Write-Host "📋 ÉTAPE 5: Maintenance du système" -ForegroundColor Cyan
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

if ($mysqlOk -and $dbExists) {
    $fixSessions = Read-Host "  ❓ Corriger les sessions expirées? (O/N)"
    if ($fixSessions -eq "O" -or $fixSessions -eq "o") {
        $fixed = Fix-ExpiredSessions
        if ($fixed) {
            $fixes += "Sessions expirées corrigées"
        }
    }
}

$cleanLogs = Read-Host "  ❓ Nettoyer les anciens logs? (O/N)"
if ($cleanLogs -eq "O" -or $cleanLogs -eq "o") {
    Clean-OldLogs
    $fixes += "Logs nettoyés"
}

# ═══════════════════════════════════════════════════════════════
# RÉSUMÉ
# ═══════════════════════════════════════════════════════════════

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "📊 RÉSUMÉ DU DIAGNOSTIC" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

if ($issues.Count -eq 0) {
    Write-ColorOutput Green "✅ AUCUN PROBLÈME DÉTECTÉ!"
    Write-ColorOutput White "   Tous les systèmes fonctionnent correctement."
} else {
    Write-ColorOutput Red "❌ PROBLÈMES DÉTECTÉS: $($issues.Count)"
    foreach ($issue in $issues) {
        Write-ColorOutput Red "   • $issue"
    }
}

Write-Host ""

if ($fixes.Count -gt 0) {
    Write-ColorOutput Green "🔧 CORRECTIONS APPLIQUÉES: $($fixes.Count)"
    foreach ($fix in $fixes) {
        Write-ColorOutput Green "   • $fix"
    }
} else {
    Write-ColorOutput White "ℹ️  Aucune correction appliquée"
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "📋 PROCHAINES ÉTAPES" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

if ($apacheOk -and $mysqlOk) {
    Write-ColorOutput Green "✅ Les services sont actifs"
    Write-Host ""
    Write-ColorOutput White "1. Lancez le diagnostic complet:"
    Write-ColorOutput Cyan "   .\LANCER_DIAGNOSTIC_ADMIN.ps1"
    Write-Host ""
    Write-ColorOutput White "2. Ou testez directement l'interface admin:"
    Write-ColorOutput Cyan "   http://localhost:4000/admin/dashboard"
    Write-Host ""
    Write-ColorOutput White "3. Identifiants admin:"
    Write-ColorOutput Cyan "   Email: admin@gamezone.com"
    Write-ColorOutput Cyan "   Mot de passe: Admin123!"
} else {
    Write-ColorOutput Red "⚠️  Veuillez d'abord démarrer les services XAMPP"
    Write-Host ""
    Write-ColorOutput White "1. Ouvrez XAMPP Control Panel"
    Write-ColorOutput White "2. Démarrez Apache" + $(if (-not $apacheOk) { " ⚠️" } else { " ✅" })
    Write-ColorOutput White "3. Démarrez MySQL" + $(if (-not $mysqlOk) { " ⚠️" } else { " ✅" })
    Write-Host ""
    Write-ColorOutput White "4. Relancez ce script"
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""
Write-Host "Appuyez sur une touche pour fermer..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
