# 🚀 Déploiement Frontend sur Vercel - Guide Rapide

## 📋 Pré-requis

- [x] Git installé
- [ ] Compte GitHub
- [ ] Compte Vercel
- [ ] Backend déjà déployé sur InfinityFree

---

## 🎯 Étape 1 : Initialiser Git et GitHub

### Sur votre PC, dans le dossier du projet :

```powershell
cd "c:\xampp\htdocs\projet ismo"

# Si Git n'est pas encore initialisé
git init

# Ajouter tous les fichiers
git add .

# Premier commit
git commit -m "Initial commit - GameZone v1.0"
```

### Créer le Repository GitHub

1. Allez sur [github.com/new](https://github.com/new)
2. **Repository name:** `gamezone`
3. **Description:** `Plateforme de gestion de cyber café`
4. **Public** ou **Private** (votre choix)
5. ❗ **NE PAS** cocher "Initialize this repository with a README"
6. Cliquez **Create repository**

### Connecter et Push

GitHub vous donnera des commandes. Exécutez :

```powershell
git remote add origin https://github.com/VOTRE-USERNAME/gamezone.git
git branch -M main
git push -u origin main
```

**✅ Votre code est maintenant sur GitHub !**

---

## 🎯 Étape 2 : Déployer sur Vercel

### 2.1 Créer un Compte Vercel

1. Allez sur [vercel.com](https://vercel.com)
2. Cliquez **Sign Up**
3. Choisissez **Continue with GitHub** (recommandé)
4. Autorisez Vercel à accéder à vos repositories

### 2.2 Importer le Projet

1. Dans le dashboard Vercel, cliquez **Add New... → Project**
2. Sélectionnez votre repository **`gamezone`**
3. Cliquez **Import**

### 2.3 Configuration du Projet

**📁 Root Directory:**
```
createxyz-project/_/apps/web
```

**⚙️ Framework Preset:**
```
Vite
```

**🔨 Build Command:**
```
npm run build
```

**📦 Output Directory:**
```
build/client
```

**📥 Install Command:**
```
npm install
```

### 2.4 Variables d'Environnement

Cliquez sur **Environment Variables** et ajoutez :

| Name | Value |
|------|-------|
| `NEXT_PUBLIC_API_BASE` | `https://votre-nom.infinityfreeapp.com/api` |
| `NEXT_PUBLIC_KKIAPAY_PUBLIC_KEY` | `072b361d25546db0aee3d69bf07b15331c51e39f` |
| `NEXT_PUBLIC_KKIAPAY_SANDBOX` | `0` |
| `NODE_ENV` | `production` |

**⚠️ IMPORTANT:** Remplacez `votre-nom.infinityfreeapp.com` par votre vrai domaine InfinityFree !

### 2.5 Déployer

1. Cliquez **Deploy**
2. Attendez 2-5 minutes
3. Votre site sera accessible sur : `https://gamezone-XXXX.vercel.app`

**🎉 Frontend déployé !**

---

## 🔧 Étape 3 : Configuration CORS Backend

Maintenant que vous avez votre URL Vercel, configurez le CORS sur InfinityFree.

### 3.1 Modifier .htaccess sur InfinityFree

Dans `/htdocs/.htaccess`, remplacez :

```apache
Header set Access-Control-Allow-Origin "https://gamezone.vercel.app"
```

Par votre **vraie URL Vercel** :

```apache
Header set Access-Control-Allow-Origin "https://gamezone-XXXX.vercel.app"
```

### 3.2 Modifier api/config.php

Ajoutez votre URL Vercel dans les origines autorisées :

```php
$allowed_origins = [
    'https://gamezone-XXXX.vercel.app', // Votre URL Vercel
    'http://localhost:4000' // Dev local
];
```

---

## ✅ Tests

### 1. Frontend charge
Ouvrez : `https://gamezone-XXXX.vercel.app`

### 2. API répond
1. Ouvrez F12 → Network
2. Essayez de vous connecter
3. Vérifiez les requêtes API :
   - Status: 200 OK
   - Pas d'erreurs CORS

### 3. Fonctionnalités
- [ ] Login fonctionne
- [ ] Inscription fonctionne
- [ ] Dashboard charge
- [ ] Images s'affichent
- [ ] Profil accessible

---

## 🔄 Mises à Jour

Chaque fois que vous modifiez le code :

```powershell
git add .
git commit -m "Description des changements"
git push
```

**Vercel redéploie automatiquement !** ✨

Vous recevrez un email à chaque déploiement.

---

## 🌐 Domaine Personnalisé (Optionnel)

### Ajouter votre propre domaine

1. Achetez un domaine (ex: `gamezone.com`) sur :
   - Namecheap (~10€/an)
   - OVH
   - Google Domains

2. Dans Vercel → Settings → Domains
3. Ajoutez votre domaine
4. Suivez les instructions DNS

---

## 🆘 Dépannage

### Build échoue sur Vercel

**Vérifiez :**
- Root Directory = `createxyz-project/_/apps/web`
- Build Command = `npm run build`
- Node.js version (Vercel utilise v18+ par défaut)

**Solution :** Consultez les logs de build dans Vercel

### Erreur CORS

**Symptôme :** `Cross-Origin Request Blocked`

**Solution :**
1. Vérifiez `.htaccess` sur InfinityFree
2. URL exacte de Vercel (avec https://)
3. Videz le cache du navigateur (Ctrl+Shift+R)

### API ne répond pas

**Solution :**
1. Testez directement : `https://votre-nom.infinityfreeapp.com/api/auth/check.php`
2. Vérifiez que le backend est bien uploadé
3. Vérifiez `api/.env` sur InfinityFree

### Session ne persiste pas

**Solution :**
1. Backend et Frontend doivent être en HTTPS
2. Vérifiez `credentials: 'include'` dans les fetch()
3. CORS doit avoir `Access-Control-Allow-Credentials: true`

---

## 📊 Monitoring

### Vercel Analytics (Gratuit)

1. Vercel → Analytics
2. Activez Web Analytics
3. Voyez les visites en temps réel

### UptimeRobot (Gratuit)

1. [uptimerobot.com](https://uptimerobot.com)
2. Ajoutez votre URL Vercel
3. Recevez des alertes si le site est down

---

## 💡 Astuces Vercel

### Preview Deployments

Chaque branche Git a sa propre URL de preview ! 

```powershell
git checkout -b nouvelle-fonctionnalite
# Faites vos modifications
git push origin nouvelle-fonctionnalite
```

Vercel crée automatiquement : `https://gamezone-XXXX-nouvelle-fonctionnalite.vercel.app`

### Environment Variables par Environnement

Vous pouvez avoir des variables différentes pour :
- Production
- Preview
- Development

### Rollback Instantané

Si un déploiement a un problème, cliquez sur un ancien déploiement → **Promote to Production**

---

## 📚 Ressources

- **Vercel Docs:** [vercel.com/docs](https://vercel.com/docs)
- **Vercel Support:** [vercel.com/support](https://vercel.com/support)
- **Guide complet:** `DEPLOIEMENT_SEPARE.md`

---

## ✅ Checklist Finale

- [ ] Git initialisé
- [ ] Code sur GitHub
- [ ] Compte Vercel créé
- [ ] Projet importé
- [ ] Variables d'environnement configurées
- [ ] Build réussi
- [ ] Site accessible
- [ ] CORS configuré sur backend
- [ ] Tests passés
- [ ] Déploiement automatique fonctionne

---

**🎉 Félicitations ! Votre application est en production !**

**Frontend:** `https://gamezone-XXXX.vercel.app`  
**Backend:** `https://votre-nom.infinityfreeapp.com`

**Créé le:** 2025-01-23  
**Version:** 1.0
