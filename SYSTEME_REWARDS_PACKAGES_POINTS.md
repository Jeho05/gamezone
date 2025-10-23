# Système de Récompenses avec Packages de Jeux Payables en Points

## 📋 Vue d'ensemble

Ce système permet aux administrateurs de créer des **récompenses** qui peuvent être soit des objets physiques/digitaux, soit des **packages de jeux** payables uniquement avec des points de fidélité (pas d'argent).

### Cas d'usage principal

**L'admin peut maintenant créer une récompense de type "Package de Jeu"** qui :
- Coûte des **points** (pas d'argent)
- Donne accès à un **temps de jeu** spécifique
- Accorde des **points bonus** après avoir joué
- Apparaît dans le catalogue des récompenses ET dans la boutique de jeux

---

## 🗂️ Architecture de la base de données

### Nouvelles colonnes ajoutées

#### Table `game_packages`
```sql
- is_points_only TINYINT(1)      -- 1 si payable uniquement en points
- points_cost INT                -- Coût en points (si is_points_only = 1)
- reward_id INT                  -- Lien vers la récompense associée
```

#### Table `rewards`
```sql
- reward_type ENUM               -- Type: physical, digital, game_package, discount, other
- game_package_id INT            -- Lien vers le package de jeu associé
```

#### Table `purchases`
```sql
- paid_with_points TINYINT(1)    -- 1 si acheté avec des points
- points_spent INT               -- Nombre de points dépensés
```

### Vues SQL créées

#### `point_packages`
Liste tous les packages payables en points avec leurs statistiques :
- Informations du jeu et du package
- Informations de la récompense liée
- Nombre d'échanges et d'utilisateurs uniques

#### `points_redemption_history`
Historique complet des achats effectués avec des points.

---

## 🔧 APIs Backend

### 1. API Admin - Gestion des Récompenses
**Fichier:** `api/admin/rewards.php`

#### GET - Lister les récompenses
```http
GET /api/admin/rewards.php
```

**Réponse enrichie:**
```json
{
  "rewards": [
    {
      "id": 1,
      "name": "FIFA 2024 - 30 minutes",
      "cost": 50,
      "reward_type": "game_package",
      "game_package_id": 15,
      "game_name": "FIFA 2024",
      "game_slug": "fifa-2024",
      "package_name": "Session Express - 30 min",
      "duration_minutes": 30,
      "package_points_cost": 50,
      "package_points_earned": 5,
      "redemptions_count": 12,
      "package_purchases_count": 12
    }
  ]
}
```

#### POST - Créer une récompense avec package de jeu
```http
POST /api/admin/rewards.php
Content-Type: application/json
```

**Payload:**
```json
{
  "name": "FIFA 2024 - 30 minutes",
  "description": "Profitez de 30 minutes de jeu sur FIFA 2024",
  "cost": 50,
  "reward_type": "game_package",
  "category": "gaming",
  "game_id": 1,
  "duration_minutes": 30,
  "points_earned": 5,
  "bonus_multiplier": 1.0,
  "is_promotional": 0,
  "max_per_user": null,
  "available": 1,
  "is_featured": 1
}
```

**Ce qui se passe:**
1. Crée un `game_package` avec `is_points_only = 1`
2. Crée une `reward` de type `game_package`
3. Lie les deux via `reward_id` et `game_package_id`

**Réponse:**
```json
{
  "success": true,
  "message": "Récompense et package de jeu créés avec succès",
  "reward_id": 1,
  "package_id": 15
}
```

---

### 2. API Shop - Échange de Points
**Fichier:** `api/shop/redeem_with_points.php`

#### GET - Liste des packages échangeables
```http
GET /api/shop/redeem_with_points.php?game_id=1
```

**Réponse:**
```json
{
  "packages": [
    {
      "id": 15,
      "game_id": 1,
      "game_name": "FIFA 2024",
      "game_slug": "fifa-2024",
      "package_name": "Session Express - 30 min",
      "duration_minutes": 30,
      "points_cost": 50,
      "points_earned": 5,
      "reward_id": 1,
      "reward_name": "FIFA 2024 - 30 minutes",
      "user_purchases_count": 2
    }
  ],
  "user_points": 230,
  "count": 1
}
```

#### POST - Échanger des points contre un package
```http
POST /api/shop/redeem_with_points.php
Content-Type: application/json
```

**Payload:**
```json
{
  "package_id": 15
}
```

**Processus d'échange:**
1. ✅ Vérifier que le package existe et est actif
2. ✅ Vérifier que l'utilisateur n'a pas dépassé la limite d'achats
3. ✅ Vérifier que l'utilisateur a assez de points (avec verrouillage SQL)
4. ✅ Déduire les points de l'utilisateur
5. ✅ Créer l'achat avec `paid_with_points = 1`
6. ✅ Enregistrer la transaction dans `points_transactions`
7. ✅ Créer l'échange dans `reward_redemptions` si applicable

**Réponse:**
```json
{
  "success": true,
  "message": "Échange effectué avec succès !",
  "purchase_id": 42,
  "package_name": "Session Express - 30 min",
  "game_name": "FIFA 2024",
  "duration_minutes": 30,
  "points_spent": 50,
  "points_earned": 5,
  "remaining_points": 180,
  "redirect_to": "/player/my-purchases"
}
```

---

## 🎨 Interface Admin

**Fichier:** `admin/rewards_manager.html`

### Fonctionnalités

1. **Vue d'ensemble avec statistiques:**
   - Total récompenses
   - Packages de jeux
   - Échanges effectués
   - Points dépensés

2. **Création de récompense:**
   - Sélection du type (physique, package de jeu, digitale, etc.)
   - Si "Package de Jeu" → champs supplémentaires apparaissent:
     - Sélection du jeu
     - Durée en minutes
     - Points gagnés en jouant
     - Multiplicateur de bonus
     - Options promotionnelles

3. **Liste des récompenses:**
   - Cartes visuelles différenciées
   - Packages de jeux avec dégradé coloré
   - Filtrage par type
   - Statistiques par récompense

### Captures d'écran du workflow

```
1. Admin ouvre rewards_manager.html
   ↓
2. Clique "Nouvelle Récompense"
   ↓
3. Sélectionne type "Package de Jeu"
   ↓
4. Remplit les champs:
   - Nom: "FIFA 2024 - 1 heure"
   - Coût: 100 points
   - Jeu: FIFA 2024
   - Durée: 60 minutes
   - Points gagnés: 10
   ↓
5. Clique "Enregistrer"
   ↓
6. Système crée automatiquement:
   - Un game_package (payable en points)
   - Une reward liée
```

---

## 🔄 Flux complet pour l'utilisateur

### Étape 1: Accumuler des points
L'utilisateur joue et gagne des points via:
- Temps de jeu (points_per_hour du jeu)
- Bonus des packages (bonus_multiplier)
- Récompenses diverses

### Étape 2: Consulter les récompenses disponibles
```
GET /api/shop/redeem_with_points.php
```
L'utilisateur voit:
- Packages de jeux échangeables contre points
- Son solde de points actuel
- Combien de fois il a déjà échangé chaque package

### Étape 3: Échanger ses points
```
POST /api/shop/redeem_with_points.php
{
  "package_id": 15
}
```

**Vérifications automatiques:**
- ✅ Points suffisants ?
- ✅ Limite d'achats respectée ?
- ✅ Jeu disponible ?

### Étape 4: Session créée
Un achat (`purchase`) est créé avec:
- `paid_with_points = 1`
- `points_spent = 50`
- `payment_status = completed` (immédiatement)
- `session_status = pending` (en attente d'activation)

### Étape 5: Jouer et gagner des points bonus
Après avoir joué, l'utilisateur reçoit les points bonus configurés.

---

## 📊 Exemples de packages créés

### Exemple 1: FIFA Express
```json
{
  "name": "FIFA 2024 - 30 minutes",
  "cost": 50,
  "reward_type": "game_package",
  "game_id": 1,
  "duration_minutes": 30,
  "points_earned": 5
}
```
**ROI pour l'utilisateur:** Dépense 50 points, récupère 5 points = coût net 45 points

### Exemple 2: COD Marathon
```json
{
  "name": "Call of Duty - 2 heures",
  "cost": 200,
  "reward_type": "game_package",
  "game_id": 2,
  "duration_minutes": 120,
  "points_earned": 30,
  "bonus_multiplier": 1.5,
  "is_promotional": 1,
  "promotional_label": "BEST VALUE"
}
```
**ROI:** Dépense 200 points, récupère 30 × 1.5 = 45 points = coût net 155 points

### Exemple 3: VR Premium
```json
{
  "name": "Beat Saber VR - 30 minutes",
  "cost": 150,
  "reward_type": "game_package",
  "game_id": 6,
  "duration_minutes": 30,
  "points_earned": 15,
  "max_per_user": 1
}
```
**Limitation:** Un seul échange par utilisateur (package premium)

---

## 🚀 Démarrage et migration

### 1. Appliquer la migration SQL
```bash
# Ouvrir phpMyAdmin ou MySQL
# Exécuter le fichier:
api/migrations/add_reward_game_packages.sql
```

**Ce que fait la migration:**
- Ajoute les nouvelles colonnes
- Crée les contraintes de clés étrangères
- Crée les vues SQL
- Insère 3 exemples de packages payables en points

### 2. Vérifier les tables
```sql
-- Vérifier la structure
DESCRIBE game_packages;
DESCRIBE rewards;
DESCRIBE purchases;

-- Voir les packages payables en points
SELECT * FROM point_packages;

-- Voir l'historique des échanges
SELECT * FROM points_redemption_history;
```

### 3. Tester l'interface admin
```
Ouvrir: http://localhost/projet%20ismo/admin/rewards_manager.html
```

### 4. Tester l'API
```bash
# Liste des packages
curl -X GET "http://localhost/projet%20ismo/api/shop/redeem_with_points.php" \
  --cookie "session_cookie"

# Échanger des points
curl -X POST "http://localhost/projet%20ismo/api/shop/redeem_with_points.php" \
  -H "Content-Type: application/json" \
  -d '{"package_id": 15}' \
  --cookie "session_cookie"
```

---

## 🎯 Avantages du système

### Pour l'admin
✅ **Création simplifiée:** Un seul formulaire crée reward + package  
✅ **Contrôle total:** Définir coûts, durées, limites  
✅ **Statistiques en temps réel:** Voir combien de fois chaque package est échangé  
✅ **Flexibilité:** Mix de récompenses physiques et packages de jeux  

### Pour l'utilisateur
✅ **Fidélisation:** Utiliser ses points pour jouer  
✅ **Transparence:** Voir exactement combien de points il gagne/dépense  
✅ **ROI:** Récupérer des points bonus en jouant  
✅ **Pas d'argent:** Alternative au paiement monétaire  

### Technique
✅ **Atomic transactions:** Utilisation de `BEGIN/COMMIT` avec `FOR UPDATE`  
✅ **Intégrité des données:** Contraintes de clés étrangères  
✅ **Audit trail:** Toutes les transactions enregistrées dans `points_transactions`  
✅ **Vues SQL:** Requêtes complexes simplifiées  

---

## 🔐 Sécurité

### Protection contre les abus
1. **Verrouillage de ligne:** `SELECT ... FOR UPDATE` empêche les conditions de course
2. **Transaction atomique:** Tout réussit ou tout échoue
3. **Validation côté serveur:** Tous les champs sont vérifiés
4. **Limite d'achats:** `max_purchases_per_user` par package
5. **Authentification requise:** `require_auth()` sur toutes les routes

### Validation des points
```php
// Vérifier avec verrouillage
$stmt = $pdo->prepare('SELECT points FROM users WHERE id = ? FOR UPDATE');
$stmt->execute([$user['id']]);
$currentPoints = $stmt->fetchColumn();

if ($currentPoints < $requiredPoints) {
    $pdo->rollBack();
    // Erreur: points insuffisants
}
```

---

## 📈 Métriques et rapports

### Statistiques disponibles via les vues

#### Top packages échangés
```sql
SELECT 
  package_name,
  game_name,
  points_cost,
  total_redemptions,
  unique_users
FROM point_packages
ORDER BY total_redemptions DESC
LIMIT 10;
```

#### Points dépensés par utilisateur
```sql
SELECT 
  username,
  SUM(points_spent) as total_spent,
  COUNT(*) as purchases_count
FROM points_redemption_history
GROUP BY user_id, username
ORDER BY total_spent DESC;
```

#### ROI des packages
```sql
SELECT 
  package_name,
  points_cost,
  points_earned,
  (points_cost - points_earned) as net_cost,
  ROUND((points_earned / points_cost) * 100, 2) as return_percentage
FROM point_packages
WHERE points_cost > 0;
```

---

## 🛠️ Maintenance

### Désactiver un package
```sql
UPDATE game_packages 
SET is_active = 0 
WHERE id = 15;
```

### Modifier le coût en points
```sql
-- Modifier le package
UPDATE game_packages 
SET points_cost = 60 
WHERE id = 15;

-- Synchroniser la récompense
UPDATE rewards 
SET cost = 60 
WHERE game_package_id = 15;
```

### Voir les utilisateurs qui ont échangé un package
```sql
SELECT 
  u.username,
  p.created_at,
  p.points_spent,
  s.status as session_status
FROM purchases p
JOIN users u ON p.user_id = u.id
LEFT JOIN game_sessions s ON s.purchase_id = p.id
WHERE p.package_id = 15
  AND p.paid_with_points = 1
ORDER BY p.created_at DESC;
```

---

## 🐛 Dépannage

### Problème: Les packages n'apparaissent pas
**Solution:** Vérifier que `is_points_only = 1` et `is_active = 1`
```sql
SELECT id, name, is_points_only, is_active 
FROM game_packages 
WHERE id = 15;
```

### Problème: Points non déduits
**Solution:** Vérifier les transactions
```sql
SELECT * FROM points_transactions 
WHERE user_id = 1 
ORDER BY created_at DESC 
LIMIT 5;
```

### Problème: Erreur "Points insuffisants" mais l'utilisateur a des points
**Solution:** Vérifier les points en temps réel
```sql
SELECT id, username, points 
FROM users 
WHERE id = 1;
```

---

## 📝 TODO / Améliorations futures

- [ ] Interface player pour voir les packages échangeables
- [ ] Notifications push quand nouveaux packages disponibles
- [ ] Système de badges pour gros échangeurs
- [ ] Packages "combo" (plusieurs jeux)
- [ ] Packages "happy hour" (coût réduit à certaines heures)
- [ ] Historique détaillé des échanges dans le profil player
- [ ] Export CSV des statistiques pour l'admin

---

## 📞 Support

En cas de problème:
1. Vérifier les logs: `logs/api_*.log`
2. Vérifier la console navigateur (F12)
3. Tester l'API avec curl/Postman
4. Consulter ce document

---

**Version:** 1.0  
**Date:** 20 Octobre 2025  
**Auteur:** Système Arcade GameZone
