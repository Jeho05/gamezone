# 🔥 GUIDE SYSTÈME TEMPS RÉEL

## ✅ Modifications Appliquées

### 1️⃣ Interface Admin - Refresh Accéléré
**Fichier:** `createxyz-project\_\apps\web\src\app\admin\sessions\page.jsx`

**Changements:**
- ✅ Auto-refresh: **30s → 5s**
- ✅ Mise à jour immédiate de la progression
- ✅ Temps restant recalculé automatiquement
- ✅ Indicateur visuel "Actualisation toutes les 5 secondes"

**Résultat:**
```
Avant: Progression statique, mise à jour lente
Après: Progression fluide, évolution visible en temps réel ✅
```

### 2️⃣ Interface Joueur - Session en Direct
**Fichier:** `createxyz-project\_\apps\web\src\app\player\my-session\page.jsx`

**Changements:**
- ✅ Auto-refresh: **10s → 5s**
- ✅ Détection automatique de l'expiration
- ✅ Notification toast quand session terminée
- ✅ **Redirection automatique** vers /player/my-purchases après 5s
- ✅ Badge "SESSION TERMINÉE" avec animation pulse

**Résultat:**
```javascript
// Détecte quand status = 'completed'
if (session.status === 'completed') {
  toast.error('Votre session de jeu est terminée !');
  setTimeout(() => navigate('/player/my-purchases'), 5000);
}
```

### 3️⃣ API Rapide pour Polling
**Nouveau fichier:** `api/player/session_status.php`

**Objectif:** Endpoint léger pour vérifier rapidement le statut

**Retour:**
```json
{
  "success": true,
  "has_session": true,
  "session": {
    "id": 4,
    "status": "active",
    "total_minutes": 60,
    "used_minutes": 15,
    "remaining_minutes": 45,
    "progress_percent": 25.0
  },
  "timestamp": 1697547891
}
```

### 4️⃣ Scripts d'Automatisation

#### A) Décompte Manuel en Boucle
**Fichier:** `start_auto_countdown.bat`

**Usage:**
```batch
cd C:\xampp\htdocs\projet ismo
start_auto_countdown.bat
```

**Action:** Exécute le CRON toutes les 60 secondes en boucle

#### B) Tâche Planifiée Windows
**Fichier:** `install_countdown_task.ps1`

**Installation:**
```powershell
cd C:\xampp\htdocs\projet ismo
powershell -ExecutionPolicy Bypass -File install_countdown_task.ps1
```

**Action:** Crée une tâche Windows qui exécute le CRON chaque minute automatiquement

**Vérifier:**
```powershell
Get-ScheduledTask -TaskName "GameZone_AutoCountdown"
```

**Désactiver:**
```powershell
Disable-ScheduledTask -TaskName "GameZone_AutoCountdown"
```

**Supprimer:**
```powershell
Unregister-ScheduledTask -TaskName "GameZone_AutoCountdown"
```

---

## 🎮 FLUX COMPLET AVEC EXPIRATION

### Scénario: Joueur Achète 5 Minutes

```
T = 0:00   Joueur achète package 5 min
           → Obtient QR code

T = 0:30   Admin scanne le code
           → Facture activée
           → Session créée (status: ready)

T = 1:00   Admin clique "Démarrer"
           → Session démarre (status: active)
           → Temps: 5/5 minutes

T = 2:00   CRON s'exécute (1ère minute)
           → Temps: 4/5 minutes ✅
           → Interface joueur se met à jour (5s après)
           → Interface admin se met à jour (5s après)

T = 3:00   CRON s'exécute (2ème minute)
           → Temps: 3/5 minutes ✅
           → Mise à jour automatique

T = 4:00   CRON s'exécute (3ème minute)
           → Temps: 2/5 minutes ✅
           → Mise à jour automatique

T = 5:00   CRON s'exécute (4ème minute)
           → Temps: 1/5 minutes ✅
           → ⚠️ ALERTE: Temps faible !
           → Event "warning_low_time" créé

T = 6:00   CRON s'exécute (5ème minute)
           → Temps: 0/5 minutes
           → 🛑 Session terminée automatiquement
           → Status: active → completed ✅
           → Facture: active → used (DÉTRUITE) ✅

T = 6:05   Interface joueur détecte status = 'completed'
           → 🔔 Notification: "Votre session est terminée !"
           → Affiche badge rouge "SESSION TERMINÉE"
           → Compte à rebours 5 secondes

T = 6:10   Redirection automatique
           → Joueur redirigé vers /player/my-purchases
           → Message: "Votre facture a été utilisée et est maintenant inactive"
```

---

## 📊 Affichages en Temps Réel

### Admin Dashboard (`/admin/sessions`)

**Avant:**
- Refresh: 30 secondes
- Progression ne bouge pas
- Temps statique

**Après:**
- ✅ Refresh: **5 secondes**
- ✅ Progress bar animée
- ✅ Temps décompte visible
- ✅ Badge "X min restantes"
- ✅ Alerte si < 5 min

**Exemple visuel:**
```
Session #4 | testuser | Test Game
[████████████░░░░░░░░] 60%
⏱️ 36/60 minutes restantes
🟢 Active | Démarré: 15:26
```

### Joueur Session (`/player/my-session`)

**Avant:**
- Refresh: 10 secondes
- Pas de notification expiration
- Pas de redirection

**Après:**
- ✅ Refresh: **5 secondes**
- ✅ Grande affichage temps (36 min)
- ✅ Progress bar colorée (vert → jaune → rouge)
- ✅ Notification expiration
- ✅ Redirection automatique
- ✅ Badge "SESSION TERMINÉE"

**Exemple visuel:**
```
┌──────────────────────────────────┐
│   🎮 SESSION EN COURS             │
│                                   │
│           36 min                  │
│   restant sur 60 minutes          │
│                                   │
│   [████████████░░░░░░] 60%        │
│                                   │
│   Total: 60min | Utilisé: 24min  │
└──────────────────────────────────┘
```

---

## 🧪 Tests à Effectuer

### Test 1: Progression Admin
```
1. Aller sur http://localhost:4001/admin/sessions
2. Avoir une session active
3. Ouvrir console navigateur
4. Lancer CRON manuellement:
   php api/cron/countdown_sessions.php
5. Attendre 5 secondes max
6. ✅ Voir le temps restant diminuer
7. ✅ Progress bar se mettre à jour
```

### Test 2: Expiration Joueur
```
1. Créer session avec 1 minute seulement
2. Admin scanne et démarre
3. Joueur va sur /player/my-session
4. Lancer CRON après 1 minute
5. ✅ Notification "Session terminée" s'affiche
6. ✅ Badge rouge "SESSION TERMINÉE" visible
7. ✅ Redirection après 5 secondes
8. ✅ Message "Facture inutilisable" affiché
```

### Test 3: Auto-refresh
```
1. Avoir session active
2. Joueur sur /player/my-session
3. Admin sur /admin/sessions
4. Lancer CRON
5. Sans recharger manuellement:
6. ✅ Joueur voit temps diminuer (5s)
7. ✅ Admin voit temps diminuer (5s)
```

---

## 🚀 Mise en Production

### Étape 1: Installer la Tâche Planifiée
```powershell
cd C:\xampp\htdocs\projet ismo
powershell -ExecutionPolicy Bypass -File install_countdown_task.ps1
```

### Étape 2: Vérifier Installation
```powershell
Get-ScheduledTask -TaskName "GameZone_AutoCountdown"
```

**Résultat attendu:**
```
TaskName                 State
--------                 -----
GameZone_AutoCountdown   Ready
```

### Étape 3: Tester
```powershell
# Exécuter manuellement une fois
Start-ScheduledTask -TaskName "GameZone_AutoCountdown"

# Voir le résultat
php test_countdown_now.php
```

### Étape 4: Monitorer les Logs
```bash
# Voir les logs du jour
type logs\countdown_2025-10-17.log
```

**Exemple de logs:**
```
[2025-10-17 15:52:11] Début du décompte automatique
[2025-10-17 15:52:11] Décompte terminé en 45.23ms - Actives: 1, Complétées: 0, Expirées: 0
[2025-10-17 15:53:11] Début du décompte automatique
[2025-10-17 15:53:11] ALERTE: Session #4 (testuser) - Il reste 4 minute(s)
[2025-10-17 15:53:11] Décompte terminé en 52.18ms - Actives: 1, Complétées: 0, Expirées: 0
```

---

## ✅ Checklist Finale

### Backend
- [x] CRON countdown_sessions.php fonctionnel
- [x] Stored procedure countdown_active_sessions
- [x] Vue session_summary pour calculs
- [x] API session_status.php (polling rapide)
- [x] Logs dans countdown_YYYY-MM-DD.log

### Frontend Admin
- [x] Auto-refresh 5 secondes
- [x] Progression visible en temps réel
- [x] Badge alerte < 5 min
- [x] Texte "Auto-refresh 5s"

### Frontend Joueur
- [x] Auto-refresh 5 secondes
- [x] Détection expiration
- [x] Notification toast
- [x] Badge "SESSION TERMINÉE"
- [x] Redirection automatique
- [x] Progress bar colorée

### Automatisation
- [x] Script start_auto_countdown.bat
- [x] Script install_countdown_task.ps1
- [x] Script test_countdown_now.php

### Tests
- [ ] Test progression admin temps réel
- [ ] Test notification joueur expiration
- [ ] Test redirection automatique
- [ ] Test tâche planifiée Windows
- [ ] Test avec plusieurs sessions simultanées

---

## 🎯 Résumé des Améliorations

| Fonctionnalité | Avant | Après |
|---|---|---|
| **Refresh Admin** | 30s | **5s** ✅ |
| **Refresh Joueur** | 10s | **5s** ✅ |
| **Progression** | Statique | **Temps réel** ✅ |
| **Notification expiration** | ❌ | **Toast + Badge** ✅ |
| **Redirection auto** | ❌ | **Après 5s** ✅ |
| **Message facture** | ❌ | **"Inutilisable"** ✅ |
| **Automatisation** | Manuelle | **Tâche Windows** ✅ |

---

## 🔗 URLs de Test

- Admin Sessions: `http://localhost:4001/admin/sessions`
- Joueur Session: `http://localhost:4001/player/my-session`
- Joueur Achats: `http://localhost:4001/player/my-purchases`

---

**SYSTÈME 100% EN TEMPS RÉEL !** 🚀✨
