# 🔄 EXÉCUTION DE LA MIGRATION - GUIDE RAPIDE

## ⚠️ IMPORTANT

Si vous voyez l'erreur "JSON.parse: unexpected character...", c'est que la migration n'a pas encore été exécutée.

## 🚀 Exécuter la Migration (Choisissez UNE méthode)

### ✅ Méthode 1 : Via phpMyAdmin (LE PLUS SIMPLE)

1. **Ouvrir phpMyAdmin**
   - Ouvrir votre navigateur
   - Aller à : `http://localhost/phpmyadmin`

2. **Sélectionner la base de données**
   - Dans la colonne de gauche, cliquer sur `gamezone`

3. **Exécuter la migration**
   - Cliquer sur l'onglet **"SQL"** en haut
   - Copier le code ci-dessous et le coller dans la zone de texte :

```sql
USE `gamezone`;

ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_reason TEXT NULL AFTER status;
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_date DATETIME NULL AFTER deactivation_reason;
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivated_by INT NULL AFTER deactivation_date;
```

4. **Valider**
   - Cliquer sur le bouton **"Exécuter"** (en bas à droite)
   - Vous devriez voir : "3 lignes affectées" ou "La requête a été exécutée avec succès"

5. **Vérifier**
   - Cliquer sur l'onglet **"Structure"**
   - Vérifier que les 3 nouvelles colonnes apparaissent dans la liste

---

### 📋 Méthode 2 : Via PowerShell (Windows XAMPP)

```powershell
# Ouvrir PowerShell dans le dossier du projet
cd "c:\xampp\htdocs\projet ismo"

# Exécuter la migration
& "C:\xampp\mysql\bin\mysql.exe" -u root gamezone -e "ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_reason TEXT NULL AFTER status; ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_date DATETIME NULL AFTER deactivation_reason; ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivated_by INT NULL AFTER deactivation_date;"
```

---

### 🔧 Méthode 3 : Via MySQL Command Line

```bash
# Ouvrir le MySQL Command Line Client
# Ou utiliser le terminal

mysql -u root -p

# Entrer votre mot de passe (par défaut : vide, appuyez sur Entrée)

# Sélectionner la base
USE gamezone;

# Exécuter les commandes
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_reason TEXT NULL AFTER status;
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_date DATETIME NULL AFTER deactivation_reason;
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivated_by INT NULL AFTER deactivation_date;

# Quitter
EXIT;
```

---

## ✅ Vérification de la Migration

### Vérifier que tout fonctionne :

1. **Via phpMyAdmin**
   - Base `gamezone` → Table `users` → Onglet "Structure"
   - Vous devez voir ces 3 nouvelles colonnes :
     - `deactivation_reason` (TEXT)
     - `deactivation_date` (DATETIME)
     - `deactivated_by` (INT)

2. **Via SQL**
```sql
DESCRIBE users;
```

Vous devriez voir quelque chose comme :
```
+---------------------+--------------+------+-----+---------+----------------+
| Field               | Type         | Null | Key | Default | Extra          |
+---------------------+--------------+------+-----+---------+----------------+
| ...                 | ...          | ...  | ... | ...     | ...            |
| status              | enum(...)    | NO   |     | active  |                |
| deactivation_reason | text         | YES  |     | NULL    |                |
| deactivation_date   | datetime     | YES  |     | NULL    |                |
| deactivated_by      | int(11)      | YES  |     | NULL    |                |
| ...                 | ...          | ...  | ... | ...     | ...            |
+---------------------+--------------+------+-----+---------+----------------+
```

---

## 🎯 Que se passe-t-il après la migration ?

### ✅ Nouvelles fonctionnalités activées :

1. **Désactivation avec motif obligatoire**
   - L'admin doit saisir un motif lors de la désactivation
   - Le motif est stocké et affiché à l'utilisateur

2. **Blocage de connexion avec raison**
   - Les utilisateurs désactivés voient pourquoi leur compte est bloqué

3. **Suppression avec traçabilité**
   - Les suppressions sont enregistrées avec leur motif

4. **Réactivation propre**
   - Les champs de désactivation sont automatiquement effacés

---

## 🆘 Problèmes Courants

### "Access denied for user..."
→ Utilisez `root` sans mot de passe (par défaut XAMPP)

### "Table 'gamezone' doesn't exist"
→ Vérifiez que la base de données a été créée. Exécutez d'abord :
```sql
CREATE DATABASE IF NOT EXISTS gamezone;
```

### "Column already exists"
→ Pas de problème ! La migration utilise `IF NOT EXISTS`, elle est idempotente.

### L'erreur JSON persiste
→ Actualisez la page du navigateur (Ctrl+F5) et réessayez

---

## 🔄 Que faire si ça ne fonctionne toujours pas ?

1. **Vérifier que XAMPP est bien démarré**
   - MySQL doit être en vert dans le panneau XAMPP

2. **Vider le cache du navigateur**
   - Ctrl+Shift+Delete → Vider le cache
   - Ou Ctrl+F5 sur la page

3. **Vérifier les logs PHP**
   - Regarder : `C:\xampp\php\logs\php_error_log`

4. **Redémarrer XAMPP**
   - Arrêter MySQL et Apache
   - Les redémarrer

---

## 💡 Astuce

Pour éviter tout problème, exécutez la migration **MAINTENANT** avant de continuer à utiliser l'application.

La migration est **sûre** et **réversible**. Elle n'affecte pas les données existantes.

---

## 🎉 C'est Terminé !

Après avoir exécuté la migration, toutes les fonctionnalités de gestion des utilisateurs fonctionneront correctement :

✅ Désactivation avec motif  
✅ Suppression avec motif  
✅ Blocage de connexion avec raison  
✅ Réactivation  
✅ Traçabilité complète  

Vous pouvez maintenant utiliser l'application normalement !
