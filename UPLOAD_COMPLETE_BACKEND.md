# 📤 Upload Backend Complet vers InfinityFree

## ✅ Backend Copié

Le backend LOCAL qui fonctionne a été copié vers `backend_infinityfree/api/`

**Total: 181 fichiers**

---

## 📋 Fichiers à Uploader via FileZilla

### Dossier Source (Local)
```
C:\xampp\htdocs\projet ismo\backend_infinityfree\api\
```

### Destination (Serveur)
```
/htdocs/api/
```

---

## 🚀 UPLOAD - Étapes Détaillées

### 1. Ouvrir FileZilla

### 2. Connecter au Serveur
- **Host:** `ftpupload.net`
- **Username:** `if0_40238088`
- **Password:** `OTnlRESWse7lVB`
- **Port:** `21`
- Cliquer "Connexion rapide"

### 3. Navigation

**Panel Gauche (Local):**
```
C:\xampp\htdocs\projet ismo\backend_infinityfree\api
```

**Panel Droit (Remote):**
```
/htdocs/api
```

### 4. Upload COMPLET

**Option A - Glisser-Déposer (RECOMMANDÉ):**
1. Dans le panel gauche, sélectionner TOUS les fichiers (Ctrl+A)
2. Glisser vers le panel droit
3. Confirmer le remplacement si demandé
4. **ATTENDRE que les 181 fichiers soient uploadés** (cela peut prendre 5-10 minutes)

**Option B - Click Droit:**
1. Click droit dans le panel gauche sur le dossier `api`
2. Sélectionner "Upload"
3. Attendre la fin de l'upload

### 5. Vérifier l'Upload

Dans le panel droit, vérifier que ces dossiers existent:
- ✅ `/htdocs/api/auth/`
- ✅ `/htdocs/api/admin/`
- ✅ `/htdocs/api/middleware/`
- ✅ `/htdocs/api/users/`
- ✅ `/htdocs/api/rewards/`
- ✅ `/htdocs/api/.env`
- ✅ `/htdocs/api/config.php`

---

## ✅ Test Après Upload

### Test 1: Santé Backend

Ouvrir dans navigateur:
```
https://ismo.gamer.gd/api/test.php
```

**Résultat attendu:**
```json
{
  "status": "OK",
  "timestamp": "..."
}
```

### Test 2: CORS Test

Dans console (F12) sur https://gamezoneismo.vercel.app:

```javascript
fetch('https://ismo.gamer.gd/api/test.php')
  .then(r => r.json())
  .then(d => console.log('✅ CORS Works:', d))
  .catch(e => console.error('❌ Failed:', e))
```

**Résultat attendu:**
```
✅ CORS Works: {status: "OK", ...}
```

### Test 3: Login

Dans console (F12):

```javascript
fetch('https://ismo.gamer.gd/api/auth/login.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  credentials: 'include',
  body: JSON.stringify({
    email: 'admin@gmail.com',
    password: 'demo123'
  })
})
.then(r => r.json())
.then(d => console.log('✅ LOGIN:', d))
.catch(e => console.error('❌ Error:', e))
```

**Résultat attendu:**
```
✅ LOGIN: {message: "Connexion réussie", user: {...}}
```

---

## 🎯 Changements Importants

### Différences vs Ancien Backend:

1. **Headers CORS:**
   - Ajouté `https://gamezoneismo.vercel.app` dans les origines autorisées
   - Support des sous-domaines Vercel (`*.vercel.app`)

2. **Configuration:**
   - Fichier `.env` avec les credentials InfinityFree
   - Headers `X-CSRF-Token` ajoutés aux headers CORS autorisés

3. **Structure:**
   - Backend LOCAL complet copié (181 fichiers)
   - Même structure que le backend qui fonctionne en local

---

## ⚠️ IMPORTANT

**NE PAS modifier le fichier `.env` sur le serveur après l'upload!**

Il contient déjà:
- DB_HOST=sql308.infinityfree.com
- DB_NAME=if0_40238088_gamezone
- DB_USER=if0_40238088
- DB_PASS=OTnlRESWse7lVB

---

## 🚨 Si Ça Ne Marche Toujours Pas

Si après l'upload le CORS ne fonctionne toujours pas, cela confirmera que **InfinityFree bloque CORS au niveau serveur**, et il faudra:

**Option 1:** Migrer vers Railway.app (gratuit, supporte CORS)
**Option 2:** Utiliser le proxy CORS déjà déployé sur Vercel

Mais essayons d'abord avec le backend complet!

---

**UPLOADEZ LES 181 FICHIERS MAINTENANT ET TESTEZ!** 🚀
