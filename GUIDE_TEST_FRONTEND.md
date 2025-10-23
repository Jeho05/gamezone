# ✅ GUIDE DE TEST FRONTEND - RÉCOMPENSES

## Résumé des tests backend

### ✅ Backend vérifié et fonctionnel

L'API `api/shop/redeem_with_points.php` retourne correctement **4 packages**:

1. **FIFA 2024 - 30 minutes** (50 points)
2. **Naruto - 30 minutes** (150 points)  
3. **TEST NOUVELLE Récompense - 45 minutes** (200 points) 🆕
4. **Action Game - 1 heure** (100 points)

La nouvelle récompense créée est VISIBLE dans l'API backend.

---

## 🔍 Comment tester le frontend

### Étape 1: Démarrer le serveur de développement

```bash
cd createxyz-project/_/apps/web
npm run dev
```

Le serveur devrait démarrer sur `http://localhost:4000`

### Étape 2: Accéder à la page Récompenses

1. Ouvrez votre navigateur
2. Connectez-vous en tant que **joueur** (pas admin)
3. Accédez à: `http://localhost:4000/player/rewards`

### Étape 3: Vérifier l'affichage

Vous devriez voir:
- ✅ 4 packages affichés (ou plus si d'autres ont été créés)
- ✅ La nouvelle récompense "TEST NOUVELLE Récompense - 45 minutes"
- ✅ Badge 🔥 NOUVEAU sur la nouvelle récompense
- ✅ Coût: 200 points

---

## 🐛 Si les récompenses ne s'affichent pas

### Problème 1: Serveur non démarré

**Symptôme**: Page blanche ou erreur de connexion

**Solution**:
```bash
cd createxyz-project/_/apps/web
npm run dev
```

### Problème 2: Cache du navigateur

**Symptôme**: Anciennes données affichées

**Solution**:
1. Ouvrez les DevTools (F12)
2. Faites un "Hard Refresh" (Ctrl+Shift+R ou Ctrl+F5)
3. Ou videz le cache du navigateur

### Problème 3: Erreur d'authentification

**Symptôme**: Redirection vers la page de login

**Solution**:
1. Connectez-vous en tant que **player** (pas admin)
2. Vérifiez que le rôle est bien "player"

### Problème 4: Erreur API (Network Error)

**Symptôme**: Message d'erreur dans la console ou page vide

**Solution**:
1. Vérifiez que XAMPP Apache est démarré
2. Vérifiez la configuration du proxy dans `vite.config.ts`
3. Testez l'API directement: `http://localhost/projet%20ismo/test_backend_api_direct.php`

---

## 🔧 Tests de diagnostic

### Test 1: Vérifier l'API directement

Ouvrez dans le navigateur:
```
http://localhost/projet%20ismo/test_backend_api_direct.php
```

Vous devriez voir un JSON avec 4 packages.

### Test 2: Vérifier la console du navigateur

1. Ouvrez la page `/player/rewards`
2. Appuyez sur F12
3. Allez dans l'onglet "Console"
4. Recherchez des erreurs

### Test 3: Vérifier les requêtes réseau

1. Ouvrez la page `/player/rewards`
2. Appuyez sur F12
3. Allez dans l'onglet "Network"
4. Rechargez la page
5. Cherchez la requête à `redeem_with_points.php`
6. Vérifiez:
   - Status Code: devrait être 200
   - Response: devrait contenir les 4 packages

---

## 📝 Créer une nouvelle récompense via l'admin

1. Accédez à `/admin/rewards-manager.html`
2. Remplissez le formulaire:
   - **Nom**: "Ma Nouvelle Récompense"
   - **Type**: game_package
   - **Coût**: 250 points
   - **Jeu**: Sélectionnez un jeu actif
   - **Durée**: 60 minutes
   - **Points bonus**: 20
   - **En vedette**: ✅
3. Cliquez sur "Créer"
4. Rafraîchissez la page joueur `/player/rewards`
5. La nouvelle récompense devrait apparaître immédiatement

---

## ✅ Checklist de vérification

- [ ] XAMPP Apache démarré
- [ ] MySQL démarré
- [ ] Migration SQL appliquée (`APPLIQUER_FIX_REWARDS.bat`)
- [ ] Packages créés (minimum 3-4 packages visibles)
- [ ] Serveur frontend démarré (`npm run dev`)
- [ ] Navigateur ouvert sur `http://localhost:4000`
- [ ] Connecté en tant que **player**
- [ ] Page `/player/rewards` accessible
- [ ] Récompenses affichées correctement

---

## 🎯 Résultat attendu

Sur la page `/player/rewards`, vous devriez voir une grille de cartes avec:

```
┌─────────────────┬─────────────────┬─────────────────┐
│  FIFA 2024      │  Naruto         │  TEST NOUVELLE  │
│  30 min         │  30 min         │  45 min         │
│  50 pts         │  150 pts        │  200 pts        │
│  ⭐ En vedette   │  ⭐ En vedette   │  🔥 NOUVEAU     │
└─────────────────┴─────────────────┴─────────────────┘
┌─────────────────┐
│  Action Game    │
│  1h             │
│  100 pts        │
│  ⭐ En vedette   │
└─────────────────┘
```

Chaque carte affiche:
- Image du jeu (ou gradient de couleur)
- Nom de la récompense
- Durée en minutes
- Coût en points
- Points bonus
- Badge "En vedette" ou "NOUVEAU"
- Bouton "Échanger" (activé si assez de points)

---

**Date**: 21 octobre 2025  
**Backend Status**: ✅ Fonctionnel (testé et vérifié)  
**Nombre de packages**: 4 (confirmé)
