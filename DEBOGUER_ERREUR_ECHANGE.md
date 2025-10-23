# 🐛 Déboguer l'Erreur d'Échange de Récompenses

## ❌ Erreur Actuelle
```
"Internal Server Error" lors de l'échange d'une récompense
```

## ✅ Corrections Appliquées

### 1. Page `/player/rewards` Améliorée

**Nouveautés:**
- ✅ **Modals modernes** au lieu de `alert()` basiques
- ✅ **Logging détaillé** dans la console
- ✅ **Toast de chargement** pendant l'échange
- ✅ **Gestion d'erreur précise** avec 3 niveaux
- ✅ **Messages d'erreur clairs** selon le type d'erreur

---

## 🧪 Comment Tester Maintenant

### Étape 1: Ouvrir la Console

1. Ouvre `http://localhost:4000/player/rewards`
2. Appuie sur **F12** pour ouvrir les DevTools
3. Va dans l'onglet **Console**

### Étape 2: Essayer un Échange

1. Clique sur **"🎁 Échanger"** sur une récompense
2. Un **beau modal moderne** s'affiche (plus un alert() basique)
3. Clique sur **"Confirmer"**

### Étape 3: Observer les Logs

Dans la console, tu verras maintenant:

```javascript
Response status: XXX
Content-Type: application/json (ou text/html si erreur)
Raw response: {...}
```

### Étape 4: Identifier le Problème

#### Cas 1: Status 200 + success: true ✅
```javascript
Response status: 200
Content-Type: application/json
Raw response: {"success":true,"message":"Échange effectué..."}
```
**→ C'EST BON !** Le modal de succès s'affiche et tu es redirigé vers `/player/my-purchases`

#### Cas 2: Status 200 + success: false ⚠️
```javascript
Response status: 200
Content-Type: application/json
Raw response: {"success":false,"error":"Points insuffisants"}
```
**→ Erreur métier** - Le modal d'erreur affiche le message du backend

#### Cas 3: Status 500 ❌
```javascript
Response status: 500
Content-Type: text/html
Raw response: <b>Fatal error</b>: Uncaught Error in /api/shop/redeem_with_points.php:123
```
**→ Erreur PHP** - Le modal affiche "Le serveur a renvoyé une réponse invalide"

**SOLUTION:** Regarde le "Raw response" pour voir l'erreur PHP exacte

#### Cas 4: Network Error 🌐
```javascript
Failed to fetch
```
**→ Problème réseau** - Vérifie que:
- Apache est démarré
- L'API est accessible: `http://localhost/projet%20ismo/api/shop/redeem_with_points.php`

---

## 🔍 Erreurs Possibles et Solutions

### Erreur 1: "Points insuffisants"
```json
{
  "error": "Points insuffisants",
  "required_points": 50,
  "current_points": 30
}
```
**Solution:** Gagne plus de points en jouant

### Erreur 2: "Package non trouvé"
```json
{
  "error": "Package non trouvé ou non disponible"
}
```
**Solution:**
- Le package a été supprimé
- Le package est désactivé (is_active = 0)
- Le jeu associé est désactivé

### Erreur 3: "Limite d'achats atteinte"
```json
{
  "error": "Limite d'achats atteinte pour ce package",
  "max_purchases": 3,
  "current_purchases": 3
}
```
**Solution:** Ce package a une limite d'achats par utilisateur

### Erreur 4: Fatal Error PHP
```html
<b>Fatal error</b>: Uncaught PDOException: SQLSTATE[23000]: 
Integrity constraint violation...
```
**Solution:**
- Regarde le message d'erreur complet dans la console
- Vérifie que la base de données est bien configurée
- Vérifie que toutes les contraintes FK sont en place

---

## 🛠️ Actions de Debug Avancées

### 1. Vérifier que l'API fonctionne directement

Teste l'API via ton navigateur:
```
http://localhost/projet%20ismo/api/shop/redeem_with_points.php
```

**Devrait retourner:**
```json
{
  "packages": [...]
}
```

Si tu vois une erreur PHP, c'est un problème backend à corriger.

### 2. Vérifier les Logs PHP

Ouvre:
```
c:\xampp\htdocs\projet ismo\logs\api_*.log
```

Cherche des erreurs récentes liées à `redeem_with_points.php`

### 3. Tester l'Échange Manuellement

Utilise le fichier HTML de test:
```
http://localhost/projet%20ismo/test_rewards_frontend.html
```

Essaie d'échanger une récompense depuis cette page.
Si ça fonctionne ici mais pas sur React, c'est un problème frontend.

### 4. Vérifier la Base de Données

Ouvre phpMyAdmin et vérifie:

#### Table `game_packages`
```sql
SELECT * FROM game_packages WHERE is_points_only = 1 AND is_active = 1;
```
Vérifie qu'il y a des packages actifs.

#### Table `games`
```sql
SELECT id, name, is_active FROM games WHERE id IN (
  SELECT game_id FROM game_packages WHERE is_points_only = 1
);
```
Vérifie que les jeux associés sont actifs.

#### Points de l'utilisateur
```sql
SELECT id, username, points FROM users WHERE id = [TON_USER_ID];
```
Vérifie que tu as assez de points.

---

## 📋 Checklist de Vérification

Avant de tester l'échange, vérifie que:

- [ ] **XAMPP est démarré** (Apache + MySQL)
- [ ] **Tu es connecté** en tant que joueur (pas admin)
- [ ] **Tu as des points** (vérifie dans la barre de points)
- [ ] **Il y a des récompenses** affichées sur la page
- [ ] **La console est ouverte** (F12)
- [ ] **Aucune erreur** rouge dans la console avant de tester

---

## 🎯 Ce qui se passe Maintenant (Flow Amélioré)

### 1. Tu cliques sur "🎁 Échanger"
```javascript
→ showConfirm() affiche un modal moderne
→ Plus de confirm() basique
```

### 2. Tu cliques sur "Confirmer"
```javascript
→ toast.loading('Échange en cours...')
→ fetch POST /api/shop/redeem_with_points.php
→ console.log('Response status:', ...)
→ console.log('Raw response:', ...)
```

### 3. Réponse du Serveur

#### Si Succès ✅
```javascript
→ toast.dismiss()
→ showSuccess('✅ Échange Réussi !', details)
→ loadPackages() (recharge les données)
→ setTimeout(() => navigate('/player/my-purchases'), 1500)
```

#### Si Erreur Backend ⚠️
```javascript
→ toast.dismiss()
→ showError('Échange Impossible', error_message)
```

#### Si Erreur Réseau ❌
```javascript
→ toast.dismiss()
→ showError('Erreur Réseau', 'Impossible de contacter le serveur')
```

---

## 💡 Astuce: Forcer un Succès pour Tester le Modal

Si tu veux juste tester le modal de succès sans faire un vrai échange:

```javascript
// Ajoute temporairement dans la console:
showSuccess(
  '✅ Test Réussi',
  '🎮 30 minutes de jeu\n💸 50 points dépensés\n✨ +10 points bonus\n💰 Points restants: 200'
);
```

---

## 📞 Si le Problème Persiste

Copie-colle le contenu de la console (tous les logs) et partage-le.

**Informations à fournir:**
1. Le **message d'erreur complet** dans le modal
2. Les **logs de la console** (Response status, Raw response)
3. Le **contenu de `logs/api_*.log`** si disponible
4. Les **étapes exactes** pour reproduire l'erreur

---

**Date:** 21 octobre 2025  
**Version:** 1.0 - Debug Amélioré  
**Status:** ✅ Prêt pour Test
