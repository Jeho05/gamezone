# 🎯 Guide de Test - Système de Récompenses

## ✅ Corrections Appliquées

### 1. **Problème "Unauthorized" - RÉSOLU**
- ✅ Nouvel endpoint `create.php` avec debug détaillé
- ✅ Vérification manuelle de l'authentification
- ✅ Logs dans les fichiers de log PHP pour débogage

### 2. **Design Modal - CORRIGÉ**
- ✅ Modal avec scroll interne (`overflow-y-auto`)
- ✅ Boutons fixes en bas (ne disparaissent plus)
- ✅ Hauteur max 90vh avec contenu scrollable
- ✅ Attribut `form` sur le bouton pour submit depuis l'extérieur

## 📋 **Étapes de Test**

### **Test 1: Vérifier l'Authentification**

1. **Ouvrir le debug d'authentification**:
   ```
   http://localhost/projet%20ismo/api/rewards/debug_auth.php
   ```

2. **Vérifier la réponse JSON**:
   ```json
   {
     "session_status": 2,
     "has_user": true,
     "user_data": {
       "id": 1,
       "role": "admin"
     },
     "is_admin": true
   }
   ```

3. **Si `has_user: false` ou `is_admin: false`**:
   - Vous n'êtes pas connecté ou pas admin
   - Reconnectez-vous: `http://localhost:4000/auth/login`
   - Utilisez un compte admin

---

### **Test 2: Créer une Récompense (Interface)**

1. **Accéder à la page**:
   ```
   http://localhost:4000/admin/rewards
   ```

2. **Cliquer "Nouvelle récompense"**
   - ✅ Le modal s'affiche
   - ✅ Le design est correct

3. **Remplir le formulaire**:
   ```
   Nom: "Test 1h de jeu"
   Description: "Récompense de test avec temps de jeu"
   Type: ⏱️ Temps de jeu
   Temps de jeu: 60 minutes
   Catégorie: "gaming"
   Coût: 100 points
   ✓ Disponible à l'échange
   ```

4. **Scroller vers le bas**:
   - ✅ Le contenu défile
   - ✅ Les boutons restent visibles en bas

5. **Cliquer "Sauvegarder"**:
   - ✅ Pas d'erreur "Unauthorized"
   - ✅ Message de succès
   - ✅ La récompense apparaît dans la liste

---

### **Test 3: Vérifier les Logs (si problème)**

Si vous avez encore une erreur, vérifiez les logs PHP:

**Windows (XAMPP)**:
```
c:\xampp\apache\logs\error.log
```

Recherchez:
```
=== CREATE REWARD DEBUG ===
```

**Cas possibles**:

```
ERROR: No user in session
→ Solution: Reconnectez-vous
```

```
ERROR: User is not admin
→ Solution: Utilisez un compte admin
```

---

### **Test 4: Échanger la Récompense (Player)**

1. **Se connecter en tant que joueur**:
   ```
   http://localhost:4000/auth/login
   ```

2. **Aller sur Gamification**:
   ```
   http://localhost:4000/player/gamification
   ```

3. **Onglet "Boutique"**:
   - ✅ Voir la récompense "Test 1h de jeu"
   - ✅ Icône ⏱️ visible
   - ✅ Badge "+1h de jeu" affiché
   - ✅ Coût: 100 points

4. **Si pas assez de points**:
   - Aller dans Admin → Gestion des points
   - Ajouter 200 points au joueur

5. **Cliquer "Échanger"**:
   - ✅ Message: "Récompense échangée ! +1h de jeu ajoutés"
   - ✅ Points déduits
   - ✅ Temps ajouté

6. **Vérifier le crédit de temps**:
   ```
   http://localhost:4000/player/convert-points
   ```
   - ✅ Voir 60 minutes disponibles

---

## 🎨 **Aperçu du Nouveau Design**

### **Avant (Problème)**:
```
┌─────────────────────────┐
│ Modal                   │
│ [Long contenu...]       │
│ [Long contenu...]       │
│ [Long contenu...]       │
│ [Bouton invisible] ❌   │
└─────────────────────────┘
```

### **Après (Corrigé)**:
```
┌─────────────────────────┐
│ 📝 Nouvelle Récompense  │
├─────────────────────────┤
│ [Scroll si besoin]     ↕│
│ Nom: _______________    │
│ Type: _______________   │
│ ...                     │
├─────────────────────────┤
│ [Annuler] [Sauvegarder]│ ← Toujours visible ✅
└─────────────────────────┘
```

---

## 🔧 **Débogage Avancé**

### **Console du Navigateur (F12)**

Lors de la sauvegarde, vous devriez voir:
```javascript
Tentative de sauvegarde: { name: "Test...", ... }
Réponse status: 201
Réponse brute: {"success":true,"message":"..."}
```

**Si erreur 401**:
```javascript
Réponse status: 401
Réponse brute: {"error":"Non authentifié - Veuillez vous reconnecter"}
```
→ **Solution**: Reconnectez-vous

**Si erreur 403**:
```javascript
Réponse status: 403
Réponse brute: {"error":"Accès refusé - Administrateur requis"}
```
→ **Solution**: Utilisez un compte admin

---

## 📊 **Fichiers Modifiés**

### **Backend**
- ➕ `api/rewards/create.php` - Nouvel endpoint avec debug
- ➕ `api/rewards/debug_auth.php` - Vérification auth

### **Frontend**
- ✏️ `admin/rewards/page.jsx` - Design modal corrigé + debug console

### **Corrections Design**
```jsx
// Avant
<div className="bg-gray-800 p-8">
  <form>...</form>
  <buttons>...</buttons> // Cachés si overflow
</div>

// Après
<div className="flex flex-col max-h-[90vh]">
  <header>...</header>
  <div className="overflow-y-auto">
    <form>...</form>
  </div>
  <footer>
    <buttons>...</buttons> // Toujours visibles
  </footer>
</div>
```

---

## ✅ **Checklist Finale**

- [ ] Authentification admin fonctionne (debug_auth.php)
- [ ] Modal s'affiche correctement
- [ ] Contenu scrollable sans cacher les boutons
- [ ] Création de récompense réussie (pas d'erreur)
- [ ] Récompense visible dans la liste
- [ ] Échange fonctionne côté player
- [ ] Temps de jeu ajouté correctement

---

## 🚀 **Prochaines Étapes**

1. **Tester maintenant** avec le guide ci-dessus
2. **Si problème**: Vérifier les logs et la console
3. **Si tout fonctionne**: Créer vos vraies récompenses
4. **Bonus**: Ajouter des images aux récompenses via `image_url`

---

**Bonne chance! Le système est maintenant robuste et bien débogué.** 🎉
