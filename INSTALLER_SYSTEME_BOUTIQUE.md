# 🎮 Guide d'Installation - Système de Boutique de Jeux

## Vue d'Ensemble

Ce système permet aux utilisateurs d'**acheter du temps de jeu** sur différents jeux, de **gagner des points**, et de gérer les paiements via plusieurs méthodes. L'administrateur a un **contrôle total** sur:

- Les jeux disponibles
- Les packages de temps de jeu (durées, prix, points)
- Les méthodes de paiement acceptées
- La confirmation des paiements
- Le suivi des sessions de jeu

---

## 📋 Prérequis

- XAMPP installé et démarré (Apache + MySQL)
- Base de données `gamezone` existante
- Accès admin au système

---

## 🚀 Installation en 3 Étapes

### Étape 1: Exécuter la Migration SQL

Ouvrez votre navigateur et accédez à:

```
http://localhost/projet%20ismo/api/run_migration.php?file=add_game_purchase_system.sql
```

**Résultat attendu:** Message de succès confirmant la création des tables

**Tables créées:**
- `games` - Catalogue des jeux
- `game_packages` - Packages de temps avec tarifs
- `payment_methods` - Méthodes de paiement disponibles
- `purchases` - Achats effectués
- `game_sessions` - Sessions de jeu actives
- `session_activities` - Historique des activités
- `payment_transactions` - Transactions de paiement

**Données de démonstration incluses:**
- 8 jeux populaires (FIFA, Call of Duty, GTA V, etc.)
- Packages variés pour chaque jeu
- 5 méthodes de paiement (Espèces, Carte, PayPal, Mobile Money)

### Étape 2: Accéder à l'Interface Admin

URL: `http://localhost/projet%20ismo/admin/game_shop_manager.html`

**Connexion requise:** Utilisateur admin

**Fonctionnalités disponibles:**

#### 📦 Gestion des Jeux
- Ajouter/Modifier/Supprimer des jeux
- Configurer: nom, catégorie, description, images
- Définir: points/heure, prix de base, plateforme
- Activer/Désactiver, mettre en avant

#### 💰 Gestion des Packages
- Créer des offres de temps (30min, 1h, 3h, etc.)
- Définir prix et points gagnés
- Créer des promotions (-20%, BEST VALUE, etc.)
- Ajouter des bonus multiplicateurs
- Limiter les achats par utilisateur

#### 💳 Gestion des Paiements
- Activer/Désactiver les méthodes
- Configurer paiement en ligne ou sur place
- Définir confirmation auto ou manuelle
- Ajouter instructions pour l'utilisateur

#### 🛒 Gestion des Achats
- Voir tous les achats en temps réel
- Confirmer les paiements manuels (espèces)
- Rembourser si nécessaire
- Suivre les statuts de paiement

### Étape 3: Tester la Boutique Utilisateur

URL: `http://localhost/projet%20ismo/shop.html`

**Connexion requise:** Utilisateur (player)

**Parcours utilisateur:**

1. **Navigation** - Parcourir les jeux par catégorie
2. **Sélection** - Cliquer sur un jeu pour voir les détails
3. **Package** - Choisir un package de temps
4. **Paiement** - Sélectionner la méthode de paiement
5. **Confirmation** - Valider l'achat
6. **Points** - Recevoir les points automatiquement (si paiement confirmé)

---

## 🎯 Flux Complet du Système

### Pour l'Utilisateur

```
1. Connexion → shop.html
2. Parcourir les jeux disponibles
3. Cliquer sur un jeu → Voir packages
4. Choisir un package
5. Sélectionner méthode de paiement
6. Confirmer l'achat
   
   SI paiement en ligne (carte, PayPal):
   → Redirection vers provider
   → Callback automatique
   → Points crédités automatiquement
   
   SI paiement sur place (espèces):
   → Statut "En attente"
   → Admin confirme manuellement
   → Points crédités après confirmation

7. Accès "Mes Achats" pour voir l'historique
8. Session de jeu créée automatiquement
```

### Pour l'Admin

```
1. Connexion → admin/game_shop_manager.html

Configuration initiale:
2. Ajouter/Configurer les jeux
3. Créer les packages pour chaque jeu
4. Configurer les méthodes de paiement

Gestion quotidienne:
5. Surveiller les achats (onglet Achats)
6. Confirmer les paiements en espèces
7. Gérer les sessions de jeu actives
8. Consulter les statistiques
```

---

## 📊 Exemples de Configuration

### Exemple 1: Jeu avec Packages Simples

**Jeu:** FIFA 2024
- Points/heure: 15
- Prix de base: 5.00 XOF/h

**Packages:**
1. "30 minutes" - 2.50 XOF - 8 points
2. "1 heure" - 5.00 XOF - 15 points (POPULAIRE)
3. "3 heures" - 12.00 XOF - 50 points (PROMO -20%)

### Exemple 2: Jeu Premium VR

**Jeu:** Beat Saber VR
- Points/heure: 25
- Prix de base: 7.00 XOF/h

**Packages:**
1. "15 minutes" - 4.00 XOF - 7 points (Découverte)
2. "30 minutes" - 7.00 XOF - 13 points
3. "1 heure" - 12.00 XOF - 30 points (Bonus x1.2)

### Exemple 3: Configuration Méthode de Paiement

**Espèces (Cash):**
- Requires online: Non
- Auto confirm: Non
- Instructions: "Payez à la réception. Montrez cette commande à l'accueil."

**Mobile Money MTN:**
- Requires online: Oui
- Auto confirm: Non
- Provider: mtn
- Instructions: "Composez *133# et suivez les instructions"

---

## 🔧 APIs Disponibles

### APIs Admin (Require role: admin)

```
GET/POST/PUT/DELETE  /api/admin/games.php
GET/POST/PUT/DELETE  /api/admin/game_packages.php
GET/POST/PUT/DELETE  /api/admin/payment_methods.php
GET/PATCH            /api/admin/purchases.php
```

### APIs Utilisateur (Require auth)

```
GET   /api/shop/games.php?id={id}           # Voir jeux/packages
POST  /api/shop/create_purchase.php         # Créer achat
GET   /api/shop/my_purchases.php            # Mes achats
GET   /api/shop/game_sessions.php           # Mes sessions
PATCH /api/shop/game_sessions.php           # Gérer session (start/pause/resume)
```

### APIs Publiques

```
GET   /api/shop/payment_methods.php         # Méthodes disponibles
POST  /api/shop/payment_callback.php        # Callback providers
```

---

## 💡 Fonctionnalités Avancées

### 1. Système de Points Automatique

Lors de la confirmation d'un achat:
- ✅ Points ajoutés au compte utilisateur
- ✅ Transaction enregistrée dans `points_transactions`
- ✅ Utilisateur notifié

### 2. Gestion des Sessions de Jeu

Une fois l'achat confirmé:
- Session créée avec temps total alloué
- États: pending → active → paused → completed
- Historique complet des activités
- Expiration après 30 jours si non utilisé

### 3. Système de Promotions

L'admin peut créer:
- **Prix barrés** (original_price)
- **Labels promo** ("PROMO -20%", "BEST VALUE")
- **Bonus multiplicateurs** (points x1.5)
- **Limites d'achat** par utilisateur
- **Périodes de disponibilité** (available_from/until)

### 4. Méthodes de Paiement Flexibles

Support intégré pour:
- **Paiements sur place** (espèces) - Confirmation manuelle admin
- **Paiements en ligne** - Callback automatique
- **Frais personnalisables** (pourcentage + fixe)
- **Instructions personnalisées** par méthode

---

## 🔒 Sécurité

✅ Authentification requise pour tous les achats
✅ Vérification des rôles (admin/player)
✅ Validation des données côté serveur
✅ Protection CSRF avec sessions
✅ Clés API stockées de manière sécurisée
✅ Historique complet des transactions

---

## 📈 Statistiques Disponibles

L'admin peut consulter:
- Total jeux/packages/achats
- Revenus totaux par période
- Jeux les plus populaires
- Méthodes de paiement préférées
- Taux de conversion
- Sessions actives en temps réel

---

## 🐛 Dépannage

### Erreur: "Unauthorized"
➡️ **Solution:** Connectez-vous en tant qu'utilisateur valide

### Les jeux ne s'affichent pas
➡️ **Solution:** Vérifiez que `is_active = 1` dans la base de données

### Paiement non confirmé
➡️ **Solution:** Admin doit confirmer manuellement dans l'onglet "Achats"

### Points non crédités
➡️ **Solution:** Vérifiez le statut du paiement (doit être "completed")

---

## 🎓 Conseils d'Utilisation

### Pour Maximiser les Ventes

1. **Créez des packages attractifs**
   - Offrez des réductions sur les gros packages
   - Utilisez des labels accrocheurs ("POPULAIRE", "MEILLEUR DEAL")
   - Ajoutez des bonus de points

2. **Optimisez les images**
   - Utilisez des images HD de qualité
   - Format recommandé: 800x600px minimum

3. **Proposez plusieurs méthodes de paiement**
   - Au moins une méthode sur place
   - Une méthode en ligne si possible

4. **Suivez vos statistiques**
   - Identifiez les jeux populaires
   - Ajustez les prix selon la demande
   - Créez des promotions ciblées

### Pour une Gestion Efficace

1. **Confirmez rapidement les paiements** en espèces
2. **Répondez aux demandes** dans l'onglet Achats
3. **Mettez à jour** régulièrement le catalogue
4. **Archivez** les anciens jeux plutôt que de les supprimer

---

## 🚀 Prochaines Étapes

Une fois le système installé et testé:

1. ✅ Personnalisez les jeux selon votre arcade
2. ✅ Ajustez les prix selon votre marché local
3. ✅ Configurez vos vraies méthodes de paiement
4. ✅ Testez le parcours complet (achat → confirmation → points)
5. ✅ Formez votre équipe à l'utilisation de l'interface admin
6. ✅ Lancez avec quelques jeux populaires
7. ✅ Collectez les retours utilisateurs
8. ✅ Optimisez et ajoutez plus de jeux

---

## 📞 Support

Pour toute question ou problème:
- Consultez les logs: `error_log` de PHP
- Vérifiez la console navigateur (F12)
- Examinez les requêtes réseau
- Vérifiez les données dans phpMyAdmin

---

## ✨ Résumé

Vous avez maintenant un **système complet et professionnel** de vente de temps de jeu avec:

✅ Catalogue de jeux flexible
✅ Packages personnalisables
✅ Multi-méthodes de paiement
✅ Gestion des points automatique
✅ Suivi des sessions de jeu
✅ Interface admin complète
✅ Interface utilisateur moderne
✅ Système de promotions
✅ Statistiques en temps réel

**Le système est prêt à être utilisé en production !** 🎉
