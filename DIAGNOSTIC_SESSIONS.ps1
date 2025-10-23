# ============================================================================
# Script de Diagnostic et Réparation des Sessions
# ============================================================================

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  DIAGNOSTIC SESSIONS GAMEZONE" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

$MYSQL = "C:\xampp\mysql\bin\mysql.exe"
$DB = "gamezone"

# ============================================================================
# 1. Sessions bloquées à 100%
# ============================================================================

Write-Host "[1/5] Vérification des sessions à 100%..." -ForegroundColor Yellow

$query1 = @"
SELECT 
  COUNT(*) as blocked_sessions
FROM active_game_sessions_v2 
WHERE status = 'active' 
  AND used_minutes >= total_minutes;
"@

$result1 = & $MYSQL -u root $DB -e $query1 2>&1 | Select-String -Pattern "^\d+$"
$blocked = if ($result1) { [int]$result1.Line } else { 0 }

if ($blocked -gt 0) {
    Write-Host "  [PROBLEME] $blocked session(s) bloquée(s) à 100%" -ForegroundColor Red
    Write-Host "  [ACTION] Exécution du décompte automatique..." -ForegroundColor Yellow
    
    & $MYSQL -u root $DB -e "CALL countdown_active_sessions();" | Out-Null
    
    Write-Host "  [OK] Décompte exécuté" -ForegroundColor Green
} else {
    Write-Host "  [OK] Aucune session bloquée" -ForegroundColor Green
}

Write-Host ""

# ============================================================================
# 2. Sessions avec temps négatif
# ============================================================================

Write-Host "[2/5] Vérification des sessions avec temps négatif..." -ForegroundColor Yellow

$query2 = @"
SELECT 
  id, 
  total_minutes, 
  used_minutes,
  TIMESTAMPDIFF(MINUTE, started_at, NOW()) as real_elapsed
FROM active_game_sessions_v2 
WHERE status = 'active' 
  AND started_at IS NOT NULL
  AND TIMESTAMPDIFF(MINUTE, started_at, NOW()) > total_minutes;
"@

$result2 = & $MYSQL -u root $DB -e $query2 2>&1
$hasNegative = $result2 | Select-String -Pattern "^\d+\s+"

if ($hasNegative) {
    Write-Host "  [PROBLEME] Sessions dépassées détectées" -ForegroundColor Red
    Write-Host "  [ACTION] Forçage de la complétion..." -ForegroundColor Yellow
    
    $forceComplete = @"
UPDATE active_game_sessions_v2 
SET status = 'completed', 
    used_minutes = total_minutes, 
    completed_at = NOW() 
WHERE status = 'active' 
  AND started_at IS NOT NULL
  AND TIMESTAMPDIFF(MINUTE, started_at, NOW()) > total_minutes;
"@
    
    & $MYSQL -u root $DB -e $forceComplete | Out-Null
    Write-Host "  [OK] Sessions forcées à completed" -ForegroundColor Green
} else {
    Write-Host "  [OK] Aucune session dépassée" -ForegroundColor Green
}

Write-Host ""

# ============================================================================
# 3. Vérifier la synchronisation purchases <-> sessions
# ============================================================================

Write-Host "[3/5] Vérification de la synchronisation..." -ForegroundColor Yellow

$query3 = @"
SELECT COUNT(*) as mismatches
FROM purchases p
INNER JOIN active_game_sessions_v2 s ON p.id = s.purchase_id
WHERE p.session_status != s.status;
"@

$result3 = & $MYSQL -u root $DB -e $query3 2>&1 | Select-String -Pattern "^\d+$"
$mismatches = if ($result3) { [int]$result3.Line } else { 0 }

if ($mismatches -gt 0) {
    Write-Host "  [PROBLEME] $mismatches incohérence(s) détectée(s)" -ForegroundColor Red
    Write-Host "  [ACTION] Synchronisation..." -ForegroundColor Yellow
    
    & $MYSQL -u root $DB -e "CALL sync_purchase_session_status();" | Out-Null
    
    Write-Host "  [OK] Synchronisation effectuée" -ForegroundColor Green
} else {
    Write-Host "  [OK] Tout est synchronisé" -ForegroundColor Green
}

Write-Host ""

# ============================================================================
# 4. Sessions actives actuellement
# ============================================================================

Write-Host "[4/5] État des sessions actives..." -ForegroundColor Yellow

$query4 = @"
SELECT 
  status,
  COUNT(*) as count
FROM active_game_sessions_v2
WHERE status IN ('ready', 'active', 'paused')
GROUP BY status;
"@

$result4 = & $MYSQL -u root $DB -e $query4 2>&1

Write-Host "  Sessions par statut:" -ForegroundColor White
$result4 | ForEach-Object {
    if ($_ -match "ready") {
        Write-Host "    - $_" -ForegroundColor Cyan
    } elseif ($_ -match "active") {
        Write-Host "    - $_" -ForegroundColor Green
    } elseif ($_ -match "paused") {
        Write-Host "    - $_" -ForegroundColor Yellow
    }
}

Write-Host ""

# ============================================================================
# 5. Factures en attente d'activation
# ============================================================================

Write-Host "[5/5] Factures en attente..." -ForegroundColor Yellow

$query5 = @"
SELECT COUNT(*) as pending_invoices
FROM invoices
WHERE status = 'pending';
"@

$result5 = & $MYSQL -u root $DB -e $query5 2>&1 | Select-String -Pattern "^\d+$"
$pending = if ($result5) { [int]$result5.Line } else { 0 }

if ($pending -gt 0) {
    Write-Host "  [INFO] $pending facture(s) en attente de scan" -ForegroundColor Cyan
} else {
    Write-Host "  [INFO] Aucune facture en attente" -ForegroundColor Gray
}

Write-Host ""

# ============================================================================
# RÉSUMÉ
# ============================================================================

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  RÉSUMÉ DU DIAGNOSTIC" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

if ($blocked -eq 0 -and $mismatches -eq 0) {
    Write-Host "  ✓ Système en bon état" -ForegroundColor Green
    Write-Host ""
    Write-Host "  Si vous ne pouvez toujours pas démarrer une session:" -ForegroundColor Yellow
    Write-Host "    1. Vérifiez que la facture est bien activée (status='active')" -ForegroundColor Gray
    Write-Host "    2. Vérifiez que la session existe et est en status='ready'" -ForegroundColor Gray
    Write-Host "    3. Consultez les logs: session_events" -ForegroundColor Gray
} else {
    Write-Host "  ⚠ Des problèmes ont été détectés et corrigés" -ForegroundColor Yellow
    Write-Host "  Relancez ce script pour vérifier" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================================================
# ACTIONS RECOMMANDÉES
# ============================================================================

Write-Host "ACTIONS RECOMMANDÉES:" -ForegroundColor White
Write-Host ""
Write-Host "1. Configurer le décompte automatique:" -ForegroundColor Yellow
Write-Host "   Consultez: CONFIGURER_CRON_DECOMPTE.md" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Exécuter manuellement le décompte:" -ForegroundColor Yellow
Write-Host "   .\api\cron\run_countdown.bat" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Vérifier les logs en temps réel:" -ForegroundColor Yellow
Write-Host "   SELECT * FROM session_events ORDER BY created_at DESC LIMIT 20;" -ForegroundColor Gray
Write-Host ""

Write-Host "Appuyez sur une touche pour continuer..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
