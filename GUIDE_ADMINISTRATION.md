# 📚 Guide d'Administration GameZone

## 🎯 Vue d'ensemble

Ce système d'administration permet de gérer l'ensemble du contenu de GameZone :
- **Événements** : Tous types d'événements (tournois, streams, actualités, événements généraux)
- **Galerie** : Gestion des images et médias
- **Tournois** : Détails spécifiques aux compétitions
- **Streams** : Gestion des sessions de streaming
- **Actualités** : News et annonces

## 🚀 Démarrage Rapide

### 1. Installation de la Base de Données

Exécutez le schéma SQL mis à jour :

```bash
mysql -u root -p gamezone < api/schema.sql
```

Ou importez-le via phpMyAdmin.

### 2. Connexion Administrateur

**URL d'accès** : `http://localhost/admin/login.html`

**Compte par défaut** :
- Email: `admin@gmail.com`
- Mot de passe: `demo123`

⚠️ **Important** : Changez ce mot de passe immédiatement en production !

### 3. Accéder au Dashboard

Une fois connecté, vous accédez au dashboard principal à : `http://localhost/admin/index.html`

## 📊 Structure de la Base de Données

### Tables Principales

#### **events**
Table centrale pour tous les types de contenus :
- `id` : Identifiant unique
- `title` : Titre de l'événement
- `date` : Date de l'événement
- `type` : Type (tournament, event, stream, news)
- `image_url` : URL de l'image principale
- `description` : Description courte
- `content` : Contenu détaillé (HTML supporté)
- `status` : Statut (draft, published, archived)
- `participants` : Nombre de participants
- `winner` : Gagnant (pour les tournois)
- `likes` : Nombre de likes
- `created_by` : ID de l'administrateur créateur

#### **gallery**
Galerie d'images :
- `id` : Identifiant unique
- `title` : Titre de l'image
- `description` : Description
- `image_url` : URL de l'image originale
- `thumbnail_url` : URL de la miniature
- `category` : Catégorie (tournament, event, stream, general, vr, retro)
- `event_id` : Lien vers un événement (optionnel)
- `status` : Statut (active, archived)
- `display_order` : Ordre d'affichage
- `views` : Nombre de vues
- `likes` : Nombre de likes

#### **tournaments**
Détails spécifiques aux tournois :
- `event_id` : Lien vers l'événement parent
- `game_name` : Nom du jeu
- `platform` : Plateforme (PC, PS5, Xbox, etc.)
- `max_participants` : Nombre maximum de participants
- `current_participants` : Participants inscrits
- `prize_pool` : Dotation
- `registration_start/end` : Période d'inscription
- `tournament_start/end` : Dates du tournoi
- `rules` : Règlement
- `status` : Statut (upcoming, registration_open, in_progress, completed, cancelled)

#### **streams**
Détails des sessions de streaming :
- `event_id` : Lien vers l'événement parent
- `streamer_name` : Nom du streamer
- `platform` : Plateforme (Twitch, YouTube, etc.)
- `stream_url` : URL du stream
- `game_name` : Jeu streamé
- `scheduled_start/end` : Horaires prévus
- `actual_start/end` : Horaires réels
- `viewers_count` : Nombre de viewers
- `status` : Statut (scheduled, live, ended, cancelled)

## 🎨 Utilisation du Dashboard

### Navigation

Le dashboard est organisé en 6 onglets principaux :

1. **📊 Dashboard** : Vue d'ensemble et statistiques
2. **📅 Événements** : Gestion de tous les événements
3. **🖼️ Galerie** : Gestion des images
4. **🏆 Tournois** : Tournois spécifiques
5. **📹 Streams** : Sessions de streaming
6. **📰 Actualités** : News et annonces

### Créer un Événement

1. Cliquez sur l'onglet correspondant au type de contenu
2. Cliquez sur "➕ Nouvel Événement"
3. Remplissez le formulaire :
   - **Titre** : Nom de l'événement (requis)
   - **Date** : Date de l'événement (requis)
   - **Type** : Sélectionnez le type (requis)
   - **Statut** : draft (brouillon) ou published (publié)
   - **Image** : Uploadez une image
   - **Description** : Courte description
   - **Contenu** : Texte détaillé (supporte HTML)
   - **Participants** : Nombre de participants
   - **Gagnant** : Nom du gagnant (optionnel)
4. Cliquez sur "Sauvegarder"

### Upload d'Images

Le système gère automatiquement :
- ✅ Validation du type de fichier (JPEG, PNG, WebP, GIF)
- ✅ Limite de taille : 10MB maximum
- ✅ Création automatique de miniatures (400x400px)
- ✅ Stockage sécurisé dans `/uploads/images/`
- ✅ Protection contre l'exécution de code PHP

**Pour uploader une image** :
1. Cliquez sur la zone "📷 Cliquez pour uploader une image"
2. Sélectionnez votre fichier
3. L'image est automatiquement uploadée et affichée en aperçu
4. L'URL est enregistrée dans le formulaire

### Gérer la Galerie

1. Allez dans l'onglet **🖼️ Galerie**
2. Cliquez sur "➕ Ajouter une Image"
3. Remplissez :
   - **Titre** : Nom de l'image (requis)
   - **Image** : Upload de l'image (requis)
   - **Description** : Description (optionnel)
   - **Catégorie** : Type de contenu
   - **Statut** : Active ou Archivée
   - **Ordre d'affichage** : Numéro pour le tri (0 = premier)
4. Cliquez sur "Sauvegarder"

Les images sont affichées en grille avec aperçu, catégorie et actions.

### Statuts des Contenus

**Pour les Événements** :
- **Draft (Brouillon)** : Non visible publiquement, en cours de création
- **Published (Publié)** : Visible sur le site public
- **Archived (Archivé)** : Conservé mais non affiché

**Pour la Galerie** :
- **Active** : Visible sur le site
- **Archived** : Masqué mais conservé

### Modifier ou Supprimer

- **Modifier** : Cliquez sur "✏️ Modifier" pour éditer un élément
- **Supprimer** : Cliquez sur "🗑️ Supprimer" (confirmation demandée)

## 🔌 API Disponibles

### APIs d'Administration (Authentification requise)

#### Événements
```
GET    /api/admin/events.php              - Liste tous les événements
GET    /api/admin/events.php?id=X         - Détails d'un événement
POST   /api/admin/events.php              - Créer un événement
PUT    /api/admin/events.php?id=X         - Modifier un événement
DELETE /api/admin/events.php?id=X         - Supprimer un événement
```

#### Galerie
```
GET    /api/admin/gallery.php             - Liste toutes les images
GET    /api/admin/gallery.php?id=X        - Détails d'une image
POST   /api/admin/gallery.php             - Ajouter une image
PUT    /api/admin/gallery.php?id=X        - Modifier une image
DELETE /api/admin/gallery.php?id=X        - Supprimer une image
```

#### Upload
```
POST   /api/admin/upload.php              - Upload une image
DELETE /api/admin/upload.php              - Supprimer un fichier
```

### APIs Publiques (Pas d'authentification)

#### Événements Publics
```
GET /api/events/public.php                - Liste des événements publiés
GET /api/events/public.php?id=X           - Détails d'un événement
GET /api/events/public.php?type=tournament - Filtrer par type
```

**Paramètres** :
- `type` : tournament, event, stream, news
- `limit` : Nombre de résultats (max 100, défaut 20)
- `offset` : Pagination

#### Galerie Publique
```
GET  /api/gallery/public.php              - Liste des images actives
GET  /api/gallery/public.php?id=X         - Détails d'une image
POST /api/gallery/public.php              - Liker une image
```

**Paramètres** :
- `category` : general, tournament, event, stream, vr, retro
- `event_id` : Filtrer par événement
- `limit` : Nombre de résultats (max 200, défaut 50)
- `offset` : Pagination

## 🎨 Intégration Frontend

### Exemple : Afficher les Événements

```javascript
// Récupérer tous les événements publiés
async function loadEvents() {
    const response = await fetch('/api/events/public.php?limit=10');
    const data = await response.json();
    
    data.events.forEach(event => {
        console.log(event.title, event.date, event.type);
    });
}

// Récupérer un événement spécifique
async function loadEvent(id) {
    const response = await fetch(`/api/events/public.php?id=${id}`);
    const event = await response.json();
    
    console.log(event.title);
    console.log(event.content);
    console.log(event.gallery); // Images liées
}

// Filtrer par type
async function loadTournaments() {
    const response = await fetch('/api/events/public.php?type=tournament');
    const data = await response.json();
    
    return data.events;
}
```

### Exemple : Afficher la Galerie

```javascript
// Récupérer les images
async function loadGallery() {
    const response = await fetch('/api/gallery/public.php?limit=20');
    const data = await response.json();
    
    data.items.forEach(item => {
        // Utiliser thumbnail_url pour l'aperçu
        // Utiliser image_url pour l'image complète
        console.log(item.title, item.thumbnail_url);
    });
}

// Liker une image
async function likeImage(id) {
    const response = await fetch('/api/gallery/public.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    
    const result = await response.json();
    console.log('Nouveau nombre de likes:', result.likes);
}
```

### Exemple : Afficher les Tournois avec Détails

```javascript
async function loadTournamentDetails(eventId) {
    const response = await fetch(`/api/events/public.php?id=${eventId}`);
    const event = await response.json();
    
    if (event.type === 'tournament') {
        console.log('Jeu:', event.tournament_game);
        console.log('Participants max:', event.tournament_max_participants);
        console.log('Participants actuels:', event.tournament_current_participants);
        console.log('Prize Pool:', event.tournament_prize);
        console.log('Statut:', event.tournament_status);
        console.log('Début:', event.tournament_start);
    }
}
```

## 🔒 Sécurité

### Protection des APIs Admin

Toutes les APIs d'administration sont protégées par :
1. **Authentification** : Session PHP requise
2. **Vérification du rôle** : Seuls les admins peuvent accéder
3. **CORS** : Configuré pour localhost en développement

### Upload Sécurisé

- ✅ Validation stricte du type MIME
- ✅ Limite de taille (10MB)
- ✅ Génération de noms de fichiers aléatoires
- ✅ Stockage en dehors du contexte d'exécution PHP
- ✅ .htaccess empêche l'exécution de scripts

### Bonnes Pratiques

1. **Changez le mot de passe admin** immédiatement
2. **Utilisez HTTPS** en production
3. **Configurez CORS** pour votre domaine réel
4. **Sauvegardez régulièrement** la base de données et les uploads
5. **Limitez l'accès** au dossier /admin/ via .htaccess si nécessaire

## 📱 Responsive Design

Le dashboard est entièrement responsive et s'adapte à :
- 💻 Desktop (1400px+)
- 📱 Tablettes (768px - 1024px)
- 📱 Mobiles (< 768px)

## 🎨 Personnalisation

### Modifier les Couleurs

Éditez les variables CSS dans `/admin/index.html` :

```css
:root {
    --primary: #6366f1;      /* Couleur principale */
    --secondary: #8b5cf6;    /* Couleur secondaire */
    --success: #10b981;      /* Succès */
    --danger: #ef4444;       /* Danger */
    --warning: #f59e0b;      /* Avertissement */
}
```

### Ajouter des Champs Personnalisés

1. Modifiez le schéma SQL pour ajouter des colonnes
2. Ajoutez les champs dans le formulaire HTML
3. Mettez à jour le JavaScript pour inclure les nouveaux champs
4. Modifiez l'API PHP pour gérer les nouveaux champs

## 🐛 Dépannage

### Problèmes Courants

**1. "Accès refusé" à la connexion**
- Vérifiez que l'utilisateur existe dans la table `users`
- Vérifiez que `role = 'admin'`
- Vérifiez les sessions PHP

**2. Upload d'images échoue**
- Vérifiez les permissions du dossier `/uploads/` (755)
- Vérifiez la limite PHP `upload_max_filesize` dans php.ini
- Vérifiez que GD est activé pour la création de thumbnails

**3. "Database connection failed"**
- Vérifiez les paramètres dans `/api/config.php`
- Assurez-vous que MySQL/MariaDB est démarré
- Vérifiez que la base `gamezone` existe

**4. Images ne s'affichent pas**
- Vérifiez le fichier `/uploads/.htaccess`
- Vérifiez les URLs dans la base de données
- Vérifiez les permissions des fichiers

### Logs et Debug

Pour activer le mode debug, ajoutez dans `/api/config.php` :

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

⚠️ Ne jamais activer en production !

## 📞 Support

Pour toute question ou problème :
1. Consultez ce guide
2. Vérifiez les logs d'erreur PHP
3. Inspectez la console du navigateur (F12)
4. Vérifiez les réponses des APIs avec les DevTools (Network)

## 🚀 Prochaines Améliorations Possibles

- [ ] Système de commentaires sur les événements
- [ ] Notifications push pour les nouveaux événements
- [ ] Éditeur WYSIWYG pour le contenu
- [ ] Gestion des inscriptions aux tournois
- [ ] Statistiques avancées et analytics
- [ ] Export/Import de données
- [ ] Gestion multi-administrateurs avec permissions
- [ ] Historique des modifications
- [ ] Système de tags et recherche avancée
- [ ] Intégration calendrier
- [ ] API REST complète avec documentation Swagger

---

**Version** : 1.0.0  
**Dernière mise à jour** : Octobre 2025  
**Auteur** : GameZone Team
