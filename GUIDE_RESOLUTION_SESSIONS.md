# 🔧 Guide de Résolution: Problèmes de Sessions

## 🎯 Problèmes Fréquents & Solutions

### ❌ Problème 1: Sessions à 100% non terminées

**Symptôme**: Des sessions affichent 100% de progression mais restent en statut "active".

**Cause**: Le décompte automatique ne s'exécute pas.

**Solution Immédiate**:
```batch
.\REPARER_SESSIONS.bat
```

**Solution Permanente**: Configurer le CRON (voir `CONFIGURER_CRON_DECOMPTE.md`)

---

### ❌ Problème 2: Impossible de démarrer une session

**Symptôme**: Le bouton "Démarrer la Session" ne fonctionne pas ou affiche une erreur.

**Causes possibles**:

#### A. La session n'est pas en statut "ready"
```sql
-- Vérifier le statut de la session
SELECT id, status FROM active_game_sessions_v2 WHERE id = [SESSION_ID];
```

**Solution**: Si le statut n'est pas "ready", la facture n'a peut-être pas été activée correctement.

#### B. La session n'existe pas
```sql
-- Vérifier si la session existe
SELECT * FROM active_game_sessions_v2 WHERE invoice_id = [INVOICE_ID];
```

**Solution**: Scanner à nouveau la facture pour créer la session.

#### C. La facture n'est pas encore activée
```sql
-- Vérifier le statut de la facture
SELECT id, status, validation_code FROM invoices WHERE invoice_number = '[NUMERO]';
```

**Solution**: Scanner le code QR de la facture pour l'activer.

---

### ❌ Problème 3: Incohérences purchases ↔ sessions

**Symptôme**: `purchases.session_status` ne correspond pas à `active_game_sessions_v2.status`.

**Vérification**:
```sql
SELECT * FROM purchase_session_overview WHERE sync_status = 'MISMATCH';
```

**Solution**:
```batch
.\REPARER_SESSIONS.bat
```

Ou manuellement:
```sql
CALL sync_purchase_session_status();
```

---

## 🛠️ Outils de Diagnostic

### 1. Script de Réparation Rapide
```batch
.\REPARER_SESSIONS.bat
```

Exécute automatiquement:
- Décompte des minutes
- Synchronisation des statuts
- Vérification des incohérences

### 2. Vérification Manuelle

#### État global des sessions:
```sql
SELECT 
  status,
  COUNT(*) as count,
  SUM(total_minutes) as total_minutes,
  SUM(used_minutes) as used_minutes
FROM active_game_sessions_v2
GROUP BY status;
```

#### Sessions problématiques:
```sql
SELECT 
  s.id,
  s.status,
  s.total_minutes,
  s.used_minutes,
  TIMESTAMPDIFF(MINUTE, s.started_at, NOW()) as real_elapsed,
  u.username
FROM active_game_sessions_v2 s
INNER JOIN users u ON s.user_id = u.id
WHERE s.status = 'active'
  AND s.started_at IS NOT NULL
  AND TIMESTAMPDIFF(MINUTE, s.started_at, NOW()) > s.total_minutes;
```

#### Factures en attente:
```sql
SELECT 
  i.id,
  i.invoice_number,
  i.validation_code,
  i.status,
  u.username,
  i.game_name
FROM invoices i
INNER JOIN users u ON i.user_id = u.id
WHERE i.status = 'pending'
ORDER BY i.issued_at DESC;
```

---

## 📋 Checklist de Démarrage d'une Session

Avant de démarrer une session, vérifiez:

- [ ] **Étape 1**: L'achat est payé (`purchases.payment_status = 'completed'`)
- [ ] **Étape 2**: La facture est créée (`invoices.status = 'pending'` ou `'active'`)
- [ ] **Étape 3**: Scanner le code QR pour activer la facture
- [ ] **Étape 4**: La session est créée avec statut "ready"
- [ ] **Étape 5**: Cliquer sur "Démarrer la Session"
- [ ] **Étape 6**: Vérifier que le statut passe à "active"

---

## 🔍 Logs à Consulter

### Session Events
```sql
SELECT * FROM session_events 
WHERE session_id = [SESSION_ID] 
ORDER BY created_at DESC;
```

### Invoice Scans
```sql
SELECT * FROM invoice_scans 
WHERE validation_code = '[CODE]' 
ORDER BY scanned_at DESC;
```

### Invoice Audit
```sql
SELECT * FROM invoice_audit_log 
WHERE invoice_id = [INVOICE_ID] 
ORDER BY created_at DESC;
```

---

## 🚨 Problèmes Critiques

### Session Bloquée Définitivement

Si une session est vraiment bloquée, forcer la complétion:

```sql
UPDATE active_game_sessions_v2 
SET status = 'completed',
    used_minutes = total_minutes,
    completed_at = NOW()
WHERE id = [SESSION_ID];

-- Marquer la facture comme utilisée
UPDATE invoices 
SET status = 'used', 
    used_at = NOW()
WHERE id = (SELECT invoice_id FROM active_game_sessions_v2 WHERE id = [SESSION_ID]);
```

Le trigger synchronisera automatiquement `purchases.session_status`.

---

## 📞 Aide Supplémentaire

Si le problème persiste après toutes ces vérifications:

1. **Vérifiez les logs Apache/PHP** dans `logs/api_[DATE].log`
2. **Consultez la console du navigateur** (F12) pour les erreurs JavaScript
3. **Rechargez complètement la page** (Ctrl+F5)
4. **Videz le cache du navigateur**

---

## ✅ Prévention

Pour éviter ces problèmes à l'avenir:

### 1. Configurer le Décompte Automatique

Voir: `CONFIGURER_CRON_DECOMPTE.md`

### 2. Monitoring Quotidien

Ajoutez à votre routine:

```sql
-- Vérifier les incohérences
SELECT COUNT(*) FROM purchase_session_overview WHERE sync_status = 'MISMATCH';

-- Vérifier les sessions bloquées
SELECT COUNT(*) FROM active_game_sessions_v2 
WHERE status = 'active' AND used_minutes >= total_minutes;
```

### 3. Script de Maintenance Hebdomadaire

```batch
REM Nettoyer les vieilles sessions terminées (> 30 jours)
mysql -u root gamezone -e "DELETE FROM active_game_sessions_v2 WHERE status IN ('completed', 'terminated') AND completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);"

REM Synchroniser
.\REPARER_SESSIONS.bat
```

---

## 🎉 Résumé

**En cas de problème, la solution rapide est:**

```batch
.\REPARER_SESSIONS.bat
```

**Pour prévenir les problèmes:**
- Configurez le décompte automatique (CRON)
- Surveillez régulièrement les incohérences
- Gardez le système à jour

---

**Questions?** Consultez la documentation complète:
- `GUIDE_CORRECTIONS_SESSIONS_ACHATS.md`
- `RECAPITULATIF_CORRECTIONS_SESSIONS.md`
