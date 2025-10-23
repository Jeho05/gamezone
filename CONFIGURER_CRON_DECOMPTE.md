# Configuration du Décompte Automatique

## 🎯 Problème
Le décompte des minutes de session ne s'exécute pas automatiquement, causant des sessions bloquées à 100%.

## ✅ Solution: CRON Job

### Option 1: Planificateur de Tâches Windows (Recommandé)

1. **Créer un script de décompte**:

Fichier: `api/cron/run_countdown.bat`
```batch
@echo off
C:\xampp\mysql\bin\mysql.exe -u root gamezone -e "CALL countdown_active_sessions();"
```

2. **Ouvrir le Planificateur de Tâches**:
   - Appuyez sur `Win + R`
   - Tapez `taskschd.msc`
   - Cliquez sur "Créer une tâche"

3. **Configuration**:
   - **Nom**: Décompte Sessions GameZone
   - **Déclencheur**: Toutes les 1 minutes
   - **Action**: Démarrer un programme
     - Programme: `C:\xampp\htdocs\projet ismo\api\cron\run_countdown.bat`
   - **Conditions**: Décocher "Démarrer uniquement si sur secteur"

### Option 2: Script PowerShell avec Boucle

Fichier: `api/cron/countdown_loop.ps1`
```powershell
while ($true) {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Exécution du décompte..."
    
    & "C:\xampp\mysql\bin\mysql.exe" -u root gamezone -e "CALL countdown_active_sessions();"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "[OK] Décompte exécuté avec succès" -ForegroundColor Green
    } else {
        Write-Host "[ERREUR] Échec du décompte" -ForegroundColor Red
    }
    
    # Attendre 60 secondes
    Start-Sleep -Seconds 60
}
```

**Lancer en arrière-plan**:
```powershell
Start-Process powershell -ArgumentList "-NoExit", "-File", "api\cron\countdown_loop.ps1" -WindowStyle Minimized
```

### Option 3: Via PHP (pour développement)

Fichier: `api/cron/countdown_sessions.php` (déjà existant)
```php
<?php
require_once __DIR__ . '/../config.php';

$pdo = get_db();
$stmt = $pdo->query('CALL countdown_active_sessions()');

echo json_encode(['success' => true, 'timestamp' => date('Y-m-d H:i:s')]);
```

**Appeler via cURL en boucle**:
```batch
:loop
curl http://localhost/projet%%20ismo/api/cron/countdown_sessions.php
timeout /t 60 /nobreak
goto loop
```

## 🔍 Vérification

Après configuration, vérifiez que ça fonctionne:

```sql
-- Démarrer une session de 1 minute
-- Attendre 2 minutes
-- Vérifier qu'elle est automatiquement completed:

SELECT id, status, used_minutes, total_minutes 
FROM active_game_sessions_v2 
WHERE status = 'completed' 
ORDER BY completed_at DESC 
LIMIT 5;
```

## 📊 Monitoring

Vérifier les sessions actives:

```sql
SELECT 
  COUNT(*) as total_active,
  SUM(CASE WHEN used_minutes >= total_minutes THEN 1 ELSE 0 END) as should_be_completed
FROM active_game_sessions_v2 
WHERE status = 'active';
```

Si `should_be_completed > 0`, le CRON ne fonctionne pas correctement.
