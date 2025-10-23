# 🧪 Guide de Test - Gestion Complète des Erreurs

## 🎯 Objectif

Valider que **toutes** les situations possibles sont gérées professionnellement dans le scanner de factures.

---

## 📋 Checklist des Tests

### ✅ Tests de Succès

| # | Test | Résultat Attendu | Statut |
|---|------|------------------|--------|
| 1 | Scanner facture valide | ✅ Session démarrée auto + Messages clairs | ⬜ |
| 2 | Scanner avec code manuel | ✅ Même résultat qu'avec QR | ⬜ |
| 3 | Historique enregistré | ✅ Apparaît dans liste historique | ⬜ |

### ❌ Tests d'Erreurs Métier

| # | Test | Résultat Attendu | Statut |
|---|------|------------------|--------|
| 4 | Code invalide (format) | ❌ Format invalide + Solution | ⬜ |
| 5 | Code n'existe pas | ❌ Code invalide + Solution | ⬜ |
| 6 | Facture déjà activée | ⚠️ Déjà activée + Pas de retry | ⬜ |
| 7 | Facture déjà utilisée | 🔒 Déjà utilisée + Pas de retry | ⬜ |
| 8 | Facture annulée | 🚫 Annulée + Contactez admin | ⬜ |
| 9 | Facture expirée | ⏰ Expirée + Nouvel achat requis | ⬜ |
| 10 | Paiement en attente | 💳 Confirmez paiement + Retry | ⬜ |
| 11 | Paiement échoué | ❌ Paiement échoué + Nouveau paiement | ⬜ |
| 12 | Réservation trop tôt | ⏱️ Trop tôt + Minutes restantes | ⬜ |
| 13 | Fraude détectée | 🚨 Fraude + Contactez admin | ⬜ |

### 🌐 Tests Techniques

| # | Test | Résultat Attendu | Statut |
|---|------|------------------|--------|
| 14 | Connexion coupée | ❌ Hors ligne + Badge + Scan désactivé | ⬜ |
| 15 | Connexion rétablie | ✅ Toast "Connexion rétablie" | ⬜ |
| 16 | Timeout serveur | ⏱️ Timeout + Auto-retry 3x | ⬜ |
| 17 | Erreur 500 serveur | 🔧 Erreur serveur + Auto-retry | ⬜ |
| 18 | Session expirée (401) | 🔐 Redirection login après 2s | ⬜ |
| 19 | Erreur réseau (fetch) | 📡 Erreur réseau + Auto-retry | ⬜ |

---

## 🧪 Procédures de Test Détaillées

### Test 1: Scanner Facture Valide ✅

**Préparation**:
```sql
-- Créer un achat test avec paiement confirmé
INSERT INTO purchases (...) VALUES (...);
UPDATE purchases SET payment_status = 'completed' WHERE id = [ID];
```

**Étapes**:
1. Aller sur Admin > Scanner de Factures
2. Scanner un code QR valide
3. Attendre le chargement

**Résultat Attendu**:
```
✅ Toast: "Facture Activée !"
🎮 Toast: "Session démarrée automatiquement"
✨ Toast: "Le joueur peut commencer à jouer immédiatement"

Affichage:
┌────────────────────────────────┐
│ ✅ Facture Validée            │
│ Facture: INV-...              │
│ Joueur: testuser              │
│ Jeu: FIFA                     │
│ Durée: 60 minutes             │
│                                │
│ 🎮 Session Démarrée           │
│ Session activée et démarrée   │
│ automatiquement               │
│ 60 minutes disponibles        │
└────────────────────────────────┘
```

---

### Test 4: Code Format Invalide ❌

**Étapes**:
1. Entrer un code de 10 caractères (au lieu de 16)
2. Cliquer sur "Valider"

**Résultat Attendu**:
```
❌ Toast: "Format invalide. Le code doit contenir 16 caractères."

Affichage:
┌────────────────────────────────┐
│ ❌ Format de code invalide    │
│ Le code doit contenir         │
│ 16 caractères alphanumériques │
└────────────────────────────────┘
```

---

### Test 6: Facture Déjà Activée ⚠️

**Préparation**:
```sql
-- Scanner 2 fois le même code
```

**Étapes**:
1. Scanner un code valide (première fois) → Succès
2. Cliquer sur "Scanner un Autre Code"
3. Rescanner le MÊME code

**Résultat Attendu**:
```
⚠️ Toast: "Déjà Activée"
Description: "Cette facture a déjà été activée"

Affichage:
┌────────────────────────────────┐
│ ❌ Déjà Activée               │
│ Cette facture a déjà été      │
│ activée                        │
│                                │
│ 💡 Solution:                  │
│ Utilisez un nouveau code QR   │
│                                │
│ [PAS de bouton Réessayer]     │
└────────────────────────────────┘
```

---

### Test 10: Paiement En Attente 💳

**Préparation**:
```sql
-- Créer un achat avec paiement non confirmé
INSERT INTO purchases (...) VALUES (...);
-- payment_status reste à 'pending'
```

**Étapes**:
1. Scanner le code QR de cet achat
2. Observer le message d'erreur
3. Cliquer sur "Réessayer" (doit être présent)

**Résultat Attendu**:
```
💳 Toast: "Paiement En Attente"
Description: "Le paiement n'a pas encore été confirmé"

Affichage:
┌────────────────────────────────┐
│ ❌ Paiement En Attente        │
│ Le paiement n'a pas encore    │
│ été confirmé                   │
│                                │
│ 💡 Solution:                  │
│ Confirmez le paiement d'abord │
│ dans Gestion Boutique         │
│                                │
│ [Réessayer]  ← Bouton présent │
└────────────────────────────────┘
```

**Action de Correction**:
1. Aller dans Admin > Boutique > Achats
2. Trouver l'achat en attente
3. Cliquer sur "Confirmer"
4. Retourner au scanner
5. Cliquer sur "Réessayer"
6. → Devrait maintenant réussir! ✅

---

### Test 11: Paiement Échoué ❌

**Préparation**:
```sql
-- Simuler un paiement échoué
UPDATE purchases SET payment_status = 'failed' WHERE id = [ID];
```

**Étapes**:
1. Scanner le code de cet achat

**Résultat Attendu**:
```
❌ Toast: "Paiement Échoué"
Description: "Le paiement a échoué ou a été refusé"

Affichage:
┌────────────────────────────────┐
│ ❌ Paiement Échoué            │
│ Le paiement a échoué ou a     │
│ été refusé                     │
│                                │
│ 💡 Solution:                  │
│ Créez un nouvel achat avec    │
│ un nouveau paiement            │
│                                │
│ [PAS de bouton Réessayer]     │
│ (Pas de retry possible)       │
└────────────────────────────────┘
```

---

### Test 12: Réservation Trop Tôt ⏱️

**Préparation**:
```sql
-- Créer une réservation pour dans 1 heure
INSERT INTO game_reservations (scheduled_start, ...) 
VALUES (DATE_ADD(NOW(), INTERVAL 1 HOUR), ...);
```

**Étapes**:
1. Scanner le code maintenant (avant l'heure prévue)

**Résultat Attendu**:
```
⏱️ Toast: "Trop Tôt"
Description: "La session ne peut pas encore être activée"

Affichage:
┌────────────────────────────────┐
│ ❌ Trop Tôt                   │
│ La session ne peut pas encore │
│ être activée                   │
│                                │
│ 💡 Solution:                  │
│ Réessayez dans 60 minutes     │
│                                │
│ Programmé: 14:00              │
│ Maintenant: 13:00             │
│                                │
│ [Réessayer]  ← Bouton présent │
└────────────────────────────────┘
```

---

### Test 14: Connexion Coupée 🌐

**Étapes**:
1. Ouvrir le scanner
2. **Désactiver le Wi-Fi** ou débrancher le câble réseau
3. Observer l'interface
4. Essayer de scanner un code

**Résultat Attendu**:
```
Immédiat:
⚠️ Toast: "Connexion perdue! Vérifiez votre réseau."

Badge affiché:
┌──────────────────┐
│ [📡 Hors ligne]  │
└──────────────────┘

Bouton "Valider": DÉSACTIVÉ (gris)
Champ de saisie: DÉSACTIVÉ

Si tentative de scan:
❌ Toast: "Pas de connexion internet. Impossible de scanner."
```

---

### Test 15: Connexion Rétablie ✅

**Étapes**:
1. Après le Test 14, **réactiver le Wi-Fi**

**Résultat Attendu**:
```
✅ Toast: "Connexion rétablie"

Badge disparaît
Bouton "Valider": RÉACTIVÉ
Champ de saisie: RÉACTIVÉ

Peut maintenant scanner normalement
```

---

### Test 16: Timeout Serveur ⏱️

**Simulation**:
```php
// Dans scan_invoice.php (temporairement)
sleep(20); // Forcer un délai de 20s
```

**Étapes**:
1. Scanner un code
2. Attendre 15 secondes

**Résultat Attendu**:
```
Après 15s:
⏱️ Toast: "Timeout: Le serveur ne répond pas"

Auto-retry commence:
🔄 Toast: "Nouvelle tentative (1/3)..."
(attente 2s)

🔄 Toast: "Nouvelle tentative (2/3)..."
(attente 2s)

🔄 Toast: "Nouvelle tentative (3/3)..."
(attente 2s)

Si toujours timeout:
┌────────────────────────────────┐
│ ❌ Délai d'attente dépassé    │
│ (15s)                          │
│                                │
│ 💡 Solution:                  │
│ Le serveur met trop de temps  │
│ à répondre. Vérifiez votre    │
│ connexion.                     │
│                                │
│ 🔧 Détails techniques ▼       │
│                                │
│ [Réessayer]                   │
└────────────────────────────────┘
```

---

### Test 17: Erreur 500 Serveur 🔧

**Simulation**:
```php
// Dans scan_invoice.php (temporairement)
http_response_code(500);
json_response(['error' => 'Internal Server Error'], 500);
```

**Étapes**:
1. Scanner un code

**Résultat Attendu**:
```
🔧 Toast: "Erreur serveur: Service temporairement indisponible"

Auto-retry 3x comme pour timeout

Affichage final:
┌────────────────────────────────┐
│ ❌ Erreur serveur (500)       │
│ Le serveur rencontre un       │
│ problème                       │
│                                │
│ 💡 Solution:                  │
│ Réessayez dans 1 minute       │
│                                │
│ [Réessayer]                   │
└────────────────────────────────┘
```

---

### Test 18: Session Expirée (401) 🔐

**Simulation**:
```php
// Dans scan_invoice.php (temporairement)
http_response_code(401);
json_response(['error' => 'Unauthorized'], 401);
```

**Étapes**:
1. Scanner un code

**Résultat Attendu**:
```
❌ Toast: "Session expirée. Veuillez vous reconnecter."
Duration: 5 secondes

Affichage:
┌────────────────────────────────┐
│ ❌ Session expirée            │
│ Reconnectez-vous et réessayez │
│                                │
│ Redirection dans 2s...        │
└────────────────────────────────┘

Après 2 secondes:
→ Redirection automatique vers /admin/login
```

---

### Test 19: Erreur Réseau (Fetch) 📡

**Simulation**:
```
// Arrêter XAMPP temporairement
// OU bloquer l'API dans le firewall
```

**Étapes**:
1. Arrêter le serveur Apache
2. Scanner un code

**Résultat Attendu**:
```
📡 Toast: "Erreur réseau: Impossible de se connecter au serveur"

Auto-retry 3x:
🔄 "Nouvelle tentative (1/3)..."
🔄 "Nouvelle tentative (2/3)..."
🔄 "Nouvelle tentative (3/3)..."

Affichage final:
┌────────────────────────────────┐
│ ❌ Erreur de connexion réseau │
│                                │
│ 💡 Solution:                  │
│ Vérifiez votre connexion      │
│ internet et réessayez          │
│                                │
│ 🔧 Détails techniques ▼       │
│ Type: network                  │
│ Tentatives: 3/3               │
│                                │
│ [Réessayer]                   │
└────────────────────────────────┘
```

---

## 📊 Tableau de Résultats

Après tous les tests, remplir ce tableau:

| Test | Résultat | Notes |
|------|----------|-------|
| Facture valide | ⬜ Pass / ⬜ Fail | |
| Code format invalide | ⬜ Pass / ⬜ Fail | |
| Déjà activée | ⬜ Pass / ⬜ Fail | |
| Paiement en attente | ⬜ Pass / ⬜ Fail | |
| Paiement échoué | ⬜ Pass / ⬜ Fail | |
| Trop tôt | ⬜ Pass / ⬜ Fail | |
| Connexion coupée | ⬜ Pass / ⬜ Fail | |
| Connexion rétablie | ⬜ Pass / ⬜ Fail | |
| Timeout | ⬜ Pass / ⬜ Fail | |
| Erreur 500 | ⬜ Pass / ⬜ Fail | |
| Session expirée | ⬜ Pass / ⬜ Fail | |
| Erreur réseau | ⬜ Pass / ⬜ Fail | |

---

## ✅ Critères de Validation

Un test passe si:

1. ✅ **Message d'erreur clair** affiché
2. ✅ **Solution proposée** pertinente
3. ✅ **Bouton Réessayer** présent si applicable
4. ✅ **Bouton Réessayer** absent si non-applicable
5. ✅ **Auto-retry** fonctionne pour erreurs réseau
6. ✅ **Toast informatifs** au bon moment
7. ✅ **Pas de crash** JavaScript
8. ✅ **Historique** enregistré correctement
9. ✅ **UI responsive** et claire
10. ✅ **Comportement** conforme à la documentation

---

## 🐛 Reporting de Bugs

Si un test échoue, documenter:

```markdown
### Bug #X: [Titre court]

**Test**: Test #X - [Nom du test]
**Attendu**: [Comportement attendu]
**Obtenu**: [Comportement réel]
**Reproduire**:
1. Étape 1
2. Étape 2
3. ...

**Logs console**:
```
[Copier les erreurs de la console]
```

**Screenshot**: [Si possible]
```

---

## 🎯 Objectif Final

**100% des tests doivent passer** pour considérer l'implémentation complète.

**Tous les cas d'erreur** doivent être gérés professionnellement, avec:
- Messages clairs
- Solutions proposées
- Retry si pertinent
- Feedback visuel
- Pas de crash

---

## 🚀 Après les Tests

Une fois tous les tests validés:

1. ✅ Documenter les résultats
2. ✅ Former l'équipe sur les nouveaux messages
3. ✅ Communiquer aux utilisateurs
4. ✅ Monitorer en production
5. ✅ Collecter les feedbacks

**Le système est alors prêt pour la production!** 🎉
