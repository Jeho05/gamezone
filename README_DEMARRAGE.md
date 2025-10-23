# GameZone - Guide de démarrage

## Architecture du projet

- **Backend PHP**: `api/` - API REST avec MySQL
- **Frontend React**: `createxyz-project/_/apps/web/` - Interface utilisateur

## Prérequis

1. **XAMPP** installé avec Apache et MySQL démarrés
2. **Node.js** (v18+) et npm installés

## Étape 1: Démarrer le backend (XAMPP)

### 1.1 Démarrer Apache et MySQL
- Ouvrez XAMPP Control Panel
- Cliquez sur "Start" pour Apache
- Cliquez sur "Start" pour MySQL

### 1.2 Initialiser la base de données
Ouvrez votre navigateur et accédez à:
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

### 1.3 Vérifier l'API
Testez quelques endpoints:
- http://localhost/projet%20ismo/api/events/index.php (liste des événements)
- http://localhost/projet%20ismo/api/leaderboard/index.php?period=weekly (classement)

## Étape 2: Démarrer le frontend (React)

### 2.1 Installer les dépendances
```bash
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
npm install
```

### 2.2 Démarrer le serveur de développement
```bash
npm run dev
```

Le serveur démarre sur **http://localhost:4000**

### 2.3 Accéder à l'application
Ouvrez votre navigateur:
```
http://localhost:4000
```

## Étape 3: Tester l'application

### Compte Admin par défaut
- **Email**: admin@gamezone.fr
- **Mot de passe**: demo123

### Parcours de test

#### Joueur
1. Cliquez sur "S'inscrire"
2. Créez un compte joueur
3. Connectez-vous
4. Dashboard: réclamez votre bonus journalier (25 points)
5. Allez dans Classements pour voir votre rang
6. Allez dans Galerie pour liker des événements

#### Admin
1. Connectez-vous avec le compte admin
2. Allez dans "Gestion des joueurs"
3. Ajustez les points d'un joueur
4. Vérifiez que le classement se met à jour

## Configuration

### Ports
- **Frontend**: http://localhost:4000
- **Backend**: http://localhost/projet%20ismo/api

### Proxy Vite
Le serveur Vite (port 4000) proxifie automatiquement `/api` vers le backend PHP.
Cela évite les problèmes CORS pendant le développement.

### Variables d'environnement (optionnel)
Créez un fichier `.env` dans `api/` si besoin:
```env
DB_HOST=127.0.0.1
DB_NAME=gamezone
DB_USER=root
DB_PASS=
```

## Structure de la base de données

### Tables principales
- **users**: Joueurs et admins
- **points_transactions**: Historique des points
- **rewards**: Catalogue de récompenses
- **reward_redemptions**: Échanges de récompenses
- **events**: Événements, tournois, news
- **daily_bonuses**: Suivi des bonus journaliers

### Données de seed
- 1 admin: admin@gamezone.fr / demo123
- 3 récompenses (1h jeu, boisson, t-shirt)
- 5 événements (tournois, streams, news)

## Dépannage

### "Method Not Allowed" sur login/register
- Vérifiez qu'Apache est bien démarré
- Vérifiez que vous accédez via `http://localhost:4000` (pas file://)
- Redémarrez le serveur Vite: Ctrl+C puis `npm run dev`

### Erreur de connexion base de données
- Vérifiez que MySQL est démarré dans XAMPP
- Vérifiez les paramètres dans `api/config.php`
- Accédez à phpMyAdmin: http://localhost/phpmyadmin

### CORS errors
- Le proxy Vite est configuré, donc pas de problème CORS en dev
- Si vous déployez en production, adaptez `api/config.php` ligne 22-34

### Port 4000 déjà utilisé
Modifiez le port dans `createxyz-project/_/apps/web/vite.config.ts` ligne 84:
```ts
port: 4001,  // ou un autre port disponible
```

## Endpoints API disponibles

### Auth
- POST `/api/auth/login.php` - Connexion
- POST `/api/auth/register.php` - Inscription
- POST `/api/auth/logout.php` - Déconnexion
- GET `/api/auth/me.php` - Utilisateur courant
- POST `/api/auth/change_password.php` - Changer mot de passe

### Users (admin only)
- GET `/api/users/index.php` - Liste des utilisateurs
- POST `/api/users/index.php` - Créer un utilisateur
- GET `/api/users/item.php?id=X` - Détail utilisateur
- PUT `/api/users/item.php?id=X` - Modifier utilisateur
- DELETE `/api/users/item.php?id=X` - Supprimer utilisateur

### Points
- POST `/api/points/adjust.php` - Ajuster points (admin)
- POST `/api/points/bonus.php` - Réclamer bonus journalier
- GET `/api/points/history.php` - Historique des points

### Leaderboard
- GET `/api/leaderboard/index.php?period=weekly` - Classement (weekly/monthly/all)

### Rewards
- GET `/api/rewards/index.php` - Liste des récompenses
- POST `/api/rewards/index.php` - Créer/modifier récompense (admin)
- POST `/api/rewards/redeem.php` - Échanger une récompense
- POST `/api/rewards/delete.php` - Supprimer récompense (admin)

### Events
- GET `/api/events/index.php` - Liste des événements
- POST `/api/events/index.php` - Créer événement (admin)
- POST `/api/events/like.php` - Liker un événement

### Upload
- POST `/api/upload/index.php` - Upload fichier (multipart/base64/url)

## Développement

### Logs
- **Apache**: `c:\xampp\apache\logs\error.log`
- **MySQL**: `c:\xampp\mysql\data\*.err`
- **Vite**: Console du terminal

### Hot reload
- Le frontend se recharge automatiquement à chaque modification
- Le backend PHP s'exécute à chaque requête (pas de restart nécessaire)

### Ajout d'endpoints
1. Créez le fichier PHP dans `api/`
2. Utilisez `require_once __DIR__ . '/../utils.php'`
3. Appelez `require_method([...])` pour valider la méthode HTTP
4. Retournez via `json_response([...], status)`

### Ajout de pages front
1. Créez `page.jsx` dans `src/app/chemin/`
2. Utilisez `API_BASE` pour les appels API
3. Ajoutez `credentials: 'include'` pour les sessions
4. Gérez loading/error states

## Production

Pour déployer en production:
1. Buildez le front: `npm run build`
2. Copiez le dossier `build/` sur votre serveur
3. Configurez Apache pour servir le front et l'API
4. Mettez à jour les origines CORS dans `api/config.php`
5. Sécurisez MySQL (mot de passe root, accès distant)

## Support

En cas de problème:
1. Vérifiez les logs Apache/MySQL
2. Ouvrez la console DevTools (F12) pour les erreurs JS
3. Testez les endpoints API directement dans le navigateur
4. Vérifiez que tous les services sont démarrés
