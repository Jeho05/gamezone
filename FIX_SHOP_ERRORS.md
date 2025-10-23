# 🔧 Guide de Résolution - Erreurs Boutique

## ❌ Problèmes Identifiés

1. **Erreur lors du chargement des jeux** sur `/player/shop`
2. **Erreur lors du chargement** sur `/player/my-purchases`

---

## 🎯 Diagnostic Rapide

### Étape 1 : Vérifier la Base de Données

Ouvrez votre navigateur et allez sur :

```
http://localhost/projet%20ismo/TEST_SHOP_DEBUG.html
```

Cliquez sur **"🔍 Vérifier la DB"**

### Ce que vous devriez voir :

✅ **Si tout va bien :**
```json
{
  "success": true,
  "database": "Connected",
  "tables": {
    "games": { "exists": true, "count": 8 },
    "game_packages": { "exists": true, "count": 15 },
    "payment_methods": { "exists": true, "count": 5 },
    "purchases": { "exists": true, "count": 0 },
    "game_sessions": { "exists": true, "count": 0 }
  }
}
```

❌ **Si vous voyez des erreurs "Table doesn't exist" :**
→ Passez à l'**Étape 2**

---

## 🔨 Solutions

### Solution 1 : Exécuter la Migration SQL

#### Option A : Via phpMyAdmin

1. Ouvrez **phpMyAdmin** : http://localhost/phpmyadmin
2. Sélectionnez votre base de données **`gamezone`**
3. Cliquez sur l'onglet **SQL**
4. Ouvrez le fichier :
   ```
   c:\xampp\htdocs\projet ismo\api\migrations\add_game_purchase_system.sql
   ```
5. **Copiez tout le contenu** du fichier
6. **Collez-le** dans phpMyAdmin
7. Cliquez sur **"Exécuter"**

#### Option B : Via MySQL Console

```bash
# Ouvrir le terminal
cd c:\xampp\mysql\bin

# Se connecter à MySQL
mysql -u root -p

# Sélectionner la base de données
USE gamezone;

# Exécuter la migration
SOURCE c:/xampp/htdocs/projet ismo/api/migrations/add_game_purchase_system.sql
```

---

### Solution 2 : Insérer des Données de Test

Une fois les tables créées, vous devez insérer des données de test.

1. **Connectez-vous en tant qu'ADMIN** sur votre application
2. Ouvrez dans votre navigateur :
   ```
   http://localhost/projet%20ismo/api/shop/seed_test_data.php
   ```

**OU** utilisez la page de test :

1. Allez sur `http://localhost/projet%20ismo/TEST_SHOP_DEBUG.html`
2. Cliquez sur **"🌱 Insérer Données de Test"**

### Données insérées :

- ✅ **8 jeux** (FIFA, Call of Duty, GTA V, etc.)
- ✅ **15+ packages** de temps de jeu
- ✅ **5 méthodes de paiement**

---

## 🧪 Vérification

### Test 1 : Charger les Jeux

Sur `TEST_SHOP_DEBUG.html`, cliquez sur **"📋 Charger les Jeux"**

✅ **Succès attendu :**
```json
{
  "success": true,
  "count": 8,
  "games": [
    {
      "id": 1,
      "name": "FIFA 2024",
      "category": "sports",
      "min_price": "2.50",
      "packages_count": 4
    },
    ...
  ]
}
```

---

### Test 2 : Charger Mes Achats

Sur `TEST_SHOP_DEBUG.html`, cliquez sur **"🛍️ Charger Mes Achats"**

✅ **Succès attendu (si connecté) :**
```json
{
  "success": true,
  "count": 0,
  "purchases": []
}
```

❌ **Si non authentifié :**
```json
{
  "error": "Non authentifié",
  "message": "Vous devez être connecté"
}
```
→ Connectez-vous d'abord sur l'application

---

## 📊 Structure des Tables Créées

### `games` - Catalogue des jeux
- Jeux disponibles à l'achat
- Catégories, plateformes, joueurs
- Points par heure, prix de base

### `game_packages` - Packages de temps
- Durées (15min, 1h, 3h, etc.)
- Prix et promotions
- Points gagnés

### `payment_methods` - Méthodes de paiement
- Espèces, Mobile Money, Carte
- Configuration paiement en ligne/manuel

### `purchases` - Achats utilisateurs
- Historique des achats
- Statut paiement (pending, completed, etc.)
- Points crédités

### `game_sessions` - Sessions de jeu
- Temps utilisé/restant
- Statut (active, completed, etc.)
- Dates de début/fin

---

## 🐛 Erreurs Courantes

### Erreur 1 : "Table 'gamezone.games' doesn't exist"

**Cause :** Migration pas exécutée  
**Solution :** Exécutez la migration SQL (Solution 1)

---

### Erreur 2 : "No games found" ou liste vide

**Cause :** Pas de données insérées  
**Solution :** Exécutez le seed (Solution 2)

---

### Erreur 3 : "Unauthorized" sur my_purchases

**Cause :** Session expirée ou utilisateur non connecté  
**Solution :** 
1. Allez sur `http://localhost:4000/`
2. Connectez-vous avec vos identifiants
3. Réessayez

---

### Erreur 4 : "CORS error" dans la console

**Cause :** Configuration CORS  
**Solution :** Vérifiez que `api/config.php` contient :

```php
// CORS Headers
header('Access-Control-Allow-Origin: http://localhost:4000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

---

## ✅ Checklist Finale

Après avoir suivi les solutions, vérifiez :

- [ ] Tables créées dans phpMyAdmin
- [ ] Données de test insérées (8 jeux minimum)
- [ ] API `/api/shop/games.php` retourne des jeux
- [ ] API `/api/shop/my_purchases.php` accessible (si connecté)
- [ ] Page `/player/shop` affiche les jeux
- [ ] Page `/player/my-purchases` se charge sans erreur

---

## 🚀 Test Final

1. **Ouvrez** : `http://localhost:4000/player/shop`
2. **Vérifiez** : Les jeux s'affichent avec leurs packages
3. **Cliquez** sur un jeu
4. **Vérifiez** : La page de détails se charge
5. **Allez sur** : `http://localhost:4000/player/my-purchases`
6. **Vérifiez** : La page se charge (liste vide si aucun achat)

---

## 📞 Aide Supplémentaire

Si le problème persiste après avoir suivi ce guide :

1. **Vérifiez les logs Apache** : `c:\xampp\apache\logs\error.log`
2. **Vérifiez les logs MySQL** : `c:\xampp\mysql\data\*.err`
3. **Ouvrez la console du navigateur** (F12) et regardez les erreurs

---

## 🎯 Résumé Rapide

```bash
# 1. Exécuter migration SQL
phpMyAdmin → gamezone → SQL → Coller add_game_purchase_system.sql → Exécuter

# 2. Insérer données de test
http://localhost/projet%20ismo/api/shop/seed_test_data.php

# 3. Tester
http://localhost/projet%20ismo/TEST_SHOP_DEBUG.html

# 4. Vérifier l'application
http://localhost:4000/player/shop
```

---

**Après avoir suivi ces étapes, votre boutique devrait fonctionner parfaitement ! ✅**
