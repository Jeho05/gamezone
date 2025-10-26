# 🚀 Déploiement Backend sur Railway.app - Guide Étape par Étape

## ✅ Préparation Terminée

Le code est prêt et pushé sur GitHub:
- Branch: `backend-railway`
- Fichiers: Dockerfile, railway.json, config.php adapté

---

## 📋 ÉTAPES À SUIVRE MAINTENANT

### Étape 1: Créer Compte Railway (2 minutes)

1. **Aller sur:** https://railway.app
2. **Cliquer:** "Login" → "Login with GitHub"
3. **Autoriser Railway** à accéder à vos repos GitHub
4. **Confirmer** votre email si demandé

✅ **Compte créé!**

---

### Étape 2: Créer Nouveau Projet (3 minutes)

1. **Dashboard Railway** → Cliquer "**New Project**"
2. Sélectionner "**Deploy from GitHub repo**"
3. Chercher et sélectionner: **`Jeho05/gamezone`**
4. **IMPORTANT:** 
   - Branch: Sélectionner **`backend-railway`** (pas main!)
   - Root Directory: **`backend_infinityfree/api`**
5. Cliquer "**Deploy**"

Railway va:
- Détecter le Dockerfile
- Build l'image Docker
- Déployer automatiquement

⏱️ **Attendre 2-3 minutes** que le build se termine

---

### Étape 3: Ajouter Base de Données MySQL (2 minutes)

1. Dans votre projet Railway, cliquer "**+ New**"
2. Sélectionner "**Database**" → "**Add MySQL**"
3. Railway crée automatiquement:
   - Base de données MySQL
   - Variables d'environnement (MYSQLHOST, MYSQLDATABASE, etc.)
   - Connexion automatique au backend

✅ **Base de données créée!**

---

### Étape 4: Configurer Variables d'Environnement (1 minute)

1. Dans Railway, cliquer sur le **service backend** (pas la DB)
2. Onglet "**Variables**"
3. Railway a déjà ajouté automatiquement:
   - `MYSQLHOST`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLPORT`

4. **Ajouter manuellement** ces variables:
   ```
   SESSION_SAMESITE = None
   SESSION_SECURE = 1
   APP_ENV = production
   ```

5. Cliquer "**Redeploy**" si demandé

✅ **Variables configurées!**

---

### Étape 5: Obtenir l'URL du Backend (1 minute)

1. Dans Railway, cliquer sur le **service backend**
2. Onglet "**Settings**"
3. Section "**Networking**"
4. Cliquer "**Generate Domain**"
5. Railway génère une URL comme:
   ```
   https://backend-production-xxxx.up.railway.app
   ```

6. **COPIER cette URL** - vous en aurez besoin!

✅ **URL obtenue!**

---

### Étape 6: Créer les Tables de Base de Données (2 minutes)

Votre backend est déployé mais la DB est vide. Il faut créer les tables.

**Option A - Via Railway CLI (recommandé):**

Ouvrir l'URL de votre backend dans le navigateur:
```
https://backend-production-xxxx.up.railway.app/install.php
```

**Option B - Via SQL direct:**

1. Dans Railway, cliquer sur la **base de données MySQL**
2. Onglet "**Data**"
3. Cliquer "**Query**"
4. Copier-coller le contenu de `backend_infinityfree/api/schema.sql`
5. Exécuter

✅ **Tables créées!**

---

### Étape 7: Mettre à Jour le Frontend Vercel (3 minutes)

1. **Aller sur:** https://vercel.com/jeho05/gamezoneismo
2. **Settings** → **Environment Variables**
3. **Trouver:** `NEXT_PUBLIC_API_BASE`
4. **Modifier** la valeur:
   ```
   Ancienne: https://ismo.gamer.gd/api
   Nouvelle: https://backend-production-xxxx.up.railway.app
   ```
   ⚠️ **Remplacer `xxxx` par votre vrai URL Railway!**

5. **Cocher:** Production, Preview, Development
6. **Sauvegarder**
7. **Redéployer:**
   - Onglet "Deployments"
   - Cliquer "..." sur le dernier déploiement
   - Sélectionner "Redeploy"

⏱️ **Attendre 1-2 minutes** que Vercel redéploie

✅ **Frontend mis à jour!**

---

### Étape 8: Tester! (1 minute)

1. **Vider le cache navigateur:**
   ```
   Ctrl + Shift + Delete → Clear cache
   ```

2. **Aller sur:**
   ```
   https://gamezoneismo.vercel.app
   ```

3. **Essayer de se connecter:**
   ```
   Email: admin@gmail.com
   Password: demo123
   ```

4. **Devrait fonctionner!** 🎉

---

## ✅ Vérifications

### Test 1: Backend Santé

Ouvrir dans navigateur:
```
https://backend-production-xxxx.up.railway.app/test.php
```

**Attendu:**
```json
{
  "status": "OK",
  "timestamp": "..."
}
```

### Test 2: CORS Headers

Console navigateur (F12):
```javascript
fetch('https://backend-production-xxxx.up.railway.app/test.php')
  .then(r => r.json())
  .then(d => console.log('✅ CORS Works!', d))
```

**Attendu:** Pas d'erreur CORS!

### Test 3: Login

Dans l'app Vercel, essayer de se connecter.

**Attendu:** Login réussi, redirection vers dashboard!

---

## 🎯 Résumé des URLs

| Service | URL |
|---------|-----|
| **Backend Railway** | https://backend-production-xxxx.up.railway.app |
| **Frontend Vercel** | https://gamezoneismo.vercel.app |
| **GitHub Repo** | https://github.com/Jeho05/gamezone |
| **Branch Backend** | backend-railway |

---

## 💰 Coûts Railway

- **$5 de crédit gratuit/mois**
- **Consommation estimée:**
  - Backend PHP: ~$0.50/mois
  - MySQL: ~$1/mois
  - **Total: ~$1.50/mois** → Gratuit pendant 3-4 mois!

Après épuisement du crédit:
- Ajouter carte de crédit
- OU migrer vers Render.com (totalement gratuit)

---

## 🚨 Problèmes Courants

### Build Failed

**Solution:** Vérifier que:
- Branch = `backend-railway`
- Root Directory = `backend_infinityfree/api`
- Dockerfile existe

### Database Connection Failed

**Solution:**
- Vérifier que MySQL est bien ajouté
- Redéployer le backend après ajout de la DB

### CORS Errors

**Solution:**
- Vérifier que l'URL Vercel est mise à jour
- Vider cache navigateur
- Attendre que Vercel redéploie

---

## 📞 Support

Si vous avez des problèmes:
1. Vérifier les logs Railway (onglet "Deployments" → cliquer sur le build)
2. Vérifier les variables d'environnement
3. Tester l'URL backend directement dans le navigateur

---

**Prêt à déployer? Suivez les étapes ci-dessus!** 🚀

**Temps total estimé: 15-20 minutes**
