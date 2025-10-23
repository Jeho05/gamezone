# 🚀 Guide de Déploiement sur InfinityFree

## 📋 Prérequis

- ✅ Compte GitHub créé
- ✅ Git installé sur votre machine
- ✅ Compte InfinityFree créé (gratuit)

---

## 🎯 Étape 1 : Inscription sur InfinityFree

1. Allez sur [infinityfree.net](https://infinityfree.net)
2. Cliquez sur **"Sign Up"**
3. Créez votre compte gratuit
4. Attendez l'email de confirmation

---

## 🎯 Étape 2 : Créer un Site sur InfinityFree

1. Connectez-vous à votre compte InfinityFree
2. Cliquez sur **"Create Account"**
3. Choisissez un sous-domaine : `votre-nom.infinityfreeapp.com`
4. Notez vos identifiants :
   - **Username** : `epiz_XXXXXXXX`
   - **Password** : (conservez-le précieusement)
   - **FTP Host** : `ftpupload.net`
   - **MySQL Host** : `sqlXXX.infinityfreeapp.com`

---

## 🎯 Étape 3 : Préparer l'Application

### 3.1 Construire le Frontend

Ouvrez PowerShell et exécutez :

```powershell
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

# Copier la config de production
Copy-Item .env.production.example .env.production

# IMPORTANT : Éditer .env.production avec votre domaine
notepad .env.production
```

**Dans .env.production**, remplacez :
```env
NEXT_PUBLIC_API_BASE=https://votre-nom.infinityfreeapp.com/api
```

Ensuite, construire le projet :

```powershell
# Installer les dépendances (si pas déjà fait)
npm install

# Construire pour production
npm run build
```

⏳ **Cela peut prendre 5-10 minutes...**

### 3.2 Configurer le Backend PHP

```powershell
cd "c:\xampp\htdocs\projet ismo\api"

# Copier la config
Copy-Item .env.example .env

# Éditer avec vos infos InfinityFree
notepad .env
```

**Dans api/.env**, remplissez avec vos infos InfinityFree :
```env
DB_HOST=sqlXXX.infinityfreeapp.com
DB_NAME=epiz_XXXXXXXX_gamezone
DB_USER=epiz_XXXXXXXX
DB_PASS=votre_mot_de_passe_mysql
APP_URL=https://votre-nom.infinityfreeapp.com
```

---

## 🎯 Étape 4 : Créer la Base de Données

1. Dans le panneau InfinityFree, allez dans **"MySQL Databases"**
2. Cliquez sur **"Create Database"**
3. Nom : `gamezone` (sera préfixé automatiquement : `epiz_XXXXXXXX_gamezone`)
4. Notez les informations de connexion
5. Cliquez sur **"phpMyAdmin"** pour accéder à la base

### 4.1 Importer la Structure

1. Dans phpMyAdmin, sélectionnez votre base `epiz_XXXXXXXX_gamezone`
2. Cliquez sur **"Import"**
3. Sélectionnez le fichier : `c:\xampp\htdocs\projet ismo\api\database\schema.sql`
4. Cliquez sur **"Go"**

### 4.2 Importer les Données Initiales (optionnel)

Si vous voulez importer vos données de test :

```powershell
# Exporter votre base locale d'abord
cd "c:\xampp\htdocs\projet ismo"
.\backup_database.php
```

Puis importez le fichier généré dans `backups/` via phpMyAdmin.

---

## 🎯 Étape 5 : Uploader les Fichiers via FTP

### Option A : FileZilla (Recommandé)

1. Téléchargez [FileZilla Client](https://filezilla-project.org)
2. Connectez-vous :
   - **Host** : `ftpupload.net`
   - **Username** : `epiz_XXXXXXXX`
   - **Password** : votre mot de passe
   - **Port** : `21`

3. Structure d'upload :

```
/htdocs/                           (racine sur InfinityFree)
├── index.html                     ← Build React
├── assets/                        ← Assets React
├── api/                           ← Backend PHP
│   ├── admin/
│   ├── auth/
│   ├── shop/
│   ├── config.php
│   └── ...
├── uploads/
├── images/
├── .htaccess                      ← Configuration Apache
└── ...
```

### Option B : Gestionnaire de Fichiers InfinityFree

1. Dans le panneau InfinityFree → **"File Manager"**
2. Naviguez vers `/htdocs/`
3. Uploadez les fichiers (plus lent, mais fonctionne)

---

## 🎯 Étape 6 : Configuration .htaccess

Créez un fichier `.htaccess` à la racine `/htdocs/` :

```apache
# Activer le rewriting
RewriteEngine On

# HTTPS redirect (une fois SSL actif)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# API routes - rediriger vers le dossier api/
RewriteRule ^api/(.*)$ api/$1 [L]

# Frontend React Router - rediriger tout vers index.html
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/api/
RewriteRule ^(.*)$ index.html [L]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Prevent access to sensitive files
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## 🎯 Étape 7 : Tester l'Application

1. Ouvrez votre navigateur
2. Allez sur : `https://votre-nom.infinityfreeapp.com`
3. Testez :
   - ✅ Page d'accueil charge
   - ✅ Login fonctionne
   - ✅ API répond (ouvrez F12 → Network)

### En cas de problème :

**Erreur 500** :
- Vérifiez `api/config.php` - connexion DB correcte
- Vérifiez les permissions des dossiers (755 pour dossiers, 644 pour fichiers)

**Erreur "Cannot connect to database"** :
- Vérifiez les infos dans `api/.env`
- Vérifiez que la base est créée sur InfinityFree

**CSS/JS ne charge pas** :
- Vérifiez que le build React est bien uploadé
- Vérifiez le chemin dans `index.html`

---

## 🎯 Étape 8 : Activer SSL (Certificat HTTPS)

1. Dans InfinityFree → **"SSL Certificates"**
2. Activez le SSL gratuit (Let's Encrypt)
3. Attendez 5-10 minutes pour activation
4. Décommentez les lignes HTTPS dans `.htaccess`

---

## 🎯 Étape 9 : Configuration KkiaPay

1. Vérifiez que `NEXT_PUBLIC_KKIAPAY_SANDBOX=0` (mode production)
2. Configurez le webhook KkiaPay :
   - URL : `https://votre-nom.infinityfreeapp.com/api/shop/payment_callback.php`
3. Testez un paiement avec un petit montant

---

## 🎯 Étape 10 : Créer le Repository GitHub

```powershell
cd "c:\xampp\htdocs\projet ismo"

# Initialiser Git
git init

# Ajouter tous les fichiers (sauf ceux dans .gitignore)
git add .

# Premier commit
git commit -m "Initial commit - GameZone Application v1.0"

# Créer le repo sur GitHub.com (dans votre navigateur)
# Puis connecter :
git remote add origin https://github.com/votre-username/gamezone.git
git branch -M main
git push -u origin main
```

---

## ✅ Checklist Finale

- [ ] Frontend build créé et uploadé
- [ ] Backend PHP uploadé avec config correcte
- [ ] Base de données créée et importée
- [ ] .htaccess configuré
- [ ] SSL activé (HTTPS)
- [ ] Tests effectués (login, achats, sessions)
- [ ] KkiaPay configuré et testé
- [ ] Repository GitHub créé
- [ ] Backup de la base de données conservé localement

---

## 🎉 Félicitations !

Votre application est maintenant en ligne sur InfinityFree !

**URL de production** : `https://votre-nom.infinityfreeapp.com`

---

## 📞 Support

**Problèmes courants** :

1. **Upload lent** : InfinityFree gratuit a des limitations de vitesse
2. **Temps de réponse** : Possible latence sur version gratuite
3. **Quotas** : 10 Go bande passante/jour max (largement suffisant pour tests)

**Pour migration future vers hébergement payant** :
- Exportez la base via phpMyAdmin
- Téléchargez tous les fichiers via FTP
- Importez sur le nouvel hébergeur

---

## 🚀 Prochaines Étapes

Une fois stable sur InfinityFree, envisagez :
- **Hostinger** (2-3€/mois) pour meilleures performances
- **Domaine personnalisé** (ex: `gamezone.com`)
- **Monitoring** avec uptimerobot.com (gratuit)
- **Backups automatiques** quotidiens

---

**Créé le** : 2025-01-23
**Version** : 1.0
