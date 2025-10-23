# 🚀 Guide de Déploiement Séparé

## Architecture

```
┌─────────────────────────────────────────┐
│  VERCEL (Frontend React)                │
│  https://gamezone.vercel.app            │
│  - Interface utilisateur                │
│  - Build statique                       │
│  - CDN mondial ultra-rapide             │
└──────────────┬──────────────────────────┘
               │ API Calls
               │ fetch()
               ↓
┌─────────────────────────────────────────┐
│  INFINITYFREE (Backend PHP)             │
│  https://votre-nom.infinityfreeapp.com  │
│  - API REST                             │
│  - Base de données MySQL                │
│  - Upload fichiers                      │
└─────────────────────────────────────────┘
```

---

## 📋 Avantages de cette Architecture

✅ **Frontend sur Vercel :**
- Déploiement automatique depuis GitHub
- Build optimisé automatiquement
- CDN mondial (site ultra-rapide)
- HTTPS gratuit
- Preview deployments (tester avant prod)
- Totalement GRATUIT

✅ **Backend sur InfinityFree :**
- PHP + MySQL gratuit
- Vos APIs PHP fonctionnent sans modification
- Upload de fichiers
- phpMyAdmin inclus

---

## 🎯 Partie 1 : Backend sur InfinityFree

### Étape 1.1 : Créer compte InfinityFree

1. Allez sur [infinityfree.net](https://infinityfree.net)
2. Inscription gratuite
3. Créez un site : `gamezone-api.infinityfreeapp.com` (ou autre nom)
4. Notez vos identifiants :
   ```
   FTP Host: ftpupload.net
   Username: epiz_XXXXXXXX
   Password: ***********
   MySQL Host: sqlXXX.infinityfreeapp.com
   ```

### Étape 1.2 : Créer la Base de Données

1. Dans InfinityFree → **MySQL Databases**
2. Créer : `gamezone`
3. Accéder à **phpMyAdmin**
4. Importer : `api/database/schema.sql`

### Étape 1.3 : Configurer le Backend

Créez `api/.env` sur votre PC :

```env
DB_HOST=sqlXXX.infinityfreeapp.com
DB_NAME=epiz_XXXXXXXX_gamezone
DB_USER=epiz_XXXXXXXX
DB_PASS=votre_mot_de_passe
APP_URL=https://gamezone-api.infinityfreeapp.com
KKIAPAY_PUBLIC_KEY=072b361d25546db0aee3d69bf07b15331c51e39f
KKIAPAY_PRIVATE_KEY=votre_cle_privee
KKIAPAY_SANDBOX=false
```

### Étape 1.4 : Uploader le Backend

**Via FileZilla :**

1. Connectez-vous à `ftpupload.net`
2. Uploadez vers `/htdocs/` :
   ```
   /htdocs/
   ├── api/           ← Tout le dossier api/
   │   ├── admin/
   │   ├── auth/
   │   ├── shop/
   │   ├── config.php
   │   └── .env
   ├── uploads/
   ├── images/
   └── .htaccess
   ```

### Étape 1.5 : Créer .htaccess

Créez `/htdocs/.htaccess` :

```apache
RewriteEngine On

# CORS Headers pour Vercel
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "https://gamezone.vercel.app"
    Header set Access-Control-Allow-Credentials "true"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
</IfModule>

# Handle preflight
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ $1 [R=200,L]

# Security
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### Étape 1.6 : Activer SSL

1. InfinityFree → **SSL Certificates**
2. Activer Let's Encrypt (gratuit)
3. Attendre 5-10 min

### Étape 1.7 : Tester l'API

```
https://gamezone-api.infinityfreeapp.com/api/auth/check.php
```

Devrait retourner du JSON.

---

## 🎯 Partie 2 : Frontend sur Vercel

### Étape 2.1 : Préparer le Projet

Sur votre PC, dans le dossier du projet :

```powershell
cd "c:\xampp\htdocs\projet ismo"

# Initialiser Git si pas déjà fait
git init
git add .
git commit -m "Initial commit"
```

### Étape 2.2 : Créer Repository GitHub

1. Allez sur [github.com/new](https://github.com/new)
2. Nom : `gamezone`
3. Public ou Private
4. **NE PAS** cocher "Initialize with README"
5. Créer le repository

### Étape 2.3 : Push sur GitHub

```powershell
git remote add origin https://github.com/VOTRE-USERNAME/gamezone.git
git branch -M main
git push -u origin main
```

### Étape 2.4 : Créer Compte Vercel

1. Allez sur [vercel.com](https://vercel.com)
2. Inscription avec GitHub (recommandé)
3. Autorisez Vercel à accéder à vos repos

### Étape 2.5 : Importer le Projet

1. Dans Vercel → **Add New Project**
2. Import depuis GitHub
3. Sélectionnez `gamezone`
4. **Configuration importante :**
   
   **Framework Preset:** Vite
   
   **Root Directory:** `createxyz-project/_/apps/web`
   
   **Build Command:** `npm run build`
   
   **Output Directory:** `build/client`
   
   **Install Command:** `npm install`

### Étape 2.6 : Variables d'Environnement

Dans Vercel → Settings → Environment Variables :

```
NEXT_PUBLIC_API_BASE=https://gamezone-api.infinityfreeapp.com/api
NEXT_PUBLIC_KKIAPAY_PUBLIC_KEY=072b361d25546db0aee3d69bf07b15331c51e39f
NEXT_PUBLIC_KKIAPAY_SANDBOX=0
NODE_ENV=production
```

### Étape 2.7 : Déployer

1. Cliquez **Deploy**
2. Attendez 2-5 minutes
3. Votre site sera live sur : `https://gamezone-XXXX.vercel.app`

### Étape 2.8 : Domaine Personnalisé (Optionnel)

Vous pouvez ajouter un domaine comme `gamezone.com` dans Vercel → Settings → Domains

---

## 🔧 Configuration CORS Finale

### Backend (InfinityFree)

Dans `api/config.php`, ajoutez :

```php
// CORS pour Vercel
$allowed_origins = [
    'https://gamezone.vercel.app',
    'https://gamezone-XXXX.vercel.app', // Votre URL Vercel
    'http://localhost:4000' // Dev local
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
```

---

## ✅ Checklist Complète

### Backend (InfinityFree)

- [ ] Compte InfinityFree créé
- [ ] Site créé
- [ ] Base MySQL créée et importée
- [ ] Fichier `api/.env` configuré
- [ ] Backend uploadé via FTP
- [ ] `.htaccess` créé avec CORS
- [ ] SSL activé
- [ ] API testée et fonctionnelle

### Frontend (Vercel)

- [ ] Git initialisé
- [ ] Repository GitHub créé
- [ ] Code pushé sur GitHub
- [ ] Compte Vercel créé
- [ ] Projet importé dans Vercel
- [ ] Variables d'environnement configurées
- [ ] Build réussi
- [ ] Site accessible

### Vérifications Finales

- [ ] Frontend charge correctement
- [ ] Login fonctionne
- [ ] API répond (F12 → Network)
- [ ] Images s'affichent
- [ ] CORS configuré correctement
- [ ] KkiaPay fonctionne

---

## 🆘 Dépannage

### Erreur CORS

**Symptôme:** `Cross-Origin Request Blocked`

**Solution:**
1. Vérifiez `.htaccess` sur InfinityFree
2. Vérifiez l'URL exacte de Vercel dans les headers CORS
3. Videz le cache du navigateur

### API ne répond pas

**Symptôme:** `Failed to fetch`

**Solution:**
1. Testez l'API directement : `https://gamezone-api.infinityfreeapp.com/api/auth/check.php`
2. Vérifiez `api/.env` (connexion DB)
3. Consultez les logs dans InfinityFree

### Build Vercel échoue

**Solution:**
1. Vérifiez le Root Directory : `createxyz-project/_/apps/web`
2. Vérifiez Build Command : `npm run build`
3. Consultez les logs de build dans Vercel

### Session ne persiste pas

**Solution:**
1. Vérifiez que `credentials: 'include'` est dans les fetch()
2. Vérifiez CORS avec `Access-Control-Allow-Credentials: true`
3. Backend et Frontend doivent être en HTTPS (pas de mix HTTP/HTTPS)

---

## 🚀 Mises à Jour

### Mettre à jour le Backend

1. Modifiez les fichiers PHP localement
2. Uploadez via FTP (FileZilla)
3. Testez

### Mettre à jour le Frontend

```powershell
git add .
git commit -m "Description des changements"
git push
```

Vercel redéploie automatiquement ! ✨

---

## 💡 Optimisations Futures

Une fois stable :

1. **Domaine personnalisé**
   - Acheter `gamezone.com` (~10€/an)
   - Le connecter à Vercel (frontend)
   - Sous-domaine `api.gamezone.com` → InfinityFree (backend)

2. **Migration Backend**
   - Hostinger (2-3€/mois) pour meilleures performances
   - Railway.app (5$ gratuit) pour Node.js + PHP

3. **Monitoring**
   - UptimeRobot (gratuit) pour surveiller l'uptime
   - Google Analytics pour les statistiques

4. **Backups**
   - Exporter la base régulièrement via phpMyAdmin
   - GitHub = backup automatique du code

---

## 📞 Support

**Frontend (Vercel):**
- Documentation : [vercel.com/docs](https://vercel.com/docs)
- Support : dashboard Vercel → Help

**Backend (InfinityFree):**
- Forum : [forum.infinityfree.com](https://forum.infinityfree.com)
- FAQ : [infinityfree.net/support](https://infinityfree.net/support)

---

**Créé le:** 2025-01-23  
**Version:** 1.0  
**Architecture:** Vercel (Frontend) + InfinityFree (Backend)

Bon déploiement ! 🎉
