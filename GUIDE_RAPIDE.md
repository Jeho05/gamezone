# Guide de démarrage rapide - GameZone

## ✅ Problèmes résolus

1. **"Method Not Allowed"** → CORS configuré correctement
2. **"JSON.parse error"** → Warnings PHP supprimés, gestion d'erreurs améliorée
3. **Proxy Vite** → Retiré (l'espace dans "projet ismo" causait des problèmes)
4. **API Base URL** → Hardcodée dans `root.tsx` et `apiBase.js`

## 🚀 Démarrage en 3 étapes

### 1. Démarrer XAMPP
- Ouvrez XAMPP Control Panel
- Start **Apache**
- Start **MySQL**

### 2. Installer la base de données
Ouvrez dans votre navigateur:
```
http://localhost/projet%20ismo/api/install.php
```

Vous devriez voir:
```json
{
  "message": "Installation terminée",
  "database": "gamezone"
}
```

### 3. Démarrer le frontend
```powershell
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
npm run dev
```

Puis ouvrez: **http://localhost:4000**

## 🧪 Tester l'API directement

### Test d'inscription (PowerShell):
```powershell
cd "c:\xampp\htdocs\projet ismo"
.\test_register.ps1
```

### Test manuel (navigateur):
```javascript
// Ouvrez la console (F12) sur http://localhost:4000

// Test inscription
fetch('http://localhost/projet%20ismo/api/auth/register.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include',
  body: JSON.stringify({
    username: 'TestUser',
    email: 'test@example.com',
    password: 'password123'
  })
}).then(r => r.json()).then(console.log);

// Test login admin
fetch('http://localhost/projet%20ismo/api/auth/login.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include',
  body: JSON.stringify({
    email: 'admin@gamezone.fr',
    password: 'demo123'
  })
}).then(r => r.json()).then(console.log);
```

## 🔐 Comptes de test

**Admin:**
- Email: `admin@gamezone.fr`
- Password: `demo123`

**Joueur:**
- Créez un compte via http://localhost:4000/auth/register

## 📡 URL de l'API

L'API est accessible sur:
```
http://localhost/projet%20ismo/api
```

Le front (port 4000) communique directement avec cette URL (pas de proxy).

## ✅ Vérification

Lancez le script de vérification:
```powershell
cd "c:\xampp\htdocs\projet ismo"
.\verifier.ps1
```

Il vérifie:
- Apache démarré ✓
- MySQL démarré ✓
- API accessible ✓
- Base de données initialisée ✓
- Frontend installé ✓

## 🐛 Dépannage

### Problème: "Erreur serveur (réponse invalide)"

**Cause:** Apache/MySQL non démarrés ou base non initialisée

**Solution:**
1. Vérifiez XAMPP Control Panel (Apache et MySQL verts)
2. Accédez à http://localhost/projet%20ismo/api/test.php
3. Si erreur 404: vérifiez que le dossier existe
4. Si erreur DB: lancez http://localhost/projet%20ismo/api/install.php

### Problème: "CORS error"

**Cause:** Headers CORS manquants

**Solution:**
- Vérifiez que `api/config.php` contient le port 4000 dans `$allowed_origins`
- Vérifiez que `api/.htaccess` existe
- Redémarrez Apache

### Problème: Email déjà utilisé

**Cause:** Vous avez déjà créé un compte avec cet email

**Solution:**
- Utilisez un autre email
- Ou connectez-vous avec l'email existant
- Ou supprimez la BDD et réinstallez: http://localhost/phpmyadmin → Drop database `gamezone`, puis réexécutez install.php

### Problème: Port 4000 déjà utilisé

**Solution:**
Changez le port dans `vite.config.ts`:
```ts
server: {
  port: 4001,  // ou un autre port
}
```

Et dans `root.tsx`:
```ts
window.APP_API_BASE = 'http://localhost/projet%20ismo/api';
// Pas de changement nécessaire
```

## 📊 Structure du projet

```
c:\xampp\htdocs\projet ismo\
├── api/                           # Backend PHP
│   ├── auth/                      # login, register, logout, me
│   ├── users/                     # CRUD users (admin)
│   ├── points/                    # adjust, bonus, history
│   ├── leaderboard/               # weekly/monthly/all
│   ├── rewards/                   # list, create, redeem, delete
│   ├── events/                    # list, create, like
│   ├── upload/                    # file uploads
│   ├── config.php                 # DB + CORS
│   ├── utils.php                  # Helpers
│   ├── install.php                # DB installation
│   └── .htaccess                  # Apache config
│
├── createxyz-project/_/apps/web/  # Frontend React
│   ├── src/
│   │   ├── app/
│   │   │   ├── auth/              # login, register
│   │   │   ├── player/            # dashboard, leaderboard, gallery
│   │   │   ├── admin/             # players management
│   │   │   └── root.tsx           # ← API_BASE défini ici
│   │   ├── components/
│   │   └── utils/
│   │       └── apiBase.js         # ← Config API
│   └── vite.config.ts             # ← Port 4000, pas de proxy
│
├── README_DEMARRAGE.md            # Guide complet
├── GUIDE_RAPIDE.md                # Ce fichier
├── demarrer.ps1                   # Script de démarrage
├── verifier.ps1                   # Script de vérification
└── test_register.ps1              # Test d'inscription
```

## 🎯 Endpoints principaux

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/auth/login.php` | POST | Connexion |
| `/auth/register.php` | POST | Inscription |
| `/auth/logout.php` | POST | Déconnexion |
| `/auth/me.php` | GET | Utilisateur courant |
| `/users/index.php` | GET | Liste utilisateurs (admin) |
| `/points/adjust.php` | POST | Ajuster points (admin) |
| `/points/bonus.php` | POST | Réclamer bonus journalier |
| `/leaderboard/index.php?period=weekly` | GET | Classement |
| `/rewards/index.php` | GET | Liste récompenses |
| `/rewards/redeem.php` | POST | Échanger récompense |
| `/events/index.php` | GET | Liste événements |
| `/events/like.php` | POST | Liker un événement |

## 📞 Support

Si un problème persiste:

1. **Vérifiez les logs Apache:**
   ```
   c:\xampp\apache\logs\error.log
   ```

2. **Console du navigateur (F12 > Console):**
   - Regardez les erreurs rouges
   - Vérifiez les requêtes réseau (onglet Network)

3. **Testez l'API directement:**
   ```
   http://localhost/projet%20ismo/api/test.php
   ```

4. **Vérifiez phpMyAdmin:**
   ```
   http://localhost/phpmyadmin
   ```
   - Base `gamezone` doit exister
   - Tables: users, points_transactions, rewards, events, etc.

## ✨ Prêt!

Votre application GameZone est maintenant configurée et prête à l'emploi!

1. ✅ Backend PHP opérationnel
2. ✅ Base de données initialisée
3. ✅ Frontend React branché
4. ✅ CORS configuré correctement
5. ✅ Pas de proxy (connexion directe)

**Lancez l'application:**
```powershell
cd "c:\xampp\htdocs\projet ismo"
.\demarrer.ps1
```

Puis rendez-vous sur: **http://localhost:4000** 🎮
