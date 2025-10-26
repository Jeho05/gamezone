# 🔧 Configuration des Variables d'Environnement Vercel

## ⚠️ ACTION REQUISE

Les variables d'environnement doivent être configurées dans le **Dashboard Vercel** car je ne peux pas y accéder directement.

---

## 📋 Étapes à Suivre

### Étape 1: Aller sur Vercel Dashboard

**URL:** https://vercel.com/jeho05/gamezoneismo/settings/environment-variables

### Étape 2: Ajouter Ces Variables

Cliquez sur "Add New" pour chaque variable:

#### Variable 1: API_BASE
```
Name:  NEXT_PUBLIC_API_BASE
Value: https://ismo.gamer.gd/api
Environments: ✅ Production ✅ Preview ✅ Development
```

#### Variable 2: KKiaPay Public Key
```
Name:  NEXT_PUBLIC_KKIAPAY_PUBLIC_KEY
Value: 072b361d25546db0aee3d69bf07b15331c51e39f
Environments: ✅ Production ✅ Preview ✅ Development
```

#### Variable 3: KKiaPay Sandbox
```
Name:  NEXT_PUBLIC_KKIAPAY_SANDBOX
Value: 0
Environments: ✅ Production ✅ Preview ✅ Development
```

### Étape 3: Sauvegarder

Cliquez sur "Save" après chaque variable.

---

## 🚀 Étape 4: Redéployer l'Application

**CRITIQUE:** Les variables d'environnement ne prennent effet qu'après un redéploiement!

### Option A: Redéploiement via Dashboard (Le plus simple)

1. Aller à: https://vercel.com/jeho05/gamezoneismo
2. Cliquer sur l'onglet "Deployments"
3. Trouver le dernier déploiement en haut
4. Cliquer sur le bouton "..." (trois points) à droite
5. Sélectionner "Redeploy"
6. Confirmer le redéploiement
7. Attendre 1-2 minutes que le build se termine

### Option B: Push Git (Automatique)

Si le projet est connecté à GitHub:

```powershell
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
git add .env.production
git commit -m "Add production environment variables"
git push
```

Vercel redéploiera automatiquement.

---

## ✅ Vérification

Une fois le redéploiement terminé:

### Test 1: Vérifier que l'API Base est correcte

1. Ouvrir: https://gamezoneismo.vercel.app
2. Ouvrir Console (F12)
3. Taper dans la console:
```javascript
console.log('Checking env vars...');
// This won't work in production build, so test the actual login
fetch('https://ismo.gamer.gd/api/test.php', {credentials: 'include'})
  .then(r => r.json())
  .then(d => console.log('✅ API Connected:', d))
  .catch(e => console.error('❌ Error:', e));
```

### Test 2: Essayer de se connecter

1. Aller sur: https://gamezoneismo.vercel.app
2. Essayer de se connecter avec:
   - Email: `admin@gmail.com`
   - Password: `demo123`
3. **Devrait fonctionner!** ✅

---

## 🔍 Diagnostic si ça ne marche toujours pas

### Check 1: Vérifier que les variables sont bien définies

Dans Vercel Dashboard:
- Aller à Settings > Environment Variables
- Vérifier que les 3 variables sont présentes
- Vérifier que "Production" est coché pour chacune

### Check 2: Vérifier que le redéploiement est terminé

- Le statut doit être "Ready" (vert)
- Pas "Building" ou "Error"

### Check 3: Vider le cache du navigateur

```
Ctrl + Shift + Delete
→ Cocher "Cached images and files"
→ Cliquer "Clear data"
```

Puis:
```
Ctrl + Shift + R (Hard refresh)
```

---

## 📸 Ce qu'il faut voir dans le Dashboard

**Settings > Environment Variables devrait montrer:**

```
┌──────────────────────────────────┬──────────────────────────────┬──────────────────────┐
│ Name                             │ Value                        │ Environments         │
├──────────────────────────────────┼──────────────────────────────┼──────────────────────┤
│ NEXT_PUBLIC_API_BASE             │ https://ismo.gamer.gd/api    │ Production, Preview  │
│ NEXT_PUBLIC_KKIAPAY_PUBLIC_KEY   │ 072b361d25546db0aee3d6...    │ Production, Preview  │
│ NEXT_PUBLIC_KKIAPAY_SANDBOX      │ 0                            │ Production, Preview  │
└──────────────────────────────────┴──────────────────────────────┴──────────────────────┘
```

---

## ⚡ Résumé Rapide

1. ✅ Aller sur: https://vercel.com/jeho05/gamezoneismo/settings/environment-variables
2. ✅ Ajouter les 3 variables listées ci-dessus
3. ✅ Redéployer via: https://vercel.com/jeho05/gamezoneismo (onglet Deployments → Redeploy)
4. ✅ Attendre 1-2 minutes
5. ✅ Vider le cache navigateur
6. ✅ Tester le login sur https://gamezoneismo.vercel.app

**Après ces étapes, le login fonctionnera!** 🎉

---

## 🚨 Important

- Les variables d'environnement dans le code (.env.production) ne sont **PAS** utilisées par Vercel en production
- Vercel utilise **UNIQUEMENT** les variables définies dans son dashboard
- C'est pourquoi vous DEVEZ les configurer manuellement dans le dashboard

---

**Je ne peux pas accéder au dashboard Vercel, vous devez faire ces étapes vous-même!**
