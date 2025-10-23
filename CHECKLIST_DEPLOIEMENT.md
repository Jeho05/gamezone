# ✅ Checklist de Déploiement GameZone

Suivez cette checklist dans l'ordre. Cochez au fur et à mesure.

---

## 🎯 Phase 1 : Préparation (15 minutes)

### Compte InfinityFree
- [ ] Compte créé sur [infinityfree.net](https://infinityfree.net)
- [ ] Email confirmé
- [ ] Connexion au panneau de contrôle réussie

### Site Web
- [ ] Nouveau site créé ("Create Account")
- [ ] Sous-domaine choisi : `______________________.infinityfreeapp.com`
- [ ] Identifiants notés :
  ```
  Username FTP : epiz_______________
  Password : ___________________
  FTP Host : ftpupload.net
  MySQL Host : sql___.infinityfreeapp.com
  ```

### Base de Données
- [ ] Base MySQL créée (nom : `gamezone`)
- [ ] Nom complet noté : `epiz_______________gamezone`
- [ ] Accès phpMyAdmin vérifié
- [ ] Structure importée depuis `api/database/schema.sql`

---

## 🔨 Phase 2 : Build Local (10 minutes)

### Configuration Frontend
- [ ] Fichier `.env.production` créé (copie de `.env.production.example`)
- [ ] Variable `NEXT_PUBLIC_API_BASE` modifiée avec votre domaine
- [ ] Variable `NEXT_PUBLIC_KKIAPAY_SANDBOX` = 0 (mode production)

### Configuration Backend
- [ ] Fichier `api/.env` créé (copie de `api/.env.example`)
- [ ] Variables DB remplies (host, name, user, pass)
- [ ] Variable `APP_URL` remplie avec votre domaine

### Build
- [ ] PowerShell ouvert dans le dossier du projet
- [ ] Commande `.\BUILD_PRODUCTION.ps1` exécutée
- [ ] Build terminé sans erreur
- [ ] Dossier `production_build/` créé avec succès

---

## 📤 Phase 3 : Upload FTP (30 minutes)

### Préparation FileZilla
- [ ] FileZilla installé
- [ ] Connexion configurée :
  - Hôte : `ftpupload.net`
  - Utilisateur : `epiz_______________`
  - Mot de passe : `___________________`
  - Port : `21`
- [ ] Connexion établie avec succès

### Upload des Fichiers
- [ ] Navigation vers `/htdocs/` sur InfinityFree (panneau droit)
- [ ] Ancien contenu de `/htdocs/` supprimé (si existant)
- [ ] Tout le contenu de `production_build/` uploadé
- [ ] Vérification : `index.html` présent dans `/htdocs/`
- [ ] Vérification : dossier `/htdocs/api/` existe
- [ ] Vérification : dossier `/htdocs/assets/` existe
- [ ] Vérification : fichier `.htaccess` présent à la racine

### Fichier .env Backend
- [ ] Fichier `api/.env` vérifié sur le serveur
- [ ] Infos DB correctes dans `api/.env`

---

## 🔒 Phase 4 : Sécurité (10 minutes)

### SSL/HTTPS
- [ ] SSL Certificate activé (panneau InfinityFree)
- [ ] Attente 5-10 minutes pour activation
- [ ] Site accessible en HTTPS
- [ ] Redirection HTTP → HTTPS fonctionne

### Permissions
- [ ] Dossiers : 755
- [ ] Fichiers : 644
- [ ] Fichier `.env` : 600 (pas accessible publiquement)

---

## 🧪 Phase 5 : Tests (15 minutes)

### Tests Basiques
- [ ] Page d'accueil charge : `https://votre-nom.infinityfreeapp.com`
- [ ] Aucune erreur JavaScript (F12 → Console)
- [ ] Images s'affichent correctement
- [ ] CSS appliqué (design correct)

### Tests Authentification
- [ ] Page `/auth/login` accessible
- [ ] Login admin fonctionne : `admin@gamezone.fr` / `demo123`
- [ ] Déconnexion fonctionne
- [ ] Page `/auth/register` accessible
- [ ] Inscription nouveau joueur fonctionne
- [ ] Login avec nouveau joueur fonctionne

### Tests Fonctionnels
- [ ] Dashboard admin accessible : `/admin`
- [ ] Dashboard joueur accessible : `/player`
- [ ] Liste des jeux charge
- [ ] Profil utilisateur accessible
- [ ] Upload d'avatar fonctionne
- [ ] Classement s'affiche

### Tests API
- [ ] F12 → Network : Requêtes API en status 200
- [ ] Pas d'erreurs CORS
- [ ] Session maintenue après rafraîchissement

---

## 🐙 Phase 6 : GitHub (5 minutes)

### Repository GitHub
- [ ] Repository créé sur [github.com/new](https://github.com/new)
  - Nom : `gamezone`
  - Description remplie
  - Public ou Private choisi
  - **SANS** README initial
- [ ] URL du repo notée : `https://github.com/_______________/gamezone.git`

### Push Initial
- [ ] PowerShell ouvert dans le projet
- [ ] Commande `.\INIT_GITHUB.ps1` exécutée
- [ ] Remote ajouté avec succès
- [ ] Premier commit créé
- [ ] Push réussi vers GitHub
- [ ] Code visible sur GitHub.com

---

## 💳 Phase 7 : KkiaPay (5 minutes)

### Configuration Webhook
- [ ] Dashboard KkiaPay ouvert
- [ ] Webhook URL configurée :
  ```
  https://votre-nom.infinityfreeapp.com/api/shop/payment_callback.php
  ```
- [ ] Mode sandbox désactivé (production)
- [ ] Test paiement effectué avec petit montant
- [ ] Paiement reçu et confirmé

---

## 📊 Phase 8 : Monitoring (Optionnel)

### UptimeRobot
- [ ] Compte créé sur [uptimerobot.com](https://uptimerobot.com)
- [ ] Monitor ajouté pour votre site
- [ ] Alertes email configurées

### Google Analytics
- [ ] Compte créé
- [ ] Code tracking ajouté
- [ ] Visites trackées

---

## ✅ DÉPLOIEMENT TERMINÉ !

Félicitations ! Votre application GameZone est maintenant en ligne.

### 📝 Informations Importantes

**URLs de votre application :**
- 🏠 Accueil : `https://_____________________.infinityfreeapp.com`
- 👑 Admin : `https://_____________________.infinityfreeapp.com/admin`
- 🎮 Joueur : `https://_____________________.infinityfreeapp.com/player`

**Repository GitHub :**
- 📦 URL : `https://github.com/_______________/gamezone`

**Identifiants Admin :**
- 📧 Email : `admin@gamezone.fr`
- 🔑 Password : `demo123`

### 📋 Pour les Mises à Jour Futures

Quand vous modifiez le code localement :

```powershell
# 1. Reconstruire
.\BUILD_PRODUCTION.ps1

# 2. Re-uploader via FTP
# (seulement les fichiers modifiés)

# 3. Commit sur GitHub
git add .
git commit -m "Description des changements"
git push
```

### 🆘 En Cas de Problème

1. **Site ne charge pas** :
   - Vérifiez `.htaccess` présent
   - Vérifiez `api/.env` correctement configuré
   - Consultez phpMyAdmin (tables présentes ?)

2. **Erreur 500** :
   - Ouvrez File Manager → `logs/`
   - Vérifiez error.log
   - Vérifiez connexion DB

3. **API ne répond pas** :
   - F12 → Network (regardez les erreurs)
   - Vérifiez URL API dans `.env.production`
   - Testez directement : `https://votre-site/api/auth/check.php`

4. **Paiements ne fonctionnent pas** :
   - Vérifiez webhook KkiaPay
   - Mode sandbox désactivé ?
   - Consultez logs KkiaPay

### 📚 Documentation de Référence

- `DEPLOIEMENT_ETAPES.md` - Guide détaillé
- `DEPLOIEMENT_INFINITYFREE.md` - Documentation technique
- `README.md` - Documentation générale
- Forum InfinityFree - Support communautaire

---

**Date de déploiement** : _______________  
**Version** : 1.0  
**Statut** : ✅ EN PRODUCTION
