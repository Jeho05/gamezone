# 🎯 Guide Ultra-Détaillé - Pas à Pas

**Pour ceux qui veulent des instructions TRÈS précises, clic par clic.**

---

## 🔴 PARTIE 1 : INFINITYFREE - BACKEND (45 minutes)

### ➡️ Étape 1. **Créer compte InfinityFree et site web**
2. **Créer base MySQL et importez api/schema.sql**
3. **Editez api/.env.example -> api/.env avec vos infos DB**
4. **Uploadez backend_infinityfree/* vers /htdocs/ via FTP**
5. **Modifiez .htaccess avec votre URL Vercel**

**📍 Sur la page d'accueil InfinityFree :**
1. Cherchez le bouton **"SIGN UP"** (en haut à droite)
2. Cliquez dessus

**📍 Sur la page d'inscription :**
1. **Email Address** → Tapez votre email (ex: `votre.email@gmail.com`)
2. **Password** → Créez un mot de passe fort
3. Cochez la case **"I agree to the Terms of Service"**
4. Cliquez sur **"SIGN UP"**

**📍 Vérification email :**
1. Ouvrez votre boîte email
2. Cherchez un email de **"InfinityFree"**
3. Ouvrez l'email
4. Cliquez sur le lien de confirmation
5. ✅ Votre compte est activé !

---

### ➡️ Étape 1.2 : Créer votre site web

**📍 Après connexion, vous êtes sur le "Client Area" :**

1. Cherchez le bouton vert **"Create Account"** (gros bouton au centre)
2. Cliquez dessus

**📍 Page "Create Account" :**

**Champ 1 : Choose a Domain**
- Vous avez 3 options, choisissez l'option 1 : **"Use a subdomain"**
- Dans le champ texte, tapez : `gamezone-api` (ou autre nom)
- Dans le menu déroulant à côté, choisissez : `.infinityfreeapp.com`
- Résultat : `gamezone-api.infinityfreeapp.com`

**Champ 2 : Account Label**
- Tapez : `GameZone API` (c'est juste un nom pour vous)

**Puis :**
- Cochez **"I have read and agree to the Terms of Service"**
- Cliquez sur le bouton vert **"CREATE ACCOUNT"**

**⏳ Attendez 1-2 minutes** (création du compte)

**✅ Vous verrez :**
- Message de confirmation
- Vous êtes redirigé vers votre "Client Area"

---

### ➡️ Étape 1.3 : Récupérer vos identifiants FTP et MySQL

**📍 Dans le "Client Area" :**

1. Vous voyez votre site : `gamezone-api.infinityfreeapp.com`
2. Cliquez dessus (sur le nom du site)

**📍 Vous êtes maintenant sur le panneau de contrôle (Control Panel) :**

**🔑 RÉCUPÉRER LES IDENTIFIANTS FTP :**

1. Dans le menu de gauche, cherchez **"FTP Accounts"**
2. Cliquez dessus
3. Vous voyez une section **"FTP Details"**

**📋 NOTEZ CES INFORMATIONS (très important !) :**

```
FTP Hostname : ftpupload.net
FTP Username : epiz_XXXXXXXX  (notez exactement ce qui est affiché)
FTP Password : (cliquez sur "View" pour voir le mot de passe, notez-le)
FTP Port     : 21
```

**💾 SAUVEGARDEZ ces infos dans un fichier texte !**

---

**🔑 RÉCUPÉRER LES IDENTIFIANTS MYSQL :**

1. Dans le menu de gauche, cherchez **"MySQL Databases"**
2. Cliquez dessus

**📍 Page MySQL Databases :**

**Section 1 : "Create Database"**
1. Dans le champ **"New Database Name"**, tapez : `gamezone`
2. Cliquez sur **"Create Database"**
3. ⏳ Attendez quelques secondes

**Section 2 : "MySQL Databases"** (en bas)
Vous voyez maintenant votre base de données créée :

**📋 NOTEZ CES INFORMATIONS (TRÈS IMPORTANT !) :**

```
MySQL Hostname : sql308.infinityfree.com   (ex: sql203.infinityfreeapp.com)
MySQL Database : if0_40238088_gamezone      (nom complet de votre base)
MySQL Username : if0_40238088                (même que FTP username)
MySQL Password : OTnlRESWse7lVB (même que FTP password)
MySQL Port     : 3306
```

**💾 SAUVEGARDEZ ces infos dans le même fichier texte !**

---

### ➡️ Étape 1.4 : Importer la structure de la base de données

**📍 Toujours sur la page MySQL Databases :**

1. Cherchez un bouton ou lien **"phpMyAdmin"**
2. Cliquez dessus
3. ➡️ Une nouvelle page s'ouvre (phpMyAdmin)

**📍 Dans phpMyAdmin :**

**Si on vous demande de vous connecter :**
- Username : `epiz_XXXXXXXX` (votre MySQL username)
- Password : (votre MySQL password)
- Cliquez **"Go"**

**📍 Une fois connecté :**

1. À gauche, vous voyez une liste de bases de données
2. Cherchez `epiz_XXXXXXXX_gamezone` (votre base)
3. Cliquez dessus (la ligne devient bleue)

4. En haut, vous voyez plusieurs onglets : **General | Structure | SQL | Search | Query | Export | Import | ...**
5. Cliquez sur l'onglet **"Import"**

**📍 Page Import :**

1. Section **"File to import"**
2. Cliquez sur le bouton **"Choose File"** (ou "Browse")
3. ➡️ Une fenêtre Windows s'ouvre

**📍 Dans la fenêtre Windows :**

**⚠️ CHEMIN EXACT DE LA BASE DE DONNÉES (INFINITYFREE) :**

```
C:\xampp\htdocs\projet ismo\schema_infinityfree.sql
```

**⚠️ IMPORTANT : Utilisez `schema_infinityfree.sql` (PAS `schema.sql`) !**

**Comment naviguer :**
1. Disque : Cliquez sur **"Ce PC"** ou **"Ordinateur"**
2. Double-cliquez sur **"Disque local (C:)"**
3. Double-cliquez sur **"xampp"**
4. Double-cliquez sur **"htdocs"**
5. Double-cliquez sur **"projet ismo"**
6. Vous voyez le fichier **"schema_infinityfree.sql"**
7. Cliquez dessus (1 clic)
8. Cliquez sur le bouton **"Ouvrir"** (en bas)

**📍 De retour sur phpMyAdmin :**

1. Le nom du fichier apparaît : `schema_infinityfree.sql`
2. Descendez en bas de la page
3. Cliquez sur le bouton **"Import"** (ou "Go")
4. ⏳ Attendez 5-30 secondes

**✅ Vous verrez :**
- Message vert : **"Import has been successfully finished"**
- Liste des tables créées (users, points_transactions, rewards, events, etc.)

**❌ Si erreur "#1044 - Accès refusé" :**
→ Lisez le fichier : `SOLUTION_ERREUR_IMPORT.md`

**🎉 Base de données créée avec succès !**

---

### ➡️ Étape 1.5 : Créer le fichier .env sur votre PC

**📍 Sur votre ordinateur :**

1. Ouvrez l'Explorateur Windows (icône dossier jaune)
2. Naviguez vers : `C:\xampp\htdocs\projet ismo\backend_infinityfree\api`

**📍 Dans ce dossier :**

1. Vous voyez un fichier **".env.example"**
2. Faites **Clic droit** dessus
3. Choisissez **"Ouvrir avec" → "Bloc-notes"** (ou votre éditeur)

**📍 Dans le fichier qui s'ouvre :**

Vous voyez ce template :

```
DB_HOST=sqlXXX.infinityfreeapp.com
DB_NAME=epiz_XXXXXXXX_gamezone
DB_USER=epiz_XXXXXXXX
DB_PASS=votre_mot_de_passe_mysql
APP_URL=https://gamezone-api.infinityfreeapp.com
KKIAPAY_PUBLIC_KEY=072b361d25546db0aee3d69bf07b15331c51e39f
KKIAPAY_PRIVATE_KEY=votre_cle_privee
KKIAPAY_SANDBOX=false
SESSION_LIFETIME=1440
SESSION_SECURE=true
```

**📍 REMPLACEZ les valeurs avec VOS informations notées :**

**Exemple concret :**

```
DB_HOST=sql203.infinityfreeapp.com
DB_NAME=epiz_12345678_gamezone
DB_USER=epiz_12345678
DB_PASS=MotDePasseQuiEtaitDansVosNotes
APP_URL=https://gamezone-api.infinityfreeapp.com
KKIAPAY_PUBLIC_KEY=072b361d25546db0aee3d69bf07b15331c51e39f
KKIAPAY_PRIVATE_KEY=votre_cle_privee_kkiapay
KKIAPAY_SANDBOX=false
SESSION_LIFETIME=1440
SESSION_SECURE=true
```

**📍 Sauvegarder :**

1. Menu **"Fichier" → "Enregistrer sous..."**
2. **IMPORTANT :** Dans "Nom du fichier", tapez : `.env` (avec le point au début)
3. Dans "Type", choisissez **"Tous les fichiers (*.*)"**
4. Enregistrez dans le MÊME dossier : `C:\xampp\htdocs\projet ismo\backend_infinityfree\api`
5. Cliquez **"Enregistrer"**

**✅ Fichier .env créé !**

---

### ➡️ Étape 1.6 : Télécharger et installer FileZilla

**📍 Téléchargement :**

1. Ouvrez votre navigateur
2. Allez sur : `filezilla-project.org`
3. Cliquez sur **"Download FileZilla Client"** (gros bouton vert)
4. Choisissez votre version Windows
5. Téléchargez le fichier `.exe`
6. Double-cliquez sur le fichier téléchargé
7. Suivez l'installation (Suivant → Suivant → Installer)

**✅ FileZilla installé !**

---

### ➡️ Étape 1.7 : Uploader le backend sur InfinityFree

**📍 Ouvrez FileZilla :**

**Section du haut (Connexion rapide) :**

1. **Hôte :** Tapez `ftpupload.net`
2. **Identifiant :** Tapez votre `epiz_XXXXXXXX` (celui noté)
3. **Mot de passe :** Tapez votre mot de passe FTP (celui noté)
4. **Port :** Tapez `21`
5. Cliquez sur **"Connexion rapide"**

**⏳ Connexion en cours...**

**✅ Connecté ! Vous voyez :**
- À gauche : **"Site local"** (votre PC)
- À droite : **"Site distant"** (InfinityFree)

**📍 Panneau de GAUCHE (votre PC) :**

1. Naviguez vers : `C:\xampp\htdocs\projet ismo\backend_infinityfree`
2. Vous voyez les dossiers : `api`, `uploads`, `images`, et le fichier `.htaccess`

**📍 Panneau de DROITE (InfinityFree) :**

1. Vous voyez un dossier **"htdocs"**
2. **Double-cliquez** sur `htdocs` pour entrer dedans
3. ⚠️ **IMPORTANT :** Vous devez être DANS le dossier htdocs (pas au niveau parent)

**📍 Upload :**

**À GAUCHE (votre PC) :**
1. Sélectionnez TOUT le contenu de `backend_infinityfree` :
   - Dossier `api`
   - Dossier `uploads`
   - Dossier `images`
   - Fichier `.htaccess`

**Comment sélectionner tout :**
- Cliquez sur le premier item
- Maintenez **Ctrl + A** (sélectionner tout)

2. **Clic droit** sur la sélection
3. Choisissez **"Upload"** (ou "Envoyer")

**⏳ Upload en cours... (5-15 minutes selon votre connexion)**

**Vous voyez en bas :**
- Liste des fichiers en cours d'upload
- Progression

**✅ Upload terminé quand :**
- La liste en bas est vide
- Message : "File transfer successful"

**📍 Vérification :**

À DROITE (InfinityFree), dans `/htdocs/`, vous devez voir :
- Dossier `api`
- Dossier `uploads`
- Dossier `images`
- Fichier `.htaccess`

**✅ Backend uploadé avec succès !**

---

### ➡️ Étape 1.8 : Activer SSL (HTTPS)

**📍 Retournez sur InfinityFree Client Area :**

1. Dans votre navigateur, onglet InfinityFree
2. Menu de gauche : **"SSL Certificates"**
3. Cliquez dessus

**📍 Page SSL Certificates :**

1. Section **"SSL Certificate for yourdomain.infinityfreeapp.com"**
2. Vous voyez : **"Let's Encrypt SSL"** (gratuit)
3. Cliquez sur **"Install Let's Encrypt Certificate"**
4. ⏳ Attendez 5-10 minutes (activation SSL)

**📍 Vérification :**

1. Rafraîchissez la page après 5-10 min
2. Statut : **"Active"** ou **"Installed"**

**✅ SSL activé !**

---

### ➡️ Étape 1.9 : Tester votre API

**📍 Dans votre navigateur :**

1. Tapez dans la barre d'adresse :
   ```
   https://gamezone-api.infinityfreeapp.com/api/auth/check.php
   ```
   (Remplacez `gamezone-api` par votre nom de domaine)

2. Appuyez sur Entrée

**✅ Si ça marche, vous verrez du JSON :**
```json
{
  "authenticated": false,
  "message": "No active session"
}
```

**❌ Si erreur :**
- Vérifiez que `.env` est bien dans `/htdocs/api/`
- Vérifiez les infos de connexion DB dans `.env`
- Vérifiez que la base est bien importée

---

## 🎉 PARTIE 1 TERMINÉE !

**Backend InfinityFree : ✅ EN LIGNE**

**Notez votre URL API finale :**
```
https://gamezone-api.infinityfreeapp.com/api
```

Vous en aurez besoin pour la Partie 2 (Vercel).

---

## 🔵 PARTIE 2 : GITHUB + VERCEL - FRONTEND (30 minutes)

### ➡️ Étape 2.1 : Créer un compte GitHub

**📍 Si vous n'avez PAS de compte GitHub :**

1. Allez sur : `github.com`
2. Cliquez sur **"Sign up"** (en haut à droite)
3. **Email :** Votre email
4. **Password :** Créez un mot de passe
5. **Username :** Choisissez un nom d'utilisateur (ex: `monnom123`)
6. Suivez les étapes de vérification
7. ✅ Compte créé !

**📍 Si vous AVEZ déjà un compte GitHub :**

1. Allez sur : `github.com`
2. Cliquez sur **"Sign in"**
3. Entrez email + mot de passe
4. ✅ Connecté !

---

### ➡️ Étape 2.2 : Créer un nouveau repository

**📍 Sur GitHub, une fois connecté :**

1. En haut à droite, cliquez sur l'icône **"+"**
2. Dans le menu, choisissez **"New repository"**

**📍 Page "Create a new repository" :**

**Remplissez :**

1. **Repository name :** Tapez `gamezone`
2. **Description :** (optionnel) Tapez : `Application de gestion de cyber café`
3. **Public / Private :** Choisissez (Public = tout le monde peut voir, Private = seulement vous)
4. **⚠️ IMPORTANT :** **NE COCHEZ PAS** "Initialize this repository with a README"
5. Ne cochez rien d'autre

6. Cliquez sur le bouton vert **"Create repository"**

**✅ Repository créé !**

**📍 Vous voyez une page avec des instructions.**

**📋 NOTEZ L'URL de votre repository :**

Elle ressemble à :
```
https://github.com/VOTRE-USERNAME/gamezone.git
```

Exemple :
```
https://github.com/monnom123/gamezone.git
```

**💾 Notez cette URL, vous en aurez besoin !**

---

### ➡️ Étape 2.3 : Pousser votre code sur GitHub

**📍 Sur votre PC, ouvrez PowerShell :**

1. Appuyez sur **Windows + X**
2. Choisissez **"Windows PowerShell"** ou **"Terminal"**

**📍 Dans PowerShell, tapez ces commandes UNE PAR UNE :**

**Commande 1 : Aller dans le dossier du projet**
```powershell
cd "C:\xampp\htdocs\projet ismo"
```
Appuyez sur Entrée

**Commande 2 : Ajouter le remote GitHub**

⚠️ **REMPLACEZ `VOTRE-USERNAME` par votre vrai username GitHub !**

```powershell
git remote add origin https://github.com/VOTRE-USERNAME/gamezone.git
```

Exemple :
```powershell
git remote add origin https://github.com/monnom123/gamezone.git
```
Appuyez sur Entrée

**Commande 3 : Renommer la branche en main**
```powershell
git branch -M main
```
Appuyez sur Entrée

**Commande 4 : Pousser le code**
```powershell
git push -u origin main
```
Appuyez sur Entrée

**📍 Authentification GitHub :**

**Si on vous demande de vous connecter :**
- Une fenêtre s'ouvre
- Cliquez sur **"Sign in with your browser"**
- Connectez-vous à GitHub dans le navigateur
- Autorisez l'accès
- Revenez dans PowerShell

**⏳ Upload en cours... (2-5 minutes)**

**✅ Terminé quand vous voyez :**
```
Writing objects: 100% ...
Branch 'main' set up to track remote branch 'main' from 'origin'.
```

**📍 Vérification :**

1. Retournez sur GitHub dans votre navigateur
2. Rafraîchissez la page de votre repository
3. Vous voyez maintenant tous vos fichiers !

**✅ Code sur GitHub !**

---

### ➡️ Étape 2.4 : Créer un compte Vercel

**📍 Créer le compte :**

1. Allez sur : `vercel.com`
2. Cliquez sur **"Sign Up"** (en haut à droite)
3. **⭐ IMPORTANT :** Choisissez **"Continue with GitHub"** (recommandé)
4. Une fenêtre GitHub s'ouvre
5. Cliquez sur **"Authorize Vercel"**
6. Suivez les étapes

**✅ Compte Vercel créé et lié à GitHub !**

---

### ➡️ Étape 2.5 : Importer votre projet dans Vercel

**📍 Sur le dashboard Vercel :**

1. Vous voyez un bouton **"Add New..."** (en haut à droite)
2. Cliquez dessus
3. Dans le menu, choisissez **"Project"**

**📍 Page "Import Git Repository" :**

1. Section **"Import Git Repository"**
2. Vous voyez votre repository **"gamezone"** dans la liste
3. À côté, cliquez sur le bouton **"Import"**

**📍 Page "Configure Project" :**

**⚠️ CONFIGURATION TRÈS IMPORTANTE :**

**Section 1 : Project Name**
- Laissez : `gamezone` (ou changez si vous voulez)

**Section 2 : Framework Preset**
- Cliquez sur le menu déroulant
- Sélectionnez : **"Vite"**

**Section 3 : Root Directory**
- Par défaut : `./` (la racine)
- ⚠️ **Cliquez sur "Edit"**
- Tapez : `createxyz-project/_/apps/web`
- Cliquez sur **"Continue"** ou validez

**Section 4 : Build and Output Settings**

**⚠️ Vérifiez ces valeurs (elles doivent être remplies automatiquement) :**

- **Build Command :** `npm run build`
- **Output Directory :** `build/client`
- **Install Command :** `npm install`

**Si elles ne sont pas remplies, remplissez-les manuellement.**

**Section 5 : Environment Variables**

**⚠️ TRÈS IMPORTANT : Vous devez ajouter vos variables ici !**

1. Cliquez sur **"Add Environment Variable"** (ou le bouton +)

**Variable 1 :**
- **Name :** `NEXT_PUBLIC_API_BASE`
- **Value :** `https://gamezone-api.infinityfreeapp.com/api`
  
  ⚠️ **REMPLACEZ** `gamezone-api` par VOTRE domaine InfinityFree noté à l'Étape 1.9

2. Cliquez sur **"Add"** ou validez

**Variable 2 :**
- Cliquez encore sur **"Add Environment Variable"**
- **Name :** `NEXT_PUBLIC_KKIAPAY_PUBLIC_KEY`
- **Value :** `072b361d25546db0aee3d69bf07b15331c51e39f`

**Variable 3 :**
- **Name :** `NEXT_PUBLIC_KKIAPAY_SANDBOX`
- **Value :** `0`

**Variable 4 :**
- **Name :** `NODE_ENV`
- **Value :** `production`

**✅ Vous devez avoir 4 variables ajoutées.**

**📍 Déployer :**

1. Descendez en bas de la page
2. Cliquez sur le gros bouton bleu **"Deploy"**

**⏳ Build en cours... (5-10 minutes)**

Vous voyez :
- Logs de build qui défilent
- "Building..."
- "Running Build Command..."

**✅ Déploiement réussi quand vous voyez :**
- 🎉 **"Congratulations!"**
- Bouton **"Visit"** ou **"Continue to Dashboard"**

**📋 NOTEZ VOTRE URL VERCEL :**

Elle ressemble à :
```
https://gamezone-abc123.vercel.app
```

**💾 Notez cette URL !**

---

### ➡️ Étape 2.6 : Configurer CORS sur InfinityFree

**⚠️ Maintenant que vous avez votre URL Vercel, il faut autoriser le frontend à appeler l'API.**

**📍 Sur votre PC :**

1. Ouvrez FileZilla (déjà configuré de l'Étape 1.7)
2. Connectez-vous à InfinityFree (les infos sont déjà enregistrées)

**📍 Dans FileZilla, côté DROIT (InfinityFree) :**

1. Allez dans `/htdocs/`
2. Cherchez le fichier **`.htaccess`**
3. **Clic droit** dessus
4. Choisissez **"View/Edit"** (ou "Afficher/Éditer")

**📍 Le fichier s'ouvre dans votre éditeur :**

Vous voyez une ligne :
```apache
Header set Access-Control-Allow-Origin "https://gamezone.vercel.app"
```

**⚠️ REMPLACEZ** `https://gamezone.vercel.app` par VOTRE URL Vercel notée :

Exemple :
```apache
Header set Access-Control-Allow-Origin "https://gamezone-abc123.vercel.app"
```

**📍 Sauvegarder :**

1. Menu **"Fichier" → "Enregistrer"** (ou Ctrl+S)
2. Fermez l'éditeur
3. FileZilla demande : **"Upload modified file?"**
4. Cliquez **"Yes"** (ou "Oui")

**✅ CORS configuré !**

---

### ➡️ Étape 2.7 : Tester l'application complète

**📍 Dans votre navigateur :**

1. Allez sur votre URL Vercel :
   ```
   https://gamezone-abc123.vercel.app
   ```

2. La page d'accueil devrait charger

**📍 Tester le login :**

1. Cliquez sur **"Connexion"** ou **"Login"**
2. Essayez de vous connecter avec :
   - Email : `admin@gamezone.fr`
   - Password : `demo123`

**✅ Si ça marche :**
- Vous êtes redirigé vers le dashboard
- Pas d'erreurs dans la console (F12)

**❌ Si erreurs CORS :**
- Appuyez sur F12
- Onglet Console
- Vérifiez les erreurs
- Vérifiez que l'URL dans `.htaccess` est exacte

---

## 🎉 DÉPLOIEMENT TERMINÉ !

**✅ Votre application est EN LIGNE !**

**Frontend :** `https://gamezone-abc123.vercel.app`  
**Backend :** `https://gamezone-api.infinityfreeapp.com`

---

## 📋 Récapitulatif de VOS URLs et infos

**Complétez ce formulaire pour vous :**

```
=== INFINITYFREE ===
Site URL : https://_______________________.infinityfreeapp.com
FTP Username : epiz_________________
FTP Password : _____________________
MySQL Host : sql___.infinityfreeapp.com
MySQL Database : epiz_________________gamezone
MySQL Username : epiz_________________
MySQL Password : _____________________

=== GITHUB ===
Repository URL : https://github.com/____________/gamezone

=== VERCEL ===
Site URL : https://gamezone-________.vercel.app

=== KKIAPAY ===
Public Key : 072b361d25546db0aee3d69bf07b15331c51e39f
```

**💾 Sauvegardez ces infos précieusement !**

---

## 🔄 Pour les mises à jour futures

**Quand vous modifiez le code :**

**Backend (PHP) :**
1. Modifiez les fichiers localement
2. Ouvrez FileZilla
3. Uploadez les fichiers modifiés

**Frontend (React) :**
1. Modifiez les fichiers localement
2. Ouvrez PowerShell
3. Tapez :
   ```powershell
   cd "C:\xampp\htdocs\projet ismo"
   git add .
   git commit -m "Description des changements"
   git push
   ```
4. Vercel redéploie automatiquement ! ✨

---

**✅ GUIDE TERMINÉ !**

Vous savez maintenant exactement où cliquer à chaque étape ! 🎉
