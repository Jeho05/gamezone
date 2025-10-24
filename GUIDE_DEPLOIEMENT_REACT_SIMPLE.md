# 🚀 Guide Simple de Déploiement React - GameZone

## 📋 Problèmes Possibles et Solutions

Si vous n'arrivez pas à déployer votre application React, ce guide vous aidera étape par étape.

---

## 🎯 Option 1: Démarrage Local (Le Plus Simple)

### Pour tester votre application en local:

**1. Ouvrez PowerShell dans le dossier du projet**
```powershell
cd "c:\xampp\htdocs\projet ismo"
```

**2. Lancez le script de démarrage automatique**
```powershell
.\START.ps1
```

Ce script va:
- ✅ Vérifier Apache et MySQL
- ✅ Installer la base de données
- ✅ Installer les dépendances npm
- ✅ Démarrer le serveur React sur http://localhost:4000

**3. Ouvrez votre navigateur**
```
http://localhost:4000
```

### Si START.ps1 ne fonctionne pas:

**Méthode manuelle:**

```powershell
# 1. Démarrez XAMPP (Apache + MySQL)
# Ouvrez XAMPP Control Panel et cliquez sur "Start" pour Apache et MySQL

# 2. Allez dans le dossier React
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

# 3. Installez les dépendances (première fois seulement)
npm install

# 4. Démarrez le serveur de développement
npm run dev
```

**5. Ouvrez http://localhost:4000**

---

## 🌐 Option 2: Déploiement sur GitHub Pages (Gratuit)

### Prérequis:
- Compte GitHub
- Git installé sur votre PC

### Étapes:

**1. Créer un repository sur GitHub**
- Allez sur https://github.com
- Cliquez sur "New repository"
- Nom: `gamezone` (ou autre nom)
- Public ou Private: choisissez
- Cliquez "Create repository"

**2. Modifier la configuration de build**

Ouvrez le fichier: `createxyz-project\_\apps\web\package.json`

Changez la ligne `"homepage"`:
```json
"homepage": "https://VOTRE_USERNAME.github.io/gamezone"
```

Remplacez `VOTRE_USERNAME` par votre nom d'utilisateur GitHub.

**3. Déployer sur GitHub Pages**

```powershell
# Allez dans le dossier web
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

# Installez gh-pages (première fois seulement)
npm install gh-pages --save-dev

# Initialisez Git (si pas déjà fait)
git init
git add .
git commit -m "Initial commit"

# Ajoutez votre repository GitHub
git remote add origin https://github.com/VOTRE_USERNAME/gamezone.git

# Déployez sur GitHub Pages
npm run deploy
```

**4. Activez GitHub Pages**
- Allez sur votre repository GitHub
- Settings → Pages
- Source: sélectionnez "gh-pages" branch
- Cliquez Save

**5. Attendez 2-3 minutes et visitez:**
```
https://VOTRE_USERNAME.github.io/gamezone
```

⚠️ **Important**: GitHub Pages héberge uniquement le frontend. Vous devrez héberger votre backend PHP séparément (voir Option 3).

---

## 🌍 Option 3: Déploiement Complet (Frontend + Backend)

Pour déployer l'application complète avec le backend PHP, vous avez plusieurs options:

### A. Sur InfinityFree (Gratuit)

**1. Créez un compte sur InfinityFree**
- Allez sur https://infinityfree.net
- Créez un compte gratuit
- Créez un site web (choisissez un sous-domaine)

**2. Préparez les fichiers pour le déploiement**

```powershell
cd "c:\xampp\htdocs\projet ismo"
.\BUILD_PRODUCTION.ps1
```

Ce script va:
- ✅ Builder l'application React
- ✅ Préparer tous les fichiers backend
- ✅ Créer un dossier `production_build` prêt à déployer

**3. Uploadez les fichiers via FTP**

Téléchargez FileZilla: https://filezilla-project.org/

Connectez-vous avec les infos FTP d'InfinityFree:
- Host: ftp.VOTRE_SITE.infinityfreeapp.com
- Username: (fourni par InfinityFree)
- Password: (fourni par InfinityFree)
- Port: 21

Uploadez tout le contenu de `production_build` vers `/htdocs/`

**4. Créez la base de données**
- Allez dans le Control Panel d'InfinityFree
- MySQL Databases → Create Database
- Notez: nom de la BDD, username, password, hostname

**5. Configurez le backend**

Créez le fichier `api/.env` sur le serveur avec:
```env
DB_HOST=sql123.infinityfreeapp.com
DB_NAME=ifXXXXXXXX_gamezone
DB_USER=ifXXXXXXXX_admin
DB_PASS=votre_mot_de_passe
SESSION_SECRET=un_secret_aleatoire_64_caracteres
```

**6. Importez la base de données**
- Allez dans phpMyAdmin (lien dans InfinityFree)
- Sélectionnez votre base de données
- Cliquez "Import"
- Uploadez `api/database/schema.sql`

**7. Testez votre site**
```
http://VOTRE_SITE.infinityfreeapp.com
```

### B. Sur Vercel (Gratuit - Frontend seulement)

**1. Installez Vercel CLI**
```powershell
npm install -g vercel
```

**2. Déployez**
```powershell
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
vercel
```

Suivez les instructions à l'écran.

⚠️ **Note**: Vercel héberge uniquement le frontend React. Le backend PHP devra être hébergé ailleurs.

### C. Sur Netlify (Gratuit - Frontend seulement)

**1. Allez sur https://netlify.com**

**2. Drag & Drop**
- Buildez d'abord: `npm run build`
- Glissez le dossier `build/client` sur Netlify

**3. Configurez**
- Build command: `npm run build`
- Publish directory: `build/client`

---

## 🔧 Résolution des Problèmes Courants

### Problème: "npm: command not found"
**Solution**: Installez Node.js depuis https://nodejs.org/

### Problème: "Port 4000 déjà utilisé"
**Solution**: 
```powershell
# Tuez le process qui utilise le port
npx kill-port 4000

# Ou changez le port dans vite.config.ts
# server: { port: 4001 }
```

### Problème: "Module not found"
**Solution**:
```powershell
# Supprimez node_modules et package-lock.json
rm -rf node_modules
rm package-lock.json

# Réinstallez
npm install
```

### Problème: Erreur pendant le build
**Solution**:
```powershell
# Nettoyez le cache
npm cache clean --force

# Réinstallez
rm -rf node_modules
npm install

# Rebuild
npm run build
```

### Problème: Page blanche après déploiement
**Solution**: Vérifiez le `base` dans `vite.config.production.ts`
```typescript
base: '/gamezone/', // Doit correspondre à votre chemin
```

### Problème: API ne fonctionne pas en production
**Solution**: Vérifiez que:
1. Le fichier `api/.env` est configuré correctement
2. La base de données est importée
3. Les permissions des dossiers sont correctes (755 pour dossiers, 644 pour fichiers)

---

## 📊 Comparaison des Options

| Option | Coût | Difficulté | Frontend | Backend | Base de Données |
|--------|------|------------|----------|---------|-----------------|
| **Local (XAMPP)** | Gratuit | ⭐ Facile | ✅ | ✅ | ✅ |
| **GitHub Pages** | Gratuit | ⭐⭐ Moyen | ✅ | ❌ | ❌ |
| **InfinityFree** | Gratuit | ⭐⭐⭐ Avancé | ✅ | ✅ | ✅ |
| **Vercel** | Gratuit | ⭐⭐ Moyen | ✅ | ❌ | ❌ |
| **Netlify** | Gratuit | ⭐⭐ Moyen | ✅ | ❌ | ❌ |

---

## 🎯 Recommandation

### Pour développement et tests:
➡️ **Utilisez Option 1 (Local avec XAMPP)**

### Pour montrer à des clients/amis:
➡️ **Utilisez Option 3A (InfinityFree)** - C'est gratuit et complet

### Si vous voulez seulement le frontend:
➡️ **Utilisez GitHub Pages ou Netlify** - Plus simple mais sans backend

---

## 📞 Besoin d'Aide?

1. **Pour le démarrage local**: Lancez `.\START.ps1` et suivez les instructions
2. **Pour les erreurs de build**: Vérifiez la console et cherchez l'erreur exacte
3. **Pour le déploiement**: Consultez les guides détaillés dans les fichiers MD

---

## ✅ Checklist de Déploiement

### Local:
- [ ] XAMPP installé et démarré
- [ ] Node.js installé
- [ ] Dépendances npm installées
- [ ] Base de données créée
- [ ] Application accessible sur localhost:4000

### Production (InfinityFree):
- [ ] Compte créé
- [ ] Fichiers buildés avec BUILD_PRODUCTION.ps1
- [ ] Fichiers uploadés via FTP
- [ ] Base de données créée et importée
- [ ] Fichier .env configuré
- [ ] Site accessible via URL

---

**🎉 Bonne chance avec votre déploiement !**

Si vous rencontrez des problèmes spécifiques, n'hésitez pas à demander de l'aide en décrivant l'erreur exacte que vous obtenez.
