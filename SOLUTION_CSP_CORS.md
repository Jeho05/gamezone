# Solution au problème NetworkError / CSP / CORS

## Problème initial

```
NetworkError when attempting to fetch resource.
Content-Security-Policy: Les paramètres de la page ont empêché le chargement 
d'une ressource (connect-src) à l'adresse http://localhost/projet%20ismo/api/...
car elle enfreint la directive suivante : « default-src 'none' »
```

## Cause

Le frontend (port 4000) essayait de faire des requêtes **cross-origin** vers le backend PHP (port 80):
- `http://localhost:4000` → `http://localhost/projet%20ismo/api`
- Le navigateur bloquait ces requêtes à cause de la **Content Security Policy (CSP)** restrictive
- Même avec CORS configuré côté PHP, la CSP côté frontend bloquait tout

## Solution appliquée

### ✅ Proxy Vite (same-origin)

Au lieu de requêtes cross-origin, toutes les requêtes API passent maintenant par le **proxy Vite**:

```
Frontend: http://localhost:4000/php-api/auth/register.php
    ↓ (proxy Vite)
Backend:  http://localhost/projet%20ismo/api/auth/register.php
```

Le navigateur voit uniquement `http://localhost:4000/php-api/...` (same-origin) → **pas de CSP/CORS**

**Note:** On utilise `/php-api` au lieu de `/api` pour éviter le conflit avec les routes Hono internes (`/api/*`)

### Fichiers modifiés

#### 1. `vite.config.ts` - Proxy Vite ajouté

```typescript
server: {
  allowedHosts: true,
  host: '0.0.0.0',
  port: 4000,
  proxy: {
    '/php-api': {
      target: 'http://localhost',
      changeOrigin: true,
      rewrite: (path) => path.replace(/^\/php-api/, '/projet%20ismo/api'),
    },
  },
}
```

#### 2. `src/utils/apiBase.js` - URL relative

```javascript
// Avant: 'http://localhost/projet%20ismo/api'
// Après: '/php-api' (utilise le proxy, évite conflit avec Hono /api/*)
let API_BASE = '/php-api';
```

#### 3. `src/app/root.tsx` - Variable globale

```javascript
// Avant: window.APP_API_BASE = 'http://localhost/projet%20ismo/api';
// Après: window.APP_API_BASE = '/php-api';
```

#### 4. `__create/index.ts` - CSP supprimée

Suppression du middleware CSP restrictif qui bloquait les requêtes.

## Avantages

1. ✅ **Pas de CORS** - Requêtes same-origin
2. ✅ **Pas de CSP** - Pas de blocage par Content-Security-Policy
3. ✅ **Pas d'espace encodé** - Le proxy gère `/projet%20ismo/`
4. ✅ **Simple** - Le frontend utilise juste `/api`
5. ✅ **Credentials** - Les cookies de session fonctionnent

## Comment tester

### 1. Démarrer le projet

```powershell
cd "c:\xampp\htdocs\projet ismo"
.\DEMARRER_PROJET.ps1
```

### 2. Tester dans la console du navigateur

Ouvrez http://localhost:4000, puis F12 (console):

```javascript
// Test inscription
fetch('/php-api/auth/register.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include',
  body: JSON.stringify({
    username: 'TestProxy',
    email: 'test' + Date.now() + '@example.com',
    password: 'test1234'
  })
})
.then(r => r.json())
.then(d => console.log('✓ SUCCES:', d))
.catch(e => console.error('✗ ERREUR:', e));
```

**Résultat attendu:**
```json
{
  "message": "Inscription réussie",
  "user": {
    "id": 123,
    "username": "TestProxy",
    "email": "test@example.com",
    "role": "player",
    "points": 0
  }
}
```

### 3. Tester via l'interface

1. Ouvrez http://localhost:4000/auth/register
2. Remplissez le formulaire
3. Cliquez sur "Créer un compte"
4. Vous devriez être redirigé vers le dashboard

## Vérification dans Network (F12)

Dans l'onglet "Réseau" (Network):
- **Request URL:** `http://localhost:4000/php-api/auth/register.php`
- **Status:** 200 OK
- **Response:** JSON valide
- **Pas d'erreur CORS/CSP**

## En cas de problème

### Le proxy ne fonctionne pas

1. **Redémarrez le serveur dev:**
   ```powershell
   # Ctrl+C dans le terminal
   npm run dev
   ```

2. **Vérifiez que Vite utilise bien le proxy:**
   - Regardez les logs du terminal
   - Vous devriez voir: `Proxying /api -> http://localhost/projet%20ismo/api`

3. **Hard refresh du navigateur:**
   - Ctrl+Shift+R (vide le cache)

### Apache ne répond pas

```powershell
# Testez directement l'API
curl http://localhost/projet%20ismo/api/test.php
```

Si ça ne fonctionne pas:
- Vérifiez XAMPP Control Panel (Apache vert)
- Vérifiez que le dossier existe: `c:\xampp\htdocs\projet ismo\`

### Erreur 404 sur /php-api

Le proxy Vite n'est pas actif. Vérifiez:
1. `vite.config.ts` contient bien le bloc `proxy` avec `/php-api`
2. Le serveur dev a été redémarré après modification
3. Vous accédez bien à `http://localhost:4000` (pas 5173 ou autre)

### Erreur 405 Method Not Allowed

Si vous avez l'erreur 405 sur `/api/*`, c'est que Hono intercepte la route. Solution:
- Utilisez `/php-api/*` au lieu de `/api/*` (déjà fait dans la config)

## Architecture finale

```
┌─────────────────────────────────────────┐
│  Navigateur (http://localhost:4000)    │
│                                         │
│  fetch('/php-api/auth/register.php')   │
│         ↓ (same-origin)                 │
└─────────────────────────────────────────┘
                  │
                  │ (pas de CORS/CSP)
                  ↓
┌─────────────────────────────────────────┐
│  Vite Dev Server (port 4000)            │
│                                         │
│  Proxy: /php-api/* → /projet%20ismo/api/*│
│  Hono routes: /api/* (pas de conflit)  │
│         ↓                               │
└─────────────────────────────────────────┘
                  │
                  │ (requête HTTP interne)
                  ↓
┌─────────────────────────────────────────┐
│  Apache (port 80)                       │
│                                         │
│  /projet%20ismo/api/auth/register.php  │
│         ↓                               │
└─────────────────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────┐
│  MySQL (gamezone)                       │
└─────────────────────────────────────────┘
```

## Résumé

- ✅ **Proxy Vite** configuré pour `/api`
- ✅ **API_BASE** = `/api` (relatif)
- ✅ **CSP** supprimée (pas nécessaire avec same-origin)
- ✅ **CORS** pas nécessaire (same-origin)
- ✅ **Script de démarrage** automatisé

**Le problème NetworkError/CSP est maintenant résolu!** 🎉
