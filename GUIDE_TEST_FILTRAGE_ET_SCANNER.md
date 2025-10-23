# 🧪 Guide de Test : Filtrage des Achats & Scanner QR

## 📋 Problèmes Rapportés

1. ❌ **Filtrage ne fonctionne pas** - Tout apparaît dans "Tous" et "En attente", rien dans "Actifs" et "Complétés"
2. ❌ **Scanner QR invisible** - Seul le champ de saisie manuelle est visible

---

## ✅ État Actuel de Vos Données

```
📊 9 achats trouvés
├─ 8 avec game_session_status = 'pending' → 🟡 En Attente
├─ 1 avec game_session_status = 'active' → 🟢 Actif (créé par test)
└─ 1 avec game_session_status = 'completed' → ✅ Complété (créé par test)
```

**C'est NORMAL !** Vos achats sont tous en attente car **aucune session n'a été démarrée par l'admin**.

---

## 🎯 Comment Tester le Filtrage

### Étape 1 : Vérifier "En Attente" (Déjà OK)

1. Allez sur **http://localhost:4000/player/my-purchases**
2. Cliquez sur **"🟡 En attente"**
3. ✅ Vous devriez voir vos 8 achats en attente

**Pourquoi "En attente" ?**
- Achat payé ✅
- Mais admin n'a PAS encore démarré la session ❌

---

### Étape 2 : Créer une Session Active

**Méthode 1 : Via Script PHP (Rapide)**
```bash
C:\xampp\php\php.exe test_start_session.php
```

**Méthode 2 : Via Interface Admin**
1. Allez sur **http://localhost:4000/admin/invoice-scanner**
2. Scannez ou saisissez le code de validation d'un achat
3. Cliquez sur "Démarrer la Session"

**Résultat :**
- L'achat disparaît de "En attente"
- L'achat apparaît dans **"🟢 Actifs"**
- Bouton "Voir ma facture QR" visible

---

### Étape 3 : Créer une Session Terminée

**Méthode 1 : Via Script PHP (Rapide)**
```bash
C:\xampp\php\php.exe test_complete_session.php
```

**Méthode 2 : Attendre**
- Démarrez une session de 1 minute
- Attendez 1 minute
- La session passe automatiquement en "Complété"

**Méthode 3 : Via Admin**
- Allez sur **/admin/sessions**
- Cliquez sur "Terminer" sur une session active

**Résultat :**
- L'achat disparaît de "Actifs"
- L'achat apparaît dans **"✅ Complétés"**
- Bouton facture QR MASQUÉ
- Badge "Session terminée" affiché

---

## 🎥 Comment Tester le Scanner QR

### Vérification Rapide

1. Allez sur **http://localhost:4000/admin/invoice-scanner**
2. Vous devriez voir **DEUX boutons** :

```
┌────────────────────────────────────────┐
│  [🎥 Scanner Caméra]  [📱 Valider Code]  │
└────────────────────────────────────────┘
```

### Si vous ne voyez PAS le bouton vert "Scanner Caméra"

**Cause possible : Erreur React**
1. Ouvrez la console du navigateur (F12)
2. Vérifiez s'il y a des erreurs en rouge
3. Partagez-moi les erreurs si vous en voyez

### Test du Scanner

1. Cliquez sur **"🎥 Scanner Caméra"**
2. Autorisez l'accès à la caméra (popup du navigateur)
3. Un modal s'ouvre avec :
   - Flux vidéo de votre caméra
   - Cadre vert pour viser
   - Indicateur "Positionnez le QR code dans le cadre"

4. Présentez un QR code devant la caméra
5. Le scan est automatique (< 1 seconde)
6. Le modal se ferme et valide le code

---

## 📊 Flux Complet d'un Achat

```
1️⃣ ACHAT CRÉÉ
   ├─ payment_status: pending
   ├─ game_session_status: NULL
   └─ Catégorie: 🟡 "En Attente"

2️⃣ PAIEMENT CONFIRMÉ
   ├─ payment_status: completed
   ├─ game_session_status: pending
   └─ Catégorie: 🟡 "En Attente" (pas encore démarré)

3️⃣ ADMIN DÉMARRE LA SESSION
   ├─ game_session_status: active
   └─ Catégorie: 🟢 "Actifs" ✅
   └─ Bouton: "Voir ma facture QR" visible

4️⃣ TEMPS ÉCOULÉ / ADMIN TERMINE
   ├─ game_session_status: completed
   └─ Catégorie: ✅ "Complétés" ✅
   └─ Bouton facture MASQUÉ
   └─ Badge: "Session terminée"
```

---

## 🔍 Débogage de l'API

Pour voir exactement ce que retourne l'API :

```bash
C:\xampp\php\php.exe debug_my_purchases.php
```

**Vous verrez :**
- Tous vos achats avec leurs statuts
- Le comptage par catégorie
- La répartition dans les filtres

---

## 🛠️ Scripts de Test Fournis

### 1. `test_start_session.php`
**Usage :** Démarrer une session en attente
```bash
C:\xampp\php\php.exe test_start_session.php
```
**Effet :** Passe un achat de "En attente" → "Actifs"

### 2. `test_complete_session.php`
**Usage :** Terminer une session active
```bash
C:\xampp\php\php.exe test_complete_session.php
```
**Effet :** Passe un achat de "Actifs" → "Complétés"

### 3. `debug_my_purchases.php`
**Usage :** Voir toutes les données de l'API
```bash
C:\xampp\php\php.exe debug_my_purchases.php
```
**Effet :** Affiche la structure des données + filtrage

### 4. `check_game_sessions_structure.php`
**Usage :** Voir la structure de la table
```bash
C:\xampp\php\php.exe check_game_sessions_structure.php
```
**Effet :** Liste toutes les colonnes de game_sessions

---

## ⚠️ Problèmes Connus

### Problème : Tout reste dans "En Attente"

**Cause :** Aucune session n'a été démarrée par l'admin

**Solution :**
1. Utiliser `test_start_session.php` pour créer une session active
2. OU scanner un code QR depuis l'interface admin
3. OU aller sur `/admin/sessions` et démarrer manuellement

### Problème : Scanner QR ne s'affiche pas

**Causes possibles :**
1. Erreur React dans la console (F12 pour vérifier)
2. Bibliothèque jsQR non chargée
3. Composant QRScanner non importé

**Solution :**
1. Vérifiez la console (F12) pour les erreurs
2. Actualisez la page (Ctrl + Shift + R)
3. Si erreur persiste, utilisez la saisie manuelle

### Problème : Caméra non autorisée

**Solution :**
1. Cliquez sur l'icône 🔒 ou ℹ️ dans la barre d'adresse
2. Autorisez l'accès à la caméra
3. Rechargez la page

---

## ✅ Checklist de Test

### Filtrage "En Attente"
- [ ] Créer un achat depuis `/player/shop`
- [ ] Vérifier qu'il apparaît dans "🟡 En attente"
- [ ] Message "Paiement en attente de confirmation" OU "Session prête à démarrer"

### Filtrage "Actifs"
- [ ] Démarrer une session (script ou admin)
- [ ] Rafraîchir `/player/my-purchases`
- [ ] L'achat apparaît dans "🟢 Actifs"
- [ ] Bouton "Voir ma facture QR" visible
- [ ] Temps restant affiché

### Filtrage "Complétés"
- [ ] Terminer une session (script ou attendre)
- [ ] Rafraîchir `/player/my-purchases`
- [ ] L'achat apparaît dans "✅ Complétés"
- [ ] Badge "Session terminée" affiché
- [ ] Bouton facture QR MASQUÉ

### Scanner QR
- [ ] Aller sur `/admin/invoice-scanner`
- [ ] Bouton "🎥 Scanner Caméra" visible
- [ ] Clic sur le bouton ouvre le modal
- [ ] Flux vidéo de la caméra affiché
- [ ] QR code détecté automatiquement
- [ ] Code validé après scan

---

## 🎯 Test Rapide Complet (5 minutes)

```bash
# 1. Vérifier l'état actuel
C:\xampp\php\php.exe debug_my_purchases.php

# 2. Créer une session active
C:\xampp\php\php.exe test_start_session.php

# 3. Vérifier dans le navigateur
# → Aller sur http://localhost:4000/player/my-purchases
# → Cliquer sur "Actifs"
# → Vérifier qu'une session apparaît

# 4. Terminer la session
C:\xampp\php\php.exe test_complete_session.php

# 5. Vérifier dans le navigateur
# → Rafraîchir la page
# → Cliquer sur "Complétés"
# → Vérifier que la session terminée apparaît

# 6. Tester le scanner QR
# → Aller sur http://localhost:4000/admin/invoice-scanner
# → Cliquer sur "Scanner Caméra"
# → Vérifier que la caméra s'ouvre
```

---

## 📞 Si Ça Ne Marche Toujours Pas

**Partagez-moi :**

1. **Sortie de `debug_my_purchases.php`**
   ```bash
   C:\xampp\php\php.exe debug_my_purchases.php
   ```

2. **Console du navigateur** (F12 → Console)
   - Copier tous les messages en rouge

3. **Onglet Network** (F12 → Network)
   - Filtrer "my_purchases.php"
   - Copier la réponse

4. **Screenshot de la page**
   - `/player/my-purchases` avec tous les onglets

---

## 🎉 Résultat Final Attendu

### Page "Mes Achats" (Joueur)

**Onglet "Tous"**
```
✅ 9 achats affichés
```

**Onglet "En Attente"**
```
🟡 7 achats en attente de démarrage
```

**Onglet "Actifs"**
```
🟢 1 session en cours de jeu
   ├─ Temps restant: XX min
   ├─ Progression: XX%
   └─ Bouton: "Voir ma facture QR"
```

**Onglet "Complétés"**
```
✅ 1 session terminée
   ├─ Badge: "Session terminée"
   ├─ Temps utilisé: XX / XX min
   └─ Bouton facture MASQUÉ
```

### Page Scanner (Admin)

```
┌──────────────────────────────────────────┐
│ 📝 Code de Validation (16 caractères)   │
│ [________________] 0/16                  │
│                                          │
│  [🎥 Scanner Caméra] [📱 Valider Code]   │
└──────────────────────────────────────────┘
```

---

**Suivez ce guide et tout devrait fonctionner !** 🚀

Si un problème persiste, exécutez les scripts de debug et partagez-moi les résultats.
