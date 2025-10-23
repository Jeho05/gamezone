# 🔄 Migration: Motifs de Désactivation/Suppression

## Vue d'ensemble

Cette migration ajoute les fonctionnalités nécessaires pour exiger et afficher les motifs de désactivation et de suppression de comptes utilisateurs.

## ⚠️ IMPORTANT : Migration de la Base de Données

Avant d'utiliser les nouvelles fonctionnalités, vous **DEVEZ** exécuter la migration SQL pour ajouter les nouveaux champs à la table `users`.

### Option 1: Via phpMyAdmin (Recommandé)

1. Ouvrir phpMyAdmin dans votre navigateur : `http://localhost/phpmyadmin`
2. Sélectionner la base de données `gamezone`
3. Cliquer sur l'onglet "SQL"
4. Copier et coller le contenu du fichier `api/add_deactivation_reason.sql`
5. Cliquer sur "Exécuter"

### Option 2: Via MySQL en ligne de commande

```bash
# Depuis le répertoire du projet
mysql -u root -p gamezone < api/add_deactivation_reason.sql
```

### Option 3: Via PowerShell (Windows avec XAMPP)

```powershell
# Depuis le répertoire du projet
& "C:\xampp\mysql\bin\mysql.exe" -u root gamezone -e "SOURCE api/add_deactivation_reason.sql"
```

## 📋 Nouveaux Champs Ajoutés

La migration ajoute 3 nouveaux champs à la table `users` :

| Champ | Type | Description |
|-------|------|-------------|
| `deactivation_reason` | TEXT NULL | Motif de désactivation (affiché à l'utilisateur lors de la connexion) |
| `deactivation_date` | DATETIME NULL | Date et heure de désactivation |
| `deactivated_by` | INT NULL | ID de l'admin qui a désactivé le compte |

## 🆕 Nouvelles Fonctionnalités

### 1. Désactivation avec Motif Obligatoire

**Côté Admin :**
- Lors de la désactivation d'un compte, l'admin **DOIT** fournir un motif
- Le motif est stocké dans la base de données
- Le compte est automatiquement déconnecté et les points réinitialisés à 0

**Côté Utilisateur:**
- Si l'utilisateur désactivé tente de se connecter, il voit :
  ```
  Votre compte a été désactivé.
  
  Motif: [Le motif saisi par l'admin]
  
  Date de désactivation: [Date et heure]
  
  Veuillez contacter un administrateur pour plus d'informations.
  ```

### 2. Suppression avec Motif Obligatoire

**Côté Admin :**
- Lors de la suppression d'un compte, l'admin **DOIT** fournir un motif
- Le motif est stocké dans une table d'audit `deleted_users`
- La suppression est traçable

**Table `deleted_users` (créée automatiquement) :**
```sql
CREATE TABLE deleted_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL,
    deletion_reason TEXT NOT NULL,
    deleted_by INT NOT NULL,
    deleted_at DATETIME NOT NULL
)
```

### 3. Réactivation

- Lors de la réactivation d'un compte, les champs de désactivation sont **automatiquement effacés**
- L'utilisateur peut à nouveau se connecter
- **Note :** Les points ne sont PAS restaurés

## 🔒 Sécurité et Validation

### Backend (API)

**`api/auth/login.php`** - Vérifie le statut avant connexion :
- ✅ Bloque les comptes `status = 'inactive'`
- ✅ Retourne le motif de désactivation
- ✅ Retourne la date de désactivation
- ✅ Code HTTP 403 (Forbidden)

**`api/users/item.php`** - Modifications :
- ✅ Requiert `deactivation_reason` pour désactiver un compte (erreur 400 si vide)
- ✅ Requiert `deletion_reason` pour supprimer un compte (erreur 400 si vide)
- ✅ Stocke automatiquement la date et l'ID de l'admin
- ✅ Efface les champs de désactivation lors de la réactivation

### Frontend

**Page de login** (`auth/login/page.jsx`) :
- ✅ Affiche le message complet avec retours à la ligne
- ✅ Gestion spéciale pour les comptes désactivés
- ✅ Message d'erreur avec `whitespace-pre-wrap` pour formater correctement

**Page de profil admin** (`admin/players/[id]/page.jsx`) :
- ✅ Champ textarea obligatoire pour le motif de désactivation
- ✅ Champ textarea obligatoire pour le motif de suppression
- ✅ Validation côté client avant envoi
- ✅ Placeholder explicatif pour guider l'admin

## 📝 Exemples d'Utilisation

### Exemple 1: Désactiver un compte

1. Admin clique sur "Désactiver" sur le profil du joueur
2. Une modale s'ouvre avec un champ "Motif de désactivation"
3. Admin saisit par exemple : "Utilisation de langage inapproprié de manière répétée"
4. Admin clique sur "Confirmer"
5. Le compte est désactivé, les points sont à 0

### Exemple 2: Utilisateur désactivé tente de se connecter

**Ce que voit l'utilisateur :**
```
Votre compte a été désactivé.

Motif: Utilisation de langage inapproprié de manière répétée

Date de désactivation: 14/10/2025 à 20:55

Veuillez contacter un administrateur pour plus d'informations.
```

### Exemple 3: Supprimer un compte

1. Admin clique sur "Supprimer" sur le profil du joueur
2. Une modale s'ouvre avec un champ "Motif de suppression"
3. Admin saisit par exemple : "Compte inactif depuis plus de 2 ans - demande de l'utilisateur"
4. Admin clique sur "Supprimer définitivement"
5. Le compte est supprimé et une trace est conservée dans `deleted_users`

## 🧪 Tests à Effectuer

### Test 1: Migration de la base de données
```sql
-- Vérifier que les colonnes ont été ajoutées
DESCRIBE users;
-- Vous devriez voir: deactivation_reason, deactivation_date, deactivated_by
```

### Test 2: Désactivation avec motif
1. Se connecter en tant qu'admin
2. Aller sur le profil d'un joueur
3. Cliquer sur "Désactiver"
4. Laisser le motif vide → Doit afficher "Le motif de désactivation est obligatoire"
5. Saisir un motif → Doit fonctionner

### Test 3: Connexion compte désactivé
1. Désactiver un compte avec un motif spécifique
2. Se déconnecter
3. Tenter de se connecter avec ce compte
4. Vérifier que le motif s'affiche correctement

### Test 4: Réactivation
1. Réactiver un compte désactivé
2. Vérifier en base que `deactivation_reason`, `deactivation_date`, et `deactivated_by` sont NULL
3. Tenter de se connecter → Doit fonctionner

### Test 5: Suppression avec motif
1. Tenter de supprimer sans motif → Erreur
2. Supprimer avec motif
3. Vérifier la table `deleted_users` :
```sql
SELECT * FROM deleted_users ORDER BY deleted_at DESC LIMIT 1;
```

## 🔍 Traçabilité

### Où trouver les informations

**Compte désactivé :**
```sql
SELECT 
    id,
    username,
    email,
    status,
    deactivation_reason,
    deactivation_date,
    deactivated_by
FROM users
WHERE status = 'inactive';
```

**Comptes supprimés :**
```sql
SELECT * FROM deleted_users
ORDER BY deleted_at DESC;
```

**Historique des points (désactivations) :**
```sql
SELECT 
    user_id,
    change_amount,
    reason,
    admin_id,
    created_at
FROM points_transactions
WHERE reason LIKE 'Compte désactivé%'
ORDER BY created_at DESC;
```

## ⚡ Rollback (Annuler la migration)

Si vous devez annuler la migration :

```sql
USE gamezone;

-- Supprimer les colonnes ajoutées
ALTER TABLE users DROP COLUMN IF EXISTS deactivation_reason;
ALTER TABLE users DROP COLUMN IF EXISTS deactivation_date;
ALTER TABLE users DROP COLUMN IF EXISTS deactivated_by;

-- Supprimer la table d'audit (optionnel)
DROP TABLE IF EXISTS deleted_users;
```

⚠️ **Attention :** Cette action supprimera tous les motifs de désactivation stockés !

## 📞 Support

En cas de problème :
1. Vérifier que la migration SQL a bien été exécutée
2. Vérifier les logs d'erreur PHP (xampp/php/logs)
3. Vérifier la console du navigateur (F12)
4. Consulter la documentation dans `SYSTEME_SANCTIONS.md`
