# Script simplifié de diagnostic admin
# Version corrigée et fonctionnelle

Clear-Host

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "     DIAGNOSTIC RAPIDE ADMIN - GAMEZONE" -ForegroundColor Yellow
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

# Test Apache
Write-Host "[1/5] Test Apache (port 80)..." -ForegroundColor Cyan
$apache = Test-NetConnection -ComputerName localhost -Port 80 -WarningAction SilentlyContinue
if ($apache.TcpTestSucceeded) {
    Write-Host "  ✅ Apache ACTIF" -ForegroundColor Green
} else {
    Write-Host "  ❌ Apache NON ACTIF - Démarrez Apache dans XAMPP" -ForegroundColor Red
}

# Test MySQL
Write-Host ""
Write-Host "[2/5] Test MySQL (port 3306)..." -ForegroundColor Cyan
$mysql = Test-NetConnection -ComputerName localhost -Port 3306 -WarningAction SilentlyContinue
if ($mysql.TcpTestSucceeded) {
    Write-Host "  ✅ MySQL ACTIF" -ForegroundColor Green
} else {
    Write-Host "  ❌ MySQL NON ACTIF - Démarrez MySQL dans XAMPP" -ForegroundColor Red
}

# Test API
Write-Host ""
Write-Host "[3/5] Test API Backend..." -ForegroundColor Cyan
if ($apache.TcpTestSucceeded) {
    try {
        $response = Invoke-WebRequest -Uri "http://localhost/projet%20ismo/api/auth/check.php" -UseBasicParsing -TimeoutSec 5
        Write-Host "  ✅ API Backend accessible (Status: $($response.StatusCode))" -ForegroundColor Green
    } catch {
        Write-Host "  ⚠️  API répond mais erreur: $($_.Exception.Message)" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ⚠️  Test ignoré (Apache non actif)" -ForegroundColor Yellow
}

# Test Base de données
Write-Host ""
Write-Host "[4/5] Test Base de données..." -ForegroundColor Cyan
if ($mysql.TcpTestSucceeded) {
    $mysqlCmd = "C:\xampp\mysql\bin\mysql.exe"
    if (Test-Path $mysqlCmd) {
        try {
            $dbTest = & $mysqlCmd -u root -e "SHOW DATABASES LIKE 'gamezone';" 2>&1
            if ($dbTest -match "gamezone") {
                Write-Host "  ✅ Base de données 'gamezone' existe" -ForegroundColor Green
                
                # Test compte admin
                try {
                    $adminTest = & $mysqlCmd -u root gamezone -e "SELECT username, role FROM users WHERE email='admin@gamezone.com' LIMIT 1;" 2>&1
                    if ($adminTest -match "admin") {
                        Write-Host "  ✅ Compte admin existe" -ForegroundColor Green
                    } else {
                        Write-Host "  ⚠️  Compte admin non trouvé" -ForegroundColor Yellow
                    }
                } catch {
                    Write-Host "  ⚠️  Impossible de vérifier le compte admin" -ForegroundColor Yellow
                }
            } else {
                Write-Host "  ❌ Base de données 'gamezone' n'existe pas" -ForegroundColor Red
            }
        } catch {
            Write-Host "  ❌ Erreur MySQL: $($_.Exception.Message)" -ForegroundColor Red
        }
    } else {
        Write-Host "  ❌ MySQL client non trouvé à $mysqlCmd" -ForegroundColor Red
    }
} else {
    Write-Host "  ⚠️  Test ignoré (MySQL non actif)" -ForegroundColor Yellow
}

# Test Frontend
Write-Host ""
Write-Host "[5/5] Test Frontend React..." -ForegroundColor Cyan
$frontend = Test-NetConnection -ComputerName localhost -Port 4000 -WarningAction SilentlyContinue
if ($frontend.TcpTestSucceeded) {
    Write-Host "  ✅ Frontend React actif sur http://localhost:4000" -ForegroundColor Green
} else {
    Write-Host "  ⚠️  Frontend non actif - Lancez 'npm run dev' dans le dossier web" -ForegroundColor Yellow
}

# Résumé
Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "     RÉSUMÉ ET ACTIONS" -ForegroundColor Yellow
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

if ($apache.TcpTestSucceeded -and $mysql.TcpTestSucceeded) {
    Write-Host "✅ SERVICES PRINCIPAUX ACTIFS" -ForegroundColor Green
    Write-Host ""
    Write-Host "Prochaines étapes:" -ForegroundColor White
    Write-Host ""
    Write-Host "1. Ouvrir le diagnostic complet:" -ForegroundColor Cyan
    Write-Host "   Double-cliquez sur: DIAGNOSTIC_ADMIN_COMPLET.html" -ForegroundColor Gray
    Write-Host ""
    Write-Host "2. Se connecter avec:" -ForegroundColor Cyan
    Write-Host "   Email: admin@gamezone.com" -ForegroundColor Gray
    Write-Host "   Mot de passe: Admin123!" -ForegroundColor Gray
    Write-Host ""
    Write-Host "3. Cliquer sur 'Tester Tous les Endpoints'" -ForegroundColor Cyan
    Write-Host ""
} else {
    Write-Host "❌ PROBLÈMES DÉTECTÉS" -ForegroundColor Red
    Write-Host ""
    if (-not $apache.TcpTestSucceeded) {
        Write-Host "• Démarrez Apache dans XAMPP Control Panel" -ForegroundColor Yellow
    }
    if (-not $mysql.TcpTestSucceeded) {
        Write-Host "• Démarrez MySQL dans XAMPP Control Panel" -ForegroundColor Yellow
    }
    Write-Host ""
    Write-Host "Puis relancez ce script." -ForegroundColor White
}

Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Appuyez sur une touche pour continuer..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')
