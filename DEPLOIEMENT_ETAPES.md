# 🚀 Guide de Déploiement GameZone - Version Simplifiée

## ✅ Ce qui a été préparé pour vous

J'ai créé tous les fichiers nécessaires pour déployer votre application sur InfinityFree :

### 📁 Fichiers créés

1. **`.gitignore`** - Empêche de versionner les fichiers sensibles
2. **`BUILD_PRODUCTION.ps1`** - Script automatique de build
3. **`INIT_GITHUB.ps1`** - Script pour GitHub
4. **`DEPLOIEMENT_INFINITYFREE.md`** - Guide détaillé complet
5. **`.env.production.example`** - Configuration production frontend
6. **`api/.env.example`** - Configuration production backend

---

## 🎯 Étapes à Suivre (dans l'ordre)

### 📍 **ÉTAPE 1 : Créer votre compte InfinityFree**

1. Allez sur [infinityfree.net](https://infinityfree.net)
2. Cliquez sur "Sign Up" (inscription gratuite)
3. Confirmez votre email
4. Connectez-vous

**⏱️ Temps estimé : 5 minutes**

---

### 📍 **ÉTAPE 2 : Créer un site web sur InfinityFree**

1. Dans votre panneau InfinityFree, cliquez sur **"Create Account"**
2. Choisissez un sous-domaine (ex: `gamezone-ismo.infinityfreeapp.com`)
3. Notez précieusement vos identifiants :
   ```
   📝 Username FTP : epiz_XXXXXXXX
   📝 Password : ************
   📝 FTP Host : ftpupload.net
   📝 MySQL Host : sqlXXX.infinityfreeapp.com
   📝 Votre domaine : https://votre-nom.infinityfreeapp.com
   ```

**⏱️ Temps estimé : 3 minutes**

---

### 📍 **ÉTAPE 3 : Construire l'application**

Ouvrez PowerShell et exécutez :

```powershell
cd "c:\xampp\htdocs\projet ismo"
.\BUILD_PRODUCTION.ps1
```

Ce script va :
- ✅ Vérifier votre configuration
- ✅ Compiler le frontend React
- ✅ Préparer tous les fichiers
- ✅ Créer un dossier `production_build` prêt à uploader

**⏱️ Temps estimé : 5-10 minutes** (compilation React)

**❗ IMPORTANT** : Le script vous demandera de configurer `.env.production`
- Remplacez `votre-domaine.infinityfreeapp.com` par votre vrai domaine

---

### 📍 **ÉTAPE 4 : Créer la base de données MySQL**

1. Dans InfinityFree → **"MySQL Databases"**
2. Cliquez sur **"Create Database"**
3. Nom : `gamezone` (sera : `epiz_XXXXXXXX_gamezone`)
4. Notez les infos de connexion
5. Cliquez sur **"phpMyAdmin"**

#### Importer la structure de base de données :

1. Dans phpMyAdmin, sélectionnez `epiz_XXXXXXXX_gamezone`
2. Onglet **"Import"**
3. Choisir le fichier : `c:\xampp\htdocs\projet ismo\api\database\schema.sql`
4. Cliquez **"Go"**

**⏱️ Temps estimé : 5 minutes**

---

### 📍 **ÉTAPE 5 : Configurer le backend (.env)**

1. Dans le dossier `production_build`, allez dans `api/`
2. Créez un fichier `.env` (copiez `.env.example`)
3. Remplissez avec vos infos InfinityFree :

```env
DB_HOST=sqlXXX.infinityfreeapp.com
DB_NAME=epiz_XXXXXXXX_gamezone
DB_USER=epiz_XXXXXXXX
DB_PASS=votre_mot_de_passe_mysql
APP_URL=https://votre-nom.infinityfreeapp.com
```

**⏱️ Temps estimé : 2 minutes**

---

### 📍 **ÉTAPE 6 : Uploader les fichiers via FTP**

#### Option recommandée : FileZilla

1. Téléchargez [FileZilla](https://filezilla-project.org)
2. Installez et lancez FileZilla
3. Connectez-vous :
   - **Hôte** : `ftpupload.net`
   - **Utilisateur** : `epiz_XXXXXXXX`
   - **Mot de passe** : (celui d'InfinityFree)
   - **Port** : `21`

4. Sur InfinityFree (panneau de droite), naviguez vers `/htdocs/`
5. Sur votre PC (panneau de gauche), ouvrez `production_build`
6. **Glissez-déposez** tout le contenu de `production_build` vers `/htdocs/`

**⏱️ Temps estimé : 15-30 minutes** (selon connexion)

#### Structure finale sur InfinityFree :

```
/htdocs/
├── index.html          ← Page React
├── assets/             ← CSS, JS, images du build
├── api/                ← Backend PHP
│   ├── .env           ← Config DB
│   ├── config.php
│   ├── admin/
│   ├── auth/
│   └── ...
├── uploads/
├── images/
└── .htaccess          ← Configuration Apache
```

---

### 📍 **ÉTAPE 7 : Activer SSL (HTTPS)**

1. Dans InfinityFree → **"SSL Certificates"**
2. Activez le certificat gratuit (Let's Encrypt)
3. Attendez 5-10 minutes
4. Votre site sera accessible en HTTPS

**⏱️ Temps estimé : 10 minutes** (activation automatique)

---

### 📍 **ÉTAPE 8 : Tester l'application**

1. Ouvrez votre navigateur
2. Allez sur : `https://votre-nom.infinityfreeapp.com`
3. **Tests à effectuer** :
   - ✅ Page d'accueil charge correctement
   - ✅ Inscription fonctionne
   - ✅ Login admin : `admin@gamezone.fr` / `demo123`
   - ✅ Dashboard joueur accessible
   - ✅ Les images s'affichent

#### En cas de problème :

**Page blanche ou erreur 500** :
- Vérifiez `api/.env` (infos DB correctes ?)
- Vérifiez phpMyAdmin (tables créées ?)

**API ne répond pas** :
- Ouvrez F12 → Console → Network
- Vérifiez les erreurs
- URL API correcte dans `.env.production` ?

**CSS/Images ne chargent pas** :
- Vérifiez que `/htdocs/assets/` existe
- Videz le cache du navigateur (Ctrl+Shift+R)

---

### 📍 **ÉTAPE 9 : Push sur GitHub**

Pour versionner votre code :

```powershell
cd "c:\xampp\htdocs\projet ismo"
.\INIT_GITHUB.ps1
```

Ce script va :
1. Initialiser Git
2. Faire le premier commit
3. Vous demander l'URL de votre repo GitHub
4. Pousser le code

**Avant d'exécuter**, créez le repo sur GitHub.com :
- Allez sur [github.com/new](https://github.com/new)
- Nom : `gamezone`
- Description : `Plateforme de gestion de cyber café`
- Public ou Private (votre choix)
- **NE PAS** cocher "Initialize with README"
- Créer le repository

**⏱️ Temps estimé : 5 minutes**

---

### 📍 **ÉTAPE 10 : Configuration KkiaPay (Paiements)**

1. Vérifiez que votre clé KkiaPay est en mode LIVE (pas sandbox)
2. Configurez le webhook sur le dashboard KkiaPay :
   ```
   URL : https://votre-nom.infinityfreeapp.com/api/shop/payment_callback.php
   ```
3. Testez avec un petit paiement (100 FCFA par exemple)

**⏱️ Temps estimé : 5 minutes**

---

## ✅ Checklist Finale

Avant de considérer le déploiement terminé :

- [ ] Compte InfinityFree créé
- [ ] Site web créé sur InfinityFree
- [ ] Build production généré (`production_build/` existe)
- [ ] Base de données créée et importée
- [ ] Fichier `api/.env` configuré
- [ ] Tous les fichiers uploadés via FTP
- [ ] SSL activé (HTTPS)
- [ ] Site accessible et fonctionnel
- [ ] Login admin fonctionne
- [ ] Inscription joueur fonctionne
- [ ] Repository GitHub créé et code pushé
- [ ] KkiaPay configuré (si paiements utilisés)

---

## 📊 Temps Total Estimé

| Étape | Durée |
|-------|-------|
| Inscription InfinityFree | 5 min |
| Création du site | 3 min |
| Build production | 10 min |
| Config base de données | 5 min |
| Config backend | 2 min |
| Upload FTP | 30 min |
| Activation SSL | 10 min |
| Tests | 10 min |
| GitHub | 5 min |
| KkiaPay | 5 min |
| **TOTAL** | **~85 minutes** |

---

## 🎉 Une fois terminé

Votre application sera accessible à :
- 🌐 **Frontend** : `https://votre-nom.infinityfreeapp.com`
- 🔧 **Admin** : `https://votre-nom.infinityfreeapp.com/admin`
- 📱 **Joueur** : `https://votre-nom.infinityfreeapp.com/player`

---

## 📞 Besoin d'aide ?

1. Consultez **DEPLOIEMENT_INFINITYFREE.md** (guide détaillé)
2. Vérifiez les logs d'erreur :
   - InfinityFree → File Manager → `logs/`
   - Navigateur → F12 → Console
3. Forum InfinityFree : [forum.infinityfree.com](https://forum.infinityfree.com)

---

## 🚀 Prochaines Étapes (Optionnel)

Une fois votre app stable sur InfinityFree :

1. **Acheter un domaine personnalisé** (ex: `gamezone.com`)
   - Namecheap, OVH, Google Domains (~10€/an)
   - Le connecter à InfinityFree (gratuit)

2. **Migrer vers Hostinger** (2-3€/mois)
   - Meilleures performances
   - Support 24/7
   - Backup automatiques

3. **Monitoring** avec [UptimeRobot](https://uptimerobot.com)
   - Gratuit
   - Notifications si site down

4. **Analytics** avec [Google Analytics](https://analytics.google.com)
   - Gratuit
   - Statistiques de visites

---

**Créé le** : 2025-01-23  
**Pour** : Déploiement GameZone v1.0 sur InfinityFree  
**Bon déploiement ! 🚀**
