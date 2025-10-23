# 🔧 Solution NetworkError - GameZone Admin

## 🎯 Diagnostic

Vous obtenez: **"NetworkError when attempting to fetch resource"**

### Vérifications Effectuées

✅ Apache actif (port 80) - **OK**
✅ MySQL actif (port 3306) - **OK**  
✅ Frontend React actif (port 4000) - **OK**
✅ API répond directement - **OK** (testé avec curl)

**Conclusion**: Le problème est **CORS ou configuration frontend**.

---

## 🔍 Causes Possibles

1. **CORS mal configuré** sur le backend PHP
2. **Credentials** non envoyés correctement
3. **Port différent** entre dev et production
4. **Cache navigateur** avec anciennes config

---

## 💡 Solutions (Dans l'Ordre)

### Solution 1: Utiliser le Test de Diagnostic

J'ai créé un outil de test qui s'est **ouvert dans votre navigateur**:

**Fichier**: `TEST_API_BROWSER.html`

**Actions**:
1. Cliquez sur "Test 1: Fetch Simple"
2. Cliquez sur "Test 2: Fetch avec credentials"
3. Observez l'erreur **EXACTE** dans le résultat

➡️ **Copiez-moi l'erreur exacte** que vous voyez pour que je puisse corriger précisément.

---

### Solution 2: Vider le Cache Navigateur

```javascript
// Dans la console navigateur (F12):
localStorage.clear();
sessionStorage.clear();
location.reload(true);
```

---

### Solution 3: Vérifier les Headers CORS

Ouvrez la console du navigateur (F12) et regardez:

**Network Tab** → Cliquez sur une requête API → **Headers**

Vous devriez voir:
```
Access-Control-Allow-Origin: http://localhost:4000
Access-Control-Allow-Credentials: true
```

Si ces headers **manquent** → problème backend CORS

---

### Solution 4: Test Direct de l'API

Ouvrez directement dans votre navigateur:

```
http://localhost/projet%20ismo/api/auth/check.php
```

**Résultat attendu**:
```json
{"error":"Unauthorized"}
```

ou

```json
{"authenticated":false}
```

Si vous voyez une **page blanche** ou **erreur 404** → problème Apache/PHP

---

### Solution 5: Forcer l'URL API Directe

Modifiez temporairement `apiBase.js`:

```javascript
// Ligne 7 - Forcer cette URL
API_BASE = 'http://localhost/projet%20ismo/api';
```

Puis redémarrez le serveur React:
```powershell
# Dans le dossier web
npm run dev
```

---

### Solution 6: Désactiver les Bloqueurs

Désactivez temporairement:
- ❌ AdBlock
- ❌ Antivirus (pare-feu)
- ❌ Extensions navigateur
- ❌ VPN

Puis testez à nouveau.

---

### Solution 7: Vérifier le Fichier HOSTS

Ouvrez: `C:\Windows\System32\drivers\etc\hosts`

Vérifiez que cette ligne existe:
```
127.0.0.1    localhost
```

---

## 🔬 Tests Détaillés

### Test dans la Console Navigateur

Ouvrez F12 → Console, puis collez:

```javascript
// Test 1: Basique
fetch('http://localhost/projet%20ismo/api/auth/check.php')
  .then(r => r.json())
  .then(console.log)
  .catch(console.error);

// Test 2: Avec credentials
fetch('http://localhost/projet%20ismo/api/auth/check.php', {
  credentials: 'include'
})
  .then(r => r.json())
  .then(console.log)
  .catch(console.error);
```

**Observez l'erreur** et dites-moi ce que vous voyez.

---

## 🆘 Si Rien ne Fonctionne

### Option A: Utiliser le Proxy Vite

Modifiez `vite.config.ts`:

```typescript
export default defineConfig({
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost/projet%20ismo',
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path
      }
    }
  }
})
```

Puis dans `apiBase.js`:
```javascript
API_BASE = '/api'; // Utiliser le proxy
```

### Option B: Servir depuis Apache

Au lieu de React sur port 4000, servez tout depuis Apache:

1. Build le frontend:
   ```powershell
   npm run build
   ```

2. Copiez `dist/` vers `c:\xampp\htdocs\projet ismo\public\`

3. Accédez via: `http://localhost/projet%20ismo/public/`

---

## 📊 Checklist de Diagnostic

Exécutez ces commandes et donnez-moi les résultats:

```powershell
# 1. Vérifier Apache
netstat -ano | findstr ":80"

# 2. Test API direct
curl http://localhost/projet%20ismo/api/auth/check.php

# 3. Vérifier React
netstat -ano | findstr ":4000"
```

---

## 🎯 Action Immédiate

**FAITES CECI MAINTENANT**:

1. Ouvrez `TEST_API_BROWSER.html` (déjà ouvert)
2. Cliquez sur "Test 1: Fetch Simple"
3. **Copiez-moi l'erreur EXACTE** affichée

Avec cette info, je pourrai vous donner la **solution précise**! 🎯

---

## 💡 Astuce

Si vous voyez dans la console:

- **"CORS"** → Problème backend (je vais corriger)
- **"Failed to fetch"** → Problème réseau/pare-feu
- **"404"** → Mauvaise URL
- **"Timeout"** → Apache lent ou bloqué

Dites-moi laquelle et je corrige immédiatement!
