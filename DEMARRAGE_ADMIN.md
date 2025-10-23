# 🚀 Démarrage Rapide - Administration GameZone

## ⚡ Installation en 3 étapes

### 1️⃣ Mettre à jour la base de données

Choisissez une option :

**Option A : Base de données vierge**
```bash
mysql -u root -p gamezone < api/schema.sql
```

**Option B : Base existante (migration)**
```bash
mysql -u root -p gamezone < api/migrations/add_admin_features.sql
```

### 2️⃣ Créer les dossiers d'upload

Les dossiers sont créés automatiquement au premier upload, mais vous pouvez les créer manuellement :

```bash
mkdir -p uploads/images uploads/thumbnails
chmod 755 uploads uploads/images uploads/thumbnails
```

### 3️⃣ Accéder au dashboard

1. **Connexion** : http://localhost/admin/login.html
   - Email : `admin@gmail.com`
   - Mot de passe : `demo123`

2. **Dashboard** : http://localhost/admin/index.html

## 📋 Checklist Rapide

- [ ] Base de données mise à jour
- [ ] Dossiers uploads créés avec bonnes permissions
- [ ] Connexion admin fonctionnelle
- [ ] Upload d'images testé
- [ ] Événement de test créé
- [ ] Image de test ajoutée à la galerie

## 🎯 Actions Essentielles

### Créer un nouvel événement
1. Onglet "Événements" → "Nouvel Événement"
2. Remplir titre, date, type
3. Uploader une image
4. Statut "Published" pour le rendre visible
5. Sauvegarder

### Ajouter une image à la galerie
1. Onglet "Galerie" → "Ajouter une Image"
2. Titre + Upload image
3. Choisir catégorie
4. Sauvegarder

### Créer un tournoi
1. Onglet "Tournois" → "Nouveau Tournoi"
2. Remplir les informations
3. Type doit être "tournament"
4. Sauvegarder

## 🔌 Tester les APIs

### API Publique - Événements
```bash
curl http://localhost/api/events/public.php
```

### API Publique - Galerie
```bash
curl http://localhost/api/gallery/public.php
```

### API Admin - Liste événements (nécessite authentification)
```bash
curl -b cookies.txt http://localhost/api/admin/events.php
```

## 📚 Documentation Complète

Consultez **GUIDE_ADMINISTRATION.md** pour :
- Documentation complète des APIs
- Structure de la base de données
- Exemples d'intégration frontend
- Sécurité et bonnes pratiques
- Dépannage

## 🛠️ Fichiers Créés

### Backend (API PHP)
- `/api/schema.sql` - Schéma complet de base de données
- `/api/migrations/add_admin_features.sql` - Migration pour BDD existante
- `/api/admin/auth_check.php` - Middleware authentification admin
- `/api/admin/events.php` - CRUD événements
- `/api/admin/gallery.php` - CRUD galerie
- `/api/admin/upload.php` - Upload d'images sécurisé
- `/api/auth/check.php` - Vérification authentification
- `/api/events/public.php` - API publique événements
- `/api/gallery/public.php` - API publique galerie

### Frontend (Interface Admin)
- `/admin/index.html` - Dashboard principal
- `/admin/admin.js` - Logique JavaScript
- `/admin/login.html` - Page de connexion
- `/admin/.htaccess` - Sécurité dossier admin

### Configuration
- `/uploads/.htaccess` - Sécurité uploads

### Documentation
- `/GUIDE_ADMINISTRATION.md` - Guide complet
- `/DEMARRAGE_ADMIN.md` - Ce fichier

## ⚠️ Important

### Sécurité
1. **CHANGEZ LE MOT DE PASSE ADMIN** immédiatement !
2. En production, utilisez HTTPS
3. Configurez CORS pour votre domaine
4. Limitez l'accès à /admin/ si nécessaire

### Permissions
```bash
# Sur Linux/Mac
chmod 755 uploads uploads/images uploads/thumbnails
chmod 644 uploads/.htaccess

# Propriétaire = serveur web (www-data, apache, nginx, etc.)
chown -R www-data:www-data uploads
```

## 🎨 Fonctionnalités

✅ Dashboard avec statistiques en temps réel  
✅ Gestion complète des événements (CRUD)  
✅ Galerie d'images avec upload sécurisé  
✅ Création automatique de miniatures  
✅ Gestion des tournois avec détails  
✅ Gestion des streams  
✅ Système de statuts (brouillon, publié, archivé)  
✅ Interface responsive (mobile, tablette, desktop)  
✅ APIs publiques pour intégration frontend  
✅ Sécurité renforcée (validation, authentification)  

## 🎯 Prochaines Étapes

1. Testez la création d'événements
2. Uploadez quelques images à la galerie
3. Intégrez les APIs publiques à votre frontend
4. Personnalisez le design si nécessaire
5. Configurez la sécurité pour la production

## 💡 Astuce

Pour tester rapidement, créez quelques événements avec les statuts "published" pour les voir apparaître via l'API publique :

```javascript
fetch('/api/events/public.php')
  .then(r => r.json())
  .then(data => console.log(data.events));
```

---

**Besoin d'aide ?** Consultez le GUIDE_ADMINISTRATION.md complet !
