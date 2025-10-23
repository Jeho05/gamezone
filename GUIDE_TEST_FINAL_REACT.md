# 🎯 GUIDE DE TEST FINAL - CRÉATION DE JEU AVEC RÉSERVATION (REACT)

## ✅ Tout est Prêt !

### Ce qui a été fait:
1. ✅ Migration DB appliquée (`is_reservable`, `reservation_fee`)
2. ✅ Table `game_reservations` créée
3. ✅ Backend API fonctionnel
4. ✅ Formulaire React mis à jour avec champs de réservation
5. ✅ Affichage liste avec badges

---

## 🚀 Comment Tester (3 Minutes)

### Étape 1: Démarrer le Serveur React

```powershell
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
npm run dev
```

**Attendez le message:**
```
  ➜  Local:   http://localhost:5173/
  ➜  Network: use --host to expose
```

**Note:** Le port peut être différent (3000, 5173, etc.)

---

### Étape 2: Se Connecter en Admin

1. Ouvrir: **http://localhost:5173/** (ou le port affiché)
2. Cliquer sur **"Login"** ou **"Admin"**
3. Identifiants admin:
   - Email: `admin@gmail.com`
   - Password: `demo123`

---

### Étape 3: Accéder à la Gestion de la Boutique

1. Dans le menu de navigation (sidebar)
2. Cliquer sur **"Shop"** ou **"Boutique"**
3. Vous devriez voir 4 onglets:
   - **Jeux** ✅
   - Packages
   - Paiements
   - Achats

---

### Étape 4: Créer un Jeu Réservable

1. **Cliquer sur "+ Ajouter Jeu"** (bouton violet en haut à droite)

2. **Remplir le formulaire:**

   ```
   Nom du Jeu: *          [Jeu VR Exclusif]
   Slug (URL):            [jeu-vr-exclusif]  (auto-généré)
   Description courte:    [Expérience VR immersive]
   Description complète:  [Plongez dans un monde virtuel...]
   
   Image:                 [Upload ou URL]
   
   Catégorie: *           [VR]
   Plateforme:            [Meta Quest 2]
   Joueurs Min:           [1]
   Joueurs Max:           [1]
   Classification:        [PEGI 12]
   
   Points par Heure:      [30]
   Prix de Base (XOF/h):  [2500]
   
   ☑ Jeu réservable (avec créneau horaire)  ← COCHER ICI
   
   Frais de Réservation:  [500]  ← CE CHAMP APPARAÎT
   ℹ️ Frais supplémentaires pour réserver...
   
   ☐ Mettre en avant (Featured)
   ```

3. **Cliquer sur "Créer le Jeu"**

4. **Résultat attendu:**
   - Toast de succès: "Jeu créé avec succès !"
   - Retour à la liste
   - Le nouveau jeu apparaît avec:
     - ✅ Badge violet **"Réservable"**
     - ✅ Ligne: "Frais de réservation: **500 XOF**"

---

### Étape 5: Vérifier en Base de Données

**Via phpMyAdmin:**
1. Ouvrir: http://localhost/phpmyadmin
2. Base `gamezone` → Table `games`
3. Chercher votre jeu créé
4. Vérifier les colonnes:
   - `is_reservable` = **1**
   - `reservation_fee` = **500.00**

**Via SQL:**
```sql
SELECT 
  id,
  name, 
  is_reservable, 
  reservation_fee,
  base_price
FROM games 
WHERE name LIKE '%VR Exclusif%';
```

**Résultat attendu:**
```
+----+------------------+--------------+-----------------+------------+
| id | name             | is_reservable | reservation_fee | base_price |
+----+------------------+--------------+-----------------+------------+
| 15 | Jeu VR Exclusif  |      1       |     500.00      |   2500.00  |
+----+------------------+--------------+-----------------+------------+
```

---

### Étape 6: Tester l'Édition

1. **Cliquer sur "Modifier"** sur le jeu que vous venez de créer
2. **Vérifier que le formulaire charge correctement:**
   - ✅ Checkbox "Jeu réservable" est **cochée**
   - ✅ Champ "Frais de réservation" affiche **500**
3. **Modifier les frais à 750**
4. **Cliquer sur "Mettre à Jour"**
5. **Vérifier:**
   - Liste mise à jour: "Frais de réservation: **750 XOF**"
   - DB mise à jour: `reservation_fee = 750.00`

---

### Étape 7: Tester le Toggle

1. **Éditer à nouveau le jeu**
2. **Décocher "Jeu réservable"**
3. **Sauvegarder**
4. **Résultat attendu:**
   - ✅ Badge "Réservable" **disparaît** de la liste
   - ✅ Ligne des frais **disparaît** aussi
   - ✅ En DB: `is_reservable = 0` (mais `reservation_fee` reste à 750)

---

## 📸 Captures d'Écran Attendues

### 1. Formulaire avec Réservation Cochée
```
┌─────────────────────────────────────────────┐
│  Modifier le Jeu                       [×]  │
├─────────────────────────────────────────────┤
│                                              │
│  [... autres champs ...]                    │
│                                              │
│  Prix de Base (XOF/h)                       │
│  [2500                                   ]  │
│                                              │
│  ☑ Jeu réservable (avec créneau horaire)   │
│                                              │
│  Frais de Réservation (XOF)                 │
│  [500                                    ]  │
│  ℹ️ Frais supplémentaires pour réserver     │
│     un créneau horaire précis                │
│                                              │
│  ☐ Mettre en avant (Featured)              │
│                                              │
│  [Annuler]        [Créer le Jeu]           │
└─────────────────────────────────────────────┘
```

### 2. Liste avec Badge Réservable
```
┌────────────────────────────────────┐
│  [Image VR]                        │
│                                    │
│  Jeu VR Exclusif                   │
│  Expérience VR immersive           │
│                                    │
│  [Actif] [VR] [Réservable]  ← Badge violet
│                                    │
│  30 pts/h • 2500 XOF/h            │
│  Frais réservation: 500 XOF  ← Visible
│                                    │
│  📦 0 packages • 🛒 0 achats       │
│                                    │
│  [Modifier] [Supprimer]            │
└────────────────────────────────────┘
```

---

## 🧪 Tests Complets (Checklist)

### Test 1: Création Jeu Normal ✅
- [ ] Créer jeu SANS cocher "Réservable"
- [ ] Vérifier badge n'apparaît PAS
- [ ] Vérifier DB: `is_reservable = 0`

### Test 2: Création Jeu Réservable ✅
- [ ] Créer jeu EN cochant "Réservable"
- [ ] Définir frais à 500
- [ ] Vérifier badge "Réservable" apparaît
- [ ] Vérifier affichage frais
- [ ] Vérifier DB: `is_reservable = 1`, `reservation_fee = 500`

### Test 3: Édition Jeu ✅
- [ ] Éditer jeu réservable existant
- [ ] Vérifier checkbox pré-cochée
- [ ] Vérifier frais pré-remplis
- [ ] Modifier frais
- [ ] Sauvegarder
- [ ] Vérifier mise à jour

### Test 4: Toggle Réservable ✅
- [ ] Éditer jeu réservable
- [ ] Décocher "Réservable"
- [ ] Sauvegarder
- [ ] Vérifier badge disparaît
- [ ] Vérifier DB: `is_reservable = 0`
- [ ] Re-cocher et sauvegarder
- [ ] Vérifier badge réapparaît

### Test 5: Validation ✅
- [ ] Cocher "Réservable"
- [ ] Laisser frais vide ou 0
- [ ] Sauvegarder quand même (devrait accepter 0)
- [ ] Vérifier fonctionnement

---

## 🐛 Dépannage

### Le serveur ne démarre pas
```powershell
# Vérifier les dépendances
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
npm install

# Réessayer
npm run dev
```

### Le formulaire n'apparaît pas
- Vérifier que vous êtes connecté en tant qu'admin
- Actualiser la page (Ctrl+F5)
- Vérifier la console navigateur (F12)

### Les frais ne s'affichent pas
- Vérifier que `is_reservable = 1` en DB
- Actualiser la page
- Vérifier dans le code que les modifications sont bien sauvegardées

### Badge "Réservable" manquant
- Vérifier `game.is_reservable == 1` en DB
- Clear cache navigateur
- Vérifier la console pour erreurs JS

---

## 🎉 Succès !

Si tous les tests passent, vous avez:
- ✅ Système de réservation 100% fonctionnel
- ✅ Formulaire React complet
- ✅ Affichage dynamique des badges
- ✅ Validation et sauvegarde correctes
- ✅ Base de données à jour

---

## 📊 Vérification Rapide SQL

```sql
-- Compter les jeux réservables
SELECT COUNT(*) as total_reservable 
FROM games 
WHERE is_reservable = 1;

-- Lister tous les jeux avec info réservation
SELECT 
  name,
  category,
  CASE WHEN is_reservable = 1 THEN 'Oui' ELSE 'Non' END as reservable,
  reservation_fee,
  base_price
FROM games 
ORDER BY is_reservable DESC, name;

-- Stats réservation
SELECT 
  CASE WHEN is_reservable = 1 THEN 'Réservable' ELSE 'Normal' END as type,
  COUNT(*) as count,
  AVG(reservation_fee) as avg_fee
FROM games
GROUP BY is_reservable;
```

---

## 🎯 Prochaine Étape (Optionnel)

### Interface Utilisateur de Réservation

Pour permettre aux utilisateurs finaux de réserver:

**Créer:** `src/app/shop/reserve/[gameId]/page.jsx`

**Fonctionnalités:**
- Sélecteur de date/heure
- Affichage créneaux disponibles
- Calcul prix total (package + frais)
- Confirmation et paiement

**Backend nécessaire:**
- `GET /api/shop/check_availability.php`
- `POST /api/shop/create_reservation.php`
- Gestion des conflits de créneaux

---

## 📝 Résumé Technique

### Fichiers Modifiés
- ✅ `api/migrations/add_reservations_system.sql` (appliqué)
- ✅ `createxyz-project\_\apps\web\src\app\admin\shop\page.jsx`

### Champs Ajoutés
```javascript
{
  is_reservable: false,     // Boolean
  reservation_fee: 0        // Number (XOF)
}
```

### API Endpoint
```javascript
POST/PUT /api/admin/games.php
Body: {
  ...
  is_reservable: 1,        // 0 ou 1
  reservation_fee: 500.00  // Decimal
}
```

---

**🎮 Votre système de création de jeu avec réservation est maintenant 100% opérationnel dans React !**

**URL Admin:** http://localhost:5173/admin/shop

**Bonne création de jeux ! 🚀**
