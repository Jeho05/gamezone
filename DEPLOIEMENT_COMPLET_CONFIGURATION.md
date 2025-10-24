# 🚀 Guide de Déploiement Complet - Configuration Actuelle

## 📋 VOS INFORMATIONS ACTUELLES

### ✅ Backend InfinityFree (DÉJÀ CONFIGURÉ)

| Paramètre | Valeur |
|-----------|--------|
| **URL du site** | `http://ismo.gamer.gd` |
| **URL API** | `http://ismo.gamer.gd/api` |
| **FTP Host** | `ftpupload.net` |
| **FTP Username** | `if0_40238088` |
| **FTP Password** | `OTnlRESWse7lVB` |
| **FTP Port** | `21` |

### ✅ Base de Données MySQL (DÉJÀ CONFIGURÉE)

| Paramètre | Valeur |
|-----------|--------|
| **Host** | `sql308.infinityfree.com` |
| **Database** | `if0_40238088_gamezone` |
| **Username** | `if0_40238088` |
| **Password** | `OTnlRESWse7lVB` |
| **Port** | `3306` |

### ✅ GitHub (DÉJÀ CONFIGURÉ)

| Paramètre | Valeur |
|-----------|--------|
| **Username** | `Jeho05` |
| **Repository** | `https://github.com/Jeho05/gamezone` |

### ✅ KkiaPay (DÉJÀ CONFIGURÉ)

| Paramètre | Valeur |
|-----------|--------|
| **Public Key** | `072b361d25546db0aee3d69bf07b15331c51e39f` |
| **Mode** | Production (Sandbox: false) |

---

## 🎯 ÉTAT ACTUEL DU PROJET

### ✅ Ce qui est DÉJÀ fait:
- [x] Backend PHP créé et configuré
- [x] Fichier `.env` créé dans `backend_infinityfree/api/`
- [x] Base de données MySQL configurée sur InfinityFree
- [x] Compte FTP InfinityFree créé
- [x] Domaine `ismo.gamer.gd` configuré
- [x] Structure SQL prête
- [x] Frontend React fonctionnel en local
- [x] Configuration Vercel prête (`vercel.json`)

### ⚠️ Ce qui reste à faire:
- [ ] Uploader le backend via FTP sur InfinityFree
- [ ] Tester l'API backend en ligne
- [ ] Créer le repository GitHub (si pas encore fait)
- [ ] Déployer le frontend sur Vercel/Netlify/GitHub Pages
- [ ] Configurer CORS sur le backend
- [ ] Tester l'application complète

---

## 🚀 DÉPLOIEMENT RAPIDE (3 OPTIONS)

### Option 1: Backend InfinityFree + Frontend Vercel (RECOMMANDÉ)

#### Étape 1: Uploader le Backend sur InfinityFree

```powershell
# Le backend est déjà prêt dans: backend_infinityfree/
```

**Via FileZilla:**
1. Ouvrez FileZilla
2. Connectez-vous:
   - Host: `ftpupload.net`
   - Username: `if0_40238088`
   - Password: `OTnlRESWse7lVB`
   - Port: `21`
3. Navigation:
   - GAUCHE: `C:\xampp\htdocs\projet ismo\backend_infinityfree\`
   - DROITE: `/htdocs/`
4. Uploadez TOUT le contenu de `backend_infinityfree/` vers `/htdocs/`
5. Attendez 5-15 minutes

**Tester le backend:**
```
http://ismo.gamer.gd/api/health.php
```

Résultat attendu:
```json
{
  "status": "healthy",
  "database": "connected",
  "php_version": "7.4"
}
```

#### Étape 2: Déployer le Frontend sur Vercel

```powershell
# 1. Aller dans le dossier React
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

# 2. Installer Vercel CLI
npm install -g vercel

# 3. Déployer
vercel

# Suivez les instructions:
# - Link to existing project? No
# - Project name: gamezone
# - Directory: ./
# - Build command: npm run build
# - Output directory: build/client
# - Development command: npm run dev
```

**Variables d'environnement à ajouter dans Vercel Dashboard:**

1. Allez sur: `https://vercel.com/dashboard`
2. Sélectionnez votre projet
3. Settings → Environment Variables
4. Ajoutez:

| Name | Value |
|------|-------|
| `NEXT_PUBLIC_API_BASE` | `http://ismo.gamer.gd/api` |
| `NEXT_PUBLIC_KKIAPAY_PUBLIC_KEY` | `072b361d25546db0aee3d69bf07b15331c51e39f` |
| `NEXT_PUBLIC_KKIAPAY_SANDBOX` | `0` |
| `NODE_ENV` | `production` |

5. Redéployez: `vercel --prod`

#### Étape 3: Configurer CORS sur le Backend

Une fois que Vercel vous donne une URL (ex: `https://gamezone-xxxx.vercel.app`):

1. Connectez-vous via FTP
2. Éditez le fichier `/htdocs/.htaccess`
3. Trouvez la ligne avec `Access-Control-Allow-Origin`
4. Remplacez `*` par votre URL Vercel:
   ```apache
   Header set Access-Control-Allow-Origin "https://gamezone-xxxx.vercel.app"
   ```
5. Sauvegardez

#### Étape 4: Tester l'Application

Ouvrez votre URL Vercel et testez:
- [ ] Page d'accueil charge
- [ ] Login fonctionne
- [ ] Dashboard s'affiche
- [ ] API fonctionne

---

### Option 2: Backend InfinityFree + Frontend GitHub Pages

#### Étape 1: Uploader le Backend (identique à Option 1)

#### Étape 2: Déployer sur GitHub Pages

```powershell
# 1. Aller dans le dossier React
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

# 2. Créer le repository GitHub (si pas encore fait)
# Allez sur: https://github.com/new
# Nom: gamezone
# Public
# Create repository

# 3. Modifier package.json
# Ouvrez package.json et modifiez la ligne homepage:
"homepage": "https://Jeho05.github.io/gamezone"

# 4. Installer gh-pages
npm install gh-pages --save-dev

# 5. Initialiser Git et déployer
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/Jeho05/gamezone.git
git push -u origin main

# 6. Déployer sur GitHub Pages
npm run deploy
```

#### Étape 3: Activer GitHub Pages

1. Allez sur: `https://github.com/Jeho05/gamezone/settings/pages`
2. Source: Sélectionnez `gh-pages` branch
3. Cliquez Save
4. Attendez 2-3 minutes

Votre site sera sur: `https://Jeho05.github.io/gamezone`

#### Étape 4: Configurer CORS

Éditez `/htdocs/.htaccess` via FTP:
```apache
Header set Access-Control-Allow-Origin "https://Jeho05.github.io"
```

---

### Option 3: Backend InfinityFree + Frontend Netlify

#### Étape 1: Uploader le Backend (identique à Option 1)

#### Étape 2: Déployer sur Netlify

**Via Netlify CLI:**

```powershell
# 1. Aller dans le dossier React
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

# 2. Installer Netlify CLI
npm install -g netlify-cli

# 3. Build
npm run build

# 4. Déployer
netlify deploy --prod

# Choisissez:
# - Create & configure a new site
# - Publish directory: build/client
```

**Variables d'environnement dans Netlify:**

1. Allez sur: `https://app.netlify.com`
2. Site settings → Environment variables
3. Ajoutez les mêmes que pour Vercel

---

## 📝 FICHIERS DE CONFIGURATION DÉJÀ PRÊTS

### ✅ backend_infinityfree/api/.env
```env
DB_HOST=sql308.infinityfree.com
DB_NAME=if0_40238088_gamezone
DB_USER=if0_40238088
DB_PASS=OTnlRESWse7lVB
APP_URL=http://ismo.gamer.gd
KKIAPAY_PUBLIC_KEY=072b361d25546db0aee3d69bf07b15331c51e39f
KKIAPAY_PRIVATE_KEY=votre_cle_privee_si_vous_lavez
KKIAPAY_SANDBOX=false
SESSION_LIFETIME=1440
SESSION_SECURE=false
```

### ✅ createxyz-project/_/apps/web/vercel.json
```json
{
  "buildCommand": "npm run build",
  "outputDirectory": "build/client",
  "installCommand": "npm install --legacy-peer-deps",
  "rewrites": [
    {
      "source": "/api/:path*",
      "destination": "http://ismo.gamer.gd/api/:path*"
    }
  ]
}
```

### ✅ createxyz-project/_/apps/web/package.json
```json
{
  "homepage": "https://Jeho05.github.io/gamezone",
  "scripts": {
    "dev": "react-router dev",
    "build": "vite build --config vite.config.production.ts",
    "deploy": "gh-pages -d build/client"
  }
}
```

---

## 🔧 SCRIPTS DISPONIBLES

### Pour le Déploiement

| Script | Description |
|--------|-------------|
| **`DEPLOYER_REACT.ps1`** | Menu interactif de déploiement |
| **`BUILD_PRODUCTION.ps1`** | Prépare les fichiers pour production |
| **`REPARER_REACT.ps1`** | Résout les problèmes courants |
| **`START.ps1`** | Démarre l'application en local |

### Utilisation Rapide

```powershell
# Déploiement interactif
.\DEPLOYER_REACT.ps1

# Build pour production
.\BUILD_PRODUCTION.ps1

# Réparer les problèmes
.\REPARER_REACT.ps1

# Démarrage local
.\START.ps1
```

---

## 🧪 TESTS À EFFECTUER

### Après Upload Backend

```bash
# Test 1: Health Check
curl http://ismo.gamer.gd/api/health.php

# Test 2: Auth Check
curl http://ismo.gamer.gd/api/auth/check.php

# Test 3: Diagnostic Env
curl http://ismo.gamer.gd/api/diagnostic_env.php
```

### Résultats Attendus

**health.php:**
```json
{
  "status": "healthy",
  "database": "connected"
}
```

**auth/check.php:**
```json
{
  "authenticated": false
}
```

**diagnostic_env.php:**
```json
{
  "env_file_exists": true,
  "env_values": {
    "DB_HOST": "sql308.infinityfree.com",
    "DB_NAME": "if0_40238088_gamezone",
    "DB_USER": "if0_40238088"
  }
}
```

---

## 📊 RÉCAPITULATIF DES URLS FINALES

### Après Déploiement Complet

| Service | URL |
|---------|-----|
| **Backend API** | `http://ismo.gamer.gd/api` |
| **Frontend (Vercel)** | `https://gamezone-xxxx.vercel.app` |
| **Frontend (GitHub Pages)** | `https://Jeho05.github.io/gamezone` |
| **Frontend (Netlify)** | `https://gamezone-xxxx.netlify.app` |
| **phpMyAdmin** | InfinityFree Control Panel |
| **FTP** | `ftpupload.net` |

---

## ⚡ DÉMARRAGE RAPIDE (CHOIX SIMPLE)

### Je veux déployer MAINTENANT avec Vercel (Le plus simple)

```powershell
# 1. Upload backend
# Ouvrez FileZilla et uploadez backend_infinityfree/ vers /htdocs/

# 2. Deploy frontend
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
npm install -g vercel
vercel

# 3. Attendez l'URL Vercel, puis configurez CORS
# Éditez /htdocs/.htaccess via FTP avec votre URL Vercel
```

### Je veux tester en local d'abord

```powershell
.\START.ps1
# Ouvrez http://localhost:4000
```

---

## 🆘 EN CAS DE PROBLÈME

### Backend ne répond pas

```bash
# Vérifiez que les fichiers sont uploadés
# Testez: http://ismo.gamer.gd/api/health.php
# Si erreur 404: vérifiez que le dossier api/ est dans /htdocs/
```

### Frontend ne se connecte pas à l'API

```bash
# 1. Vérifiez CORS dans .htaccess
# 2. Vérifiez les variables d'environnement
# 3. Testez l'API directement dans le navigateur
```

### Erreur "Module not found"

```powershell
.\REPARER_REACT.ps1
# Choisissez option 5 (nettoyage complet)
```

---

## 📞 DOCUMENTATION COMPLÈTE

- **VOS_URLS_COMPLETES.txt** - Toutes vos informations
- **CHECK_UPLOAD_STATUS.md** - Guide d'upload détaillé
- **BACKEND_PRET_POUR_UPLOAD.txt** - Checklist backend
- **GUIDE_DEPLOIEMENT_REACT_SIMPLE.md** - Guide simplifié
- **DEPLOIEMENT_INFINITYFREE.md** - Guide InfinityFree détaillé

---

## ✅ CHECKLIST FINALE

### Backend InfinityFree
- [ ] Connecté à FileZilla
- [ ] Uploadé `backend_infinityfree/` vers `/htdocs/`
- [ ] Testé `http://ismo.gamer.gd/api/health.php`
- [ ] Vérifié `.env` sur le serveur
- [ ] Base de données accessible

### Frontend (Vercel)
- [ ] Installé Vercel CLI
- [ ] Déployé avec `vercel`
- [ ] Variables d'environnement configurées
- [ ] CORS configuré avec URL Vercel
- [ ] Application testée et fonctionnelle

### Frontend (GitHub Pages)
- [ ] Repository créé sur GitHub
- [ ] `package.json` modifié avec homepage
- [ ] Déployé avec `npm run deploy`
- [ ] GitHub Pages activé
- [ ] CORS configuré

### Tests Finaux
- [ ] Login fonctionne
- [ ] Dashboard s'affiche
- [ ] Points visibles
- [ ] Shop fonctionne
- [ ] Admin panel accessible

---

**🎉 Votre application GameZone est prête à être déployée !**

**Prochaine étape recommandée:**
```powershell
.\DEPLOYER_REACT.ps1
```

Choisissez l'option 1 pour démarrer en local, ou l'option 3 pour déployer sur GitHub Pages.
