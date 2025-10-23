# 🔧 Fix pour "NetworkError when attempting to fetch resource"

## 🎯 Problème

Erreur "NetworkError when attempting to fetch resource" lors de la connexion admin.

## ✅ Solutions Implémentées

### 1. Amélioration du Proxy Vite

**Fichier modifié** : `createxyz-project/_/apps/web/vite.config.ts`

Ajout de :
- `secure: false` pour éviter les problèmes HTTPS
- Logs de debug pour tracer les requêtes proxy
- Gestion des erreurs de proxy

### 2. Configuration API avec Fallback

**Fichier modifié** : `createxyz-project/_/apps/web/src/utils/apiBase.js`

Ajout de :
- Logs de debug pour voir quelle URL API est utilisée
- Commentaire pour activer le fallback direct si le proxy échoue

### 3. Outil de Diagnostic

**Fichier créé** : `TEST_API_CONNECTION.html`

Outil HTML standalone pour tester :
- Connexion directe au backend PHP
- Connexion via le proxy Vite
- Login admin direct et via proxy
- Diagnostic complet de la configuration

## 🧪 Comment Tester

### Option 1 : Utiliser l'Outil de Diagnostic

1. Ouvrez dans un navigateur :
   ```
   http://localhost/projet%20ismo/TEST_API_CONNECTION.html
   ```

2. Cliquez sur "Lancer Tous les Tests"

3. Vérifiez les résultats :
   - ✅ Si tout est vert : le backend fonctionne
   - ❌ Si erreurs : suivez les instructions dans le rapport

### Option 2 : Tester dans l'Application

1. Assurez-vous que **XAMPP** est démarré (Apache + MySQL)

2. Le serveur React est sur **http://localhost:4000**

3. Ouvrez la console du navigateur (F12)

4. Allez sur la page de login : http://localhost:4000/auth/login

5. Vérifiez les logs :
   ```
   [API Config] API_BASE: /php-api
   [API Config] Window location: http://localhost:4000/auth/login
   ```

6. Essayez de vous connecter avec :
   - Email : `admin@gamezone.fr`
   - Mot de passe : `demo123`

7. Vérifiez dans l'onglet Network (F12) :
   - La requête vers `/php-api/auth/login.php`
   - Le status code (devrait être 200 si succès)

## 🔧 Solutions de Dépannage

### Solution A : Activer le Fallback Direct (RECOMMANDÉ)

Si le proxy ne fonctionne pas, utilisez le backend directement :

**Fichier** : `createxyz-project/_/apps/web/src/utils/apiBase.js`

Ligne 8-11, changez :
```javascript
API_BASE = '/php-api';

// Alternative: Si le proxy ne fonctionne pas, décommentez la ligne ci-dessous
// API_BASE = 'http://localhost/projet%20ismo/api';
```

En :
```javascript
// API_BASE = '/php-api';

// Alternative: Si le proxy ne fonctionne pas, décommentez la ligne ci-dessous
API_BASE = 'http://localhost/projet%20ismo/api';
```

**Puis redémarrez le serveur React** (Ctrl+C puis `npm run dev`)

### Solution B : Vérifier XAMPP

1. Ouvrez le panneau de contrôle XAMPP
2. Vérifiez que **Apache** est démarré (vert)
3. Vérifiez que **MySQL** est démarré (vert)
4. Testez l'accès direct :
   ```
   http://localhost/projet%20ismo/api/auth/check.php
   ```
   Devrait retourner : `{"error":"Unauthorized"}`

### Solution C : Vérifier les Ports

1. Apache doit écouter sur le port **80**
2. React doit écouter sur le port **4000**

Vérifiez avec :
```powershell
# Vérifier Apache
Test-NetConnection -ComputerName localhost -Port 80

# Vérifier React
Test-NetConnection -ComputerName localhost -Port 4000
```

### Solution D : Redémarrer Tout

Si rien ne fonctionne, redémarrez tout :

```powershell
# Dans le dossier du projet
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

# Arrêter le serveur React (Ctrl+C)

# Redémarrer XAMPP (via le panneau de contrôle)

# Redémarrer React
npm run dev
```

## 📊 Diagnostic des Erreurs Courantes

### Erreur : "NetworkError when attempting to fetch"

**Cause** : Le backend PHP n'est pas accessible

**Solutions** :
1. Vérifier que XAMPP/Apache est démarré
2. Tester avec l'outil de diagnostic
3. Activer le fallback direct (Solution A)

### Erreur : "CORS policy"

**Cause** : Problème de Cross-Origin

**Solutions** :
1. Le backend PHP gère déjà CORS dans `api/config.php`
2. Si le problème persiste, utilisez le fallback direct
3. Vérifiez que `credentials: 'include'` est présent

### Erreur : "401 Unauthorized"

**Cause** : C'est normal pour `/auth/check.php` si non connecté

**Solution** : Aucune, c'est le comportement attendu

### Erreur : "404 Not Found"

**Cause** : Le chemin de l'API est incorrect

**Solutions** :
1. Vérifier l'URL dans la console
2. Vérifier que le proxy redirige correctement
3. Tester avec l'outil de diagnostic

## 🎨 Logs de Debug

Avec les modifications, vous devriez voir dans la console :

```
[API Config] API_BASE: /php-api
[API Config] Window location: http://localhost:4000/auth/login
```

Et dans le terminal du serveur React :
```
Sending Request to the Target: POST /php-api/auth/login.php
Received Response from the Target: 200 /php-api/auth/login.php
```

## 📝 Résumé Rapide

### Vérifications Rapides

✅ XAMPP démarré ?
✅ Apache sur port 80 ?
✅ React sur port 4000 ?
✅ Backend accessible : http://localhost/projet%20ismo/api/auth/check.php
✅ Outil de diagnostic fonctionne ?

### Fix Rapide

Si vous voulez juste que ça fonctionne immédiatement :

1. Éditez `createxyz-project/_/apps/web/src/utils/apiBase.js`
2. Ligne 8 : Commentez `API_BASE = '/php-api';`
3. Ligne 11 : Décommentez `API_BASE = 'http://localhost/projet%20ismo/api';`
4. Redémarrez le serveur React
5. Testez la connexion

### Commandes Utiles

```powershell
# Vérifier si Apache fonctionne
Get-Process -Name httpd

# Tester l'API directement
Invoke-WebRequest -Uri "http://localhost/projet%20ismo/api/auth/check.php" -UseBasicParsing

# Voir les logs du serveur React
# (déjà visible dans le terminal où vous avez lancé npm run dev)
```

## 🎉 Après le Fix

Une fois que la connexion fonctionne :

1. ✅ Le nouveau client API (`api-client.js`) gérera automatiquement les erreurs
2. ✅ Les sessions PHP dureront 24 heures
3. ✅ Les erreurs "unauthorized" seront gérées automatiquement
4. ✅ Vous pourrez naviguer sans problème dans l'application

---

**Date** : Octobre 2025  
**Version** : 1.0  
**Status** : ✅ Ready to Fix
