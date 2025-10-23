# 📤 Upload FTP - Guide Simplifié avec VOS Infos

## 🎯 Télécharger FileZilla (si pas déjà fait)

1. Allez sur : **filezilla-project.org**
2. Cliquez **"Download FileZilla Client"**
3. Téléchargez et installez

---

## 🔌 Connexion à InfinityFree

### Ouvrez FileZilla

**En haut de la fenêtre, remplissez :**

```
Hôte        : ftpupload.net
Identifiant : if0_40238088
Mot de passe: OTnlRESWse7lVB
Port        : 21
```

**Cliquez sur "Connexion rapide"**

⏳ Attendez quelques secondes...

✅ **Connecté !** Vous voyez maintenant 2 panneaux :
- **Gauche :** Votre PC
- **Droite :** Serveur InfinityFree

---

## 📂 Navigation sur le Serveur (Droite)

**Sur le panneau de DROITE :**

1. Vous voyez des dossiers
2. Cherchez et **double-cliquez** sur : **`htdocs`**
3. Vous êtes maintenant dans `/htdocs/`

**⚠️ IMPORTANT : Vous devez être DANS htdocs, pas au-dessus !**

---

## 📁 Préparer les Fichiers à Uploader (Gauche)

**Sur le panneau de GAUCHE :**

1. Naviguez vers : `C:\xampp\htdocs\projet ismo\backend_infinityfree`
2. Vous voyez :
   - Dossier `api`
   - Dossier `uploads`
   - Dossier `images`
   - Fichier `.htaccess`
   - Fichier `schema_infinityfree.sql`

---

## ⚠️ ÉTAPE CRITIQUE : Créer le .env

**AVANT d'uploader, vérifiez que le fichier .env existe :**

1. Sur votre PC, allez dans : `C:\xampp\htdocs\projet ismo\backend_infinityfree\api`
2. Cherchez le fichier **`.env`**

**❌ Si le fichier .env N'EXISTE PAS :**

1. Ouvrez : `C:\xampp\htdocs\projet ismo\.env_a_copier_vers_backend.txt`
2. Copiez tout le contenu (Ctrl+A, Ctrl+C)
3. Allez dans : `C:\xampp\htdocs\projet ismo\backend_infinityfree\api`
4. Créez un nouveau fichier : **`.env`** (avec le point au début)
5. Ouvrez-le avec Bloc-notes
6. Collez le contenu (Ctrl+V)
7. Sauvegardez (Ctrl+S)

**✅ Maintenant le .env existe avec vos informations !**

---

## 📤 Upload du Backend

**Dans FileZilla :**

### Méthode Simple :

1. **Panneau GAUCHE :** Sélectionnez TOUT dans `backend_infinityfree/`
   - Clic sur le premier item
   - Maintenez **Shift** et cliquez sur le dernier item
   - Tout est sélectionné (surligné en bleu)

2. **Glissez-déposez** vers le panneau DROIT (dans `/htdocs/`)
   - OU Clic droit → **"Upload"**

### Vous devez uploader :
- ✅ Dossier `api` (avec tous les fichiers PHP + le `.env`)
- ✅ Dossier `uploads`
- ✅ Dossier `images`
- ✅ Fichier `.htaccess`

**⚠️ NE PAS uploader :**
- ❌ `schema_infinityfree.sql` (déjà importé dans phpMyAdmin)
- ❌ `README.txt` (optionnel)

---

## ⏱️ Progression de l'Upload

**En bas de FileZilla, vous voyez :**

- **Queue :** Liste des fichiers en attente
- **Failed transfers :** Fichiers échoués (doit être vide)
- **Successful transfers :** Fichiers réussis

**Durée estimée :** 5-15 minutes (selon connexion internet)

---

## ✅ Vérification Upload Réussi

**Panneau DROIT (serveur), dans `/htdocs/` :**

Vous devez voir :

```
htdocs/
├── api/
│   ├── admin/
│   ├── auth/
│   ├── content/
│   ├── migrations/
│   ├── sessions/
│   ├── shop/
│   ├── .env           ← TRÈS IMPORTANT !
│   ├── config.php
│   └── ... (plein de fichiers PHP)
├── uploads/
│   ├── avatars/
│   └── games/
├── images/
│   ├── gaming tof/
│   ├── objet/
│   └── video/
└── .htaccess
```

**🔍 Vérification Critique du .env :**

1. Dans FileZilla, panneau DROIT
2. Allez dans `/htdocs/api/`
3. Cherchez le fichier **`.env`**
4. **Clic droit** dessus → **"View/Edit"**
5. Vérifiez que le contenu est correct :
   ```
   DB_HOST=sql308.infinityfree.com
   DB_NAME=if0_40238088_gamezone
   DB_USER=if0_40238088
   DB_PASS=OTnlRESWse7lVB
   APP_URL=http://ismo.gamer.gd
   ```

**✅ Si tout est là, c'est parfait !**

---

## 🧪 Test de l'API

**Dans votre navigateur, allez sur :**

```
http://ismo.gamer.gd/api/auth/check.php
```

### ✅ Si ça marche, vous voyez :

```json
{
  "authenticated": false,
  "message": "No active session"
}
```

**🎉 VOTRE API FONCTIONNE !**

---

## ❌ Dépannage Erreurs Courantes

### Erreur 1 : "404 Not Found"

**Causes possibles :**
- Les fichiers ne sont pas dans `/htdocs/`
- Le dossier `api` n'est pas uploadé

**Solution :**
- Vérifiez que vous avez bien uploadé dans `/htdocs/`
- Vérifiez que le dossier `api` existe sur le serveur

---

### Erreur 2 : "500 Internal Server Error"

**Causes possibles :**
- Le fichier `.env` n'existe pas
- Le fichier `.env` contient des erreurs
- Les identifiants MySQL sont incorrects

**Solution :**
1. Vérifiez que `.env` existe dans `/htdocs/api/`
2. Ouvrez `.env` et vérifiez les infos MySQL
3. Testez la connexion MySQL via phpMyAdmin

---

### Erreur 3 : "Database connection failed"

**Causes possibles :**
- Identifiants MySQL incorrects dans `.env`
- La base de données n'est pas créée

**Solution :**
1. Vérifiez dans phpMyAdmin que la base `if0_40238088_gamezone` existe
2. Vérifiez que les tables sont créées (users, rewards, etc.)
3. Vérifiez les identifiants dans `.env`

---

### Erreur 4 : Upload échoue / Timeout

**Solutions :**
1. Réessayez la connexion FTP
2. Uploadez dossier par dossier (d'abord `api`, puis `uploads`, puis `images`)
3. Vérifiez votre connexion internet

---

## 🎯 Checklist Complète

- [ ] FileZilla installé
- [ ] Connecté à InfinityFree (ftpupload.net)
- [ ] Navigué dans `/htdocs/` sur le serveur
- [ ] Fichier `.env` créé localement dans `backend_infinityfree/api/`
- [ ] Dossier `api` uploadé
- [ ] Dossier `uploads` uploadé
- [ ] Dossier `images` uploadé
- [ ] Fichier `.htaccess` uploadé
- [ ] Vérifié que `.env` existe dans `/htdocs/api/` sur le serveur
- [ ] Testé l'API : `http://ismo.gamer.gd/api/auth/check.php`
- [ ] L'API répond du JSON

---

## 🎉 Backend Déployé !

**✅ Une fois que l'API répond correctement, passez à l'Étape 2 : GitHub + Vercel**

---

**Vos URLs Backend :**
- Site : `http://ismo.gamer.gd`
- API : `http://ismo.gamer.gd/api`
- Test : `http://ismo.gamer.gd/api/auth/check.php`

**Conservez ces URLs, vous en aurez besoin pour configurer Vercel !**
