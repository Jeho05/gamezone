# Script PowerShell pour créer une tâche planifiée Windows
# Exécute le décompte automatique chaque minute

$taskName = "GameZone_AutoCountdown"
$phpPath = "C:\xampp\php\php.exe"
$scriptPath = "C:\xampp\htdocs\projet ismo\api\cron\countdown_sessions.php"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Installation Tache Planifiee GameZone" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier si la tâche existe déjà
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

if ($existingTask) {
    Write-Host "La tache existe deja. Suppression..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Créer l'action
$action = New-ScheduledTaskAction -Execute $phpPath -Argument "`"$scriptPath`""

# Créer le trigger (toutes les minutes)
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1)

# Créer les paramètres
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

# Créer la tâche
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description "GameZone - Decompte automatique des sessions de jeu toutes les minutes"

Write-Host ""
Write-Host "Tache planifiee creee avec succes!" -ForegroundColor Green
Write-Host ""
Write-Host "Nom: $taskName" -ForegroundColor White
Write-Host "Frequence: Toutes les minutes" -ForegroundColor White
Write-Host "Script: $scriptPath" -ForegroundColor White
Write-Host ""
Write-Host "Pour verifier:" -ForegroundColor Cyan
Write-Host "  Get-ScheduledTask -TaskName '$taskName'" -ForegroundColor Gray
Write-Host ""
Write-Host "Pour desactiver:" -ForegroundColor Cyan
Write-Host "  Disable-ScheduledTask -TaskName '$taskName'" -ForegroundColor Gray
Write-Host ""
Write-Host "Pour supprimer:" -ForegroundColor Cyan
Write-Host "  Unregister-ScheduledTask -TaskName '$taskName'" -ForegroundColor Gray
Write-Host ""
