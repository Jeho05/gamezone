# 🎮 GameZone - Plateforme Gaming

Système complet de gestion de gaming lounge avec points, classements, récompenses et événements.

## 🚀 Démarrage rapide

### Option 1: Script automatique (RECOMMANDÉ)

```powershell
cd "c:\xampp\htdocs\projet ismo"
.\START.ps1
```

Ce script va:
- ✅ Vérifier Apache et MySQL
- ✅ Installer la base de données automatiquement
- ✅ Installer les dépendances npm
- ✅ Tester l'API
- ✅ Démarrer le serveur dev

### Option 2: Manuel

**1. Démarrez XAMPP**
```
Apache + MySQL dans XAMPP Control Panel
```

**2. Installez la base de données**
```
http://localhost/projet%20ismo/api/install.php
```

**3. Démarrez le frontend**
```powershell
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
npm install  # première fois seulement
npm run dev
```

**4. Ouvrez l'application**
```
http://localhost:4000
```

## 🔐 Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| **Admin** | admin@gamezone.fr | demo123 |
| **Joueur** | (créez via /auth/register) | - |

## 📋 Fonctionnalités

### 🎯 Pour les Joueurs
- ✅ Inscription / Connexion avec avatar
- ✅ Dashboard personnel avec statistiques
- ✅ Bonus journalier de points (25 pts/jour)
- ✅ Consultation de l'historique des points
- ✅ Échange de récompenses contre des points
- ✅ Classement hebdomadaire/mensuel avec podium
- ✅ Galerie d'événements et actualités
- ✅ Like sur les événements

### 👑 Pour les Admins
- ✅ Gestion complète des utilisateurs (CRUD)
- ✅ Ajustement manuel des points
- ✅ Création/modification/suppression de récompenses
- ✅ Création d'événements et tournois
- ✅ Vue d'ensemble des statistiques

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────┐
│                  Frontend React                      │
│              http://localhost:4000                   │
│  ┌──────────┬──────────┬───────────┬──────────┐    │
│  │  Auth    │  Player  │   Admin   │  Gallery │    │
│  │  Pages   │Dashboard │  Panel    │  Events  │    │
│  └──────────┴──────────┴───────────┴──────────┘    │
└───────────────────┬─────────────────────────────────┘
                    │ fetch() + credentials
                    │ http://localhost/projet%20ismo/api
┌───────────────────▼─────────────────────────────────┐
│                  Backend PHP API                     │
│         http://localhost/projet%20ismo/api           │
│  ┌──────┬───────┬────────┬──────────┬─────────┐    │
│  │ Auth │ Users │ Points │ Rewards  │ Events  │    │
│  └──────┴───────┴────────┴──────────┴─────────┘    │
└───────────────────┬─────────────────────────────────┘
                    │ PDO
┌───────────────────▼─────────────────────────────────┐
│              MySQL Database (gamezone)               │
│  users • points_transactions • rewards • events     │
└─────────────────────────────────────────────────────┘
```

## 📡 API Endpoints

### Authentification
| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/auth/register.php` | POST | Inscription (username, email, password) |
| `/auth/login.php` | POST | Connexion (email, password) |
| `/auth/logout.php` | POST | Déconnexion |
| `/auth/me.php` | GET | Utilisateur courant |
| `/auth/change_password.php` | POST | Changer mot de passe |

### Users (Admin only)
| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/users/index.php` | GET | Liste des utilisateurs |
| `/users/index.php` | POST | Créer un utilisateur |
| `/users/item.php?id=X` | GET | Détails utilisateur |
| `/users/item.php?id=X` | PUT | Modifier utilisateur |
| `/users/item.php?id=X` | DELETE | Supprimer utilisateur |

### Points
| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/points/adjust.php` | POST | Ajuster points (admin) |
| `/points/bonus.php` | POST | Réclamer bonus journalier |
| `/points/history.php` | GET | Historique des points |

### Leaderboard
| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/leaderboard/index.php?period=weekly` | GET | Classement (weekly/monthly/all) |

### Rewards
| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/rewards/index.php` | GET | Liste des récompenses |
| `/rewards/index.php` | POST | Créer/modifier récompense (admin) |
| `/rewards/redeem.php` | POST | Échanger une récompense |
| `/rewards/delete.php` | POST | Supprimer récompense (admin) |

### Events
| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/events/index.php` | GET | Liste des événements |
| `/events/index.php` | POST | Créer un événement (admin) |
| `/events/like.php` | POST | Liker un événement |

### Upload
| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/upload/index.php` | POST | Upload fichier (multipart/base64/url) |

## 🗄️ Base de données

### Tables principales

**users**
- Joueurs et administrateurs
- Points, niveau, statut
- Avatar, email, mot de passe hashé

**points_transactions**
- Historique de tous les mouvements de points
- Type: game, tournament, bonus, reward, adjustment
- Lien vers l'admin qui a fait l'ajustement

**rewards**
- Catalogue de récompenses
- Coût en points
- Stock disponible

**reward_redemptions**
- Historique des échanges
- Statut: pending, completed, cancelled

**events**
- Tournois, actualités, streams
- Type, date, lieu
- Nombre de likes

**daily_bonuses**
- Suivi des bonus réclamés par jour
- 1 bonus max par jour par utilisateur

## 🔧 Configuration

### Backend (api/config.php)

```php
// Base de données
$DB_HOST = '127.0.0.1';
$DB_NAME = 'gamezone';
$DB_USER = 'root';
$DB_PASS = '';

// CORS activé pour localhost:4000
```

### Frontend (src/app/root.tsx)

```javascript
// URL de l'API
window.APP_API_BASE = 'http://localhost/projet%20ismo/api';
```

## 🧪 Tests

### Test complet de l'API
```powershell
.\test_register.ps1  # Test d'inscription
.\verifier.ps1       # Vérification complète
```

### Test manuel dans le navigateur
```javascript
// Console (F12) sur http://localhost:4000

// Inscription
fetch('http://localhost/projet%20ismo/api/auth/register.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include',
  body: JSON.stringify({
    username: 'Test',
    email: 'test@example.com',
    password: 'password123'
  })
}).then(r => r.json()).then(console.log);

// Login
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

## 📦 Technologies

### Backend
- **PHP 7.4+** - Langage serveur
- **MySQL 5.7+** - Base de données
- **PDO** - Accès base de données
- **Sessions** - Authentification

### Frontend
- **React 18** - Framework UI
- **React Router v7** - Routing
- **Vite** - Build tool
- **TailwindCSS** - Styling
- **Lucide React** - Icons

## 🐛 Dépannage

### Apache/MySQL ne démarre pas
```
Vérifiez les logs:
c:\xampp\apache\logs\error.log
c:\xampp\mysql\data\*.err
```

### Erreur de connexion à la base
```
1. Vérifiez MySQL dans XAMPP
2. Accédez à phpMyAdmin: http://localhost/phpmyadmin
3. Vérifiez que la base 'gamezone' existe
4. Relancez install.php si nécessaire
```

### "Method Not Allowed" ou CORS error
```
1. Vérifiez api/config.php (ligne 27: port 4000)
2. Vérifiez api/.htaccess existe
3. Redémarrez Apache
```

### Port 4000 déjà utilisé
```
Changez le port dans vite.config.ts:
server: { port: 4001 }
```

### Email déjà utilisé
```
Soit:
- Utilisez un autre email
- Connectez-vous avec l'existant
- Supprimez la BDD dans phpMyAdmin et réinstallez
```

## 📝 Scripts disponibles

| Script | Description |
|--------|-------------|
| `START.ps1` | **Démarrage complet automatique** |
| `verifier.ps1` | Vérifier l'installation |
| `test_register.ps1` | Tester l'API d'inscription |
| `demarrer.ps1` | Démarrage guidé |

## 📚 Documentation complète

- **GUIDE_RAPIDE.md** - Guide de démarrage simplifié
- **README_DEMARRAGE.md** - Documentation détaillée avec tous les endpoints

## 🌐 Déploiement en Production

### Sur InfinityFree (Gratuit)

**1. Build de l'application**
```powershell
.\BUILD_PRODUCTION.ps1
```

**2. Suivez le guide complet**
```
DEPLOIEMENT_INFINITYFREE.md
```

**3. Push sur GitHub**
```powershell
.\INIT_GITHUB.ps1
```

Ce script vous guidera pour :
- ✅ Créer le repository Git local
- ✅ Faire le premier commit
- ✅ Pousser sur GitHub
- ✅ Conserver l'historique du projet

### Autres hébergeurs supportés

- **Hostinger** (2-3€/mois) - Recommandé pour production
- **o2switch** (5€/mois) - Hébergeur français
- **Vercel** (Frontend uniquement) - Gratuit
- **Railway.app** (Backend) - 5$ gratuit

## 🎯 Roadmap

- [x] Authentification complète
- [x] Système de points
- [x] Classements hebdo/mensuel
- [x] Récompenses échangeables
- [x] Événements et likes
- [x] Upload d'avatars
- [x] Admin panel
- [x] Système de réservations
- [x] Paiement KkiaPay
- [x] Scanner de factures QR
- [x] Monitoring et backups
- [ ] Notifications en temps réel
- [ ] Export CSV/PDF
- [ ] Stats avancées (graphiques)
- [ ] Mode sombre
- [x] Responsive mobile

## 📄 Licence

Projet éducatif - Tous droits réservés

## 👥 Support

En cas de problème:
1. Consultez **GUIDE_RAPIDE.md**
2. Lancez `.\verifier.ps1`
3. Vérifiez les logs Apache
4. Testez l'API directement

---

**Fait avec ❤️ pour GameZone**
