# 🔒 Activer SSL sur InfinityFree - Guide Complet

**Problème Actuel** : Mixed Content (HTTPS → HTTP bloqué)
**Solution** : Activer SSL gratuit sur InfinityFree

---

## 🚀 Étape 1 : Activer SSL (5 minutes)

### Via Control Panel InfinityFree

1. **Connectez-vous** à InfinityFree
2. Allez dans **"SSL Certificates"**
3. Sélectionnez votre domaine : `ismo.gamer.gd`
4. Cliquez sur **"Install SSL Certificate"**
5. Choisissez **"Free SSL (Let's Encrypt)"**
6. Cliquez **"Install"**

### Temps d'Activation
- ⏱️ **5 à 30 minutes** normalement
- 🕐 Parfois jusqu'à **24 heures** maximum

### Vérifier si SSL est Actif

Testez dans votre navigateur :
```
https://ismo.gamer.gd/api/health.php
```

**✅ Si ça marche** : SSL activé !
**❌ Si erreur SSL** : Attendez encore un peu

---

## 🎯 Étape 2 : Après Activation SSL

### A) Forcer HTTPS dans .htaccess

Éditez `/htdocs/.htaccess` via FileZilla :

**DÉCOMMENTEZ ces lignes** (lignes 5-6) :
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Résultat :**
```apache
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# CORS Headers pour Vercel...
```

### B) Redéployer le Frontend Vercel

Le fichier `.env.production` a déjà été mis à jour avec HTTPS :
```env
NEXT_PUBLIC_API_BASE=https://ismo.gamer.gd/api
```

**Redéployez :**
```powershell
cd "c:\xampp\htdocs\gamezone-frontend-clean"

# Option 1 : Git push (si configuré)
git add .
git commit -m "Update API to HTTPS"
git push

# Option 2 : CLI Vercel
vercel --prod
```

---

## 🧪 Étape 3 : Tests

### Test 1 : API en HTTPS
```
https://ismo.gamer.gd/api/health.php
```
✅ Doit afficher le JSON de santé

### Test 2 : Site Vercel
```
https://gamezoneismo.vercel.app/
```
✅ Doit charger sans erreur

### Test 3 : Login
1. Allez sur votre site Vercel
2. Essayez de vous connecter
3. **F12 > Console** : Plus d'erreur "Mixed Content" !

---

## ⚠️ Solution Temporaire (en attendant SSL)

### Option A : Autoriser Mixed Content (Test Uniquement)

**Chrome/Edge :**
1. Cliquez sur le cadenas 🔒 dans la barre d'adresse
2. Cliquez **"Site settings"**
3. Trouvez **"Insecure content"**
4. Changez en **"Allow"**
5. Rechargez la page

**Firefox :**
1. Cliquez sur le cadenas 🔒
2. Cliquez **"Turn off protection for now"**

⚠️ **Attention** : C'est TEMPORAIRE et NON SÉCURISÉ. À utiliser uniquement pour tester.

### Option B : Tester en Local

Pour tester l'application sans SSL :

1. **Backend** : Reste sur `localhost` (HTTP)
2. **Frontend** : `npm run dev` en local (HTTP aussi)
3. Les deux en HTTP → pas de Mixed Content

---

## 📋 Checklist Complète

### Avant Activation SSL
- [x] Backend uploadé sur InfinityFree
- [x] Health check fonctionne en HTTP
- [x] Frontend déployé sur Vercel
- [ ] **SSL activé sur InfinityFree** ← Vous êtes ici

### Après Activation SSL
- [ ] Test HTTPS : `https://ismo.gamer.gd/api/health.php`
- [ ] .htaccess mis à jour (force HTTPS)
- [ ] Frontend redéployé avec HTTPS
- [ ] Login fonctionne sans erreur Mixed Content
- [ ] Application complètement opérationnelle

---

## 🎉 Résultat Final

Une fois le SSL actif :
- ✅ Frontend : `https://gamezoneismo.vercel.app/` (HTTPS)
- ✅ Backend : `https://ismo.gamer.gd/api` (HTTPS)
- ✅ Plus d'erreur Mixed Content
- ✅ Application 100% sécurisée
- ✅ Tout fonctionne !

---

## 📞 Liens Utiles

- **Control Panel InfinityFree** : https://app.infinityfree.com/
- **Documentation SSL InfinityFree** : https://forum.infinityfree.com/docs?topic=49
- **Vérifier SSL** : https://www.ssllabs.com/ssltest/

---

**Prochaine action : Activez le SSL sur InfinityFree (5 minutes) ! 🔒**
