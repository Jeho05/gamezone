# 🚀 Migration Backend vers Railway.app

## ✅ Pourquoi Railway?

- ✅ **Gratuit** ($5 crédit/mois = plusieurs mois gratuits)
- ✅ **CORS fonctionne parfaitement**
- ✅ **Support PHP + MySQL**
- ✅ **Déploiement automatique via Git**
- ✅ **HTTPS gratuit**
- ✅ **Plus rapide qu'InfinityFree**

---

## 📋 Étape 1: Créer Compte Railway

1. **Aller sur:** https://railway.app
2. **Cliquer:** "Start a New Project"
3. **Se connecter avec GitHub** (recommandé)
4. **Autoriser Railway** à accéder à vos repos

✅ **Compte créé!**

---

## 📦 Étape 2: Préparer le Backend pour Railway

### A. Créer Dockerfile

Railway a besoin d'un Dockerfile pour PHP. Je vais le créer.

### B. Créer railway.json

Configuration de déploiement Railway.

### C. Adapter config.php

Utiliser les variables d'environnement Railway.

---

## 🔧 Étape 3: Configuration Railway

### Créer Nouveau Projet

1. Dashboard Railway → **"New Project"**
2. Sélectionner **"Deploy from GitHub repo"**
3. Choisir repo: `Jeho05/gamezone`
4. Root Directory: `backend_infinityfree/api`

### Ajouter Base de Données

1. Dans le projet → **"New"** → **"Database"**
2. Sélectionner **"Add MySQL"**
3. Railway crée automatiquement la DB avec credentials

### Variables d'Environnement

Railway auto-génère:
- `MYSQLHOST`
- `MYSQLDATABASE`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQLPORT`

Ajouter manuellement:
```
SESSION_SAMESITE=None
SESSION_SECURE=1
APP_ENV=production
```

---

## 🌐 Étape 4: Mettre à Jour Frontend

### Obtenir l'URL Railway

Après déploiement, Railway donne une URL:
```
https://xxx.up.railway.app
```

### Mettre à Jour Vercel

Dans Vercel Dashboard:
1. Settings → Environment Variables
2. Modifier `NEXT_PUBLIC_API_BASE`
3. Nouvelle valeur: `https://xxx.up.railway.app`
4. Redéployer

---

## ✅ Étape 5: Tester

1. **Backend:** https://xxx.up.railway.app/test.php
2. **Frontend:** https://gamezoneismo.vercel.app
3. **Login:** Devrait fonctionner! 🎉

---

## 📝 Checklist

- [ ] Compte Railway créé
- [ ] Dockerfile créé
- [ ] Config.php adapté
- [ ] Projet Railway créé
- [ ] MySQL ajouté
- [ ] Variables d'env configurées
- [ ] Backend déployé
- [ ] URL obtenue
- [ ] Frontend mis à jour
- [ ] Testé et fonctionnel

---

**Prêt? Commençons!** 🚀
