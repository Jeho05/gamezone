# ⏱️ Système de Points Basés sur le Temps Réel de Jeu

## 🎯 Concept

Le système calcule **automatiquement** les points gagnés par les joueurs en fonction du **temps réellement joué**, et non du package acheté.

---

## 📐 **Formule de Calcul**

```
Points Gagnés = (Temps Joué en Minutes / 60) × Points par Heure du Jeu
```

### **Exemple Concret**

**Configuration du Jeu :**
- Jeu : **FIFA 2024**
- Points par heure : **15 points/h**

**Scénarios :**

| Temps Joué | Calcul | Points Gagnés |
|------------|--------|---------------|
| 15 minutes | (15/60) × 15 = 0.25 × 15 | **4 points** |
| 30 minutes | (30/60) × 15 = 0.5 × 15 | **8 points** |
| 1 heure (60 min) | (60/60) × 15 = 1 × 15 | **15 points** |
| 2 heures (120 min) | (120/60) × 15 = 2 × 15 | **30 points** |
| 45 minutes | (45/60) × 15 = 0.75 × 15 | **11 points** |

---

## 🔄 **Workflow Complet**

### **1️⃣ Achat d'un Package**

```
Player achète : "Premium 2h - FIFA 2024"
- Durée : 120 minutes
- Prix : 2500 XOF
- Points potentiels : (120/60) × 15 = 30 points
```

**État :** 
- ❌ Points **PAS** crédités immédiatement
- ⏸️ Paiement en attente

---

### **2️⃣ Confirmation du Paiement (Admin)**

```
Admin confirme le paiement
→ Session créée (status: "pending")
→ Temps total : 120 minutes
→ Temps utilisé : 0 minute
```

**État :**
- ✅ Paiement confirmé
- 📋 Session prête à démarrer
- ❌ Points toujours **pas crédités**

---

### **3️⃣ Démarrage de la Session**

```php
POST /api/sessions/start_session.php
{
  "purchase_id": 123
}
```

**Réponse :**
```json
{
  "success": true,
  "session": {
    "id": 456,
    "status": "active",
    "total_minutes": 120,
    "used_minutes": 0,
    "started_at": "2025-10-16 14:00:00"
  },
  "points_calculation": {
    "points_per_hour": 15,
    "total_minutes": 120,
    "max_points": 30,
    "formula": "(minutes / 60) × points_per_hour"
  }
}
```

**État :**
- ✅ Session active
- ⏱️ Chronomètre démarre
- ❌ Points **toujours pas crédités**

---

### **4️⃣ Mise à Jour du Temps (Pendant le Jeu)**

Le système met à jour régulièrement le temps joué :

```php
POST /api/sessions/update_session.php
{
  "session_id": 456,
  "action": "update_time",
  "used_minutes": 45  // Player a joué 45 minutes
}
```

**Calcul Automatique :**
```
Points = (45 / 60) × 15 = 11.25 ≈ 11 points
```

**Réponse :**
```json
{
  "success": true,
  "session": {
    "used_minutes": 45,
    "remaining_minutes": 75,
    "calculated_points": 11,
    "points_this_update": 11
  }
}
```

**État :**
- ✅ **11 points crédités** dans le compte du joueur
- 💰 Solde de points mis à jour
- 📊 Transaction enregistrée

---

### **5️⃣ Continuation du Jeu**

Le joueur continue à jouer :

```php
POST /api/sessions/update_session.php
{
  "session_id": 456,
  "action": "update_time",
  "used_minutes": 90  // Total 90 minutes maintenant
}
```

**Calcul Incrémental :**
```
Total calculé : (90 / 60) × 15 = 22.5 ≈ 23 points
Déjà crédité : 11 points
À créditer : 23 - 11 = 12 points
```

**Réponse :**
```json
{
  "success": true,
  "session": {
    "used_minutes": 90,
    "remaining_minutes": 30,
    "calculated_points": 23,
    "points_this_update": 12
  }
}
```

**État :**
- ✅ **12 points supplémentaires** crédités
- 💰 Total crédité : 23 points
- 📊 Nouvelle transaction enregistrée

---

### **6️⃣ Fin de Session**

**Scénario A : Le joueur termine avant la fin**
```php
POST /api/sessions/update_session.php
{
  "session_id": 456,
  "action": "complete",
  "used_minutes": 100  // Arrêt à 100 minutes (au lieu de 120)
}
```

**Calcul Final :**
```
Total calculé : (100 / 60) × 15 = 25 points
Déjà crédité : 23 points
À créditer : 25 - 23 = 2 points
```

**Résultat :**
- ✅ **2 points finaux** crédités
- ✅ Total crédité : **25 points** (pour 100 min jouées)
- ❌ Les 20 minutes non utilisées ne donnent **aucun point**

---

**Scénario B : Le joueur utilise tout le temps**
```php
POST /api/sessions/update_session.php
{
  "session_id": 456,
  "action": "complete",
  "used_minutes": 120  // Utilise les 120 minutes complètes
}
```

**Calcul Final :**
```
Total calculé : (120 / 60) × 15 = 30 points
Déjà crédité : 23 points
À créditer : 30 - 23 = 7 points
```

**Résultat :**
- ✅ **7 points finaux** crédités
- ✅ Total crédité : **30 points** (maximum possible)

---

## 💾 **Enregistrement dans la Base de Données**

### **Table `points_transactions`**

Chaque mise à jour de temps crée une transaction :

```sql
INSERT INTO points_transactions (
  user_id, 
  change_amount, 
  reason, 
  type, 
  reference_type, 
  reference_id, 
  created_at
) VALUES (
  123,  -- ID du joueur
  11,   -- Points crédités
  'Temps de jeu: FIFA 2024 (45 min = 0.8 pts/h × 15 pts/h)',
  'game_session',
  'game_session',
  456,  -- ID de la session
  NOW()
);
```

### **Mise à Jour du Solde**

```sql
UPDATE users 
SET points = points + 11, 
    updated_at = NOW() 
WHERE id = 123;
```

### **Statistiques**

```sql
INSERT INTO user_stats (user_id, total_points_earned, updated_at)
VALUES (123, 11, NOW())
ON DUPLICATE KEY UPDATE 
  total_points_earned = total_points_earned + 11,
  updated_at = NOW();
```

---

## 📊 **Avantages du Système**

### ✅ **Équitable**
- Les joueurs paient **uniquement pour le temps utilisé**
- Pas de perte si arrêt anticipé

### ✅ **Motivant**
- Plus vous jouez, plus vous gagnez
- Calcul en temps réel

### ✅ **Transparent**
- Formule simple et claire
- Historique complet dans `points_transactions`

### ✅ **Flexible**
- Chaque jeu a son propre `points_per_hour`
- Ajustable par l'admin

---

## 🔧 **Configuration Admin**

### **Modifier les Points par Heure d'un Jeu**

1. Allez sur `/admin/shop`
2. Cliquez sur "Modifier" sur un jeu
3. Changez **"Points par Heure"**
4. Exemple :
   - Jeu populaire (FIFA) : **20 pts/h**
   - Jeu standard (COD) : **15 pts/h**
   - Jeu VR (Half-Life Alyx) : **25 pts/h** (plus immersif)

**Impact immédiat :**
- Toutes les **nouvelles sessions** utiliseront le nouveau taux
- Les sessions **en cours** gardent le taux initial

---

## 📈 **Exemples Réels**

### **Exemple 1 : Joueur Casual**

**Achat :** 
- Package : 1h de GTA V
- Points/h : 12 pts/h

**Jeu :**
- Temps joué : **35 minutes**
- Points gagnés : (35/60) × 12 = **7 points**

---

### **Exemple 2 : Joueur Régulier**

**Achat :**
- Package : 3h de Forza Horizon
- Points/h : 18 pts/h

**Jeu :**
- Session 1 : 60 min → **18 points**
- Session 2 : 45 min → **14 points**
- Session 3 : 75 min → **23 points**
- **Total : 55 points** pour 180 minutes

---

### **Exemple 3 : Joueur Intensif**

**Achat :**
- Package : 5h de Call of Duty
- Points/h : 20 pts/h

**Jeu :**
- Utilise les **300 minutes complètes**
- Points gagnés : (300/60) × 20 = **100 points** (maximum)

---

## 🛡️ **Protection Anti-Abus**

### **Vérifications**
1. ✅ Le temps utilisé **ne peut pas dépasser** le temps acheté
2. ✅ Les points sont calculés **uniquement** sur le temps réel
3. ✅ Impossible de "tricher" le chronomètre
4. ✅ Toutes les transactions sont **traçables**

### **Remboursements**
Si un admin rembourse un achat :
- ❌ **Tous les points crédités** sont retirés
- ❌ La session est annulée
- 📊 Transaction de débit enregistrée

---

## 🚀 **API Endpoints**

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/sessions/start_session.php` | POST | Démarrer une session |
| `/api/sessions/update_session.php` | POST | Mettre à jour le temps/points |
| `/api/sessions/my_sessions.php` | GET | Voir mes sessions |
| `/api/admin/purchases.php` | PATCH | Confirmer paiement (admin) |

---

## ✅ **Résumé**

**Le système de points par heure fonctionne maintenant vraiment !**

1. ✅ Calcul **automatique** basé sur le temps réel
2. ✅ Formule simple : **(minutes / 60) × points_per_hour**
3. ✅ Crédit **incrémental** pendant le jeu
4. ✅ Transparent et **traçable**
5. ✅ Équitable pour tous les joueurs

---

**Les joueurs sont récompensés proportionnellement au temps qu'ils passent réellement à jouer ! ⏱️🎮✨**
