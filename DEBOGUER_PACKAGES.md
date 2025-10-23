# 🐛 Guide de Débogage - Problèmes avec les Packages

## 🔍 Diagnostics Rapides

### Vérification 1 : Console du Navigateur
1. Ouvrir la page avec le problème
2. Appuyer sur **F12** (ou Cmd+Option+I sur Mac)
3. Aller dans l'onglet **Console**
4. Noter les erreurs en rouge

### Vérification 2 : Onglet Network
1. F12 → Onglet **Network**
2. Actualiser la page (Ctrl+R)
3. Chercher les requêtes en **rouge** (erreur 404, 500, etc.)
4. Cliquer sur la requête → Onglet **Response**

---

## 🎮 Problème : Packages de Jeu (Admin)

### Symptômes
- ❌ Impossible de créer un package
- ❌ Impossible de modifier un package
- ❌ Impossible de supprimer un package
- ❌ Les changements ne s'enregistrent pas

### Solution 1 : Vérifier les Permissions
```sql
-- Dans phpMyAdmin
SELECT * FROM users WHERE id = [VOTRE_ID];
-- Vérifier que role = 'admin'
```

### Solution 2 : Vérifier l'API Backend
```bash
# Tester l'API de packages
curl http://localhost/projet%20ismo/api/admin/game_packages.php
```

### Solution 3 : Vérifier la Base de Données
```sql
-- Structure de la table game_packages
DESCRIBE game_packages;

-- Lister les packages existants
SELECT id, game_id, name, duration_minutes, price 
FROM game_packages 
ORDER BY created_at DESC 
LIMIT 10;
```

---

## 🛒 Problème : Achat de Package (Joueur)

### Symptômes
- ❌ Bouton "Acheter" ne fonctionne pas
- ❌ Erreur lors du paiement
- ❌ L'achat ne s'enregistre pas

### Solution 1 : Vérifier le Solde de Points
```sql
SELECT id, username, balance_points, balance_amount 
FROM users 
WHERE id = [VOTRE_ID];
```

### Solution 2 : Vérifier l'API d'Achat
Ouvrir la console (F12) et regarder la réponse de :
```
POST /api/shop/purchase_with_package.php
```

### Solution 3 : Vérifier les Logs PHP
```
C:\xampp\apache\logs\error.log
```

---

## 🔄 Problème : Mise à Jour ne s'Affiche Pas

### Symptômes
- ✅ Modification enregistrée dans la base de données
- ❌ Mais ne s'affiche pas sur la page

### Solution : Cache du Navigateur
1. **Hard Refresh** : Ctrl + Shift + R (ou Cmd + Shift + R sur Mac)
2. **Vider le cache** :
   - Chrome : F12 → Network → Cocher "Disable cache"
   - Firefox : F12 → Network → Cocher "Disable cache"

---

## 📊 Script de Debug pour Packages

Créez un fichier `debug_packages.php` :

```php
<?php
require_once __DIR__ . '/api/config.php';

$pdo = get_db();

// Lister tous les packages
$stmt = $pdo->query('
    SELECT p.*, g.name as game_name 
    FROM game_packages p
    INNER JOIN games g ON p.game_id = g.id
    ORDER BY p.created_at DESC
');
$packages = $stmt->fetchAll();

echo "Total packages : " . count($packages) . "\n\n";

foreach ($packages as $pkg) {
    echo "ID: {$pkg['id']} | Jeu: {$pkg['game_name']}\n";
    echo "  Nom: {$pkg['name']}\n";
    echo "  Durée: {$pkg['duration_minutes']} min\n";
    echo "  Prix: {$pkg['price']} {$pkg['currency']}\n";
    echo "  Points: {$pkg['points_reward']}\n";
    echo "  Status: " . ($pkg['is_active'] ? 'Actif' : 'Inactif') . "\n";
    echo str_repeat('-', 50) . "\n";
}
?>
```

Puis exécutez :
```bash
C:\xampp\php\php.exe debug_packages.php
```

---

## ⚠️ Erreurs Courantes

### Erreur : "Column not found"
**Cause** : Nom de colonne incorrect dans la requête SQL
**Solution** : Vérifier la structure de la table

```sql
DESCRIBE game_packages;
DESCRIBE purchases;
```

### Erreur : "Access denied"
**Cause** : Pas connecté ou pas les bonnes permissions
**Solution** :
1. Vérifier la session : Ouvrir F12 → Application → Cookies
2. Vérifier le rôle : Doit être 'admin' pour certaines actions

### Erreur : "CORS policy"
**Cause** : Problème de configuration CORS
**Solution** : Vérifier dans `api/config.php` :
```php
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: http://localhost:4000');
```

---

## 🔧 Checklist de Débogage

### Avant de signaler un bug :

- [ ] J'ai actualisé la page (Ctrl + F5)
- [ ] J'ai vérifié la console du navigateur (F12)
- [ ] J'ai vérifié l'onglet Network pour les erreurs API
- [ ] J'ai vérifié que je suis bien connecté
- [ ] J'ai vérifié mes permissions (admin/player)
- [ ] J'ai vérifié les logs PHP
- [ ] J'ai testé dans un autre navigateur
- [ ] J'ai redémarré le serveur React si nécessaire

---

## 📞 Informations à Fournir pour le Support

Quand vous signalez un problème, fournissez :

1. **Action effectuée** : "J'ai essayé de créer un package"
2. **Résultat attendu** : "Le package devrait s'enregistrer"
3. **Résultat obtenu** : "Message d'erreur X"
4. **Erreur console** : Screenshot de F12 → Console
5. **Erreur Network** : Screenshot de F12 → Network
6. **Navigateur** : Chrome/Firefox/Edge + version

---

## 🚀 Solutions Rapides

### Problème : Rien ne s'affiche
```bash
# 1. Vérifier que le serveur React tourne
# Doit afficher : http://localhost:4000

# 2. Vérifier que XAMPP Apache tourne
# http://localhost/dashboard devrait fonctionner

# 3. Vérifier que MySQL tourne
# phpMyAdmin devrait être accessible
```

### Problème : Modifications ne s'enregistrent pas
```sql
-- Vérifier les transactions en cours
SHOW PROCESSLIST;

-- Vérifier les dernières modifications
SELECT * FROM game_packages 
ORDER BY updated_at DESC 
LIMIT 5;
```

### Problème : Erreur 500
```bash
# Consulter les logs Apache
tail -f C:\xampp\apache\logs\error.log
```
