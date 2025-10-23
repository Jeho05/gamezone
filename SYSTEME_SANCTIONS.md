# 🚨 Système de Sanctions - Guide Administrateur

## Vue d'ensemble

Le système de sanctions permet aux administrateurs de gérer les comportements inappropriés des joueurs en appliquant des pénalités de points ou en désactivant/supprimant des comptes.

## 🔒 Règles de Sécurité

### Protection du compte administrateur
- **Un administrateur NE PEUT PAS :**
  - Désactiver son propre compte
  - Supprimer son propre compte
  - S'appliquer des sanctions à lui-même
  
- **Lorsqu'un admin consulte son propre profil :**
  - Tous les boutons d'action (Sanctionner, Désactiver, Supprimer) sont **automatiquement cachés**
  - Seul le bouton "Retour aux joueurs" est visible

## 📋 Types de Sanctions Disponibles

| Type de Sanction | Points Déduits | Description |
|-----------------|----------------|-------------|
| **Avertissement** | -50 | Comportement inapproprié léger |
| **Infraction mineure** | -100 | Langage inapproprié, non-respect des règles mineures |
| **Infraction majeure** | -250 | Triche, comportement abusif |
| **Triche détectée** | -500 | Utilisation de logiciels de triche ou exploitation de bugs |
| **Harcèlement** | -400 | Harcèlement d'autres joueurs |
| **Partage de compte** | -200 | Partage de compte non autorisé |
| **Spam** | -75 | Messages répétitifs ou non sollicités |
| **Sanction personnalisée** | -100 (modifiable) | Raison définie par l'administrateur |

## ⚠️ Désactivation de Compte

### Conséquences de la désactivation :
1. **Réinitialisation automatique des points à 0**
2. L'utilisateur ne peut plus se connecter
3. Une entrée est créée dans l'historique des points avec la raison : "Compte désactivé - Sanction administrative"

### Avertissement affiché :
> ⚠️ **Attention :** La désactivation réinitialisera automatiquement tous les points de l'utilisateur à 0.

### Notes importantes :
- Les points **ne sont PAS restaurés** lors de la réactivation
- C'est une sanction **permanente** sur les points
- À utiliser pour des infractions graves

## 🗑️ Suppression de Compte

### Conséquences :
- **Action IRRÉVERSIBLE**
- Suppression définitive de l'utilisateur
- Toutes les données associées sont perdues (grâce aux contraintes CASCADE en base de données)
- Redirection automatique vers la liste des joueurs après suppression

## 💡 Comment Appliquer une Sanction

### Étape 1 : Accéder au profil
1. Aller dans **Admin → Gestion des Joueurs**
2. Cliquer sur l'icône "👁️ Œil" pour voir le profil du joueur

### Étape 2 : Choisir le type de sanction
1. Cliquer sur le bouton jaune **"Sanctionner"**
2. Sélectionner le type de sanction dans le menu déroulant
3. (Optionnel) Ajouter une raison personnalisée

### Étape 3 : Valider
1. Vérifier les informations affichées (description et points à déduire)
2. Cliquer sur **"Appliquer la sanction"**
3. Une confirmation s'affiche avec le résumé de la sanction

## 📊 Traçabilité

Toutes les sanctions sont enregistrées dans la table `points_transactions` avec :
- L'ID de l'utilisateur sanctionné
- Le montant de points déduits (négatif)
- La raison complète : `"SANCTION: [Type] - [Description]"`
- Le type : `adjustment`
- L'ID de l'administrateur qui a appliqué la sanction
- La date et l'heure

### Exemple d'entrée dans l'historique :
```
SANCTION: Triche détectée - Utilisation de logiciels de triche
Points: -500
Type: adjustment
Admin ID: 1
Date: 2025-01-14 20:15:00
```

## 🎯 Bonnes Pratiques

1. **Gradualité** : Commencer par un avertissement avant d'appliquer des sanctions plus lourdes
2. **Documentation** : Toujours ajouter une raison personnalisée pour les sanctions importantes
3. **Vérification** : S'assurer que le joueur mérite la sanction avant de l'appliquer
4. **Communication** : Informer le joueur de la raison de sa sanction (en dehors du système)

## 🔧 API Endpoints

### Appliquer une sanction
```
POST /api/users/sanction.php
```

**Body:**
```json
{
  "user_id": 123,
  "sanction_type": "cheating",
  "reason": "Utilisation de logiciels de triche détectée lors du tournoi",
  "custom_points": -100  // Optionnel, uniquement pour "custom"
}
```

**Réponse:**
```json
{
  "message": "Sanction appliquée avec succès",
  "sanction": {
    "type": "Triche détectée",
    "points_deducted": 500,
    "previous_points": 1250,
    "new_points": 750,
    "description": "Utilisation de logiciels de triche détectée lors du tournoi"
  }
}
```

### Modifier le statut (désactiver/réactiver)
```
PUT /api/users/item.php?id=123
```

**Body:**
```json
{
  "status": "inactive"  // ou "active"
}
```

**Comportement spécial:**
- Si `status: "inactive"` → Points automatiquement mis à 0
- Entrée créée dans `points_transactions` avec la raison "Compte désactivé - Sanction administrative"

### Supprimer un utilisateur
```
DELETE /api/users/item.php?id=123
```

**Sécurité:**
- Retourne une erreur 403 si l'admin essaie de supprimer son propre compte

## 📱 Interface Utilisateur

### Zone d'information (bandeau bleu)
Affichée uniquement si l'admin consulte le profil d'un **autre** utilisateur :

> ℹ️ **Note pour l'administrateur :**
> Vous pouvez appliquer des sanctions qui réduiront les points du joueur selon la gravité de l'infraction. 
> **Désactiver un compte réinitialise automatiquement tous les points à 0.**
> Les sanctions disponibles incluent : avertissement (-50 pts), infractions mineures/majeures, triche, 
> harcèlement, partage de compte, spam, ou une sanction personnalisée.

### Boutons d'action
- 🟡 **Sanctionner** : Ouvre la modale de sanction
- 🟠 **Désactiver** : Désactive le compte (visible si actif)
- 🟢 **Réactiver** : Réactive le compte (visible si inactif)
- 🔴 **Supprimer** : Supprime définitivement

## ✅ Vérifications Effectuées

### Côté Backend (API)
- ✅ Empêche l'admin de modifier son propre compte
- ✅ Empêche l'admin de se sanctionner lui-même
- ✅ Empêche l'admin de supprimer son propre compte
- ✅ Validation des types de sanctions
- ✅ Enregistrement dans l'historique

### Côté Frontend
- ✅ Cache les boutons d'action sur son propre profil
- ✅ Affiche des avertissements clairs
- ✅ Confirmation avant chaque action
- ✅ Rafraîchissement automatique des données après sanction
- ✅ Gestion des états de chargement

## 🛡️ Sécurité Additionnelle

Toutes les actions nécessitent :
1. Une session active
2. Le rôle `admin`
3. Des credentials valides (envoi avec `credentials: 'include'`)

Les tentatives d'actions interdites retournent des erreurs HTTP appropriées :
- `401 Unauthorized` : Session invalide
- `403 Forbidden` : Tentative d'action sur son propre compte
- `404 Not Found` : Utilisateur inexistant
- `400 Bad Request` : Données invalides
