# Script PowerShell pour corriger le fichier admin sessions avec le système temps réel

$filePath = "C:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web\src\app\admin\sessions\page.jsx"

Write-Host "=== CORRECTION ADMIN SESSIONS TEMPS RÉEL ===" -ForegroundColor Cyan
Write-Host ""

# Lire le contenu du fichier
$content = Get-Content $filePath -Raw

# Backup du fichier original
$backupPath = $filePath + ".backup"
Copy-Item $filePath $backupPath
Write-Host "✓ Backup créé: $backupPath" -ForegroundColor Green

# 1. Corriger la ligne 172 - Texte de description
$content = $content -replace 'Surveillance en temps réel • Auto-refresh toutes les 5 secondes', 'Temps réel côté client • Sync serveur: 30s'

# 2. Corriger les lignes 281-282 et 306-308 - Utiliser calculateRemainingTime
$old1 = @'
                      const progressPercent = session.progress_percent || 0;
                      const isLowTime = session.remaining_minutes <= 10 && session.status === 'active';
'@

$new1 = @'
                      const remainingTime = calculateRemainingTime(session);
                      const progressPercent = Math.round((remainingTime / session.total_minutes) * 100);
                      const isLowTime = remainingTime <= 10 && session.status === 'active';
'@

$content = $content -replace [regex]::Escape($old1), $new1

# 3. Corriger l'affichage du temps restant ligne 306-308
$old2 = @'
                                <span className={isLowTime ? 'text-red-600 font-bold' : ''}>
                                  {formatTime(session.remaining_minutes)} restant
                                </span>
'@

$new2 = @'
                                <span className={isLowTime ? 'text-red-600 font-bold animate-pulse' : ''}>
                                  {formatTime(remainingTime)} restant
                                  {remainingTime === 0 && ' - EXPIRÉ'}
                                </span>
'@

$content = $content -replace [regex]::Escape($old2), $new2

# 4. Corriger l'affichage used_minutes ligne 310-312
$old3 = @'
                              <div className="text-xs text-gray-500">
                                {formatTime(session.used_minutes)} / {formatTime(session.total_minutes)}
                              </div>
'@

$new3 = @'
                              <div className="text-xs text-gray-500">
                                {formatTime(session.total_minutes - remainingTime)} / {formatTime(session.total_minutes)}
                              </div>
'@

$content = $content -replace [regex]::Escape($old3), $new3

# Sauvegarder le fichier modifié
Set-Content -Path $filePath -Value $content -NoNewline

Write-Host "✓ Fichier corrigé avec succès" -ForegroundColor Green
Write-Host ""
Write-Host "Modifications appliquées:" -ForegroundColor Yellow
Write-Host "  1. Texte 'Temps réel côté client • Sync serveur: 30s'" -ForegroundColor White
Write-Host "  2. calculateRemainingTime() utilisé pour le temps" -ForegroundColor White
Write-Host "  3. Progress bar calculée dynamiquement" -ForegroundColor White
Write-Host "  4. Alerte 'EXPIRÉ' ajoutée" -ForegroundColor White
Write-Host ""
Write-Host "Pour restaurer l'original:" -ForegroundColor Cyan
Write-Host "  Copy-Item '$backupPath' '$filePath'" -ForegroundColor Gray
